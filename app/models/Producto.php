<?php
/**
 * FrigoTIC - Modelo de Producto
 * 
 * @package FrigoTIC
 * @author MJCRSoftware
 * @version 1.0.0
 */

namespace App\Models;

class Producto
{
    private Database $db;
    private string $table = 'productos';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Obtener todos los productos
     */
    public function getAll(array $filters = [], int $page = 1, int $perPage = 10): array
    {
        $where = ['1 = 1'];
        $params = [];

        if (isset($filters['activo'])) {
            $where[] = 'activo = ?';
            $params[] = $filters['activo'];
        }

        if (!empty($filters['buscar'])) {
            $where[] = '(nombre LIKE ? OR descripcion LIKE ?)';
            $search = '%' . $filters['buscar'] . '%';
            $params = array_merge($params, [$search, $search]);
        }

        if (!empty($filters['stock_bajo'])) {
            $where[] = 'stock <= ?';
            $params[] = $filters['stock_bajo'];
        }

        $whereClause = implode(' AND ', $where);
        
        // Contar total
        $total = $this->db->count($this->table, $whereClause, $params);
        
        // Calcular offset
        $offset = ($page - 1) * $perPage;
        
        // Obtener registros
        $sql = "SELECT * FROM {$this->table} 
                WHERE {$whereClause} 
                ORDER BY nombre ASC 
                LIMIT {$perPage} OFFSET {$offset}";
        
        $productos = $this->db->fetchAll($sql, $params);

        return [
            'data' => $productos,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($total / $perPage)
        ];
    }

    /**
     * Obtener productos activos (para usuarios)
     */
    public function getActivos(): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM {$this->table} WHERE activo = 1 AND stock > 0 ORDER BY nombre ASC"
        );
    }

    /**
     * Obtener producto por ID
     */
    public function getById(int $id): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM {$this->table} WHERE id = ?",
            [$id]
        );
    }

    /**
     * Crear nuevo producto
     */
    public function create(array $data): int
    {
        $productoData = [
            'nombre' => $data['nombre'],
            'descripcion' => $data['descripcion'] ?? null,
            'precio_compra' => $data['precio_compra'] ?? 0,
            'precio_venta' => $data['precio_venta'] ?? 0,
            'stock' => $data['stock'] ?? 0,
            'stock_minimo' => $data['stock_minimo'] ?? 5,
            'imagen' => $data['imagen'] ?? null,
            'activo' => $data['activo'] ?? 1
        ];

        return $this->db->insert($this->table, $productoData);
    }

    /**
     * Actualizar producto
     */
    public function update(int $id, array $data): bool
    {
        $allowedFields = ['nombre', 'descripcion', 'precio_compra', 'precio_venta', 'stock', 'stock_minimo', 'imagen', 'activo'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));

        if (empty($updateData)) {
            return false;
        }

        return $this->db->update($this->table, $updateData, 'id = ?', [$id]) > 0;
    }

    /**
     * Actualizar stock
     */
    public function updateStock(int $id, int $cantidad, string $operacion = 'restar'): bool
    {
        $producto = $this->getById($id);
        if (!$producto) {
            return false;
        }

        $nuevoStock = $operacion === 'sumar' 
            ? $producto['stock'] + $cantidad 
            : $producto['stock'] - $cantidad;

        // No permitir stock negativo
        if ($nuevoStock < 0) {
            return false;
        }

        return $this->db->update(
            $this->table,
            ['stock' => $nuevoStock],
            'id = ?',
            [$id]
        ) > 0;
    }

    /**
     * Eliminar producto (soft delete)
     */
    public function delete(int $id): bool
    {
        return $this->db->update($this->table, ['activo' => 0], 'id = ?', [$id]) > 0;
    }

    /**
     * Eliminar producto permanentemente
     */
    public function hardDelete(int $id): bool
    {
        // Primero eliminar la imagen si existe
        $producto = $this->getById($id);
        if ($producto && $producto['imagen']) {
            $imagePath = dirname(__DIR__, 2) . '/public/uploads/productos/' . $producto['imagen'];
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }

        return $this->db->delete($this->table, 'id = ?', [$id]) > 0;
    }

    /**
     * Obtener productos con stock bajo (según su stock_minimo individual)
     */
    public function getStockBajo(): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM {$this->table} WHERE activo = 1 AND stock <= stock_minimo ORDER BY stock ASC"
        );
    }

    /**
     * Obtener productos activos (para usuarios) - incluye todos, incluso sin stock
     */
    public function getActivosConAgotados(): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM {$this->table} WHERE activo = 1 ORDER BY stock = 0, nombre ASC"
        );
    }

    /**
     * Obtener estadísticas de productos
     */
    public function getEstadisticas(): array
    {
        $sql = "SELECT 
                    COUNT(*) as total_productos,
                    SUM(CASE WHEN activo = 1 THEN 1 ELSE 0 END) as productos_activos,
                    SUM(stock) as stock_total,
                    SUM(stock * precio_compra) as valor_inventario_compra,
                    SUM(stock * precio_venta) as valor_inventario_venta
                FROM {$this->table}";
        
        return $this->db->fetch($sql) ?? [];
    }

    /**
     * Obtener productos más consumidos
     */
    public function getMasConsumidos(int $limite = 10): array
    {
        $sql = "SELECT p.*, COALESCE(SUM(m.cantidad), 0) as total_consumido
                FROM {$this->table} p
                LEFT JOIN movimientos m ON p.id = m.producto_id AND m.tipo = 'consumo'
                WHERE p.activo = 1
                GROUP BY p.id
                ORDER BY total_consumido DESC
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$limite]);
    }
}
