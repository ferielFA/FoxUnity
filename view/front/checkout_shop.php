<?php
/**
 * ====================================================
 * CHECKOUT SHOP - Traitement des achats de produits
 * ====================================================
 * 
 * Ce fichier gère UNIQUEMENT les achats de produits du shop
 * (pas les skins du trading)
 * 
 * Place ce fichier dans : /view/front/checkout_shop.php
 */

declare(strict_types=1);
header('Content-Type: application/json');

require_once __DIR__ . '/../../model/config.php';
require_once __DIR__ . '/../../controller/ProductController.php';
require_once __DIR__ . '/../../controller/CouponController.php';

// Vérifier que l'utilisateur est connecté
if (!isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'error' => 'You must be logged in to make a purchase'
    ]);
    exit;
}

// Récupérer les infos utilisateur
$currentUserId = getCurrentUserId();
$currentUsername = getCurrentUsername();

// Vérifier que c'est bien une requête d'achat
if (!isset($_POST['buy_products'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid request'
    ]);
    exit;
}

try {
    $pdo = getDB();
    $pdo->beginTransaction();
    
    // ========== 1. RÉCUPÉRER LES DONNÉES DU PANIER ==========
    
    $productIds = json_decode($_POST['product_ids'] ?? '[]', true);
    $quantities = json_decode($_POST['quantities'] ?? '{}', true);
    
    if (empty($productIds)) {
        throw new Exception('No products in cart');
    }
    
    // ========== 2. VÉRIFIER ET CALCULER LE TOTAL ==========
    
    $productController = new ProductController();
    $totalAmount = 0;
    $purchaseDetails = [];
    
    foreach ($productIds as $productId) {
        $product = $productController->getProductById($productId);
        
        if (!$product) {
            throw new Exception("Product ID $productId not found");
        }
        
        // Récupérer la quantité demandée
        $quantity = 1;
        if (isset($quantities['products'])) {
            foreach ($quantities['products'] as $item) {
                if ($item['id'] == $productId) {
                    $quantity = $item['quantity'];
                    break;
                }
            }
        }
        
        // Vérifier le stock
        if ($product->getStock() < $quantity) {
            throw new Exception("Insufficient stock for {$product->getName()}");
        }
        
        $itemTotal = $product->getPrice() * $quantity;
        $totalAmount += $itemTotal;
        
        $purchaseDetails[] = [
            'product_id' => $productId,
            'name' => $product->getName(),
            'price' => $product->getPrice(),
            'quantity' => $quantity,
            'subtotal' => $itemTotal
        ];
    }
    
    // ========== 3. APPLIQUER LE COUPON SI PRÉSENT ==========
    
    $finalAmount = $totalAmount;
    $discountAmount = 0;
    $couponId = null;
    $couponCode = null;
    
    if (isset($_POST['coupon_id']) && !empty($_POST['coupon_id'])) {
        $couponId = intval($_POST['coupon_id']);
        $couponCode = $_POST['coupon_code'] ?? '';
        $discountAmount = floatval($_POST['discount_amount'] ?? 0);
        $finalAmount = floatval($_POST['final_amount'] ?? $totalAmount);
        
        // Validation : vérifier que le calcul est cohérent
        if ($finalAmount > $totalAmount) {
            throw new Exception('Invalid coupon calculation');
        }
    }
    
    // ========== 4. VÉRIFIER LE SOLDE UTILISATEUR ==========
    
    require_once __DIR__ . '/../../model/User.php';
    $user = User::getByUsername($currentUsername);
    
    if (!$user) {
        throw new Exception('User not found');
    }
    
    if ($user->getBalance() < $finalAmount) {
        throw new Exception('Insufficient balance');
    }
    
    // ========== 5. METTRE À JOUR LE STOCK ==========
    
    foreach ($purchaseDetails as $detail) {
        $product = $productController->getProductById($detail['product_id']);
        $newStock = $product->getStock() - $detail['quantity'];
        $product->setStock($newStock);
        $productController->updateProduct($product);
    }
    
    // ========== 6. DÉDUIRE LE MONTANT FINAL ==========
    
    $newBalance = $user->getBalance() - $finalAmount;
    $user->setBalance($newBalance);
    
    $stmt = $pdo->prepare("UPDATE users SET balance = :balance WHERE user_id = :user_id");
    $stmt->execute([
        ':balance' => $newBalance,
        ':user_id' => $currentUserId
    ]);
    
    // ========== 7. ENREGISTRER L'HISTORIQUE D'ACHAT ==========
    
    $stmt = $pdo->prepare("
        INSERT INTO purchase_history 
        (user_id, product_ids, quantities, total_amount, discount_amount, final_amount, coupon_code, purchased_at) 
        VALUES 
        (:user_id, :product_ids, :quantities, :total_amount, :discount_amount, :final_amount, :coupon_code, NOW())
    ");
    
    $stmt->execute([
        ':user_id' => $currentUserId,
        ':product_ids' => json_encode($productIds),
        ':quantities' => json_encode($quantities),
        ':total_amount' => $totalAmount,
        ':discount_amount' => $discountAmount,
        ':final_amount' => $finalAmount,
        ':coupon_code' => $couponCode
    ]);
    
    // ========== 8. ENREGISTRER L'UTILISATION DU COUPON ==========
    
    if ($couponId) {
        $couponController = new CouponController();
        
        $couponRecorded = $couponController->recordUsage(
            $couponId,
            $currentUserId,
            $finalAmount,
            $discountAmount
        );
        
        if ($couponRecorded) {
            error_log("✅ Coupon '$couponCode' enregistré pour user $currentUserId");
        } else {
            error_log("⚠️ Échec enregistrement coupon '$couponCode' pour user $currentUserId");
            // Note : On ne fait pas échouer l'achat si le coupon ne s'enregistre pas
        }
    }
    
    // ========== 9. COMMIT ET SUCCÈS ==========
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Purchase completed successfully',
        'details' => [
            'total_items' => count($productIds),
            'original_amount' => number_format($totalAmount, 2),
            'discount' => number_format($discountAmount, 2),
            'final_amount' => number_format($finalAmount, 2),
            'new_balance' => number_format($newBalance, 2),
            'coupon_used' => $couponCode ?? 'None'
        ]
    ]);
    
} catch (Exception $e) {
    // Rollback en cas d'erreur
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Checkout error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>