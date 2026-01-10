<?php
/**
 * FrigoTIC - Pestañas de Navegación de Admin
 */
$currentPage = $_SERVER['REQUEST_URI'] ?? '';
?>
<nav class="tabs-nav">
    <ul class="tabs-list">
        <li class="tab-item">
            <a href="/admin/usuarios" class="tab-link <?= strpos($currentPage, 'usuarios') !== false ? 'active' : '' ?>">
                <i class="fas fa-users"></i>
                <span>Usuarios</span>
            </a>
        </li>
        <li class="tab-item">
            <a href="/admin/productos" class="tab-link <?= strpos($currentPage, 'productos') !== false ? 'active' : '' ?>">
                <i class="fas fa-box"></i>
                <span>Productos</span>
            </a>
        </li>
        <li class="tab-item">
            <a href="/admin/facturas" class="tab-link <?= strpos($currentPage, 'facturas') !== false ? 'active' : '' ?>">
                <i class="fas fa-file-invoice"></i>
                <span>Facturas</span>
            </a>
        </li>
        <li class="tab-item">
            <a href="/admin/movimientos" class="tab-link <?= strpos($currentPage, 'movimientos') !== false ? 'active' : '' ?>">
                <i class="fas fa-exchange-alt"></i>
                <span>Movimientos</span>
            </a>
        </li>
        <li class="tab-item">
            <a href="/admin/graficos" class="tab-link <?= strpos($currentPage, 'graficos') !== false ? 'active' : '' ?>">
                <i class="fas fa-chart-bar"></i>
                <span>Gráficos</span>
            </a>
        </li>
        <li class="tab-item">
            <a href="/admin/configuracion" class="tab-link <?= strpos($currentPage, 'configuracion') !== false ? 'active' : '' ?>">
                <i class="fas fa-cog"></i>
                <span>Configuración</span>
            </a>
        </li>
    </ul>
</nav>
