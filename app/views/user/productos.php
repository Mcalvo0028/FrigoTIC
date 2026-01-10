<?php
/**
 * FrigoTIC - Vista de Productos (Usuario)
 */

$pageTitle = 'Productos';

// Obtener datos
require_once APP_PATH . '/models/Database.php';
require_once APP_PATH . '/models/Producto.php';
require_once APP_PATH . '/models/Movimiento.php';

use App\Models\Producto;
use App\Models\Movimiento;

$productoModel = new Producto();
$movimientoModel = new Movimiento();

$productos = $productoModel->getActivos();
$resumen = $movimientoModel->getResumenUsuario($_SESSION['user_id']);

include APP_PATH . '/views/partials/header.php';
include APP_PATH . '/views/partials/user-tabs.php';
?>

<!-- Resumen del usuario -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon primary">
            <i class="fas fa-shopping-cart"></i>
        </div>
        <div class="stat-value"><?= $resumen['cantidad_productos'] ?? 0 ?></div>
        <div class="stat-label">Productos consumidos</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon warning">
            <i class="fas fa-euro-sign"></i>
        </div>
        <div class="stat-value"><?= number_format($resumen['deuda'] ?? 0, 2, ',', '.') ?> €</div>
        <div class="stat-label">Deuda actual</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon success">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-value"><?= number_format($resumen['total_pagos'] ?? 0, 2, ',', '.') ?> €</div>
        <div class="stat-label">Total pagado</div>
    </div>
</div>

<!-- Lista de productos -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">
            <i class="fas fa-box"></i> Productos Disponibles
        </h2>
    </div>
    <div class="card-body">
        <?php if (empty($productos)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle alert-icon"></i>
                <div class="alert-content">No hay productos disponibles en este momento.</div>
            </div>
        <?php else: ?>
            <div class="products-grid">
                <?php foreach ($productos as $producto): ?>
                    <div class="product-card">
                        <?php if ($producto['imagen']): ?>
                            <img src="/frigotic/uploads/productos/<?= htmlspecialchars($producto['imagen']) ?>" 
                                 alt="<?= htmlspecialchars($producto['nombre']) ?>" 
                                 class="product-image">
                        <?php else: ?>
                            <div class="product-image-placeholder">
                                <i class="fas fa-box"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="product-info">
                            <h3 class="product-name"><?= htmlspecialchars($producto['nombre']) ?></h3>
                            <div class="product-price"><?= number_format($producto['precio_venta'], 2, ',', '.') ?> €</div>
                            <div class="product-stock <?= $producto['stock'] <= 5 ? ($producto['stock'] == 0 ? 'out' : 'low') : '' ?>">
                                <i class="fas fa-cubes"></i>
                                <?php if ($producto['stock'] == 0): ?>
                                    Sin stock
                                <?php else: ?>
                                    <?= $producto['stock'] ?> disponibles
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="product-actions">
                            <button 
                                class="btn btn-primary" 
                                style="width: 100%;"
                                onclick="registrarConsumo(<?= $producto['id'] ?>, '<?= htmlspecialchars($producto['nombre']) ?>')"
                                <?= $producto['stock'] == 0 ? 'disabled' : '' ?>
                            >
                                <i class="fas fa-plus-circle"></i> Coger
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal de confirmación -->
<div class="modal-overlay" id="confirmModal">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title">
                <i class="fas fa-shopping-cart"></i> Confirmar Consumo
            </h2>
            <button class="modal-close" onclick="closeConfirmModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p>¿Confirmas que coges <strong id="confirmProductName"></strong>?</p>
            <div class="form-group mt-4">
                <label for="cantidad" class="form-label">Cantidad:</label>
                <input type="number" id="cantidad" class="form-control" value="1" min="1" max="10">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeConfirmModal()">Cancelar</button>
            <button class="btn btn-primary" id="confirmBtn">
                <i class="fas fa-check"></i> Confirmar
            </button>
        </div>
    </div>
</div>

<script>
let selectedProductId = null;

function registrarConsumo(productoId, productoNombre) {
    selectedProductId = productoId;
    document.getElementById('confirmProductName').textContent = productoNombre;
    document.getElementById('cantidad').value = 1;
    document.getElementById('confirmModal').classList.add('active');
}

function closeConfirmModal() {
    document.getElementById('confirmModal').classList.remove('active');
    selectedProductId = null;
}

document.getElementById('confirmBtn').addEventListener('click', async function() {
    if (!selectedProductId) return;
    
    const cantidad = document.getElementById('cantidad').value;
    
    try {
        const response = await fetch('/frigotic/api/consumo', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: `producto_id=${selectedProductId}&cantidad=${cantidad}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            closeConfirmModal();
            location.reload();
        } else {
            alert(data.message || 'Error al registrar el consumo');
        }
    } catch (error) {
        alert('Error de conexión');
    }
});
</script>

<?php include APP_PATH . '/views/partials/footer.php'; ?>
