<?php
/**
 * API Endpoint: Validate Coupon
 * Usage: POST /api/validate_coupon.php
 * Body: { "coupon_code": "WELCOME10", "total_amount": 50.00 }
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../../controller/UserController.php';
require_once __DIR__ . '/../../../controller/Couponcontroller.php';

// Check authentication
if (!UserController::isLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'You must be logged in to use coupons'
    ]);
    exit();
}

$currentUser = UserController::getCurrentUser();
if (!$currentUser) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid user session'
    ]);
    exit();
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['coupon_code']) || !isset($input['total_amount'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Missing required parameters'
    ]);
    exit();
}

$couponCode = trim($input['coupon_code']);
$totalAmount = floatval($input['total_amount']);

if (empty($couponCode)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Please enter a coupon code'
    ]);
    exit();
}

if ($totalAmount <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid cart total'
    ]);
    exit();
}

// Validate coupon
$couponController = new CouponController();
$result = $couponController->validateCoupon($couponCode, $currentUser->getId(), $totalAmount);

if ($result['success']) {
    echo json_encode([
        'success' => true,
        'coupon_id' => $result['coupon']->getCouponId(),
        'code' => $result['coupon']->getCode(),
        'discount_type' => $result['coupon']->getDiscountType(),
        'discount_value' => $result['coupon']->getDiscountValue(),
        'discount_amount' => $result['discount'],
        'original_amount' => $result['original_amount'],
        'final_amount' => $result['final_amount'],
        'message' => $result['message']
    ]);
} else {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $result['error']
    ]);
}
?>