<?php
/**
 * FrigoTIC - Vista de Perfil (Usuario)
 */

$pageTitle = 'Mi Perfil';

require_once APP_PATH . '/models/Database.php';
require_once APP_PATH . '/models/Usuario.php';
require_once APP_PATH . '/models/Movimiento.php';
require_once APP_PATH . '/controllers/AuthController.php';

use App\Models\Usuario;
use App\Models\Movimiento;
use App\Controllers\AuthController;

$usuarioModel = new Usuario();
$movimientoModel = new Movimiento();

$usuario = $usuarioModel->getById($_SESSION['user_id']);
$resumen = $movimientoModel->getResumenUsuario($_SESSION['user_id']);

// Procesar actualización de perfil
$message = null;
$messageType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar token CSRF
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!AuthController::verifyCsrfToken($csrfToken)) {
        $message = 'Error de seguridad. Por favor, recarga la página e intenta de nuevo.';
        $messageType = 'danger';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'update_email') {
            $newEmail = trim($_POST['email'] ?? '');
            if (filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
                if (!$usuarioModel->emailExists($newEmail, $_SESSION['user_id'])) {
                    $usuarioModel->update($_SESSION['user_id'], ['email' => $newEmail]);
                    $_SESSION['user_email'] = $newEmail;
                    $message = 'Email actualizado correctamente';
                    $messageType = 'success';
                    $usuario['email'] = $newEmail;
                } else {
                    $message = 'Este email ya está en uso';
                    $messageType = 'danger';
                }
            } else {
                $message = 'Email no válido';
                $messageType = 'danger';
            }
        } elseif ($action === 'update_password') {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            if (strlen($newPassword) < 6) {
                $message = 'La contraseña debe tener al menos 6 caracteres';
                $messageType = 'danger';
            } elseif ($newPassword !== $confirmPassword) {
                $message = 'Las contraseñas no coinciden';
                $messageType = 'danger';
            } elseif (!$usuarioModel->verifyPassword($currentPassword, $usuario['password_hash'])) {
                $message = 'Contraseña actual incorrecta';
                $messageType = 'danger';
            } else {
                $usuarioModel->updatePassword($_SESSION['user_id'], $newPassword);
                AuthController::regenerateCsrfToken();
                $message = 'Contraseña actualizada correctamente';
                $messageType = 'success';
            }
        }
    }
}

include APP_PATH . '/views/partials/header.php';
include APP_PATH . '/views/partials/user-tabs.php';
?>

<?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?>">
        <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-circle' ?> alert-icon"></i>
        <div class="alert-content"><?= htmlspecialchars($message) ?></div>
    </div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 1.5rem;">
    <!-- Información del usuario -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                <i class="fas fa-user"></i> Mi Información
            </h2>
        </div>
        <div class="card-body">
            <div class="stat-card" style="box-shadow: none; background: var(--color-gray-50);">
                <div class="stat-icon primary">
                    <i class="fas fa-user-circle"></i>
                </div>
                <div class="stat-value"><?= htmlspecialchars($usuario['nombre_usuario']) ?></div>
                <div class="stat-label">Nombre de usuario</div>
            </div>
            
            <hr style="margin: 1rem 0; border: none; border-top: 1px solid var(--color-gray-200);">
            
            <p><strong>Nombre completo:</strong> <?= htmlspecialchars($usuario['nombre_completo'] ?? 'No especificado') ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($usuario['email']) ?></p>
            <p><strong>Miembro desde:</strong> <?= date('d/m/Y', strtotime($usuario['fecha_registro'])) ?></p>
            
            <hr style="margin: 1rem 0; border: none; border-top: 1px solid var(--color-gray-200);">
            
            <h4 class="mb-3">Resumen de actividad</h4>
            <div class="d-flex gap-4 flex-wrap">
                <div>
                    <span class="text-muted">Consumos:</span>
                    <strong><?= $resumen['num_consumos'] ?? 0 ?></strong>
                </div>
                <div>
                    <span class="text-muted">Pagos:</span>
                    <strong><?= $resumen['num_pagos'] ?? 0 ?></strong>
                </div>
                <div>
                    <span class="text-muted">Deuda:</span>
                    <strong class="text-warning"><?= number_format($resumen['deuda'] ?? 0, 2, ',', '.') ?> €</strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Cambiar email -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                <i class="fas fa-envelope"></i> Cambiar Email
            </h2>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <?= \App\Controllers\AuthController::csrfField() ?>
                <input type="hidden" name="action" value="update_email">
                
                <div class="form-group">
                    <label for="email" class="form-label">Nuevo email</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-control" 
                        value="<?= htmlspecialchars($usuario['email']) ?>"
                        required
                    >
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Guardar Email
                </button>
            </form>
        </div>
    </div>

    <!-- Cambiar contraseña -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                <i class="fas fa-key"></i> Cambiar Contraseña
            </h2>
        </div>
        <div class="card-body">
            <form method="POST" action="">
                <?= \App\Controllers\AuthController::csrfField() ?>
                <input type="hidden" name="action" value="update_password">
                
                <div class="form-group">
                    <label for="current_password" class="form-label">Contraseña actual</label>
                    <div class="password-wrapper">
                        <input 
                            type="password" 
                            id="current_password" 
                            name="current_password" 
                            class="form-control" 
                            required
                        >
                        <button type="button" class="password-toggle" onclick="togglePassword('current_password', this)" title="Mostrar/ocultar contraseña">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="new_password" class="form-label">Nueva contraseña</label>
                    <div class="password-wrapper">
                        <input 
                            type="password" 
                            id="new_password" 
                            name="new_password" 
                            class="form-control" 
                            required
                        >
                        <button type="button" class="password-toggle" onclick="togglePassword('new_password', this)" title="Mostrar/ocultar contraseña">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password" class="form-label">Confirmar contraseña</label>
                    <div class="password-wrapper">
                        <input 
                            type="password" 
                            id="confirm_password" 
                            name="confirm_password" 
                            class="form-control" 
                            required
                        >
                        <button type="button" class="password-toggle" onclick="togglePassword('confirm_password', this)" title="Mostrar/ocultar contraseña">
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
</div>

<script>
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
