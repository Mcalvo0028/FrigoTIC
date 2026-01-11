<?php
/**
 * FrigoTIC - Controlador de Exportación PDF
 * MJCRSoftware
 */

// La autenticación ya se verifica en index.php
// APP_PATH ya está definido

require_once APP_PATH . '/models/Database.php';
require_once APP_PATH . '/helpers/PdfHelper.php';

use App\Models\Database;
use App\Helpers\PdfHelper;

$db = Database::getInstance();
$action = $_GET['action'] ?? '';
$type = $_GET['type'] ?? '';

// Variable auxiliar para rol
$userRole = $_SESSION['user_role'] ?? '';
$userId = $_SESSION['user_id'] ?? 0;

switch ($action) {
    case 'export':
        handleExport($db, $type, $userRole, $userId);
        break;
    case 'delete_user':
        handleDeleteUser($db, $userRole);
        break;
    default:
        http_response_code(400);
        echo 'Acción no válida';
}

/**
 * Manejar exportación de datos
 */
function handleExport($db, $type, $userRole, $userId) {
    $filters = $_GET;
    unset($filters['action'], $filters['type']);
    
    switch ($type) {
        case 'movimientos':
            exportMovimientos($db, $filters, $userRole, $userId);
            break;
        case 'productos':
            exportProductos($db, $filters, $userRole);
            break;
        case 'usuarios':
            exportUsuarios($db, $filters, $userRole);
            break;
        case 'facturas':
            exportFacturas($db, $filters, $userRole);
            break;
        case 'graficos':
            exportGraficos($db, $userRole);
            break;
        default:
            http_response_code(400);
            echo 'Tipo de exportación no válido';
    }
}

/**
 * Exportar movimientos
 */
function exportMovimientos($db, $filters, $userRole, $userId) {
    $where = ['1=1'];
    $params = [];
    
    // Solo movimientos del usuario si no es admin
    if ($userRole !== 'admin') {
        $where[] = 'm.usuario_id = ?';
        $params[] = $userId;
    } elseif (!empty($filters['usuario_id'])) {
        $where[] = 'm.usuario_id = ?';
        $params[] = $filters['usuario_id'];
    }
    
    if (!empty($filters['producto_id'])) {
        $where[] = 'm.producto_id = ?';
        $params[] = $filters['producto_id'];
    }
    
    if (!empty($filters['tipo'])) {
        $where[] = 'm.tipo = ?';
        $params[] = $filters['tipo'];
    }
    
    if (!empty($filters['fecha_desde'])) {
        $where[] = 'DATE(m.fecha_hora) >= ?';
        $params[] = $filters['fecha_desde'];
    }
    
    if (!empty($filters['fecha_hasta'])) {
        $where[] = 'DATE(m.fecha_hora) <= ?';
        $params[] = $filters['fecha_hasta'];
    }
    
    $whereClause = implode(' AND ', $where);
    
    $sql = "SELECT m.*, u.nombre_usuario, p.nombre as producto_nombre 
            FROM movimientos m 
            LEFT JOIN usuarios u ON m.usuario_id = u.id 
            LEFT JOIN productos p ON m.producto_id = p.id 
            WHERE {$whereClause} 
            ORDER BY m.fecha_hora DESC";
    
    $data = $db->fetchAll($sql, $params);
    
    $columns = [
        ['field' => 'fecha_hora', 'label' => 'Fecha', 'type' => 'datetime'],
        ['field' => 'nombre_usuario', 'label' => 'Usuario'],
        ['field' => 'tipo', 'label' => 'Tipo', 'type' => 'badge'],
        ['field' => 'producto_nombre', 'label' => 'Producto'],
        ['field' => 'cantidad', 'label' => 'Cant.', 'class' => 'center'],
        ['field' => 'precio_unitario', 'label' => 'Precio', 'type' => 'money', 'class' => 'right'],
        ['field' => 'total', 'label' => 'Total', 'type' => 'money', 'class' => 'right']
    ];
    
    // Si es usuario, no mostrar columna de usuario
    if ($userRole !== 'admin') {
        array_splice($columns, 1, 1);
    }
    
    // Calcular totales
    $totalConsumos = 0;
    $totalPagos = 0;
    foreach ($data as $row) {
        if ($row['tipo'] === 'consumo') {
            $totalConsumos += abs($row['total']);
        } elseif ($row['tipo'] === 'pago') {
            $totalPagos += abs($row['total']);
        }
    }
    
    $summary = [
        'Total Consumos' => number_format($totalConsumos, 2, ',', '.') . ' €',
        'Total Pagos' => number_format($totalPagos, 2, ',', '.') . ' €',
        'Saldo' => number_format($totalConsumos - $totalPagos, 2, ',', '.') . ' €'
    ];
    
    $html = PdfHelper::generateListReport('Listado de Movimientos', $columns, $data, $filters, $summary);
    PdfHelper::outputForPrint($html);
}

/**
 * Exportar productos
 */
function exportProductos($db, $filters, $userRole) {
    // Solo admin
    if ($userRole !== 'admin') {
        http_response_code(403);
        echo 'Acceso denegado';
        exit;
    }
    
    $where = ['1=1'];
    $params = [];
    
    if (!empty($filters['buscar'])) {
        $where[] = '(nombre LIKE ? OR descripcion LIKE ?)';
        $params[] = '%' . $filters['buscar'] . '%';
        $params[] = '%' . $filters['buscar'] . '%';
    }
    
    if (isset($filters['activo']) && $filters['activo'] !== '') {
        $where[] = 'activo = ?';
        $params[] = $filters['activo'];
    }
    
    $whereClause = implode(' AND ', $where);
    
    $sql = "SELECT * FROM productos WHERE {$whereClause} ORDER BY nombre";
    $data = $db->fetchAll($sql, $params);
    
    $columns = [
        ['field' => 'imagen', 'label' => 'Imagen', 'type' => 'image', 'class' => 'center'],
        ['field' => 'nombre', 'label' => 'Producto'],
        ['field' => 'descripcion', 'label' => 'Descripción'],
        ['field' => 'precio_compra', 'label' => 'P. Compra', 'type' => 'money', 'class' => 'right'],
        ['field' => 'precio_venta', 'label' => 'P. Venta', 'type' => 'money', 'class' => 'right'],
        ['field' => 'stock', 'label' => 'Stock', 'class' => 'center'],
        ['field' => 'stock_minimo', 'label' => 'Stock Mín.', 'class' => 'center']
    ];
    
    // Resumen
    $totalStock = array_sum(array_column($data, 'stock'));
    $summary = ['Total productos' => count($data), 'Stock total' => $totalStock . ' uds.'];
    
    $html = PdfHelper::generateListReport('Listado de Productos', $columns, $data, $filters, $summary);
    PdfHelper::outputForPrint($html);
}

/**
 * Exportar usuarios
 */
function exportUsuarios($db, $filters, $userRole) {
    // Solo admin
    if ($userRole !== 'admin') {
        http_response_code(403);
        echo 'Acceso denegado';
        exit;
    }
    
    $where = ["rol = 'user'"];
    $params = [];
    
    if (!empty($filters['buscar'])) {
        $where[] = '(nombre_usuario LIKE ? OR email LIKE ? OR nombre_completo LIKE ?)';
        $params[] = '%' . $filters['buscar'] . '%';
        $params[] = '%' . $filters['buscar'] . '%';
        $params[] = '%' . $filters['buscar'] . '%';
    }
    
    $whereClause = implode(' AND ', $where);
    
    // Obtener usuarios con deudas
    $sql = "SELECT u.*, 
            COALESCE((SELECT SUM(CASE WHEN tipo = 'consumo' THEN total ELSE -total END) 
                      FROM movimientos WHERE usuario_id = u.id), 0) as deuda
            FROM usuarios u 
            WHERE {$whereClause} 
            ORDER BY nombre_usuario";
    $data = $db->fetchAll($sql, $params);
    
    $columns = [
        ['field' => 'nombre_usuario', 'label' => 'Usuario'],
        ['field' => 'email', 'label' => 'Email'],
        ['field' => 'telefono', 'label' => 'Teléfono'],
        ['field' => 'nombre_completo', 'label' => 'Nombre completo'],
        ['field' => 'fecha_registro', 'label' => 'Registro', 'type' => 'date'],
        ['field' => 'deuda', 'label' => 'Deuda', 'type' => 'money', 'class' => 'right']
    ];
    
    // Resumen
    $totalDeuda = array_sum(array_column($data, 'deuda'));
    $summary = ['Total usuarios' => count($data), 'Deuda total' => number_format($totalDeuda, 2, ',', '.') . ' €'];
    
    $html = PdfHelper::generateListReport('Listado de Usuarios', $columns, $data, $filters, $summary);
    PdfHelper::outputForPrint($html);
}

/**
 * Exportar facturas
 */
function exportFacturas($db, $filters, $userRole) {
    // Solo admin
    if ($userRole !== 'admin') {
        http_response_code(403);
        echo 'Acceso denegado';
        exit;
    }
    
    $where = ['1=1'];
    $params = [];
    
    if (!empty($filters['buscar'])) {
        $where[] = '(nombre_archivo LIKE ? OR descripcion LIKE ?)';
        $params[] = '%' . $filters['buscar'] . '%';
        $params[] = '%' . $filters['buscar'] . '%';
    }
    
    if (!empty($filters['fecha_desde'])) {
        $where[] = 'DATE(fecha_subida) >= ?';
        $params[] = $filters['fecha_desde'];
    }
    
    if (!empty($filters['fecha_hasta'])) {
        $where[] = 'DATE(fecha_subida) <= ?';
        $params[] = $filters['fecha_hasta'];
    }
    
    $whereClause = implode(' AND ', $where);
    
    $sql = "SELECT * FROM facturas WHERE {$whereClause} ORDER BY fecha_subida DESC";
    $data = $db->fetchAll($sql, $params);
    
    $columns = [
        ['field' => 'nombre_archivo', 'label' => 'Archivo'],
        ['field' => 'descripcion', 'label' => 'Descripción'],
        ['field' => 'fecha_subida', 'label' => 'Fecha Subida', 'type' => 'datetime'],
        ['field' => 'fecha_factura', 'label' => 'Fecha Factura', 'type' => 'date']
    ];
    
    $summary = ['Total facturas' => count($data)];
    
    $html = PdfHelper::generateListReport('Listado de Facturas', $columns, $data, $filters, $summary);
    PdfHelper::outputForPrint($html);
}

/**
 * Exportar gráficos (resumen estadístico)
 */
function exportGraficos($db, $userRole) {
    // Solo admin
    if ($userRole !== 'admin') {
        http_response_code(403);
        echo 'Acceso denegado';
        exit;
    }
    
    // Obtener datos del último mes (30 días)
    $consumosPorDia = $db->fetchAll(
        "SELECT DATE(fecha_hora) as dia, SUM(total) as total_dinero, SUM(cantidad) as total_cantidad
         FROM movimientos 
         WHERE tipo = 'consumo' AND fecha_hora >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)
         GROUP BY DATE(fecha_hora) ORDER BY dia ASC"
    );
    
    $consumosPorProducto = $db->fetchAll(
        "SELECT p.nombre as producto, SUM(m.cantidad) as total_cantidad, SUM(m.total) as total_dinero
         FROM movimientos m JOIN productos p ON m.producto_id = p.id
         WHERE m.tipo = 'consumo' AND m.fecha_hora >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)
         GROUP BY m.producto_id ORDER BY total_dinero DESC LIMIT 10"
    );
    
    $consumosPorUsuario = $db->fetchAll(
        "SELECT u.nombre_usuario as usuario, SUM(m.cantidad) as total_cantidad, SUM(m.total) as total_dinero
         FROM movimientos m JOIN usuarios u ON m.usuario_id = u.id
         WHERE m.tipo = 'consumo' AND m.fecha_hora >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)
         GROUP BY m.usuario_id ORDER BY total_dinero DESC LIMIT 10"
    );
    
    $pagosVsConsumos = $db->fetchAll(
        "SELECT DATE(fecha_hora) as dia,
                SUM(CASE WHEN tipo = 'consumo' THEN total ELSE 0 END) as consumos,
                SUM(CASE WHEN tipo = 'pago' THEN total ELSE 0 END) as pagos
         FROM movimientos 
         WHERE fecha_hora >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)
         GROUP BY DATE(fecha_hora) ORDER BY dia ASC"
    );
    
    $html = PdfHelper::generateGraficosReportWithCharts(
        $consumosPorDia, 
        $consumosPorProducto, 
        $consumosPorUsuario, 
        $pagosVsConsumos
    );
    PdfHelper::outputForPrint($html);
}

/**
 * Manejar eliminación de usuario
 */
function handleDeleteUser($db, $userRole) {
    // Solo admin y POST
    if ($userRole !== 'admin' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Acceso denegado']);
        exit;
    }
    
    $userId = (int)($_POST['user_id'] ?? 0);
    
    if (!$userId) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'ID de usuario no válido']);
        exit;
    }
    
    // Obtener datos del usuario
    $usuario = $db->fetch("SELECT * FROM usuarios WHERE id = ? AND rol = 'user'", [$userId]);
    
    if (!$usuario) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Usuario no encontrado']);
        exit;
    }
    
    // Obtener todos los movimientos del usuario
    $movimientos = $db->fetchAll(
        "SELECT m.*, p.nombre as producto_nombre 
         FROM movimientos m 
         LEFT JOIN productos p ON m.producto_id = p.id 
         WHERE m.usuario_id = ? 
         ORDER BY m.fecha_hora DESC",
        [$userId]
    );
    
    // Generar HTML del informe
    $html = PdfHelper::generateUserDeletionReport($usuario, $movimientos);
    
    // Guardar archivo
    $filename = 'usuario_eliminado_' . preg_replace('/[^a-zA-Z0-9]/', '_', $usuario['nombre_usuario']) . '_' . date('Y-m-d_His');
    $reportUrl = PdfHelper::saveHtmlFile($html, $filename);
    
    // Eliminar usuario de la BD (los movimientos se mantienen)
    $db->delete('usuarios', 'id = ?', [$userId]);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Usuario eliminado correctamente',
        'reportUrl' => $reportUrl
    ]);
}
