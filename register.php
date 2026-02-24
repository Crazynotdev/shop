<?php require 'config.php'; ?>

<?php
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
    
    // Vérifier si l'utilisateur existe déjà
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
            $success = "Compte créé avec succès ! Vous pouvez vous connecter.";
            // Option : connecter directement
            // $_SESSION['user_id'] = $pdo->lastInsertId();
            // redirect('index.php');
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
    <link rel="stylesheet" href="./css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <img src="https://files.catbox.moe/e0a61i.jpg" alt="LBS SHOP" class="auth-logo">
                <h1>Inscription</h1>
                <p>Créez votre compte LBS SHOP</p>
            </div>
            
            <?php if(!empty($errors)): ?>
                <div class="alert alert-error">
                    <?php foreach($errors as $error): ?>
                        <div>• <?= sanitize($error) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="alert alert-success"><?= sanitize($success) ?></div>
            <?php endif; ?>
            
            <form method="POST" class="auth-form" data-validate>
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
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Mot de passe *</label>
                        <input type="password" id="password" name="password" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirmer *</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">Créer mon compte</button>
            </form>
            
            <div class="auth-footer">
                <p>Déjà inscrit ? <a href="login.php">Se connecter</a></p>
                <p class="mt-2"><a href="index.php">← Retour à l'accueil</a></p>
            </div>
        </div>
    </div>
    
    <script>
    // Validation simple côté client
    document.querySelector('form[data-validate]').addEventListener('submit', function(e) {
        const password = document.getElementById('password').value;
        const confirm = document.getElementById('confirm_password').value;
        
        if(password !== confirm) {
            e.preventDefault();
            alert('Les mots de passe ne correspondent pas');
        }
    });
    </script>
</body>
</html>
