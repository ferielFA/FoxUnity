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
        // Check if user exists and is pending
        $user = User::getByEmail($email);
        
        if (!$user) {
            $message = 'No account found with this email address.';
            $messageType = 'error';
        } elseif ($user->getStatus() === 'active') {
            $message = 'Your account is already verified! You can login now.';
            $messageType = 'success';
        } elseif ($user->getStatus() === 'banned') {
            $message = 'Your account has been banned. Please contact support.';
            $messageType = 'error';
        } else {
            // User exists and is pending - resend email
            $controller = new UserController();
            $result = $controller->sendVerificationEmail(
                $user->getId(), 
                $user->getEmail(), 
                $user->getUsername()
            );
            
            if ($result['success']) {
                $message = 'Verification email sent successfully! Please check your inbox.';
                $messageType = 'success';
                $emailSent = true;
            } else {
                $message = 'Failed to send verification email. Please try again later.';
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
    <title>Resend Verification Email - FoxUnity</title>
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

        .resend-container {
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
            background: rgba(255, 122, 0, 0.1);
            border: 3px solid #ff7a00;
            color: #ff7a00;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .resend-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 28px;
            color: #fff;
            margin: 20px 0 10px;
        }

        .resend-subtitle {
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

        .form-input {
            width: 100%;
            padding: 15px;
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

        .resend-button {
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

        .resend-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(255, 122, 0, 0.4);
        }

        .resend-button:disabled {
            background: rgba(255, 122, 0, 0.3);
            cursor: not-allowed;
            transform: none;
        }

        .back-link {
            display: block;
            margin-top: 25px;
            color: #ff7a00;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s;
        }

        .back-link:hover {
            color: #fff;
            text-decoration: underline;
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
            .resend-container {
                padding: 40px 30px;
            }

            .resend-title {
                font-size: 24px;
            }

            .success-actions {
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

    <div class="resend-container">
        <div class="icon-container">
            <div class="icon-circle">
                <i class="fas fa-envelope"></i>
            </div>
        </div>

        <h1 class="resend-title">Resend Verification Email</h1>
        <p class="resend-subtitle">
            Enter your email address and we'll send you a new verification link.
        </p>

        <?php if (!empty($message)): ?>
        <div class="message-box <?php echo $messageType; ?>">
            <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <span><?php echo htmlspecialchars($message); ?></span>
        </div>
        <?php endif; ?>

        <?php if ($emailSent): ?>
            <div class="success-actions">
                <a href="login.php" class="action-button primary">
                    <i class="fas fa-sign-in-alt"></i> Go to Login
                </a>
                <a href="index.php" class="action-button secondary">
                    <i class="fas fa-home"></i> Homepage
                </a>
            </div>
        <?php else: ?>
            <form method="POST" id="resendForm">
                <div class="input-group">
                    <label class="form-label">
                        <i class="fas fa-envelope"></i> Email Address
                    </label>
                    <input 
                        type="email" 
                        class="form-input" 
                        name="email" 
                        placeholder="your@email.com"
                        value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                        required
                    />
                </div>

                <button type="submit" class="resend-button" id="submitBtn">
                    <i class="fas fa-paper-plane"></i> Send Verification Email
                </button>
            </form>

            <a href="login.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Login
            </a>
        <?php endif; ?>
    </div>

    <script>
        // Prevent multiple submissions
        const form = document.getElementById('resendForm');
        const submitBtn = document.getElementById('submitBtn');
        
        if (form) {
            form.addEventListener('submit', function() {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
            });
        }
    </script>
</body>
</html>  