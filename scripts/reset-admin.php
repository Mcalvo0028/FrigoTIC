<?php
/**
 * FrigoTIC - Script de Reseteo de Contraseña Admin
 * 
 * ⚠️ SEGURIDAD: Este script SOLO se ejecuta desde línea de comandos (CLI)
 * NO es accesible desde el navegador
 * 
 * Uso: php scripts/reset-admin.php
 */

// Verificar que se ejecuta desde CLI (línea de comandos)
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("❌ ERROR: Este script solo se puede ejecutar desde la línea de comandos (CLI)\n");
}

$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "🔑 FrigoTIC - Reset de Contraseña Admin\n";
echo "=====================================\n\n";
echo "Contraseña: $password\n";
echo "Hash: $hash\n\n";

// Conectar a BD
require_once __DIR__ . '/../app/helpers/EnvHelper.php';
use App\Helpers\EnvHelper;

EnvHelper::load(__DIR__ . '/../.env');
$dbConfig = EnvHelper::getDatabaseConfig();

try {
    $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['name']};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    $stmt = $pdo->prepare("UPDATE usuarios SET password_hash = ?, debe_cambiar_password = 0 WHERE nombre_usuario = 'admin'");
    $stmt->execute([$hash]);
    
    echo "✅ Contraseña actualizada correctamente para el usuario 'admin'\n";
    echo "Ahora puedes entrar con:\n";
    echo "  Usuario: admin\n";
    echo "  Contraseña: admin123\n\n";
    echo "⚠️ RECUERDA: Cambia la contraseña en el primer login.\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
