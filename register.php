<?php
require 'includes/config.php';

if(isLoggedIn()) {
    redirect('index.php');
}

$errors = [];
$success = '';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validations
    if(empty($username)) $errors[] = "Nom d'utilisateur requis";
    elseif(strlen($username) < 3) $errors[] = "Nom d'utilisateur trop court (min 3 caractères)";
    elseif(!preg_match('/^[a-zA-Z0-9_]+$/', $username)) $errors[] = "Caractères autorisés : lettres, chiffres, _";
    
    if(empty($email)) $errors[] = "Email requis";
    elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email invalide";
    
    if(empty($password)) $errors[] = "Mot de passe requis";
    elseif(strlen($password) < 6) $errors[] = "Mot de passe trop court (min 6 caractères)";
    
    if($password !== $confirm_password) $errors[] = "Les mots de passe ne correspondent pas";
    
    // Vérifier si l'utilisateur existe
    if(empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if($stmt->fetch()) {
            $errors[] = "Nom d'utilisateur ou email déjà utilisé";
        }
    }
    
    // Créer le compte
    if(empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, full_name, password) VALUES (?, ?, ?, ?)");
        
        if($stmt->execute([$username, $email, $full_name, $hashed_password])) {
            $_SESSION['success'] = "Compte créé avec succès ! Vous pouvez maintenant vous connecter.";
            redirect('login.php');
        } else {
            $errors[] = "Erreur lors de la création du compte";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - LBS SHOP</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="auth-page" style="background: linear-gradient(135deg, #0f172a, #1e293b);">
    <div class="form-container" style="max-width: 500px;">
        <div style="text-align: center; margin-bottom: 2rem;">
            <a href="index.php" class="logo" style="font-size: 2.5rem;">LBS<span>SHOP</span></a>
        </div>
        
        <h1 class="form-title">Créer un compte</h1>
        
        <?php if(!empty($errors)): ?>
            <div class="alert alert-error">
                <?php foreach($errors as $error): ?>
                    <div>• <?= $error ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="username">Nom d'utilisateur *</label>
                <input type="text" id="username" name="username" class="form-control" 
                       value="<?= sanitize($_POST['username'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" class="form-control" 
                       value="<?= sanitize($_POST['email'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="full_name">Nom complet</label>
                <input type="text" id="full_name" name="full_name" class="form-control" 
                       value="<?= sanitize($_POST['full_name'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="password">Mot de passe *</label>
                <input type="password" id="password" name="password" class="form-control" required>
                <small style="color: var(--gray-500);">Minimum 6 caractères</small>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirmer le mot de passe *</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Créer mon compte</button>
        </form>

        <div style="text-align: center; margin-top: 2rem;">
            <p>Déjà inscrit ? <a href="login.php" style="color: var(--primary); font-weight: 600;">Se connecter</a></p>
            <p style="margin-top: 0.5rem;"><a href="index.php" style="color: var(--gray-500);">← Retour à l'accueil</a></p>
        </div>
    </div>
</body>
</html>
