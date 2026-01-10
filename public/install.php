<?php
/**
 * FrigoTIC - Script de instalación de base de datos
 * Ejecutar desde el navegador: http://localhost/frigotic/install.php
 * O desde consola: php install.php
 */

// Cargar configuración
require_once __DIR__ . '/app/helpers/EnvHelper.php';
use App\Helpers\EnvHelper;

EnvHelper::load(__DIR__ . '/.env');

$dbConfig = EnvHelper::getDatabaseConfig();

echo "===========================================\n";
echo "FrigoTIC - Instalación de Base de Datos\n";
echo "===========================================\n\n";

echo "Configuración detectada:\n";
echo "  Host: {$dbConfig['host']}\n";
echo "  Puerto: {$dbConfig['port']}\n";
echo "  Base de datos: {$dbConfig['name']}\n";
echo "  Usuario: {$dbConfig['user']}\n";
echo "  Contraseña: " . (empty($dbConfig['pass']) ? '(vacía)' : '******') . "\n\n";

// Paso 1: Conectar sin base de datos para crearla
echo "Paso 1: Conectando al servidor MySQL...\n";

try {
    $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "  ✅ Conexión exitosa al servidor MySQL\n\n";
} catch (PDOException $e) {
    echo "  ❌ Error de conexión: " . $e->getMessage() . "\n";
    echo "\nVerifica las credenciales en el archivo .env\n";
    exit(1);
}

// Paso 2: Crear la base de datos
echo "Paso 2: Creando base de datos '{$dbConfig['name']}'...\n";

try {
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbConfig['name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "  ✅ Base de datos creada o ya existía\n\n";
} catch (PDOException $e) {
    echo "  ❌ Error al crear la base de datos: " . $e->getMessage() . "\n";
    exit(1);
}

// Paso 3: Seleccionar la base de datos
$pdo->exec("USE `{$dbConfig['name']}`");

// Paso 4: Ejecutar migraciones
echo "Paso 3: Ejecutando migraciones...\n";

$migrationFile = __DIR__ . '/database/migrations/001_create_tables.sql';
if (file_exists($migrationFile)) {
    $sql = file_get_contents($migrationFile);
    
    // Dividir por ; para ejecutar cada statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $count = 0;
    foreach ($statements as $statement) {
        if (!empty($statement) && stripos($statement, '--') !== 0) {
            try {
                $pdo->exec($statement);
                $count++;
            } catch (PDOException $e) {
                // Ignorar errores de "tabla ya existe"
                if (strpos($e->getMessage(), 'already exists') === false) {
                    echo "  ⚠️ Advertencia: " . $e->getMessage() . "\n";
                }
            }
        }
    }
    echo "  ✅ Migraciones ejecutadas ({$count} statements)\n\n";
} else {
    echo "  ⚠️ Archivo de migraciones no encontrado\n\n";
}

// Paso 5: Ejecutar seeds
echo "Paso 4: Ejecutando datos iniciales (seeds)...\n";

$seedFile = __DIR__ . '/database/seeds/001_initial_data.sql';
if (file_exists($seedFile)) {
    $sql = file_get_contents($seedFile);
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $count = 0;
    foreach ($statements as $statement) {
        if (!empty($statement) && stripos($statement, '--') !== 0) {
            try {
                $pdo->exec($statement);
                $count++;
            } catch (PDOException $e) {
                // Ignorar errores de duplicados
                if (strpos($e->getMessage(), 'Duplicate') === false) {
                    echo "  ⚠️ Advertencia: " . $e->getMessage() . "\n";
                }
            }
        }
    }
    echo "  ✅ Datos iniciales insertados ({$count} statements)\n\n";
} else {
    echo "  ⚠️ Archivo de seeds no encontrado\n\n";
}

// Paso 6: Verificar instalación
echo "Paso 5: Verificando instalación...\n";

$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo "  Tablas creadas: " . implode(', ', $tables) . "\n";

$adminCheck = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'admin'")->fetchColumn();
echo "  Administradores: {$adminCheck}\n\n";

echo "===========================================\n";
echo "✅ INSTALACIÓN COMPLETADA\n";
echo "===========================================\n\n";
echo "Credenciales por defecto:\n";
echo "  Usuario: admin\n";
echo "  Contraseña: admin123\n\n";
echo "Accede a: http://localhost/frigotic/\n";
echo "\n⚠️ IMPORTANTE: Elimina este archivo (install.php) después de la instalación\n";
