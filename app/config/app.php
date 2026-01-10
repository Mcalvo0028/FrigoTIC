<?php
/**
 * FrigoTIC - Configuración de la Aplicación
 * 
 * @package FrigoTIC
 * @author MJCRSoftware
 * @version 1.0.0
 */

// Leer versión del archivo
$versionFile = dirname(__DIR__, 2) . '/version_info.txt';
$version = file_exists($versionFile) ? trim(file_get_contents($versionFile)) : '1.0.0';

return [
    // Información de la aplicación
    'name' => 'FrigoTIC',
    'version' => $version,
    'company' => 'MJCRSoftware',
    'email' => 'frigotic@gmail.com',
    
    // URLs
    'base_url' => 'http://localhost/frigotic',
    
    // Rutas del sistema
    'paths' => [
        'root' => dirname(__DIR__, 2),
        'app' => dirname(__DIR__),
        'public' => dirname(__DIR__, 2) . '/public',
        'uploads' => dirname(__DIR__, 2) . '/public/uploads',
        'views' => dirname(__DIR__) . '/views',
    ],
    
    // Configuración de sesión
    'session' => [
        'name' => 'FRIGOTIC_SESSION',
        'lifetime' => 3600 * 8,  // 8 horas
        'path' => '/',
        'secure' => false,       // Cambiar a true en producción con HTTPS
        'httponly' => true,
    ],
    
    // Configuración de paginación
    'pagination' => [
        'default' => 10,
        'options' => [5, 10, 25, 50, 100],
    ],
    
    // Configuración de uploads
    'uploads' => [
        'max_image_size' => 2 * 1024 * 1024,    // 2MB
        'max_pdf_size' => 10 * 1024 * 1024,     // 10MB
        'allowed_images' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        'allowed_docs' => ['pdf'],
    ],
    
    // Contraseña por defecto para reseteo
    'default_password' => 'Cambiar123',
    
    // Configuración de debug (desactivar en producción)
    'debug' => true,
];
