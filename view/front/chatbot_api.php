<?php
// Prevent any output before JSON
ob_start();

// Disable error display (errors will be logged instead)
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Set JSON header first
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../../controller/ChatbotController.php';
    require_once __DIR__ . '/../../controller/UserController.php';
    
    // Clear any output buffer content
    ob_clean();
    
    // Only allow POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        exit;
    }
    
    // Get JSON input
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
        exit;
    }
    
    if (!isset($input['message']) || empty(trim($input['message']))) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Message is required']);
        exit;
    }
    
    $userMessage = trim($input['message']);
    $conversationHistory = $input['history'] ?? [];
    
    // Get chatbot response
    $result = ChatbotController::getChatResponse($userMessage, $conversationHistory);
    
    // Optionally save conversation if user is logged in
    if ($result['success'] && UserController::isLoggedIn()) {
        $currentUser = UserController::getCurrentUser();
        ChatbotController::saveConversation(
            $currentUser->getId(),
            $userMessage,
            $result['response']
        );
    }
    
    // Output JSON
    echo json_encode($result);
    
} catch (Exception $e) {
    // Log the error
    error_log("Chatbot API Error: " . $e->getMessage());
    
    // Return JSON error
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'An internal error occurred. Please try again.'
    ]);
} finally {
    // End output buffering and send
    ob_end_flush();
}