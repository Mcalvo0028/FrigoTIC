<?php
/**
 * FrigoTIC - Configuración de Base de Datos
 * 
 * @package FrigoTIC
 * @author MJCRSoftware
 * @version 1.0.0
 */

return [
    'host' => 'localhost',
    'port' => 3307,           // Puerto diferente para no conflictuar con MySQL existente
    'database' => 'frigotic',
    'username' => 'frigotic_user',
    'password' => '',         // Configurar en database.local.php
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
];
