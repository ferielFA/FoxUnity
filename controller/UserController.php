<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../model/User.php';

class UserController {
    
    // Register new user
    public function register($username, $email, $dob, $password, $confirmPassword, $gender) {
        $errors = [];
        
        // Validation
        if (empty($username) || empty($email) || empty($dob) || empty($password) || empty($gender)) {
            $errors[] = "All fields are required.";
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format.";
        }
        
        if (strlen($password) < 6) {
            $errors[] = "Password must be at least 6 characters long.";
        }
        
        if ($password !== $confirmPassword) {
            $errors[] = "Passwords do not match.";
        }
        
        if (!in_array($gender, ['Male', 'Female'])) {
            $errors[] = "Please select a valid gender.";
        }
        
        if (User::emailExists($email)) {
            $errors[] = "Email already registered.";
        }
        
        if (User::usernameExists($username)) {
            $errors[] = "Username already taken.";
        }
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Create user
        $user = new User(null, $username, $email, $dob, $password, $gender, 'Gamer', 'active', null);
        
        if ($user->create()) {
            return ['success' => true, 'message' => 'Registration successful! Please login.'];
        } else {
            return ['success' => false, 'errors' => ['Failed to create account. Please try again.']];
        }
    }
    
    // Login user
    public function login($email, $password) {
        $errors = [];
        
        // Validation
        if (empty($email) || empty($password)) {
            $errors[] = "Email and password are required.";
            return ['success' => false, 'errors' => $errors];
        }
        
        // Get user by email
        $user = User::getByEmail($email);
        
        if (!$user) {
            $errors[] = "Invalid email or password.";
            return ['success' => false, 'errors' => $errors];
        }
        
        // Verify password
        if (!$user->verifyPassword($password)) {
            $errors[] = "Invalid email or password.";
            return ['success' => false, 'errors' => $errors];
        }
        
        // Check if account is banned
        if ($user->getStatus() === 'banned') {
            $errors[] = "ACCOUNT_BANNED"; // Special error code
            return ['success' => false, 'errors' => $errors, 'banned' => true];
        }
        
        // Set session variables
        $_SESSION['user_id'] = $user->getId();
        $_SESSION['username'] = $user->getUsername();
        $_SESSION['email'] = $user->getEmail();
        $_SESSION['role'] = $user->getRole();
        $_SESSION['logged_in'] = true;
        
        // Determine redirect URL based on role
        $redirectUrl = 'index.php'; // Default for Gamer
        if ($user->getRole() === 'Admin') {
            $redirectUrl = '../back/dashboard.php';
        }
        
        return ['success' => true, 'message' => 'Login successful!', 'user' => $user, 'redirect' => $redirectUrl];
    }
    
    // Logout user
    public function logout() {
        session_unset();
        session_destroy();
        return ['success' => true, 'message' => 'Logged out successfully.'];
    }
    
    // Check if user is logged in
    public static function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    // Get current logged in user
    public static function getCurrentUser() {
        if (self::isLoggedIn() && isset($_SESSION['user_id'])) {
            return User::getById($_SESSION['user_id']);
        }
        return null;
    }
    
    // Update user profile
    public function updateProfile($userId, $username, $email, $dob, $image = null) {
        $errors = [];
        
        // Get current user
        $user = User::getById($userId);
        
        if (!$user) {
            $errors[] = "User not found.";
            return ['success' => false, 'errors' => $errors];
        }
        
        // Validation
        if (empty($username) || empty($email) || empty($dob)) {
            $errors[] = "Username, email, and date of birth are required.";
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format.";
        }
        
        // Check if email is taken by another user
        if ($email !== $user->getEmail() && User::emailExists($email)) {
            $errors[] = "Email already registered to another account.";
        }
        
        // Check if username is taken by another user
        if ($username !== $user->getUsername() && User::usernameExists($username)) {
            $errors[] = "Username already taken.";
        }
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Update user data
        $user->setUsername($username);
        $user->setEmail($email);
        $user->setDob($dob);
        
        if ($image !== null) {
            $user->setImage($image);
        }
        
        if ($user->update()) {
            // Update session variables
            $_SESSION['username'] = $username;
            $_SESSION['email'] = $email;
            
            return ['success' => true, 'message' => 'Profile updated successfully!'];
        } else {
            return ['success' => false, 'errors' => ['Failed to update profile. Please try again.']];
        }
    }
    
    // Handle file upload for profile image
    public function uploadProfileImage($file) {
        $errors = [];
        
        // Check if file was uploaded
        if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return ['success' => false, 'errors' => ['No file uploaded.']];
        }
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Error uploading file.";
            return ['success' => false, 'errors' => $errors];
        }
        
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file['type'], $allowedTypes)) {
            $errors[] = "Invalid file type. Only JPG, PNG, GIF, and WEBP are allowed.";
            return ['success' => false, 'errors' => $errors];
        }
        
        // Validate file size (max 5MB)
        $maxSize = 5 * 1024 * 1024; // 5MB in bytes
        if ($file['size'] > $maxSize) {
            $errors[] = "File size too large. Maximum size is 5MB.";
            return ['success' => false, 'errors' => $errors];
        }
        
        // Create uploads directory if it doesn't exist
        $uploadDir = __DIR__ . '/../view/uploads/profiles/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('profile_') . '.' . $extension;
        $filepath = $uploadDir . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            return ['success' => true, 'filename' => 'uploads/profiles/' . $filename];
        } else {
            return ['success' => false, 'errors' => ['Failed to save uploaded file.']];
        }
    }
    
    // Change password
    public function changePassword($userId, $currentPassword, $newPassword, $confirmPassword) {
        $errors = [];
        
        // Get user
        $user = User::getById($userId);
        
        if (!$user) {
            $errors[] = "User not found.";
            return ['success' => false, 'errors' => $errors];
        }
        
        // Validate current password
        if (!$user->verifyPassword($currentPassword)) {
            $errors[] = "Current password is incorrect.";
        }
        
        // Validate new password
        if (strlen($newPassword) < 6) {
            $errors[] = "New password must be at least 6 characters long.";
        }
        
        if ($newPassword !== $confirmPassword) {
            $errors[] = "New passwords do not match.";
        }
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Update password
        if ($user->updatePassword($newPassword)) {
            return ['success' => true, 'message' => 'Password changed successfully!'];
        } else {
            return ['success' => false, 'errors' => ['Failed to change password. Please try again.']];
        }
    }
}
?>