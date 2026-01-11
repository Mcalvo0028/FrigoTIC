<?php
/**
 * FrigoTIC - Pestañas de Navegación de Admin
 */
$currentPage = $_SERVER['REQUEST_URI'] ?? '';
?>
<nav class="tabs-nav">
    <ul class="tabs-list">
        <li class="tab-item">
            <a href="/admin/dashboard" class="tab-link <?= strpos($currentPage, 'dashboard') !== false ? 'active' : '' ?>">
                <i class="fas fa-tachometer-alt tab-icon-dashboard"></i>
                <span>Dashboard</span>
            </a>
        </li>
        <li class="tab-item">
            <a href="/admin/usuarios" class="tab-link <?= strpos($currentPage, 'usuarios') !== false ? 'active' : '' ?>">
                <i class="fas fa-users tab-icon-users"></i>
                <span>Usuarios</span>
            </a>
        </li>
        <li class="tab-item">
            <a href="/admin/productos" class="tab-link <?= strpos($currentPage, 'productos') !== false ? 'active' : '' ?>">
                <i class="fas fa-box tab-icon-products"></i>
                <span>Productos</span>
            </a>
        </li>
        <li class="tab-item">
            <a href="/admin/facturas" class="tab-link <?= strpos($currentPage, 'facturas') !== false ? 'active' : '' ?>">
                <i class="fas fa-file-invoice tab-icon-invoices"></i>
                <span>Facturas</span>
            </a>
        </li>
        <li class="tab-item">
            <a href="/admin/movimientos" class="tab-link <?= strpos($currentPage, 'movimientos') !== false ? 'active' : '' ?>">
                <i class="fas fa-exchange-alt tab-icon-movements"></i>
                <span>Movimientos</span>
            </a>
        </li>
        <li class="tab-item">
            <a href="/admin/graficos" class="tab-link <?= strpos($currentPage, 'graficos') !== false ? 'active' : '' ?>">
                <i class="fas fa-chart-bar tab-icon-charts"></i>
                <span>Gráficos</span>
            </a>
        </li>
        <li class="tab-item">
            <a href="/admin/correos" class="tab-link <?= strpos($currentPage, 'correos') !== false ? 'active' : '' ?>">
                <i class="fas fa-envelope tab-icon-emails"></i>
                <span>Correos</span>
            </a>
        </li>
        <li class="tab-item">
            <a href="/admin/configuracion" class="tab-link <?= strpos($currentPage, 'configuracion') !== false ? 'active' : '' ?>">
                <i class="fas fa-cog tab-icon-settings"></i>
                <span>Configuración</span>
            </a>
        </li>
    </ul>
</nav>
