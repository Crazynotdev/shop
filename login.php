<?php
require_once __DIR__ . '/includes/config.php';

if(isLoggedIn()) {
    redirect('index.php');
}

$error = '';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if(empty($username) || empty($password)) {
        $error = "Veuillez remplir tous les champs";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        
        if($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'] ?: $user['username'];
            $_SESSION['user_role'] = $user['role'];
            
            // Redirection vers la page demandée ou accueil
            $redirect = $_GET['redirect'] ?? 'index.php';
            redirect($redirect);
        } else {
            $error = "Email/Utilisateur ou mot de passe incorrect";
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
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="auth-page" style="background: linear-gradient(135deg, #0f172a, #1e293b);">
    <div class="form-container" style="max-width: 450px;">
        <div style="text-align: center; margin-bottom: 2rem;">
            <a href="index.php" class="logo" style="font-size: 2.5rem;">LBS<span>SHOP</span></a>
        </div>
        
        <h1 class="form-title">Connexion</h1>
        
        <?php if($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="username">Nom d'utilisateur ou email</label>
                <input type="text" id="username" name="username" class="form-control" 
                       value="<?= sanitize($_POST['username'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block">Se connecter</button>
        </form>

        <div style="text-align: center; margin-top: 2rem;">
            <p>Pas encore de compte ? <a href="register.php" style="color: var(--primary); font-weight: 600;">S'inscrire</a></p>
            <p style="margin-top: 0.5rem;"><a href="index.php" style="color: var(--gray-500);">← Retour à l'accueil</a></p>
        </div>
    </div>
</body>
</html>
