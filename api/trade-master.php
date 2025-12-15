<?php
/**
 * Trade Master REST API
 * Provides analytics and AI recommendation endpoints
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in production
ini_set('log_errors', 1);

// Set JSON response header
header('Content-Type: application/json');

// CORS headers (optional - enable if needed for external access)
// header('Access-Control-Allow-Origin: *');
// header('Access-Control-Allow-Methods: GET, OPTIONS');
// header('Access-Control-Allow-Headers: Content-Type');

// Handle OPTIONS preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Start session for authentication
session_start();

// Include dependencies
require_once __DIR__ . '/../model/config.php';
require_once __DIR__ . '/../model/User.php';
require_once __DIR__ . '/../controller/TradingController.php';
require_once __DIR__ . '/../controller/TradeHistoryController.php';

/**
 * Send JSON response with proper HTTP status code
 */
function sendResponse(int $statusCode, array $data): void {
    http_response_code($statusCode);
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit();
}

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

// Main API logic
try {
    // Authenticate user
    $user = authenticateUser();
    if (!$user) {
        sendResponse(401, [
            'success' => false,
            'error' => 'Unauthorized. Please log in.',
            'code' => 'AUTH_REQUIRED'
        ]);
    }
    
    // Get action parameter
    $action = $_GET['action'] ?? '';
    
    if (empty($action)) {
        sendResponse(400, [
            'success' => false,
            'error' => 'Missing required parameter: action',
            'code' => 'MISSING_PARAMETER',
            'available_actions' => ['analytics', 'recommend']
        ]);
    }
    
    // Initialize controllers
    $tradingController = new TradingController($user['username']);
    $tradeHistoryController = new TradeHistoryController();
    
    // Route to appropriate action
    switch ($action) {
        case 'analytics':
            // Get user analytics
            $result = $tradeHistoryController->getUserAnalytics($user['id']);
            
            if ($result['success']) {
                sendResponse(200, [
                    'success' => true,
                    'data' => [
                        'total_trades' => $result['total_trades'],
                        'total_spent' => $result['total_spent'],
                        'favorite_game' => $result['favorite_game']
                    ],
                    'timestamp' => date('c')
                ]);
            } else {
                sendResponse(500, [
                    'success' => false,
                    'error' => $result['error'] ?? 'Failed to fetch analytics',
                    'code' => 'ANALYTICS_ERROR'
                ]);
            }
            break;
            
        case 'recommend':
            // Get AI recommendation directly
            // Use reflection to access the private method
            $reflection = new ReflectionClass($tradingController);
            $method = $reflection->getMethod('handleAIPickSkin');
            $method->setAccessible(true);
            $result = $method->invoke($tradingController);
            
            if ($result && $result['success']) {
                sendResponse(200, [
                    'success' => true,
                    'data' => [
                        'skin' => $result['skin'],
                        'reason' => $result['reason']
                    ],
                    'timestamp' => date('c')
                ]);
            } else {
                sendResponse(500, [
                    'success' => false,
                    'error' => $result['error'] ?? 'Failed to generate recommendation',
                    'code' => 'RECOMMENDATION_ERROR'
                ]);
            }
            break;
            
        default:
            sendResponse(400, [
                'success' => false,
                'error' => 'Invalid action: ' . $action,
                'code' => 'INVALID_ACTION',
                'available_actions' => ['analytics', 'recommend']
            ]);
    }
    
} catch (Exception $e) {
    error_log('Trade Master API Error: ' . $e->getMessage());
    sendResponse(500, [
        'success' => false,
        'error' => 'Internal server error',
        'code' => 'INTERNAL_ERROR',
        'message' => $e->getMessage() // Remove in production
    ]);
}
