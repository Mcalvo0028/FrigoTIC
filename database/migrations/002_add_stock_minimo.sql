-- =====================================================
-- FrigoTIC - Migración: Añadir campo stock_minimo a productos
-- Versión: 1.1.0
-- Empresa: MJCRSoftware
-- =====================================================

USE frigotic;

-- Añadir campo stock_minimo a la tabla productos
ALTER TABLE productos 
ADD COLUMN stock_minimo INT NOT NULL DEFAULT 5 AFTER stock;

-- Comentario descriptivo
ALTER TABLE productos 
MODIFY COLUMN stock_minimo INT NOT NULL DEFAULT 5 
COMMENT 'Umbral de stock bajo configurable por producto';
