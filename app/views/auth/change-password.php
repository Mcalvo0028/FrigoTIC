<?php
$mustChange = $_SESSION['must_change_password'] ?? false;
$pageTitle = $mustChange ? 'Cambiar Contraseña (Obligatorio)' : 'Cambiar Contraseña';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - FrigoTIC</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/frigotic/css/style.css">
</head>
<body class="login-page <?= $_SESSION['user_role'] === 'admin' ? 'theme-admin' : '' ?>">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-logo">
                    <i class="fas fa-key"></i>
                </div>
                <h1 class="login-title"><?= $pageTitle ?></h1>
                <?php if ($mustChange): ?>
                    <p class="login-subtitle text-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        Debes cambiar tu contraseña para continuar
                    </p>
                <?php endif; ?>
            </div>

            <?php if (isset($_SESSION['change_password_error'])): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle alert-icon"></i>
                    <div class="alert-content">
                        <?= htmlspecialchars($_SESSION['change_password_error']) ?>
                    </div>
                </div>
                <?php unset($_SESSION['change_password_error']); ?>
            <?php endif; ?>

            <form method="POST" action="/frigotic/cambiar-password" id="changePasswordForm">
                <?php if (!$mustChange): ?>
                    <div class="form-group">
                        <label for="current_password" class="form-label">
                            <i class="fas fa-lock"></i> Contraseña Actual
                        </label>
                        <input 
                            type="password" 
                            id="current_password" 
                            name="current_password" 
                            class="form-control" 
                            placeholder="Ingresa tu contraseña actual"
                            required
                        >
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="new_password" class="form-label">
                        <i class="fas fa-key"></i> Nueva Contraseña
                    </label>
                    <input 
                        type="password" 
                        id="new_password" 
                        name="new_password" 
                        class="form-control" 
                        placeholder="Ingresa tu nueva contraseña"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="confirm_password" class="form-label">
                        <i class="fas fa-key"></i> Confirmar Contraseña
                    </label>
                    <input 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password" 
                        class="form-control" 
                        placeholder="Confirma tu nueva contraseña"
                        required
                    >
                </div>

                <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
                    <i class="fas fa-save"></i> Cambiar Contraseña
                </button>

                <?php if (!$mustChange): ?>
                    <div class="mt-4 text-center">
                        <a href="/frigotic/<?= $_SESSION['user_role'] === 'admin' ? 'admin/dashboard' : 'user/productos' ?>" class="text-muted">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <script src="/frigotic/js/app.js"></script>
</body>
</html>
