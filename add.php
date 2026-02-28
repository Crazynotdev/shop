<?php
require_once __DIR__ . '/includes/config.php';

$email = "paulvan@gmail.com";
$password = "lbs2026";

$hash = password_hash($password, PASSWORD_BCRYPT);

$stmt = $pdo->prepare("UPDATE users SET role='admin', password=? WHERE email=?");
$stmt->execute([$hash, $email]);

if($stmt->rowCount()) {
    echo "✅ Compte admin mis à jour avec succès";
} else {
    echo "❌ Aucun compte trouvé avec cet email";
}
