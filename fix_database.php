<?php
// Check database structure and fix if needed
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config.php';

echo "<h2>Database Structure Check</h2>";

try {
    $pdo = getDB();
    echo "<p style='color:green;'>✓ Database connected successfully</p>";
    
    // Check if produit table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'produit'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color:green;'>✓ Table 'produit' exists</p>";
        
        // Get table structure
        echo "<h3>Current Table Structure:</h3>";
        $stmt = $pdo->query("DESCRIBE produit");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        $hasIdColumn = false;
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($col['Extra']) . "</td>";
            echo "</tr>";
            
            if (strtolower($col['Field']) === 'id') {
                $hasIdColumn = true;
            }
        }
        echo "</table>";
        
        if (!$hasIdColumn) {
            echo "<p style='color:red;'>✗ 'id' column is missing!</p>";
            echo "<h3>Fixing table structure...</h3>";
            
            // Get the first column name to use as reference
            $firstColumn = $columns[0]['Field'];
            
            // Add id column as first column
            $pdo->exec("ALTER TABLE produit ADD COLUMN id INT AUTO_INCREMENT PRIMARY KEY FIRST");
            echo "<p style='color:green;'>✓ Added 'id' column successfully</p>";
            
            // Verify
            $stmt = $pdo->query("DESCRIBE produit");
            $newColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<h3>Updated Table Structure:</h3>";
            echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
            echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
            foreach ($newColumns as $col) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
                echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
                echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
                echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
                echo "<td>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td>";
                echo "<td>" . htmlspecialchars($col['Extra']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color:green;'>✓ 'id' column exists</p>";
        }
        
        // Count products
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM produit");
        $count = $stmt->fetch()['count'];
        echo "<p>Total products in database: <strong>$count</strong></p>";
        
        if ($count > 0) {
            echo "<h3>Sample Products:</h3>";
            $stmt = $pdo->query("SELECT * FROM produit LIMIT 5");
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
            if (count($products) > 0) {
                // Header
                echo "<tr>";
                foreach (array_keys($products[0]) as $key) {
                    echo "<th>" . htmlspecialchars($key) . "</th>";
                }
                echo "</tr>";
                
                // Data
                foreach ($products as $product) {
                    echo "<tr>";
                    foreach ($product as $value) {
                        echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
                    }
                    echo "</tr>";
                }
            }
            echo "</table>";
        }
        
    } else {
        echo "<p style='color:red;'>✗ Table 'produit' does NOT exist</p>";
        echo "<h3>Creating table...</h3>";
        
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS produit (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                price DECIMAL(10,2) NOT NULL,
                image VARCHAR(500),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        echo "<p style='color:green;'>✓ Table 'produit' created successfully</p>";
    }
    
    echo "<hr>";
    echo "<p><a href='view/front/shop.php'>Go to Shop</a> | <a href='view/back/dashboard.php'>Go to Dashboard</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color:red;'>✗ Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
