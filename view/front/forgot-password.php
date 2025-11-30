<?php
require_once __DIR__ . '/../../controller/UserController.php';

$message = '';
$messageType = '';
$emailSent = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $message = 'Please enter your email address.';
        $messageType = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        $messageType = 'error';
    } else {
        // Check if user exists
        $user = User::getByEmail($email);
        
        if (!$user) {
            $message = 'No account found with this email address.';
            $messageType = 'error';
        } elseif ($user->getStatus() === 'banned') {
            $message = 'Your account has been banned. Please contact support.';
            $messageType = 'error';
        } else {
            // User exists - send reset email
            $controller = new UserController();
            $result = $controller->sendPasswordResetEmail(
                $user->getId(), 
                $user->getEmail(), 
                $user->getUsername()
            );
            
            if ($result['success']) {
                $message = 'Password reset link sent! Please check your email inbox.';
                $messageType = 'success';
                $emailSent = true;
            } else {
                $message = 'Failed to send reset email. Please try again later.';
                $messageType = 'error';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FoxUnity - Forgot Password</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Orbitron:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }

        .forgot-page-wrapper {
            width: 100%;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .forgot-main-content {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
            position: relative;
            z-index: 10;
        }

        .forgot-container {
            background: linear-gradient(135deg, rgba(20, 20, 20, 0.95) 0%, rgba(10, 10, 10, 0.95) 100%);
            border: 2px solid rgba(255, 122, 0, 0.3);
            border-radius: 25px;
            padding: 50px;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(10px);
            text-align: center;
        }

        .forgot-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #ff7a00, #ff4f00);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            font-size: 35px;
            color: #fff;
            box-shadow: 0 10px 30px rgba(255, 122, 0, 0.4);
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .forgot-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 32px;
            color: #fff;
            margin-bottom: 15px;
            font-weight: 700;
        }

        .forgot-subtitle {
            color: #aaa;
            font-size: 15px;
            line-height: 1.6;
            margin-bottom: 35px;
        }

        .message-box {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            text-align: left;
            font-weight: 600;
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

        .form-group {
            margin-bottom: 25px;
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

        .form-input {
            width: 100%;
            padding: 15px 20px;
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
            box-shadow: 0 0 20px rgba(255, 122, 0, 0.2);
        }

        .form-input::placeholder {
            color: #666;
        }

        .submit-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #ff7a00 0%, #ff4f00 100%);
            color: #fff;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-bottom: 20px;
        }

        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(255, 122, 0, 0.4);
        }

        .submit-btn:active {
            transform: translateY(-1px);
        }

        .submit-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .back-to-login {
            color: #ff7a00;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: color 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .back-to-login:hover {
            color: #fff;
            text-decoration: underline;
        }

        .divider {
            display: flex;
            align-items: center;
            margin: 25px 0;
            color: #666;
            font-size: 14px;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: rgba(255, 255, 255, 0.1);
        }

        .divider span {
            padding: 0 15px;
        }

        .info-box {
            background: rgba(255, 122, 0, 0.05);
            border: 1px solid rgba(255, 122, 0, 0.2);
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 25px;
            text-align: left;
        }

        .info-box-title {
            color: #ff7a00;
            font-weight: 700;
            font-size: 14px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-box-text {
            color: #aaa;
            font-size: 13px;
            line-height: 1.6;
        }

        .success-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
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
            border: none;
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
            .forgot-container {
                padding: 35px 25px;
            }

            .forgot-title {
                font-size: 26px;
            }

            .success-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="forgot-page-wrapper">
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

        <div class="forgot-main-content">
            <div class="forgot-container">
                <div class="forgot-icon">
                    <i class="fas fa-key"></i>
                </div>
                
                <h1 class="forgot-title">Forgot Password?</h1>
                <p class="forgot-subtitle">
                    No worries! Enter your email address and we'll send you a password reset link.
                </p>

                <?php if (!empty($message)): ?>
                <div class="message-box <?php echo $messageType; ?>">
                    <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <span><?php echo htmlspecialchars($message); ?></span>
                </div>
                <?php endif; ?>

                <?php if (!$emailSent): ?>
                    <div class="info-box">
                        <div class="info-box-title">
                            <i class="fas fa-info-circle"></i> How it works
                        </div>
                        <div class="info-box-text">
                            We'll send you an email with a secure reset link. Click the link to create a new password. The link will expire in 1 hour for security reasons.
                        </div>
                    </div>

                    <form method="POST" id="forgot-form">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-envelope"></i> Email Address
                            </label>
                            <input 
                                type="email" 
                                class="form-input" 
                                name="email"
                                id="email-input"
                                placeholder="your@email.com" 
                                value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                required
                            >
                        </div>

                        <button type="submit" class="submit-btn" id="submit-btn">
                            <i class="fas fa-paper-plane"></i> Send Reset Link
                        </button>
                    </form>

                    <div class="divider">
                        <span>or</span>
                    </div>

                    <a href="login.php" class="back-to-login">
                        <i class="fas fa-arrow-left"></i> Back to Login
                    </a>
                <?php else: ?>
                    <div class="success-actions">
                        <a href="login.php" class="action-button primary">
                            <i class="fas fa-sign-in-alt"></i> Go to Login
                        </a>
                        <a href="index.php" class="action-button secondary">
                            <i class="fas fa-home"></i> Homepage
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Prevent multiple submissions
        const form = document.getElementById('forgot-form');
        const submitBtn = document.getElementById('submit-btn');
        
        if (form) {
            form.addEventListener('submit', function() {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
            });
        }
    </script>
</body>
</html>