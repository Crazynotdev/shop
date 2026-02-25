<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if(!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    redirect('../login.php');
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Recherche
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Construction requête
$sql = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id";
$count_sql = "SELECT COUNT(*) FROM products";
$params = [];

if($search) {
    $sql .= " WHERE p.name ILIKE ? OR p.description ILIKE ?";
    $count_sql .= " WHERE name ILIKE ? OR description ILIKE ?";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
}

$sql .= " ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

// Compter total
$stmt = $pdo->prepare($count_sql);
$stmt->execute($search ? [$search_term, $search_term] : []);
$total_products = $stmt->fetchColumn();
$total_pages = ceil($total_products / $limit);

// Récupérer produits
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Catégories pour le filtre
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des produits - Administration</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Styles identiques au dashboard */
        .admin-wrapper { display: flex; min-height: 100vh; }
        .admin-sidebar { width: 280px; background: var(--gray-900); color: white; position: fixed; height: 100vh; overflow-y: auto; }
        .admin-main { flex: 1; margin-left: 280px; background: var(--gray-100); }
        .admin-header { background: white; padding: 1rem 2rem; border-bottom: 1px solid var(--gray-200); display: flex; justify-content: space-between; align-items: center; }
        .admin-content { padding: 2rem; }
        .sidebar-header { padding: 2rem; border-bottom: 1px solid rgba(255,255,255,0.1); text-align: center; }
        .sidebar-header h2 { color: white; font-size: 1.8rem; }
        .sidebar-header span { color: var(--primary); }
        .sidebar-menu a { display: flex; align-items: center; gap: 1rem; padding: 1rem 2rem; color: var(--gray-400); text-decoration: none; transition: all 0.3s; border-left: 3px solid transparent; }
        .sidebar-menu a:hover, .sidebar-menu a.active { background: rgba(249,115,22,0.1); color: var(--primary); border-left-color: var(--primary); }
        
        .action-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .search-box {
            display: flex;
            gap: 0.5rem;
            flex: 1;
            max-width: 400px;
        }
        
        .search-box input {
            flex: 1;
            padding: 0.75rem 1rem;
            border: 2px solid var(--gray-200);
            border-radius: var(--radius);
            font-size: 0.95rem;
        }
        
        .search-box button {
            padding: 0.75rem 1.5rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: var(--radius);
            cursor: pointer;
        }
        
        .product-table {
            background: white;
            border-radius: var(--radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            overflow-x: auto;
        }
        
        .product-table table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .product-table th {
            text-align: left;
            padding: 1rem;
            background: var(--gray-100);
            color: var(--gray-700);
            font-weight: 600;
        }
        
        .product-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--gray-200);
        }
        
        .product-table tr:hover {
            background: var(--gray-50);
        }
        
        .product-image-small {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: var(--radius-sm);
            border: 2px solid var(--gray-200);
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: var(--radius-full);
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-active {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-draft {
            background: #f3f4f6;
            color: #4b5563;
        }
        
        .status-archived {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .stock-low {
            color: var(--warning);
            font-weight: 600;
        }
        
        .stock-out {
            color: var(--danger);
            font-weight: 600;
        }
        
        .action-group {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-action {
            padding: 0.4rem 1rem;
            border-radius: var(--radius);
            text-decoration: none;
            font-size: 0.85rem;
            transition: all 0.2s;
        }
        
        .btn-edit {
            background: var(--primary);
            color: white;
        }
        
        .btn-view {
            background: var(--gray-200);
            color: var(--gray-700);
        }
        
        .btn-delete {
            background: var(--danger);
            color: white;
        }
        
        .btn-add {
            background: var(--success);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: var(--radius);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }
        
        .pagination a {
            padding: 0.5rem 1rem;
            border: 2px solid var(--gray-200);
            border-radius: var(--radius);
            text-decoration: none;
            color: var(--gray-700);
            transition: all 0.2s;
        }
        
        .pagination a:hover,
        .pagination a.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <div class="admin-sidebar">
            <div class="sidebar-header">
                <h2>LBS<span>SHOP</span></h2>
                <p style="color: var(--gray-500);">Administration</p>
            </div>
            
            <div class="sidebar-menu">
                <a href="dashboard.php">
                    <span>📊</span> Dashboard
                </a>
                <a href="orders.php">
                    <span>📦</span> Commandes
                </a>
                <a href="products.php" class="active">
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
                <h2>Gestion des produits</h2>
                <div>👋 <?= sanitize($_SESSION['user_name']) ?></div>
            </div>
            
            <div class="admin-content">
                <!-- Barre d'actions -->
                <div class="action-bar">
                    <div class="search-box">
                        <form method="GET" style="display: flex; gap: 0.5rem; width: 100%;">
                            <input type="text" name="search" placeholder="Rechercher un produit..." value="<?= sanitize($search) ?>">
                            <button type="submit">🔍</button>
                        </form>
                    </div>
                    
                    <a href="product-add.php" class="btn-add">
                        <span>➕</span> Ajouter un produit
                    </a>
                </div>
                
                <!-- Message de succès -->
                <?php if(isset($_SESSION['success'])): ?>
                    <div class="alert alert-success" style="margin-bottom: 1rem;">
                        <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Tableau des produits -->
                <div class="product-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Produit</th>
                                <th>Catégorie</th>
                                <th>Prix</th>
                                <th>Stock</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($products as $product): ?>
                            <tr>
                                <td>
                                    <?php if($product['image']): ?>
                                        <img src="../uploads/products/<?= $product['image'] ?>" class="product-image-small">
                                    <?php else: ?>
                                        <div style="width: 60px; height: 60px; background: var(--gray-200); border-radius: var(--radius-sm);"></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?= sanitize($product['name']) ?></strong><br>
                                    <small style="color: var(--gray-500);"><?= sanitize($product['description_short'] ?: substr($product['description'] ?? '', 0, 50)) ?>...</small>
                                </td>
                                <td><?= sanitize($product['category_name'] ?? 'Non catégorisé') ?></td>
                                <td>
                                    <strong><?= formatPrice($product['price']) ?></strong>
                                    <?php if($product['old_price'] > 0): ?>
                                        <br><small style="color: var(--gray-400); text-decoration: line-through;"><?= formatPrice($product['old_price']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($product['stock'] == 0): ?>
                                        <span class="stock-out">Rupture</span>
                                    <?php elseif($product['stock'] < 5): ?>
                                        <span class="stock-low"><?= $product['stock'] ?> unités</span>
                                    <?php else: ?>
                                        <?= $product['stock'] ?> unités
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?= $product['status'] ?>">
                                        <?= $product['status'] == 'active' ? 'Actif' : ($product['status'] == 'draft' ? 'Brouillon' : 'Archivé') ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-group">
                                        <a href="product-edit.php?id=<?= $product['id'] ?>" class="btn-action btn-edit">✏️</a>
                                        <a href="../product.php?id=<?= $product['id'] ?>" target="_blank" class="btn-action btn-view">👁️</a>
                                        <a href="product-delete.php?id=<?= $product['id'] ?>" class="btn-action btn-delete" onclick="return confirm('Supprimer ce produit ?')">🗑️</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if(empty($products)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 3rem; color: var(--gray-500);">
                                    Aucun produit trouvé
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if($total_pages > 1): ?>
                <div class="pagination">
                    <?php for($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?= $i ?><?= $search ? '&search='.urlencode($search) : '' ?>" 
                           class="<?= $i == $page ? 'active' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
