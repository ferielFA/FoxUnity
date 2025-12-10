<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Database Connection Test</h2>";

// Test 1: Check PDO availability
echo "<h3>Test 1: PDO MySQL Driver</h3>";
if (extension_loaded('pdo_mysql')) {
    echo "✅ PDO MySQL extension is loaded<br>";
} else {
    echo "❌ PDO MySQL extension is NOT loaded<br>";
}

// Test 2: Test connection without database
echo "<h3>Test 2: MySQL Server Connection</h3>";
try {
    $pdo = new PDO("mysql:host=localhost", "root", "");
    echo "✅ MySQL server is accessible<br>";
    
    // List databases
    $stmt = $pdo->query("SHOW DATABASES");
    echo "<strong>Available databases:</strong><br>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- " . $row['Database'] . "<br>";
    }
} catch (PDOException $e) {
    echo "❌ MySQL server connection failed: " . $e->getMessage() . "<br>";
}

// Test 3: Test integration database
echo "<h3>Test 3: Integration Database</h3>";
try {
    $pdo = new PDO("mysql:host=localhost;dbname=integration;charset=utf8mb4", "root", "");
    echo "✅ Integration database exists and is accessible<br>";
    
    // Count tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Found " . count($tables) . " tables<br>";
    
} catch (PDOException $e) {
    echo "❌ Integration database connection failed: " . $e->getMessage() . "<br>";
    
    // Try to create it
    echo "<br><strong>Attempting to create database...</strong><br>";
    try {
        $pdo = new PDO("mysql:host=localhost", "root", "");
        $pdo->exec("CREATE DATABASE IF NOT EXISTS integration DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "✅ Integration database created successfully!<br>";
        echo "⚠️ Please import the schema: database/integration_complete.sql<br>";
    } catch (PDOException $e2) {
        echo "❌ Failed to create database: " . $e2->getMessage() . "<br>";
    }
}

// Test 4: Test Database class
echo "<h3>Test 4: Database Class</h3>";
try {
    require_once __DIR__ . '/config/database.php';
    $conn = Database::getConnection();
    echo "✅ Database class connection successful<br>";
    
    // Test query
    $stmt = $conn->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Users table has " . $result['count'] . " records<br>";
    
} catch (Exception $e) {
    echo "❌ Database class failed: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<p>If you see errors above, the database needs to be recreated.</p>";
echo "<p>Run in terminal: <code>Get-Content database\\integration_complete.sql | C:\\xampp\\mysql\\bin\\mysql.exe -u root</code></p>";
?>
