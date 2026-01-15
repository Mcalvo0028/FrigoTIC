<?php
/**
 * FrigoTIC - Configuración (Admin)
 */

$pageTitle = 'Configuración';

require_once APP_PATH . '/helpers/EnvHelper.php';
require_once APP_PATH . '/models/Database.php';
require_once APP_PATH . '/models/Configuracion.php';
require_once APP_PATH . '/models/Usuario.php';

use App\Helpers\EnvHelper;
use App\Models\Configuracion;
use App\Models\Usuario;

EnvHelper::load();

$configModel = new Configuracion();
$usuarioModel = new Usuario();

// Procesar acciones
$message = null;
$messageType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'change_password':
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            $admin = $usuarioModel->getById($_SESSION['user_id']);
            
            if (!$usuarioModel->verifyPassword($currentPassword, $admin['password_hash'])) {
                $message = 'Contraseña actual incorrecta';
                $messageType = 'danger';
            } elseif ($newPassword !== $confirmPassword) {
                $message = 'Las contraseñas no coinciden';
                $messageType = 'danger';
            } else {
                $usuarioModel->updatePassword($_SESSION['user_id'], $newPassword);
                $message = 'Contraseña actualizada correctamente';
                $messageType = 'success';
            }
            break;
            
        case 'save_database':
            $dbConfig = [
                'host' => trim($_POST['db_host'] ?? 'localhost'),
                'port' => trim($_POST['db_port'] ?? '3306'),
                'name' => trim($_POST['db_name'] ?? 'frigotic'),
                'user' => trim($_POST['db_user'] ?? 'root'),
                'pass' => $_POST['db_pass'] ?? '',
            ];
            
            // Si la contraseña está vacía, mantener la anterior
            if (empty($dbConfig['pass'])) {
                $dbConfig['pass'] = EnvHelper::get('DB_PASS', '');
            }
            
            // Probar conexión antes de guardar
            $testResult = EnvHelper::testDatabaseConnection($dbConfig);
            
            if ($testResult['success']) {
                EnvHelper::saveDatabaseConfig($dbConfig);
                $message = 'Configuración de base de datos guardada correctamente';
                $messageType = 'success';
            } else {
                $message = 'Error de conexión: ' . $testResult['message'];
                $messageType = 'danger';
            }
            break;
            
        case 'test_database':
            $dbConfig = [
                'host' => trim($_POST['db_host'] ?? 'localhost'),
                'port' => trim($_POST['db_port'] ?? '3306'),
                'name' => trim($_POST['db_name'] ?? 'frigotic'),
                'user' => trim($_POST['db_user'] ?? 'root'),
                'pass' => $_POST['db_pass'] ?? EnvHelper::get('DB_PASS', ''),
            ];
            
            $testResult = EnvHelper::testDatabaseConnection($dbConfig);
            
            if ($testResult['success']) {
                $message = '✅ Conexión exitosa a la base de datos';
                $messageType = 'success';
            } else {
                $message = '❌ Error: ' . $testResult['message'];
                $messageType = 'danger';
            }
            break;
            
        case 'save_smtp':
            $smtpConfig = [
                'host' => trim($_POST['smtp_host'] ?? 'smtp.gmail.com'),
                'port' => trim($_POST['smtp_port'] ?? '587'),
                'encryption' => $_POST['smtp_encryption'] ?? 'tls',
                'username' => trim($_POST['smtp_user'] ?? ''),
                'password' => $_POST['smtp_password'] ?? '',
                'from_name' => trim($_POST['smtp_from_name'] ?? 'FrigoTIC'),
            ];
            
            // Si la contraseña está vacía, mantener la anterior
            if (empty($smtpConfig['password'])) {
                $smtpConfig['password'] = EnvHelper::get('SMTP_PASS', '');
            }
            
            EnvHelper::saveSmtpConfig($smtpConfig);
            $message = 'Configuración SMTP guardada correctamente';
            $messageType = 'success';
            break;
            
        case 'save_general':
            $configModel->set('app_nombre', $_POST['app_nombre'] ?? 'FrigoTIC', 'string');
            $configModel->set('items_por_pagina', $_POST['items_por_pagina'] ?? 10, 'int');
            $configModel->set('password_default', $_POST['password_default'] ?? 'Cambiar123', 'string');
            $message = 'Configuración guardada';
            $messageType = 'success';
            break;
    }
    
    // Recargar configuración del .env
    EnvHelper::load(dirname(dirname(APP_PATH)) . '/.env');
}

// Obtener configuración actual desde .env
$dbConfig = EnvHelper::getDatabaseConfig();
$smtpConfig = EnvHelper::getSmtpConfig();

include APP_PATH . '/views/partials/header.php';
include APP_PATH . '/views/partials/admin-tabs.php';
?>

<?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?>">
        <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-circle' ?> alert-icon"></i>
        <div class="alert-content"><?= $message ?></div>
    </div>
<?php endif; ?>

<h1 class="mb-4"><i class="fas fa-cog"></i> Configuración</h1>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 1.5rem;">
    
    <!-- Configuración de Base de Datos -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-database"></i> Base de Datos</h2>
        </div>
        <div class="card-body">
            <form method="POST" action="" id="formDatabase">
                <input type="hidden" name="action" value="save_database" id="db_action">
                
                <div class="form-group">
                    <label class="form-label">Servidor</label>
                    <input type="text" name="db_host" class="form-control" 
                           value="<?= htmlspecialchars($dbConfig['host']) ?>" required>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Puerto</label>
                        <input type="number" name="db_port" class="form-control" 
                               value="<?= htmlspecialchars($dbConfig['port']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Base de datos</label>
                        <input type="text" name="db_name" class="form-control" 
                               value="<?= htmlspecialchars($dbConfig['name']) ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Usuario</label>
                    <input type="text" name="db_user" class="form-control" 
                           value="<?= htmlspecialchars($dbConfig['user']) ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Contraseña</label>
                    <div class="password-wrapper">
                        <input type="password" name="db_pass" id="db_pass" class="form-control" 
                               placeholder="<?= empty($dbConfig['pass']) ? 'Sin contraseña' : '••••••••' ?>">
                        <button type="button" class="password-toggle" onclick="togglePassword('db_pass', this)" title="Mostrar/ocultar contraseña">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <small class="form-text">Dejar vacío para mantener la actual</small>
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="testDatabase()">
                        <i class="fas fa-plug"></i> Probar Conexión
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Cambiar Contraseña Admin -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-key"></i> Cambiar Contraseña Admin</h2>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <input type="hidden" name="action" value="change_password">
                
                <div class="form-group">
                    <label class="form-label">Contraseña actual</label>
                    <div class="password-wrapper">
                        <input type="password" name="current_password" id="current_password_admin" class="form-control" required>
                        <button type="button" class="password-toggle" onclick="togglePassword('current_password_admin', this)" title="Mostrar/ocultar contraseña">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Nueva contraseña</label>
                    <div class="password-wrapper">
                        <input type="password" name="new_password" id="new_password_admin" class="form-control" required>
                        <button type="button" class="password-toggle" onclick="togglePassword('new_password_admin', this)" title="Mostrar/ocultar contraseña">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Confirmar contraseña</label>
                    <div class="password-wrapper">
                        <input type="password" name="confirm_password" id="confirm_password_admin" class="form-control" required>
                        <button type="button" class="password-toggle" onclick="togglePassword('confirm_password_admin', this)" title="Mostrar/ocultar contraseña">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Cambiar Contraseña
                </button>
            </form>
        </div>
    </div>

    <!-- Configuración SMTP -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-envelope"></i> Configuración SMTP</h2>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <input type="hidden" name="action" value="save_smtp">
                
                <div class="form-group">
                    <label class="form-label">Servidor SMTP</label>
                    <input type="text" name="smtp_host" class="form-control" 
                           value="<?= htmlspecialchars($smtpConfig['host']) ?>">
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Puerto</label>
                        <input type="number" name="smtp_port" class="form-control" 
                               value="<?= htmlspecialchars($smtpConfig['port']) ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Encriptación</label>
                        <select name="smtp_encryption" class="form-control form-select">
                            <option value="tls" <?= $smtpConfig['encryption'] === 'tls' ? 'selected' : '' ?>>TLS</option>
                            <option value="ssl" <?= $smtpConfig['encryption'] === 'ssl' ? 'selected' : '' ?>>SSL</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Usuario (email)</label>
                    <input type="email" name="smtp_user" class="form-control" 
                           value="<?= htmlspecialchars($smtpConfig['username']) ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Contraseña de aplicación</label>
                    <div class="password-wrapper">
                        <input type="password" name="smtp_password" id="smtp_password" class="form-control" 
                               placeholder="<?= empty($smtpConfig['password']) ? 'Sin configurar' : '••••••••' ?>">
                        <button type="button" class="password-toggle" onclick="togglePassword('smtp_password', this)" title="Mostrar/ocultar contraseña">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <small class="form-text">Dejar vacío para mantener la actual</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Nombre remitente</label>
                    <input type="text" name="smtp_from_name" class="form-control" 
                           value="<?= htmlspecialchars($smtpConfig['from_name']) ?>">
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Guardar SMTP
                </button>
            </form>
        </div>
    </div>

    <!-- Configuración General -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-sliders-h"></i> Configuración General</h2>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <input type="hidden" name="action" value="save_general">
                
                <div class="form-group">
                    <label class="form-label">Nombre de la aplicación</label>
                    <input type="text" name="app_nombre" class="form-control" 
                           value="<?= htmlspecialchars($configModel->get('app_nombre', 'FrigoTIC')) ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Elementos por página</label>
                    <select name="items_por_pagina" class="form-control form-select">
                        <?php foreach ([5, 10, 25, 50] as $opt): ?>
                            <option value="<?= $opt ?>" <?= $configModel->get('items_por_pagina', 10) == $opt ? 'selected' : '' ?>>
                                <?= $opt ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Contraseña por defecto (reseteos)</label>
                    <input type="text" name="password_default" class="form-control" 
                           value="<?= htmlspecialchars($configModel->get('password_default', 'Cambiar123')) ?>">
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Guardar
                </button>
            </form>
        </div>
    </div>

    <!-- Información del Sistema -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-info-circle"></i> Información del Sistema</h2>
        </div>
        <div class="card-body">
            <table class="table">
                <tr>
                    <td><strong>Versión</strong></td>
                    <td><?= htmlspecialchars(getAppVersion()) ?></td>
                </tr>
                <tr>
                    <td><strong>PHP</strong></td>
                    <td><?= phpversion() ?></td>
                </tr>
                <tr>
                    <td><strong>Servidor</strong></td>
                    <td><?= $_SERVER['SERVER_SOFTWARE'] ?? 'N/A' ?></td>
                </tr>
                <tr>
                    <td><strong>BD Conectada</strong></td>
                    <td>
                        <?php
                        $testResult = EnvHelper::testDatabaseConnection();
                        if ($testResult['success']) {
                            echo '<span class="badge badge-success"><i class="fas fa-check"></i> Sí</span>';
                        } else {
                            echo '<span class="badge badge-danger"><i class="fas fa-times"></i> No</span>';
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <td><strong>Entorno</strong></td>
                    <td><?= htmlspecialchars(EnvHelper::get('APP_ENV', 'development')) ?></td>
                </tr>
                <tr>
                    <td><strong>Empresa</strong></td>
                    <td>MJCRSoftware</td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Archivo .env -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="fas fa-file-code"></i> Archivo .env</h2>
        </div>
        <div class="card-body">
            <p class="mb-3">
                La configuración sensible se guarda en el archivo <code>.env</code> 
                que no se sube al repositorio Git.
            </p>
            <div class="alert alert-info">
                <i class="fas fa-info-circle alert-icon"></i>
                <div class="alert-content">
                    <strong>Ubicación:</strong> <code><?= dirname(dirname(APP_PATH)) ?>/.env</code>
                </div>
            </div>
            <p class="text-muted">
                En producción, copia <code>.env.example</code> a <code>.env</code> y 
                configura los valores apropiados para ese entorno.
            </p>
        </div>
    </div>
</div>

<!-- Instrucciones SMTP Google -->
<div class="card mt-4">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-question-circle"></i> Cómo configurar SMTP de Google</h2>
    </div>
    <div class="card-body">
        <ol>
            <li>Accede a tu cuenta de Google: <a href="https://myaccount.google.com/" target="_blank">myaccount.google.com</a></li>
            <li>Ve a <strong>Seguridad</strong></li>
            <li>Activa la <strong>Verificación en dos pasos</strong> si no la tienes</li>
            <li>Busca <strong>Contraseñas de aplicaciones</strong></li>
            <li>Selecciona "Correo" y "Ordenador Windows"</li>
            <li>Haz clic en <strong>Generar</strong></li>
            <li>Copia la contraseña de 16 caracteres generada</li>
            <li>Usa esa contraseña en el campo "Contraseña de aplicación" de arriba</li>
        </ol>
        <div class="alert alert-warning mt-3">
            <i class="fas fa-exclamation-triangle alert-icon"></i>
            <div class="alert-content">
                <strong>Importante:</strong> No uses tu contraseña normal de Google. Debes generar una contraseña de aplicación específica.
            </div>
        </div>
    </div>
</div>

<script>
function testDatabase() {
    document.getElementById('db_action').value = 'test_database';
    document.getElementById('formDatabase').submit();
}

// Restaurar action después de cargar
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('db_action').value = 'save_database';
});

// Toggle mostrar/ocultar contraseña
function togglePassword(inputId, button) {
    const input = document.getElementById(inputId);
    const icon = button.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}
</script>

<?php include APP_PATH . '/views/partials/footer.php'; ?>
