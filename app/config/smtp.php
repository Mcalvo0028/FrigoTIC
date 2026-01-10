<?php
/**
 * FrigoTIC - Configuración SMTP
 * 
 * @package FrigoTIC
 * @author MJCRSoftware
 * @version 1.0.0
 * 
 * Los valores se cargan desde el archivo .env
 */

require_once dirname(__DIR__) . '/helpers/EnvHelper.php';
use App\Helpers\EnvHelper;

EnvHelper::load();

return [
    'host' => EnvHelper::get('SMTP_HOST', 'smtp.gmail.com'),
    'port' => EnvHelper::get('SMTP_PORT', '587'),
    'encryption' => EnvHelper::get('SMTP_ENCRYPTION', 'tls'),
    'username' => EnvHelper::get('SMTP_USER', ''),
    'password' => EnvHelper::get('SMTP_PASS', ''),
    'from' => [
        'email' => EnvHelper::get('SMTP_USER', 'frigotic@gmail.com'),
        'name' => EnvHelper::get('SMTP_FROM_NAME', 'FrigoTIC')
    ],
    'reply_to' => [
        'email' => EnvHelper::get('SMTP_USER', 'frigotic@gmail.com'),
        'name' => EnvHelper::get('SMTP_FROM_NAME', 'FrigoTIC')
    ],
    // Habilitar si hay contraseña configurada
    'enabled' => !empty(EnvHelper::get('SMTP_PASS', '')),
    
    // Modo debug
    'debug' => EnvHelper::get('APP_DEBUG', 'false') === 'true',
];
