<?php
// includes/config.php
session_start();

// Détection de l'environnement
if(getenv('RENDER') === 'true') {
    // Sur Render - on utilise les variables d'environnement
    $host = getenv('DB_HOST');
    $port = getenv('DB_PORT') ?: '5432';
    $dbname = getenv('DB_NAME');
    $user = getenv('DB_USER');
    $pass = getenv('DB_PASS');
} else {
    // En local - on utilise le fichier .env
    $env_file = __DIR__ . '/../.env';
    if(file_exists($env_file)) {
        $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach($lines as $line) {
            if(strpos(trim($line), '#') === 0) continue;
            putenv($line);
        }
    }
    
    $host = getenv('DB_HOST') ?: 'db.cdx.tngpj.rdfzyqnturwi.supabase.co';
    $port = getenv('DB_PORT') ?: '5432';
    $dbname = getenv('DB_NAME') ?: 'postgres';
    $user = getenv('DB_USER') ?: 'postgres';
    $pass = getenv('DB_PASS') ?: 'LPBzHJV0PsHhHHzY';
}

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 5
    ]);
    
    // Test de connexion silencieux
    $pdo->query("SELECT 1");
    
} catch(PDOException $e) {
    error_log("DB Error: " . $e->getMessage());
    die("Erreur de connexion à la base de données");
}

// Fonctions utilitaires
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function formatPrice($price) {
    return number_format($price, 0, ',', ' ') . ' FCFA';
}

// Récupérer les catégories
try {
    $categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
} catch(Exception $e) {
    $categories = [];
}

// Récupérer le nombre d'articles dans le panier
$cartCount = 0;
if(isLoggedIn()) {
    $stmt = $pdo->prepare("SELECT SUM(quantity) FROM carts WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $cartCount = $stmt->fetchColumn() ?: 0;
}
?>
