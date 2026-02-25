<?php
// includes/config.php
session_start();

// Configuration Supabase
define('DB_HOST', 'db.cdxtnqpjrdfzyqnturwi.supabase.co');
define('DB_PORT', '5432');
define('DB_NAME', 'postgres');
define('DB_USER', 'postgres');
define('DB_PASS', 'TMsV-RsQ-prb84.');

try {
    $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 5
    ]);
    
    // Test de connexion silencieux
    $pdo->query("SELECT 1");
    
} catch(PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
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

// Récupérer les catégories pour la navigation
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// Récupérer le nombre d'articles dans le panier (si connecté)
$cartCount = 0;
if(isLoggedIn()) {
    $stmt = $pdo->prepare("SELECT SUM(quantity) FROM carts WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $cartCount = $stmt->fetchColumn() ?: 0;
}
?>
