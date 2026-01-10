<?php
/**
 * FrigoTIC - Gestión de Facturas (Admin)
 */

$pageTitle = 'Gestión de Facturas';

require_once APP_PATH . '/models/Database.php';
require_once APP_PATH . '/models/Factura.php';

use App\Models\Factura;

$facturaModel = new Factura();

// Procesar acciones
$message = null;
$messageType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'upload':
                if (empty($_FILES['archivo']['name'])) {
                    throw new \Exception('Selecciona un archivo PDF');
                }
                
                $data = [
                    'descripcion' => trim($_POST['descripcion'] ?? ''),
                    'fecha_factura' => $_POST['fecha_factura'] ?? null
                ];
                
                $facturaModel->upload($_FILES['archivo'], $data, $_SESSION['user_id']);
                $message = 'Factura subida correctamente';
                $messageType = 'success';
                break;
                
            case 'update':
                $id = (int) $_POST['factura_id'];
                $data = [
                    'descripcion' => trim($_POST['descripcion'] ?? ''),
                    'fecha_factura' => $_POST['fecha_factura'] ?? null
                ];
                $facturaModel->update($id, $data);
                $message = 'Factura actualizada correctamente';
                $messageType = 'success';
                break;
                
            case 'delete':
                $id = (int) $_POST['factura_id'];
                $facturaModel->delete($id);
                $message = 'Factura eliminada correctamente';
                $messageType = 'success';
                break;
        }
    } catch (\Exception $e) {
        $message = $e->getMessage();
        $messageType = 'danger';
    }
}

// Descargar factura
if (isset($_GET['download'])) {
    $id = (int) $_GET['download'];
    $factura = $facturaModel->getById($id);
    if ($factura) {
        $ruta = $facturaModel->getRutaCompleta($id);
        if ($ruta) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $factura['nombre_original'] . '"');
            header('Content-Length: ' . filesize($ruta));
            readfile($ruta);
            exit;
        }
    }
}

// Obtener facturas
$page = (int) ($_GET['page'] ?? 1);
$perPage = (int) ($_GET['perPage'] ?? 10);
$filters = [
    'buscar' => $_GET['buscar'] ?? null,
    'fecha_desde' => $_GET['fecha_desde'] ?? null,
    'fecha_hasta' => $_GET['fecha_hasta'] ?? null,
];

$result = $facturaModel->getAll($filters, $page, $perPage);
$facturas = $result['data'];
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
    <h1><i class="fas fa-file-invoice"></i> Gestión de Facturas</h1>
    <button class="btn btn-primary" onclick="openUploadModal()">
        <i class="fas fa-file-upload"></i> Subir Factura
    </button>
</div>

<!-- Filtros -->
<div class="filters-container">
    <form method="GET" action="">
        <div class="filters-row">
            <div class="filter-group">
                <label>Buscar</label>
                <input type="text" name="buscar" class="form-control" 
                       placeholder="Nombre o descripción..." 
                       value="<?= htmlspecialchars($filters['buscar'] ?? '') ?>">
            </div>
            <div class="filter-group">
                <label>Fecha desde</label>
                <input type="date" name="fecha_desde" class="form-control" 
                       value="<?= htmlspecialchars($filters['fecha_desde'] ?? '') ?>">
            </div>
            <div class="filter-group">
                <label>Fecha hasta</label>
                <input type="date" name="fecha_hasta" class="form-control" 
                       value="<?= htmlspecialchars($filters['fecha_hasta'] ?? '') ?>">
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Filtrar
                </button>
                <a href="/frigotic/admin/facturas" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Limpiar
                </a>
            </div>
        </div>
    </form>
</div>

<!-- Tabla de facturas -->
<div class="card">
    <div class="card-body">
        <?php if (empty($facturas)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle alert-icon"></i>
                <div class="alert-content">No hay facturas registradas.</div>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Archivo</th>
                            <th>Descripción</th>
                            <th>Fecha Factura</th>
                            <th>Fecha Subida</th>
                            <th>Tamaño</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($facturas as $f): ?>
                            <tr>
                                <td>
                                    <i class="fas fa-file-pdf text-danger"></i>
                                    <?= htmlspecialchars($f['nombre_original']) ?>
                                </td>
                                <td><?= htmlspecialchars($f['descripcion'] ?? '-') ?></td>
                                <td><?= $f['fecha_factura'] ? date('d/m/Y', strtotime($f['fecha_factura'])) : '-' ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($f['fecha_subida'])) ?></td>
                                <td><?= number_format($f['tamano'] / 1024, 1) ?> KB</td>
                                <td>
                                    <div class="table-actions">
                                        <a href="?download=<?= $f['id'] ?>" class="btn btn-success btn-sm" title="Descargar">
                                            <i class="fas fa-download"></i>
                                        </a>
                                        <button class="btn btn-primary btn-sm" 
                                                onclick='openEditModal(<?= json_encode($f) ?>)'
                                                title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('¿Eliminar esta factura?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="factura_id" value="<?= $f['id'] ?>">
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
            <?php if ($totalPages > 1): ?>
                <div class="pagination-container">
                    <nav class="pagination-nav">
                        <ul class="pagination">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li>
                                    <a href="?page=<?= $i ?>&perPage=<?= $perPage ?>" 
                                       class="pagination-link <?= $i == $page ? 'active' : '' ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Subir Factura -->
<div class="modal-overlay" id="uploadModal">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title"><i class="fas fa-file-upload"></i> Subir Factura</h2>
            <button class="modal-close" onclick="closeUploadModal()">&times;</button>
        </div>
        <form method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" name="action" value="upload">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Archivo PDF *</label>
                    <input type="file" name="archivo" class="form-control" accept=".pdf" required>
                    <small class="form-text">Máximo 10MB</small>
                </div>
                <div class="form-group">
                    <label class="form-label">Fecha de la factura</label>
                    <input type="date" name="fecha_factura" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Descripción</label>
                    <textarea name="descripcion" class="form-control" rows="2" 
                              placeholder="Compra de bebidas, proveedor..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeUploadModal()">Cancelar</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-upload"></i> Subir</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Editar Factura -->
<div class="modal-overlay" id="editModal">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title"><i class="fas fa-edit"></i> Editar Factura</h2>
            <button class="modal-close" onclick="closeEditModal()">&times;</button>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="factura_id" id="edit_factura_id">
            <div class="modal-body">
                <p><strong>Archivo:</strong> <span id="edit_nombre"></span></p>
                <div class="form-group mt-3">
                    <label class="form-label">Fecha de la factura</label>
                    <input type="date" name="fecha_factura" id="edit_fecha" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Descripción</label>
                    <textarea name="descripcion" id="edit_descripcion" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancelar</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar</button>
            </div>
        </form>
    </div>
</div>

<script>
function openUploadModal() {
    document.getElementById('uploadModal').classList.add('active');
}

function closeUploadModal() {
    document.getElementById('uploadModal').classList.remove('active');
}

function openEditModal(factura) {
    document.getElementById('edit_factura_id').value = factura.id;
    document.getElementById('edit_nombre').textContent = factura.nombre_original;
    document.getElementById('edit_fecha').value = factura.fecha_factura || '';
    document.getElementById('edit_descripcion').value = factura.descripcion || '';
    document.getElementById('editModal').classList.add('active');
}

function closeEditModal() {
    document.getElementById('editModal').classList.remove('active');
}
</script>

<?php include APP_PATH . '/views/partials/footer.php'; ?>
