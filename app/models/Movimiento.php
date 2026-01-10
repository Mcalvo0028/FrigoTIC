<?php
/**
 * FrigoTIC - Modelo de Movimiento
 * 
 * @package FrigoTIC
 * @author MJCRSoftware
 * @version 1.0.0
 */

namespace App\Models;

class Movimiento
{
    private Database $db;
    private string $table = 'movimientos';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Obtener todos los movimientos con filtros
     */
    public function getAll(array $filters = [], int $page = 1, int $perPage = 10): array
    {
        $where = ['1 = 1'];
        $params = [];

        if (!empty($filters['usuario_id'])) {
            $where[] = 'm.usuario_id = ?';
            $params[] = $filters['usuario_id'];
        }

        if (!empty($filters['producto_id'])) {
            $where[] = 'm.producto_id = ?';
            $params[] = $filters['producto_id'];
        }

        if (!empty($filters['tipo'])) {
            $where[] = 'm.tipo = ?';
            $params[] = $filters['tipo'];
        }

        if (!empty($filters['fecha_desde'])) {
            $where[] = 'DATE(m.fecha_hora) >= ?';
            $params[] = $filters['fecha_desde'];
        }

        if (!empty($filters['fecha_hasta'])) {
            $where[] = 'DATE(m.fecha_hora) <= ?';
            $params[] = $filters['fecha_hasta'];
        }

        $whereClause = implode(' AND ', $where);
        
        // Contar total
        $countSql = "SELECT COUNT(*) as total FROM {$this->table} m WHERE {$whereClause}";
        $countResult = $this->db->fetch($countSql, $params);
        $total = (int) ($countResult['total'] ?? 0);
        
        // Calcular offset
        $offset = ($page - 1) * $perPage;
        
        // Obtener registros con joins
        $sql = "SELECT m.*, 
                       u.nombre_usuario, u.nombre_completo,
                       p.nombre as producto_nombre, p.imagen as producto_imagen
                FROM {$this->table} m
                LEFT JOIN usuarios u ON m.usuario_id = u.id
                LEFT JOIN productos p ON m.producto_id = p.id
                WHERE {$whereClause} 
                ORDER BY m.fecha_hora DESC 
                LIMIT {$perPage} OFFSET {$offset}";
        
        $movimientos = $this->db->fetchAll($sql, $params);

        return [
            'data' => $movimientos,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($total / $perPage)
        ];
    }

    /**
     * Obtener movimiento por ID
     */
    public function getById(int $id): ?array
    {
        $sql = "SELECT m.*, 
                       u.nombre_usuario, u.nombre_completo,
                       p.nombre as producto_nombre
                FROM {$this->table} m
                LEFT JOIN usuarios u ON m.usuario_id = u.id
                LEFT JOIN productos p ON m.producto_id = p.id
                WHERE m.id = ?";
        
        return $this->db->fetch($sql, [$id]);
    }

    /**
     * Registrar consumo de producto
     */
    public function registrarConsumo(int $usuarioId, int $productoId, int $cantidad = 1): int
    {
        $producto = (new Producto())->getById($productoId);
        if (!$producto || $producto['stock'] < $cantidad) {
            throw new \Exception('Stock insuficiente');
        }

        $total = $producto['precio_venta'] * $cantidad;

        // Registrar movimiento
        $id = $this->db->insert($this->table, [
            'usuario_id' => $usuarioId,
            'producto_id' => $productoId,
            'tipo' => 'consumo',
            'cantidad' => $cantidad,
            'precio_unitario' => $producto['precio_venta'],
            'total' => $total,
            'descripcion' => "Consumo de {$cantidad}x {$producto['nombre']}"
        ]);

        // Actualizar stock
        (new Producto())->updateStock($productoId, $cantidad, 'restar');

        return $id;
    }

    /**
     * Registrar pago de usuario
     */
    public function registrarPago(int $usuarioId, float $cantidad, string $descripcion = ''): int
    {
        return $this->db->insert($this->table, [
            'usuario_id' => $usuarioId,
            'producto_id' => null,
            'tipo' => 'pago',
            'cantidad' => 1,
            'precio_unitario' => $cantidad,
            'total' => $cantidad,
            'descripcion' => $descripcion ?: 'Pago de deuda'
        ]);
    }

    /**
     * Registrar reposición de stock
     */
    public function registrarReposicion(int $adminId, int $productoId, int $cantidad): int
    {
        $producto = (new Producto())->getById($productoId);
        if (!$producto) {
            throw new \Exception('Producto no encontrado');
        }

        // Registrar movimiento
        $id = $this->db->insert($this->table, [
            'usuario_id' => $adminId,
            'producto_id' => $productoId,
            'tipo' => 'reposicion',
            'cantidad' => $cantidad,
            'precio_unitario' => $producto['precio_compra'],
            'total' => $producto['precio_compra'] * $cantidad,
            'descripcion' => "Reposición de {$cantidad}x {$producto['nombre']}"
        ]);

        // Actualizar stock
        (new Producto())->updateStock($productoId, $cantidad, 'sumar');

        return $id;
    }

    /**
     * Registrar ajuste de inventario
     */
    public function registrarAjuste(int $adminId, int $productoId, int $cantidad, string $descripcion): int
    {
        $producto = (new Producto())->getById($productoId);
        if (!$producto) {
            throw new \Exception('Producto no encontrado');
        }

        return $this->db->insert($this->table, [
            'usuario_id' => $adminId,
            'producto_id' => $productoId,
            'tipo' => 'ajuste',
            'cantidad' => $cantidad,
            'precio_unitario' => 0,
            'total' => 0,
            'descripcion' => $descripcion
        ]);
    }

    /**
     * Obtener resumen de movimientos de un usuario
     */
    public function getResumenUsuario(int $usuarioId): array
    {
        $sql = "SELECT 
                    COALESCE(SUM(CASE WHEN tipo = 'consumo' THEN total ELSE 0 END), 0) as total_consumos,
                    COALESCE(SUM(CASE WHEN tipo = 'pago' THEN total ELSE 0 END), 0) as total_pagos,
                    COALESCE(SUM(CASE WHEN tipo = 'consumo' THEN cantidad ELSE 0 END), 0) as cantidad_productos,
                    COUNT(CASE WHEN tipo = 'consumo' THEN 1 END) as num_consumos,
                    COUNT(CASE WHEN tipo = 'pago' THEN 1 END) as num_pagos
                FROM {$this->table}
                WHERE usuario_id = ?";
        
        $result = $this->db->fetch($sql, [$usuarioId]);
        $result['deuda'] = ($result['total_consumos'] ?? 0) - ($result['total_pagos'] ?? 0);
        
        return $result;
    }

    /**
     * Obtener movimientos del mes actual de un usuario
     */
    public function getMovimientosMesActual(int $usuarioId): array
    {
        $sql = "SELECT m.*, p.nombre as producto_nombre
                FROM {$this->table} m
                LEFT JOIN productos p ON m.producto_id = p.id
                WHERE m.usuario_id = ? 
                AND MONTH(m.fecha_hora) = MONTH(CURRENT_DATE())
                AND YEAR(m.fecha_hora) = YEAR(CURRENT_DATE())
                ORDER BY m.fecha_hora DESC";
        
        return $this->db->fetchAll($sql, [$usuarioId]);
    }

    /**
     * Obtener estadísticas para gráficos
     */
    public function getEstadisticasGraficos(string $tipo = 'consumos_por_mes', array $params = []): array
    {
        switch ($tipo) {
            case 'consumos_por_mes':
                $sql = "SELECT 
                            DATE_FORMAT(fecha_hora, '%Y-%m') as mes,
                            COUNT(*) as total_movimientos,
                            SUM(cantidad) as total_cantidad,
                            SUM(total) as total_dinero
                        FROM {$this->table}
                        WHERE tipo = 'consumo'
                        AND fecha_hora >= DATE_SUB(CURRENT_DATE(), INTERVAL 12 MONTH)
                        GROUP BY DATE_FORMAT(fecha_hora, '%Y-%m')
                        ORDER BY mes ASC";
                break;

            case 'consumos_por_producto':
                $sql = "SELECT 
                            p.nombre as producto,
                            SUM(m.cantidad) as total_cantidad,
                            SUM(m.total) as total_dinero
                        FROM {$this->table} m
                        JOIN productos p ON m.producto_id = p.id
                        WHERE m.tipo = 'consumo'
                        GROUP BY m.producto_id
                        ORDER BY total_cantidad DESC
                        LIMIT 10";
                break;

            case 'consumos_por_usuario':
                $sql = "SELECT 
                            u.nombre_usuario as usuario,
                            SUM(m.cantidad) as total_cantidad,
                            SUM(m.total) as total_dinero
                        FROM {$this->table} m
                        JOIN usuarios u ON m.usuario_id = u.id
                        WHERE m.tipo = 'consumo'
                        GROUP BY m.usuario_id
                        ORDER BY total_dinero DESC
                        LIMIT 10";
                break;

            case 'pagos_vs_consumos':
                $sql = "SELECT 
                            DATE_FORMAT(fecha_hora, '%Y-%m') as mes,
                            SUM(CASE WHEN tipo = 'consumo' THEN total ELSE 0 END) as consumos,
                            SUM(CASE WHEN tipo = 'pago' THEN total ELSE 0 END) as pagos
                        FROM {$this->table}
                        WHERE fecha_hora >= DATE_SUB(CURRENT_DATE(), INTERVAL 12 MONTH)
                        GROUP BY DATE_FORMAT(fecha_hora, '%Y-%m')
                        ORDER BY mes ASC";
                break;

            default:
                return [];
        }

        return $this->db->fetchAll($sql);
    }

    /**
     * Eliminar movimiento
     */
    public function delete(int $id): bool
    {
        return $this->db->delete($this->table, 'id = ?', [$id]) > 0;
    }
}
