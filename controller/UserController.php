<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../model/User.php';
require_once __DIR__ . '/EmailConfig.php';

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
        
        // Create user with 'pending' status (not verified yet)
        $user = new User(null, $username, $email, $dob, $password, $gender, 'Gamer', 'pending', null);
        
        if ($user->create()) {
            // Send verification email
            $emailResult = $this->sendVerificationEmail($user->getId(), $email, $username);
            
            if ($emailResult['success']) {
                return [
                    'success' => true, 
                    'message' => 'Registration successful! Please check your email to verify your account before logging in.'
                ];
            } else {
                return [
                    'success' => true, 
                    'message' => 'Registration successful! However, we couldn\'t send the verification email. Error: ' . ($emailResult['errors'][0] ?? 'Unknown error')
                ];
            }
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
            $errors[] = "ACCOUNT_BANNED";
            return ['success' => false, 'errors' => $errors, 'banned' => true];
        }
        
        // Check if account is verified
        if ($user->getStatus() === 'pending') {
            $errors[] = "EMAIL_NOT_VERIFIED";
            return ['success' => false, 'errors' => $errors, 'notVerified' => true];
        }
        
        // Set session variables
        $_SESSION['user_id'] = $user->getId();
        $_SESSION['username'] = $user->getUsername();
        $_SESSION['email'] = $user->getEmail();
        $_SESSION['role'] = $user->getRole();
        $_SESSION['logged_in'] = true;
        
        // Determine redirect URL based on role
        $redirectUrl = 'index.php';
        if ($user->getRole() === 'Admin' || $user->getRole() === 'SuperAdmin') {
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
    public function updateProfile($userId, $username, $email, $dob, $image = null, $gender = null) {
        $errors = [];
        
        $user = User::getById($userId);
        
        if (!$user) {
            $errors[] = "User not found.";
            return ['success' => false, 'errors' => $errors];
        }
        
        if (empty($username) || empty($email) || empty($dob)) {
            $errors[] = "Username, email, and date of birth are required.";
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format.";
        }
        
        if ($email !== $user->getEmail() && User::emailExists($email)) {
            $errors[] = "Email already registered to another account.";
        }
        
        if ($username !== $user->getUsername() && User::usernameExists($username)) {
            $errors[] = "Username already taken.";
        }
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        $user->setUsername($username);
        $user->setEmail($email);
        $user->setDob($dob);
        
        if ($image !== null) {
            $user->setImage($image);
        }
        
        if ($gender !== null) {
            $user->setGender($gender);
        }
        
        if ($user->update()) {
            $_SESSION['username'] = $username;
            $_SESSION['email'] = $email;
            
            return ['success' => true, 'message' => 'Profile updated successfully!'];
        } else {
            return ['success' => false, 'errors' => ['Failed to update profile. Please try again.']];
        }
    }
    
    // Update admin profile
    public function updateAdminProfile($userId, $username, $email, $dob, $gender, $image = null) {
        $errors = [];
        
        $user = User::getById($userId);
        
        if (!$user) {
            $errors[] = "User not found.";
            return ['success' => false, 'errors' => $errors];
        }
        
        if (empty($username) || empty($email) || empty($dob)) {
            $errors[] = "Username, email, and date of birth are required.";
        }
        
        if (empty($gender)) {
            $errors[] = "Gender is required.";
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format.";
        }
        
        if ($email !== $user->getEmail() && User::emailExists($email)) {
            $errors[] = "Email already registered to another account.";
        }
        
        if ($username !== $user->getUsername() && User::usernameExists($username)) {
            $errors[] = "Username already taken.";
        }
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        $user->setUsername($username);
        $user->setEmail($email);
        $user->setDob($dob);
        $user->setGender($gender);
        
        if ($image !== null) {
            $user->setImage($image);
        }
        
        if ($user->update()) {
            $_SESSION['username'] = $username;
            $_SESSION['email'] = $email;
            $_SESSION['user'] = serialize($user);
            
            return ['success' => true, 'message' => 'Profile updated successfully!'];
        } else {
            return ['success' => false, 'errors' => ['Failed to update profile. Please try again.']];
        }
    }
    
    // Handle file upload for profile image
    public function uploadProfileImage($file) {
        $errors = [];
        
        if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return ['success' => false, 'errors' => ['No file uploaded.']];
        }
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = "Error uploading file.";
            return ['success' => false, 'errors' => $errors];
        }
        
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file['type'], $allowedTypes)) {
            $errors[] = "Invalid file type. Only JPG, PNG, GIF, and WEBP are allowed.";
            return ['success' => false, 'errors' => $errors];
        }
        
        $maxSize = 5 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            $errors[] = "File size too large. Maximum size is 5MB.";
            return ['success' => false, 'errors' => $errors];
        }
        
        $uploadDir = __DIR__ . '/../view/uploads/profiles/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('profile_') . '.' . $extension;
        $filepath = $uploadDir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            return ['success' => true, 'filename' => 'uploads/profiles/' . $filename];
        } else {
            return ['success' => false, 'errors' => ['Failed to save uploaded file.']];
        }
    }
    
    // Change password
    public function changePassword($userId, $currentPassword, $newPassword, $confirmPassword) {
        $errors = [];
        
        $user = User::getById($userId);
        
        if (!$user) {
            $errors[] = "User not found.";
            return ['success' => false, 'errors' => $errors];
        }
        
        if (!$user->verifyPassword($currentPassword)) {
            $errors[] = "Current password is incorrect.";
        }
        
        if (strlen($newPassword) < 6) {
            $errors[] = "New password must be at least 6 characters long.";
        }
        
        if ($newPassword !== $confirmPassword) {
            $errors[] = "New passwords do not match.";
        }
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        if ($user->updatePassword($newPassword)) {
            return ['success' => true, 'message' => 'Password changed successfully!'];
        } else {
            return ['success' => false, 'errors' => ['Failed to change password. Please try again.']];
        }
    }
    
    // ✅ EMAIL VERIFICATION - Send verification email with PHPMailer
    public function sendVerificationEmail($userId, $email, $username) {
        // Generate unique token
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+3 minutes'));
        
        // Save token to database
        if (!User::saveVerificationToken($userId, $token, $expires)) {
            return ['success' => false, 'errors' => ['Failed to generate verification token.']];
        }
        
        // Create verification link
        $verificationLink = EmailConfig::SITE_URL . "/view/front/verify-email.php?token=" . $token;
        
        try {
            // Get PHPMailer instance
            $mail = EmailConfig::getMailer();
            
            if (!$mail) {
                return ['success' => false, 'errors' => ['Email configuration error.']];
            }
            
            // Recipients
            $mail->addAddress($email, $username);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'FoxUnity - Verify Your Email Address';
            $mail->Body = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
                    .container { max-width: 600px; margin: 40px auto; background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
                    .header { background: linear-gradient(135deg, #ff7a00, #ff4f00); padding: 30px; text-align: center; }
                    .header h1 { color: #fff; margin: 0; font-size: 28px; }
                    .content { padding: 40px 30px; }
                    .content p { color: #333; line-height: 1.6; font-size: 16px; }
                    .button { display: inline-block; padding: 15px 40px; background: linear-gradient(135deg, #ff7a00, #ff4f00); color: #fff; text-decoration: none; border-radius: 25px; font-weight: bold; margin: 20px 0; }
                    .footer { background: #f4f4f4; padding: 20px; text-align: center; color: #888; font-size: 12px; }
                    .link { word-break: break-all; color: #ff7a00; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>🦊 FoxUnity</h1>
                    </div>
                    <div class='content'>
                        <h2>Welcome to FoxUnity, " . htmlspecialchars($username) . "!</h2>
                        <p>Thank you for registering. Please verify your email address to activate your account.</p>
                        <p>Click the button below to verify your email:</p>
                        <center>
                            <a href='" . $verificationLink . "' class='button'>Verify Email Address</a>
                        </center>
                        <p>Or copy and paste this link into your browser:</p>
                        <p class='link'>" . $verificationLink . "</p>
                        <p><strong>This link will expire in 3 minutes.</strong></p>
                        <p>If you didn't create an account with FoxUnity, please ignore this email.</p>
                    </div>
                    <div class='footer'>
                        <p>© 2025 FoxUnity. All rights reserved.</p>
                        <p>Gaming for Good - Every action makes a difference</p>
                    </div>
                </div>
            </body>
            </html>
            ";
            
            $mail->AltBody = "Welcome to FoxUnity, $username!\n\n";
            $mail->AltBody .= "Please verify your email address by clicking this link:\n";
            $mail->AltBody .= "$verificationLink\n\n";
            $mail->AltBody .= "This link will expire in +3 minutes.\n\n";
            $mail->AltBody .= "If you didn't create an account with FoxUnity, please ignore this email.";
            
            // Send email
            $mail->send();
            return ['success' => true, 'message' => 'Verification email sent successfully!'];
            
        } catch (Exception $e) {
            error_log("Email sending error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Failed to send verification email: ' . $mail->ErrorInfo]];
        }
    }
    
    // ✅ EMAIL VERIFICATION - Verify email with token
    public function verifyEmail($token) {
        $errors = [];
        
        if (empty($token)) {
            $errors[] = "Invalid verification token.";
            return ['success' => false, 'errors' => $errors];
        }
        
        $result = User::verifyEmailToken($token);
        
        if ($result) {
            return ['success' => true, 'message' => 'Your email has been verified successfully! You can now login to your account.'];
        } else {
            $errors[] = "Invalid or expired verification token. Please request a new verification email.";
            return ['success' => false, 'errors' => $errors];
        }
    }
    
    // ✅ FORGOT PASSWORD - Send password reset email
    public function sendPasswordResetEmail($userId, $email, $username) {
        // Generate unique token
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+3 minutes'));
        
        // Save token to database
        if (!User::savePasswordResetToken($userId, $token, $expires)) {
            return ['success' => false, 'errors' => ['Failed to generate reset token.']];
        }
        
        // Create reset link
        $resetLink = EmailConfig::SITE_URL . "/view/front/reset-password.php?token=" . $token;
        
        try {
            // Get PHPMailer instance
            $mail = EmailConfig::getMailer();
            
            if (!$mail) {
                return ['success' => false, 'errors' => ['Email configuration error.']];
            }
            
            // Recipients
            $mail->addAddress($email, $username);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'FoxUnity - Password Reset Request';
            $mail->Body = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
                    .container { max-width: 600px; margin: 40px auto; background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
                    .header { background: linear-gradient(135deg, #ff7a00, #ff4f00); padding: 30px; text-align: center; }
                    .header h1 { color: #fff; margin: 0; font-size: 28px; }
                    .content { padding: 40px 30px; }
                    .content p { color: #333; line-height: 1.6; font-size: 16px; }
                    .button { display: inline-block; padding: 15px 40px; background: linear-gradient(135deg, #ff7a00, #ff4f00); color: #fff; text-decoration: none; border-radius: 25px; font-weight: bold; margin: 20px 0; }
                    .footer { background: #f4f4f4; padding: 20px; text-align: center; color: #888; font-size: 12px; }
                    .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; }
                    .link { word-break: break-all; color: #ff7a00; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>🦊 FoxUnity</h1>
                    </div>
                    <div class='content'>
                        <h2>Password Reset Request</h2>
                        <p>Hello " . htmlspecialchars($username) . ",</p>
                        <p>We received a request to reset your password. Click the button below to create a new password:</p>
                        <center>
                            <a href='" . $resetLink . "' class='button'>Reset Password</a>
                        </center>
                        <p>Or copy and paste this link into your browser:</p>
                        <p class='link'>" . $resetLink . "</p>
                        <div class='warning'>
                            <p style='margin: 0;'><strong>⚠️ Security Notice:</strong></p>
                            <p style='margin: 5px 0 0 0;'>This link will expire in 3 minutes for your security.</p>
                            <p style='margin: 5px 0 0 0;'>If you didn't request this password reset, please ignore this email and your password will remain unchanged.</p>
                        </div>
                    </div>
                    <div class='footer'>
                        <p>© 2025 FoxUnity. All rights reserved.</p>
                        <p>Gaming for Good - Every action makes a difference</p>
                    </div>
                </div>
            </body>
            </html>
            ";
            
            $mail->AltBody = "Hello $username,\n\n";
            $mail->AltBody .= "We received a request to reset your password.\n\n";
            $mail->AltBody .= "Click this link to reset your password:\n";
            $mail->AltBody .= "$resetLink\n\n";
            $mail->AltBody .= "This link will expire in 3 minutes.\n\n";
            $mail->AltBody .= "If you didn't request this password reset, please ignore this email.";
            
            // Send email
            $mail->send();
            return ['success' => true, 'message' => 'Password reset email sent successfully!'];
            
        } catch (Exception $e) {
            error_log("Email sending error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['Failed to send reset email: ' . $mail->ErrorInfo]];
        }
    }
    
    // ✅ FORGOT PASSWORD - Reset password with token
    public function resetPassword($token, $newPassword) {
        $errors = [];
        
        if (empty($token)) {
            $errors[] = "Invalid reset token.";
            return ['success' => false, 'errors' => $errors];
        }
        
        if (strlen($newPassword) < 8) {
            $errors[] = "Password must be at least 8 characters long.";
            return ['success' => false, 'errors' => $errors];
        }
        
        // Verify token and reset password
        $result = User::resetPasswordWithToken($token, $newPassword);
        
        if ($result) {
            return ['success' => true, 'message' => 'Password reset successfully!'];
        } else {
            $errors[] = "Invalid or expired reset token.";
            return ['success' => false, 'errors' => $errors];
        }
    }
    
    // ✅ GOOGLE LOGIN - Register with Google
    public function registerWithGoogle($googleId, $email, $name, $picture = null) {
        $errors = [];
        
        // Generate username from name
        $username = strtolower(str_replace(' ', '', $name));
        $username = preg_replace('/[^a-z0-9]/', '', $username);
        
        // Check if username exists, add number if needed
        $baseUsername = $username;
        $counter = 1;
        while (User::getByUsername($username)) {
            $username = $baseUsername . $counter;
            $counter++;
        }
        
        // Create user with Google
        $userId = User::createGoogleUser($googleId, $email, $username, $picture);
        
        if ($userId) {
            return [
                'success' => true, 
                'message' => 'Account created successfully with Google!',
                'user_id' => $userId
            ];
        } else {
            $errors[] = "Failed to create account. Please try again.";
            return ['success' => false, 'errors' => $errors];
        }
    }
}
?>