<?php
require_once __DIR__ . '/model/db.php';
global $pdo;

try {
    echo "--- 1. Checking Schema ---\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM comments LIKE 'parent_id'");
    if ($stmt->fetch()) {
        echo "Column parent_id already exists.\n";
    } else {
        $pdo->exec("ALTER TABLE comments ADD COLUMN parent_id INT DEFAULT NULL");
        echo "Column parent_id added successfully.\n";
    }

    echo "--- 2. Repairing Data ---\n";
    // Update comments that have empty email but a matching username
    $sql = "UPDATE comments c 
            JOIN users u ON c.name = u.username 
            SET c.email = u.email 
            WHERE (c.email IS NULL OR c.email = '')";
            
    $count = $pdo->exec($sql);
    echo "Repaired $count comments by linking to user email.\n";

} catch(Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
