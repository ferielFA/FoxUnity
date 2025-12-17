<?php
// Test script for coupon validation
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/model/config.php';
require_once __DIR__ . '/controller/Couponcontroller.php';
require_once __DIR__ . '/model/Coupon.php';

try {
    $controller = new CouponController();

    // Create a dummy user and coupon if needed, or just test with a random code
    // For now let's try to validate a non-existent coupon to see if it even reaches the DB
    echo "Testing validation...<br>";

    // Attempt to validate a coupon
    $result = $controller->validateCoupon('TESTCODE', 1, 100);

    echo "Result: <pre>" . print_r($result, true) . "</pre>";

} catch (Throwable $e) {
    echo "Caught Error: " . $e->getMessage() . "<br>";
    echo "Trace: <pre>" . $e->getTraceAsString() . "</pre>";
}
?>