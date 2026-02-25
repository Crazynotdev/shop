<?php
require 'includes/config.php';

if(!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Récupérer les infos actuelles
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    
    // Gestion de l'avatar
    if(isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        if(!in_array($_FILES['avatar']['type'], $allowed_types)) {
            $error = "Type de fichier non autorisé (JPEG, PNG, WEBP uniquement)";
        } elseif($_FILES['avatar']['size'] > $max_size) {
            $error = "Fichier trop volumineux (max 2MB)";
        } else {
            // Créer le dossier s'il n'existe pas
            if(!is_dir('uploads/profiles')) {
                mkdir('uploads/profiles', 0777, true);
            }
            
            // Supprimer l'ancien avatar
            if(!empty($user['avatar']) && file_exists('uploads/profiles/' . $user['avatar'])) {
                unlink('uploads/profiles/' . $user['avatar']);
            }
            
            // Upload du nouveau
            $extension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
            $filename = 'user_' . $user_id . '_' . time() . '.' . $extension;
            
            if(move_uploaded_file($_FILES['avatar']['tmp_name'], 'uploads/profiles/' . $filename)) {
                $stmt = $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
                $stmt->execute([$filename, $user_id]);
            }
        }
    }
    
    if(empty($error)) {
        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ?, address = ? WHERE id = ?");
        if($stmt->execute([$full_name, $phone, $address, $user_id])) {
            $_SESSION['success'] = "Profil mis à jour avec succès";
            redirect('account.php');
        } else {
            $error = "Erreur lors de la mise à jour";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier mon profil - LBS SHOP</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <a href="index.php" class="logo">LBS<span>SHOP</span></a>
            <div class="nav-links">
                <a href="shop.php" class="nav-link">Boutique</a>
                <a href="cart.php" class="nav-link">🛒 Panier</a>
                <a href="account.php" class="nav-link">← Retour au profil</a>
            </div>
        </div>
    </nav>

    <div class="form-container">
        <h1 class="form-title">Modifier mon profil</h1>
        
        <?php if($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <!-- Avatar -->
            <div class="avatar-upload">
                <?php if(!empty($user['avatar'])): ?>
                    <img src="uploads/profiles/<?= $user['avatar'] ?>" alt="Avatar" class="avatar-preview" id="avatarPreview">
                <?php else: ?>
                    <img src="assets/images/default-avatar.png" alt="Avatar" class="avatar-preview" id="avatarPreview">
                <?php endif; ?>
                
                <input type="file" id="avatar" name="avatar" accept="image/jpeg,image/png,image/webp">
                <label for="avatar">📸 Changer ma photo</label>
            </div>

            <div class="form-group">
                <label for="full_name">Nom complet</label>
                <input type="text" id="full_name" name="full_name" class="form-control" 
                       value="<?= sanitize($user['full_name'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" class="form-control" value="<?= sanitize($user['email']) ?>" readonly disabled>
                <small style="color: var(--gray-500);">L'email ne peut pas être modifié</small>
            </div>

            <div class="form-group">
                <label for="phone">Téléphone</label>
                <input type="tel" id="phone" name="phone" class="form-control" 
                       value="<?= sanitize($user['phone'] ?? '') ?>" placeholder="+241 XX XXX XXXX">
            </div>

            <div class="form-group">
                <label for="address">Adresse</label>
                <textarea id="address" name="address" class="form-control" rows="3" 
                          placeholder="Votre adresse complète"><?= sanitize($user['address'] ?? '') ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary btn-block">💾 Enregistrer les modifications</button>
        </form>
    </div>

    <script>
        // Aperçu de l'avatar avant upload
        document.getElementById('avatar').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if(file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('avatarPreview').src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>
