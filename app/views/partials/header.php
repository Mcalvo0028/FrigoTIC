<?php
/**
 * FrigoTIC - Header Partial
 * 
 * Variables esperadas:
 * - $pageTitle: Título de la página
 * - $isAdmin: Boolean indicando si es admin
 */

$isAdmin = ($_SESSION['user_role'] ?? '') === 'admin';
$themeClass = $isAdmin ? 'theme-admin' : '';
$userName = $_SESSION['user_name'] ?? $_SESSION['username'] ?? 'Usuario';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'FrigoTIC') ?> - FrigoTIC</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="/css/style.css">
    <?php if (isset($extraCss)): ?>
        <?= $extraCss ?>
    <?php endif; ?>
</head>
<body class="<?= $themeClass ?>">
    <div class="app-container">
        <!-- Header -->
        <header class="header">
            <div class="header-brand">
                <i class="fas fa-snowflake"></i>
                <span>FrigoTIC</span>
            </div>

            <nav class="header-nav">
                <a href="/ayuda/<?= $isAdmin ? 'admin' : 'usuario' ?>" 
                   class="header-link" 
                   onclick="openHelpModal(); return false;">
                    <i class="fas fa-question-circle"></i>
                    <span>Ayuda</span>
                </a>

                <div class="header-user">
                    <div class="user-info">
                        <div class="user-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <span><?= htmlspecialchars($userName) ?></span>
                        <?php if ($isAdmin): ?>
                            <span class="badge badge-danger">Admin</span>
                        <?php endif; ?>
                    </div>

                    <a href="/logout" class="header-link">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Cerrar sesión</span>
                    </a>
                </div>
            </nav>
        </header>

        <!-- Main Content -->
        <main class="main-content">
