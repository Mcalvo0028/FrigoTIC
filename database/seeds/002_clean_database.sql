-- =====================================================
-- FrigoTIC - Limpieza Completa de Base de Datos
-- Versión: 1.1.0
-- Empresa: MJCRSoftware
-- =====================================================
-- ADVERTENCIA: Este script elimina TODOS los datos de la base de datos
-- La estructura de las tablas se mantiene intacta
-- Los IDs se reinician a 1
-- =====================================================

USE frigotic;

-- Desactivar verificación de claves foráneas temporalmente
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- Limpiar todas las tablas (orden no importa con FK desactivadas)
-- =====================================================
TRUNCATE TABLE sesiones;
TRUNCATE TABLE movimientos;
TRUNCATE TABLE facturas;
TRUNCATE TABLE plantillas_correo;
TRUNCATE TABLE configuracion;
TRUNCATE TABLE productos;
TRUNCATE TABLE usuarios;

-- Reactivar verificación de claves foráneas
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- Verificación: Mostrar que las tablas están vacías
-- =====================================================
SELECT 'usuarios' AS tabla, COUNT(*) AS registros FROM usuarios
UNION ALL
SELECT 'productos', COUNT(*) FROM productos
UNION ALL
SELECT 'movimientos', COUNT(*) FROM movimientos
UNION ALL
SELECT 'facturas', COUNT(*) FROM facturas
UNION ALL
SELECT 'configuracion', COUNT(*) FROM configuracion
UNION ALL
SELECT 'plantillas_correo', COUNT(*) FROM plantillas_correo
UNION ALL
SELECT 'sesiones', COUNT(*) FROM sesiones;

-- =====================================================
-- Mensaje de confirmación
-- =====================================================
SELECT '✅ Base de datos limpiada correctamente. Todos los IDs reiniciados a 1.' AS mensaje;
