<?php
/**
 * FrigoTIC - Pestañas de Navegación de Usuario
 */
$currentPage = $_SERVER['REQUEST_URI'] ?? '';
?>
<nav class="tabs-nav">
    <ul class="tabs-list">
        <li class="tab-item">
            <a href="/user/productos" class="tab-link <?= strpos($currentPage, 'productos') !== false ? 'active' : '' ?>">
                <i class="fas fa-shopping-cart tab-icon-products"></i>
                <span>Productos</span>
            </a>
        </li>
        <li class="tab-item">
            <a href="/user/movimientos" class="tab-link <?= strpos($currentPage, 'movimientos') !== false ? 'active' : '' ?>">
                <i class="fas fa-history tab-icon-movements"></i>
                <span>Movimientos</span>
            </a>
        </li>
        <li class="tab-item">
            <a href="/user/perfil" class="tab-link <?= strpos($currentPage, 'perfil') !== false ? 'active' : '' ?>">
                <i class="fas fa-user-cog tab-icon-profile"></i>
                <span>Perfil</span>
            </a>
        </li>
    </ul>
</nav>
