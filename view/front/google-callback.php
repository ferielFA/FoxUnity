<?php
// ⚠️ CRITICAL: Start session FIRST!
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../controller/GoogleConfig.php';
require_once __DIR__ . '/../../controller/UserController.php';
require_once __DIR__ . '/../../model/User.php';

// Check if code is present
if (!isset($_GET['code'])) {
    header('Location: Login.php?error=no_code');
    exit();
}

// Authenticate with Google
$result = GoogleConfig::authenticate($_GET['code']);

if (!$result['success']) {
    header('Location: Login.php?error=google_auth_failed');
    exit();
}

// Google authentication successful
$googleId = $result['google_id'];
$email = $result['email'];
$name = $result['name'];
$picture = $result['picture']; // Photo Google

// Check if user exists with this Google ID
$user = User::getByGoogleId($googleId);

if ($user) {
    // User exists - login directly
    $_SESSION['user_id'] = $user->getId();
    $_SESSION['username'] = $user->getUsername();
    $_SESSION['email'] = $user->getEmail();
    $_SESSION['role'] = $user->getRole();
    $_SESSION['logged_in'] = true;
    
    // Redirect based on role
    if ($user->getRole() === 'SuperAdmin' || $user->getRole() === 'Admin') {
        header('Location: ../back/dashboard.php');
    } else {
        header('Location: index.php');
    }
    exit();
} else {
    // Check if email already exists (registered normally)
    $existingUser = User::getByEmail($email);
    
    if ($existingUser) {
        // Email exists - link Google ID to existing account
        $updated = User::linkGoogleAccount($existingUser->getId(), $googleId, $picture);
        
        if ($updated) {
            // Login with linked account
            $_SESSION['user_id'] = $existingUser->getId();
            $_SESSION['username'] = $existingUser->getUsername();
            $_SESSION['email'] = $existingUser->getEmail();
            $_SESSION['role'] = $existingUser->getRole();
            $_SESSION['logged_in'] = true;
            
            if ($existingUser->getRole() === 'SuperAdmin' || $existingUser->getRole() === 'Admin') {
                header('Location: ../back/dashboard.php');
            } else {
                header('Location: index.php');
            }
            exit();
        } else {
            header('Location: Login.php?error=link_failed');
            exit();
        }
    } else {
        // New user - create account automatically
        $controller = new UserController();
        $result = $controller->registerWithGoogle($googleId, $email, $name, $picture);
        
        if ($result['success']) {
            // Auto-login after registration
            $newUser = User::getByEmail($email);
            
            $_SESSION['user_id'] = $newUser->getId();
            $_SESSION['username'] = $newUser->getUsername();
            $_SESSION['email'] = $newUser->getEmail();
            $_SESSION['role'] = $newUser->getRole();
            $_SESSION['logged_in'] = true;
            
            header('Location: index.php?welcome=1');
            exit();
        } else {
            header('Location: Login.php?error=registration_failed');
            exit();
        }
    }
}
?>