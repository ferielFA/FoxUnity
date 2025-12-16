<?php
require_once __DIR__ . '/config.php';

try {
    $pdo = getDB();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Drop existing table
    $pdo->exec("DROP TABLE IF EXISTS `panier_items`");
    echo "Table 'panier_items' dropped.<br>";

    // Re-create WITHOUT Foreign Key on product_id to avoid constraint errors
    // We keep FK on panier_id to cascade delete if cart is removed
    $sqlPanierItems = "CREATE TABLE `panier_items` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `panier_id` int(11) NOT NULL,
        `product_id` int(11) NOT NULL,
        `quantity` int(11) NOT NULL DEFAULT 1,
        `price` decimal(10,2) NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `panier_id` (`panier_id`),
        KEY `product_id` (`product_id`),
        CONSTRAINT `panier_items_ibfk_1` FOREIGN KEY (`panier_id`) REFERENCES `panier` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $pdo->exec($sqlPanierItems);
    echo "Table 'panier_items' re-created successfully (FK removed).<br>";

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
