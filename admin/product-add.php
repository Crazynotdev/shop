<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

if(!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    redirect('../login.php');
}

$error = '';
$success = '';

// Récupérer les catégories
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $description = trim($_POST['description'] ?? '');
    $description_short = trim($_POST['description_short'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $old_price = (float)($_POST['old_price'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);
    $status = $_POST['status'] ?? 'active';
    
    // Validations
    if(empty($name)) $error = "Le nom du produit est requis";
    elseif($price <= 0) $error = "Le prix doit être supérieur à 0";
    
    // Générer le slug
    $slug = generateSlug($name);
    
    // Vérifier si le slug existe déjà
    $stmt = $pdo->prepare("SELECT id FROM products WHERE slug = ?");
    $stmt->execute([$slug]);
    if($stmt->fetch()) {
        $slug .= '-' . time();
    }
    
    // Upload de l'image
    $image = '';
    if(isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload = uploadFile($_FILES['image'], '../uploads/products');
        if(isset($upload['success'])) {
            $image = $upload['filename'];
        } else {
            $error = $upload['error'];
        }
    }
    
    if(empty($error)) {
        $stmt = $pdo->prepare("
            INSERT INTO products (name, slug, category_id, description, description_short, price, old_price, stock, image, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        if($stmt->execute([$name, $slug, $category_id, $description, $description_short, $price, $old_price, $stock, $image, $status])) {
            $_SESSION['success'] = "Produit ajouté avec succès";
            redirect('products.php');
        } else {
            $error = "Erreur lors de l'ajout du produit";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un produit - Administration</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Reprendre les styles du dashboard */
        .admin-wrapper { display: flex; min-height: 100vh; }
        .admin-sidebar { width: 280px; background: var(--gray-900); color: white; position: fixed; height: 100vh; overflow-y: auto; }
        .admin-main { flex: 1; margin-left: 280px; background: var(--gray-100); }
        .admin-header { background: white; padding: 1rem 2rem; border-bottom: 1px solid var(--gray-200); }
        .admin-content { padding: 2rem; }
        
        .form-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: 2rem;
            box-shadow: var(--shadow);
            max-width: 800px;
            margin: 0 auto;
        }
        
        .form-title {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 2rem;
            color: var(--gray-800);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--gray-700);
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid var(--gray-200);
            border-radius: var(--radius);
            font-size: 1rem;
            transition: all 0.2s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(249,115,22,0.1);
        }
        
        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }
        
        .image-preview {
            margin-top: 1rem;
            max-width: 200px;
            border-radius: var(--radius);
            border: 2px solid var(--gray-200);
            display: none;
        }
        
        .image-preview.show {
            display: block;
        }
        
        .btn-submit {
            background: var(--primary);
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: var(--radius);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-submit:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }
        
        .btn-cancel {
            background: var(--gray-200);
            color: var(--gray-700);
            padding: 1rem 2rem;
            border-radius: var(--radius);
            text-decoration: none;
            margin-left: 1rem;
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
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="admin-main">
            <div class="admin-header">
                <h2>Ajouter un produit</h2>
            </div>
            
            <div class="admin-content">
                <div class="form-card">
                    <h1 class="form-title">➕ Nouveau produit</h1>
                    
                    <?php if($error): ?>
                        <div class="alert alert-error" style="margin-bottom: 1.5rem;"><?= $error ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="name">Nom du produit *</label>
                            <input type="text" id="name" name="name" class="form-control" 
                                   value="<?= sanitize($_POST['name'] ?? '') ?>" required>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="category_id">Catégorie</label>
                                <select id="category_id" name="category_id" class="form-control">
                                    <option value="">-- Sélectionner --</option>
                                    <?php foreach($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>" <?= (isset($_POST['category_id']) && $_POST['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                            <?= $cat['icon'] ?> <?= sanitize($cat['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="status">Statut</label>
                                <select id="status" name="status" class="form-control">
                                    <option value="active" <?= (isset($_POST['status']) && $_POST['status'] == 'active') ? 'selected' : '' ?>>Actif</option>
                                    <option value="draft" <?= (isset($_POST['status']) && $_POST['status'] == 'draft') ? 'selected' : '' ?>>Brouillon</option>
                                    <option value="archived" <?= (isset($_POST['status']) && $_POST['status'] == 'archived') ? 'selected' : '' ?>>Archivé</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="description_short">Description courte</label>
                            <input type="text" id="description_short" name="description_short" class="form-control" 
                                   value="<?= sanitize($_POST['description_short'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description détaillée</label>
                            <textarea id="description" name="description" class="form-control"><?= sanitize($_POST['description'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="price">Prix (FCFA) *</label>
                                <input type="number" id="price" name="price" class="form-control" 
                                       value="<?= $_POST['price'] ?? '' ?>" min="0" step="100" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="old_price">Ancien prix (FCFA)</label>
                                <input type="number" id="old_price" name="old_price" class="form-control" 
                                       value="<?= $_POST['old_price'] ?? '' ?>" min="0" step="100">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="stock">Stock</label>
                                <input type="number" id="stock" name="stock" class="form-control" 
                                       value="<?= $_POST['stock'] ?? 0 ?>" min="0">
                            </div>
                            
                            <div class="form-group">
                                <label for="image">Image du produit</label>
                                <input type="file" id="image" name="image" class="form-control" accept="image/jpeg,image/png,image/webp" onchange="previewImage(this)">
                                <img id="imagePreview" class="image-preview">
                            </div>
                        </div>
                        
                        <div style="display: flex; align-items: center; margin-top: 2rem;">
                            <button type="submit" class="btn-submit">💾 Enregistrer le produit</button>
                            <a href="products.php" class="btn-cancel">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            if(input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.classList.add('show');
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>
