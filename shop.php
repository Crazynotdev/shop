<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/functions.php';


// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// Filtres
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : null;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Construction de la requête
$sql = "SELECT p.*, c.name as category_name, 
        COALESCE(AVG(r.rating), 0) as avg_rating,
        COUNT(r.id) as review_count
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        LEFT JOIN reviews r ON p.id = r.product_id
        WHERE p.status = 'active'";
        
$count_sql = "SELECT COUNT(*) FROM products WHERE status = 'active'";
$params = [];

if($category_id) {
    $sql .= " AND p.category_id = ?";
    $count_sql .= " AND category_id = ?";
    $params[] = $category_id;
}

if($search) {
    $sql .= " AND (p.name ILIKE ? OR p.description ILIKE ?)";
    $count_sql .= " AND (name ILIKE ? OR description ILIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
}

$sql .= " GROUP BY p.id, c.name";

// Tri
switch($sort) {
    case 'price_asc':
        $sql .= " ORDER BY p.price ASC";
        break;
    case 'price_desc':
        $sql .= " ORDER BY p.price DESC";
        break;
    case 'popular':
        $sql .= " ORDER BY p.sold_count DESC";
        break;
    case 'rating':
        $sql .= " ORDER BY avg_rating DESC";
        break;
    default:
        $sql .= " ORDER BY p.created_at DESC";
}

$sql .= " LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

// Compter le total
$stmt = $pdo->prepare($count_sql);
$stmt->execute(array_slice($params, 0, count($params)-2));
$total_products = $stmt->fetchColumn();
$total_pages = ceil($total_products / $limit);

// Récupérer les produits
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Boutique - LBS SHOP</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <a href="index.php" class="logo">LBS<span>SHOP</span></a>
            
            <div class="nav-search">
                <form action="shop.php" method="GET" class="search-form">
                    <input type="text" name="search" placeholder="Rechercher un produit..." value="<?= sanitize($search) ?>">
                    <button type="submit">🔍</button>
                </form>
            </div>
            
            <div class="nav-links">
                <a href="shop.php" class="nav-link active">Boutique</a>
                <a href="cart.php" class="nav-link">🛒 Panier</a>
                <?php if(isLoggedIn()): ?>
                    <a href="account.php" class="nav-link">👤 <?= sanitize($_SESSION['user_name']) ?></a>
                <?php else: ?>
                    <a href="login.php" class="btn-login">Connexion</a>
                    <a href="register.php" class="btn-register">Inscription</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Filtres -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin: 2rem 0; flex-wrap: wrap; gap: 1rem;">
            <div style="display: flex; gap: 1rem;">
                <a href="shop.php" class="btn <?= !$category_id ? 'btn-primary' : 'btn-outline' ?>">Tous</a>
                <?php foreach($categories as $cat): ?>
                    <a href="shop.php?category=<?= $cat['id'] ?>" class="btn <?= $category_id == $cat['id'] ? 'btn-primary' : 'btn-outline' ?>">
                        <?= $cat['icon'] ?> <?= sanitize($cat['name']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
            
            <select onchange="window.location.href = 'shop.php?<?= $category_id ? 'category='.$category_id.'&' : '' ?>sort=' + this.value" class="form-control" style="width: auto;">
                <option value="newest" <?= $sort == 'newest' ? 'selected' : '' ?>>Plus récents</option>
                <option value="price_asc" <?= $sort == 'price_asc' ? 'selected' : '' ?>>Prix croissant</option>
                <option value="price_desc" <?= $sort == 'price_desc' ? 'selected' : '' ?>>Prix décroissant</option>
                <option value="popular" <?= $sort == 'popular' ? 'selected' : '' ?>>Plus populaires</option>
                <option value="rating" <?= $sort == 'rating' ? 'selected' : '' ?>>Mieux notés</option>
            </select>
        </div>

        <!-- Grille produits -->
        <div class="products-grid">
            <?php foreach($products as $product): ?>
                <div class="product-card">
                    <?php if($product['old_price'] > 0): ?>
                        <div class="product-badge">-<?= round((1 - $product['price']/$product['old_price'])*100) ?>%</div>
                    <?php endif; ?>
                    
                    <div class="product-image">
                        <img src="uploads/products/<?= $product['image'] ?>" alt="<?= sanitize($product['name']) ?>">
                        
                        <div class="product-actions">
                            <button class="action-btn" onclick="addToWishlist(<?= $product['id'] ?>)" title="Ajouter aux favoris">❤️</button>
                            <button class="action-btn" onclick="quickView(<?= $product['id'] ?>)" title="Aperçu rapide">👁️</button>
                        </div>
                    </div>
                    
                    <div class="product-info">
                        <div class="product-category"><?= sanitize($product['category_name']) ?></div>
                        <h3><?= sanitize($product['name']) ?></h3>
                        
                        <div class="product-price">
                            <span class="current-price"><?= formatPrice($product['price']) ?></span>
                            <?php if($product['old_price'] > 0): ?>
                                <span class="old-price"><?= formatPrice($product['old_price']) ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="product-rating">
                            <span class="stars">
                                <?php
                                $rating = round($product['avg_rating'] * 2) / 2;
                                for($i = 1; $i <= 5; $i++) {
                                    if($i <= $rating) {
                                        echo '★';
                                    } elseif($i - 0.5 <= $rating) {
                                        echo '½';
                                    } else {
                                        echo '☆';
                                    }
                                }
                                ?>
                            </span>
                            <span>(<?= $product['review_count'] ?> avis)</span>
                        </div>
                        
                        <a href="product.php?id=<?= $product['id'] ?>&slug=<?= generateSlug($product['name']) ?>" class="btn btn-primary" style="width: 100%;">Voir le produit</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if($total_pages > 1): ?>
            <div style="display: flex; justify-content: center; gap: 0.5rem; margin: 3rem 0;">
                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="shop.php?<?= $category_id ? 'category='.$category_id.'&' : '' ?><?= $search ? 'search='.urlencode($search).'&' : '' ?>page=<?= $i ?>" 
                       class="btn <?= $i == $page ? 'btn-primary' : 'btn-outline' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <h4>LBS SHOP</h4>
                    <p>Le meilleur du drip et des maillots au Gabon 🇬🇦</p>
                </div>
                <div class="footer-col">
                    <h4>Liens rapides</h4>
                    <a href="shop.php">Boutique</a>
                    <a href="contact.php">Contact</a>
                    <a href="about.php">À propos</a>
                </div>
                <div class="footer-col">
                    <h4>Informations</h4>
                    <a href="privacy.php">Confidentialité</a>
                    <a href="terms.php">Conditions</a>
                </div>
                <div class="footer-col">
                    <h4>Contact</h4>
                    <p>📧 contact@lbsshop.ga</p>
                    <p>📞 +241 77 123 456</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> LBS SHOP. Tous droits réservés.</p>
            </div>
        </div>
    </footer>

    <script>
    function addToWishlist(id) {
        alert('Fonctionnalité à venir : Ajouter aux favoris');
    }
    
    function quickView(id) {
        alert('Fonctionnalité à venir : Aperçu rapide');
    }
    </script>
</body>
</html>
