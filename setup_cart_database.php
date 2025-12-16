<?php
/**
 * Database setup for shopping cart and orders
 * Run this file once to create necessary tables
 */

require_once __DIR__ . '/config.php';

try {
    $pdo = getDB();
    echo "<h2>Setting up Shopping Cart Database</h2>";
    
    // Create panier (cart) table
    echo "<p>Creating 'panier' table...</p>";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS panier (
            id INT AUTO_INCREMENT PRIMARY KEY,
            session_id VARCHAR(255) NOT NULL,
            user_id INT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX(session_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "<p style='color:green;'>✓ Table 'panier' created</p>";
    
    // Create panier_items table
    echo "<p>Creating 'panier_items' table...</p>";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS panier_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            panier_id INT NOT NULL,
            product_id INT NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            price DECIMAL(10,2) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (panier_id) REFERENCES panier(id) ON DELETE CASCADE,
            INDEX(panier_id),
            INDEX(product_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "<p style='color:green;'>✓ Table 'panier_items' created</p>";
    
    // Create commande (orders) table
    echo "<p>Creating 'commande' table...</p>";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS commande (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT DEFAULT NULL,
            total DECIMAL(10,2) NOT NULL,
            subtotal DECIMAL(10,2) NOT NULL,
            charity_amount DECIMAL(10,2) NOT NULL,
            status VARCHAR(50) DEFAULT 'pending',
            payment_method VARCHAR(50),
            customer_name VARCHAR(255),
            customer_email VARCHAR(255),
            customer_phone VARCHAR(50),
            shipping_address TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX(status),
            INDEX(created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "<p style='color:green;'>✓ Table 'commande' created</p>";
    
    // Create commande_items table
    echo "<p>Creating 'commande_items' table...</p>";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS commande_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            commande_id INT NOT NULL,
            product_id INT NOT NULL,
            product_name VARCHAR(255) NOT NULL,
            quantity INT NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            FOREIGN KEY (commande_id) REFERENCES commande(id) ON DELETE CASCADE,
            INDEX(commande_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "<p style='color:green;'>✓ Table 'commande_items' created</p>";
    
    echo "<hr>";
    echo "<h3 style='color:green;'>✓ Database setup complete!</h3>";
    echo "<p><a href='view/front/shop.php'>Go to Shop</a> | <a href='view/front/panier.php'>Go to Cart</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color:red;'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
