<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/model/CartModel.php';

session_start();
$sessionId = session_id();
echo "Session ID: " . $sessionId . "\n";

$cartModel = new CartModel();

// 1. Get/Create Cart
echo "1. Getting Cart...\n";
$cart = $cartModel->getCart($sessionId);
print_r($cart);

if (!$cart) {
    die("Failed to get cart.\n");
}

$cartId = $cart['id'];
echo "Cart ID: " . $cartId . "\n";

// 2. Add Item (Product ID 1)
echo "2. Adding Item (Product 1)...\n";
// Ensure product 1 exists first
$pdo = getDB();
$stmt = $pdo->query("SELECT * FROM produit WHERE produit_id = 1");
if (!$stmt->fetch()) {
    // If prod 1 doesn't exist, get first available
    $stmt = $pdo->query("SELECT produit_id FROM produit LIMIT 1");
    $prod = $stmt->fetch();
    $prodId = $prod['produit_id'];
} else {
    $prodId = 1;
}
echo "Using Product ID: $prodId\n";

$result = $cartModel->addItem($cartId, $prodId, 1);
if ($result) {
    echo "Item added successfully.\n";
} else {
    echo "Failed to add item.\n";
}

// 3. Check Cart Items
echo "3. Checking Cart Items...\n";
$items = $cartModel->getCartItems($cartId);
print_r($items);

if (count($items) > 0) {
    echo "SUCCESS: Cart contains items.\n";
} else {
    echo "FAILURE: Cart is empty.\n";
}
?>
