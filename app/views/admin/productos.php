<?php
/**
 * FrigoTIC - Gestión de Productos (Admin)
 */

$pageTitle = 'Gestión de Productos';

require_once APP_PATH . '/models/Database.php';
require_once APP_PATH . '/models/Producto.php';
require_once APP_PATH . '/models/Movimiento.php';
require_once APP_PATH . '/models/Configuracion.php';

use App\Models\Producto;
use App\Models\Movimiento;
use App\Models\Configuracion;

$productoModel = new Producto();
$movimientoModel = new Movimiento();
$configModel = new Configuracion();
$defaultPerPage = (int) $configModel->get('items_por_pagina', 10);

// Procesar acciones
$message = null;
$messageType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create':
            $data = [
                'nombre' => trim($_POST['nombre'] ?? ''),
                'descripcion' => trim($_POST['descripcion'] ?? ''),
                'precio_compra' => (float) $_POST['precio_compra'],
                'precio_venta' => (float) $_POST['precio_venta'],
                'stock' => (int) $_POST['stock'],
                'stock_minimo' => (int) ($_POST['stock_minimo'] ?? 5),
            ];
            
            // Procesar imagen
            if (!empty($_FILES['imagen']['name'])) {
                $ext = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $nombreImagen = uniqid('prod_') . '.' . $ext;
                    $rutaDestino = PUBLIC_PATH . '/uploads/productos/' . $nombreImagen;
                    if (move_uploaded_file($_FILES['imagen']['tmp_name'], $rutaDestino)) {
                        $data['imagen'] = $nombreImagen;
                    }
                }
            }
            
            $productoModel->create($data);
            $message = 'Producto creado correctamente';
            $messageType = 'success';
            break;
            
        case 'update':
            $id = (int) $_POST['producto_id'];
            $data = [
                'nombre' => trim($_POST['nombre'] ?? ''),
                'descripcion' => trim($_POST['descripcion'] ?? ''),
                'precio_compra' => (float) $_POST['precio_compra'],
                'precio_venta' => (float) $_POST['precio_venta'],
                'stock' => (int) $_POST['stock'],
                'stock_minimo' => (int) ($_POST['stock_minimo'] ?? 5),
            ];
            
            // Procesar nueva imagen
            if (!empty($_FILES['imagen']['name'])) {
                $ext = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $nombreImagen = uniqid('prod_') . '.' . $ext;
                    $rutaDestino = PUBLIC_PATH . '/uploads/productos/' . $nombreImagen;
                    if (move_uploaded_file($_FILES['imagen']['tmp_name'], $rutaDestino)) {
                        $data['imagen'] = $nombreImagen;
                    }
                }
            }
            
            $productoModel->update($id, $data);
            $message = 'Producto actualizado correctamente';
            $messageType = 'success';
            break;
            
        case 'delete':
            $id = (int) $_POST['producto_id'];
            $productoModel->delete($id);
            $message = 'Producto eliminado correctamente';
            $messageType = 'success';
            break;
            
        case 'restock':
            $id = (int) $_POST['producto_id'];
            $cantidad = (int) $_POST['cantidad'];
            $movimientoModel->registrarReposicion($_SESSION['user_id'], $id, $cantidad);
            $message = "Stock aumentado en {$cantidad} unidades";
            $messageType = 'success';
            break;
    }
}

// Obtener productos
$page = (int) ($_GET['page'] ?? 1);
$perPage = (int) ($_GET['perPage'] ?? $defaultPerPage);
$filters = ['buscar' => $_GET['buscar'] ?? null];

$result = $productoModel->getAll($filters, $page, $perPage);
$productos = $result['data'];
$totalPages = $result['totalPages'];

include APP_PATH . '/views/partials/header.php';
include APP_PATH . '/views/partials/admin-tabs.php';
?>

<?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?>">
        <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-circle' ?> alert-icon"></i>
        <div class="alert-content"><?= htmlspecialchars($message) ?></div>
    </div>
<?php endif; ?>

<div class="d-flex justify-between align-center mb-4 flex-wrap gap-3">
    <h1><i class="fas fa-box"></i> Gestión de Productos</h1>
    <div class="d-flex gap-2">
        <a href="/export?action=export&type=productos&<?= http_build_query($filters) ?>" 
           target="_blank" class="btn btn-secondary">
            <i class="fas fa-file-pdf"></i> Exportar PDF
        </a>
        <button class="btn btn-primary" onclick="openCreateModal()">
            <i class="fas fa-plus-circle"></i> Nuevo Producto
        </button>
    </div>
</div>

<!-- Búsqueda -->
<div class="filters-container">
    <form method="GET" action="">
        <div class="filters-row">
            <div class="filter-group" style="flex: 2;">
                <label>Buscar</label>
                <input type="text" name="buscar" class="form-control" 
                       placeholder="Nombre del producto..." 
                       value="<?= htmlspecialchars($filters['buscar'] ?? '') ?>">
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Buscar
                </button>
                <a href="/admin/productos" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Limpiar
                </a>
            </div>
        </div>
    </form>
</div>

<!-- Tabla de productos -->
<div class="card">
    <div class="card-body">
        <?php if (empty($productos)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle alert-icon"></i>
                <div class="alert-content">No hay productos registrados.</div>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width: 60px;">Imagen</th>
                            <th>Nombre</th>
                            <th class="text-right">P. Compra</th>
                            <th class="text-right">P. Venta</th>
                            <th class="text-center">Stock</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productos as $p): ?>
                            <tr>
                                <td>
                                    <?php if ($p['imagen']): ?>
                                        <img src="/uploads/productos/<?= htmlspecialchars($p['imagen']) ?>" 
                                             alt="" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                                    <?php else: ?>
                                        <div style="width: 50px; height: 50px; background: var(--color-gray-200); border-radius: 4px; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-box text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($p['nombre']) ?></strong>
                                    <?php if ($p['descripcion']): ?>
                                        <br><small class="text-muted"><?= htmlspecialchars(substr($p['descripcion'], 0, 50)) ?>...</small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-right"><?= number_format($p['precio_compra'], 2, ',', '.') ?> €</td>
                                <td class="text-right"><strong><?= number_format($p['precio_venta'], 2, ',', '.') ?> €</strong></td>
                                <td class="text-center">
                                    <?php 
                                    $stockMinimo = $p['stock_minimo'] ?? 5;
                                    $stockClass = $p['stock'] == 0 ? 'badge-danger' : ($p['stock'] <= $stockMinimo ? 'badge-warning' : 'badge-success');
                                    ?>
                                    <span class="badge <?= $stockClass ?>">
                                        <?= $p['stock'] ?>
                                    </span>
                                    <?php if ($p['stock'] <= $stockMinimo && $p['stock'] > 0): ?>
                                        <br><small class="text-warning">Min: <?= $stockMinimo ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?= $p['activo'] ? 'badge-success' : 'badge-danger' ?>">
                                        <?= $p['activo'] ? 'Activo' : 'Inactivo' ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <button class="btn btn-success btn-sm" 
                                                onclick="openRestockModal(<?= $p['id'] ?>, '<?= htmlspecialchars($p['nombre']) ?>')"
                                                title="Añadir stock">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                        <button class="btn btn-primary btn-sm" 
                                                onclick='openEditModal(<?= json_encode($p) ?>)'
                                                title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('¿Eliminar este producto?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="producto_id" value="<?= $p['id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
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
                    <select onchange="window.location.href='?perPage='+this.value+'&<?= http_build_query(array_filter($filters)) ?>'">
                        <?php foreach ([5, 10, 25, 50] as $opt): ?>
                            <option value="<?= $opt ?>" <?= $perPage == $opt ? 'selected' : '' ?>><?= $opt ?></option>
                        <?php endforeach; ?>
                    </select>
                    <span>elementos</span>
                </div>
                <?php if ($totalPages > 1): ?>
                    <nav class="pagination-nav">
                        <ul class="pagination">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li>
                                    <a href="?page=<?= $i ?>&perPage=<?= $perPage ?>&<?= http_build_query(array_filter($filters)) ?>" 
                                       class="pagination-link <?= $i == $page ? 'active' : '' ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Crear Producto -->
<div class="modal-overlay" id="createModal">
    <div class="modal" style="max-width: 600px;">
        <div class="modal-header">
            <h2 class="modal-title"><i class="fas fa-plus-circle"></i> Nuevo Producto</h2>
            <button class="modal-close" onclick="closeCreateModal()">&times;</button>
        </div>
        <form method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" name="action" value="create">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Nombre *</label>
                    <input type="text" name="nombre" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Descripción</label>
                    <textarea name="descripcion" class="form-control" rows="2"></textarea>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Precio Compra (€)</label>
                        <input type="number" name="precio_compra" class="form-control" step="0.01" min="0" value="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Precio Venta (€) *</label>
                        <input type="number" name="precio_venta" class="form-control" step="0.01" min="0" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Stock inicial</label>
                    <input type="number" name="stock" class="form-control" min="0" value="0">
                </div>
                <div class="form-group">
                    <label class="form-label">Stock mínimo (alerta)</label>
                    <input type="number" name="stock_minimo" class="form-control" min="0" value="5">
                    <small class="form-text">Se mostrará alerta cuando el stock sea igual o menor a este valor</small>
                </div>
                <div class="form-group">
                    <label class="form-label">Imagen</label>
                    <input type="file" name="imagen" class="form-control" accept="image/*">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeCreateModal()">Cancelar</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Crear</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Editar Producto -->
<div class="modal-overlay" id="editModal">
    <div class="modal" style="max-width: 600px;">
        <div class="modal-header">
            <h2 class="modal-title"><i class="fas fa-edit"></i> Editar Producto</h2>
            <button class="modal-close" onclick="closeEditModal()">&times;</button>
        </div>
        <form method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="producto_id" id="edit_producto_id">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Nombre *</label>
                    <input type="text" name="nombre" id="edit_nombre" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Descripción</label>
                    <textarea name="descripcion" id="edit_descripcion" class="form-control" rows="2"></textarea>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Precio Compra (€)</label>
                        <input type="number" name="precio_compra" id="edit_precio_compra" class="form-control" step="0.01" min="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Precio Venta (€) *</label>
                        <input type="number" name="precio_venta" id="edit_precio_venta" class="form-control" step="0.01" min="0" required>
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label class="form-label">Stock</label>
                        <input type="number" name="stock" id="edit_stock" class="form-control" min="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Stock mínimo (alerta)</label>
                        <input type="number" name="stock_minimo" id="edit_stock_minimo" class="form-control" min="0">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Nueva imagen (opcional)</label>
                    <input type="file" name="imagen" class="form-control" accept="image/*">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancelar</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Añadir Stock -->
<div class="modal-overlay" id="restockModal">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title"><i class="fas fa-boxes"></i> Añadir Stock</h2>
            <button class="modal-close" onclick="closeRestockModal()">&times;</button>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="action" value="restock">
            <input type="hidden" name="producto_id" id="restock_producto_id">
            <div class="modal-body">
                <p>Producto: <strong id="restock_nombre"></strong></p>
                <div class="form-group mt-4">
                    <label class="form-label">Cantidad a añadir</label>
                    <input type="number" name="cantidad" class="form-control" min="1" value="1" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeRestockModal()">Cancelar</button>
                <button type="submit" class="btn btn-success"><i class="fas fa-plus"></i> Añadir</button>
            </div>
        </form>
    </div>
</div>

<script>
function openCreateModal() {
    document.getElementById('createModal').classList.add('active');
}

function closeCreateModal() {
    document.getElementById('createModal').classList.remove('active');
}

function openEditModal(producto) {
    document.getElementById('edit_producto_id').value = producto.id;
    document.getElementById('edit_nombre').value = producto.nombre;
    document.getElementById('edit_descripcion').value = producto.descripcion || '';
    document.getElementById('edit_precio_compra').value = producto.precio_compra;
    document.getElementById('edit_precio_venta').value = producto.precio_venta;
    document.getElementById('edit_stock').value = producto.stock;
    document.getElementById('edit_stock_minimo').value = producto.stock_minimo || 5;
    document.getElementById('editModal').classList.add('active');
}

function closeEditModal() {
    document.getElementById('editModal').classList.remove('active');
}

function openRestockModal(id, nombre) {
    document.getElementById('restock_producto_id').value = id;
    document.getElementById('restock_nombre').textContent = nombre;
    document.getElementById('restockModal').classList.add('active');
}

function closeRestockModal() {
    document.getElementById('restockModal').classList.remove('active');
}
</script>

<?php include APP_PATH . '/views/partials/footer.php'; ?>
