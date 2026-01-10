<?php
/**
 * FrigoTIC - Modelo de Usuario
 * 
 * @package FrigoTIC
 * @author MJCRSoftware
 * @version 1.0.0
 */

namespace App\Models;

class Usuario
{
    private Database $db;
    private string $table = 'usuarios';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Obtener todos los usuarios
     */
    public function getAll(array $filters = [], int $page = 1, int $perPage = 10): array
    {
        $where = ['1 = 1'];
        $params = [];

        if (!empty($filters['rol'])) {
            $where[] = 'rol = ?';
            $params[] = $filters['rol'];
        }

        if (!empty($filters['activo'])) {
            $where[] = 'activo = ?';
            $params[] = $filters['activo'];
        }

        if (!empty($filters['buscar'])) {
            $where[] = '(nombre_usuario LIKE ? OR email LIKE ? OR nombre_completo LIKE ?)';
            $search = '%' . $filters['buscar'] . '%';
            $params = array_merge($params, [$search, $search, $search]);
        }

        $whereClause = implode(' AND ', $where);
        
        // Contar total
        $total = $this->db->count($this->table, $whereClause, $params);
        
        // Calcular offset
        $offset = ($page - 1) * $perPage;
        
        // Obtener registros
        $sql = "SELECT id, nombre_usuario, email, nombre_completo, rol, activo, 
                       debe_cambiar_password, fecha_registro, ultimo_acceso 
                FROM {$this->table} 
                WHERE {$whereClause} 
                ORDER BY nombre_usuario ASC 
                LIMIT {$perPage} OFFSET {$offset}";
        
        $usuarios = $this->db->fetchAll($sql, $params);

        return [
            'data' => $usuarios,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => ceil($total / $perPage)
        ];
    }

    /**
     * Obtener usuario por ID
     */
    public function getById(int $id): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM {$this->table} WHERE id = ?",
            [$id]
        );
    }

    /**
     * Obtener usuario por nombre de usuario
     */
    public function getByUsername(string $username): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM {$this->table} WHERE nombre_usuario = ?",
            [$username]
        );
    }

    /**
     * Obtener usuario por email
     */
    public function getByEmail(string $email): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM {$this->table} WHERE email = ?",
            [$email]
        );
    }

    /**
     * Crear nuevo usuario
     */
    public function create(array $data): int
    {
        $userData = [
            'nombre_usuario' => $data['nombre_usuario'],
            'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            'email' => $data['email'],
            'nombre_completo' => $data['nombre_completo'] ?? null,
            'rol' => $data['rol'] ?? 'user',
            'debe_cambiar_password' => $data['debe_cambiar_password'] ?? 1,
            'activo' => $data['activo'] ?? 1
        ];

        return $this->db->insert($this->table, $userData);
    }

    /**
     * Actualizar usuario
     */
    public function update(int $id, array $data): bool
    {
        $allowedFields = ['nombre_usuario', 'email', 'nombre_completo', 'rol', 'activo', 'debe_cambiar_password'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));

        if (empty($updateData)) {
            return false;
        }

        return $this->db->update($this->table, $updateData, 'id = ?', [$id]) > 0;
    }

    /**
     * Actualizar contraseña
     */
    public function updatePassword(int $id, string $newPassword, bool $mustChange = false): bool
    {
        return $this->db->update(
            $this->table,
            [
                'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
                'debe_cambiar_password' => $mustChange ? 1 : 0
            ],
            'id = ?',
            [$id]
        ) > 0;
    }

    /**
     * Resetear contraseña a valor por defecto
     */
    public function resetPassword(int $id, string $defaultPassword): bool
    {
        return $this->updatePassword($id, $defaultPassword, true);
    }

    /**
     * Verificar contraseña
     */
    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Actualizar último acceso
     */
    public function updateLastAccess(int $id): bool
    {
        return $this->db->update(
            $this->table,
            ['ultimo_acceso' => date('Y-m-d H:i:s')],
            'id = ?',
            [$id]
        ) > 0;
    }

    /**
     * Eliminar usuario (soft delete)
     */
    public function delete(int $id): bool
    {
        return $this->db->update($this->table, ['activo' => 0], 'id = ?', [$id]) > 0;
    }

    /**
     * Eliminar usuario permanentemente
     */
    public function hardDelete(int $id): bool
    {
        return $this->db->delete($this->table, 'id = ?', [$id]) > 0;
    }

    /**
     * Verificar si el nombre de usuario existe
     */
    public function usernameExists(string $username, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE nombre_usuario = ?";
        $params = [$username];

        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }

        $result = $this->db->fetch($sql, $params);
        return ($result['total'] ?? 0) > 0;
    }

    /**
     * Verificar si el email existe
     */
    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE email = ?";
        $params = [$email];

        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }

        $result = $this->db->fetch($sql, $params);
        return ($result['total'] ?? 0) > 0;
    }

    /**
     * Obtener deuda total de un usuario
     */
    public function getDeuda(int $id): float
    {
        $sql = "SELECT 
                    COALESCE(SUM(CASE WHEN tipo = 'consumo' THEN total ELSE 0 END), 0) -
                    COALESCE(SUM(CASE WHEN tipo = 'pago' THEN total ELSE 0 END), 0) as deuda
                FROM movimientos 
                WHERE usuario_id = ?";
        
        $result = $this->db->fetch($sql, [$id]);
        return (float) ($result['deuda'] ?? 0);
    }

    /**
     * Obtener listado de usuarios con sus deudas
     */
    public function getAllWithDeudas(): array
    {
        $sql = "SELECT u.id, u.nombre_usuario, u.nombre_completo, u.email, u.activo,
                       COALESCE(SUM(CASE WHEN m.tipo = 'consumo' THEN m.total ELSE 0 END), 0) -
                       COALESCE(SUM(CASE WHEN m.tipo = 'pago' THEN m.total ELSE 0 END), 0) as deuda
                FROM {$this->table} u
                LEFT JOIN movimientos m ON u.id = m.usuario_id
                WHERE u.rol = 'user'
                GROUP BY u.id
                ORDER BY u.nombre_usuario ASC";
        
        return $this->db->fetchAll($sql);
    }
}
