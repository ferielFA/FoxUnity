<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../../model/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

// Get the POST data
$data = json_decode(file_get_contents('php://input'), true);
$amount = $data['amount'] ?? 0;
$currency = 'usd';

if ($amount <= 0) {
    echo json_encode(['error' => 'Invalid amount']);
    exit;
}

// STRIPE CONFIGURATION
// IMPORTANT: Replace this with your actual Stripe Secret Key from your dashboard
// You can get it at https://dashboard.stripe.com/test/apikeys
$stripeSecretKey = 'sk_test_YOUR_ACTUAL_SECRET_KEY'; 

function createStripePaymentIntent($amount, $currency, $secretKey) {
    $url = 'https://api.stripe.com/v1/payment_intents';
    
    // Stripe expects amount in cents
    $amountInCents = round($amount * 100);
    
    $fields = [
        'amount' => $amountInCents,
        'currency' => $currency,
        'payment_method_types[]' => 'card',
        'description' => 'FoxUnity Gaming Purchase'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
    curl_setopt($ch, CURLOPT_USERPWD, $secretKey . ':');
    
    // SSL bypass for local testing if needed
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'body' => json_decode($response, true)
    ];
}

$result = null;
if ($stripeSecretKey === 'sk_test_YOUR_ACTUAL_SECRET_KEY') {
    // DEMO MODE: If no real key is provided, simulate a successful response
    echo json_encode([
        'success' => true,
        'clientSecret' => 'mock_secret_' . bin2hex(random_bytes(16)),
        'demo_mode' => true
    ]);
    exit;
} else {
    $result = createStripePaymentIntent($amount, $currency, $stripeSecretKey);
}

if ($result && $result['code'] === 200) {
    echo json_encode([
        'success' => true,
        'clientSecret' => $result['body']['client_secret']
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => $result['body']['error']['message'] ?? 'Failed to create payment intent'
    ]);
}
