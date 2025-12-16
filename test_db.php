<?php
require __DIR__ . '/config.php';

echo "Testing database connections...\n";

// Test PDO
try {
    $stmt = $pdo->query('SELECT 1');
    $res = $stmt->fetchColumn();
    if ($res == 1) {
        echo "PDO: OK\n";
    } else {
        echo "PDO: Unexpected result\n";
    }
} catch (Exception $e) {
    echo "PDO error: " . $e->getMessage() . "\n";
}

// Test MySQLi
if (isset($mysqli) && $mysqli->connect_errno === 0) {
    $result = $mysqli->query('SELECT 1');
    if ($result && $row = $result->fetch_row()) {
        echo "MySQLi: OK\n";
    } else {
        echo "MySQLi: Unexpected result or query failed\n";
    }
} else {
    echo "MySQLi error: " . ($mysqli->connect_error ?? 'not initialized') . "\n";
}

echo "Done.\n";

?>