<?php
/**
 * FrigoTIC - Controlador de Autenticación
 * 
 * @package FrigoTIC
 * @author MJCRSoftware
 * @version 1.0.0
 */

namespace App\Controllers;

use App\Models\Usuario;

class AuthController
{
    private Usuario $usuarioModel;

    public function __construct()
    {
        $this->usuarioModel = new Usuario();
    }

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

        require_once dirname(__DIR__) . '/views/auth/login.php';
    }

    /**
     * Procesar login
     */
    public function login(): array
    {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        // Validaciones básicas
        if (empty($username) || empty($password)) {
            return ['success' => false, 'message' => 'Por favor, completa todos los campos'];
        }

        // Buscar usuario
        $usuario = $this->usuarioModel->getByUsername($username);

        if (!$usuario) {
            return ['success' => false, 'message' => 'Usuario o contraseña incorrectos'];
        }

        // Verificar si está activo
        if (!$usuario['activo']) {
            return ['success' => false, 'message' => 'Tu cuenta está desactivada. Contacta al administrador'];
        }

        // Verificar contraseña
        if (!$this->usuarioModel->verifyPassword($password, $usuario['password_hash'])) {
            return ['success' => false, 'message' => 'Usuario o contraseña incorrectos'];
        }

        // Iniciar sesión
        $this->createSession($usuario);

        // Actualizar último acceso
        $this->usuarioModel->updateLastAccess($usuario['id']);

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
        // Destruir sesión
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
        
        session_destroy();
        
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
    }

    /**
     * Verificar si hay sesión activa
     */
    public function isLoggedIn(): bool
    {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
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
            header('Location: /frigotic/cambiar-password');
        } elseif ($this->isAdmin()) {
            header('Location: /frigotic/admin/dashboard');
        } else {
            header('Location: /frigotic/user/productos');
        }
        exit;
    }

    /**
     * Requerir autenticación
     */
    public function requireAuth(): void
    {
        if (!$this->isLoggedIn()) {
            header('Location: /frigotic/login');
            exit;
        }

        if ($this->mustChangePassword() && !$this->isChangePasswordPage()) {
            header('Location: /frigotic/cambiar-password');
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
            header('Location: /frigotic/user/productos');
            exit;
        }
    }

    /**
     * Verificar si es la página de cambio de contraseña
     */
    private function isChangePasswordPage(): bool
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        return strpos($uri, 'cambiar-password') !== false;
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
