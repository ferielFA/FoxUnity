<?php
require_once __DIR__ . '/../../controller/UserController.php';
require_once __DIR__ . '/../../model/User.php';

// Check if user is logged in
if (!UserController::isLoggedIn()) {
    header('Location: ../front/login.php');
    exit();
}

$currentUser = UserController::getCurrentUser();
if (!$currentUser) {
    header('Location: ../front/login.php');
    exit();
}

// Handle account deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $currentUser->getId();
    
    // Delete user's profile image if exists
    if ($currentUser->getImage()) {
        $imagePath = __DIR__ . '/../' . $currentUser->getImage();
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
    }
    
    
    // Delete user from database
    if ($currentUser->delete()) {
        // Destroy session
        session_destroy();
        
        // Redirect to homepage with success message
        header('Location: ../front/index.php?deleted=1');
        exit();
    } else {
        // Redirect back to profile with error
        header('Location: ../front/profile.php?error=delete_failed');
        exit();
    }
} else {
    // If not POST request, redirect to profile
    header('Location: ../front/profile.php');
    exit();
}
?>