<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

require_once __DIR__ . '/../../controller/UserController.php';
require_once __DIR__ . '/../../controller/FaceRecognition.php';
require_once __DIR__ . '/../../controller/FaceConfig.php';
require_once __DIR__ . '/../../model/User.php';

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Get JSON data
$jsonData = file_get_contents('php://input');
$data = json_decode($jsonData, true);

if (!isset($data['image'])) {
    echo json_encode([
        'success' => false,
        'message' => 'No image data provided'
    ]);
    exit;
}

try {
    // Initialize face recognition with API key from config
    $faceRecognition = new FaceRecognition(FaceConfig::MXFACE_API_KEY);
    
    // Get all users with profile pictures
    $allUsers = User::getAll();
    
    error_log("Total users in database: " . count($allUsers));
    
    // Filter only active users with profile pictures
    $usersWithPictures = array_filter($allUsers, function($user) {
        return $user->getStatus() !== 'banned' 
            && $user->getStatus() === 'active' 
            && !empty($user->getImage());
    });
    
    error_log("Users with profile pictures: " . count($usersWithPictures));
    
    if (empty($usersWithPictures)) {
        echo json_encode([
            'success' => false,
            'message' => 'No users with profile pictures found. Please register or upload a profile picture first.'
        ]);
        exit;
    }
    
    // Find matching user (with debug data)
    $result = $faceRecognition->findMatchingUser(
        $data['image'],
        $usersWithPictures,
        FaceConfig::SIMILARITY_THRESHOLD
    );
    
    error_log("Face recognition result: " . json_encode($result));
    
    if ($result['success']) {
        $user = $result['user'];
        
        // Set session variables (same as normal login)
        $_SESSION['user_id'] = $user->getId();
        $_SESSION['username'] = $user->getUsername();
        $_SESSION['email'] = $user->getEmail();
        $_SESSION['role'] = $user->getRole();
        $_SESSION['logged_in'] = true;
        
        error_log("Face login successful for user: " . $user->getUsername());
        
        // Determine redirect URL based on role
        $redirectUrl = 'index.php';
        if ($user->getRole() === 'Admin' || $user->getRole() === 'SuperAdmin') {
            $redirectUrl = '../back/dashboard.php';
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Face recognized! Logging you in...',
            'redirect' => $redirectUrl,
            'username' => $user->getUsername(),
            'confidence' => round($result['confidence']),
            'debug' => $result['debug'] ?? null
        ]);
    } else {
        error_log("Face recognition failed: " . $result['message']);
        
        echo json_encode([
            'success' => false,
            'message' => $result['message'],
            'debug' => $result['debug'] ?? null
        ]);
    }
    
} catch (Exception $e) {
    error_log("Face login error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred during face recognition. Please try again.',
        'error_details' => $e->getMessage()
    ]);
}
?>