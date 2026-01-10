<?php
/**
 * FrigoTIC - Dashboard Admin
 */

$pageTitle = 'Dashboard';

require_once APP_PATH . '/models/Database.php';
require_once APP_PATH . '/models/Usuario.php';
require_once APP_PATH . '/models/Producto.php';
require_once APP_PATH . '/models/Movimiento.php';

use App\Models\Usuario;
use App\Models\Producto;
use App\Models\Movimiento;

$usuarioModel = new Usuario();
$productoModel = new Producto();
$movimientoModel = new Movimiento();

// Estadísticas
$usuarios = $usuarioModel->getAll(['rol' => 'user']);
$estadisticasProductos = $productoModel->getEstadisticas();
$productosStockBajo = $productoModel->getStockBajo(5);

include APP_PATH . '/views/partials/header.php';
include APP_PATH . '/views/partials/admin-tabs.php';
?>

<h1 class="mb-5">
    <i class="fas fa-tachometer-alt"></i> Panel de Administración
</h1>

<!-- Estadísticas generales -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon primary">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-value"><?= $usuarios['total'] ?? 0 ?></div>
        <div class="stat-label">Usuarios registrados</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon success">
            <i class="fas fa-box"></i>
        </div>
        <div class="stat-value"><?= $estadisticasProductos['productos_activos'] ?? 0 ?></div>
        <div class="stat-label">Productos activos</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon warning">
            <i class="fas fa-cubes"></i>
        </div>
        <div class="stat-value"><?= $estadisticasProductos['stock_total'] ?? 0 ?></div>
        <div class="stat-label">Unidades en stock</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon danger">
            <i class="fas fa-euro-sign"></i>
        </div>
        <div class="stat-value"><?= number_format($estadisticasProductos['valor_inventario_venta'] ?? 0, 2, ',', '.') ?> €</div>
        <div class="stat-label">Valor del inventario</div>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 1.5rem;">
    <!-- Usuarios con deuda -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                <i class="fas fa-money-bill-wave"></i> Deudas Pendientes
            </h2>
            <a href="/admin/usuarios" class="btn btn-sm btn-secondary">Ver todos</a>
        </div>
        <div class="card-body">
            <?php 
            $usuariosConDeuda = $usuarioModel->getAllWithDeudas();
            $usuariosConDeuda = array_filter($usuariosConDeuda, fn($u) => $u['deuda'] > 0);
            usort($usuariosConDeuda, fn($a, $b) => $b['deuda'] <=> $a['deuda']);
            $usuariosConDeuda = array_slice($usuariosConDeuda, 0, 5);
            ?>
            
            <?php if (empty($usuariosConDeuda)): ?>
                <div class="alert alert-success" style="margin: 0;">
                    <i class="fas fa-check-circle alert-icon"></i>
                    <div class="alert-content">¡No hay deudas pendientes!</div>
                </div>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th class="text-right">Deuda</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuariosConDeuda as $u): ?>
                            <tr>
                                <td><?= htmlspecialchars($u['nombre_usuario']) ?></td>
                                <td class="text-right">
                                    <strong class="text-danger"><?= number_format($u['deuda'], 2, ',', '.') ?> €</strong>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Productos con stock bajo -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">
                <i class="fas fa-exclamation-triangle"></i> Stock Bajo
            </h2>
            <a href="/admin/productos" class="btn btn-sm btn-secondary">Ver todos</a>
        </div>
        <div class="card-body">
            <?php if (empty($productosStockBajo)): ?>
                <div class="alert alert-success" style="margin: 0;">
                    <i class="fas fa-check-circle alert-icon"></i>
                    <div class="alert-content">Todos los productos tienen stock suficiente.</div>
                </div>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th class="text-right">Stock</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productosStockBajo as $p): ?>
                            <tr>
                                <td><?= htmlspecialchars($p['nombre']) ?></td>
                                <td class="text-right">
                                    <span class="badge <?= $p['stock'] == 0 ? 'badge-danger' : 'badge-warning' ?>">
                                        <?= $p['stock'] ?> uds
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Accesos rápidos -->
<div class="card mt-5">
    <div class="card-header">
        <h2 class="card-title">
            <i class="fas fa-bolt"></i> Accesos Rápidos
        </h2>
    </div>
    <div class="card-body">
        <div class="d-flex gap-3 flex-wrap">
            <a href="/admin/usuarios" class="btn btn-primary btn-lg">
                <i class="fas fa-user-plus"></i> Nuevo Usuario
            </a>
            <a href="/admin/productos" class="btn btn-primary btn-lg">
                <i class="fas fa-plus-circle"></i> Nuevo Producto
            </a>
            <a href="/admin/facturas" class="btn btn-primary btn-lg">
                <i class="fas fa-file-upload"></i> Subir Factura
            </a>
            <a href="/admin/graficos" class="btn btn-primary btn-lg">
                <i class="fas fa-chart-bar"></i> Ver Estadísticas
            </a>
        </div>
    </div>
</div>

<?php include APP_PATH . '/views/partials/footer.php'; ?>
