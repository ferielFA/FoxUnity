<?php
// Simple database connection test
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Database Connection Test</h2>";

// Test 1: Check if config.php exists
echo "<h3>1. Checking config.php...</h3>";
if (file_exists(__DIR__ . '/config.php')) {
    echo "✓ config.php found<br>";
} else {
    echo "✗ config.php NOT found<br>";
    die();
}

// Test 2: Include config
echo "<h3>2. Loading config.php...</h3>";
try {
    require_once __DIR__ . '/config.php';
    echo "✓ config.php loaded successfully<br>";
} catch (Exception $e) {
    echo "✗ Error loading config: " . $e->getMessage() . "<br>";
    die();
}

// Test 3: Check database constants
echo "<h3>3. Database Configuration:</h3>";
echo "Host: " . (defined('DB_HOST') ? DB_HOST : 'NOT DEFINED') . "<br>";
echo "Database: " . (defined('DB_NAME') ? DB_NAME : 'NOT DEFINED') . "<br>";
echo "User: " . (defined('DB_USER') ? DB_USER : 'NOT DEFINED') . "<br>";
echo "Password: " . (defined('DB_PASS') ? (DB_PASS === '' ? '(empty)' : '(set)') : 'NOT DEFINED') . "<br>";

// Test 4: Try to connect
echo "<h3>4. Testing Connection...</h3>";
try {
    $pdo = getDB();
    echo "✓ <strong style='color:green;'>Database connection successful!</strong><br>";
    
    // Test 5: Check if produit table exists
    echo "<h3>5. Checking 'produit' table...</h3>";
    $stmt = $pdo->query("SHOW TABLES LIKE 'produit'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Table 'produit' exists<br>";
        
        // Count products
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM produit");
        $count = $stmt->fetch()['count'];
        echo "✓ Found $count product(s) in database<br>";
    } else {
        echo "✗ Table 'produit' does NOT exist<br>";
        echo "<p>You need to create the table. Here's the SQL:</p>";
        echo "<pre>
CREATE TABLE IF NOT EXISTS produit (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
</pre>";
    }
    
} catch (PDOException $e) {
    echo "✗ <strong style='color:red;'>Connection failed!</strong><br>";
    echo "Error: " . $e->getMessage() . "<br>";
    echo "<br><h3>Troubleshooting Steps:</h3>";
    echo "<ol>";
    echo "<li>Make sure XAMPP MySQL is running (check XAMPP Control Panel)</li>";
    echo "<li>Verify database 'foxunity0' exists in phpMyAdmin</li>";
    echo "<li>Check if MySQL is running on port 3306</li>";
    echo "<li>Try using '127.0.0.1' instead of 'localhost'</li>";
    echo "</ol>";
}
?>
