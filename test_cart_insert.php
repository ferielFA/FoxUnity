<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/model/CartModel.php';

session_start();
echo "<h1>Cart Insertion Test</h1>";

// 1. Get/Create Cart
$sessionId = session_id();
echo "Session ID: " . $sessionId . "<br>";

$model = new CartModel();
try {
    echo "Getting cart...<br>";
    $cart = $model->getCart($sessionId);
    
    if ($cart) {
        echo "Cart found/created. ID: " . $cart['id'] . "<br>";
        
        // 2. Try Add Item
        // Use a known existing product ID from my previous check (e.g., from the first few rows of 'produit')
        // I'll query one valid product ID first just to be sure
        $pdo = getDB();
        $stmt = $pdo->query("SELECT produit_id FROM produit LIMIT 1");
        $prod = $stmt->fetch();
        
        if ($prod) {
            $productId = $prod['produit_id'];
            echo "Attempting to add Product ID: " . $productId . "<br>";
            
            $result = $model->addItem($cart['id'], $productId, 2);
            
            if ($result) {
                echo "<strong style='color:green'>SUCCESS: Item added to cart!</strong><br>";
            } else {
                echo "<strong style='color:red'>FAILURE: addItem returned false.</strong><br>";
                // Try to see PDO error info if possible? (Model doesn't expose it easily, but verify via catch)
            }
            
            // 3. Verify Content
            $items = $model->getCartItems($cart['id']);
            echo "Items in cart now: " . count($items) . "<br>";
            echo "<pre>";
            print_r($items);
            echo "</pre>";
            
        } else {
            echo "No products found in DB to test with.<br>";
        }
        
    } else {
        echo "Failed to get cart.<br>";
    }
    
} catch (Exception $e) {
    echo "<h3>Exception: " . $e->getMessage() . "</h3>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
