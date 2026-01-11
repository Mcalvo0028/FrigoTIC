<?php
/**
 * FrigoTIC - Controlador de Descargas Seguras
 * 
 * Este archivo sirve archivos sensibles (facturas, reports) 
 * verificando la sesión del usuario antes de permitir la descarga.
 * 
 * Uso:
 *   /download.php?type=factura&file=factura_xxx.pdf
 *   /download.php?type=report&file=usuario_eliminado_xxx.html
 * 
 * @package FrigoTIC
 * @author MJCRSoftware
 * @version 1.1.0
 */

// Iniciar sesión
session_start();

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die('No autorizado. Debe iniciar sesión.');
}

// Obtener parámetros
$type = $_GET['type'] ?? '';
$file = $_GET['file'] ?? '';

// Validar parámetros
if (empty($type) || empty($file)) {
    http_response_code(400);
    die('Parámetros inválidos.');
}

// Sanitizar nombre de archivo (prevenir directory traversal)
$file = basename($file);

// Definir rutas según tipo
$storagePath = dirname(__DIR__) . '/storage/';
$allowedTypes = [
    'factura' => [
        'path' => $storagePath . 'facturas/',
        'mime' => 'application/pdf',
        'roles' => ['admin'] // Solo admin puede ver facturas
    ],
    'report' => [
        'path' => $storagePath . 'reports/',
        'mime' => 'text/html',
        'roles' => ['admin'] // Solo admin puede ver reports
    ]
];

// Verificar tipo válido
if (!isset($allowedTypes[$type])) {
    http_response_code(400);
    die('Tipo de archivo no válido.');
}

$config = $allowedTypes[$type];

// Verificar permisos de rol
$userRole = $_SESSION['user_role'] ?? 'user';
if (!in_array($userRole, $config['roles'])) {
    http_response_code(403);
    die('No tiene permisos para acceder a este archivo.');
}

// Construir ruta completa
$filePath = $config['path'] . $file;

// Verificar que el archivo existe
if (!file_exists($filePath)) {
    http_response_code(404);
    die('Archivo no encontrado.');
}

// Verificar que está dentro del directorio permitido (seguridad extra)
$realPath = realpath($filePath);
$realStoragePath = realpath($config['path']);

if ($realPath === false || strpos($realPath, $realStoragePath) !== 0) {
    http_response_code(403);
    die('Acceso denegado.');
}

// Servir el archivo
$fileSize = filesize($filePath);
$fileName = $file;

// Headers para descarga
header('Content-Type: ' . $config['mime']);
header('Content-Length: ' . $fileSize);
header('Content-Disposition: inline; filename="' . $fileName . '"');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

// Limpiar buffer y enviar archivo
ob_clean();
flush();
readfile($filePath);
exit;
