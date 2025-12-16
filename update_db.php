<?php
require 'config.php';

try {
    $sql = "ALTER TABLE produit ADD COLUMN image VARCHAR(255) DEFAULT NULL";
    $pdo->exec($sql);
    echo "Column 'image' added successfully to table 'produit'.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
