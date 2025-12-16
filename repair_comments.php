<?php
require_once __DIR__ . '/model/db.php';
global $pdo;

try {
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
