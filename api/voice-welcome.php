<?php
/**
 * Text-to-Speech API for Trade Master
 * Generates welcome voice message using ElevenLabs API
 */

header('Content-Type: application/json');
session_start();

// Include dependencies
require_once __DIR__ . '/../model/config.php';
require_once __DIR__ . '/../model/User.php';

/**
 * Authenticate user from session
 */
function authenticateUser(): ?array {
    if (!isset($_SESSION['username'])) {
        return null;
    }
    
    $username = $_SESSION['username'];
    $userObj = User::getByUsername($username);
    
    if (!$userObj || $userObj->getStatus() !== 'active') {
        return null;
    }
    
    return [
        'id' => $userObj->getId(),
        'username' => $username
    ];
}

// Authenticate
$user = authenticateUser();
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

// ElevenLabs API Configuration
// Get your free API key from: https://elevenlabs.io/
$ELEVENLABS_API_KEY = '';
$VOICE_ID = 'pNInz6obpgDQGcFmaJgB'; // Adam - Professional male voice

// Generate welcome message
$text = "Welcome, " . $user['username'] . ". The Trade Master is ready to assist you.";

// Call ElevenLabs API
$url = "https://api.elevenlabs.io/v1/text-to-speech/{$VOICE_ID}";

$data = [
    'text' => $text,
    'model_id' => 'eleven_turbo_v2_5',
    'voice_settings' => [
        'stability' => 0.5,
        'similarity_boost' => 0.75
    ]
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'xi-api-key: ' . $ELEVENLABS_API_KEY
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// Log for debugging
error_log("Voice API Response Code: $httpCode");
if ($curlError) {
    error_log("Voice API Curl Error: $curlError");
}

if ($httpCode === 200) {
    // Return audio as base64
    $audioBase64 = base64_encode($response);
    echo json_encode([
        'success' => true,
        'audio' => $audioBase64,
        'format' => 'mp3'
    ]);
} else {
    // Decode error response from API
    $errorResponse = json_decode($response, true);
    $errorMessage = $errorResponse['detail']['message'] ?? 'Failed to generate voice';
    
    error_log("Voice API Error Response: " . print_r($errorResponse, true));
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $errorMessage,
        'http_code' => $httpCode,
        'curl_error' => $curlError
    ]);
}
