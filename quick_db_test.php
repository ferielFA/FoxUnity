<?php
// Quick database test
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once __DIR__ . '/config/database.php';
    $db = Database::getConnection();
    
    echo "✅ Database connection successful!<br><br>";
    
    // Show tables
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<strong>Tables in database (" . count($tables) . " total):</strong><br>";
    foreach ($tables as $table) {
        echo "- $table<br>";
    }
    
    // Test users table
    echo "<br><strong>Testing users table:</strong><br>";
    $stmt = $db->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Users: " . $result['count'] . " records<br>";
    
    // Test evenement table
    echo "<br><strong>Testing evenement table:</strong><br>";
    $stmt = $db->query("DESCRIBE evenement");
    echo "Columns: ";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . ", ";
    }
    
    echo "<br><br>✅ All checks passed! Database is working correctly.";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
