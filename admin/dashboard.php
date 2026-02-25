<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Vérifier si l'utilisateur est admin
if(!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    $_SESSION['error'] = "Accès non autorisé";
    redirect('../login.php');
}

// Statistiques
$stats = [];

// Total produits
$stmt = $pdo->query("SELECT COUNT(*) FROM products WHERE status = 'active'");
$stats['total_products'] = $stmt->fetchColumn();

// Total commandes
$stmt = $pdo->query("SELECT COUNT(*) FROM orders");
$stats['total_orders'] = $stmt->fetchColumn();

// Commandes en attente
$stmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'");
$stats['pending_orders'] = $stmt->fetchColumn();

// Chiffre d'affaires du mois
$stmt = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE EXTRACT(MONTH FROM created_at) = EXTRACT(MONTH FROM CURRENT_DATE) AND EXTRACT(YEAR FROM created_at) = EXTRACT(YEAR FROM CURRENT_DATE)");
$stats['month_revenue'] = $stmt->fetchColumn();

// Total utilisateurs
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'");
$stats['total_users'] = $stmt->fetchColumn();

// Nouveaux utilisateurs (mois)
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user' AND EXTRACT(MONTH FROM created_at) = EXTRACT(MONTH FROM CURRENT_DATE)");
$stats['new_users'] = $stmt->fetchColumn();

// Stock faible (< 5)
$stmt = $pdo->query("SELECT COUNT(*) FROM products WHERE stock < 5 AND stock > 0");
$stats['low_stock'] = $stmt->fetchColumn();

// Dernières commandes
$stmt = $pdo->query("
    SELECT o.*, u.full_name, u.email 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    ORDER BY o.created_at DESC 
    LIMIT 10
");
$recent_orders = $stmt->fetchAll();

// Produits les plus vendus
$stmt = $pdo->query("
    SELECT p.*, SUM(oi.quantity) as total_sold 
    FROM products p 
    LEFT JOIN order_items oi ON p.id = oi.product_id 
    GROUP BY p.id 
    ORDER BY total_sold DESC NULLS LAST 
    LIMIT 5
");
$top_products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Administration LBS SHOP</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }
        
        .admin-sidebar {
            width: 280px;
            background: var(--gray-900);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 100;
        }
        
        .admin-main {
            flex: 1;
            margin-left: 280px;
            background: var(--gray-100);
            min-height: 100vh;
        }
        
        .admin-header {
            background: white;
            padding: 1rem 2rem;
            border-bottom: 1px solid var(--gray-200);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 90;
        }
        
        .admin-content {
            padding: 2rem;
        }
        
        .sidebar-header {
            padding: 2rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }
        
        .sidebar-header h2 {
            color: white;
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }
        
        .sidebar-header span {
            color: var(--primary);
        }
        
        .sidebar-menu {
            padding: 1.5rem 0;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 2rem;
            color: var(--gray-400);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }
        
        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: rgba(249, 115, 22, 0.1);
            color: var(--primary);
            border-left-color: var(--primary);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            border-radius: var(--radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.3s ease;
            border: 1px solid var(--gray-200);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary);
        }
        
        .stat-info h3 {
            color: var(--gray-500);
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        
        .stat-info .stat-value {
            font-size: 2.2rem;
            font-weight: 700;
            color: var(--gray-800);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.8rem;
            box-shadow: 0 10px 20px rgba(249, 115, 22, 0.2);
        }
        
        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 2rem 0 1.5rem;
            color: var(--gray-800);
        }
        
        .table-responsive {
            background: white;
            border-radius: var(--radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            overflow-x: auto;
            border: 1px solid var(--gray-200);
        }
        
        .admin-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .admin-table th {
            text-align: left;
            padding: 1rem;
            background: var(--gray-100);
            color: var(--gray-700);
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .admin-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--gray-200);
        }
        
        .admin-table tr:hover {
            background: var(--gray-50);
        }
        
        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: var(--radius-full);
            font-size: 0.8rem;
            font-weight: 500;
            display: inline-block;
        }
        
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-paid { background: #d1fae5; color: #065f46; }
        .badge-shipped { background: #dbeafe; color: #1e40af; }
        .badge-delivered { background: #dcfce7; color: #166534; }
        .badge-cancelled { background: #fee2e2; color: #991b1b; }
        
        .action-btn {
            padding: 0.4rem 1rem;
            border-radius: var(--radius);
            text-decoration: none;
            font-size: 0.85rem;
            margin: 0 0.2rem;
            display: inline-block;
        }
        
        .btn-view { background: var(--gray-200); color: var(--gray-700); }
        .btn-edit { background: var(--primary); color: white; }
        .btn-delete { background: var(--danger); color: white; }
        
        .user-avatar-mini {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary);
        }
        
        @media (max-width: 768px) {
            .admin-sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            .admin-sidebar.active {
                transform: translateX(0);
            }
            .admin-main {
                margin-left: 0;
            }
            .menu-toggle {
                display: block;
                font-size: 1.5rem;
                cursor: pointer;
            }
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <div class="admin-sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2>LBS<span>SHOP</span></h2>
                <p style="color: var(--gray-500); font-size: 0.9rem;">Administration</p>
            </div>
            
            <div class="sidebar-menu">
                <a href="dashboard.php" class="active">
                    <span>📊</span> Dashboard
                </a>
                <a href="orders.php">
                    <span>📦</span> Commandes
                </a>
                <a href="products.php">
                    <span>🏷️</span> Produits
                </a>
                <a href="categories.php">
                    <span>📂</span> Catégories
                </a>
                <a href="users.php">
                    <span>👥</span> Utilisateurs
                </a>
                <a href="settings.php">
                    <span>⚙️</span> Paramètres
                </a>
                <div style="margin-top: 2rem; padding: 1rem 2rem; border-top: 1px solid rgba(255,255,255,0.1);">
                    <a href="../index.php" style="color: var(--gray-400);">
                        <span>🏠</span> Voir le site
                    </a>
                    <a href="../logout.php" style="color: var(--danger); margin-top: 0.5rem;">
                        <span>🚪</span> Déconnexion
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="admin-main">
            <div class="admin-header">
                <button class="menu-toggle" onclick="toggleSidebar()" style="display: none;">☰</button>
                <h2>Dashboard</h2>
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <span>👋 Bonjour, <?= sanitize($_SESSION['user_name']) ?></span>
                    <?php
                    $stmt = $pdo->prepare("SELECT avatar FROM users WHERE id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $admin = $stmt->fetch();
                    ?>
                    <?php if(!empty($admin['avatar'])): ?>
                        <img src="../uploads/profiles/<?= $admin['avatar'] ?>" class="user-avatar-mini">
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="admin-content">
                <!-- Statistiques -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-info">
                            <h3>Produits</h3>
                            <div class="stat-value"><?= $stats['total_products'] ?></div>
                        </div>
                        <div class="stat-icon">🏷️</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-info">
                            <h3>Commandes</h3>
                            <div class="stat-value"><?= $stats['total_orders'] ?></div>
                            <small style="color: var(--warning);"><?= $stats['pending_orders'] ?> en attente</small>
                        </div>
                        <div class="stat-icon">📦</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-info">
                            <h3>CA du mois</h3>
                            <div class="stat-value"><?= formatPrice($stats['month_revenue']) ?></div>
                        </div>
                        <div class="stat-icon">💰</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-info">
                            <h3>Utilisateurs</h3>
                            <div class="stat-value"><?= $stats['total_users'] ?></div>
                            <small style="color: var(--success);">+<?= $stats['new_users'] ?> ce mois</small>
                        </div>
                        <div class="stat-icon">👥</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-info">
                            <h3>Stock faible</h3>
                            <div class="stat-value" style="color: var(--warning);"><?= $stats['low_stock'] ?></div>
                        </div>
                        <div class="stat-icon">⚠️</div>
                    </div>
                </div>

                <!-- Dernières commandes -->
                <h3 class="section-title">📦 Dernières commandes</h3>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>N°</th>
                                <th>Client</th>
                                <th>Montant</th>
                                <th>Date</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($recent_orders as $order): ?>
                            <tr>
                                <td>#<?= $order['id'] ?></td>
                                <td>
                                    <strong><?= sanitize($order['full_name'] ?: $order['email']) ?></strong><br>
                                    <small style="color: var(--gray-500);"><?= sanitize($order['email']) ?></small>
                                </td>
                                <td><strong><?= formatPrice($order['total_amount']) ?></strong></td>
                                <td><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></td>
                                <td>
                                    <span class="badge badge-<?= $order['status'] ?>">
                                        <?php
                                        $status_labels = [
                                            'pending' => 'En attente',
                                            'paid' => 'Payée',
                                            'shipped' => 'Expédiée',
                                            'delivered' => 'Livrée',
                                            'cancelled' => 'Annulée'
                                        ];
                                        echo $status_labels[$order['status']];
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="order-detail.php?id=<?= $order['id'] ?>" class="action-btn btn-view">Voir</a>
                                    <a href="order-edit.php?id=<?= $order['id'] ?>" class="action-btn btn-edit">Modifier</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Top produits -->
                <h3 class="section-title">🔥 Produits les plus vendus</h3>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Produit</th>
                                <th>Prix</th>
                                <th>Stock</th>
                                <th>Ventes</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($top_products as $product): ?>
                            <tr>
                                <td>
                                    <?php if($product['image']): ?>
                                        <img src="../uploads/products/<?= $product['image'] ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: var(--radius-sm);">
                                    <?php else: ?>
                                        <div style="width: 50px; height: 50px; background: var(--gray-200); border-radius: var(--radius-sm);"></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?= sanitize($product['name']) ?></strong><br>
                                    <small style="color: var(--gray-500);"><?= sanitize($product['description_short'] ?: substr($product['description'], 0, 50)) ?>...</small>
                                </td>
                                <td><strong><?= formatPrice($product['price']) ?></strong></td>
                                <td>
                                    <?php if($product['stock'] < 5 && $product['stock'] > 0): ?>
                                        <span style="color: var(--warning); font-weight: 600;"><?= $product['stock'] ?></span>
                                    <?php elseif($product['stock'] == 0): ?>
                                        <span style="color: var(--danger); font-weight: 600;">Rupture</span>
                                    <?php else: ?>
                                        <?= $product['stock'] ?>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?= $product['total_sold'] ?? 0 ?></strong> vendus</td>
                                <td>
                                    <a href="product-edit.php?id=<?= $product['id'] ?>" class="action-btn btn-edit">Modifier</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }
    </script>
</body>
</html>
