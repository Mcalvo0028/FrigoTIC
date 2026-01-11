<?php
/**
 * FrigoTIC - Movimientos (Admin)
 */

$pageTitle = 'Todos los Movimientos';

require_once APP_PATH . '/models/Database.php';
require_once APP_PATH . '/models/Movimiento.php';
require_once APP_PATH . '/models/Producto.php';
require_once APP_PATH . '/models/Usuario.php';
require_once APP_PATH . '/models/Configuracion.php';

use App\Models\Movimiento;
use App\Models\Producto;
use App\Models\Usuario;
use App\Models\Configuracion;

$movimientoModel = new Movimiento();
$productoModel = new Producto();
$usuarioModel = new Usuario();
$configModel = new Configuracion();
$defaultPerPage = (int) $configModel->get('items_por_pagina', 10);

// Parámetros de filtro y paginación
$page = (int) ($_GET['page'] ?? 1);
$perPage = (int) ($_GET['perPage'] ?? $defaultPerPage);
$filters = [
    'usuario_id' => $_GET['usuario_id'] ?? null,
    'producto_id' => $_GET['producto_id'] ?? null,
    'tipo' => $_GET['tipo'] ?? null,
    'fecha_desde' => $_GET['fecha_desde'] ?? null,
    'fecha_hasta' => $_GET['fecha_hasta'] ?? null,
];

$result = $movimientoModel->getAll($filters, $page, $perPage);
$movimientos = $result['data'];
$totalPages = $result['totalPages'];

$productos = $productoModel->getAll([], 1, 100)['data'];
$usuarios = $usuarioModel->getAll(['rol' => 'user'], 1, 100)['data'];

include APP_PATH . '/views/partials/header.php';
include APP_PATH . '/views/partials/admin-tabs.php';
?>

<div class="d-flex justify-between align-center mb-4 flex-wrap gap-3">
    <h1><i class="fas fa-exchange-alt"></i> Todos los Movimientos</h1>
    <a href="/export?action=export&type=movimientos&<?= http_build_query($filters) ?>" 
       target="_blank" class="btn btn-secondary">
        <i class="fas fa-file-pdf"></i> Exportar PDF
    </a>
</div>

<!-- Filtros -->
<div class="filters-container">
    <form method="GET" action="">
        <div class="filters-row">
            <div class="filter-group">
                <label>Usuario</label>
                <select name="usuario_id" class="form-control form-select">
                    <option value="">Todos</option>
                    <?php foreach ($usuarios as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= ($filters['usuario_id'] == $u['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($u['nombre_usuario']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
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
                    <option value="reposicion" <?= ($filters['tipo'] == 'reposicion') ? 'selected' : '' ?>>Reposición</option>
                    <option value="ajuste" <?= ($filters['tipo'] == 'ajuste') ? 'selected' : '' ?>>Ajuste</option>
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
        </div>
        <div class="filter-actions mt-3">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i> Filtrar
            </button>
            <a href="/admin/movimientos" class="btn btn-secondary">
                <i class="fas fa-times"></i> Limpiar
            </a>
        </div>
    </form>
</div>

<!-- Tabla de movimientos -->
<div class="card">
    <div class="card-body">
        <?php if (empty($movimientos)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle alert-icon"></i>
                <div class="alert-content">No hay movimientos registrados.</div>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Fecha/Hora</th>
                            <th>Usuario</th>
                            <th>Tipo</th>
                            <th>Producto</th>
                            <th class="text-center">Cant.</th>
                            <th class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($movimientos as $mov): ?>
                            <tr>
                                <td><?= date('d/m/Y H:i', strtotime($mov['fecha_hora'])) ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($mov['nombre_usuario']) ?></strong>
                                    <?php if ($mov['nombre_completo']): ?>
                                        <br><small class="text-muted"><?= htmlspecialchars($mov['nombre_completo']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $badges = [
                                        'consumo' => ['badge-danger', 'fa-shopping-cart'],
                                        'pago' => ['badge-success', 'fa-money-bill-wave'],
                                        'reposicion' => ['badge-info', 'fa-boxes'],
                                        'ajuste' => ['badge-warning', 'fa-sliders-h']
                                    ];
                                    $badge = $badges[$mov['tipo']] ?? ['badge-secondary', 'fa-exchange-alt'];
                                    ?>
                                    <span class="badge <?= $badge[0] ?>">
                                        <i class="fas <?= $badge[1] ?>"></i>
                                        <?= ucfirst($mov['tipo']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($mov['producto_nombre'] ?? '-') ?></td>
                                <td class="text-center"><?= $mov['cantidad'] ?></td>
                                <td class="text-right">
                                    <strong class="<?= $mov['tipo'] === 'consumo' ? 'text-danger' : ($mov['tipo'] === 'pago' ? 'text-success' : '') ?>">
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
                        <?php foreach ([5, 10, 25, 50, 100] as $opt): ?>
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
