<?php require_once __DIR__ . '/includes/config.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LBS SHOP - Drip Officiel du Gabon</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <a href="index.php" class="nav-logo">
                <img src="https://files.catbox.moe/e0a61i.jpg" alt="LBS SHOP">
            </a>
            
            <div class="nav-search">
                <form action="shop.php" method="GET" class="search-form">
                    <input type="text" name="search" placeholder="Rechercher un produit...">
                    <button type="submit">🔍</button>
                </form>
            </div>
            
            <div class="nav-links">
                <a href="shop.php" class="nav-link">Boutique</a>
                
                <?php if(isLoggedIn()): ?>
                    <a href="cart.php" class="nav-link cart-link">
                        🛒 <span class="cart-count"><?= $cartCount ?></span>
                    </a>
                    <a href="account.php" class="nav-link"><?= sanitize($_SESSION['user_name']) ?></a>
                    <a href="logout.php" class="nav-link btn-login">Déconnexion</a>
                <?php else: ?>
                    <a href="login.php" class="nav-link btn-login">Connexion</a>
                    <a href="register.php" class="nav-link btn-register">Inscription</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    
    <!-- Hero -->
    <section style="background: linear-gradient(135deg, var(--gray-900), var(--gray-800)); color: white; padding: 4rem 0; text-align: center;">
        <div class="container">
            <h1 style="font-size: 2.5rem; margin-bottom: 1rem;">DRIP OFFICIEL DU GABON 🇬🇦</h1>
            <p style="font-size: 1.2rem; margin-bottom: 2rem; opacity: 0.9;">Maillots • Baskets • Streetwear</p>
            <a href="shop.php" class="btn btn-primary">Découvrir la boutique</a>
        </div>
    </section>
    
    <!-- Produits récents -->
    <section style="padding: 4rem 0;">
        <div class="container">
            <h2 style="font-size: 2rem; text-align: center; margin-bottom: 2rem;">Nouveautés</h2>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 2rem;">
                <?php
                $stmt = $pdo->query("SELECT * FROM products WHERE stock > 0 ORDER BY created_at DESC LIMIT 4");
                while($product = $stmt->fetch()):
                ?>
                <div style="background: white; border-radius: var(--radius); overflow: hidden; box-shadow: var(--shadow);">
                    <a href="product.php?id=<?= $product['id'] ?>" style="text-decoration: none; color: inherit;">
                        <img src="uploads/<?= $product['image'] ?>" alt="<?= sanitize($product['name']) ?>" style="width: 100%; height: 200px; object-fit: cover;">
                        <div style="padding: 1rem;">
                            <h3 style="font-size: 1rem; margin-bottom: 0.5rem;"><?= sanitize($product['name']) ?></h3>
                            <div style="font-weight: 700; color: var(--primary);"><?= formatPrice($product['price']) ?></div>
                        </div>
                    </a>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </section>
    
    <!-- Features -->
    <section style="background: white; padding: 4rem 0; border-top: 1px solid var(--gray-200);">
        <div class="container">
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 2rem;">
                <div style="text-align: center;">
                    <div style="font-size: 2.5rem; margin-bottom: 1rem;">🚚</div>
                    <h3 style="margin-bottom: 0.5rem;">Livraison rapide</h3>
                    <p style="color: var(--gray-500);">Partout au Gabon</p>
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 2.5rem; margin-bottom: 1rem;">🔒</div>
                    <h3 style="margin-bottom: 0.5rem;">Paiement sécurisé</h3>
                    <p style="color: var(--gray-500);">Orange Money, Carte</p>
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 2.5rem; margin-bottom: 1rem;">⭐</div>
                    <h3 style="margin-bottom: 0.5rem;">Qualité garantie</h3>
                    <p style="color: var(--gray-500);">Produits authentiques</p>
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 2.5rem; margin-bottom: 1rem;">📞</div>
                    <h3 style="margin-bottom: 0.5rem;">Support 24/7</h3>
                    <p style="color: var(--gray-500);">Service client</p>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Footer -->
    <footer style="background: var(--gray-900); color: white; padding: 3rem 0;">
        <div class="container">
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 2rem;">
                <div>
                    <h4 style="color: var(--primary); margin-bottom: 1rem;">LBS SHOP</h4>
                    <p style="color: var(--gray-400);">Le meilleur du drip au Gabon</p>
                </div>
                <div>
                    <h4 style="color: var(--primary); margin-bottom: 1rem;">Liens rapides</h4>
                    <a href="shop.php" style="display: block; color: var(--gray-400); margin-bottom: 0.5rem; text-decoration: none;">Boutique</a>
                    <a href="login.php" style="display: block; color: var(--gray-400); margin-bottom: 0.5rem; text-decoration: none;">Connexion</a>
                    <a href="register.php" style="display: block; color: var(--gray-400); text-decoration: none;">Inscription</a>
                </div>
                <div>
                    <h4 style="color: var(--primary); margin-bottom: 1rem;">Contact</h4>
                    <p style="color: var(--gray-400);">📧 contact@lbsshop.ga</p>
                    <p style="color: var(--gray-400);">📞 +24165730123</p>
                </div>
            </div>
            <div style="text-align: center; margin-top: 3rem; padding-top: 2rem; border-top: 1px solid var(--gray-800); color: var(--gray-500);">
                <p>&copy; 2026 LBS SHOP. Tous droits réservés.</p>
            </div>
        </div>
    </footer>
</body>
</html>
