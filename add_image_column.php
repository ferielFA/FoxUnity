<?php
require_once __DIR__ . '/config.php';

try {
    $pdo = getDB();
    echo "<h2>Updating Database Schema...</h2>";
    
    // Check if 'image' column exists in 'produit' table
    $stmt = $pdo->query("SHOW COLUMNS FROM produit LIKE 'image'");
    $exists = $stmt->fetch();
    
    if (!$exists) {
        echo "<p>Adding 'image' column to 'produit' table...</p>";
        $pdo->exec("ALTER TABLE produit ADD COLUMN image VARCHAR(255) DEFAULT NULL AFTER price");
        echo "<p style='color:green'>✓ 'image' column added successfully.</p>";
    } else {
        echo "<p style='color:orange'>⚠ 'image' column already exists.</p>";
    }

    // Verify columns again
    $stmt = $pdo->query("SHOW COLUMNS FROM produit");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<h3>Current columns:</h3><ul>";
    foreach ($columns as $col) {
        echo "<li>" . $col['Field'] . "</li>";
    }
    echo "</ul>";

} catch (PDOException $e) {
    echo "<p style='color:red'>✗ Error: " . $e->getMessage() . "</p>";
}
?>
