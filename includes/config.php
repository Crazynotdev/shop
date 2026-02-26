<?php
// includes/config.php
session_start();

// Charger l'URL de la base depuis .env ou variables Render
$database_url = getenv('DATABASE_URL') ?: 'postgresql://user:pass@host:5432/dbname';

try {
    $pdo = new PDO($database_url, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 10
    ]);
    
    // Test de connexion silencieux
    $pdo->query("SELECT 1");
    
} catch(PDOException $e) {
    error_log("DB Error: " . $e->getMessage());
    die("Site en maintenance. L'équipe technique a été notifiée.");
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
