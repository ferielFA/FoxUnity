<?php
require_once __DIR__ . '/../../controller/UserController.php';

$controller = new UserController();
$errors = [];
$success = '';

// Handle Sign Up
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'signup') {
    // Vérifier le CAPTCHA côté serveur
    $recaptchaSecret = '6Ld8BxIsAAAAAMZnO7ypbmWefzS7e1Mgs5qRDK4_'; // À remplacer par votre clé secrète
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

        /* Error/Success Messages - Fixed positioning */
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
            min-height: 650px;
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

        .form-input {
            background-color: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 255, 255, 0.1);
            padding: 10px 14px;
            margin: 5px 0;
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

        /* Style pour les champs de mot de passe avec icône */
        .password-wrapper {
            position: relative;
            width: 100%;
            margin: 5px 0;
        }

        .password-wrapper .form-input {
            margin: 0;
            padding-right: 45px;
        }

        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
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
            margin: 10px 0;
        }

        .form-half {
            flex: 1;
            margin: 0 !important;
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
        }

        /* Correction de la couleur du select Gender */
        select.form-input option {
            background-color: #1a1a1a;
            color: #fff;
        }

        select.form-input option[value=""] {
            color: #666;
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
            margin-top: 8px;
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
                min-height: 550px;
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

        <!-- Messages Container (Fixed Position) -->
        <?php if (!empty($errors) || !empty($success)): ?>
        <div class="message-container" id="message-container">
            <?php if (!empty($errors)): ?>
            <div class="message-box error-box <?php echo (in_array('ACCOUNT_BANNED', $errors)) ? 'banned-error' : ''; ?>" id="error-message">
                <ul class="error-list">
                    <?php foreach ($errors as $error): ?>
                        <?php if ($error === 'ACCOUNT_BANNED'): ?>
                            <li><i class="fas fa-ban"></i> Your account has been banned. Please contact support for more information.</li>
                        <?php else: ?>
                            <li><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
            <div class="message-box success-box" id="success-message">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- ========== AUTH CONTENT ========== -->
        <div class="auth-main-content">
            <div class="auth-container" id="container">
                <!-- Sign Up Form -->
                <div class="form-container sign-up-container">
                    <form action="login.php" method="POST" id="signUpForm">
                        <div class="form-content">
                            <input type="hidden" name="action" value="signup">
                            <h1 class="form-title">Create Account</h1>
                            <div class="social-container">
                                <a href="#" class="social"><i class="fab fa-google"></i></a>
                                <a href="#" class="social"><i class="fab fa-discord"></i></a>
                                <a href="#" class="social"><i class="fab fa-steam"></i></a>
                            </div>
                            <span class="form-subtitle">or use your email for registration</span>
                            <input type="text" class="form-input" name="username" placeholder="Username (min. 3 characters)" required />
                            <input type="email" class="form-input" name="email" placeholder="Email" required />
                            <div class="form-row-inline">
                                <input type="date" class="form-input form-half" name="dob" required />
                                <select class="form-input form-half" name="gender" required>
                                    <option value="" disabled selected>Gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div>
                            <div class="password-wrapper">
                                <input type="password" class="form-input" id="signupPassword" name="password" placeholder="Password (8+ chars, uppercase, number, special char)" required />
                                <i class="fas fa-eye toggle-password" onclick="togglePassword('signupPassword', this)"></i>
                            </div>
                            <div class="password-wrapper">
                                <input type="password" class="form-input" id="signupPasswordConfirm" name="password_confirm" placeholder="Confirm Password" required />
                                <i class="fas fa-eye toggle-password" onclick="togglePassword('signupPasswordConfirm', this)"></i>
                            </div>
                            <div class="captcha-container">
                                <div class="g-recaptcha" data-sitekey="6Ld8BxIsAAAAAGuOxS5dtvoBw-ZbRmRF-MZDww-M"></div>
                            </div>
                            <button type="submit" class="auth-button">Sign Up</button>
                        </div>
                    </form>
                </div>

                <!-- Sign In Form -->
                <div class="form-container sign-in-container">
                    <form action="login.php" method="POST">
                        <div class="form-content">
                            <input type="hidden" name="action" value="signin">
                            <h1 class="form-title">Sign In</h1>
                            <div class="social-container">
                                <a href="#" class="social"><i class="fab fa-google"></i></a>
                                <a href="#" class="social"><i class="fab fa-discord"></i></a>
                                <a href="#" class="social"><i class="fab fa-steam"></i></a>
                            </div>
                            <span class="form-subtitle">or use your account</span>
                            <input type="email" class="form-input" name="email" placeholder="Email" required />
                            <div class="password-wrapper">
                                <input type="password" class="form-input" id="signinPassword" name="password" placeholder="Password" required />
                                <i class="fas fa-eye toggle-password" onclick="togglePassword('signinPassword', this)"></i>
                            </div>
                            <a href="forgot-password.html" class="forgot-link">Forgot your password?</a>
                            <button type="submit" class="auth-button">Sign In</button>
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

        const signUpButton = document.getElementById('signUp');
        const signInButton = document.getElementById('signIn');
        const container = document.getElementById('container');
        
        signUpButton.addEventListener('click', () => {
            container.classList.add("right-panel-active");
        });
        
        signInButton.addEventListener('click', () => {
            container.classList.remove("right-panel-active");
        });

        // ========== VALIDATION DU FORMULAIRE SIGN UP ==========
        const signUpForm = document.getElementById('signUpForm');
        
        signUpForm.addEventListener('submit', function(e) {
            const username = document.querySelector('.sign-up-container input[name="username"]').value.trim();
            const email = document.querySelector('.sign-up-container input[name="email"]').value.trim();
            const dob = document.querySelector('.sign-up-container input[name="dob"]').value;
            const gender = document.querySelector('.sign-up-container select[name="gender"]').value;
            const password = document.querySelector('.sign-up-container input[name="password"]').value;
            const passwordConfirm = document.querySelector('.sign-up-container input[name="password_confirm"]').value;
            
            let errors = [];
            
            // Validation Username (minimum 3 caractères)
            if (username.length < 3) {
                errors.push('Username must be at least 3 characters long');
            }
            
            // Validation Email
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                errors.push('Please enter a valid email address');
            }
            
            // Validation Date de naissance
            if (!dob) {
                errors.push('Date of birth is required');
            }
            
            // Validation Gender
            if (!gender) {
                errors.push('Please select your gender');
            }
            
            // Validation Password - Minimum 8 caractères
            if (password.length < 8) {
                errors.push('Password must be at least 8 characters long');
            }
            
            // Vérifier au moins une lettre majuscule
            if (!/[A-Z]/.test(password)) {
                errors.push('Password must contain at least one uppercase letter (A-Z)');
            }
            
            // Vérifier au moins une lettre minuscule
            if (!/[a-z]/.test(password)) {
                errors.push('Password must contain at least one lowercase letter (a-z)');
            }
            
            // Vérifier au moins un chiffre
            if (!/[0-9]/.test(password)) {
                errors.push('Password must contain at least one number (0-9)');
            }
            
            // Vérifier au moins un caractère spécial
            if (!/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)) {
                errors.push('Password must contain at least one special character (!@#$%^&*()_+-=[]{};\':"|,.<>/?)');
            }
            
            // Vérifier que les deux mots de passe correspondent
            if (password !== passwordConfirm) {
                errors.push('Passwords do not match! Please make sure both passwords are identical');
            }
            
            // Vérifier le CAPTCHA
            const recaptchaResponse = grecaptcha.getResponse();
            if (recaptchaResponse.length === 0) {
                errors.push('Please complete the CAPTCHA verification');
            }
            
            // Si des erreurs existent, empêcher la soumission
            if (errors.length > 0) {
                e.preventDefault();
                showValidationErrors(errors);
                return false;
            }
        });
        
        // Fonction pour afficher les erreurs de validation
        function showValidationErrors(errors) {
            // Supprimer les anciennes erreurs
            const oldMessages = document.getElementById('message-container');
            if (oldMessages) {
                oldMessages.remove();
            }
            
            // Créer le conteneur de messages
            const messageContainer = document.createElement('div');
            messageContainer.id = 'message-container';
            messageContainer.className = 'message-container';
            
            const errorBox = document.createElement('div');
            errorBox.className = 'message-box error-box';
            errorBox.id = 'error-message';
            
            const errorList = document.createElement('ul');
            errorList.className = 'error-list';
            
            errors.forEach(error => {
                const li = document.createElement('li');
                li.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${error}`;
                errorList.appendChild(li);
            });
            
            errorBox.appendChild(errorList);
            messageContainer.appendChild(errorBox);
            document.body.appendChild(messageContainer);
            
            // Auto-hide après 7 secondes
            setTimeout(() => {
                errorBox.classList.add('fade-out');
                setTimeout(() => {
                    messageContainer.style.display = 'none';
                }, 300);
            }, 7000);
        }

        // Auto-hide messages after 5 seconds
        window.addEventListener('load', function() {
            const messageContainer = document.getElementById('message-container');
            
            if (messageContainer) {
                const errorBox = document.getElementById('error-message');
                const isBanned = errorBox && errorBox.classList.contains('banned-error');
                
                // Don't auto-hide banned messages
                if (!isBanned) {
                    // After 5 seconds, add fade-out class
                    setTimeout(function() {
                        const messages = messageContainer.querySelectorAll('.message-box');
                        messages.forEach(function(message) {
                            message.classList.add('fade-out');
                        });
                        
                        // After animation completes, remove the container
                        setTimeout(function() {
                            messageContainer.style.display = 'none';
                        }, 300);
                    }, 5000);
                }
            }

            // Auto-switch to sign in if there's a success message
            <?php if (!empty($success)): ?>
            setTimeout(() => {
                container.classList.remove("right-panel-active");
            }, 100);
            <?php endif; ?>
        });
    </script>
</body>
</html>