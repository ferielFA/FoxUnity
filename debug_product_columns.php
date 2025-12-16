<?php
require_once __DIR__ . '/config.php';

try {
    $pdo = getDB();
    $stmt = $pdo->query("SHOW COLUMNS FROM produit");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Columns in 'produit' table:</h2>";
    echo "<ul>";
    foreach ($columns as $col) {
        echo "<li>" . $col['Field'] . " (" . $col['Type'] . ")</li>";
    }
    echo "</ul>";
    
    // Also check content of first product
    $stmt = $pdo->query("SELECT * FROM produit LIMIT 1");
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($product) {
        echo "<h2>First product data:</h2>";
        echo "<pre>";
        print_r($product);
        echo "</pre>";
    } else {
        echo "<p>No products found in table.</p>";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
