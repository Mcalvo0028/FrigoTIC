-- =====================================================
-- FrigoTIC - Datos Iniciales (Seeds)
-- Versión: 1.1.0
-- Empresa: MJCRSoftware
-- =====================================================
-- IMPORTANTE: Ejecutar después de 000_create_database_full.sql
-- o después de aplicar todas las migraciones
-- =====================================================

SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

USE frigotic;

-- =====================================================
-- Usuario Administrador por Defecto
-- Contraseña: admin123 (hasheada con password_hash)
-- Hash: $2y$10$QbaHdVk19xDWQQ2KUI60qOsYPNzodVbXm2T72deFZm7UE9h.cWoWa
-- debe_cambiar_password: 1 (obligar cambio en primer login)
-- =====================================================
INSERT INTO usuarios (nombre_usuario, password_hash, email, telefono, nombre_completo, rol, debe_cambiar_password, activo) VALUES
('admin', '$2y$10$QbaHdVk19xDWQQ2KUI60qOsYPNzodVbXm2T72deFZm7UE9h.cWoWa', 'frigotic@gmail.com', NULL, 'Administrador', 'admin', 1, 1);

-- =====================================================
-- Configuración Inicial del Sistema
-- =====================================================
INSERT INTO configuracion (clave, valor, tipo, descripcion) VALUES
-- Configuración General
('app_nombre', 'FrigoTIC', 'string', 'Nombre de la aplicación'),
('app_version', '1.1.1', 'string', 'Versión actual de la aplicación'),
('empresa', 'MJCRSoftware', 'string', 'Nombre de la empresa'),

-- Configuración de Base de Datos (referencia, se lee del .env)
('db_host', 'localhost', 'string', 'Host de la base de datos'),
('db_port', '3306', 'string', 'Puerto de la base de datos'),
('db_name', 'frigotic', 'string', 'Nombre de la base de datos'),

-- Configuración SMTP
('smtp_host', 'smtp.gmail.com', 'string', 'Servidor SMTP'),
('smtp_port', '587', 'int', 'Puerto SMTP'),
('smtp_user', '', 'string', 'Usuario SMTP'),
('smtp_password', '', 'string', 'Contraseña SMTP (App Password)'),
('smtp_from_name', 'FrigoTIC', 'string', 'Nombre remitente'),
('smtp_encryption', 'tls', 'string', 'Tipo de encriptación (tls/ssl)'),

-- Configuración de Paginación
('items_por_pagina', '10', 'int', 'Elementos por página por defecto'),
('items_por_pagina_opciones', '[5,10,25,50,100]', 'json', 'Opciones de elementos por página'),

-- Configuración de Uploads
('max_tamano_imagen', '2097152', 'int', 'Tamaño máximo de imagen en bytes (2MB)'),
('max_tamano_pdf', '10485760', 'int', 'Tamaño máximo de PDF en bytes (10MB)'),
('extensiones_imagen', '["jpg","jpeg","png","gif","webp"]', 'json', 'Extensiones de imagen permitidas'),

-- Contraseña por defecto para reseteo
('password_default', 'Cambiar123', 'string', 'Contraseña por defecto en reseteos');

-- =====================================================
-- Plantillas de Correo Electrónico
-- =====================================================
INSERT INTO plantillas_correo (tipo, nombre, asunto, cuerpo, variables_disponibles) VALUES
(
    'bienvenida',
    'Correo de Bienvenida',
    'Bienvenido a FrigoTIC',
    '<h2>¡Bienvenido a FrigoTIC!</h2>
<p>Hola <strong>{{nombre}}</strong>,</p>
<p>Tu cuenta ha sido creada exitosamente en FrigoTIC.</p>
<p><strong>Datos de acceso:</strong></p>
<ul>
    <li>Usuario: {{usuario}}</li>
    <li>Contraseña temporal: {{password_temporal}}</li>
</ul>
<p>Por seguridad, deberás cambiar tu contraseña en el primer inicio de sesión.</p>
<p>Saludos,<br>El equipo de FrigoTIC</p>',
    '{{nombre}}, {{usuario}}, {{email}}, {{password_temporal}}'
),
(
    'reseteo_password',
    'Reseteo de Contraseña',
    'FrigoTIC - Reseteo de Contraseña',
    '<h2>Reseteo de Contraseña</h2>
<p>Hola <strong>{{nombre}}</strong>,</p>
<p>Tu contraseña ha sido reseteada por el administrador.</p>
<p><strong>Nueva contraseña temporal:</strong> {{password_temporal}}</p>
<p>Por seguridad, deberás cambiar tu contraseña en el próximo inicio de sesión.</p>
<p>Saludos,<br>El equipo de FrigoTIC</p>',
    '{{nombre}}, {{usuario}}, {{email}}, {{password_temporal}}'
),
(
    'pago_confirmado',
    'Confirmación de Pago',
    'FrigoTIC - Pago Recibido',
    '<h2>Pago Confirmado</h2>
<p>Hola <strong>{{nombre}}</strong>,</p>
<p>Hemos recibido tu pago de <strong>{{cantidad}} €</strong>.</p>
<p><strong>Fecha:</strong> {{fecha}}</p>
<h3>Resumen del período:</h3>
<p>Total consumido: {{total_consumos}}</p>
{{productos_consumidos}}
<p>¡Gracias por tu pago!</p>
<p>Saludos,<br>El equipo de FrigoTIC</p>',
    '{{nombre}}, {{cantidad}}, {{fecha}}, {{total_consumos}}, {{productos_consumidos}}'
),
(
    'recordatorio_pago',
    'Recordatorio de Pago',
    'FrigoTIC - Recordatorio de Pago Pendiente',
    '<h2>Recordatorio de Pago</h2>
<p>Hola <strong>{{nombre}}</strong>,</p>
<p>Te recordamos que tienes un saldo pendiente de <strong>{{deuda}} €</strong> en FrigoTIC.</p>
<p>Tienes consumos pendientes desde <strong>{{fecha_desde}}</strong>.</p>
<p>Por favor, realiza el pago al administrador a la mayor brevedad posible.</p>
<p>Saludos,<br>El equipo de FrigoTIC</p>',
    '{{nombre}}, {{deuda}}, {{fecha_desde}}'
);

-- =====================================================
-- Productos de Ejemplo (Opcional - descomentar si se desea)
-- =====================================================
-- INSERT INTO productos (nombre, descripcion, precio_compra, precio_venta, stock, stock_minimo, activo) VALUES
-- ('Coca-Cola', 'Refresco de cola 33cl', 0.35, 0.50, 24, 5, 1),
-- ('Coca-Cola Zero', 'Refresco de cola sin azúcar 33cl', 0.35, 0.50, 24, 5, 1),
-- ('Fanta Naranja', 'Refresco de naranja 33cl', 0.35, 0.50, 12, 5, 1),
-- ('Agua Mineral', 'Botella de agua 50cl', 0.20, 0.30, 48, 10, 1),
-- ('Nestea', 'Té frío al limón 33cl', 0.40, 0.60, 12, 5, 1),
-- ('Cerveza', 'Cerveza rubia 33cl', 0.45, 0.70, 24, 5, 1),
-- ('Zumo de Naranja', 'Zumo natural 25cl', 0.50, 0.80, 12, 5, 1),
-- ('Red Bull', 'Bebida energética 25cl', 1.00, 1.50, 6, 3, 1);

-- =====================================================
-- Mensaje de confirmación
-- =====================================================
SELECT '✅ Datos iniciales insertados correctamente (v1.1.0)' AS mensaje;
