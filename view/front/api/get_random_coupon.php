<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../controller/Couponcontroller.php';
require_once __DIR__ . '/../../../controller/UserController.php';

// Check if user is logged in (optional, but good for security)
if (!UserController::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Please log in to claim a coupon']);
    exit;
}

try {
    $couponController = new CouponController();
    $userId = $_SESSION['user_id'];
    $availableCoupons = $couponController->getUnusedActiveCoupons($userId);

    if (empty($availableCoupons)) {
        echo json_encode(['success' => false, 'error' => 'You used all the available coupons']);
        exit;
    }

    // Select a random coupon
    $randomIndex = array_rand($availableCoupons);
    $coupon = $availableCoupons[$randomIndex];

    echo json_encode([
        'success' => true,
        'code' => $coupon->getCode(),
        'discount_type' => $coupon->getDiscountType(),
        'discount_value' => $coupon->getDiscountValue(),
        'message' => 'Congratulations! You won a coupon code.'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error fetching coupon: ' . $e->getMessage()]);
}
