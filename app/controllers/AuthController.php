<?php
/**
 * FrigoTIC - Controlador de Autenticación
 * 
 * Implementa medidas de seguridad:
 * - Protección CSRF
 * - Rate Limiting (protección contra fuerza bruta)
 * - Prevención de enumeración de usuarios
 * - Validación de sesión por dominio/fingerprint
 * - Timeout de inactividad
 * 
 * @package FrigoTIC
 * @author MJCRSoftware
 * @version 1.1.2
 */

namespace App\Controllers;

use App\Models\Usuario;

class AuthController
{
    private Usuario $usuarioModel;
    
    // Configuración de Rate Limiting
    private const MAX_LOGIN_ATTEMPTS = 5;
    private const LOCKOUT_TIME = 900; // 15 minutos en segundos
    
    // Mensaje genérico para evitar enumeración de usuarios
    private const GENERIC_LOGIN_ERROR = 'Las credenciales proporcionadas no son válidas';

    /**
     * Constructor con inyección de dependencias
     */
    public function __construct(?Usuario $usuarioModel = null)
    {
        $this->usuarioModel = $usuarioModel ?? new Usuario();
    }

    // =========================================================================
    // MÉTODOS DE PROTECCIÓN CSRF
    // =========================================================================

    /**
     * Generar token CSRF
     */
    public static function generateCsrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Verificar token CSRF
     */
    public static function verifyCsrfToken(?string $token): bool
    {
        if (empty($token) || empty($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Regenerar token CSRF (usar después de acciones críticas)
     */
    public static function regenerateCsrfToken(): string
    {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        return $_SESSION['csrf_token'];
    }

    /**
     * Obtener campo HTML oculto con token CSRF
     */
    public static function csrfField(): string
    {
        $token = self::generateCsrfToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }

    // =========================================================================
    // MÉTODOS DE RATE LIMITING
    // =========================================================================

    /**
     * Obtener clave única para rate limiting basada en IP
     */
    private function getRateLimitKey(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        return 'login_attempts_' . md5($ip);
    }

    /**
     * Verificar si la IP está bloqueada por demasiados intentos
     */
    private function isRateLimited(): bool
    {
        $key = $this->getRateLimitKey();
        $attempts = $_SESSION[$key] ?? ['count' => 0, 'first_attempt' => 0, 'locked_until' => 0];
        
        // Verificar si está bloqueado
        if ($attempts['locked_until'] > time()) {
            return true;
        }
        
        // Resetear si pasó el tiempo de ventana
        if ($attempts['first_attempt'] > 0 && (time() - $attempts['first_attempt']) > self::LOCKOUT_TIME) {
            unset($_SESSION[$key]);
            return false;
        }
        
        return false;
    }

    /**
     * Obtener tiempo restante de bloqueo en segundos
     */
    private function getRateLimitRemainingTime(): int
    {
        $key = $this->getRateLimitKey();
        $attempts = $_SESSION[$key] ?? ['locked_until' => 0];
        
        if ($attempts['locked_until'] > time()) {
            return $attempts['locked_until'] - time();
        }
        
        return 0;
    }

    /**
     * Registrar intento fallido de login
     */
    private function recordFailedAttempt(): void
    {
        $key = $this->getRateLimitKey();
        $attempts = $_SESSION[$key] ?? ['count' => 0, 'first_attempt' => time(), 'locked_until' => 0];
        
        $attempts['count']++;
        
        if ($attempts['count'] >= self::MAX_LOGIN_ATTEMPTS) {
            $attempts['locked_until'] = time() + self::LOCKOUT_TIME;
            $attempts['count'] = 0;
            $attempts['first_attempt'] = 0;
            
            // Log de seguridad
            error_log("FrigoTIC Security: IP bloqueada por múltiples intentos fallidos - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        }
        
        $_SESSION[$key] = $attempts;
    }

    /**
     * Limpiar intentos fallidos tras login exitoso
     */
    private function clearFailedAttempts(): void
    {
        $key = $this->getRateLimitKey();
        unset($_SESSION[$key]);
    }

    // =========================================================================
    // MÉTODOS PRINCIPALES DE AUTENTICACIÓN
    // =========================================================================

    /**
     * Mostrar formulario de login
     */
    public function showLogin(): void
    {
        // Si ya está logueado, redirigir
        if ($this->isLoggedIn()) {
            $this->redirectByRole();
            return;
        }

        // Generar token CSRF para el formulario
        self::generateCsrfToken();

        require_once dirname(__DIR__) . '/views/auth/login.php';
    }

    /**
     * Procesar login
     */
    public function login(): array
    {
        // Verificar Rate Limiting
        if ($this->isRateLimited()) {
            $remaining = $this->getRateLimitRemainingTime();
            $minutes = ceil($remaining / 60);
            return [
                'success' => false, 
                'message' => "Demasiados intentos fallidos. Por favor, espera {$minutes} minuto(s) antes de intentarlo de nuevo."
            ];
        }

        // Verificar token CSRF
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!self::verifyCsrfToken($csrfToken)) {
            return ['success' => false, 'message' => 'Error de seguridad. Por favor, recarga la página e intenta de nuevo.'];
        }

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        // Validaciones básicas
        if (empty($username) || empty($password)) {
            return ['success' => false, 'message' => 'Por favor, completa todos los campos'];
        }

        // Buscar usuario - Verificación unificada para evitar enumeración
        $usuario = $this->usuarioModel->getByUsername($username);
        
        $loginFailed = false;
        
        if (!$usuario) {
            $loginFailed = true;
        } elseif (!$usuario['activo']) {
            $loginFailed = true;
        } elseif (!$this->usuarioModel->verifyPassword($password, $usuario['password_hash'])) {
            $loginFailed = true;
        }

        if ($loginFailed) {
            $this->recordFailedAttempt();
            // Siempre devolver mensaje genérico
            return ['success' => false, 'message' => self::GENERIC_LOGIN_ERROR];
        }

        // Login exitoso - limpiar intentos fallidos
        $this->clearFailedAttempts();

        // Regenerar ID de sesión para prevenir session fixation
        session_regenerate_id(true);

        // Regenerar token CSRF
        self::regenerateCsrfToken();

        // Iniciar sesión
        $this->createSession($usuario);

        // Actualizar último acceso
        $this->usuarioModel->updateLastAccess($usuario['id']);

        // Log de seguridad
        error_log("FrigoTIC: Login exitoso - Usuario: {$usuario['nombre_usuario']} - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));

        // Verificar si debe cambiar contraseña
        if ($usuario['debe_cambiar_password']) {
            return [
                'success' => true,
                'redirect' => 'cambiar-password',
                'message' => 'Debes cambiar tu contraseña'
            ];
        }

        // Determinar redirección según rol
        $redirect = $usuario['rol'] === 'admin' ? 'admin/dashboard' : 'user/productos';

        return ['success' => true, 'redirect' => $redirect];
    }

    /**
     * Cerrar sesión
     */
    public function logout(): void
    {
        // Log de seguridad
        if (isset($_SESSION['username'])) {
            error_log("FrigoTIC: Logout - Usuario: {$_SESSION['username']} - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        }

        // Destruir sesión de forma segura
        $this->destroySession();
        
        // Iniciar nueva sesión limpia para evitar errores
        session_start();
        
        // Redirigir al login
        header('Location: /login');
        exit;
    }

    /**
     * Cambiar contraseña
     */
    public function changePassword(): array
    {
        if (!$this->isLoggedIn()) {
            return ['success' => false, 'message' => 'No has iniciado sesión'];
        }

        // Verificar token CSRF
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!self::verifyCsrfToken($csrfToken)) {
            return ['success' => false, 'message' => 'Error de seguridad. Por favor, recarga la página e intenta de nuevo.'];
        }

        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // Validaciones
        if (empty($newPassword) || empty($confirmPassword)) {
            return ['success' => false, 'message' => 'Por favor, completa todos los campos'];
        }

        if ($newPassword !== $confirmPassword) {
            return ['success' => false, 'message' => 'Las contraseñas no coinciden'];
        }

        // Validar fortaleza de la contraseña
        if (strlen($newPassword) < 6) {
            return ['success' => false, 'message' => 'La contraseña debe tener al menos 6 caracteres'];
        }

        $usuario = $this->usuarioModel->getById($_SESSION['user_id']);

        // Si debe cambiar contraseña obligatoriamente, no verificar la actual
        if (!$usuario['debe_cambiar_password']) {
            if (empty($currentPassword)) {
                return ['success' => false, 'message' => 'Ingresa tu contraseña actual'];
            }

            if (!$this->usuarioModel->verifyPassword($currentPassword, $usuario['password_hash'])) {
                return ['success' => false, 'message' => 'La contraseña actual es incorrecta'];
            }
        }

        // Actualizar contraseña
        $result = $this->usuarioModel->updatePassword($usuario['id'], $newPassword, false);

        if ($result) {
            // Regenerar token CSRF tras acción crítica
            self::regenerateCsrfToken();
            
            // Log de seguridad
            error_log("FrigoTIC: Cambio de contraseña - Usuario: {$_SESSION['username']} - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
            
            return ['success' => true, 'message' => 'Contraseña actualizada correctamente'];
        }

        return ['success' => false, 'message' => 'Error al actualizar la contraseña'];
    }

    /**
     * Crear sesión de usuario
     */
    private function createSession(array $usuario): void
    {
        $_SESSION['user_id'] = $usuario['id'];
        $_SESSION['username'] = $usuario['nombre_usuario'];
        $_SESSION['user_role'] = $usuario['rol'];
        $_SESSION['user_name'] = $usuario['nombre_completo'] ?? $usuario['nombre_usuario'];
        $_SESSION['user_email'] = $usuario['email'];
        $_SESSION['must_change_password'] = (bool) $usuario['debe_cambiar_password'];
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        
        // Seguridad: guardar fingerprint del navegador y dominio
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';
        $_SESSION['domain'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
    }

    /**
     * Verificar si hay sesión activa
     */
    public function isLoggedIn(): bool
    {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
            return false;
        }
        
        // Verificar que la sesión es del mismo dominio
        $currentDomain = $_SERVER['HTTP_HOST'] ?? 'localhost';
        if (isset($_SESSION['domain']) && $_SESSION['domain'] !== $currentDomain) {
            // Sesión de otro dominio, invalidar
            $this->destroySession();
            session_start();
            return false;
        }
        
        // Verificar fingerprint del navegador
        $currentUserAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if (isset($_SESSION['user_agent']) && $_SESSION['user_agent'] !== $currentUserAgent) {
            // User agent diferente, posible robo de sesión
            error_log("FrigoTIC Security: Posible robo de sesión detectado - User-Agent diferente - Usuario: " . ($_SESSION['username'] ?? 'unknown'));
            $this->destroySession();
            session_start();
            return false;
        }
        
        // Verificar timeout de inactividad (15 minutos)
        $timeout = 15 * 60; // 15 minutos
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
            error_log("FrigoTIC: Sesión expirada por inactividad - Usuario: " . ($_SESSION['username'] ?? 'unknown'));
            $this->destroySession();
            session_start();
            return false;
        }
        
        // Actualizar tiempo de última actividad
        $_SESSION['last_activity'] = time();
        
        return true;
    }
    
    /**
     * Destruir sesión de forma segura
     */
    private function destroySession(): void
    {
        $_SESSION = [];
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    /**
     * Verificar si el usuario es admin
     */
    public function isAdmin(): bool
    {
        return $this->isLoggedIn() && ($_SESSION['user_role'] ?? '') === 'admin';
    }

    /**
     * Verificar si debe cambiar contraseña
     */
    public function mustChangePassword(): bool
    {
        return $this->isLoggedIn() && ($_SESSION['must_change_password'] ?? false);
    }

    /**
     * Redirigir según rol
     */
    private function redirectByRole(): void
    {
        if ($this->mustChangePassword()) {
            header('Location: /cambiar-password');
        } elseif ($this->isAdmin()) {
            header('Location: /admin/dashboard');
        } else {
            header('Location: /user/productos');
        }
        exit;
    }

    /**
     * Requerir autenticación
     */
    public function requireAuth(): void
    {
        if (!$this->isLoggedIn()) {
            header('Location: /login');
            exit;
        }

        if ($this->mustChangePassword() && !$this->isChangePasswordPage()) {
            header('Location: /cambiar-password');
            exit;
        }
    }

    /**
     * Requerir rol de administrador
     */
    public function requireAdmin(): void
    {
        $this->requireAuth();

        if (!$this->isAdmin()) {
            header('Location: /user/productos');
            exit;
        }
    }

    /**
     * Verificar si es la página de cambio de contraseña
     * Usa comparación exacta para evitar falsos positivos
     */
    private function isChangePasswordPage(): bool
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        $uri = trim($uri, '/');
        
        // Comparación exacta de la ruta
        return $uri === 'cambiar-password';
    }

    /**
     * Obtener datos del usuario actual
     */
    public function getCurrentUser(): ?array
    {
        if (!$this->isLoggedIn()) {
            return null;
        }

        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'role' => $_SESSION['user_role'],
            'name' => $_SESSION['user_name'],
            'email' => $_SESSION['user_email']
        ];
    }
}
