<?php
// Enable error logging for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/chatbot_errors.log');

error_log("===== CHATBOT HANDLER START =====");

// Set JSON header first
header('Content-Type: application/json');

try {
    error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        error_log("Invalid request method");
        echo json_encode(['success' => false, 'message' => 'Invalid request method']);
        exit;
    }

    $rawInput = file_get_contents('php://input');
    error_log("Raw input: " . $rawInput);
    
    $data = json_decode($rawInput, true);
    error_log("Decoded data: " . print_r($data, true));

    if (!isset($data['message']) || empty($data['message'])) {
        error_log("Message is empty or not set");
        echo json_encode(['success' => false, 'message' => 'Message is required']);
        exit;
    }

    $userMessage = $data['message'];
    error_log("User message: " . $userMessage);
    
    // Try to create controller - catch if database fails
    try {
        error_log("Loading FoxyEventsController...");
        require_once __DIR__ . '/../../controller/FoxyEventsController.php';
        
        error_log("Creating FoxyEventsController instance...");
        $foxyController = new FoxyEventsController();
        
        error_log("Processing message...");
        // Process message and get response
        $response = $foxyController->processMessage($userMessage);
        
        error_log("Response: " . print_r($response, true));
        
        $jsonResponse = json_encode($response);
        error_log("JSON response: " . $jsonResponse);
        error_log("JSON last error: " . json_last_error_msg());
        
        echo $jsonResponse;
        
    } catch (Exception $e) {
        error_log("Chatbot Error: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        echo json_encode([
            'success' => false, 
            'message' => '❌ Une erreur est survenue. Vérifiez que XAMPP MySQL est démarré!'
        ]);
    }

} catch (Exception $e) {
    error_log("Chatbot Fatal Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode([
        'success' => false, 
        'message' => 'Une erreur est survenue. Veuillez réessayer.'
    ]);
}

error_log("===== CHATBOT HANDLER END =====");
