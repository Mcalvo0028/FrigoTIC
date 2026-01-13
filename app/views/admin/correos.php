<?php
/**
 * FrigoTIC - Gestión de Correos (Admin)
 * Versión mejorada con CRUD de plantillas y envío con variables dinámicas
 */

// Asegurar encoding UTF-8
header('Content-Type: text/html; charset=UTF-8');
mb_internal_encoding('UTF-8');

$pageTitle = 'Gestión de Correos';

require_once APP_PATH . '/models/Database.php';
require_once APP_PATH . '/models/Usuario.php';

use App\Models\Database;
use App\Models\Usuario;

$db = Database::getInstance();
$usuarioModel = new Usuario();

// Procesar acciones
$message = null;
$messageType = 'info';
$editandoPlantilla = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create_template':
            $nombre = trim($_POST['nombre'] ?? '');
            $tipo = trim($_POST['tipo'] ?? '');
            $asunto = trim($_POST['asunto'] ?? '');
            $cuerpo = trim($_POST['cuerpo'] ?? '');
            $variables = trim($_POST['variables_disponibles'] ?? '{{nombre}}, {{email}}');
            
            if (empty($nombre) || empty($tipo) || empty($asunto) || empty($cuerpo)) {
                $message = 'Todos los campos son obligatorios';
                $messageType = 'danger';
            } else {
                // Verificar que el tipo no exista
                $existe = $db->fetch("SELECT id FROM plantillas_correo WHERE tipo = ?", [$tipo]);
                if ($existe) {
                    $message = 'Ya existe una plantilla con ese identificador';
                    $messageType = 'danger';
                } else {
                    $db->insert('plantillas_correo', [
                        'tipo' => $tipo,
                        'nombre' => $nombre,
                        'asunto' => $asunto,
                        'cuerpo' => $cuerpo,
                        'variables_disponibles' => $variables
                    ]);
                    $message = 'Plantilla creada correctamente';
                    $messageType = 'success';
                }
            }
            break;
            
        case 'update_template':
            $id = (int)($_POST['id'] ?? 0);
            $nombre = trim($_POST['nombre'] ?? '');
            $asunto = trim($_POST['asunto'] ?? '');
            $cuerpo = trim($_POST['cuerpo'] ?? '');
            $variables = trim($_POST['variables_disponibles'] ?? '');
            
            if ($id > 0 && !empty($nombre) && !empty($asunto) && !empty($cuerpo)) {
                $db->update('plantillas_correo', [
                    'nombre' => $nombre,
                    'asunto' => $asunto,
                    'cuerpo' => $cuerpo,
                    'variables_disponibles' => $variables
                ], 'id = ?', [$id]);
                $message = 'Plantilla actualizada correctamente';
                $messageType = 'success';
            } else {
                $message = 'Datos incompletos';
                $messageType = 'danger';
            }
            break;
            
        case 'delete_template':
            $id = (int)($_POST['id'] ?? 0);
            if ($id > 0) {
                $db->delete('plantillas_correo', 'id = ?', [$id]);
                $message = 'Plantilla eliminada correctamente';
                $messageType = 'success';
            }
            break;
            
        case 'send_email':
            $destinatarios = $_POST['destinatarios'] ?? [];
            $asunto = trim($_POST['asunto'] ?? '');
            $mensaje = trim($_POST['mensaje'] ?? '');
            
            // Asegurar encoding UTF-8 correcto
            if (!mb_check_encoding($asunto, 'UTF-8')) {
                $asunto = mb_convert_encoding($asunto, 'UTF-8', 'ISO-8859-1');
            }
            if (!mb_check_encoding($mensaje, 'UTF-8')) {
                $mensaje = mb_convert_encoding($mensaje, 'UTF-8', 'ISO-8859-1');
            }
            
            if (empty($destinatarios) || empty($asunto) || empty($mensaje)) {
                $message = 'Selecciona al menos un destinatario y completa asunto y mensaje';
                $messageType = 'danger';
            } else {
                require_once APP_PATH . '/helpers/EnvHelper.php';
                require_once APP_PATH . '/helpers/EmailHelper.php';
                
                $enviados = 0;
                $errores = 0;
                
                foreach ($destinatarios as $userId) {
                    $usuario = $usuarioModel->getById($userId);
                    if ($usuario && !empty($usuario['email'])) {
                        // Reemplazar variables dinámicas para cada usuario
                        $asuntoPersonalizado = reemplazarVariables($asunto, $usuario);
                        $mensajePersonalizado = reemplazarVariables($mensaje, $usuario);
                        
                        $emailHelper = new \App\Helpers\EmailHelper();
                        if ($emailHelper->send($usuario['email'], $asuntoPersonalizado, $mensajePersonalizado)) {
                            $enviados++;
                        } else {
                            $errores++;
                            error_log('Error enviando correo a ' . $usuario['email'] . ': ' . $emailHelper->getLastError());
                        }
                    }
                }
                
                if ($enviados > 0) {
                    $message = "Correo enviado correctamente a {$enviados} usuario(s)";
                    $messageType = 'success';
                    if ($errores > 0) {
                        $message .= ". Hubo {$errores} error(es).";
                    }
                } else {
                    $message = 'No se pudo enviar ningún correo. Verifica la configuración SMTP.';
                    $messageType = 'danger';
                }
            }
            break;
            
        case 'test_smtp':
            $testEmail = $_POST['test_email'] ?? '';
            if (filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
                require_once APP_PATH . '/helpers/EnvHelper.php';
                require_once APP_PATH . '/helpers/EmailHelper.php';
                $emailHelper = new \App\Helpers\EmailHelper();
                
                if ($emailHelper->send($testEmail, 'Test FrigoTIC - Configuración SMTP', 
                    '<h1>¡Funciona!</h1><p>La configuración SMTP de FrigoTIC está correcta.</p><p>Fecha: ' . date('d/m/Y H:i:s') . '</p>')) {
                    $message = "Correo de prueba enviado correctamente a {$testEmail}";
                    $messageType = 'success';
                } else {
                    $error = $emailHelper->getLastError();
                    $message = 'Error al enviar el correo: ' . htmlspecialchars($error);
                    $messageType = 'danger';
                }
            } else {
                $message = 'Email de prueba no válido';
                $messageType = 'danger';
            }
            break;
    }
}

// Si estamos editando una plantilla
if (isset($_GET['editar']) && is_numeric($_GET['editar'])) {
    $editandoPlantilla = $db->fetch("SELECT * FROM plantillas_correo WHERE id = ?", [(int)$_GET['editar']]);
}

/**
 * Función para reemplazar variables en el mensaje
 * Incluye datos de consumo del usuario
 */
function reemplazarVariables($texto, $usuario) {
    // Obtener datos de consumo del usuario
    require_once APP_PATH . '/models/Movimiento.php';
    $movimientoModel = new \App\Models\Movimiento();
    $resumen = $movimientoModel->getResumenUsuario($usuario['id']);
    
    // Obtener productos consumidos este mes
    $productosConsumidos = '';
    $movimientosMes = $movimientoModel->getMovimientosMesActual($usuario['id']);
    $productosLista = [];
    foreach ($movimientosMes as $mov) {
        if ($mov['tipo'] === 'consumo' && !empty($mov['producto_nombre'])) {
            $key = $mov['producto_nombre'];
            if (!isset($productosLista[$key])) {
                $productosLista[$key] = 0;
            }
            $productosLista[$key] += $mov['cantidad'];
        }
    }
    foreach ($productosLista as $producto => $cantidad) {
        $productosConsumidos .= "- {$producto}: {$cantidad} unidad(es)\n";
    }
    if (empty($productosConsumidos)) {
        $productosConsumidos = 'Sin consumos este mes';
    }
    
    $variables = [
        '{{nombre}}' => $usuario['nombre_completo'] ?? $usuario['nombre_usuario'],
        '{{usuario}}' => $usuario['nombre_usuario'],
        '{{email}}' => $usuario['email'],
        '{{telefono}}' => $usuario['telefono'] ?? 'No especificado',
        '{{fecha}}' => date('d/m/Y'),
        '{{hora}}' => date('H:i'),
        '{{fecha_hora}}' => date('d/m/Y H:i'),
        '{{deuda}}' => number_format($resumen['deuda'] ?? 0, 2, ',', '.') . ' €',
        '{{total_consumos}}' => number_format($resumen['total_consumos'] ?? 0, 2, ',', '.') . ' €',
        '{{total_pagos}}' => number_format($resumen['total_pagos'] ?? 0, 2, ',', '.') . ' €',
        '{{cantidad}}' => ($resumen['cantidad_productos'] ?? 0) . ' productos',
        '{{num_consumos}}' => $resumen['num_consumos'] ?? 0,
        '{{productos_consumidos}}' => nl2br(trim($productosConsumidos)),
        '{{mes_actual}}' => getNombreMesActual(),
        '{{fecha_desde}}' => date('01/m/Y'),
        '{{fecha_hasta}}' => date('d/m/Y'),
    ];
    
    return str_replace(array_keys($variables), array_values($variables), $texto);
}

/**
 * Obtener nombre del mes actual en español
 */
function getNombreMesActual() {
    $meses = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
    ];
    return $meses[(int)date('n')] . ' ' . date('Y');
}

// Obtener todas las plantillas
$plantillas = $db->fetchAll("SELECT * FROM plantillas_correo ORDER BY nombre");

// Obtener usuarios activos (excluyendo admin)
$usuarios = $usuarioModel->getAll(['activo' => 1])['data'];
$usuariosNoAdmin = array_filter($usuarios, fn($u) => $u['rol'] !== 'admin');

include APP_PATH . '/views/partials/header.php';
include APP_PATH . '/views/partials/admin-tabs.php';
?>

<style>
.plantilla-card {
    background: var(--color-white);
    border: 1px solid var(--color-gray-200);
    border-radius: var(--border-radius-lg);
    padding: 1rem;
    margin-bottom: 0.75rem;
    transition: all 0.2s;
}
.plantilla-card:hover {
    border-color: var(--color-primary);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.plantilla-card h4 {
    margin: 0 0 0.25rem 0;
    color: var(--color-gray-800);
}
.plantilla-card .tipo {
    font-size: 0.75rem;
    color: var(--color-gray-500);
    background: var(--color-gray-100);
    padding: 0.125rem 0.5rem;
    border-radius: 4px;
}
.plantilla-card .asunto {
    font-size: 0.875rem;
    color: var(--color-gray-600);
    margin-top: 0.5rem;
}
.plantilla-btn-group {
    display: flex;
    gap: 0.5rem;
    margin-top: 0.75rem;
}
.template-selector {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 1rem;
    padding: 1rem;
    background: var(--color-gray-50);
    border-radius: var(--border-radius);
}
.template-selector .btn {
    flex: 1;
    min-width: 120px;
}
.email-preview {
    background: #fff;
    border: 1px solid var(--color-gray-300);
    border-radius: var(--border-radius);
    padding: 1rem;
    min-height: 150px;
    max-height: 300px;
    overflow-y: auto;
}
.variables-help {
    background: var(--color-info-light, #e0f2fe);
    border: 1px solid var(--color-info, #0ea5e9);
    border-radius: var(--border-radius);
    padding: 0.75rem;
    margin-bottom: 1rem;
}
.variables-help code {
    background: rgba(0,0,0,0.1);
    padding: 0.125rem 0.375rem;
    border-radius: 3px;
    margin: 0.125rem;
    display: inline-block;
    cursor: pointer;
}
.variables-help code:hover {
    background: var(--color-primary);
    color: white;
}
.user-list {
    max-height: 250px;
    overflow-y: auto;
    border: 1px solid var(--color-gray-300);
    border-radius: var(--border-radius);
    padding: 0.5rem;
}
.user-item {
    display: flex;
    align-items: center;
    padding: 0.375rem 0.5rem;
    border-radius: var(--border-radius-sm);
    cursor: pointer;
}
.user-item:hover {
    background: var(--color-gray-100);
}
.user-item input {
    margin-right: 0.5rem;
}
.modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    justify-content: center;
    align-items: center;
}
.modal-overlay.active {
    display: flex;
}
.modal-content {
    background: white;
    border-radius: var(--border-radius-lg);
    width: 90%;
    max-width: 700px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
}
.modal-header {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid var(--color-gray-200);
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.modal-body {
    padding: 1.5rem;
}
.modal-footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid var(--color-gray-200);
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
}
</style>

<?php if ($message): ?>
    <div class="alert alert-<?= $messageType ?>">
        <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-circle' ?> alert-icon"></i>
        <div class="alert-content"><?= htmlspecialchars($message) ?></div>
    </div>
<?php endif; ?>

<div class="d-flex justify-between align-center mb-4 flex-wrap gap-3">
    <h1><i class="fas fa-envelope tab-icon-emails"></i> Gestión de Correos</h1>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
    <!-- Columna izquierda -->
    <div>
        <!-- Plantillas de Correo -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-between align-center">
                <h2 class="card-title"><i class="fas fa-file-alt"></i> Plantillas de Correo</h2>
                <button class="btn btn-primary btn-sm" onclick="abrirModalNuevaPlantilla()">
                    <i class="fas fa-plus"></i> Nueva Plantilla
                </button>
            </div>
            <div class="card-body">
                <?php if (empty($plantillas)): ?>
                    <p class="text-muted text-center py-4">
                        <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                        No hay plantillas creadas. Crea tu primera plantilla.
                    </p>
                <?php else: ?>
                    <?php foreach ($plantillas as $plantilla): ?>
                        <div class="plantilla-card">
                            <div class="d-flex justify-between align-center">
                                <h4><?= htmlspecialchars($plantilla['nombre']) ?></h4>
                                <span class="tipo"><?= htmlspecialchars($plantilla['tipo']) ?></span>
                            </div>
                            <div class="asunto">
                                <i class="fas fa-envelope-open-text"></i> 
                                <?= htmlspecialchars($plantilla['asunto']) ?>
                            </div>
                            <div class="plantilla-btn-group">
                                <button class="btn btn-sm btn-secondary" onclick="editarPlantilla(<?= $plantilla['id'] ?>)">
                                    <i class="fas fa-edit"></i> Editar
                                </button>
                                <button class="btn btn-sm btn-info" onclick="previsualizarPlantilla(<?= $plantilla['id'] ?>)">
                                    <i class="fas fa-eye"></i> Ver
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="eliminarPlantilla(<?= $plantilla['id'] ?>, '<?= htmlspecialchars($plantilla['nombre'], ENT_QUOTES) ?>')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Test SMTP -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><i class="fas fa-cog"></i> Probar Conexión SMTP</h2>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="test_smtp">
                    <div class="form-group">
                        <label class="form-label">Email de prueba</label>
                        <input type="email" name="test_email" class="form-control" placeholder="tucorreo@ejemplo.com" required>
                        <small class="form-text">Recibirás un correo de prueba para verificar la configuración SMTP</small>
                    </div>
                    <button type="submit" class="btn btn-secondary">
                        <i class="fas fa-paper-plane"></i> Enviar Prueba
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Columna derecha: Enviar Correo -->
    <div>
        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><i class="fas fa-paper-plane"></i> Enviar Correo</h2>
            </div>
            <div class="card-body">
                <?php if (!empty($plantillas)): ?>
                    <label class="form-label">Usar plantilla:</label>
                    <div class="template-selector">
                        <?php foreach ($plantillas as $plantilla): ?>
                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                    onclick="cargarPlantilla(<?= $plantilla['id'] ?>)">
                                <i class="fas fa-file-alt"></i> <?= htmlspecialchars($plantilla['nombre']) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" id="formEnviarCorreo">
                    <input type="hidden" name="action" value="send_email">
                    
                    <div class="form-group">
                        <label class="form-label">Destinatarios *</label>
                        <div class="user-list">
                            <label class="user-item" style="background: var(--color-gray-100); font-weight: 600;">
                                <input type="checkbox" id="selectAll" onchange="toggleAllUsers(this)"> 
                                Seleccionar todos (<?= count($usuariosNoAdmin) ?>)
                            </label>
                            <hr style="margin: 0.5rem 0;">
                            <?php foreach ($usuariosNoAdmin as $u): ?>
                                <label class="user-item">
                                    <input type="checkbox" name="destinatarios[]" value="<?= $u['id'] ?>" class="user-checkbox"> 
                                    <?= htmlspecialchars($u['nombre_completo'] ?? $u['nombre_usuario']) ?>
                                    <small class="text-muted ml-1">(<?= htmlspecialchars($u['email']) ?>)</small>
                                </label>
                            <?php endforeach; ?>
                            <?php if (empty($usuariosNoAdmin)): ?>
                                <p class="text-muted text-center py-2">No hay usuarios disponibles</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="variables-help" id="variablesEnviarCorreo">
                        <strong><i class="fas fa-info-circle"></i> Variables disponibles:</strong>
                        <p class="mb-1 mt-1" style="font-size: 0.875rem;">Haz clic en una variable para insertarla donde está el cursor</p>
                        <div class="variables-group">
                            <small class="text-muted d-block mb-1"><strong>Usuario:</strong></small>
                            <code onclick="insertarVariable('{{nombre}}', 'correo')" title="Nombre completo">{{nombre}}</code>
                            <code onclick="insertarVariable('{{usuario}}', 'correo')" title="Nombre de usuario">{{usuario}}</code>
                            <code onclick="insertarVariable('{{email}}', 'correo')" title="Email">{{email}}</code>
                            <code onclick="insertarVariable('{{telefono}}', 'correo')" title="Teléfono">{{telefono}}</code>
                        </div>
                        <div class="variables-group mt-2">
                            <small class="text-muted d-block mb-1"><strong>Consumos:</strong></small>
                            <code onclick="insertarVariable('{{deuda}}', 'correo')" title="Deuda pendiente">{{deuda}}</code>
                            <code onclick="insertarVariable('{{total_consumos}}', 'correo')" title="Total consumido">{{total_consumos}}</code>
                            <code onclick="insertarVariable('{{total_pagos}}', 'correo')" title="Total pagado">{{total_pagos}}</code>
                            <code onclick="insertarVariable('{{cantidad}}', 'correo')" title="Cantidad de productos">{{cantidad}}</code>
                            <code onclick="insertarVariable('{{productos_consumidos}}', 'correo')" title="Lista de productos consumidos">{{productos_consumidos}}</code>
                        </div>
                        <div class="variables-group mt-2">
                            <small class="text-muted d-block mb-1"><strong>Fechas:</strong></small>
                            <code onclick="insertarVariable('{{fecha}}', 'correo')" title="Fecha actual">{{fecha}}</code>
                            <code onclick="insertarVariable('{{hora}}', 'correo')" title="Hora actual">{{hora}}</code>
                            <code onclick="insertarVariable('{{mes_actual}}', 'correo')" title="Mes y año actual">{{mes_actual}}</code>
                            <code onclick="insertarVariable('{{fecha_desde}}', 'correo')" title="Primer día del mes">{{fecha_desde}}</code>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Asunto *</label>
                        <input type="text" name="asunto" id="correoAsunto" class="form-control" required
                               placeholder="Asunto del correo...">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Mensaje (HTML) *</label>
                        <textarea name="mensaje" id="correoMensaje" class="form-control" rows="8" required
                                  placeholder="Escribe el contenido del correo..."
                                  oninput="actualizarVistaPrevia()"></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Vista previa:</label>
                        <div class="email-preview" id="vistaPrevia">
                            <p class="text-muted">El contenido del correo aparecerá aquí...</p>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        <i class="fas fa-paper-plane"></i> Enviar Correo
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nueva/Editar Plantilla -->
<div class="modal-overlay" id="modalPlantilla">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitulo"><i class="fas fa-file-alt"></i> Nueva Plantilla</h3>
            <button type="button" class="btn btn-sm" onclick="cerrarModal()" style="background:none;border:none;font-size:1.5rem;">
                &times;
            </button>
        </div>
        <form method="POST" id="formPlantilla">
            <input type="hidden" name="action" id="plantillaAction" value="create_template">
            <input type="hidden" name="id" id="plantillaId" value="">
            
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Nombre de la plantilla *</label>
                    <input type="text" name="nombre" id="plantillaNombre" class="form-control" required
                           placeholder="Ej: Recordatorio mensual">
                </div>
                
                <div class="form-group" id="grupoTipo">
                    <label class="form-label">Identificador único (tipo) *</label>
                    <input type="text" name="tipo" id="plantillaTipo" class="form-control" required
                           placeholder="Ej: recordatorio_mensual" pattern="[a-z0-9_]+"
                           title="Solo letras minúsculas, números y guion bajo">
                    <small class="form-text">Identificador interno. Solo letras minúsculas, números y _</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Asunto del correo *</label>
                    <input type="text" name="asunto" id="plantillaAsunto" class="form-control" required
                           placeholder="Ej: Recordatorio - {{nombre}}">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Variables disponibles (referencia)</label>
                    <input type="text" name="variables_disponibles" id="plantillaVariables" class="form-control"
                           placeholder="{{nombre}}, {{email}}, {{fecha}}"
                           value="{{nombre}}, {{usuario}}, {{email}}, {{deuda}}, {{fecha}}">
                    <small class="form-text">Lista de variables que aparecerán como referencia</small>
                </div>
                
                <div class="variables-help" id="variablesModal">
                    <strong><i class="fas fa-info-circle"></i> Insertar variable (clic para añadir al cuerpo):</strong>
                    <div class="variables-group mt-2">
                        <small class="text-muted d-block mb-1"><strong>Usuario:</strong></small>
                        <code onclick="insertarVariable('{{nombre}}', 'modal')" title="Nombre completo">{{nombre}}</code>
                        <code onclick="insertarVariable('{{usuario}}', 'modal')" title="Nombre de usuario">{{usuario}}</code>
                        <code onclick="insertarVariable('{{email}}', 'modal')" title="Email">{{email}}</code>
                        <code onclick="insertarVariable('{{telefono}}', 'modal')" title="Teléfono">{{telefono}}</code>
                    </div>
                    <div class="variables-group mt-2">
                        <small class="text-muted d-block mb-1"><strong>Consumos:</strong></small>
                        <code onclick="insertarVariable('{{deuda}}', 'modal')" title="Deuda pendiente">{{deuda}}</code>
                        <code onclick="insertarVariable('{{total_consumos}}', 'modal')" title="Total consumido">{{total_consumos}}</code>
                        <code onclick="insertarVariable('{{total_pagos}}', 'modal')" title="Total pagado">{{total_pagos}}</code>
                        <code onclick="insertarVariable('{{cantidad}}', 'modal')" title="Cantidad productos">{{cantidad}}</code>
                        <code onclick="insertarVariable('{{productos_consumidos}}', 'modal')" title="Lista productos">{{productos_consumidos}}</code>
                    </div>
                    <div class="variables-group mt-2">
                        <small class="text-muted d-block mb-1"><strong>Fechas:</strong></small>
                        <code onclick="insertarVariable('{{fecha}}', 'modal')" title="Fecha actual">{{fecha}}</code>
                        <code onclick="insertarVariable('{{hora}}', 'modal')" title="Hora actual">{{hora}}</code>
                        <code onclick="insertarVariable('{{mes_actual}}', 'modal')" title="Mes actual">{{mes_actual}}</code>
                        <code onclick="insertarVariable('{{fecha_desde}}', 'modal')" title="Primer día del mes">{{fecha_desde}}</code>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Cuerpo del correo (HTML) *</label>
                    <textarea name="cuerpo" id="plantillaCuerpo" class="form-control" rows="10" required
                              placeholder="<h2>Hola {{nombre}}</h2><p>Este es el contenido...</p>"
                              oninput="actualizarVistaPreviewModal()"></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Vista previa:</label>
                    <div class="email-preview" id="modalVistaPrevia">
                        <p class="text-muted">El contenido aparecerá aquí...</p>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="cerrarModal()">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Guardar Plantilla
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Previsualizar -->
<div class="modal-overlay" id="modalPreview">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-eye"></i> <span id="previewTitulo">Vista Previa</span></h3>
            <button type="button" class="btn btn-sm" onclick="cerrarModalPreview()" style="background:none;border:none;font-size:1.5rem;">
                &times;
            </button>
        </div>
        <div class="modal-body">
            <p><strong>Asunto:</strong> <span id="previewAsunto"></span></p>
            <hr>
            <div class="email-preview" id="previewCuerpo" style="min-height: 200px;"></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="cerrarModalPreview()">
                <i class="fas fa-times"></i> Cerrar
            </button>
        </div>
    </div>
</div>

<!-- Formulario oculto para eliminar -->
<form id="formEliminar" method="POST" style="display:none;">
    <input type="hidden" name="action" value="delete_template">
    <input type="hidden" name="id" id="eliminarId" value="">
</form>

<script>
// Datos de plantillas para JavaScript
const plantillasData = <?= json_encode($plantillas, JSON_UNESCAPED_UNICODE) ?>;

// Datos de ejemplo para vista previa (incluye todas las variables)
const ejemploUsuario = {
    '{{nombre}}': 'Juan García',
    '{{usuario}}': 'jgarcia',
    '{{email}}': 'juan@ejemplo.com',
    '{{telefono}}': '612 345 678',
    '{{fecha}}': '<?= date('d/m/Y') ?>',
    '{{hora}}': '<?= date('H:i') ?>',
    '{{fecha_hora}}': '<?= date('d/m/Y H:i') ?>',
    '{{deuda}}': '15,50 €',
    '{{total_consumos}}': '45,00 €',
    '{{total_pagos}}': '29,50 €',
    '{{cantidad}}': '12 productos',
    '{{num_consumos}}': '8',
    '{{productos_consumidos}}': '- Coca Cola: 5 unidad(es)<br>- Agua: 3 unidad(es)<br>- Café: 4 unidad(es)',
    '{{mes_actual}}': '<?= getNombreMesActual() ?>',
    '{{fecha_desde}}': '<?= date('01/m/Y') ?>',
    '{{fecha_hasta}}': '<?= date('d/m/Y') ?>'
};

// Variable para rastrear el último campo de texto activo
let ultimoCampoActivo = null;

// Escuchar focus en campos de texto para saber dónde insertar
document.addEventListener('DOMContentLoaded', function() {
    const camposTexto = ['correoAsunto', 'correoMensaje', 'plantillaAsunto', 'plantillaCuerpo'];
    camposTexto.forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('focus', function() {
                ultimoCampoActivo = this;
            });
        }
    });
});

// === FUNCIONES MODAL PLANTILLA ===
function abrirModalNuevaPlantilla() {
    document.getElementById('modalTitulo').innerHTML = '<i class="fas fa-plus"></i> Nueva Plantilla';
    document.getElementById('plantillaAction').value = 'create_template';
    document.getElementById('plantillaId').value = '';
    document.getElementById('plantillaNombre').value = '';
    document.getElementById('plantillaTipo').value = '';
    document.getElementById('plantillaAsunto').value = '';
    document.getElementById('plantillaVariables').value = '{{nombre}}, {{usuario}}, {{email}}, {{fecha}}';
    document.getElementById('plantillaCuerpo').value = '';
    document.getElementById('modalVistaPrevia').innerHTML = '<p class="text-muted">El contenido aparecerá aquí...</p>';
    document.getElementById('grupoTipo').style.display = 'block';
    document.getElementById('modalPlantilla').classList.add('active');
}

function editarPlantilla(id) {
    const plantilla = plantillasData.find(p => p.id == id);
    if (!plantilla) return;
    
    document.getElementById('modalTitulo').innerHTML = '<i class="fas fa-edit"></i> Editar Plantilla';
    document.getElementById('plantillaAction').value = 'update_template';
    document.getElementById('plantillaId').value = plantilla.id;
    document.getElementById('plantillaNombre').value = plantilla.nombre;
    document.getElementById('plantillaTipo').value = plantilla.tipo;
    document.getElementById('plantillaAsunto').value = plantilla.asunto;
    document.getElementById('plantillaVariables').value = plantilla.variables_disponibles || '';
    document.getElementById('plantillaCuerpo').value = plantilla.cuerpo;
    document.getElementById('grupoTipo').style.display = 'none'; // No se puede cambiar el tipo
    actualizarVistaPreviewModal();
    document.getElementById('modalPlantilla').classList.add('active');
}

function cerrarModal() {
    document.getElementById('modalPlantilla').classList.remove('active');
}

function actualizarVistaPreviewModal() {
    const cuerpo = document.getElementById('plantillaCuerpo').value;
    let html = cuerpo;
    for (const [variable, valor] of Object.entries(ejemploUsuario)) {
        html = html.replace(new RegExp(variable.replace(/[{}]/g, '\\$&'), 'g'), valor);
    }
    document.getElementById('modalVistaPrevia').innerHTML = html || '<p class="text-muted">El contenido aparecerá aquí...</p>';
}

// === FUNCIONES MODAL PREVIEW ===
function previsualizarPlantilla(id) {
    const plantilla = plantillasData.find(p => p.id == id);
    if (!plantilla) return;
    
    document.getElementById('previewTitulo').textContent = plantilla.nombre;
    
    let asunto = plantilla.asunto;
    let cuerpo = plantilla.cuerpo;
    
    for (const [variable, valor] of Object.entries(ejemploUsuario)) {
        asunto = asunto.replace(new RegExp(variable.replace(/[{}]/g, '\\$&'), 'g'), valor);
        cuerpo = cuerpo.replace(new RegExp(variable.replace(/[{}]/g, '\\$&'), 'g'), valor);
    }
    
    document.getElementById('previewAsunto').textContent = asunto;
    document.getElementById('previewCuerpo').innerHTML = cuerpo;
    document.getElementById('modalPreview').classList.add('active');
}

function cerrarModalPreview() {
    document.getElementById('modalPreview').classList.remove('active');
}

// === FUNCIONES ELIMINAR ===
function eliminarPlantilla(id, nombre) {
    if (confirm(`¿Estás seguro de eliminar la plantilla "${nombre}"?\n\nEsta acción no se puede deshacer.`)) {
        document.getElementById('eliminarId').value = id;
        document.getElementById('formEliminar').submit();
    }
}

// === FUNCIONES ENVIAR CORREO ===
function cargarPlantilla(id) {
    const plantilla = plantillasData.find(p => p.id == id);
    if (!plantilla) return;
    
    document.getElementById('correoAsunto').value = plantilla.asunto;
    document.getElementById('correoMensaje').value = plantilla.cuerpo;
    actualizarVistaPrevia();
    
    // Destacar brevemente los campos actualizados
    ['correoAsunto', 'correoMensaje'].forEach(id => {
        const el = document.getElementById(id);
        el.style.transition = 'background 0.3s';
        el.style.background = '#d4edda';
        setTimeout(() => el.style.background = '', 500);
    });
}

function actualizarVistaPrevia() {
    const mensaje = document.getElementById('correoMensaje').value;
    let html = mensaje;
    for (const [variable, valor] of Object.entries(ejemploUsuario)) {
        html = html.replace(new RegExp(variable.replace(/[{}]/g, '\\$&'), 'g'), valor);
    }
    document.getElementById('vistaPrevia').innerHTML = html || '<p class="text-muted">El contenido del correo aparecerá aquí...</p>';
}

function toggleAllUsers(checkbox) {
    document.querySelectorAll('.user-checkbox').forEach(cb => {
        cb.checked = checkbox.checked;
    });
}

/**
 * Insertar variable en el campo de texto activo
 * @param {string} variable - La variable a insertar (ej: {{nombre}})
 * @param {string} contexto - 'correo' para enviar correo, 'modal' para modal de plantilla
 */
function insertarVariable(variable, contexto) {
    let campo = null;
    
    // Determinar el campo correcto según el contexto
    if (contexto === 'correo') {
        // Preferir el textarea del mensaje, o usar el último campo activo
        campo = ultimoCampoActivo;
        if (!campo || (campo.id !== 'correoAsunto' && campo.id !== 'correoMensaje')) {
            campo = document.getElementById('correoMensaje');
        }
    } else if (contexto === 'modal') {
        // Preferir el textarea del cuerpo, o usar el último campo activo
        campo = ultimoCampoActivo;
        if (!campo || (campo.id !== 'plantillaAsunto' && campo.id !== 'plantillaCuerpo')) {
            campo = document.getElementById('plantillaCuerpo');
        }
    }
    
    if (!campo) {
        console.warn('No se encontró campo para insertar variable');
        return;
    }
    
    // Insertar en la posición del cursor
    const start = campo.selectionStart;
    const end = campo.selectionEnd;
    const texto = campo.value;
    
    campo.value = texto.substring(0, start) + variable + texto.substring(end);
    
    // Mover cursor después de la variable insertada
    const nuevaPosicion = start + variable.length;
    campo.setSelectionRange(nuevaPosicion, nuevaPosicion);
    campo.focus();
    
    // Actualizar vista previa
    if (contexto === 'correo') {
        actualizarVistaPrevia();
    } else if (contexto === 'modal') {
        actualizarVistaPreviewModal();
    }
    
    // Feedback visual en el botón
    const btn = event.target;
    const originalBg = btn.style.background;
    btn.style.background = 'var(--color-success)';
    btn.style.color = 'white';
    setTimeout(() => {
        btn.style.background = originalBg;
        btn.style.color = '';
    }, 300);
}

// Cerrar modales al hacer clic fuera
document.querySelectorAll('.modal-overlay').forEach(modal => {
    modal.addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.remove('active');
        }
    });
});

// Cerrar con tecla Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay').forEach(m => m.classList.remove('active'));
    }
});
</script>

<?php include APP_PATH . '/views/partials/footer.php'; ?>
