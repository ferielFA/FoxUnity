<?php
session_start();
require __DIR__ . '/../../config.php';

echo "Step 1: Session started<br>";
echo "Step 2: Config loaded<br>";

require __DIR__ . '/../../model/CartModel.php';
echo "Step 3: CartModel loaded<br>";

$pdo = getDB();
echo "Step 4: Database connected<br>";

$stmt = $pdo->query("SELECT * FROM produit LIMIT 1");
$products = $stmt->fetchAll();
echo "Step 5: Products fetched<br>";

if (count($products) > 0) {
    $product = $products[0];
    echo "Product ID: " . $product['id'] . "<br>";
    echo "Product Name: " . htmlspecialchars($product['name']) . "<br>";
}

echo "<br>All steps completed successfully!";
?>
