<?php
require_once __DIR__ . '/view/back/db.php';

try {
    // 1. Create subscribers table
    $pdo->exec("CREATE TABLE IF NOT EXISTS subscribers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL UNIQUE,
        categories TEXT COMMENT 'Comma separated category IDs',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "Table 'subscribers' created or already exists.<br>";

    // 2. Add notifications_sent column to article if not exists
    $cols = $pdo->query("SHOW COLUMNS FROM article LIKE 'notifications_sent'")->fetchAll();
    if (empty($cols)) {
        $pdo->exec("ALTER TABLE article ADD COLUMN notifications_sent TINYINT(1) DEFAULT 0");
        echo "Column 'notifications_sent' added to 'article'.<br>";
    } else {
        echo "Column 'notifications_sent' already exists.<br>";
    }

    echo "Database setup completed successfully.";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
