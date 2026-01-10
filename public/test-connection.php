<?php
/**
 * Test de conexión a MySQL
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Test de Conexión MySQL</h1>";

// 1. Verificar extensiones PHP
echo "<h2>1. Extensiones PHP</h2>";
echo "PDO MySQL: " . (extension_loaded('pdo_mysql') ? '✅ SÍ' : '❌ NO') . "<br>";
echo "MySQLi: " . (extension_loaded('mysqli') ? '✅ SÍ' : '❌ NO') . "<br>";

// 2. Cargar .env
echo "<h2>2. Archivo .env</h2>";
$envPath = dirname(__DIR__) . '/.env';
echo "Ruta: $envPath<br>";
echo "Existe: " . (file_exists($envPath) ? '✅ SÍ' : '❌ NO') . "<br>";

if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $config = [];
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        $config[trim($key)] = trim($value);
    }
    
    echo "<h3>Configuración detectada:</h3>";
    echo "DB_HOST: " . ($config['DB_HOST'] ?? 'NO DEFINIDO') . "<br>";
    echo "DB_PORT: " . ($config['DB_PORT'] ?? 'NO DEFINIDO') . "<br>";
    echo "DB_NAME: " . ($config['DB_NAME'] ?? 'NO DEFINIDO') . "<br>";
    echo "DB_USER: " . ($config['DB_USER'] ?? 'NO DEFINIDO') . "<br>";
    echo "DB_PASS: " . (isset($config['DB_PASS']) ? (empty($config['DB_PASS']) ? '(vacía)' : '******') : 'NO DEFINIDO') . "<br>";
    
    // 3. Intentar conexión
    echo "<h2>3. Test de Conexión</h2>";
    
    $host = $config['DB_HOST'] ?? 'localhost';
    $port = $config['DB_PORT'] ?? '3306';
    $name = $config['DB_NAME'] ?? 'frigotic';
    $user = $config['DB_USER'] ?? 'root';
    $pass = $config['DB_PASS'] ?? '';
    
    try {
        $dsn = "mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4";
        echo "DSN: $dsn<br>";
        echo "Usuario: $user<br>";
        echo "Contraseña: " . (empty($pass) ? '(vacía)' : str_repeat('*', strlen($pass))) . "<br><br>";
        
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        
        echo "<strong style='color:green'>✅ CONEXIÓN EXITOSA</strong><br><br>";
        
        // Probar query
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
        $result = $stmt->fetch();
        echo "Usuarios en BD: " . $result['total'] . "<br>";
        
        $stmt = $pdo->query("SELECT nombre_usuario, rol FROM usuarios");
        echo "<h3>Usuarios:</h3><ul>";
        while ($row = $stmt->fetch()) {
            echo "<li>{$row['nombre_usuario']} ({$row['rol']})</li>";
        }
        echo "</ul>";
        
    } catch (PDOException $e) {
        echo "<strong style='color:red'>❌ ERROR DE CONEXIÓN</strong><br>";
        echo "Mensaje: " . $e->getMessage() . "<br>";
        echo "Código: " . $e->getCode() . "<br>";
    }
} else {
    echo "<strong style='color:red'>❌ Archivo .env no encontrado</strong>";
}

echo "<hr>";
echo "<h2>4. Información del Sistema</h2>";
echo "PHP: " . phpversion() . "<br>";
echo "SO: " . PHP_OS . "<br>";
phpinfo();
?>
