<?php
/**
 * FrigoTIC - Modelo de Factura
 * 
 * @package FrigoTIC
 * @author MJCRSoftware
 * @version 1.0.0
 */

namespace App\Models;

class Factura
{
    private Database $db;
    private string $table = 'facturas';
    private string $uploadPath;

    public function __construct()
    {
        $this->db = Database::getInstance();
        // Ruta segura fuera de public
        $this->uploadPath = dirname(__DIR__, 2) . '/storage/facturas/';
    }

    /**
     * Obtener todas las facturas
     */
    public function getAll(array $filters = [], int $page = 1, int $perPage = 10): array
    {
        $where = ['1 = 1'];
        $params = [];

        if (!empty($filters['buscar'])) {
            $where[] = '(f.nombre_original LIKE ? OR f.descripcion LIKE ?)';
            $search = '%' . $filters['buscar'] . '%';
            $params = array_merge($params, [$search, $search]);
        }

        if (!empty($filters['fecha_desde'])) {
            $where[] = 'f.fecha_factura >= ?';
            $params[] = $filters['fecha_desde'];
        }

        if (!empty($filters['fecha_hasta'])) {
            $where[] = 'f.fecha_factura <= ?';
            $params[] = $filters['fecha_hasta'];
        }

        $whereClause = implode(' AND ', $where);
        
        // Contar total
        $countSql = "SELECT COUNT(*) as total FROM {$this->table} f WHERE {$whereClause}";
        $countResult = $this->db->fetch($countSql, $params);
        $total = (int) ($countResult['total'] ?? 0);
        
        // Calcular offset
        $offset = ($page - 1) * $perPage;
        
        // Obtener registros
        $sql = "SELECT f.*, u.nombre_usuario as subido_por_nombre
                FROM {$this->table} f
                LEFT JOIN usuarios u ON f.subido_por = u.id
                WHERE {$whereClause} 
                ORDER BY f.fecha_subida DESC 
                LIMIT {$perPage} OFFSET {$offset}";
        
        $facturas = $this->db->fetchAll($sql, $params);

        return [
            'data' => $facturas,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($total / $perPage)
        ];
    }

    /**
     * Obtener factura por ID
     */
    public function getById(int $id): ?array
    {
        $sql = "SELECT f.*, u.nombre_usuario as subido_por_nombre
                FROM {$this->table} f
                LEFT JOIN usuarios u ON f.subido_por = u.id
                WHERE f.id = ?";
        
        return $this->db->fetch($sql, [$id]);
    }

    /**
     * Subir nueva factura
     */
    public function upload(array $file, array $data, int $usuarioId): int
    {
        // Validar archivo
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \Exception('Error al subir el archivo');
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($extension !== 'pdf') {
            throw new \Exception('Solo se permiten archivos PDF');
        }

        // Verificar tamaño (10MB máximo)
        if ($file['size'] > 10 * 1024 * 1024) {
            throw new \Exception('El archivo es demasiado grande (máximo 10MB)');
        }

        // Generar nombre único
        $nombreArchivo = uniqid('factura_') . '_' . date('Ymd') . '.pdf';
        $rutaDestino = $this->uploadPath . $nombreArchivo;

        // Crear directorio si no existe
        if (!is_dir($this->uploadPath)) {
            mkdir($this->uploadPath, 0755, true);
        }

        // Mover archivo
        if (!move_uploaded_file($file['tmp_name'], $rutaDestino)) {
            throw new \Exception('Error al guardar el archivo');
        }

        // Guardar en base de datos
        return $this->db->insert($this->table, [
            'nombre_archivo' => $nombreArchivo,
            'nombre_original' => $file['name'],
            'ruta_archivo' => 'storage/facturas/' . $nombreArchivo,
            'tamano' => $file['size'],
            'descripcion' => $data['descripcion'] ?? null,
            'fecha_factura' => $data['fecha_factura'] ?? null,
            'subido_por' => $usuarioId
        ]);
    }

    /**
     * Actualizar datos de factura
     */
    public function update(int $id, array $data): bool
    {
        $allowedFields = ['descripcion', 'fecha_factura'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));

        if (empty($updateData)) {
            return false;
        }

        return $this->db->update($this->table, $updateData, 'id = ?', [$id]) > 0;
    }

    /**
     * Eliminar factura
     */
    public function delete(int $id): bool
    {
        $factura = $this->getById($id);
        if (!$factura) {
            return false;
        }

        // Eliminar archivo físico (ruta segura en storage)
        $rutaArchivo = dirname(__DIR__, 2) . '/' . $factura['ruta_archivo'];
        if (file_exists($rutaArchivo)) {
            unlink($rutaArchivo);
        }

        // Eliminar registro
        return $this->db->delete($this->table, 'id = ?', [$id]) > 0;
    }

    /**
     * Obtener ruta completa del archivo
     */
    public function getRutaCompleta(int $id): ?string
    {
        $factura = $this->getById($id);
        if (!$factura) {
            return null;
        }

        // Ruta segura en storage
        $ruta = dirname(__DIR__, 2) . '/' . $factura['ruta_archivo'];
        return file_exists($ruta) ? $ruta : null;
    }

    /**
     * Obtener estadísticas de facturas
     */
    public function getEstadisticas(): array
    {
        $sql = "SELECT 
                    COUNT(*) as total_facturas,
                    SUM(tamano) as tamano_total,
                    MIN(fecha_factura) as fecha_primera,
                    MAX(fecha_factura) as fecha_ultima
                FROM {$this->table}";
        
        return $this->db->fetch($sql) ?? [];
    }
}
