<?php
/**
 * FrigoTIC - Punto de Entrada Principal
 * 
 * @package FrigoTIC
 * @author MJCRSoftware
 * @version 1.0.0
 */

// Reportar errores en desarrollo
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Definir constantes
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('PUBLIC_PATH', __DIR__);

// Cargar configuración
$appConfig = require APP_PATH . '/config/app.php';

// Configurar sesión para que expire al cerrar navegador
session_name($appConfig['session']['name']);
session_set_cookie_params([
    'lifetime' => 0,  // Expira al cerrar navegador
    'path' => '/',
    'secure' => $appConfig['session']['secure'],
    'httponly' => $appConfig['session']['httponly'],
    'samesite' => 'Lax'
]);
session_start();

// Cargar helpers
require_once APP_PATH . '/helpers/functions.php';

// Autoload simple para clases
spl_autoload_register(function ($class) {
    // Convertir namespace a ruta de archivo
    $prefix = 'App\\';
    $baseDir = APP_PATH . '/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    
    // Convertir a minúsculas las carpetas
    $parts = explode('/', str_replace('\\', '/', $relativeClass));
    $className = array_pop($parts);
    $path = $baseDir . strtolower(implode('/', $parts)) . '/' . $className . '.php';

    if (file_exists($path)) {
        require $path;
    } elseif (file_exists($file)) {
        require $file;
    }
});

// Router simple
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
// Eliminar la base del path si existe (por compatibilidad con subdirectorios)
$scriptName = dirname($_SERVER['SCRIPT_NAME']);
if ($scriptName !== '/') {
    $uri = str_replace($scriptName, '', $uri);
}
$uri = trim($uri, '/');

// Instanciar controlador de autenticación
$auth = new \App\Controllers\AuthController();

// Rutas
switch ($uri) {
    case '':
    case 'login':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $auth->login();
            if (isAjax()) {
                jsonResponse($result);
            }
            if ($result['success']) {
                header('Location: /' . $result['redirect']);
                exit;
            }
            $_SESSION['login_error'] = $result['message'];
        }
        $auth->showLogin();
        break;

    case 'logout':
        $auth->logout();
        break;

    case 'cambiar-password':
        $auth->requireAuth();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $result = $auth->changePassword();
            if (isAjax()) {
                jsonResponse($result);
            }
            if ($result['success']) {
                $_SESSION['must_change_password'] = false;
                setFlash('success', $result['message']);
                $redirect = $_SESSION['user_role'] === 'admin' ? 'admin/dashboard' : 'user/productos';
                header('Location: /' . $redirect);
                exit;
            }
            $_SESSION['change_password_error'] = $result['message'];
        }
        require APP_PATH . '/views/auth/change-password.php';
        break;

    // Rutas de usuario
    case 'user/productos':
        $auth->requireAuth();
        require APP_PATH . '/views/user/productos.php';
        break;

    case 'user/movimientos':
        $auth->requireAuth();
        require APP_PATH . '/views/user/movimientos.php';
        break;

    case 'user/perfil':
        $auth->requireAuth();
        require APP_PATH . '/views/user/perfil.php';
        break;

    // Rutas de administrador
    case 'admin/dashboard':
        $auth->requireAdmin();
        require APP_PATH . '/views/admin/dashboard.php';
        break;

    case 'admin/usuarios':
        $auth->requireAdmin();
        require APP_PATH . '/views/admin/usuarios.php';
        break;

    case 'admin/productos':
        $auth->requireAdmin();
        require APP_PATH . '/views/admin/productos.php';
        break;

    case 'admin/facturas':
        $auth->requireAdmin();
        require APP_PATH . '/views/admin/facturas.php';
        break;

    case 'admin/movimientos':
        $auth->requireAdmin();
        require APP_PATH . '/views/admin/movimientos.php';
        break;

    case 'admin/graficos':
        $auth->requireAdmin();
        require APP_PATH . '/views/admin/graficos.php';
        break;

    case 'admin/configuracion':
        $auth->requireAdmin();
        require APP_PATH . '/views/admin/configuracion.php';
        break;

    case 'admin/correos':
        $auth->requireAdmin();
        require APP_PATH . '/views/admin/correos.php';
        break;

    // API endpoints
    case 'api/consumo':
        $auth->requireAuth();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $movimiento = new \App\Models\Movimiento();
                $id = $movimiento->registrarConsumo(
                    $_SESSION['user_id'],
                    (int) $_POST['producto_id'],
                    (int) ($_POST['cantidad'] ?? 1)
                );
                jsonResponse(['success' => true, 'id' => $id]);
            } catch (\Exception $e) {
                jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
            }
        }
        break;

    case 'api/pago':
        $auth->requireAdmin();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $movimiento = new \App\Models\Movimiento();
                $id = $movimiento->registrarPago(
                    (int) $_POST['usuario_id'],
                    (float) $_POST['cantidad'],
                    $_POST['descripcion'] ?? ''
                );
                jsonResponse(['success' => true, 'id' => $id]);
            } catch (\Exception $e) {
                jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
            }
        }
        break;

    // Ayuda
    case 'ayuda/usuario':
        $auth->requireAuth();
        require APP_PATH . '/views/partials/ayuda-usuario.php';
        break;

    case 'ayuda/admin':
        $auth->requireAdmin();
        require APP_PATH . '/views/partials/ayuda-admin.php';
        break;

    // Exportación PDF
    case 'export':
        $auth->requireAuth();
        require PUBLIC_PATH . '/export.php';
        break;

    default:
        http_response_code(404);
        require APP_PATH . '/views/errors/404.php';
        break;
}
