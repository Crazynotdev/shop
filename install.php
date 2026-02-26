<?php
// create-all-tables.php - À EXÉCUTER UNE SEULE FOIS, PUIS SUPPRIMER
require_once __DIR__ . '/includes/config.php';

echo "<h1>🔧 Création de toutes les tables LBS SHOP</h1>";

$tables = [];

// 1. Table users (utilisateurs)
$tables[] = "CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    avatar VARCHAR(255),
    role VARCHAR(20) DEFAULT 'user',
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP
);";

// 2. Table categories
$tables[] = "CREATE TABLE IF NOT EXISTS categories (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    slug VARCHAR(50) UNIQUE NOT NULL,
    icon VARCHAR(20),
    description TEXT,
    created_at TIMESTAMP DEFAULT NOW()
);";

// 3. Table products (produits)
$tables[] = "CREATE TABLE IF NOT EXISTS products (
    id SERIAL PRIMARY KEY,
    category_id INTEGER REFERENCES categories(id) ON DELETE SET NULL,
    name VARCHAR(200) NOT NULL,
    slug VARCHAR(200) UNIQUE NOT NULL,
    description TEXT,
    description_short VARCHAR(255),
    price DECIMAL(10,2) NOT NULL,
    old_price DECIMAL(10,2) DEFAULT 0,
    cost_price DECIMAL(10,2) DEFAULT 0,
    stock INTEGER DEFAULT 0,
    sku VARCHAR(50) UNIQUE,
    image VARCHAR(255),
    images TEXT[], -- Tableau d'images supplémentaires
    featured BOOLEAN DEFAULT FALSE,
    status VARCHAR(20) DEFAULT 'active',
    views INTEGER DEFAULT 0,
    sold_count INTEGER DEFAULT 0,
    meta_title VARCHAR(255),
    meta_description TEXT,
    weight DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP
);";

// 4. Table product_variants (variantes de produits - taille, couleur)
$tables[] = "CREATE TABLE IF NOT EXISTS product_variants (
    id SERIAL PRIMARY KEY,
    product_id INTEGER REFERENCES products(id) ON DELETE CASCADE,
    name VARCHAR(100) NOT NULL,
    value VARCHAR(100) NOT NULL,
    price DECIMAL(10,2),
    stock INTEGER DEFAULT 0,
    sku VARCHAR(50) UNIQUE,
    image VARCHAR(255),
    UNIQUE(product_id, name, value)
);";

// 5. Table tags (étiquettes)
$tables[] = "CREATE TABLE IF NOT EXISTS tags (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL,
    slug VARCHAR(50) UNIQUE NOT NULL
);";

// 6. Table product_tags (liaison produits-tags)
$tables[] = "CREATE TABLE IF NOT EXISTS product_tags (
    product_id INTEGER REFERENCES products(id) ON DELETE CASCADE,
    tag_id INTEGER REFERENCES tags(id) ON DELETE CASCADE,
    PRIMARY KEY (product_id, tag_id)
);";

// 7. Table carts (panier)
$tables[] = "CREATE TABLE IF NOT EXISTS carts (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    product_id INTEGER REFERENCES products(id) ON DELETE CASCADE,
    variant_id INTEGER REFERENCES product_variants(id) ON DELETE CASCADE,
    quantity INTEGER DEFAULT 1 NOT NULL,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP,
    UNIQUE(user_id, product_id, variant_id)
);";

// 8. Table orders (commandes)
$tables[] = "CREATE TABLE IF NOT EXISTS orders (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    shipping_cost DECIMAL(10,2) DEFAULT 0,
    tax_amount DECIMAL(10,2) DEFAULT 0,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    coupon_code VARCHAR(50),
    status VARCHAR(20) DEFAULT 'pending',
    payment_status VARCHAR(20) DEFAULT 'pending',
    payment_method VARCHAR(50),
    transaction_id VARCHAR(100),
    shipping_address TEXT,
    shipping_city VARCHAR(100),
    shipping_zip VARCHAR(20),
    shipping_country VARCHAR(50) DEFAULT 'Gabon',
    shipping_phone VARCHAR(20),
    billing_address TEXT,
    billing_phone VARCHAR(20),
    notes TEXT,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP
);";

// 9. Table order_items (détails des commandes)
$tables[] = "CREATE TABLE IF NOT EXISTS order_items (
    id SERIAL PRIMARY KEY,
    order_id INTEGER REFERENCES orders(id) ON DELETE CASCADE,
    product_id INTEGER REFERENCES products(id) ON DELETE CASCADE,
    variant_id INTEGER REFERENCES product_variants(id) ON DELETE SET NULL,
    product_name VARCHAR(200) NOT NULL,
    variant_name VARCHAR(100),
    quantity INTEGER NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT NOW()
);";

// 10. Table reviews (avis)
$tables[] = "CREATE TABLE IF NOT EXISTS reviews (
    id SERIAL PRIMARY KEY,
    product_id INTEGER REFERENCES products(id) ON DELETE CASCADE,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    rating INTEGER CHECK (rating >= 1 AND rating <= 5),
    title VARCHAR(255),
    comment TEXT,
    pros TEXT,
    cons TEXT,
    is_verified BOOLEAN DEFAULT FALSE,
    status VARCHAR(20) DEFAULT 'pending',
    helpful_count INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP,
    UNIQUE(product_id, user_id)
);";

// 11. Table wishlists (favoris)
$tables[] = "CREATE TABLE IF NOT EXISTS wishlists (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    product_id INTEGER REFERENCES products(id) ON DELETE CASCADE,
    created_at TIMESTAMP DEFAULT NOW(),
    UNIQUE(user_id, product_id)
);";

// 12. Table coupons (codes promo)
$tables[] = "CREATE TABLE IF NOT EXISTS coupons (
    id SERIAL PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    discount_type VARCHAR(20) CHECK (discount_type IN ('percentage', 'fixed')),
    discount_value DECIMAL(10,2) NOT NULL,
    min_order_amount DECIMAL(10,2),
    max_discount_amount DECIMAL(10,2),
    start_date TIMESTAMP,
    end_date TIMESTAMP,
    usage_limit INTEGER,
    used_count INTEGER DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT NOW()
);";

// 13. Table user_coupons (utilisation des coupons)
$tables[] = "CREATE TABLE IF NOT EXISTS user_coupons (
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    coupon_id INTEGER REFERENCES coupons(id) ON DELETE CASCADE,
    order_id INTEGER REFERENCES orders(id) ON DELETE SET NULL,
    used_at TIMESTAMP DEFAULT NOW(),
    PRIMARY KEY (user_id, coupon_id, order_id)
);";

// 14. Table addresses (adresses de livraison)
$tables[] = "CREATE TABLE IF NOT EXISTS addresses (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    address_type VARCHAR(20) CHECK (address_type IN ('shipping', 'billing', 'both')),
    full_name VARCHAR(100),
    address_line1 TEXT NOT NULL,
    address_line2 TEXT,
    city VARCHAR(100) NOT NULL,
    zip_code VARCHAR(20),
    country VARCHAR(50) DEFAULT 'Gabon',
    phone VARCHAR(20),
    is_default BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP
);";

// 15. Table payments (paiements)
$tables[] = "CREATE TABLE IF NOT EXISTS payments (
    id SERIAL PRIMARY KEY,
    order_id INTEGER REFERENCES orders(id) ON DELETE CASCADE,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    payment_status VARCHAR(20) DEFAULT 'pending',
    transaction_id VARCHAR(100),
    payment_data JSONB,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP
);";

// 16. Table shipping_methods (méthodes de livraison)
$tables[] = "CREATE TABLE IF NOT EXISTS shipping_methods (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    free_above_amount DECIMAL(10,2),
    estimated_days VARCHAR(50),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT NOW()
);";

// 17. Table settings (paramètres du site)
$tables[] = "CREATE TABLE IF NOT EXISTS settings (
    id SERIAL PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type VARCHAR(20) DEFAULT 'text',
    description TEXT,
    updated_at TIMESTAMP
);";

// 18. Table newsletters (inscriptions newsletter)
$tables[] = "CREATE TABLE IF NOT EXISTS newsletters (
    id SERIAL PRIMARY KEY,
    email VARCHAR(100) UNIQUE NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT NOW()
);";

// 19. Table contacts (messages de contact)
$tables[] = "CREATE TABLE IF NOT EXISTS contacts (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    subject VARCHAR(200),
    message TEXT NOT NULL,
    status VARCHAR(20) DEFAULT 'new',
    created_at TIMESTAMP DEFAULT NOW()
);";

// 20. Table notifications (notifications)
$tables[] = "CREATE TABLE IF NOT EXISTS notifications (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    type VARCHAR(50),
    title VARCHAR(200),
    message TEXT,
    is_read BOOLEAN DEFAULT FALSE,
    link VARCHAR(255),
    created_at TIMESTAMP DEFAULT NOW()
);";

// 21. Table logs (logs d'activité)
$tables[] = "CREATE TABLE IF NOT EXISTS logs (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE SET NULL,
    action VARCHAR(100),
    entity_type VARCHAR(50),
    entity_id INTEGER,
    old_data JSONB,
    new_data JSONB,
    ip_address INET,
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT NOW()
);";

// 22. Table sessions (sessions utilisateurs)
$tables[] = "CREATE TABLE IF NOT EXISTS sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    ip_address INET,
    user_agent TEXT,
    payload TEXT,
    last_activity TIMESTAMP DEFAULT NOW()
);";

// 23. Table password_resets (réinitialisation mot de passe)
$tables[] = "CREATE TABLE IF NOT EXISTS password_resets (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    token VARCHAR(255) UNIQUE NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT NOW()
);";

// 24. Table faq (questions fréquentes)
$tables[] = "CREATE TABLE IF NOT EXISTS faq (
    id SERIAL PRIMARY KEY,
    question TEXT NOT NULL,
    answer TEXT NOT NULL,
    category VARCHAR(100),
    order_index INTEGER DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT NOW()
);";

// 25. Table blog_posts (articles de blog)
$tables[] = "CREATE TABLE IF NOT EXISTS blog_posts (
    id SERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    excerpt TEXT,
    content TEXT,
    image VARCHAR(255),
    author VARCHAR(100),
    views INTEGER DEFAULT 0,
    status VARCHAR(20) DEFAULT 'draft',
    published_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP
);";

// Exécution des requêtes
$success = 0;
$errors = [];

foreach($tables as $index => $sql) {
    try {
        $pdo->exec($sql);
        echo "<p style='color: green;'>✅ Table " . ($index + 1) . " créée</p>";
        $success++;
    } catch(PDOException $e) {
        echo "<p style='color: orange;'>⚠️ Table " . ($index + 1) . " : " . $e->getMessage() . "</p>";
        $errors[] = $index + 1;
    }
}

// Insérer les données de base
echo "<h2>📦 Insertion des données de base</h2>";

try {
    // Catégories
    $pdo->exec("INSERT INTO categories (name, slug, icon) VALUES
        ('Football', 'football', '⚽'),
        ('Basketball', 'basketball', '🏀'),
        ('Streetwear', 'streetwear', '👕'),
        ('Accessoires', 'accessoires', '🧢')
    ON CONFLICT (slug) DO NOTHING;");
    echo "<p style='color: green;'>✅ Catégories insérées</p>";
    
    // Admin
    $hashed_password = password_hash('Admin123!', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, role) VALUES 
        ('admin', 'admin@lbsshop.ga', ?, 'Administrateur', 'admin')
    ON CONFLICT (email) DO NOTHING;");
    $stmt->execute([$hashed_password]);
    echo "<p style='color: green;'>✅ Admin créé (admin@lbsshop.ga / Admin123!)</p>";
    
    // Méthodes de livraison
    $pdo->exec("INSERT INTO shipping_methods (name, description, price, estimated_days) VALUES
        ('Livraison standard', 'Livraison en 24-48h', 2000, '1-2 jours'),
        ('Livraison express', 'Livraison le jour même', 5000, '24h'),
        ('Retrait en magasin', 'Gratuit - À retirer à Libreville', 0, '24h')
    ON CONFLICT DO NOTHING;");
    echo "<p style='color: green;'>✅ Méthodes de livraison insérées</p>";
    
    // Paramètres
    $pdo->exec("INSERT INTO settings (setting_key, setting_value, setting_type) VALUES
        ('site_name', 'LBS SHOP', 'text'),
        ('site_email', 'contact@lbsshop.ga', 'text'),
        ('site_phone', '+24165730123', 'text'),
        ('shipping_fee', '2000', 'number'),
        ('free_shipping_min', '60000', 'number'),
        ('delivery_time', '24-48h', 'text'),
        ('currency', 'FCFA', 'text'),
        ('whatsapp_group', 'https://chat.whatsapp.com/...', 'text'),
        ('facebook_url', '#', 'text'),
        ('instagram_url', '#', 'text')
    ON CONFLICT (setting_key) DO NOTHING;");
    echo "<p style='color: green;'>✅ Paramètres insérés</p>";
    
} catch(PDOException $e) {
    echo "<p style='color: orange;'>⚠️ Données de base : " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>📊 RÉSULTAT : $success tables créées sur " . count($tables) . "</h3>";

if(empty($errors)) {
    echo "<p style='color: green; font-size: 1.2rem; font-weight: bold;'>✅ TOUTES LES TABLES ONT ÉTÉ CRÉÉES AVEC SUCCÈS !</p>";
} else {
    echo "<p style='color: orange;'>⚠️ Problèmes sur les tables : " . implode(', ', $errors) . "</p>";
}

echo "<p style='margin-top: 2rem;'><a href='shop.php' class='btn btn-primary'>➡️ Aller à la boutique</a></p>";
echo "<p><strong style='color: red;'>⚠️ IMPORTANT : Supprime ce fichier immédiatement après utilisation !</strong></p>";
?>
