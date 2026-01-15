<?php
$mustChange = $_SESSION['must_change_password'] ?? false;
$pageTitle = $mustChange ? 'Cambiar Contraseña (Obligatorio)' : 'Cambiar Contraseña';
$isAdmin = ($_SESSION['user_role'] ?? '') === 'admin';
$favicon = $isAdmin ? '/images/favicon_rojo.ico' : '/images/favicon_azul.ico';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?> - FrigoTIC</title>
    <link rel="icon" type="image/x-icon" href="<?= $favicon ?>">
    <link rel="shortcut icon" type="image/x-icon" href="<?= $favicon ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/css/style.css">
</head>
<body class="login-page <?= $isAdmin ? 'theme-admin' : '' ?>">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-logo">
                    <img src="/images/Logo.png" alt="FrigoTIC" style="width: 80px; height: auto;">
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

            <form method="POST" action="/cambiar-password" id="changePasswordForm">
                <?php if (!$mustChange): ?>
                    <div class="form-group">
                        <label for="current_password" class="form-label">
                            <i class="fas fa-lock"></i> Contraseña Actual
                        </label>
                        <div class="password-wrapper">
                            <input 
                                type="password" 
                                id="current_password" 
                                name="current_password" 
                                class="form-control" 
                                placeholder="Ingresa tu contraseña actual"
                                required
                            >
                            <button type="button" class="password-toggle" onclick="togglePassword('current_password', this)" title="Mostrar/ocultar contraseña">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="new_password" class="form-label">
                        <i class="fas fa-key"></i> Nueva Contraseña
                    </label>
                    <div class="password-wrapper">
                        <input 
                            type="password" 
                            id="new_password" 
                            name="new_password" 
                            class="form-control" 
                            placeholder="Ingresa tu nueva contraseña"
                            required
                        >
                        <button type="button" class="password-toggle" onclick="togglePassword('new_password', this)" title="Mostrar/ocultar contraseña">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm_password" class="form-label">
                        <i class="fas fa-key"></i> Confirmar Contraseña
                    </label>
                    <div class="password-wrapper">
                        <input 
                            type="password" 
                            id="confirm_password" 
                            name="confirm_password" 
                            class="form-control" 
                            placeholder="Confirma tu nueva contraseña"
                            required
                        >
                        <button type="button" class="password-toggle" onclick="togglePassword('confirm_password', this)" title="Mostrar/ocultar contraseña">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
                    <i class="fas fa-save"></i> Cambiar Contraseña
                </button>

                <?php if (!$mustChange): ?>
                    <div class="mt-4 text-center">
                        <a href="/<?= $_SESSION['user_role'] === 'admin' ? 'admin/dashboard' : 'user/productos' ?>" class="text-muted">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <script src="/js/app.js"></script>
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
</body>
</html>
