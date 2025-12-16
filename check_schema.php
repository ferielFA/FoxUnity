<?php
require_once 'config.php';

try {
    $pdo = getDB();
    
    // Check users table
    echo "Checking users table...\n";
    $stmt = $pdo->query("DESCRIBE users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($users);
    
    // Check produit table
    echo "\nChecking produit table...\n";
    $stmt = $pdo->query("DESCRIBE produit");
    $produit = $stmt->fetchAll(PDO::FETCH_ASSOC);
    print_r($produit);

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
