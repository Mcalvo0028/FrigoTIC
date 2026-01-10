<?php
/**
 * FrigoTIC - Configuración SMTP
 * 
 * @package FrigoTIC
 * @author MJCRSoftware
 * @version 1.0.0
 */

return [
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'encryption' => 'tls',    // tls o ssl
    'username' => 'frigotic@gmail.com',
    'password' => '',         // Configurar App Password de Google aquí
    'from' => [
        'email' => 'frigotic@gmail.com',
        'name' => 'FrigoTIC'
    ],
    'reply_to' => [
        'email' => 'frigotic@gmail.com',
        'name' => 'FrigoTIC'
    ],
    // Habilitar/deshabilitar envío de correos
    'enabled' => false,       // Cambiar a true cuando esté configurado
    
    // Modo debug (muestra errores detallados)
    'debug' => false,
];
