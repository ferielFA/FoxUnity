<?php
require 'view/back/db.php';

$sql = file_get_contents('database/foxunity0.sql');

// Split into individual statements
$statements = array_filter(array_map('trim', explode(';', $sql)));

foreach ($statements as $statement) {
    if (!empty($statement) && !preg_match('/^--/', $statement)) {
        try {
            $pdo->exec($statement);
        } catch (Exception $e) {
            echo "Error executing: $statement\n" . $e->getMessage() . "\n";
        }
    }
}

echo "Database import completed.\n";
?>