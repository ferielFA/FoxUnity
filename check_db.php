<?php
require_once __DIR__ . '/model/db.php';
global $pdo;

try {
    $stmt = $pdo->query("SHOW COLUMNS FROM comments LIKE 'parent_id'");
    if ($stmt->fetch()) {
        echo "Column parent_id exists.\n";
    } else {
        echo "Column parent_id MISSING.\n";
    }
} catch(Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
