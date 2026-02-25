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
$sql = "SELECT * FROM users WHERE role = 'user'";
$count_sql = "SELECT COUNT(*) FROM users WHERE role = 'user'";
$params = [];

if($search) {
    $sql .= " AND (username ILIKE ? OR email ILIKE ? OR full_name ILIKE ? OR phone ILIKE ?)";
    $count_sql .= " AND (username ILIKE ? OR email ILIKE ? OR full_name ILIKE ? OR phone ILIKE ?)";
    $search_term = "%$search%";
    $params = array_fill(0, 4, $search_term);
}

$sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

// Compter total
$stmt = $pdo->prepare($count_sql);
$stmt->execute($search ? array_fill(0, 4, "%$search%") : []);
$total_users = $stmt->fetchColumn();
$total_pages = ceil($total_users / $limit);

// Récupérer utilisateurs
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des utilisateurs - Administration</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Mêmes styles que products.php */
        .admin-wrapper { display: flex; min-height: 100vh; }
        .admin-sidebar { width: 280px; background: var(--gray-900); color: white; position: fixed; height: 100vh; overflow-y: auto; }
        .admin-main { flex: 1; margin-left: 280px; background: var(--gray-100); }
        .admin-header { background: white; padding: 1rem 2rem; border-bottom: 1px solid var(--gray-200); display: flex; justify-content: space-between; align-items: center; }
        .admin-content { padding: 2rem; }
        
        .search-box {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 2rem;
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
        
        .users-table {
            background: white;
            border-radius: var(--radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            overflow-x: auto;
        }
        
        .users-table table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .users-table th {
            text-align: left;
            padding: 1rem;
            background: var(--gray-100);
            color: var(--gray-700);
            font-weight: 600;
        }
        
        .users-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--gray-200);
        }
        
        .users-table tr:hover {
            background: var(--gray-50);
        }
        
        .user-avatar-table {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary);
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-name {
            font-weight: 600;
            color: var(--gray-800);
        }
        
        .user-email {
            font-size: 0.85rem;
            color: var(--gray-500);
        }
        
        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: var(--radius-full);
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .badge-admin {
            background: var(--primary);
            color: white;
        }
        
        .badge-user {
            background: var(--gray-200);
            color: var(--gray-700);
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
                <a href="orders.php">
                    <span>📦</span> Commandes
                </a>
                <a href="products.php">
                    <span>🏷️</span> Produits
                </a>
                <a href="categories.php">
                    <span>📂</span> Catégories
                </a>
                <a href="users.php" class="active">
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
                <h2>Gestion des utilisateurs</h2>
                <div>👋 <?= sanitize($_SESSION['user_name']) ?></div>
            </div>
            
            <div class="admin-content">
                <!-- Recherche -->
                <div class="search-box">
                    <form method="GET" style="display: flex; gap: 0.5rem; width: 100%;">
                        <input type="text" name="search" placeholder="Rechercher un utilisateur..." value="<?= sanitize($search) ?>">
                        <button type="submit">🔍</button>
                    </form>
                </div>
                
                <!-- Message de succès -->
                <?php if(isset($_SESSION['success'])): ?>
                    <div class="alert alert-success" style="margin-bottom: 1rem;">
                        <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Tableau des utilisateurs -->
                <div class="users-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Utilisateur</th>
                                <th>Contact</th>
                                <th>Inscription</th>
                                <th>Commandes</th>
                                <th>Total dépensé</th>
                                <th>Rôle</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($users as $user): 
                                // Compter commandes
                                $stmt = $pdo->prepare("SELECT COUNT(*), COALESCE(SUM(total_amount), 0) FROM orders WHERE user_id = ?");
                                $stmt->execute([$user['id']]);
                                list($order_count, $total_spent) = $stmt->fetch();
                            ?>
                            <tr>
                                <td>
                                    <div class="user-info">
                                        <?php if(!empty($user['avatar'])): ?>
                                            <img src="../uploads/profiles/<?= $user['avatar'] ?>" class="user-avatar-table">
                                        <?php else: ?>
                                            <div style="width: 45px; height: 45px; border-radius: 50%; background: var(--gray-200); display: flex; align-items: center; justify-content: center; color: var(--gray-500);">
                                                👤
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <div class="user-name"><?= sanitize($user['full_name'] ?: $user['username']) ?></div>
                                            <div class="user-email">@<?= sanitize($user['username']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?= sanitize($user['email']) ?><br>
                                    <?= sanitize($user['phone'] ?: 'Non renseigné') ?>
                                </td>
                                <td><?= date('d/m/Y', strtotime($user['created_at'])) ?></td>
                                <td style="text-align: center;"><?= $order_count ?></td>
                                <td><strong><?= formatPrice($total_spent) ?></strong></td>
                                <td>
                                    <span class="badge badge-<?= $user['role'] ?>">
                                        <?= $user['role'] == 'admin' ? 'Admin' : 'Client' ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 0.3rem;">
                                        <a href="user-view.php?id=<?= $user['id'] ?>" class="action-btn btn-view">👁️</a>
                                        <?php if($user['role'] !== 'admin'): ?>
                                            <a href="user-edit.php?id=<?= $user['id'] ?>" class="action-btn btn-edit">✏️</a>
                                            <a href="user-delete.php?id=<?= $user['id'] ?>" class="action-btn btn-delete" onclick="return confirm('Supprimer cet utilisateur ?')">🗑️</a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if(empty($users)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 3rem; color: var(--gray-500);">
                                    Aucun utilisateur trouvé
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
                        <a href="?<?= $search ? 'search='.urlencode($search).'&' : '' ?>page=<?= $i ?>" 
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
