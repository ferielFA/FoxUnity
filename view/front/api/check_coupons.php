<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../controller/Couponcontroller.php';
require_once __DIR__ . '/../../../controller/UserController.php';

// Check if user is logged in
if (!UserController::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Please log in']);
    exit;
}

try {
    $couponController = new CouponController();
    $userId = $_SESSION['user_id'];
    $availableCoupons = $couponController->getUnusedActiveCoupons($userId);

    echo json_encode([
        'success' => true,
        'count' => count($availableCoupons),
        'message' => count($availableCoupons) > 0 ? 'Coupons available' : 'You used all the available coupons'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error: ' . $e->getMessage()]);
}
