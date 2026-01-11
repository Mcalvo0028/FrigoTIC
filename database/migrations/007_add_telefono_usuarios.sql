-- FrigoTIC - Migración: Añadir campo teléfono a usuarios
-- Fecha: 2026-01-11
-- Descripción: Añade campo telefono opcional a la tabla usuarios

ALTER TABLE usuarios ADD COLUMN telefono VARCHAR(20) NULL AFTER email;
