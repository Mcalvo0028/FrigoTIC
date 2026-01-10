<?php
// Script para resetear la contraseña del admin
$password = 'admin123';
$hash = password_hash($password, PASSWORD_DEFAULT);

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
    echo "Ahora puedes entrar con: admin / admin123\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
