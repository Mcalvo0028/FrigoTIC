<?php
/**
 * FrigoTIC - Vista de Movimientos (Usuario)
 */

$pageTitle = 'Mis Movimientos';

require_once APP_PATH . '/models/Database.php';
require_once APP_PATH . '/models/Movimiento.php';
require_once APP_PATH . '/models/Producto.php';

use App\Models\Movimiento;
use App\Models\Producto;

$movimientoModel = new Movimiento();
$productoModel = new Producto();

// Parámetros de filtro y paginación
$page = (int) ($_GET['page'] ?? 1);
$perPage = (int) ($_GET['perPage'] ?? 10);
$filters = [
    'usuario_id' => $_SESSION['user_id'],
    'producto_id' => $_GET['producto_id'] ?? null,
    'tipo' => $_GET['tipo'] ?? null,
    'fecha_desde' => $_GET['fecha_desde'] ?? null,
    'fecha_hasta' => $_GET['fecha_hasta'] ?? null,
];

$result = $movimientoModel->getAll($filters, $page, $perPage);
$movimientos = $result['data'];
$totalPages = $result['totalPages'];

$productos = $productoModel->getActivos();
$resumen = $movimientoModel->getResumenUsuario($_SESSION['user_id']);

include APP_PATH . '/views/partials/header.php';
include APP_PATH . '/views/partials/user-tabs.php';
?>

<!-- Resumen -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon danger">
            <i class="fas fa-shopping-cart"></i>
        </div>
        <div class="stat-value"><?= number_format($resumen['total_consumos'] ?? 0, 2, ',', '.') ?> €</div>
        <div class="stat-label">Total consumido</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon success">
            <i class="fas fa-money-bill-wave"></i>
        </div>
        <div class="stat-value"><?= number_format($resumen['total_pagos'] ?? 0, 2, ',', '.') ?> €</div>
        <div class="stat-label">Total pagado</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon warning">
            <i class="fas fa-euro-sign"></i>
        </div>
        <div class="stat-value"><?= number_format($resumen['deuda'] ?? 0, 2, ',', '.') ?> €</div>
        <div class="stat-label">Deuda pendiente</div>
    </div>
</div>

<!-- Filtros -->
<div class="filters-container">
    <form method="GET" action="">
        <div class="filters-row">
            <div class="filter-group">
                <label>Producto</label>
                <select name="producto_id" class="form-control form-select">
                    <option value="">Todos</option>
                    <?php foreach ($productos as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= ($filters['producto_id'] == $p['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>Tipo</label>
                <select name="tipo" class="form-control form-select">
                    <option value="">Todos</option>
                    <option value="consumo" <?= ($filters['tipo'] == 'consumo') ? 'selected' : '' ?>>Consumo</option>
                    <option value="pago" <?= ($filters['tipo'] == 'pago') ? 'selected' : '' ?>>Pago</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Desde</label>
                <input type="date" name="fecha_desde" class="form-control" value="<?= htmlspecialchars($filters['fecha_desde'] ?? '') ?>">
            </div>
            <div class="filter-group">
                <label>Hasta</label>
                <input type="date" name="fecha_hasta" class="form-control" value="<?= htmlspecialchars($filters['fecha_hasta'] ?? '') ?>">
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Filtrar
                </button>
                <a href="/user/movimientos" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Limpiar
                </a>
            </div>
        </div>
    </form>
</div>

<!-- Tabla de movimientos -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">
            <i class="fas fa-history"></i> Historial de Movimientos
        </h2>
    </div>
    <div class="card-body">
        <?php if (empty($movimientos)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle alert-icon"></i>
                <div class="alert-content">No tienes movimientos registrados.</div>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($movimientos as $mov): ?>
                            <tr>
                                <td><?= date('d/m/Y H:i', strtotime($mov['fecha_hora'])) ?></td>
                                <td>
                                    <span class="badge <?= $mov['tipo'] === 'consumo' ? 'badge-danger' : 'badge-success' ?>">
                                        <i class="fas <?= $mov['tipo'] === 'consumo' ? 'fa-shopping-cart' : 'fa-money-bill-wave' ?>"></i>
                                        <?= ucfirst($mov['tipo']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($mov['producto_nombre'] ?? '-') ?></td>
                                <td><?= $mov['cantidad'] ?></td>
                                <td class="text-right">
                                    <strong class="<?= $mov['tipo'] === 'consumo' ? 'text-danger' : 'text-success' ?>">
                                        <?= $mov['tipo'] === 'consumo' ? '-' : '+' ?>
                                        <?= number_format($mov['total'], 2, ',', '.') ?> €
                                    </strong>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <div class="pagination-container">
                <div class="per-page-selector">
                    <label>Mostrar:</label>
                    <select onchange="window.location.href='?perPage='+this.value">
                        <?php foreach ([5, 10, 25, 50] as $opt): ?>
                            <option value="<?= $opt ?>" <?= $perPage == $opt ? 'selected' : '' ?>><?= $opt ?></option>
                        <?php endforeach; ?>
                    </select>
                    <span>elementos</span>
                </div>
                
                <?php if ($totalPages > 1): ?>
                    <nav class="pagination-nav">
                        <ul class="pagination">
                            <?php if ($page > 1): ?>
                                <li><a href="?page=<?= $page - 1 ?>&perPage=<?= $perPage ?>" class="pagination-link"><i class="fas fa-chevron-left"></i></a></li>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <li>
                                    <a href="?page=<?= $i ?>&perPage=<?= $perPage ?>" 
                                       class="pagination-link <?= $i == $page ? 'active' : '' ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <li><a href="?page=<?= $page + 1 ?>&perPage=<?= $perPage ?>" class="pagination-link"><i class="fas fa-chevron-right"></i></a></li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include APP_PATH . '/views/partials/footer.php'; ?>
