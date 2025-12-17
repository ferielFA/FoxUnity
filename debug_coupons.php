<?php
// Script to verify and fix coupon tables
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/model/config.php';

try {
    // Use the correct method from config/database.php
    $pdo = Database::getConnection();
    echo "Database connection successful.<br>";

    // Check 'coupons' table
    $couponsCheck = $pdo->query("SHOW TABLES LIKE 'coupons'");
    if ($couponsCheck->rowCount() == 0) {
        echo "Table 'coupons' does NOT exist. Creating...<br>";
        $sql = "CREATE TABLE `coupons` (
            `coupon_id` int(11) NOT NULL AUTO_INCREMENT,
            `code` varchar(50) NOT NULL,
            `discount_type` enum('percentage','fixed') NOT NULL DEFAULT 'percentage',
            `discount_value` decimal(10,2) NOT NULL,
            `min_purchase` decimal(10,2) NOT NULL DEFAULT 0.00,
            `max_discount` decimal(10,2) DEFAULT NULL,
            `usage_limit` int(11) DEFAULT NULL,
            `used_count` int(11) NOT NULL DEFAULT 0,
            `expires_at` datetime NOT NULL,
            `publisher_id` int(11) DEFAULT NULL,
            `is_active` tinyint(1) NOT NULL DEFAULT 1,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`coupon_id`),
            UNIQUE KEY `code` (`code`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
        $pdo->exec($sql);
        echo "Table 'coupons' created.<br>";
    } else {
        echo "Table 'coupons' exists.<br>";
    }

    // Check 'coupon_usage' table
    $usageCheck = $pdo->query("SHOW TABLES LIKE 'coupon_usage'");
    if ($usageCheck->rowCount() == 0) {
        echo "Table 'coupon_usage' does NOT exist. Creating...<br>";
        $sql = "CREATE TABLE `coupon_usage` (
            `usage_id` int(11) NOT NULL AUTO_INCREMENT,
            `coupon_id` int(11) NOT NULL,
            `user_id` int(11) NOT NULL,
            `order_amount` decimal(10,2) NOT NULL,
            `discount_applied` decimal(10,2) NOT NULL,
            `used_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`usage_id`),
            KEY `coupon_id` (`coupon_id`),
            KEY `user_id` (`user_id`),
            CONSTRAINT `coupon_usage_ibfk_1` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`coupon_id`) ON DELETE CASCADE,
            CONSTRAINT `coupon_usage_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
        $pdo->exec($sql);
        echo "Table 'coupon_usage' created.<br>";
    } else {
        echo "Table 'coupon_usage' exists.<br>";
    }

    echo "Verification complete.";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>