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

// Filtres
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Construction requête
$sql = "SELECT o.*, u.full_name, u.email, u.phone FROM orders o JOIN users u ON o.user_id = u.id";
$count_sql = "SELECT COUNT(*) FROM orders";
$where = [];
$params = [];

if($status_filter) {
    $where[] = "o.status = ?";
    $params[] = $status_filter;
}

if($search) {
    $where[] = "(u.full_name ILIKE ? OR u.email ILIKE ? OR o.id::text ILIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

if($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
    $count_sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY o.created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

// Compter total
$stmt = $pdo->prepare($count_sql);
$stmt->execute(array_slice($params, 0, count($params)-2));
$total_orders = $stmt->fetchColumn();
$total_pages = ceil($total_orders / $limit);

// Récupérer commandes
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Statistiques des statuts
$stats = $pdo->query("
    SELECT status, COUNT(*) as count 
    FROM orders 
    GROUP BY status
")->fetchAll();

$status_counts = [];
foreach($stats as $stat) {
    $status_counts[$stat['status']] = $stat['count'];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des commandes - Administration</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Reprendre les styles de products.php */
        .admin-wrapper { display: flex; min-height: 100vh; }
        .admin-sidebar { width: 280px; background: var(--gray-900); color: white; position: fixed; height: 100vh; overflow-y: auto; }
        .admin-main { flex: 1; margin-left: 280px; background: var(--gray-100); }
        .admin-header { background: white; padding: 1rem 2rem; border-bottom: 1px solid var(--gray-200); display: flex; justify-content: space-between; align-items: center; }
        .admin-content { padding: 2rem; }
        
        .filter-bar {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        
        .filter-btn {
            padding: 0.5rem 1.5rem;
            border-radius: var(--radius-full);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s;
            background: white;
            color: var(--gray-700);
            border: 2px solid var(--gray-200);
        }
        
        .filter-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .filter-btn .count {
            background: rgba(0,0,0,0.1);
            padding: 0.2rem 0.5rem;
            border-radius: var(--radius-full);
            margin-left: 0.5rem;
            font-size: 0.8rem;
        }
        
        .filter-btn.active .count {
            background: rgba(255,255,255,0.2);
        }
        
        .orders-table {
            background: white;
            border-radius: var(--radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            overflow-x: auto;
        }
        
        .orders-table table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .orders-table th {
            text-align: left;
            padding: 1rem;
            background: var(--gray-100);
            color: var(--gray-700);
            font-weight: 600;
        }
        
        .orders-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--gray-200);
        }
        
        .orders-table tr:hover {
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
        
        .order-total {
            font-weight: 700;
            color: var(--primary);
        }
        
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
        
        .search-box {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .search-box input {
            flex: 1;
            padding: 0.75rem 1rem;
            border: 2px solid var(--gray-200);
            border-radius: var(--radius);
            font-size: 0.95rem;
            max-width: 300px;
        }
        
        .search-box button {
            padding: 0.75rem 1.5rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: var(--radius);
            cursor: pointer;
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
                <a href="orders.php" class="active">
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
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="admin-main">
            <div class="admin-header">
                <h2>Gestion des commandes</h2>
                <div>👋 <?= sanitize($_SESSION['user_name']) ?></div>
            </div>
            
            <div class="admin-content">
                <!-- Filtres par statut -->
                <div class="filter-bar">
                    <a href="orders.php" class="filter-btn <?= !$status_filter ? 'active' : '' ?>">
                        Toutes <span class="count"><?= array_sum($status_counts) ?></span>
                    </a>
                    <a href="orders.php?status=pending" class="filter-btn <?= $status_filter == 'pending' ? 'active' : '' ?>">
                        ⏳ En attente <span class="count"><?= $status_counts['pending'] ?? 0 ?></span>
                    </a>
                    <a href="orders.php?status=paid" class="filter-btn <?= $status_filter == 'paid' ? 'active' : '' ?>">
                        💳 Payées <span class="count"><?= $status_counts['paid'] ?? 0 ?></span>
                    </a>
                    <a href="orders.php?status=shipped" class="filter-btn <?= $status_filter == 'shipped' ? 'active' : '' ?>">
                        🚚 Expédiées <span class="count"><?= $status_counts['shipped'] ?? 0 ?></span>
                    </a>
                    <a href="orders.php?status=delivered" class="filter-btn <?= $status_filter == 'delivered' ? 'active' : '' ?>">
                        ✅ Livrées <span class="count"><?= $status_counts['delivered'] ?? 0 ?></span>
                    </a>
                    <a href="orders.php?status=cancelled" class="filter-btn <?= $status_filter == 'cancelled' ? 'active' : '' ?>">
                        ❌ Annulées <span class="count"><?= $status_counts['cancelled'] ?? 0 ?></span>
                    </a>
                </div>
                
                <!-- Recherche -->
                <div class="search-box">
                    <form method="GET" style="display: flex; gap: 0.5rem; width: 100%;">
                        <?php if($status_filter): ?>
                            <input type="hidden" name="status" value="<?= $status_filter ?>">
                        <?php endif; ?>
                        <input type="text" name="search" placeholder="Rechercher par client, email ou n° commande..." value="<?= sanitize($search) ?>">
                        <button type="submit">🔍</button>
                    </form>
                </div>
                
                <!-- Message de succès -->
                <?php if(isset($_SESSION['success'])): ?>
                    <div class="alert alert-success" style="margin-bottom: 1rem;">
                        <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Tableau des commandes -->
                <div class="orders-table">
                    <table>
                        <thead>
                            <tr>
                                <th>N° Commande</th>
                                <th>Client</th>
                                <th>Contact</th>
                                <th>Total</th>
                                <th>Date</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($orders as $order): ?>
                            <tr>
                                <td><strong>#<?= $order['id'] ?></strong></td>
                                <td>
                                    <strong><?= sanitize($order['full_name'] ?: $order['email']) ?></strong>
                                </td>
                                <td>
                                    <?= sanitize($order['email']) ?><br>
                                    <?= sanitize($order['shipping_phone'] ?: $order['phone']) ?>
                                </td>
                                <td class="order-total"><?= formatPrice($order['total_amount']) ?></td>
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
                                    <div style="display: flex; gap: 0.3rem;">
                                        <a href="order-detail.php?id=<?= $order['id'] ?>" class="action-btn btn-view">👁️</a>
                                        <a href="order-edit.php?id=<?= $order['id'] ?>" class="action-btn btn-edit">✏️</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if(empty($orders)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 3rem; color: var(--gray-500);">
                                    Aucune commande trouvée
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
                        <a href="?<?= $status_filter ? 'status='.$status_filter.'&' : '' ?><?= $search ? 'search='.urlencode($search).'&' : '' ?>page=<?= $i ?>" 
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
