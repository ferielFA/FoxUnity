<?php
require_once __DIR__ . '/../../controller/UserController.php';

$message = '';
$messageType = '';
$verified = false;

// Get token from URL
$token = $_GET['token'] ?? '';

if (empty($token)) {
    $message = 'Invalid verification link. Please check your email and try again.';
    $messageType = 'error';
} else {
    // Verify the token
    $controller = new UserController();
    $result = $controller->verifyEmail($token);
    
    if ($result['success']) {
        $message = $result['message'];
        $messageType = 'success';
        $verified = true;
    } else {
        $message = $result['errors'][0] ?? 'Verification failed. Please try again.';
        $messageType = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - FoxUnity</title>
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

        .verify-container {
            background: rgba(20, 20, 20, 0.95);
            border-radius: 25px;
            box-shadow: 0 14px 28px rgba(255, 122, 0, 0.25), 
                        0 10px 10px rgba(0, 0, 0, 0.22);
            border: 2px solid rgba(255, 122, 0, 0.3);
            padding: 60px 50px;
            text-align: center;
            max-width: 500px;
            width: 100%;
            position: relative;
            z-index: 10;
        }

        .icon-container {
            margin-bottom: 30px;
        }

        .icon-circle {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 60px;
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

        .verify-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 32px;
            color: #fff;
            margin: 20px 0 15px;
        }

        .verify-message {
            font-size: 16px;
            color: #aaa;
            line-height: 1.6;
            margin-bottom: 30px;
        }

        .verify-message.success {
            color: #4caf50;
        }

        .verify-message.error {
            color: #f44336;
        }

        .verify-button {
            display: inline-block;
            padding: 15px 40px;
            background: linear-gradient(135deg, #ff7a00, #ff4f00);
            color: #fff;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 700;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
        }

        .verify-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(255, 122, 0, 0.4);
        }

        .verify-button.secondary {
            background: transparent;
            border: 2px solid rgba(255, 255, 255, 0.3);
            color: #fff;
            margin-left: 10px;
        }

        .verify-button.secondary:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.5);
        }

        .button-group {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .loading {
            display: none;
            margin-top: 20px;
        }

        .loading.show {
            display: block;
        }

        .spinner {
            border: 4px solid rgba(255, 122, 0, 0.2);
            border-top: 4px solid #ff7a00;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @media (max-width: 768px) {
            .verify-container {
                padding: 40px 30px;
            }

            .verify-title {
                font-size: 26px;
            }

            .icon-circle {
                width: 100px;
                height: 100px;
                font-size: 50px;
            }

            .button-group {
                flex-direction: column;
            }

            .verify-button.secondary {
                margin-left: 0;
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

    <div class="verify-container">
        <?php if ($messageType === 'success'): ?>
            <!-- Success State -->
            <div class="icon-container">
                <div class="icon-circle success">
                    <i class="fas fa-check"></i>
                </div>
            </div>
            <h1 class="verify-title">Email Verified!</h1>
            <p class="verify-message success"><?php echo htmlspecialchars($message); ?></p>
            <div class="button-group">
                <a href="login.php" class="verify-button">
                    <i class="fas fa-sign-in-alt"></i> Login Now
                </a>
                <a href="index.php" class="verify-button secondary">
                    <i class="fas fa-home"></i> Go to Homepage
                </a>
            </div>
        <?php else: ?>
            <!-- Error State -->
            <div class="icon-container">
                <div class="icon-circle error">
                    <i class="fas fa-times"></i>
                </div>
            </div>
            <h1 class="verify-title">Verification Failed</h1>
            <p class="verify-message error"><?php echo htmlspecialchars($message); ?></p>
            <div class="button-group">
                <a href="login.php" class="verify-button">
                    <i class="fas fa-sign-in-alt"></i> Back to Login
                </a>
                <button onclick="resendEmail()" class="verify-button secondary" id="resendBtn">
                    <i class="fas fa-envelope"></i> Resend Email
                </button>
            </div>
            
            <div class="loading" id="loading">
                <div class="spinner"></div>
                <p style="color: #aaa; margin-top: 15px;">Sending verification email...</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function resendEmail() {
            const btn = document.getElementById('resendBtn');
            const loading = document.getElementById('loading');
            
            btn.disabled = true;
            loading.classList.add('show');
            
            // TODO: Implement resend email logic via AJAX
            setTimeout(() => {
                alert('Verification email has been resent! Please check your inbox.');
                loading.classList.remove('show');
                btn.disabled = false;
            }, 2000);
        }

        // Auto-redirect to login after 5 seconds if verified successfully
        <?php if ($verified): ?>
        let countdown = 5;
        const interval = setInterval(() => {
            countdown--;
            if (countdown <= 0) {
                clearInterval(interval);
                window.location.href = 'login.php';
            }
        }, 1000);
        <?php endif; ?>
    </script>
</body>
</html>