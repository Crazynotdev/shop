<?php
// includes/functions.php

function generateSlug($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return empty($text) ? 'n-a' : $text;
}

function getCartCount($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT SUM(quantity) FROM carts WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetchColumn() ?: 0;
}

function addToCart($pdo, $user_id, $product_id, $quantity = 1) {
    // Vérifier si le produit existe déjà dans le panier
    $stmt = $pdo->prepare("SELECT id, quantity FROM carts WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);
    $existing = $stmt->fetch();
    
    if($existing) {
        // Mettre à jour la quantité
        $new_quantity = $existing['quantity'] + $quantity;
        $stmt = $pdo->prepare("UPDATE carts SET quantity = ? WHERE id = ?");
        return $stmt->execute([$new_quantity, $existing['id']]);
    } else {
        // Ajouter au panier
        $stmt = $pdo->prepare("INSERT INTO carts (user_id, product_id, quantity) VALUES (?, ?, ?)");
        return $stmt->execute([$user_id, $product_id, $quantity]);
    }
}

function removeFromCart($pdo, $user_id, $product_id) {
    $stmt = $pdo->prepare("DELETE FROM carts WHERE user_id = ? AND product_id = ?");
    return $stmt->execute([$user_id, $product_id]);
}

function updateCartQuantity($pdo, $user_id, $product_id, $quantity) {
    if($quantity <= 0) {
        return removeFromCart($pdo, $user_id, $product_id);
    }
    
    $stmt = $pdo->prepare("UPDATE carts SET quantity = ? WHERE user_id = ? AND product_id = ?");
    return $stmt->execute([$quantity, $user_id, $product_id]);
}

function getCartItems($pdo, $user_id) {
    $stmt = $pdo->prepare("
        SELECT c.*, p.name, p.price, p.image, p.stock 
        FROM carts c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = ?
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

function getCartTotal($pdo, $user_id) {
    $stmt = $pdo->prepare("
        SELECT SUM(c.quantity * p.price) 
        FROM carts c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = ?
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchColumn() ?: 0;
}

function createOrder($pdo, $user_id, $shipping_data, $payment_method) {
    try {
        $pdo->beginTransaction();
        
        // Récupérer les articles du panier
        $cart_items = getCartItems($pdo, $user_id);
        $total = getCartTotal($pdo, $user_id);
        
        if(empty($cart_items)) {
            throw new Exception("Panier vide");
        }
        
        // Créer la commande
        $stmt = $pdo->prepare("
            INSERT INTO orders (user_id, total_amount, shipping_address, shipping_city, shipping_phone, payment_method) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $user_id,
            $total,
            $shipping_data['address'],
            $shipping_data['city'],
            $shipping_data['phone'],
            $payment_method
        ]);
        
        $order_id = $pdo->lastInsertId();
        
        // Ajouter les articles
        $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        foreach($cart_items as $item) {
            $stmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);
        }
        
        // Vider le panier
        $stmt = $pdo->prepare("DELETE FROM carts WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        $pdo->commit();
        return $order_id;
        
    } catch(Exception $e) {
        $pdo->rollBack();
        return false;
    }
}

function uploadFile($file, $target_dir, $allowed_types = ['image/jpeg', 'image/png', 'image/webp'], $max_size = 2097152) {
    if($file['error'] !== UPLOAD_ERR_OK) {
        return ['error' => 'Erreur lors de l\'upload'];
    }
    
    if(!in_array($file['type'], $allowed_types)) {
        return ['error' => 'Type de fichier non autorisé'];
    }
    
    if($file['size'] > $max_size) {
        return ['error' => 'Fichier trop volumineux'];
    }
    
    if(!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $target_path = $target_dir . '/' . $filename;
    
    if(move_uploaded_file($file['tmp_name'], $target_path)) {
        return ['success' => true, 'filename' => $filename];
    }
    
    return ['error' => 'Erreur lors de la sauvegarde'];
}
?>
