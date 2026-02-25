<?php
// test-corrige.php
echo "<h1>🔧 Test avec les bons paramètres</h1>";

// Récupère le vrai host depuis Supabase
$host = 'db.cdxtnqpjrdfzyqnturwi.supabase.co'; // Sans les points supplémentaires
$port = '5432';
$dbname = 'postgres';
$user = 'postgres';
$pass = 'LPBzHJV0PsHhHHzY';

echo "Host: $host<br>";
echo "Port: $port<br>";

// Test PDO
try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 10
    ]);
    echo "✅ Connexion réussie !";
} catch(PDOException $e) {
    echo "❌ Erreur: " . $e->getMessage();
}
?>
