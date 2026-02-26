<?php
// install.php - À EXÉCUTER UNE SEULE FOIS, PUIS SUPPRIMER
require_once 'includes/config.php';

echo "<h1>🔧 Installation des tables LBS SHOP</h1>";

$sql = "
-- Table des utilisateurs
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    avatar VARCHAR(255),
    role VARCHAR(20) DEFAULT 'user',
    created_at TIMESTAMP DEFAULT NOW()
);

-- Table des catégories
CREATE TABLE IF NOT EXISTS categories (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    slug VARCHAR(50) UNIQUE NOT NULL,
    icon VARCHAR(20),
    created_at TIMESTAMP DEFAULT NOW()
);

-- Table des produits
CREATE TABLE IF NOT EXISTS products (
    id SERIAL PRIMARY KEY,
    category_id INTEGER REFERENCES categories(id) ON DELETE SET NULL,
    name VARCHAR(200) NOT NULL,
    slug VARCHAR(200) UNIQUE NOT NULL,
    description TEXT,
    description_short VARCHAR(255),
    price DECIMAL(10,2) NOT NULL,
    old_price DECIMAL(10,2) DEFAULT 0,
    stock INTEGER DEFAULT 0,
    image VARCHAR(255),
    featured BOOLEAN DEFAULT FALSE,
    status VARCHAR(20) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT NOW()
);

-- Table du panier
CREATE TABLE IF NOT EXISTS carts (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    product_id INTEGER REFERENCES products(id) ON DELETE CASCADE,
    quantity INTEGER DEFAULT 1,
    created_at TIMESTAMP DEFAULT NOW(),
    UNIQUE(user_id, product_id)
);

-- Table des commandes
CREATE TABLE IF NOT EXISTS orders (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    total_amount DECIMAL(10,2) NOT NULL,
    status VARCHAR(20) DEFAULT 'pending',
    shipping_address TEXT,
    shipping_phone VARCHAR(20),
    payment_method VARCHAR(50),
    notes TEXT,
    created_at TIMESTAMP DEFAULT NOW()
);

-- Table des détails de commande
CREATE TABLE IF NOT EXISTS order_items (
    id SERIAL PRIMARY KEY,
    order_id INTEGER REFERENCES orders(id) ON DELETE CASCADE,
    product_id INTEGER REFERENCES products(id) ON DELETE CASCADE,
    quantity INTEGER NOT NULL,
    price DECIMAL(10,2) NOT NULL
);

-- Insérer un admin (mot de passe: Admin123!)
INSERT INTO users (username, email, password, full_name, role) 
VALUES ('admin', 'admin@lbsshop.ga', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrateur', 'admin')
ON CONFLICT (email) DO NOTHING;

-- Insérer des catégories
INSERT INTO categories (name, slug, icon) VALUES
('Football', 'football', '⚽'),
('Basketball', 'basketball', '🏀'),
('Streetwear', 'streetwear', '👕'),
('Accessoires', 'accessoires', '🧢')
ON CONFLICT (slug) DO NOTHING;
";

echo "<h3>Création des tables...</h3>";

try {
    $pdo->exec($sql);
    echo "<h2 style='color: green;'>✅ Tables créées avec succès !</h2>";
    echo "<p>Compte admin : admin@lbsshop.ga / Admin123!</p>";
    echo "<p><strong>N'oublie pas de supprimer ce fichier install.php !</strong></p>";
    echo "<p><a href='index.php'>→ Aller à l'accueil</a></p>";
} catch(PDOException $e) {
    echo "<h2 style='color: red;'>❌ Erreur : " . $e->getMessage() . "</h2>";
}
?>
