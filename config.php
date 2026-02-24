<?php
// config.php
session_start();

// Détection de l'environnement
if(getenv('RENDER') === 'true') {
    // 🟢 PRODUCTION SUR RENDER
    define('DB_HOST', getenv('127.0.0.1')); // ou MYSQL_HOST selon ta variable
    define('DB_NAME', getenv('lbs-shop'));
    define('DB_USER', getenv('root'));
    define('DB_PASS', getenv(''));
    define('DB_PORT', getenv('3306') ?: '3306');
    
    // IMPORTANT : Ne pas utiliser 'localhost' sur Render !
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
} else {
    // 🟢 LOCAL (XAMPP/MAMP)
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'lbs_shop');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_PORT', '3306');
    
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
}

try {
    $pdo = new PDO(
        $dsn,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 5 // Timeout de 5 secondes
        ]
    );
} catch(PDOException $e) {
    // Message d'erreur plus détaillé pour le debug
    die("Erreur de connexion à la base de données : " . $e->getMessage() . "<br>DSN utilisé : " . $dsn);
}

// Fonctions existantes...
?>
