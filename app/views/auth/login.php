<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - FrigoTIC</title>
    <link rel="icon" type="image/x-icon" href="/images/favicon_azul.ico">
    <link rel="shortcut icon" type="image/x-icon" href="/images/favicon_azul.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/css/style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-logo">
                    <img src="/images/Logo.png" alt="FrigoTIC" style="width: 80px; height: auto;">
                </div>
                <h1 class="login-title">FrigoTIC</h1>
                <p class="login-subtitle">Gestión del frigorífico compartido</p>
            </div>

            <?php if (isset($_SESSION['login_error'])): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle alert-icon"></i>
                    <div class="alert-content">
                        <?= htmlspecialchars($_SESSION['login_error']) ?>
                    </div>
                </div>
                <?php unset($_SESSION['login_error']); ?>
            <?php endif; ?>

            <form method="POST" action="/login" id="loginForm">
                <div class="form-group">
                    <label for="username" class="form-label">
                        <i class="fas fa-user"></i> Usuario
                    </label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        class="form-control" 
                        placeholder="Ingresa tu usuario"
                        required
                        autofocus
                    >
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock"></i> Contraseña
                    </label>
                    <div class="password-wrapper">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-control" 
                            placeholder="Ingresa tu contraseña"
                            required
                        >
                        <button type="button" class="password-toggle" onclick="togglePassword('password', this)" title="Mostrar/ocultar contraseña">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
                    <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                </button>
            </form>

            <div class="login-footer">
                <p>&copy; <?= date('Y') ?> MJCRSoftware</p>
                <p>Versión <?= htmlspecialchars(getAppVersion()) ?></p>
            </div>
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
