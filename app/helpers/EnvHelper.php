<?php
/**
 * FrigoTIC - Helper para gestionar archivo .env
 * MJCRSoftware
 */

namespace App\Helpers;

class EnvHelper
{
    private static $envPath = null;
    private static $values = [];
    private static $loaded = false;

    /**
     * Cargar variables del archivo .env
     */
    public static function load($path = null)
    {
        if (self::$loaded && $path === null) {
            return;
        }

        self::$envPath = $path ?? dirname(dirname(__DIR__)) . '/.env';

        if (!file_exists(self::$envPath)) {
            // Si no existe .env, intentar crear desde .env.example
            $examplePath = dirname(self::$envPath) . '/.env.example';
            if (file_exists($examplePath)) {
                copy($examplePath, self::$envPath);
            } else {
                return;
            }
        }

        $lines = file(self::$envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Ignorar comentarios
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // Parsear KEY=value
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Quitar comillas si las tiene
                $value = trim($value, '"\'');
                
                self::$values[$key] = $value;
                
                // También establecer en $_ENV y putenv
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }

        self::$loaded = true;
    }

    /**
     * Obtener valor de variable de entorno
     */
    public static function get($key, $default = null)
    {
        self::load();
        return self::$values[$key] ?? $_ENV[$key] ?? getenv($key) ?: $default;
    }

    /**
     * Establecer valor (en memoria)
     */
    public static function set($key, $value)
    {
        self::load();
        self::$values[$key] = $value;
        $_ENV[$key] = $value;
        putenv("$key=$value");
    }

    /**
     * Guardar cambios al archivo .env
     */
    public static function save($newValues = [])
    {
        self::load();

        // Actualizar valores
        foreach ($newValues as $key => $value) {
            self::$values[$key] = $value;
        }

        // Leer archivo original para mantener comentarios y estructura
        $content = '';
        $existingKeys = [];

        if (file_exists(self::$envPath)) {
            $lines = file(self::$envPath, FILE_IGNORE_NEW_LINES);
            
            foreach ($lines as $line) {
                // Mantener comentarios y líneas vacías
                if (trim($line) === '' || strpos(trim($line), '#') === 0) {
                    $content .= $line . "\n";
                    continue;
                }

                // Actualizar valores existentes
                if (strpos($line, '=') !== false) {
                    list($key, ) = explode('=', $line, 2);
                    $key = trim($key);
                    $existingKeys[] = $key;
                    
                    if (isset(self::$values[$key])) {
                        $content .= "$key=" . self::$values[$key] . "\n";
                    } else {
                        $content .= $line . "\n";
                    }
                }
            }
        }

        // Añadir nuevas keys que no existían
        foreach (self::$values as $key => $value) {
            if (!in_array($key, $existingKeys)) {
                $content .= "$key=$value\n";
            }
        }

        return file_put_contents(self::$envPath, $content) !== false;
    }

    /**
     * Obtener todos los valores
     */
    public static function all()
    {
        self::load();
        return self::$values;
    }

    /**
     * Obtener configuración de BD
     */
    public static function getDatabaseConfig()
    {
        self::load();
        return [
            'host' => self::get('DB_HOST', 'localhost'),
            'port' => self::get('DB_PORT', '3306'),
            'name' => self::get('DB_NAME', 'frigotic'),
            'user' => self::get('DB_USER', 'root'),
            'pass' => self::get('DB_PASS', ''),
        ];
    }

    /**
     * Obtener configuración SMTP
     */
    public static function getSmtpConfig()
    {
        self::load();
        return [
            'host' => self::get('SMTP_HOST', 'smtp.gmail.com'),
            'port' => self::get('SMTP_PORT', '587'),
            'encryption' => self::get('SMTP_ENCRYPTION', 'tls'),
            'username' => self::get('SMTP_USER', ''),
            'password' => self::get('SMTP_PASS', ''),
            'from_name' => self::get('SMTP_FROM_NAME', 'FrigoTIC'),
        ];
    }

    /**
     * Guardar configuración de BD
     */
    public static function saveDatabaseConfig($config)
    {
        return self::save([
            'DB_HOST' => $config['host'] ?? 'localhost',
            'DB_PORT' => $config['port'] ?? '3306',
            'DB_NAME' => $config['name'] ?? 'frigotic',
            'DB_USER' => $config['user'] ?? 'root',
            'DB_PASS' => $config['pass'] ?? '',
        ]);
    }

    /**
     * Guardar configuración SMTP
     */
    public static function saveSmtpConfig($config)
    {
        return self::save([
            'SMTP_HOST' => $config['host'] ?? 'smtp.gmail.com',
            'SMTP_PORT' => $config['port'] ?? '587',
            'SMTP_ENCRYPTION' => $config['encryption'] ?? 'tls',
            'SMTP_USER' => $config['username'] ?? '',
            'SMTP_PASS' => $config['password'] ?? '',
            'SMTP_FROM_NAME' => $config['from_name'] ?? 'FrigoTIC',
        ]);
    }

    /**
     * Probar conexión a BD
     */
    public static function testDatabaseConnection($config = null)
    {
        if ($config === null) {
            $config = self::getDatabaseConfig();
        }

        try {
            $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['name']};charset=utf8mb4";
            $pdo = new \PDO($dsn, $config['user'], $config['pass'], [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_TIMEOUT => 5
            ]);
            $pdo = null;
            return ['success' => true, 'message' => 'Conexión exitosa'];
        } catch (\PDOException $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}

/**
 * Función helper global
 */
function env($key, $default = null)
{
    return EnvHelper::get($key, $default);
}
