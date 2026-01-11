-- FrigoTIC - Migración: Campo activo ya no se usa en usuarios
-- Fecha: 2026-01-11
-- Descripción: El campo activo ya no se utiliza. Los usuarios o están activos o se eliminan.
-- NOTA: No eliminamos el campo por compatibilidad, solo se documenta que no se usa.

-- Si se desea eliminar en el futuro:
-- ALTER TABLE usuarios DROP COLUMN activo;
