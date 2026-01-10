<?php
/**
 * FrigoTIC - Configuración de Base de Datos
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
    'host' => EnvHelper::get('DB_HOST', 'localhost'),
    'port' => EnvHelper::get('DB_PORT', '3306'),
    'database' => EnvHelper::get('DB_NAME', 'frigotic'),
    'username' => EnvHelper::get('DB_USER', 'root'),
    'password' => EnvHelper::get('DB_PASS', ''),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
];
