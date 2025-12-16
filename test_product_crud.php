<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/model/ProductModel.php';

$model = new ProductModel();
$name = "Test Product " . rand(1000,9999);
$desc = "Test Description";
$price = 123.45;
$image = "https://example.com/image.png";

echo "Creating product '$name'...\n";
if ($model->createProduct($name, $desc, $price, $image)) {
    echo "Product created.\n";
    $all = $model->getAllProducts();
    // Find the product we just added
    $found = false;
    foreach ($all as $p) {
        if ($p['name'] === $name) {
            echo "Found product in DB. ID: " . $p['produit_id'] . "\n";
            echo "Image: " . $p['image'] . "\n";
            
            // Test Update
            echo "Updating product...\n";
            if ($model->updateProduct($p['produit_id'], $name . " Updated", $desc, $price, $image)) {
                echo "Update successful.\n";
            } else {
                echo "Update failed.\n";
            }
            
            // Test Delete
            echo "Deleting product...\n";
            if ($model->deleteProduct($p['produit_id'])) {
                echo "Delete successful.\n";
            } else {
                echo "Delete failed.\n";
            }
            $found = true;
            break;
        }
    }
    if (!$found) echo "Product not found in list!\n";
} else {
    echo "Create failed.\n";
}
?>
