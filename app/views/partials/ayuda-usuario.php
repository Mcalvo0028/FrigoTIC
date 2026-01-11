<?php
/**
 * FrigoTIC - Ayuda para Usuario
 */
$pageTitle = 'Ayuda - Usuario';
$isAdmin = false;
include APP_PATH . '/views/partials/header.php';
?>

<div class="help-container help-with-watermark">
    <!-- Header de ayuda -->
    <div class="help-header">
        <img src="/images/Logo.png" alt="FrigoTIC" class="help-header-logo">
        <h1><i class="fas fa-question-circle"></i> Centro de Ayuda</h1>
        <p>Guía rápida para usar FrigoTIC</p>
    </div>

    <!-- Sección Bienvenida -->
    <div class="help-section">
        <div class="help-section-icon icon-products">
            <i class="fas fa-snowflake"></i>
        </div>
        <h3>¡Bienvenido a FrigoTIC!</h3>
        <p>FrigoTIC es la aplicación para gestionar el frigorífico compartido de la oficina. Aquí podrás ver los productos disponibles, apuntar lo que consumes y llevar un control de tus pagos.</p>
    </div>

    <!-- Sección Productos -->
    <div class="help-section">
        <div class="help-section-icon icon-products">
            <i class="fas fa-shopping-cart"></i>
        </div>
        <h3>Pestaña Productos</h3>
        <p>Aquí puedes ver todo lo disponible en el frigorífico.</p>
        <ul>
            <li><strong>Ver productos:</strong> Todos los productos con su precio y stock disponible</li>
            <li><strong>Coger producto:</strong> Haz clic en "Coger" para apuntar que has cogido algo</li>
            <li><strong>Stock:</strong> Si un producto está agotado, aparecerá como "No disponible"</li>
            <li><strong>Tu resumen:</strong> Arriba verás cuánto llevas consumido y tu deuda actual</li>
        </ul>
    </div>

    <!-- Sección Movimientos -->
    <div class="help-section">
        <div class="help-section-icon icon-movements">
            <i class="fas fa-history"></i>
        </div>
        <h3>Pestaña Movimientos</h3>
        <p>Tu historial completo de actividad.</p>
        <ul>
            <li><strong>Consumos:</strong> Todos los productos que has cogido con fecha y precio</li>
            <li><strong>Pagos:</strong> Los pagos que has realizado al administrador</li>
            <li><strong>Filtros:</strong> Busca por fecha, producto o tipo de movimiento</li>
            <li><strong>Deuda:</strong> En el resumen verás tu deuda actual (número negativo = debes dinero)</li>
        </ul>
    </div>

    <!-- Sección Perfil -->
    <div class="help-section">
        <div class="help-section-icon icon-profile">
            <i class="fas fa-user-cog"></i>
        </div>
        <h3>Pestaña Perfil</h3>
        <p>Gestiona tu cuenta personal.</p>
        <ul>
            <li><strong>Cambiar contraseña:</strong> Actualiza tu contraseña cuando quieras</li>
            <li><strong>Email:</strong> Mantén tu correo electrónico actualizado</li>
            <li><strong>Tu información:</strong> Consulta los datos de tu cuenta</li>
        </ul>
    </div>

    <!-- Sección Pagos -->
    <div class="help-section">
        <div class="help-section-icon icon-pay">
            <i class="fas fa-euro-sign"></i>
        </div>
        <h3>¿Cómo pagar?</h3>
        <p>El proceso de pago es sencillo:</p>
        <ul>
            <li><strong>Consulta tu deuda:</strong> Mira cuánto debes en la pestaña Productos o Movimientos</li>
            <li><strong>Paga al admin:</strong> Entrega el dinero al administrador del frigorífico</li>
            <li><strong>Confirmación:</strong> El admin registrará tu pago y tu deuda se pondrá a cero</li>
            <li><strong>Email:</strong> Si está configurado, recibirás un correo de confirmación</li>
        </ul>
    </div>

    <!-- Sección Soporte -->
    <div class="help-section">
        <div class="help-section-icon icon-support">
            <i class="fas fa-life-ring"></i>
        </div>
        <h3>¿Necesitas ayuda?</h3>
        <p>Si tienes algún problema o duda:</p>
        <ul>
            <li>Contacta con el administrador del frigorífico</li>
            <li>Si olvidaste tu contraseña, el admin puede resetearla</li>
            <li>Para sugerencias o problemas técnicos, habla con el admin</li>
        </ul>
    </div>

    <!-- Footer de ayuda -->
    <div class="help-footer">
        <img src="/images/Logo.png" alt="FrigoTIC">
        <p><strong>FrigoTIC</strong> - Desarrollado por MJCRSoftware</p>
        <p>¡Disfruta del frigorífico compartido!</p>
    </div>
</div>

<script>
// Cerrar con tecla Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        window.history.back();
    }
});
</script>

<?php include APP_PATH . '/views/partials/footer.php'; ?>
