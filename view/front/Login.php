<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../controller/UserController.php';

$controller = new UserController();
$errors = [];
$success = '';

// Handle Google login errors
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'no_code':
            $errors[] = 'Google authentication failed. Please try again.';
            break;
        case 'google_auth_failed':
            $errors[] = 'Failed to authenticate with Google. Please try again.';
            break;
        case 'link_failed':
            $errors[] = 'Failed to link Google account. Please try again.';
            break;
        case 'registration_failed':
            $errors[] = 'Failed to create account with Google. Please try again.';
            break;
    }
}

// Handle Sign Up
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'signup') {
    // Vérifier le CAPTCHA côté serveur
    $recaptchaSecret = '6Ld8BxIsAAAAAMZnO7ypbmWefzS7e1Mgs5qRDK4_';
    $recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';
    
    if (empty($recaptchaResponse)) {
        $errors[] = 'Please complete the CAPTCHA verification';
    } else {
        // Vérifier le CAPTCHA auprès de Google
        $verifyURL = 'https://www.google.com/recaptcha/api/siteverify';
        $response = file_get_contents($verifyURL . '?secret=' . $recaptchaSecret . '&response=' . $recaptchaResponse);
        $responseData = json_decode($response);
        
        if (!$responseData->success) {
            $errors[] = 'CAPTCHA verification failed. Please try again.';
        }
    }
    
    // Si pas d'erreur CAPTCHA, procéder à l'inscription
    if (empty($errors)) {
        $result = $controller->register(
            $_POST['username'] ?? '',
            $_POST['email'] ?? '',
            $_POST['dob'] ?? '',
            $_POST['password'] ?? '',
            $_POST['password'] ?? '',
            $_POST['gender'] ?? ''
        );
        
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $errors = $result['errors'];
        }
    }
}

// Handle Sign In
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'signin') {
    $result = $controller->login(
        $_POST['email'] ?? '',
        $_POST['password'] ?? ''
    );
    
    if ($result['success']) {
        // Redirect based on role
        header('Location: ' . $result['redirect']);
        exit();
    } else {
        $errors = $result['errors'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FoxUnity - Login / Register</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Orbitron:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    
    <style>
        /* Override body for this page */
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            min-height: 100vh;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }

        .auth-page-wrapper {
            width: 100%;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 20px;
            box-sizing: border-box;
        }

        .auth-main-content {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            max-width: 1200px;
            position: relative;
            z-index: 10;
        }

        /* Server-side Error/Success Messages - Fixed positioning */
        .message-container {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 9999;
            width: 90%;
            max-width: 600px;
        }

        .message-box {
            padding: 15px 20px;
            border-radius: 12px;
            text-align: left;
            font-weight: 600;
            margin-bottom: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            animation: slideDown 0.3s ease-out;
            opacity: 1;
            transition: opacity 0.3s ease-out;
        }

        .message-box.fade-out {
            opacity: 0;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .error-box {
            background: rgba(244, 67, 54, 0.95);
            border: 2px solid #c62828;
            color: #fff;
        }

        .success-box {
            background: rgba(76, 175, 80, 0.95);
            border: 2px solid #388e3c;
            color: #fff;
        }

        .error-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .error-list li {
            margin: 5px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .message-box i {
            font-size: 18px;
        }

        .error-box.banned-error {
            background: rgba(139, 0, 0, 0.95);
            border-color: #8b0000;
            animation: shake 0.5s;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }

        /* Animation Container Styles */
        .auth-container {
            background-color: rgba(20, 20, 20, 0.95);
            border-radius: 25px;
            box-shadow: 0 14px 28px rgba(255, 122, 0, 0.25), 
                        0 10px 10px rgba(0, 0, 0, 0.22);
            position: relative;
            overflow: hidden;
            width: 900px;
            max-width: 100%;
            min-height: 700px;
            border: 2px solid rgba(255, 122, 0, 0.3);
        }

        .form-container {
            position: absolute;
            top: 0;
            height: 100%;
            transition: all 0.6s ease-in-out;
            z-index: 200;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .sign-in-container {
            left: 0;
            width: 50%;
            z-index: 201;
        }

        .auth-container.right-panel-active .sign-in-container {
            transform: translateX(100%);
        }

        .sign-up-container {
            left: 0;
            width: 50%;
            opacity: 0;
            z-index: 200;
        }

        .auth-container.right-panel-active .sign-up-container {
            transform: translateX(100%);
            opacity: 1;
            z-index: 205;
            animation: show 0.6s;
        }

        @keyframes show {
            0%, 49.99% {
                opacity: 0;
                z-index: 1;
            }
            50%, 100% {
                opacity: 1;
                z-index: 5;
            }
        }

        form {
            background-color: rgba(10, 10, 10, 0.95);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            padding: 25px 50px;
            width: 100%;
            height: 100%;
            text-align: center;
            backdrop-filter: blur(10px);
            box-sizing: border-box;
            overflow-y: auto;
        }

        .form-content {
            width: 100%;
            max-width: 350px;
        }

        .form-title {
            font-family: 'Orbitron', sans-serif;
            font-weight: 700;
            margin: 0 0 8px;
            color: #fff;
            font-size: 26px;
        }

        .form-subtitle {
            font-size: 11px;
            font-weight: 300;
            line-height: 16px;
            letter-spacing: 0.5px;
            margin: 4px 0 12px;
            color: #aaa;
        }

        .social-container {
            margin: 8px 0;
        }

        .social-container a {
            border: 2px solid rgba(255, 122, 0, 0.3);
            border-radius: 50%;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            margin: 0 5px;
            height: 36px;
            width: 36px;
            transition: all 0.3s ease;
            color: #ff7a00;
            background: rgba(255, 122, 0, 0.05);
        }

        .social-container a:hover {
            background: rgba(255, 122, 0, 0.2);
            border-color: #ff7a00;
            color: #fff;
            transform: translateY(-3px);
        }

        /* Input Field Group avec validation inline */
        .input-group {
            width: 100%;
            margin: 8px 0;
        }

        .form-input {
            background-color: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 255, 255, 0.1);
            padding: 10px 14px;
            width: 100%;
            border-radius: 12px;
            color: #fff;
            font-family: 'Poppins', sans-serif;
            font-size: 13px;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        .form-input:focus {
            outline: none;
            border-color: #ff7a00;
            background: rgba(255, 255, 255, 0.08);
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

        /* Message de validation sous le champ */
        .validation-message {
            font-size: 11px;
            margin-top: 4px;
            text-align: left;
            padding-left: 5px;
            min-height: 16px;
            transition: all 0.3s ease;
        }

        .validation-message.success {
            color: #4caf50;
        }

        .validation-message.error {
            color: #f44336;
        }

        .validation-message i {
            margin-right: 4px;
            font-size: 10px;
        }

        /* Style pour les champs de mot de passe avec icône */
        .password-wrapper {
            position: relative;
            width: 100%;
        }

        .password-wrapper .form-input {
            padding-right: 45px;
        }

        .toggle-password {
            position: absolute;
            right: 15px;
            top: 12px;
            cursor: pointer;
            color: #666;
            transition: color 0.3s ease;
            font-size: 16px;
            user-select: none;
        }

        .toggle-password:hover {
            color: #ff7a00;
        }

        .toggle-password.active {
            color: #ff7a00;
        }

        /* Style pour le CAPTCHA */
        .captcha-container {
            margin: 8px 0;
            display: flex;
            justify-content: center;
            transform: scale(0.8);
            transform-origin: center;
        }

        @media (max-width: 480px) {
            .captcha-container {
                transform: scale(0.7);
            }
        }

        .form-row-inline {
            display: flex;
            gap: 10px;
            width: 100%;
            margin: 8px 0;
        }

        .form-half {
            flex: 1;
        }

        .form-input[type="date"]::-webkit-calendar-picker-indicator {
            filter: invert(0.6);
        }

        select.form-input {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23ff7a00' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            padding-right: 40px;
            background-color: #000;
            color: #fff;
        }

        select.form-input option {
            background-color: #000;
            color: #fff;
        }

        select.form-input option[value=""] {
            color: #666;
        }

        .auth-button {
            border-radius: 25px;
            border: none;
            background: linear-gradient(135deg, #ff7a00 0%, #ff4f00 100%);
            color: #FFFFFF;
            font-size: 11px;
            font-weight: 700;
            padding: 10px 40px;
            letter-spacing: 1px;
            text-transform: uppercase;
            transition: all 0.3s ease;
            cursor: pointer;
            margin-top: 12px;
            font-family: 'Poppins', sans-serif;
        }

        .auth-button:active {
            transform: scale(0.95);
        }

        .auth-button:focus {
            outline: none;
        }

        .auth-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(255, 122, 0, 0.4);
        }

        .auth-button.ghost {
            background-color: transparent;
            border: 2px solid #FFFFFF;
            color: #fff;
        }

        .auth-button.ghost:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .forgot-link {
            color: #ff7a00;
            font-size: 12px;
            text-decoration: none;
            margin: 10px 0 15px;
            transition: color 0.3s;
            display: block;
            width: 100%;
            text-align: center;
        }

        .forgot-link:hover {
            color: #fff;
            text-decoration: underline;
        }

        /* Overlay Styles */
        .overlay-container {
            position: absolute;
            top: 0;
            left: 50%;
            width: 50%;
            height: 100%;
            overflow: hidden;
            transition: transform 0.6s ease-in-out;
            z-index: 100;
        }

        .auth-container.right-panel-active .overlay-container {
            transform: translateX(-100%);
        }

        .overlay {
            background: linear-gradient(180deg, #0a0a0a 0%, #1a1a1a 60%, #ff7a00 100%);
            background-repeat: no-repeat;
            background-size: cover;
            background-position: 0 0;
            color: #FFFFFF;
            position: relative;
            left: -100%;
            height: 100%;
            width: 200%;
            transform: translateX(0);
            transition: transform 0.6s ease-in-out;
        }

        .auth-container.right-panel-active .overlay {
            transform: translateX(50%);
        }

        .overlay-panel {
            position: absolute;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            padding: 0 40px;
            text-align: center;
            top: 0;
            height: 100%;
            width: 50%;
            transform: translateX(0);
            transition: transform 0.6s ease-in-out;
        }

        .overlay-left {
            transform: translateX(-20%);
        }

        .auth-container.right-panel-active .overlay-left {
            transform: translateX(0);
        }

        .overlay-right {
            right: 0;
            transform: translateX(0);
        }

        .auth-container.right-panel-active .overlay-right {
            transform: translateX(20%);
        }

        .overlay-title {
            font-family: 'Orbitron', sans-serif;
            font-weight: 700;
            margin: 0 0 20px;
            font-size: 32px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }

        .overlay-text {
            font-size: 14px;
            font-weight: 300;
            line-height: 22px;
            letter-spacing: 0.5px;
            margin: 15px 0 25px;
        }

        .fox-icon {
            font-size: 70px;
            margin-bottom: 15px;
            filter: drop-shadow(0 0 20px rgba(255, 255, 255, 0.3));
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .auth-container {
                width: 100%;
                min-height: 600px;
            }

            .form-container {
                width: 100% !important;
            }

            .sign-in-container,
            .sign-up-container {
                width: 100%;
            }

            .overlay-container {
                display: none;
            }

            form {
                padding: 30px 25px;
            }

            .form-title {
                font-size: 24px;
            }

            .message-container {
                width: 95%;
                top: 10px;
            }
            
            .face-modal-content {
                margin: 10% auto;
                padding: 20px;
                width: 95%;
            }
        }

        @media (max-width: 480px) {
            form {
                padding: 20px;
            }

            .form-input {
                padding: 10px 14px;
                font-size: 13px;
            }

            .auth-button {
                padding: 10px 35px;
                font-size: 11px;
            }
        }
    </style>
</head>
<body>
    <div class="auth-page-wrapper">
        <!-- Bulles animées rouges -->
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

        <!-- Messages Container -->
        <?php if (!empty($errors) || !empty($success)): ?>
        <div class="message-container" id="message-container">
            <?php if (!empty($errors)): ?>
            <div class="message-box error-box <?php echo (in_array('ACCOUNT_BANNED', $errors)) ? 'banned-error' : ''; ?>" id="error-message">
                <ul class="error-list">
                    <?php foreach ($errors as $error): ?>
                    <?php if ($error === 'ACCOUNT_BANNED'): ?>
                        <li><i class="fas fa-ban"></i> Your account has been banned. Please contact support for more information.</li>
                    <?php elseif ($error === 'EMAIL_NOT_VERIFIED'): ?>
                        <li><i class="fas fa-envelope"></i> Please verify your email address before logging in. Check your inbox!</li>
                    <?php else: ?>
                        <li><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></li>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </ul>

                <?php if (in_array('EMAIL_NOT_VERIFIED', $errors)): ?>
                <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid rgba(255, 122, 0, 0.2); text-align: center;">
                    <p style="color: #aaa; font-size: 13px; margin-bottom: 12px; font-weight: 400;">
                        Didn't receive the email?
                    </p>
                    <a href="resend_verification.php" style="
                        display: inline-block;
                        padding: 12px 30px;
                        background: linear-gradient(135deg, #ff7a00, #ff4f00);
                        color: #fff;
                        text-decoration: none;
                        border-radius: 20px;
                        font-weight: 600;
                        font-size: 13px;
                        transition: all 0.3s ease;
                        box-shadow: 0 4px 15px rgba(255, 122, 0, 0.3);
                    " onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(255, 122, 0, 0.4)';" 
                       onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(255, 122, 0, 0.3)';">
                        <i class="fas fa-paper-plane"></i> Resend Verification Email
                    </a>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
            <div class="message-box success-box" id="success-message">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- AUTH CONTENT -->
        <div class="auth-main-content">
            <div class="auth-container" id="container">
                <!-- Sign Up Form -->
                <div class="form-container sign-up-container">
                    <form action="Login.php" method="POST" id="signUpForm" novalidate>
                        <div class="form-content">
                            <input type="hidden" name="action" value="signup">
                            <h1 class="form-title">Create Account</h1>
                            <div class="social-container">
                                <a href="javascript:void(0);" onclick="googleLogin()" class="social" title="Sign up with Google">
                                    <i class="fab fa-google"></i>
                                </a>
                            </div>
                            <span class="form-subtitle">or use your email for registration</span>
                            
                            <!-- Username -->
                            <div class="input-group">
                                <input type="text" class="form-input" id="username" name="username" placeholder="Username" />
                                <div class="validation-message" id="username-message"></div>
                            </div>

                            <!-- Email -->
                            <div class="input-group">
                                <input type="text" class="form-input" id="email" name="email" placeholder="Email" />
                                <div class="validation-message" id="email-message"></div>
                            </div>

                            <!-- DOB & Gender -->
                            <div class="form-row-inline">
                                <div class="input-group form-half">
                                    <input type="date" class="form-input" id="dob" name="dob" />
                                    <div class="validation-message" id="dob-message"></div>
                                </div>
                                <div class="input-group form-half">
                                    <select class="form-input" id="gender" name="gender">
                                        <option value="" disabled selected>Gender</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                    </select>
                                    <div class="validation-message" id="gender-message"></div>
                                </div>
                            </div>

                            <!-- Password -->
                            <div class="input-group">
                                <div class="password-wrapper">
                                    <input type="password" class="form-input" id="signupPassword" name="password" placeholder="Password" />
                                    <i class="fas fa-eye toggle-password" onclick="togglePassword('signupPassword', this)"></i>
                                </div>
                                <div class="validation-message" id="password-message"></div>
                            </div>

                            <!-- Confirm Password -->
                            <div class="input-group">
                                <div class="password-wrapper">
                                    <input type="password" class="form-input" id="signupPasswordConfirm" name="password_confirm" placeholder="Confirm Password" />
                                    <i class="fas fa-eye toggle-password" onclick="togglePassword('signupPasswordConfirm', this)"></i>
                                </div>
                                <div class="validation-message" id="password-confirm-message"></div>
                            </div>

                            <!-- CAPTCHA -->
                            <div class="captcha-container">
                                <div class="g-recaptcha" data-sitekey="6Ld8BxIsAAAAAGuOxS5dtvoBw-ZbRmRF-MZDww-M"></div>
                            </div>

                            <button type="submit" class="auth-button">Sign Up</button>
                        </div>
                    </form>
                </div>

                <!-- Sign In Form -->
                <div class="form-container sign-in-container">
                    <form action="Login.php" method="POST" id="signInForm" novalidate>
                        <div class="form-content">
                            <input type="hidden" name="action" value="signin">
                            <h1 class="form-title">Sign In</h1>
                            <div class="social-container">
                                <a href="javascript:void(0);" onclick="googleLogin()" class="social" title="Sign in with Google">
                                    <i class="fab fa-google"></i>
                                </a>
                            </div>
                            <span class="form-subtitle">or use your account</span>
                            
                            <!-- Email -->
                            <div class="input-group">
                                <input type="text" class="form-input" id="signin-email" name="email" placeholder="Email" />
                                <div class="validation-message" id="signin-email-message"></div>
                            </div>

                            <!-- Password -->
                            <div class="input-group">
                                <div class="password-wrapper">
                                    <input type="password" class="form-input" id="signinPassword" name="password" placeholder="Password" />
                                    <i class="fas fa-eye toggle-password" onclick="togglePassword('signinPassword', this)"></i>
                                </div>
                                <div class="validation-message" id="signin-password-message"></div>
                            </div>

                            <a href="forgot-password.php" class="forgot-link">Forgot your password?</a>
                            <button type="submit" class="auth-button">Sign In</button>
                            
                            <!-- Face Recognition Button -->
                            <div style="margin: 15px 0; text-align: center;">
                                <span style="color: #666; font-size: 12px;">or</span>
                            </div>
                            <button type="button" class="auth-button" id="faceLoginBtn" style="background: linear-gradient(135deg, #00bcd4 0%, #0097a7 100%);">
                                <i class="fas fa-camera"></i> Sign In with Face
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Overlay Panel -->
                <div class="overlay-container">
                    <div class="overlay">
                        <div class="overlay-panel overlay-left">
                            <div class="fox-icon">🦊</div>
                            <h1 class="overlay-title">Welcome Back!</h1>
                            <p class="overlay-text">To keep connected with us please login with your personal info</p>
                            <button class="auth-button ghost" id="signIn">Sign In</button>
                        </div>
                        <div class="overlay-panel overlay-right">
                            <div class="fox-icon">🦊</div>
                            <h1 class="overlay-title">Hello, Gamer!</h1>
                            <p class="overlay-text">Enter your personal details and start your journey with FoxUnity</p>
                            <button class="auth-button ghost" id="signUp">Sign Up</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Face Recognition Camera Modal -->
    <div id="faceLoginModal" class="face-modal">
        <div class="face-modal-content">
            <span class="face-modal-close" id="closeFaceModal">&times;</span>
            <h2 class="face-modal-title">
                <i class="fas fa-user-check"></i> Face Recognition Login
            </h2>
            
            <div class="face-camera-container">
                <video id="faceVideo" autoplay playsinline></video>
                <canvas id="faceCanvas" style="display: none;"></canvas>
                
                <div id="faceStatus" class="face-status">
                    <i class="fas fa-camera"></i> Position your face in the camera
                </div>
                
                <div id="faceCountdown" class="face-countdown" style="display: none;">
                    <div class="countdown-number">3</div>
                </div>
            </div>
            
            <div class="face-actions">
                <button id="startFaceCapture" class="face-btn face-btn-primary">
                    <i class="fas fa-camera"></i> Start Camera
                </button>
                <button id="cancelFaceLogin" class="face-btn face-btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>
            
            <div id="faceLoginLoader" class="face-loader" style="display: none;">
                <div class="loader-spinner"></div>
                <p>Analyzing face...</p>
            </div>
        </div>
    </div>

    <style>
        /* Face Modal Styles */
        .face-modal {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .face-modal-content {
            position: relative;
            background: linear-gradient(135deg, #1a1a1a 0%, #0a0a0a 100%);
            margin: 3% auto;
            padding: 30px;
            border: 2px solid rgba(255, 122, 0, 0.3);
            border-radius: 20px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 10px 50px rgba(255, 122, 0, 0.3);
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .face-modal-close {
            color: #aaa;
            float: right;
            font-size: 32px;
            font-weight: bold;
            line-height: 20px;
            cursor: pointer;
            transition: color 0.3s;
        }

        .face-modal-close:hover {
            color: #ff7a00;
        }

        .face-modal-title {
            font-family: 'Orbitron', sans-serif;
            color: #fff;
            text-align: center;
            margin: 0 0 20px 0;
            font-size: 24px;
        }

        .face-modal-title i {
            color: #ff7a00;
            margin-right: 10px;
        }

        .face-camera-container {
            position: relative;
            width: 100%;
            background: #000;
            border-radius: 15px;
            overflow: hidden;
            margin: 20px 0;
            min-height: 400px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        #faceVideo {
            width: 100%;
            height: auto;
            border-radius: 15px;
            transform: scaleX(-1); /* Mirror effect */
        }

        .face-status {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.8);
            color: #fff;
            padding: 12px 25px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 600;
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 122, 0, 0.5);
        }

        .face-status i {
            margin-right: 8px;
            color: #ff7a00;
        }

        .face-countdown {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 10;
        }

        .countdown-number {
            font-size: 120px;
            font-weight: bold;
            color: #ff7a00;
            text-shadow: 0 0 30px rgba(255, 122, 0, 0.8);
            animation: pulse 1s ease-in-out;
            font-family: 'Orbitron', sans-serif;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.7; }
            50% { transform: scale(1.2); opacity: 1; }
        }

        .face-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 20px;
        }

        .face-btn {
            padding: 12px 30px;
            border: none;
            border-radius: 25px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
        }

        .face-btn i {
            margin-right: 8px;
        }

        .face-btn-primary {
            background: linear-gradient(135deg, #00bcd4 0%, #0097a7 100%);
            color: #fff;
        }

        .face-btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 188, 212, 0.4);
        }

        .face-btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            border: 2px solid rgba(255, 255, 255, 0.2);
        }

        .face-btn-secondary:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .face-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .face-loader {
            text-align: center;
            padding: 20px;
            color: #fff;
        }

        .loader-spinner {
            border: 4px solid rgba(255, 122, 0, 0.1);
            border-top: 4px solid #ff7a00;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Success/Error Messages in Modal */
        .face-message {
            padding: 15px;
            border-radius: 12px;
            margin: 15px 0;
            text-align: center;
            font-weight: 600;
        }

        .face-message.success {
            background: rgba(76, 175, 80, 0.2);
            border: 2px solid #4caf50;
            color: #4caf50;
        }

        .face-message.error {
            background: rgba(244, 67, 54, 0.2);
            border: 2px solid #f44336;
            color: #f44336;
        }

        @media (max-width: 768px) {
            .face-modal-content {
                width: 95%;
                margin: 10% auto;
                padding: 20px;
            }

            .face-camera-container {
                min-height: 300px;
            }

            .countdown-number {
                font-size: 80px;
            }
        }

        /* Face Result Popup */
        .face-result-popup {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0.7);
            z-index: 10001;
            opacity: 0;
            transition: all 0.3s ease;
            pointer-events: none;
        }

        .face-result-popup.show {
            opacity: 1;
            transform: translate(-50%, -50%) scale(1);
        }

        .face-result-content {
            background: linear-gradient(135deg, #1a1a1a 0%, #0a0a0a 100%);
            padding: 40px 50px;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.8);
            border: 2px solid;
            min-width: 350px;
            max-width: 600px;
        }

        .face-result-content.success {
            border-color: #4caf50;
        }

        .face-result-content.error {
            border-color: #f44336;
        }

        .face-result-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }

        .face-result-content.success .face-result-icon {
            color: #4caf50;
            animation: successPulse 0.6s ease;
        }

        .face-result-content.error .face-result-icon {
            color: #f44336;
            animation: errorShake 0.6s ease;
        }

        @keyframes successPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.2); }
        }

        @keyframes errorShake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }

        .face-result-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 28px;
            margin: 0 0 15px 0;
            color: #fff;
        }

        .face-result-message {
            font-size: 16px;
            color: #aaa;
            margin: 0;
            line-height: 1.6;
        }

        .face-result-content.success .face-result-title {
            color: #4caf50;
        }

        .face-result-content.error .face-result-title {
            color: #f44336;
        }

        @media (max-width: 768px) {
            .face-result-content {
                min-width: 280px;
                padding: 30px 25px;
            }

            .face-result-icon {
                font-size: 60px;
            }

            .face-result-title {
                font-size: 22px;
            }

            .face-result-message {
                font-size: 14px;
            }
        }
    </style>

    <script>
        // ========== TOGGLE PASSWORD VISIBILITY ==========
        function togglePassword(inputId, icon) {
            const input = document.getElementById(inputId);
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash', 'active');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash', 'active');
                icon.classList.add('fa-eye');
            }
        }

        // ========== PANEL SWITCHING ==========
        const signUpButton = document.getElementById('signUp');
        const signInButton = document.getElementById('signIn');
        const container = document.getElementById('container');
        
        signUpButton.addEventListener('click', () => {
            container.classList.add("right-panel-active");
        });
        
        signInButton.addEventListener('click', () => {
            container.classList.remove("right-panel-active");
        });

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

        // Validation Username
        function validateUsername() {
            const username = document.getElementById('username').value.trim();
            
            if (username.length === 0) {
                showValidation('username', 'username-message', false, '');
                return false;
            } else if (username.length < 3) {
                showValidation('username', 'username-message', false, 'Username must be at least 3 characters');
                return false;
            } else {
                showValidation('username', 'username-message', true, 'Username is valid');
                return true;
            }
        }

        // Validation Email
        function validateEmail() {
            const email = document.getElementById('email').value.trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (email.length === 0) {
                showValidation('email', 'email-message', false, '');
                return false;
            } else if (!emailRegex.test(email)) {
                showValidation('email', 'email-message', false, 'Please enter a valid email address');
                return false;
            } else {
                showValidation('email', 'email-message', true, 'Email is valid');
                return true;
            }
        }

        // Validation DOB
        function validateDOB() {
            const dob = document.getElementById('dob').value;
            
            if (!dob) {
                showValidation('dob', 'dob-message', false, 'Required');
                return false;
            }
            
            const dobDate = new Date(dob);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            if (dobDate > today) {
                showValidation('dob', 'dob-message', false, 'Cannot be in the future');
                return false;
            } else {
                showValidation('dob', 'dob-message', true, 'Valid');
                return true;
            }
        }

        // Validation Gender
        function validateGender() {
            const gender = document.getElementById('gender').value;
            
            if (!gender) {
                showValidation('gender', 'gender-message', false, 'Required');
                return false;
            } else {
                showValidation('gender', 'gender-message', true, 'Valid');
                return true;
            }
        }

        // Validation Password
        function validatePassword() {
            const password = document.getElementById('signupPassword').value;
            
            if (password.length === 0) {
                showValidation('signupPassword', 'password-message', false, '');
                return false;
            }
            
            const errors = [];
            if (password.length < 8) errors.push('8+ characters');
            if (!/[A-Z]/.test(password)) errors.push('uppercase letter');
            if (!/[a-z]/.test(password)) errors.push('lowercase letter');
            if (!/[0-9]/.test(password)) errors.push('number');
            if (!/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)) errors.push('special character');
            
            if (errors.length > 0) {
                showValidation('signupPassword', 'password-message', false, `Missing: ${errors.join(', ')}`);
                return false;
            } else {
                showValidation('signupPassword', 'password-message', true, 'Strong password');
                return true;
            }
        }

        // Validation Confirm Password
        function validatePasswordConfirm() {
            const password = document.getElementById('signupPassword').value;
            const confirmPassword = document.getElementById('signupPasswordConfirm').value;
            
            if (confirmPassword.length === 0) {
                showValidation('signupPasswordConfirm', 'password-confirm-message', false, '');
                return false;
            } else if (password !== confirmPassword) {
                showValidation('signupPasswordConfirm', 'password-confirm-message', false, 'Passwords do not match');
                return false;
            } else {
                showValidation('signupPasswordConfirm', 'password-confirm-message', true, 'Passwords match');
                return true;
            }
        }

        // Sign In Email Validation
        function validateSignInEmail() {
            const email = document.getElementById('signin-email').value.trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (email.length === 0) {
                showValidation('signin-email', 'signin-email-message', false, '');
                return false;
            } else if (!emailRegex.test(email)) {
                showValidation('signin-email', 'signin-email-message', false, 'Invalid email');
                return false;
            } else {
                showValidation('signin-email', 'signin-email-message', true, 'Valid');
                return true;
            }
        }

        // Sign In Password Validation
        function validateSignInPassword() {
            const password = document.getElementById('signinPassword').value;
            
            if (password.length === 0) {
                showValidation('signinPassword', 'signin-password-message', false, '');
                return false;
            } else if (password.length < 8) {
                showValidation('signinPassword', 'signin-password-message', false, 'Password too short');
                return false;
            } else {
                showValidation('signinPassword', 'signin-password-message', true, 'Valid');
                return true;
            }
        }

        // ========== REAL-TIME VALIDATION (Sign Up) ==========
        document.getElementById('username').addEventListener('input', validateUsername);
        document.getElementById('username').addEventListener('blur', validateUsername);

        document.getElementById('email').addEventListener('input', validateEmail);
        document.getElementById('email').addEventListener('blur', validateEmail);

        document.getElementById('dob').addEventListener('change', validateDOB);
        document.getElementById('gender').addEventListener('change', validateGender);

        document.getElementById('signupPassword').addEventListener('input', validatePassword);
        document.getElementById('signupPassword').addEventListener('blur', validatePassword);

        document.getElementById('signupPasswordConfirm').addEventListener('input', validatePasswordConfirm);
        document.getElementById('signupPasswordConfirm').addEventListener('blur', validatePasswordConfirm);

        // ========== REAL-TIME VALIDATION (Sign In) ==========
        document.getElementById('signin-email').addEventListener('input', validateSignInEmail);
        document.getElementById('signin-email').addEventListener('blur', validateSignInEmail);

        document.getElementById('signinPassword').addEventListener('input', validateSignInPassword);
        document.getElementById('signinPassword').addEventListener('blur', validateSignInPassword);

        // ========== FORM SUBMISSION VALIDATION ==========
        
        // Sign Up Form Submission
        document.getElementById('signUpForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const isUsernameValid = validateUsername();
            const isEmailValid = validateEmail();
            const isDOBValid = validateDOB();
            const isGenderValid = validateGender();
            const isPasswordValid = validatePassword();
            const isPasswordConfirmValid = validatePasswordConfirm();
            
            // Vérifier le CAPTCHA
            const recaptchaResponse = grecaptcha.getResponse();
            if (recaptchaResponse.length === 0) {
                alert('Please complete the CAPTCHA verification');
                return false;
            }
            
            if (isUsernameValid && isEmailValid && isDOBValid && isGenderValid && isPasswordValid && isPasswordConfirmValid) {
                this.submit();
            }
        });

        // Sign In Form Submission
        document.getElementById('signInForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const isEmailValid = validateSignInEmail();
            const isPasswordValid = validateSignInPassword();
            
            if (isEmailValid && isPasswordValid) {
                this.submit();
            }
        });

        // ========== GOOGLE LOGIN ==========
        function googleLogin() {
            window.location.href = '../../controller/GoogleConfig.php?action=login';
        }

        // ========== FACE RECOGNITION LOGIN ==========
        let faceStream = null;
        let faceVideo = null;
        let faceCanvas = null;
        let faceModal = null;
        let captureTimeout = null;

        // Initialize face login
        document.getElementById('faceLoginBtn').addEventListener('click', function() {
            faceModal = document.getElementById('faceLoginModal');
            faceVideo = document.getElementById('faceVideo');
            faceCanvas = document.getElementById('faceCanvas');
            
            faceModal.style.display = 'block';
            document.getElementById('faceStatus').innerHTML = '<i class="fas fa-info-circle"></i> Click "Start Camera" to begin';
        });

        // Close modal
        document.getElementById('closeFaceModal').addEventListener('click', closeFaceModal);
        document.getElementById('cancelFaceLogin').addEventListener('click', closeFaceModal);

        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target === faceModal) {
                closeFaceModal();
            }
        });

        function closeFaceModal() {
            if (faceStream) {
                faceStream.getTracks().forEach(track => track.stop());
                faceStream = null;
            }
            
            if (captureTimeout) {
                clearTimeout(captureTimeout);
                captureTimeout = null;
            }
            
            faceModal.style.display = 'none';
            document.getElementById('faceCountdown').style.display = 'none';
            document.getElementById('faceLoginLoader').style.display = 'none';
            document.getElementById('startFaceCapture').disabled = false;
        }

        // Start camera
        document.getElementById('startFaceCapture').addEventListener('click', async function() {
            try {
                this.disabled = true;
                
                // Request camera access
                faceStream = await navigator.mediaDevices.getUserMedia({ 
                    video: { 
                        facingMode: 'user',
                        width: { ideal: 1280 },
                        height: { ideal: 720 }
                    } 
                });
                
                faceVideo.srcObject = faceStream;
                
                document.getElementById('faceStatus').innerHTML = '<i class="fas fa-check-circle"></i> Camera ready! Capturing in 3 seconds...';
                
                // Start countdown after 1 second
                setTimeout(startCountdown, 1000);
                
            } catch (error) {
                console.error('Camera access error:', error);
                document.getElementById('faceStatus').innerHTML = '<i class="fas fa-exclamation-circle"></i> Camera access denied. Please enable camera permissions.';
                this.disabled = false;
            }
        });

        function startCountdown() {
            const countdownEl = document.getElementById('faceCountdown');
            const numberEl = countdownEl.querySelector('.countdown-number');
            
            countdownEl.style.display = 'block';
            let count = 3;
            
            const countdownInterval = setInterval(() => {
                count--;
                numberEl.textContent = count;
                
                if (count === 0) {
                    clearInterval(countdownInterval);
                    countdownEl.style.display = 'none';
                    captureFace();
                }
            }, 1000);
        }

        async function captureFace() {
            try {
                document.getElementById('faceStatus').innerHTML = '<i class="fas fa-camera"></i> Capturing...';
                
                // Set canvas dimensions to match video
                faceCanvas.width = faceVideo.videoWidth;
                faceCanvas.height = faceVideo.videoHeight;
                
                // Draw video frame to canvas (flip horizontally to match mirror view)
                const ctx = faceCanvas.getContext('2d');
                ctx.scale(-1, 1);
                ctx.drawImage(faceVideo, -faceCanvas.width, 0, faceCanvas.width, faceCanvas.height);
                ctx.setTransform(1, 0, 0, 1, 0, 0);
                
                // Convert canvas to base64
                const imageData = faceCanvas.toDataURL('image/jpeg', 0.9);
                
                // Stop camera
                if (faceStream) {
                    faceStream.getTracks().forEach(track => track.stop());
                    faceStream = null;
                }
                
                // Show loader
                document.getElementById('faceLoginLoader').style.display = 'block';
                document.getElementById('faceStatus').innerHTML = '<i class="fas fa-sync fa-spin"></i> Analyzing face...';
                
                // Send to server for recognition
                const response = await fetch('face-login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        image: imageData
                    })
                });
                
                // Check if response is OK
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const result = await response.json();
                
                console.log('Face recognition result:', result);
                
                document.getElementById('faceLoginLoader').style.display = 'none';
                
                if (result.success) {
                    // Show success message
                    document.getElementById('faceStatus').innerHTML = 
                        '<i class="fas fa-check-circle"></i> Welcome back, ' + result.username + '! (Match: ' + result.similarity + '%) Redirecting...';
                    document.getElementById('faceStatus').style.background = 'rgba(76, 175, 80, 0.9)';
                    document.getElementById('faceStatus').style.borderColor = '#4caf50';
                    
                    // Show success popup with debug info
                    let debugInfo = '';
                    if (result.debug && result.debug.all_scores) {
                        debugInfo = '<br><small style="font-size: 12px; opacity: 0.8;">All matches: ';
                        result.debug.all_scores.slice(0, 3).forEach(function(score) {
                            debugInfo += score.username + ' (' + score.similarity + '%), ';
                        });
                        debugInfo = debugInfo.slice(0, -2) + '</small>';
                    }
                    
                    showFaceResultPopup('success', 'Face Recognized!', 
                        'Welcome back, ' + result.username + '!<br>Match confidence: ' + result.similarity + '%' + debugInfo);
                    
                    // Redirect after 2 seconds
                    setTimeout(() => {
                        window.location.href = result.redirect;
                    }, 2000);
                } else {
                    // Show error message with debug info
                    const errorMsg = result.message || 'No matching user found. Please try again.';
                    document.getElementById('faceStatus').innerHTML = 
                        '<i class="fas fa-times-circle"></i> Recognition failed';
                    document.getElementById('faceStatus').style.background = 'rgba(244, 67, 54, 0.9)';
                    document.getElementById('faceStatus').style.borderColor = '#f44336';
                    
                    // Build detailed debug message
                    let debugMsg = '<strong>' + errorMsg + '</strong>';
                    
                    // If we have debug data with scores, show them
                    if (result.debug && result.debug.all_scores && result.debug.all_scores.length > 0) {
                        debugMsg += '<br><br><div style="text-align: left; font-size: 13px;">';
                        debugMsg += '<strong>📊 Debug Info:</strong><br>';
                        debugMsg += '• Captured Quality: <span style="color: #4caf50;">' + result.debug.captured_quality + '%</span><br>';
                        debugMsg += '• Required Match: <span style="color: #ff9800;">' + result.debug.threshold + '%</span><br>';
                        debugMsg += '• Best Match: <span style="color: #00bcd4;">' + result.debug.best_match + ' (' + result.debug.best_score + '%)</span><br>';
                        if (result.debug.using_api) {
                            debugMsg += '• Using: <span style="color: #4caf50;">✓ Real Face Comparison API</span><br>';
                        }
                        debugMsg += '<br><strong>🔍 Top Matches:</strong><br>';
                        result.debug.all_scores.slice(0, 5).forEach(function(score, index) {
                            let color = score.similarity >= result.debug.threshold ? '#4caf50' : '#f44336';
                            let apiMatch = score.api_match ? ' [API: ' + score.api_match + ']' : '';
                            debugMsg += (index + 1) + '. ' + score.username + ': ';
                            debugMsg += '<span style="color: ' + color + ';">' + score.similarity + '%</span>' + apiMatch + ' ';
                            debugMsg += '(Profile: ' + score.profile_quality + '%)<br>';
                        });
                        debugMsg += '</div>';
                    } else {
                        // No face detected at all - show captured image preview and helpful message
                        debugMsg += '<br><br><div style="text-align: center;">';
                        debugMsg += '<strong>📸 Captured Image Preview:</strong><br>';
                        debugMsg += '<img src="' + imageData + '" style="max-width: 100%; max-height: 200px; border-radius: 10px; margin: 10px 0; border: 2px solid #ff9800;" /><br>';
                        debugMsg += '<small style="color: #aaa;">This is what the API received</small>';
                        debugMsg += '</div>';
                        
                        debugMsg += '<br><div style="text-align: left; font-size: 13px;">';
                        debugMsg += '<strong>💡 Tips:</strong><br>';
                        debugMsg += '• Make sure your face is clearly visible<br>';
                        debugMsg += '• Use good lighting (not too dark/bright)<br>';
                        debugMsg += '• Face the camera directly<br>';
                        debugMsg += '• Remove glasses if needed<br>';
                        debugMsg += '• Try moving closer to the camera<br><br>';
                        debugMsg += '<strong>🔧 API Check:</strong><br>';
                        debugMsg += '• Verify API key is set in FaceConfig.php<br>';
                        debugMsg += '• Check browser console (F12) for errors';
                        debugMsg += '</div>';
                    }
                    
                    // Show error popup with debug
                    showFaceResultPopup('error', 'Recognition Failed', debugMsg);
                    
                    // Reset after 10 seconds (more time to read debug info and see image)
                    setTimeout(() => {
                        closeFaceModal();
                    }, 10000);
                }
                
            } catch (error) {
                console.error('Face capture error:', error);
                document.getElementById('faceLoginLoader').style.display = 'none';
                
                const errorMessage = '<strong>Connection error occurred</strong><br><br>' +
                    '<div style="text-align: left; font-size: 13px;">' +
                    '<strong>Error Details:</strong><br>' + error.message + 
                    '<br><br><strong>Possible Causes:</strong><br>' +
                    '• face-login.php file not found<br>' +
                    '• Server error<br>' +
                    '• Network issue<br>' +
                    '• API key not configured' +
                    '</div>';
                
                document.getElementById('faceStatus').innerHTML = 
                    '<i class="fas fa-exclamation-circle"></i> Connection error';
                document.getElementById('faceStatus').style.background = 'rgba(244, 67, 54, 0.9)';
                document.getElementById('faceStatus').style.borderColor = '#f44336';
                
                // Show error popup alert
                showFaceResultPopup('error', 'Connection Error', errorMessage);
                
                // Reset after 8 seconds
                setTimeout(() => {
                    closeFaceModal();
                }, 8000);
            }
        }

        // Function to show result popup
        function showFaceResultPopup(type, title, message) {
            // Create popup element
            const popup = document.createElement('div');
            popup.className = 'face-result-popup';
            popup.innerHTML = `
                <div class="face-result-content ${type}">
                    <div class="face-result-icon">
                        ${type === 'success' 
                            ? '<i class="fas fa-check-circle"></i>' 
                            : '<i class="fas fa-exclamation-circle"></i>'}
                    </div>
                    <h3 class="face-result-title">${title}</h3>
                    <p class="face-result-message">${message}</p>
                </div>
            `;
            
            document.body.appendChild(popup);
            
            // Trigger animation
            setTimeout(() => {
                popup.classList.add('show');
            }, 10);
            
            // Auto remove after 4 seconds
            setTimeout(() => {
                popup.classList.remove('show');
                setTimeout(() => {
                    popup.remove();
                }, 300);
            }, 4000);
        }
        
        // ========== AUTO-HIDE SERVER MESSAGES ==========
        window.addEventListener('load', function() {
            const messageContainer = document.getElementById('message-container');
            
            if (messageContainer) {
                const errorBox = document.getElementById('error-message');
                const isBanned = errorBox && errorBox.classList.contains('banned-error');
                
                if (!isBanned) {
                    setTimeout(function() {
                        const messages = messageContainer.querySelectorAll('.message-box');
                        messages.forEach(function(message) {
                            message.classList.add('fade-out');
                        });
                        
                        setTimeout(function() {
                            messageContainer.style.display = 'none';
                        }, 300);
                    }, 5000);
                }
            }

            <?php if (!empty($success)): ?>
            setTimeout(() => {
                container.classList.remove("right-panel-active");
            }, 100);
            <?php endif; ?>
        });
    </script>
</body>
</html><?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../controller/UserController.php';

$controller = new UserController();
$errors = [];
$success = '';

// Handle Google login errors
if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'no_code':
            $errors[] = 'Google authentication failed. Please try again.';
            break;
        case 'google_auth_failed':
            $errors[] = 'Failed to authenticate with Google. Please try again.';
            break;
        case 'link_failed':
            $errors[] = 'Failed to link Google account. Please try again.';
            break;
        case 'registration_failed':
            $errors[] = 'Failed to create account with Google. Please try again.';
            break;
    }
}

// Handle Sign Up
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'signup') {
    // Vérifier le CAPTCHA côté serveur
    $recaptchaSecret = '6Ld8BxIsAAAAAMZnO7ypbmWefzS7e1Mgs5qRDK4_';
    $recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';
    
    if (empty($recaptchaResponse)) {
        $errors[] = 'Please complete the CAPTCHA verification';
    } else {
        // Vérifier le CAPTCHA auprès de Google
        $verifyURL = 'https://www.google.com/recaptcha/api/siteverify';
        $response = file_get_contents($verifyURL . '?secret=' . $recaptchaSecret . '&response=' . $recaptchaResponse);
        $responseData = json_decode($response);
        
        if (!$responseData->success) {
            $errors[] = 'CAPTCHA verification failed. Please try again.';
        }
    }
    
    // Si pas d'erreur CAPTCHA, procéder à l'inscription
    if (empty($errors)) {
        $result = $controller->register(
            $_POST['username'] ?? '',
            $_POST['email'] ?? '',
            $_POST['dob'] ?? '',
            $_POST['password'] ?? '',
            $_POST['password'] ?? '',
            $_POST['gender'] ?? ''
        );
        
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $errors = $result['errors'];
        }
    }
}

// Handle Sign In
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'signin') {
    $result = $controller->login(
        $_POST['email'] ?? '',
        $_POST['password'] ?? ''
    );
    
    if ($result['success']) {
        // Redirect based on role
        header('Location: ' . $result['redirect']);
        exit();
    } else {
        $errors = $result['errors'];
    }
}
?>