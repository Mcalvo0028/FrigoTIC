<?php
/**
 * FrigoTIC - Gestión de Correos (Admin)
 */

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_template':
            $tipo = $_POST['tipo'] ?? '';
            $asunto = trim($_POST['asunto'] ?? '');
            $cuerpo = trim($_POST['cuerpo'] ?? '');
            
            // Verificar si existe la plantilla
            $existe = $db->fetch("SELECT id FROM plantillas_correo WHERE tipo = ?", [$tipo]);
            
            if ($existe) {
                $db->update('plantillas_correo', [
                    'asunto' => $asunto,
                    'cuerpo' => $cuerpo
                ], 'tipo = ?', [$tipo]);
            } else {
                // Obtener nombre legible del tipo
                $nombres = [
                    'bienvenida' => 'Correo de Bienvenida',
                    'pago_confirmado' => 'Confirmación de Pago',
                    'recordatorio_pago' => 'Recordatorio de Pago'
                ];
                $db->insert('plantillas_correo', [
                    'tipo' => $tipo,
                    'nombre' => $nombres[$tipo] ?? ucfirst($tipo),
                    'asunto' => $asunto,
                    'cuerpo' => $cuerpo,
                    'variables_disponibles' => json_encode(getVariablesForTipo($tipo))
                ]);
            }
            
            $message = 'Plantilla actualizada correctamente';
            $messageType = 'success';
            break;
            
        case 'send_email':
            $destinatarios = $_POST['destinatarios'] ?? [];
            $asunto = trim($_POST['asunto'] ?? '');
            $mensaje = trim($_POST['mensaje'] ?? '');
            
            if (empty($destinatarios) || empty($asunto) || empty($mensaje)) {
                $message = 'Todos los campos son obligatorios';
                $messageType = 'danger';
            } else {
                $enviados = 0;
                $errores = 0;
                
                foreach ($destinatarios as $userId) {
                    $usuario = $usuarioModel->getById($userId);
                    if ($usuario && !empty($usuario['email'])) {
                        if (enviarCorreo($usuario['email'], $asunto, $mensaje)) {
                            $enviados++;
                        } else {
                            $errores++;
                        }
                    }
                }
                
                if ($enviados > 0) {
                    $message = "Correo enviado a {$enviados} usuario(s)";
                    $messageType = 'success';
                    if ($errores > 0) {
                        $message .= ". {$errores} errores.";
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
                    $message = "Correo de prueba enviado a {$testEmail}";
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

/**
 * Función para obtener variables disponibles según tipo de plantilla
 */
function getVariablesForTipo($tipo) {
    $variables = [
        'bienvenida' => ['{{nombre}}', '{{usuario}}', '{{email}}', '{{password_temporal}}'],
        'pago_confirmado' => ['{{nombre}}', '{{cantidad}}', '{{fecha}}', '{{total_consumos}}', '{{productos_consumidos}}'],
        'recordatorio_pago' => ['{{nombre}}', '{{deuda}}', '{{fecha_desde}}'],
        'general' => ['{{nombre}}', '{{email}}']
    ];
    return $variables[$tipo] ?? $variables['general'];
}

/**
 * Función para enviar correo usando SMTP
 */
function enviarCorreo($para, $asunto, $mensaje) {
    require_once APP_PATH . '/helpers/EnvHelper.php';
    require_once APP_PATH . '/helpers/EmailHelper.php';
    
    $emailHelper = new \App\Helpers\EmailHelper();
    $resultado = $emailHelper->send($para, $asunto, $mensaje);
    
    if (!$resultado) {
        error_log('Error enviando correo a ' . $para . ': ' . $emailHelper->getLastError());
    }
    
    return $resultado;
}

// Obtener plantillas existentes
$plantillas = $db->fetchAll("SELECT * FROM plantillas_correo ORDER BY tipo");
$plantillasIndexadas = [];
foreach ($plantillas as $p) {
    $plantillasIndexadas[$p['tipo']] = $p;
}

// Plantillas predeterminadas
$plantillasDefault = [
    'bienvenida' => [
        'tipo' => 'bienvenida',
        'asunto' => 'Bienvenido a FrigoTIC',
        'cuerpo' => '<h2>¡Hola {{nombre}}!</h2>
<p>Se ha creado tu cuenta en FrigoTIC.</p>
<p><strong>Usuario:</strong> {{usuario}}<br>
<strong>Contraseña temporal:</strong> {{password_temporal}}</p>
<p>Por favor, cambia tu contraseña la primera vez que accedas.</p>
<p>Saludos,<br>El equipo de FrigoTIC</p>'
    ],
    'pago_confirmado' => [
        'tipo' => 'pago_confirmado',
        'asunto' => 'Pago confirmado - FrigoTIC',
        'cuerpo' => '<h2>¡Hola {{nombre}}!</h2>
<p>Se ha registrado tu pago de <strong>{{cantidad}} €</strong>.</p>
<p><strong>Fecha:</strong> {{fecha}}</p>
<h3>Resumen del período:</h3>
<p>Total consumido: {{total_consumos}}</p>
{{productos_consumidos}}
<p>¡Gracias por usar FrigoTIC!</p>'
    ],
    'recordatorio_pago' => [
        'tipo' => 'recordatorio_pago',
        'asunto' => 'Recordatorio de pago - FrigoTIC',
        'cuerpo' => '<h2>Hola {{nombre}},</h2>
<p>Te recordamos que tienes una deuda pendiente de <strong>{{deuda}} €</strong> en FrigoTIC.</p>
<p>Tienes consumos pendientes desde <strong>{{fecha_desde}}</strong>.</p>
<p>Por favor, realiza el pago lo antes posible.</p>
<p>Saludos,<br>El administrador</p>'
    ]
];

// Combinar con las existentes en BD
foreach ($plantillasDefault as $tipo => $default) {
    if (!isset($plantillasIndexadas[$tipo])) {
        $plantillasIndexadas[$tipo] = $default;
    }
}

// Obtener usuarios activos
$usuarios = $usuarioModel->getAll(['activo' => 1])['data'];

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
    <h1><i class="fas fa-envelope tab-icon-emails"></i> Gestión de Correos</h1>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
    <!-- Columna izquierda: Plantillas -->
    <div>
        <div class="card mb-4">
            <div class="card-header">
                <h2 class="card-title"><i class="fas fa-file-alt"></i> Plantillas de Correo</h2>
            </div>
            <div class="card-body">
                <!-- Tabs de plantillas -->
                <div class="template-tabs mb-4">
                    <button class="btn btn-sm btn-primary template-tab active" onclick="showTemplate('bienvenida')">
                        <i class="fas fa-user-plus"></i> Bienvenida
                    </button>
                    <button class="btn btn-sm btn-secondary template-tab" onclick="showTemplate('pago_confirmado')">
                        <i class="fas fa-check-circle"></i> Pago Confirmado
                    </button>
                    <button class="btn btn-sm btn-secondary template-tab" onclick="showTemplate('recordatorio_pago')">
                        <i class="fas fa-bell"></i> Recordatorio
                    </button>
                </div>

                <!-- Plantilla Bienvenida -->
                <div id="template_bienvenida" class="template-panel">
                    <form method="POST">
                        <input type="hidden" name="action" value="update_template">
                        <input type="hidden" name="tipo" value="bienvenida">
                        <div class="form-group">
                            <label class="form-label">Asunto</label>
                            <input type="text" name="asunto" class="form-control" 
                                   value="<?= htmlspecialchars($plantillasIndexadas['bienvenida']['asunto'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Cuerpo (HTML)</label>
                            <textarea name="cuerpo" class="form-control template-textarea" rows="10" onkeyup="updatePreview('bienvenida')"><?= htmlspecialchars($plantillasIndexadas['bienvenida']['cuerpo'] ?? '') ?></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Variables disponibles:</label>
                            <div class="text-muted">
                                <code>{{nombre}}</code> <code>{{usuario}}</code> <code>{{email}}</code> <code>{{password_temporal}}</code>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Vista previa:</label>
                            <div class="email-preview" id="preview_bienvenida"></div>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar Plantilla</button>
                    </form>
                </div>

                <!-- Plantilla Pago Confirmado -->
                <div id="template_pago_confirmado" class="template-panel" style="display: none;">
                    <form method="POST">
                        <input type="hidden" name="action" value="update_template">
                        <input type="hidden" name="tipo" value="pago_confirmado">
                        <div class="form-group">
                            <label class="form-label">Asunto</label>
                            <input type="text" name="asunto" class="form-control" 
                                   value="<?= htmlspecialchars($plantillasIndexadas['pago_confirmado']['asunto'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Cuerpo (HTML)</label>
                            <textarea name="cuerpo" class="form-control template-textarea" rows="10" onkeyup="updatePreview('pago_confirmado')"><?= htmlspecialchars($plantillasIndexadas['pago_confirmado']['cuerpo'] ?? '') ?></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Variables disponibles:</label>
                            <div class="text-muted">
                                <code>{{nombre}}</code> <code>{{cantidad}}</code> <code>{{fecha}}</code> <code>{{total_consumos}}</code> <code>{{productos_consumidos}}</code>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Vista previa:</label>
                            <div class="email-preview" id="preview_pago_confirmado"></div>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar Plantilla</button>
                    </form>
                </div>

                <!-- Plantilla Recordatorio -->
                <div id="template_recordatorio_pago" class="template-panel" style="display: none;">
                    <form method="POST">
                        <input type="hidden" name="action" value="update_template">
                        <input type="hidden" name="tipo" value="recordatorio_pago">
                        <div class="form-group">
                            <label class="form-label">Asunto</label>
                            <input type="text" name="asunto" class="form-control" 
                                   value="<?= htmlspecialchars($plantillasIndexadas['recordatorio_pago']['asunto'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Cuerpo (HTML)</label>
                            <textarea name="cuerpo" class="form-control template-textarea" rows="10" onkeyup="updatePreview('recordatorio_pago')"><?= htmlspecialchars($plantillasIndexadas['recordatorio_pago']['cuerpo'] ?? '') ?></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Variables disponibles:</label>
                            <div class="text-muted">
                                <code>{{nombre}}</code> <code>{{deuda}}</code> <code>{{fecha_desde}}</code>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Vista previa:</label>
                            <div class="email-preview" id="preview_recordatorio_pago"></div>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar Plantilla</button>
                    </form>
                </div>
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
                        <input type="email" name="test_email" class="form-control" placeholder="tu@email.com" required>
                    </div>
                    <button type="submit" class="btn btn-secondary">
                        <i class="fas fa-paper-plane"></i> Enviar prueba
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Columna derecha: Enviar correo -->
    <div>
        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><i class="fas fa-paper-plane"></i> Enviar Correo</h2>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="send_email">
                    
                    <div class="form-group">
                        <label class="form-label">Destinatarios</label>
                        <div style="max-height: 200px; overflow-y: auto; border: 1px solid var(--color-gray-300); border-radius: var(--border-radius); padding: 0.5rem;">
                            <label style="display: block; padding: 0.25rem 0.5rem; cursor: pointer;">
                                <input type="checkbox" id="selectAll" onchange="toggleAllUsers(this)"> 
                                <strong>Seleccionar todos</strong>
                            </label>
                            <hr style="margin: 0.5rem 0;">
                            <?php foreach ($usuarios as $u): ?>
                                <?php if ($u['rol'] !== 'admin'): ?>
                                <label style="display: block; padding: 0.25rem 0.5rem; cursor: pointer;">
                                    <input type="checkbox" name="destinatarios[]" value="<?= $u['id'] ?>" class="user-checkbox"> 
                                    <?= htmlspecialchars($u['nombre_completo'] ?? $u['nombre_usuario']) ?>
                                    <small class="text-muted">(<?= htmlspecialchars($u['email']) ?>)</small>
                                </label>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Asunto *</label>
                        <input type="text" name="asunto" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Mensaje (HTML) *</label>
                        <textarea name="mensaje" class="form-control" rows="10" required></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        <i class="fas fa-paper-plane"></i> Enviar Correo
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Datos de ejemplo para la vista previa
const ejemploDatos = {
    '{{nombre}}': 'Juan García',
    '{{usuario}}': 'jgarcia',
    '{{email}}': 'juan@email.com',
    '{{password_temporal}}': 'abc123XYZ',
    '{{cantidad}}': '15,50',
    '{{fecha}}': '11/01/2026',
    '{{total_consumos}}': '25,00 €',
    '{{productos_consumidos}}': '<ul><li>Coca-Cola x3</li><li>Agua x2</li></ul>',
    '{{deuda}}': '12,50',
    '{{fecha_desde}}': '05/01/2026'
};

function updatePreview(tipo) {
    const textarea = document.querySelector('#template_' + tipo + ' .template-textarea');
    const preview = document.getElementById('preview_' + tipo);
    
    if (textarea && preview) {
        let html = textarea.value;
        // Reemplazar variables con datos de ejemplo
        for (const [variable, valor] of Object.entries(ejemploDatos)) {
            html = html.replace(new RegExp(variable.replace(/[{}]/g, '\\$&'), 'g'), valor);
        }
        preview.innerHTML = html;
    }
}

function showTemplate(tipo) {
    // Ocultar todos los paneles
    document.querySelectorAll('.template-panel').forEach(p => p.style.display = 'none');
    // Mostrar el seleccionado
    document.getElementById('template_' + tipo).style.display = 'block';
    
    // Actualizar botones
    document.querySelectorAll('.template-tab').forEach(btn => {
        btn.classList.remove('btn-primary');
        btn.classList.add('btn-secondary');
    });
    event.target.classList.remove('btn-secondary');
    event.target.classList.add('btn-primary');
    
    // Actualizar vista previa
    updatePreview(tipo);
}

function toggleAllUsers(checkbox) {
    document.querySelectorAll('.user-checkbox').forEach(cb => {
        cb.checked = checkbox.checked;
    });
}

// Inicializar vistas previas al cargar
document.addEventListener('DOMContentLoaded', function() {
    updatePreview('bienvenida');
    updatePreview('pago_confirmado');
    updatePreview('recordatorio_pago');
});
</script>

<?php include APP_PATH . '/views/partials/footer.php'; ?>
