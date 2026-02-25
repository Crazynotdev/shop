<?php
// fix-paths.php - À SUPPRIMER APRÈS UTILISATION
echo "<h2>🔧 Correction des chemins d'inclusion</h2>";

$files = array_merge(
    glob("*.php"),
    glob("admin/*.php")
);

$fixed = 0;
foreach($files as $file) {
    $content = file_get_contents($file);
    $original = $content;
    
    // Remplacer require 'config.php' par require_once __DIR__ . '/includes/config.php'
    $content = preg_replace(
        "/require\s+['\"]config\.php['\"]\s*;/",
        "require_once __DIR__ . '/includes/config.php';",
        $content
    );
    
    // Remplacer require 'includes/config.php' par require_once __DIR__ . '/includes/config.php'
    $content = preg_replace(
        "/require\s+['\"]includes\/config\.php['\"]\s*;/",
        "require_once __DIR__ . '/includes/config.php';",
        $content
    );
    
    // Pour les fichiers admin
    if(strpos($file, 'admin/') === 0) {
        $content = preg_replace(
            "/require\s+['\"]\.\.\/includes\/config\.php['\"]\s*;/",
            "require_once __DIR__ . '/../includes/config.php';",
            $content
        );
    }
    
    if($content !== $original) {
        file_put_contents($file, $content);
        echo "✅ Corrigé : $file<br>";
        $fixed++;
    }
}

echo "<h3>Total : $fixed fichiers corrigés</h3>";
echo "<p>Supprime ce fichier une fois la correction effectuée.</p>";
?>
