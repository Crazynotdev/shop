<?php
session_start();

// Configuration BDD
define('DB_HOST', 'localhost');
define('DB_NAME', 'lbs_shop');
define('DB_USER', 'root');
define('DB_PASS', '');

// Connexion PDO
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch(PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
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

// Récupérer le nombre d'articles dans le panier
$cartCount = 0;
if(isLoggedIn()) {
    $stmt = $pdo->prepare("SELECT SUM(quantity) FROM carts WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $cartCount = $stmt->fetchColumn() ?: 0;
}
?>
