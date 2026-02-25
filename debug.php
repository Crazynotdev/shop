<?php
// debug.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔧 DEBUG COMPLET</h1>";

echo "<h3>Extensions PHP chargées:</h3>";
$extensions = get_loaded_extensions();
sort($extensions);
foreach($extensions as $ext) {
    if(strpos($ext, 'pdo') !== false || strpos($ext, 'pgsql') !== false) {
        echo "✅ $ext<br>";
    }
}

echo "<h3>Test de connexion:</h3>";

$host = 'db.cdx.tngpj.rdfzyqnturwi.supabase.co';
$port = 5432;
$dbname = 'postgres';
$user = 'postgres';
$pass = 'LPBzHJV0PsHhHHzY';

echo "Host: $host<br>";
echo "Port: $port<br>";
echo "Database: $dbname<br>";
echo "User: $user<br>";

// Test réseau
echo "<h4>Test réseau (ping):</h4>";
$host_ip = gethostbyname($host);
if($host_ip === $host) {
    echo "❌ Impossible de résoudre le nom d'hôte<br>";
} else {
    echo "✅ Host résolu: $host_ip<br>";
    
    // Test port
    $connection = @fsockopen($host_ip, $port, $errno, $errstr, 5);
    if($connection) {
        echo "✅ Port $port ouvert<br>";
        fclose($connection);
    } else {
        echo "❌ Port $port fermé: $errstr<br>";
    }
}

// Test PDO
echo "<h4>Test PDO:</h4>";
try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 10
    ]);
    echo "✅ PDO OK<br>";
    
    $result = $pdo->query("SELECT 1")->fetch();
    echo "✅ Requête OK<br>";
    
} catch(PDOException $e) {
    echo "❌ PDO Error: " . $e->getMessage() . "<br>";
    echo "Code: " . $e->getCode() . "<br>";
}
?>
