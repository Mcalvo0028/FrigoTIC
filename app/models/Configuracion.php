<?php
/**
 * FrigoTIC - Modelo de Configuración
 * 
 * @package FrigoTIC
 * @author MJCRSoftware
 * @version 1.0.0
 */

namespace App\Models;

class Configuracion
{
    private Database $db;
    private string $table = 'configuracion';
    private static array $cache = [];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Obtener todas las configuraciones
     */
    public function getAll(): array
    {
        return $this->db->fetchAll("SELECT * FROM {$this->table} ORDER BY clave ASC");
    }

    /**
     * Obtener valor de configuración
     */
    public function get(string $clave, $default = null)
    {
        // Verificar caché
        if (isset(self::$cache[$clave])) {
            return self::$cache[$clave];
        }

        $result = $this->db->fetch(
            "SELECT valor, tipo FROM {$this->table} WHERE clave = ?",
            [$clave]
        );

        if (!$result) {
            return $default;
        }

        $valor = $this->castValue($result['valor'], $result['tipo']);
        self::$cache[$clave] = $valor;

        return $valor;
    }

    /**
     * Establecer valor de configuración
     */
    public function set(string $clave, $valor, string $tipo = 'string', string $descripcion = null): bool
    {
        // Convertir valor a string para almacenar
        if ($tipo === 'json') {
            $valorStr = is_string($valor) ? $valor : json_encode($valor);
        } elseif ($tipo === 'bool') {
            $valorStr = $valor ? '1' : '0';
        } else {
            $valorStr = (string) $valor;
        }

        // Verificar si existe
        $exists = $this->db->fetch(
            "SELECT id FROM {$this->table} WHERE clave = ?",
            [$clave]
        );

        if ($exists) {
            $result = $this->db->update(
                $this->table,
                ['valor' => $valorStr],
                'clave = ?',
                [$clave]
            ) > 0;
        } else {
            $data = [
                'clave' => $clave,
                'valor' => $valorStr,
                'tipo' => $tipo
            ];
            if ($descripcion) {
                $data['descripcion'] = $descripcion;
            }
            $result = $this->db->insert($this->table, $data) > 0;
        }

        // Actualizar caché
        if ($result) {
            self::$cache[$clave] = $this->castValue($valorStr, $tipo);
        }

        return $result;
    }

    /**
     * Eliminar configuración
     */
    public function delete(string $clave): bool
    {
        unset(self::$cache[$clave]);
        return $this->db->delete($this->table, 'clave = ?', [$clave]) > 0;
    }

    /**
     * Obtener configuraciones por grupo (prefijo)
     */
    public function getByPrefix(string $prefix): array
    {
        $results = $this->db->fetchAll(
            "SELECT * FROM {$this->table} WHERE clave LIKE ? ORDER BY clave ASC",
            [$prefix . '%']
        );

        $config = [];
        foreach ($results as $row) {
            $key = str_replace($prefix, '', $row['clave']);
            $config[$key] = $this->castValue($row['valor'], $row['tipo']);
        }

        return $config;
    }

    /**
     * Obtener configuración SMTP
     */
    public function getSmtpConfig(): array
    {
        return [
            'host' => $this->get('smtp_host', 'smtp.gmail.com'),
            'port' => $this->get('smtp_port', 587),
            'encryption' => $this->get('smtp_encryption', 'tls'),
            'username' => $this->get('smtp_user', ''),
            'password' => $this->get('smtp_password', ''),
            'from_name' => $this->get('smtp_from_name', 'FrigoTIC'),
            'from_email' => $this->get('smtp_user', ''),
        ];
    }

    /**
     * Guardar configuración SMTP
     */
    public function saveSmtpConfig(array $config): bool
    {
        $this->set('smtp_host', $config['host'] ?? 'smtp.gmail.com', 'string');
        $this->set('smtp_port', $config['port'] ?? 587, 'int');
        $this->set('smtp_encryption', $config['encryption'] ?? 'tls', 'string');
        $this->set('smtp_user', $config['username'] ?? '', 'string');
        if (!empty($config['password'])) {
            $this->set('smtp_password', $config['password'], 'string');
        }
        $this->set('smtp_from_name', $config['from_name'] ?? 'FrigoTIC', 'string');

        return true;
    }

    /**
     * Convertir valor al tipo correcto
     */
    private function castValue($valor, string $tipo)
    {
        switch ($tipo) {
            case 'int':
                return (int) $valor;
            case 'bool':
                return (bool) $valor;
            case 'json':
                return json_decode($valor, true);
            default:
                return $valor;
        }
    }

    /**
     * Limpiar caché
     */
    public static function clearCache(): void
    {
        self::$cache = [];
    }
}
