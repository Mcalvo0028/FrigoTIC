<?php
/**
 * FrigoTIC - Gestión de Usuarios (Admin)
 */

$pageTitle = 'Gestión de Usuarios';

require_once APP_PATH . '/models/Database.php';
require_once APP_PATH . '/models/Usuario.php';
require_once APP_PATH . '/models/Configuracion.php';

use App\Models\Usuario;
use App\Models\Configuracion;

$usuarioModel = new Usuario();
$configModel = new Configuracion();
$passwordDefault = $configModel->get('password_default', 'Cambiar123');
$defaultPerPage = (int) $configModel->get('items_por_pagina', 10);

// Procesar acciones
$message = null;
$messageType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create':
            $passwordToUse = !empty($_POST['password']) ? $_POST['password'] : $passwordDefault;
            $data = [
                'nombre_usuario' => trim($_POST['nombre_usuario'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'telefono' => trim($_POST['telefono'] ?? ''),
                'nombre_completo' => trim($_POST['nombre_completo'] ?? ''),
                'password' => $passwordToUse,
                'rol' => 'user',
                'debe_cambiar_password' => 1
            ];
            
            if ($usuarioModel->usernameExists($data['nombre_usuario'])) {
                $message = 'El nombre de usuario ya existe';
                $messageType = 'danger';
            } elseif ($usuarioModel->emailExists($data['email'])) {
                $message = 'El email ya está en uso';
                $messageType = 'danger';
            } else {
                $usuarioModel->create($data);
                $message = 'Usuario creado correctamente';
                $messageType = 'success';
                
                // Enviar correo de bienvenida si está marcado
                if (isset($_POST['enviar_bienvenida']) && !empty($data['email'])) {
                    require_once APP_PATH . '/helpers/EnvHelper.php';
                    require_once APP_PATH . '/helpers/EmailHelper.php';
                    $emailHelper = new \App\Helpers\EmailHelper();
                    
                    $nombreMostrar = !empty($data['nombre_completo']) ? $data['nombre_completo'] : $data['nombre_usuario'];
                    if ($emailHelper->sendWelcome($data['email'], $nombreMostrar, $data['nombre_usuario'], $passwordToUse)) {
                        $message .= '. Correo de bienvenida enviado.';
                    } else {
                        $message .= '. Error al enviar correo de bienvenida.';
                    }
                }
            }
            break;
            
        case 'reset_password':
            $userId = (int) $_POST['user_id'];
            $usuarioModel->resetPassword($userId, $passwordDefault);
            $message = 'Contraseña reseteada a "' . htmlspecialchars($passwordDefault) . '"';
            $messageType = 'success';
            break;
            
        case 'register_payment':
            require_once APP_PATH . '/models/Movimiento.php';
            $movimiento = new \App\Models\Movimiento();
            $movimiento->registrarPago(
                (int) $_POST['user_id'],
                (float) $_POST['cantidad'],
                $_POST['descripcion'] ?? 'Pago de deuda'
            );
            $message = 'Pago registrado correctamente';
            $messageType = 'success';
            break;
    }
}

// Obtener usuarios
$page = (int) ($_GET['page'] ?? 1);
$perPage = (int) ($_GET['perPage'] ?? $defaultPerPage);
$filters = [
    'rol' => 'user',
    'buscar' => $_GET['buscar'] ?? null
];

$result = $usuarioModel->getAll($filters, $page, $perPage);
$usuarios = $result['data'];
$totalPages = $result['totalPages'];

// Obtener usuarios con deudas
$usuariosConDeudas = $usuarioModel->getAllWithDeudas();
$deudasMap = [];
foreach ($usuariosConDeudas as $u) {
    $deudasMap[$u['id']] = $u['deuda'];
}

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
    <h1><i class="fas fa-users"></i> Gestión de Usuarios</h1>
    <div class="d-flex gap-2">
        <a href="/export?action=export&type=usuarios&<?= http_build_query($filters) ?>" 
           target="_blank" class="btn btn-secondary">
            <i class="fas fa-file-pdf"></i> Exportar PDF
        </a>
        <button class="btn btn-primary" onclick="openCreateModal()">
            <i class="fas fa-user-plus"></i> Nuevo Usuario
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
                       placeholder="Nombre de usuario, email..." 
                       value="<?= htmlspecialchars($filters['buscar'] ?? '') ?>">
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Buscar
                </button>
                <a href="/admin/usuarios" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Limpiar
                </a>
            </div>
        </div>
    </form>
</div>

<!-- Tabla de usuarios -->
<div class="card">
    <div class="card-body">
        <?php if (empty($usuarios)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle alert-icon"></i>
                <div class="alert-content">No hay usuarios registrados.</div>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Email</th>
                            <th>Teléfono</th>
                            <th>Nombre</th>
                            <th class="text-right">Deuda</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $u): ?>
                            <?php $deuda = $deudasMap[$u['id']] ?? 0; ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($u['nombre_usuario']) ?></strong></td>
                                <td><?= htmlspecialchars($u['email']) ?></td>
                                <td><?= htmlspecialchars($u['telefono'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($u['nombre_completo'] ?? '-') ?></td>
                                <td class="text-right">
                                    <?php if ($deuda > 0): ?>
                                        <strong class="text-danger"><?= number_format($deuda, 2, ',', '.') ?> €</strong>
                                    <?php else: ?>
                                        <span class="text-success">0,00 €</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <?php if ($deuda > 0): ?>
                                            <button class="btn btn-success btn-sm" 
                                                    onclick="openPaymentModal(<?= $u['id'] ?>, '<?= htmlspecialchars($u['nombre_usuario']) ?>', <?= $deuda ?>)"
                                                    title="Registrar pago">
                                                <i class="fas fa-money-bill-wave"></i>
                                            </button>
                                        <?php endif; ?>
                                        <button class="btn btn-warning btn-sm" 
                                                onclick="resetPassword(<?= $u['id'] ?>)"
                                                title="Resetear contraseña">
                                            <i class="fas fa-key"></i>
                                        </button>
                                        <button class="btn btn-danger btn-sm" 
                                                onclick="deleteUser(<?= $u['id'] ?>, '<?= htmlspecialchars($u['nombre_usuario']) ?>')"
                                                title="Eliminar usuario">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
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

<!-- Modal Crear Usuario -->
<div class="modal-overlay" id="createModal">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title"><i class="fas fa-user-plus"></i> Nuevo Usuario</h2>
            <button class="modal-close" onclick="closeCreateModal()">&times;</button>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="action" value="create">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Nombre de usuario *</label>
                    <input type="text" name="nombre_usuario" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Teléfono</label>
                    <input type="tel" name="telefono" class="form-control" placeholder="Opcional">
                </div>
                <div class="form-group">
                    <label class="form-label">Nombre completo</label>
                    <input type="text" name="nombre_completo" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Contraseña inicial</label>
                    <input type="text" name="password" class="form-control" value="<?= htmlspecialchars($passwordDefault) ?>">
                    <small class="form-text">El usuario deberá cambiarla en el primer inicio.</small>
                </div>
                <div class="form-group">
                    <label class="form-check">
                        <input type="checkbox" name="enviar_bienvenida" value="1" checked>
                        <span><i class="fas fa-envelope"></i> Enviar correo de bienvenida</span>
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeCreateModal()">Cancelar</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Crear</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Registrar Pago -->
<div class="modal-overlay" id="paymentModal">
    <div class="modal">
        <div class="modal-header">
            <h2 class="modal-title"><i class="fas fa-money-bill-wave"></i> Registrar Pago</h2>
            <button class="modal-close" onclick="closePaymentModal()">&times;</button>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="action" value="register_payment">
            <input type="hidden" name="user_id" id="payment_user_id">
            <div class="modal-body">
                <p>Usuario: <strong id="payment_username"></strong></p>
                <p>Deuda actual: <strong id="payment_deuda" class="text-danger"></strong></p>
                <div class="form-group mt-4">
                    <label class="form-label">Cantidad a pagar (€)</label>
                    <input type="number" name="cantidad" id="payment_cantidad" class="form-control" 
                           step="0.01" min="0.01" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Descripción (opcional)</label>
                    <input type="text" name="descripcion" class="form-control" placeholder="Pago del mes...">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closePaymentModal()">Cancelar</button>
                <button type="submit" class="btn btn-success"><i class="fas fa-check"></i> Registrar Pago</button>
            </div>
        </form>
    </div>
</div>

<!-- Form para reset de contraseña -->
<form id="resetForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="reset_password">
    <input type="hidden" name="user_id" id="reset_user_id">
</form>

<script>
function openCreateModal() {
    document.getElementById('createModal').classList.add('active');
}

function closeCreateModal() {
    document.getElementById('createModal').classList.remove('active');
}

function openPaymentModal(userId, username, deuda) {
    document.getElementById('payment_user_id').value = userId;
    document.getElementById('payment_username').textContent = username;
    document.getElementById('payment_deuda').textContent = deuda.toFixed(2).replace('.', ',') + ' €';
    document.getElementById('payment_cantidad').value = deuda.toFixed(2);
    document.getElementById('paymentModal').classList.add('active');
}

function closePaymentModal() {
    document.getElementById('paymentModal').classList.remove('active');
}

function resetPassword(userId) {
    const passwordDefault = <?= json_encode($passwordDefault) ?>;
    if (confirm('¿Resetear la contraseña de este usuario a "' + passwordDefault + '"?')) {
        document.getElementById('reset_user_id').value = userId;
        document.getElementById('resetForm').submit();
    }
}

function deleteUser(userId, username) {
    if (confirm('⚠️ ¿ELIMINAR al usuario "' + username + '"?\n\n' +
                'Esta acción NO se puede revertir.\n\n' +
                'Se generará un informe PDF con todos los datos del usuario antes de eliminarlo.\n' +
                'Los movimientos históricos se mantendrán para estadísticas.')) {
        
        fetch('/export?action=delete_user', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'user_id=' + userId
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Mostrar enlace de descarga en lugar de abrir ventana emergente
                const downloadMsg = '✅ Usuario eliminado correctamente.\n\n' +
                    '📄 El informe PDF se ha guardado en:\n' + data.reportUrl + '\n\n' +
                    '¿Desea ver el informe ahora?';
                
                if (confirm(downloadMsg)) {
                    // Intentar abrir - si está bloqueado, redirigir en la misma ventana
                    const newWindow = window.open(data.reportUrl, '_blank');
                    if (!newWindow || newWindow.closed || typeof newWindow.closed === 'undefined') {
                        // Bloqueador de ventanas emergentes detectado
                        // Crear enlace temporal para descarga
                        const link = document.createElement('a');
                        link.href = data.reportUrl;
                        link.target = '_blank';
                        link.download = '';
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    }
                }
                location.reload();
            } else {
                alert('Error: ' + data.error);
            }
        })
        .catch(err => {
            alert('Error al eliminar: ' + err);
        });
    }
}
</script>

<?php include APP_PATH . '/views/partials/footer.php'; ?>
