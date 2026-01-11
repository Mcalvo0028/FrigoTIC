<?php
/**
 * FrigoTIC - Ayuda para Administrador
 */
$pageTitle = 'Ayuda - Administrador';
$isAdmin = true;
include APP_PATH . '/views/partials/header.php';
?>

<div class="help-container help-with-watermark">
    <!-- Header de ayuda -->
    <div class="help-header">
        <img src="/images/Logo.png" alt="FrigoTIC" class="help-header-logo">
        <h1><i class="fas fa-question-circle"></i> Centro de Ayuda</h1>
        <p>Guía completa para administradores de FrigoTIC</p>
    </div>

    <!-- Sección Dashboard -->
    <div class="help-section">
        <div class="help-section-icon icon-dashboard">
            <i class="fas fa-tachometer-alt"></i>
        </div>
        <h3>Dashboard</h3>
        <p>Tu panel de control con toda la información importante de un vistazo.</p>
        <ul>
            <li>Resumen de ventas y consumos del mes</li>
            <li>Alertas de productos con stock bajo</li>
            <li>Usuarios con deudas pendientes</li>
            <li>Estadísticas rápidas del frigorífico</li>
        </ul>
    </div>

    <!-- Sección Usuarios -->
    <div class="help-section">
        <div class="help-section-icon icon-users">
            <i class="fas fa-users"></i>
        </div>
        <h3>Gestión de Usuarios</h3>
        <p>Administra todos los usuarios del sistema.</p>
        <ul>
            <li><strong>Crear usuario:</strong> Añade nuevos usuarios con email y contraseña temporal</li>
            <li><strong>Resetear contraseña:</strong> Genera una nueva contraseña para usuarios que la olvidaron</li>
            <li><strong>Ver deudas:</strong> Consulta cuánto debe cada usuario</li>
            <li><strong>Registrar pagos:</strong> Marca las deudas como pagadas</li>
            <li><strong>Activar/Desactivar:</strong> Gestiona el acceso de usuarios</li>
        </ul>
    </div>

    <!-- Sección Productos -->
    <div class="help-section">
        <div class="help-section-icon icon-products">
            <i class="fas fa-box"></i>
        </div>
        <h3>Gestión de Productos</h3>
        <p>Control completo del inventario del frigorífico.</p>
        <ul>
            <li><strong>Añadir productos:</strong> Crea nuevos productos con imagen, precios y stock</li>
            <li><strong>Stock mínimo:</strong> Configura el umbral de alerta de stock bajo por producto</li>
            <li><strong>Reposición:</strong> Registra cuando añades más unidades al frigorífico</li>
            <li><strong>Precios:</strong> Diferencia entre precio de compra y precio de venta</li>
        </ul>
    </div>

    <!-- Sección Facturas -->
    <div class="help-section">
        <div class="help-section-icon icon-invoices">
            <i class="fas fa-file-invoice"></i>
        </div>
        <h3>Gestión de Facturas</h3>
        <p>Archivo digital de todas tus facturas de compra.</p>
        <ul>
            <li><strong>Subir facturas:</strong> Sube PDFs de las compras que realices</li>
            <li><strong>Organización:</strong> Fecha de factura y descripción para buscar fácilmente</li>
            <li><strong>Descargar:</strong> Accede a las facturas cuando las necesites</li>
        </ul>
    </div>

    <!-- Sección Movimientos -->
    <div class="help-section">
        <div class="help-section-icon icon-movements">
            <i class="fas fa-exchange-alt"></i>
        </div>
        <h3>Historial de Movimientos</h3>
        <p>Registro completo de toda la actividad del frigorífico.</p>
        <ul>
            <li><strong>Consumos:</strong> Todos los productos cogidos por usuarios</li>
            <li><strong>Pagos:</strong> Historial de pagos recibidos</li>
            <li><strong>Reposiciones:</strong> Cuando añadiste stock al frigorífico</li>
            <li><strong>Filtros:</strong> Busca por usuario, fecha, producto o tipo</li>
        </ul>
    </div>

    <!-- Sección Gráficos -->
    <div class="help-section">
        <div class="help-section-icon icon-charts">
            <i class="fas fa-chart-bar"></i>
        </div>
        <h3>Estadísticas y Gráficos</h3>
        <p>Visualiza la información de forma gráfica.</p>
        <ul>
            <li><strong>Consumos por mes:</strong> Evolución temporal</li>
            <li><strong>Productos más populares:</strong> Ranking de ventas</li>
            <li><strong>Consumo por usuario:</strong> Quién consume más</li>
            <li><strong>Balance financiero:</strong> Ingresos vs gastos</li>
            <li><strong>Exportar PDF:</strong> Genera un informe con datos de los últimos 30 días</li>
        </ul>
    </div>

    <!-- Sección Exportación -->
    <div class="help-section">
        <div class="help-section-icon icon-export">
            <i class="fas fa-file-pdf"></i>
        </div>
        <h3>Exportación a PDF</h3>
        <p>Genera informes en PDF de cualquier sección.</p>
        <ul>
            <li><strong>Botón Exportar:</strong> Disponible en Usuarios, Productos, Facturas, Movimientos y Gráficos</li>
            <li><strong>Contenido:</strong> Se exporta la lista completa con todos los datos visibles</li>
            <li><strong>Imágenes:</strong> Los productos incluyen su imagen en la exportación</li>
            <li><strong>Gráficos:</strong> Se exportan datos tabulares de los últimos 30 días</li>
            <li><strong>Baja de usuario:</strong> Al eliminar un usuario se genera un informe con su historial</li>
        </ul>
    </div>

    <!-- Sección Correos -->
    <div class="help-section">
        <div class="help-section-icon icon-emails">
            <i class="fas fa-envelope"></i>
        </div>
        <h3>Gestión de Correos</h3>
        <p>Comunicación con los usuarios del frigorífico.</p>
        <ul>
            <li><strong>Plantillas:</strong> Personaliza los correos automáticos</li>
            <li><strong>Correo de bienvenida:</strong> Se envía al crear un usuario nuevo</li>
            <li><strong>Confirmación de pago:</strong> Resumen al registrar un pago</li>
            <li><strong>Envío masivo:</strong> Comunícate con todos los usuarios</li>
        </ul>
    </div>

    <!-- Sección Configuración -->
    <div class="help-section">
        <div class="help-section-icon icon-settings">
            <i class="fas fa-cog"></i>
        </div>
        <h3>Configuración</h3>
        <p>Ajustes del sistema.</p>
        <ul>
            <li><strong>Tu contraseña:</strong> Cambia tu contraseña de admin</li>
            <li><strong>Base de datos:</strong> Configuración de conexión MySQL</li>
            <li><strong>SMTP:</strong> Configuración del servidor de correo</li>
        </ul>
    </div>

    <!-- Footer de ayuda -->
    <div class="help-footer">
        <img src="/images/Logo.png" alt="FrigoTIC">
        <p><strong>FrigoTIC</strong> - Desarrollado por MJCRSoftware</p>
        <p>¿Necesitas más ayuda? Contacta con soporte técnico.</p>
    </div>
</div>

<script>
// Cerrar modal de ayuda al hacer clic fuera
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        window.history.back();
    }
});
</script>

<?php include APP_PATH . '/views/partials/footer.php'; ?>
