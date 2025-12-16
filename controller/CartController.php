<?php
/**
 * Cart Controller
 * Handles AJAX requests for cart operations
 */

session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../model/CartModel.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$cartModel = new CartModel();
$response = ['success' => false, 'message' => '', 'data' => null];

// Get or create session ID
if (!isset($_SESSION['cart_session_id'])) {
    $_SESSION['cart_session_id'] = session_id();
}
$sessionId = $_SESSION['cart_session_id'];

try {
    $cart = $cartModel->getCart($sessionId);
    $cartId = $cart['id'];
    
    switch ($action) {
        case 'add':
            $productId = $_POST['product_id'] ?? 0;
            $quantity = $_POST['quantity'] ?? 1;
            
            if ($productId) {
                $result = $cartModel->addItem($cartId, $productId, $quantity);
                if ($result) {
                    $response['success'] = true;
                    $response['message'] = 'Product added to cart';
                    $response['data'] = [
                        'count' => $cartModel->getCartCount($cartId),
                        'total' => $cartModel->getCartTotal($cartId)
                    ];
                } else {
                    $response['message'] = 'Failed to add product';
                }
            } else {
                $response['message'] = 'Product ID required';
            }
            break;
            
        case 'getItems':
            $items = $cartModel->getCartItems($cartId);
            $response['success'] = true;
            $response['data'] = [
                'items' => $items,
                'count' => $cartModel->getCartCount($cartId),
                'total' => $cartModel->getCartTotal($cartId)
            ];
            break;
            
        case 'updateQuantity':
            $itemId = $_POST['item_id'] ?? 0;
            $quantity = $_POST['quantity'] ?? 1;
            
            if ($itemId) {
                $result = $cartModel->updateQuantity($itemId, $quantity);
                if ($result) {
                    $response['success'] = true;
                    $response['message'] = 'Quantity updated';
                    $response['data'] = [
                        'count' => $cartModel->getCartCount($cartId),
                        'total' => $cartModel->getCartTotal($cartId)
                    ];
                } else {
                    $response['message'] = 'Failed to update quantity';
                }
            } else {
                $response['message'] = 'Item ID required';
            }
            break;
            
        case 'remove':
            $itemId = $_POST['item_id'] ?? 0;
            
            if ($itemId) {
                $result = $cartModel->removeItem($itemId);
                if ($result) {
                    $response['success'] = true;
                    $response['message'] = 'Item removed from cart';
                    $response['data'] = [
                        'count' => $cartModel->getCartCount($cartId),
                        'total' => $cartModel->getCartTotal($cartId)
                    ];
                } else {
                    $response['message'] = 'Failed to remove item';
                }
            } else {
                $response['message'] = 'Item ID required';
            }
            break;
            
        case 'clear':
            $result = $cartModel->clearCart($cartId);
            if ($result) {
                $response['success'] = true;
                $response['message'] = 'Cart cleared';
                $response['data'] = ['count' => 0, 'total' => 0];
            } else {
                $response['message'] = 'Failed to clear cart';
            }
            break;
            
        case 'getCount':
            $response['success'] = true;
            $response['data'] = [
                'count' => $cartModel->getCartCount($cartId),
                'total' => $cartModel->getCartTotal($cartId)
            ];
            break;
            
        case 'checkout':
            // 1. Validate inputs
            $customerName = $_POST['customer_name'] ?? '';
            $customerEmail = $_POST['customer_email'] ?? '';
            $customerPhone = $_POST['customer_phone'] ?? '';
            $shippingAddress = $_POST['shipping_address'] ?? '';
            $paymentMethod = $_POST['payment_method'] ?? 'credit_card';

            if (empty($customerName) || empty($customerEmail) || empty($shippingAddress)) {
                $response['message'] = 'Missing customer details';
                break;
            }

            // 2. Get cart items to calculate totals
            $items = $cartModel->getCartItems($cartId);
            if (empty($items)) {
                $response['message'] = 'Cart is empty';
                break;
            }

            $currentSubtotal = 0;
            foreach ($items as $item) {
                $currentSubtotal += ($item['price'] * $item['quantity']);
            }
            
            $shipping = 7.00;
            $charityPercent = 10;
            $charityAmount = $currentSubtotal * ($charityPercent / 100);
            $total = $currentSubtotal + $shipping;

            try {
                $pdo = getDB();
                $pdo->beginTransaction();

                // 3. Create Order
                $stmt = $pdo->prepare("
                    INSERT INTO commande 
                    (user_id, total, subtotal, charity_amount, status, payment_method, customer_name, customer_email, customer_phone, shipping_address) 
                    VALUES (?, ?, ?, ?, 'completed', ?, ?, ?, ?, ?)
                ");
                
                // Assuming user_id is null for guest checkout, or from session if logged in
                $userId = $_SESSION['user_id'] ?? null; 
                
                $stmt->execute([
                    $userId, 
                    $total, 
                    $currentSubtotal, 
                    $charityAmount, 
                    $paymentMethod, 
                    $customerName, 
                    $customerEmail, 
                    $customerPhone, 
                    $shippingAddress
                ]);
                
                $orderId = $pdo->lastInsertId();

                // 4. Create Order Items
                $stmtItem = $pdo->prepare("
                    INSERT INTO commande_items (commande_id, product_id, product_name, quantity, price) 
                    VALUES (?, ?, ?, ?, ?)
                ");

                foreach ($items as $item) {
                    $stmtItem->execute([
                        $orderId,
                        $item['product_id'],
                        $item['name'],
                        $item['quantity'],
                        $item['price']
                    ]);
                }

                // 5. Clear Cart
                $cartModel->clearCart($cartId);

                $pdo->commit();
                
                $response['success'] = true;
                $response['message'] = 'Order placed successfully';
                $response['data'] = ['order_id' => $orderId];

            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $response['message'] = 'Order failed: ' . $e->getMessage();
            }
            break;

        default:
            $response['message'] = 'Invalid action';
            break;
    }
} catch (Exception $e) {
    $response['message'] = 'Server error: ' . $e->getMessage();
    error_log("CartController error: " . $e->getMessage());
}

echo json_encode($response);
?>
