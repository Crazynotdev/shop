<?php
require_once __DIR__ . '/includes/config.php';

// Rediriger si non connecté
if(!isLoggedIn()) {
    $_SESSION['error'] = "Veuillez vous connecter pour accéder à votre compte";
    redirect('login.php');
}

// Récupérer les infos de l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Récupérer les commandes de l'utilisateur
$stmt = $pdo->prepare("
    SELECT o.*, COUNT(oi.id) as items_count 
    FROM orders o 
    LEFT JOIN order_items oi ON o.id = oi.order_id 
    WHERE o.user_id = ? 
    GROUP BY o.id 
    ORDER BY o.created_at DESC 
    LIMIT 5
");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();

// Récupérer les favoris (simulé avec une table wishlist à créer)
/*
$stmt = $pdo->prepare("
    SELECT p.* FROM products p 
    JOIN wishlists w ON p.id = w.product_id 
    WHERE w.user_id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$wishlist = $stmt->fetchAll();
*/
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Compte - LBS SHOP</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <a href="index.php" class="logo">LBS<span>SHOP</span></a>
            
            <div class="nav-links">
                <a href="shop.php" class="nav-link">Boutique</a>
                <a href="cart.php" class="nav-link">🛒 Panier</a>
                
                <div class="user-menu">
                    <?php if(!empty($user['avatar'])): ?>
                        <img src="uploads/profiles/<?= $user['avatar'] ?>" alt="Avatar" class="user-avatar">
                    <?php else: ?>
                        <img src="assets/images/default-avatar.png" alt="Avatar" class="user-avatar">
                    <?php endif; ?>
                    <span class="user-name"><?= sanitize($user['full_name'] ?: $user['username']) ?></span>
                    
                    <div class="dropdown-menu">
                        <a href="account.php" class="dropdown-item">👤 Mon profil</a>
                        <a href="orders.php" class="dropdown-item">📦 Mes commandes</a>
                        <a href="wishlist.php" class="dropdown-item">❤️ Favoris</a>
                        <div class="dropdown-divider"></div>
                        <a href="edit-account.php" class="dropdown-item">⚙️ Paramètres</a>
                        <a href="logout.php" class="dropdown-item" style="color: var(--danger);">🚪 Déconnexion</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="container">
        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        
        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-error"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <!-- En-tête du profil -->
        <div class="profile-header">
            <div>
                <?php if(!empty($user['avatar'])): ?>
                    <img src="uploads/profiles/<?= $user['avatar'] ?>" alt="Avatar" class="profile-avatar">
                <?php else: ?>
                    <img src="assets/images/default-avatar.png" alt="Avatar" class="profile-avatar">
                <?php endif; ?>
            </div>
            
            <div class="profile-info">
                <h2><?= sanitize($user['full_name'] ?: $user['username']) ?></h2>
                <p>📧 <?= sanitize($user['email']) ?></p>
                <?php if($user['phone']): ?>
                    <p>📞 <?= sanitize($user['phone']) ?></p>
                <?php endif; ?>
                <?php if($user['address']): ?>
                    <p>📍 <?= sanitize($user['address']) ?></p>
                <?php endif; ?>
                
                <a href="edit-account.php" class="btn btn-outline" style="margin-top: 1rem;">✏️ Modifier le profil</a>
            </div>
        </div>

        <!-- Statistiques -->
        <div class="profile-stats">
            <div class="stat-card">
                <div class="stat-value"><?= count($orders) ?></div>
                <div class="stat-label">Commandes</div>
            </div>
            <div class="stat-card">
                <?php
                $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE user_id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $total_spent = $stmt->fetchColumn();
                ?>
                <div class="stat-value"><?= formatPrice($total_spent) ?></div>
                <div class="stat-label">Dépensé</div>
            </div>
            <div class="stat-card">
                <?php
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM carts WHERE user_id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $cart_items = $stmt->fetchColumn();
                ?>
                <div class="stat-value"><?= $cart_items ?></div>
                <div class="stat-label">Articles dans le panier</div>
            </div>
        </div>

        <!-- Dernières commandes -->
        <h3 style="margin: 3rem 0 1.5rem;">📦 Dernières commandes</h3>
        
        <?php if(empty($orders)): ?>
            <div style="text-align: center; padding: 3rem; background: white; border-radius: var(--radius);">
                <p style="color: var(--gray-500); margin-bottom: 1rem;">Vous n'avez pas encore de commande</p>
                <a href="shop.php" class="btn btn-primary">Découvrir la boutique</a>
            </div>
        <?php else: ?>
            <div style="background: white; border-radius: var(--radius); overflow: hidden;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: var(--gray-100);">
                            <th style="padding: 1rem; text-align: left;">N° Commande</th>
                            <th style="padding: 1rem; text-align: left;">Date</th>
                            <th style="padding: 1rem; text-align: left;">Articles</th>
                            <th style="padding: 1rem; text-align: left;">Total</th>
                            <th style="padding: 1rem; text-align: left;">Statut</th>
                            <th style="padding: 1rem; text-align: left;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($orders as $order): ?>
                        <tr style="border-bottom: 1px solid var(--gray-200);">
                            <td style="padding: 1rem;">#<?= $order['id'] ?></td>
                            <td style="padding: 1rem;"><?= date('d/m/Y', strtotime($order['created_at'])) ?></td>
                            <td style="padding: 1rem;"><?= $order['items_count'] ?> article(s)</td>
                            <td style="padding: 1rem; font-weight: 600;"><?= formatPrice($order['total_amount']) ?></td>
                            <td style="padding: 1rem;">
                                <?php
                                $status_colors = [
                                    'pending' => 'warning',
                                    'paid' => 'success',
                                    'shipped' => 'primary',
                                    'delivered' => 'success',
                                    'cancelled' => 'error'
                                ];
                                $status_labels = [
                                    'pending' => 'En attente',
                                    'paid' => 'Payée',
                                    'shipped' => 'Expédiée',
                                    'delivered' => 'Livrée',
                                    'cancelled' => 'Annulée'
                                ];
                                ?>
                                <span style="background: var(--<?= $status_colors[$order['status']] ?>); color: white; padding: 0.25rem 0.75rem; border-radius: var(--radius-full); font-size: 0.8rem;">
                                    <?= $status_labels[$order['status']] ?>
                                </span>
                            </td>
                            <td style="padding: 1rem;">
                                <a href="order-detail.php?id=<?= $order['id'] ?>" style="color: var(--primary);">Voir</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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
                    <p>📞 +241 07 46 34 38</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> LBS SHOP. Tous droits réservés.</p>
            </div>
        </div>
    </footer>
</body>
</html>
