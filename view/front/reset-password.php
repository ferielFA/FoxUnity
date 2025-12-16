<?php
require_once __DIR__ . '/../../controller/UserController.php';

$message = '';
$messageType = '';
$tokenValid = false;
$token = $_GET['token'] ?? '';

// Verify token
if (empty($token)) {
    $message = 'Invalid reset link. Please request a new password reset.';
    $messageType = 'error';
} else {
    // Check if token is valid
    $tokenData = User::getPasswordResetToken($token);
    
    if (!$tokenData) {
        $message = 'Invalid or expired reset link. Please request a new password reset.';
        $messageType = 'error';
    } else {
        $tokenValid = true;
    }
}

// Handle password reset submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenValid) {
    $newPassword = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($newPassword) || empty($confirmPassword)) {
        $message = 'Please fill in all fields.';
        $messageType = 'error';
    } elseif (strlen($newPassword) < 8) {
        $message = 'Password must be at least 8 characters long.';
        $messageType = 'error';
    } elseif ($newPassword !== $confirmPassword) {
        $message = 'Passwords do not match.';
        $messageType = 'error';
    } else {
        // Reset password
        $controller = new UserController();
        $result = $controller->resetPassword($token, $newPassword);
        
        if ($result['success']) {
            $message = 'Password reset successfully! You can now login with your new password.';
            $messageType = 'success';
            $tokenValid = false; // Don't show form anymore
        } else {
            $message = $result['errors'][0] ?? 'Failed to reset password. Please try again.';
            $messageType = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - FoxUnity</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Orbitron:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            box-sizing: border-box;
        }

        .reset-container {
            background: rgba(20, 20, 20, 0.95);
            border-radius: 25px;
            box-shadow: 0 14px 28px rgba(255, 122, 0, 0.25), 
                        0 10px 10px rgba(0, 0, 0, 0.22);
            border: 2px solid rgba(255, 122, 0, 0.3);
            padding: 50px 40px;
            text-align: center;
            max-width: 500px;
            width: 100%;
            position: relative;
            z-index: 10;
        }

        .icon-container {
            margin-bottom: 25px;
        }

        .icon-circle {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 50px;
            animation: scaleIn 0.5s ease;
        }

        .icon-circle.success {
            background: rgba(76, 175, 80, 0.1);
            border: 3px solid #4caf50;
            color: #4caf50;
        }

        .icon-circle.error {
            background: rgba(244, 67, 54, 0.1);
            border: 3px solid #f44336;
            color: #f44336;
        }

        .icon-circle.default {
            background: rgba(255, 122, 0, 0.1);
            border: 3px solid #ff7a00;
            color: #ff7a00;
        }

        @keyframes scaleIn {
            from {
                transform: scale(0);
                opacity: 0;
            }
            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        .reset-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 32px;
            color: #fff;
            margin: 20px 0 15px;
        }

        .reset-subtitle {
            font-size: 15px;
            color: #aaa;
            line-height: 1.6;
            margin-bottom: 30px;
        }

        .message-box {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideDown 0.3s ease;
        }

        .message-box.success {
            background: rgba(76, 175, 80, 0.1);
            border: 2px solid #4caf50;
            color: #4caf50;
        }

        .message-box.error {
            background: rgba(244, 67, 54, 0.1);
            border: 2px solid #f44336;
            color: #f44336;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-10px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .input-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-label {
            display: block;
            color: #fff;
            font-weight: 600;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .form-label i {
            color: #ff7a00;
            margin-right: 5px;
        }

        .password-wrapper {
            position: relative;
        }

        .form-input {
            width: 100%;
            padding: 15px 45px 15px 15px;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 122, 0, 0.3);
            border-radius: 12px;
            color: #fff;
            font-size: 15px;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
            box-sizing: border-box;
        }

        .form-input:focus {
            outline: none;
            border-color: #ff7a00;
            background: rgba(255, 122, 0, 0.05);
            box-shadow: 0 0 15px rgba(255, 122, 0, 0.2);
        }

        .form-input::placeholder {
            color: #666;
        }

        /* États de validation */
        .form-input.valid {
            border-color: #4caf50;
            background: rgba(76, 175, 80, 0.05);
        }

        .form-input.invalid {
            border-color: #f44336;
            background: rgba(244, 67, 54, 0.05);
        }

        .toggle-password {
            position: absolute;
            right: 15px;
            top: 15px;
            cursor: pointer;
            color: #666;
            transition: color 0.3s ease;
            font-size: 16px;
        }

        .toggle-password:hover {
            color: #ff7a00;
        }

        .password-requirements {
            font-size: 12px;
            color: #aaa;
            margin-top: 8px;
            text-align: left;
        }

        .password-requirements ul {
            margin: 5px 0;
            padding-left: 20px;
        }

        .password-requirements li {
            margin: 3px 0;
        }

        /* Message de validation sous le champ */
        .validation-message {
            font-size: 12px;
            margin-top: 8px;
            text-align: left;
            padding-left: 5px;
            min-height: 18px;
            transition: all 0.3s ease;
        }

        .validation-message.success {
            color: #4caf50;
        }

        .validation-message.error {
            color: #f44336;
        }

        .validation-message i {
            margin-right: 5px;
            font-size: 11px;
        }

        .reset-button {
            width: 100%;
            padding: 15px 40px;
            background: linear-gradient(135deg, #ff7a00, #ff4f00);
            color: #fff;
            border: none;
            border-radius: 25px;
            font-weight: 700;
            font-size: 15px;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
            margin-top: 10px;
        }

        .reset-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(255, 122, 0, 0.4);
        }

        .reset-button:disabled {
            background: rgba(255, 122, 0, 0.3);
            cursor: not-allowed;
            transform: none;
        }

        .button-group {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 20px;
        }

        .action-button {
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 700;
            font-size: 14px;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-block;
        }

        .action-button.primary {
            background: linear-gradient(135deg, #ff7a00, #ff4f00);
            color: #fff;
        }

        .action-button.secondary {
            background: transparent;
            border: 2px solid rgba(255, 255, 255, 0.3);
            color: #fff;
        }

        .action-button:hover {
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .reset-container {
                padding: 40px 30px;
            }

            .reset-title {
                font-size: 26px;
            }

            .button-group {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Bulles animées -->
    <div class="bubbles">
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
        <div class="bubble"></div>
    </div>

    <div class="reset-container">
        <?php if ($messageType === 'success' && !$tokenValid): ?>
            <!-- Success State -->
            <div class="icon-container">
                <div class="icon-circle success">
                    <i class="fas fa-check"></i>
                </div>
            </div>
            <h1 class="reset-title">Password Reset!</h1>
            <div class="message-box success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
            <div class="button-group">
                <a href="login.php" class="action-button primary">
                    <i class="fas fa-sign-in-alt"></i> Login Now
                </a>
                <a href="index.php" class="action-button secondary">
                    <i class="fas fa-home"></i> Homepage
                </a>
            </div>
        <?php elseif (!$tokenValid): ?>
            <!-- Error State -->
            <div class="icon-container">
                <div class="icon-circle error">
                    <i class="fas fa-times"></i>
                </div>
            </div>
            <h1 class="reset-title">Invalid Link</h1>
            <div class="message-box error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
            <div class="button-group">
                <a href="forgot-password.php" class="action-button primary">
                    <i class="fas fa-redo"></i> Request New Link
                </a>
                <a href="login.php" class="action-button secondary">
                    <i class="fas fa-sign-in-alt"></i> Back to Login
                </a>
            </div>
        <?php else: ?>
            <!-- Reset Form -->
            <div class="icon-container">
                <div class="icon-circle default">
                    <i class="fas fa-lock"></i>
                </div>
            </div>
            <h1 class="reset-title">Reset Password</h1>
            <p class="reset-subtitle">
                Enter your new password below. Make sure it's strong and secure!
            </p>

            <?php if (!empty($message)): ?>
            <div class="message-box <?php echo $messageType; ?>">
                <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
            <?php endif; ?>

            <form method="POST" id="resetForm">
                <div class="input-group">
                    <label class="form-label">
                        <i class="fas fa-key"></i> New Password
                    </label>
                    <div class="password-wrapper">
                        <input 
                            type="password" 
                            class="form-input" 
                            name="password"
                            id="password"
                            placeholder="Enter new password"
                            required
                            minlength="8"
                        />
                        <i class="fas fa-eye toggle-password" onclick="togglePassword('password', this)"></i>
                    </div>
                    <div class="validation-message" id="password-message"></div>
                    <div class="password-requirements">
                        Password must contain:
                        <ul>
                            <li>At least 8 characters</li>
                            <li>Uppercase & lowercase letters</li>
                            <li>At least one number</li>
                            <li>At least one special character</li>
                        </ul>
                    </div>
                </div>

                <div class="input-group">
                    <label class="form-label">
                        <i class="fas fa-check-double"></i> Confirm Password
                    </label>
                    <div class="password-wrapper">
                        <input 
                            type="password" 
                            class="form-input" 
                            name="confirm_password"
                            id="confirm_password"
                            placeholder="Confirm new password"
                            required
                        />
                        <i class="fas fa-eye toggle-password" onclick="togglePassword('confirm_password', this)"></i>
                    </div>
                    <div class="validation-message" id="confirm-password-message"></div>
                </div>

                <button type="submit" class="reset-button" id="submitBtn">
                    <i class="fas fa-lock"></i> Reset Password
                </button>
            </form>
        <?php endif; ?>
    </div>

    <script>
        // ========== TOGGLE PASSWORD VISIBILITY ==========
        function togglePassword(inputId, icon) {
            const input = document.getElementById(inputId);
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // ========== VALIDATION FUNCTIONS ==========
        
        // Helper function pour afficher un message de validation
        function showValidation(inputId, messageId, isValid, message) {
            const input = document.getElementById(inputId);
            const messageEl = document.getElementById(messageId);
            
            input.classList.remove('valid', 'invalid');
            messageEl.classList.remove('success', 'error');
            
            if (isValid) {
                input.classList.add('valid');
                messageEl.classList.add('success');
                messageEl.innerHTML = `<i class="fas fa-check-circle"></i> ${message}`;
            } else if (message) {
                input.classList.add('invalid');
                messageEl.classList.add('error');
                messageEl.innerHTML = `<i class="fas fa-times-circle"></i> ${message}`;
            } else {
                messageEl.innerHTML = '';
            }
        }

        // Validation Password
        function validatePassword() {
            const password = document.getElementById('password').value;
            
            if (password.length === 0) {
                showValidation('password', 'password-message', false, '');
                return false;
            }
            
            const errors = [];
            if (password.length < 8) errors.push('8+ characters');
            if (!/[A-Z]/.test(password)) errors.push('uppercase letter');
            if (!/[a-z]/.test(password)) errors.push('lowercase letter');
            if (!/[0-9]/.test(password)) errors.push('number');
            if (!/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)) errors.push('special character');
            
            if (errors.length > 0) {
                showValidation('password', 'password-message', false, `Missing: ${errors.join(', ')}`);
                return false;
            } else {
                showValidation('password', 'password-message', true, 'Strong password');
                return true;
            }
        }

        // Validation Confirm Password
        function validateConfirmPassword() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (confirmPassword.length === 0) {
                showValidation('confirm_password', 'confirm-password-message', false, '');
                return false;
            } else if (password !== confirmPassword) {
                showValidation('confirm_password', 'confirm-password-message', false, 'Passwords do not match');
                return false;
            } else {
                showValidation('confirm_password', 'confirm-password-message', true, 'Passwords match');
                return true;
            }
        }

        // ========== REAL-TIME VALIDATION ==========
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        
        if (passwordInput) {
            passwordInput.addEventListener('input', validatePassword);
            passwordInput.addEventListener('blur', validatePassword);
        }
        
        if (confirmPasswordInput) {
            confirmPasswordInput.addEventListener('input', validateConfirmPassword);
            confirmPasswordInput.addEventListener('blur', validateConfirmPassword);
        }

        // ========== FORM SUBMISSION VALIDATION ==========
        const form = document.getElementById('resetForm');
        const submitBtn = document.getElementById('submitBtn');
        
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const isPasswordValid = validatePassword();
                const isConfirmPasswordValid = validateConfirmPassword();
                
                if (!isPasswordValid || !isConfirmPasswordValid) {
                    return false;
                }
                
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Resetting...';
                this.submit();
            });
        }
    </script>
</body>
</html>
