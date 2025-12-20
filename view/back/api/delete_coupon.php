<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../../controller/UserController.php';
require_once __DIR__ . '/../../../controller/Couponcontroller.php';

// Check Admin Access
if (!UserController::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$currentUser = UserController::getCurrentUser();
$userRole = strtolower($currentUser ? $currentUser->getRole() : '');
if (!$currentUser || ($userRole !== 'admin' && $userRole !== 'superadmin')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Forbidden']);
    exit();
}

// Handle Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' || (isset($_GET['id']) && $_SERVER['REQUEST_METHOD'] === 'GET')) {
    $couponId = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
    
    if ($couponId <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid coupon ID']);
        exit;
    }

    $couponController = new CouponController();
    if ($couponController->deleteCoupon($couponId)) {
        echo json_encode(['success' => true, 'message' => 'Coupon deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to delete coupon from database']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
}
