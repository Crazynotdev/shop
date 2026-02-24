<?php require 'config.php'; ?>

<?php
// Rediriger si déjà connecté
if(isLoggedIn()) {
    redirect('index.php');
}

$error = '';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if(empty($username) || empty($password)) {
        $error = 'Veuillez remplir tous les champs';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        
        if($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'] ?: $user['username'];
            $_SESSION['user_role'] = $user['role'];
            
            redirect('index.php');
        } else {
            $error = 'Identifiants incorrects';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - LBS SHOP</title>
    <link rel="stylesheet" href="./css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <img src="https://files.catbox.moe/e0a61i.jpg" alt="LBS SHOP" class="auth-logo">
                <h1>Connexion</h1>
                <p>Accédez à votre espace personnel</p>
            </div>
            
            <?php if($error): ?>
                <div class="alert alert-error"><?= sanitize($error) ?></div>
            <?php endif; ?>
            
            <form method="POST" class="auth-form">
                <div class="form-group">
                    <label for="username">Nom d'utilisateur ou email</label>
                    <input type="text" id="username" name="username" class="form-control" 
                           value="<?= sanitize($_POST['username'] ?? '') ?>" required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Se connecter</button>
            </form>
            
            <div class="auth-footer">
                <p>Pas encore de compte ? <a href="register.php">S'inscrire</a></p>
                <p class="mt-2"><a href="index.php">← Retour à l'accueil</a></p>
            </div>
        </div>
    </div>
</body>
</html>
