<?php
/**
 * FrigoTIC - Script de migración para añadir stock_minimo
 */

require_once __DIR__ . '/../app/helpers/EnvHelper.php';

// Cargar las variables de entorno
\App\Helpers\EnvHelper::load();

// Cargar configuración de base de datos
$host = getenv('DB_HOST') ?: 'localhost';
$port = getenv('DB_PORT') ?: '3306';
$dbname = getenv('DB_NAME') ?: 'frigotic';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';

echo "Conectando como: $user@$host:$port/$dbname\n";

try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "Conectado a la base de datos.\n";
    
    // Verificar si el campo ya existe
    $stmt = $pdo->query("SHOW COLUMNS FROM productos LIKE 'stock_minimo'");
    $exists = $stmt->fetch();
    
    if ($exists) {
        echo "El campo stock_minimo ya existe. No se requiere migración.\n";
    } else {
        // Añadir el campo
        $pdo->exec("ALTER TABLE productos ADD COLUMN stock_minimo INT NOT NULL DEFAULT 5 AFTER stock");
        echo "Campo stock_minimo añadido correctamente.\n";
    }
    
    // Verificar tabla plantillas_correo
    $stmt = $pdo->query("SHOW TABLES LIKE 'plantillas_correo'");
    if (!$stmt->fetch()) {
        echo "Creando tabla plantillas_correo...\n";
        $pdo->exec("
            CREATE TABLE plantillas_correo (
                id INT AUTO_INCREMENT PRIMARY KEY,
                tipo VARCHAR(50) NOT NULL UNIQUE,
                asunto VARCHAR(255) NOT NULL,
                cuerpo TEXT NOT NULL,
                variables_disponibles TEXT,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "Tabla plantillas_correo creada.\n";
    } else {
        echo "Tabla plantillas_correo ya existe.\n";
    }
    
    echo "\nMigración completada correctamente.\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
