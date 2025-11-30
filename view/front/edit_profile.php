<?php
require_once __DIR__ . '/../../controller/UserController.php';

// Check if user is logged in
if (!UserController::isLoggedIn()) {
    header('Location: login.php');
    exit();
}

// Get current user
$currentUser = UserController::getCurrentUser();
if (!$currentUser) {
    header('Location: login.php');
    exit();
}

$controller = new UserController();
$errors = [];
$success = '';
$shouldRedirect = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $dob = trim($_POST['dob'] ?? '');
    $imagePath = $currentUser->getImage();
    
    // Server-side validation
    if (empty($username)) {
        $errors[] = 'Username is required';
    }
    
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }
    
    if (empty($dob)) {
        $errors[] = 'Date of birth is required';
    } else {
        // Validate date of birth - cannot be in the future
        $dobDate = new DateTime($dob);
        $today = new DateTime();
        if ($dobDate > $today) {
            $errors[] = 'Date of birth cannot be in the future!';
        }
    }
    
    // Handle image upload
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
        $uploadResult = $controller->uploadProfileImage($_FILES['avatar']);
        
        if ($uploadResult['success']) {
            $imagePath = $uploadResult['filename'];
        } else {
            $errors = array_merge($errors, $uploadResult['errors']);
        }
    }
    
    // Update profile if no errors
    if (empty($errors)) {
        $result = $controller->updateProfile(
            $currentUser->getId(),
            $username,
            $email,
            $dob,
            $imagePath
        );
        
        if ($result['success']) {
            $success = 'Profile updated successfully! Redirecting to your profile...';
            $shouldRedirect = true;
            // Refresh user data
            $currentUser = UserController::getCurrentUser();
        } else {
            $errors = $result['errors'];
        }
    }
}

// Get user image path - NO DEFAULT IMAGE
$userImage = null;
if ($currentUser->getImage()) {
    $userImage = '../../view/' . $currentUser->getImage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FoxUnity - Edit Profile</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Orbitron:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        /* User Dropdown Menu Styles - SAME AS ADMIN */
        .user-dropdown {
            position: relative;
            display: inline-block;
        }

        .username-display {
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            padding: 5px 10px;
            border-radius: 8px;
        }

        .username-display:hover {
            background: rgba(255, 122, 0, 0.1);
        }

        .username-display img {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #ff7a00;
        }

        .username-display span {
            color: #ff7a00;
            font-weight: 600;
        }

        .username-display i.fa-chevron-down {
            font-size: 12px;
            color: #ff7a00;
            transition: transform 0.3s ease;
        }

        .user-dropdown.active .username-display i.fa-chevron-down {
            transform: rotate(180deg);
        }

        .dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 10px;
            background: rgba(20, 20, 20, 0.98);
            border: 2px solid rgba(255, 122, 0, 0.3);
            border-radius: 12px;
            min-width: 200px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            z-index: 1000;
            overflow: hidden;
        }

        .user-dropdown.active .dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .dropdown-item {
            padding: 12px 15px;
            color: #fff;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }

        .dropdown-item:hover {
            background: rgba(255, 122, 0, 0.1);
            border-left-color: #ff7a00;
        }

        .dropdown-item i {
            font-size: 16px;
            color: #ff7a00;
            width: 20px;
        }

        .dropdown-divider {
            height: 1px;
            background: rgba(255, 122, 0, 0.2);
            margin: 5px 0;
        }

        .dropdown-item.logout {
            color: #ff4444;
        }

        .dropdown-item.logout i {
            color: #ff4444;
        }

        .dropdown-item.logout:hover {
            background: rgba(255, 68, 68, 0.1);
            border-left-color: #ff4444;
        }

        /* Cart icon styling */
        .cart-icon {
            color: #ff7a00 !important;
            position: relative;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .cart-icon:hover {
            color: #ff9933 !important;
            transform: translateY(-2px);
        }
        
        .cart-icon i {
            color: #ff7a00;
            font-size: 18px;
        }
        
        .cart-count {
            background: linear-gradient(135deg, #ff7a00, #ff4f00);
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 11px;
            font-weight: 700;
            position: absolute;
            top: -8px;
            right: -8px;
            min-width: 18px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(255, 122, 0, 0.4);
        }

        .edit-profile-section {
            padding: 60px 0 80px;
            position: relative;
            z-index: 10;
        }

        .page-header {
            margin-bottom: 40px;
        }

        .page-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 36px;
            color: #fff;
            margin-bottom: 10px;
        }

        .page-subtitle {
            color: #888;
            font-size: 16px;
        }

        .edit-container {
            max-width: 900px;
            margin: 0 auto;
        }

        .edit-card {
            background: linear-gradient(135deg, rgba(20, 20, 20, 0.9) 0%, rgba(10, 10, 10, 0.9) 100%);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.4);
            backdrop-filter: blur(10px);
            margin-bottom: 30px;
        }

        .section-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 22px;
            color: #fff;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #ff7a00;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: #ff7a00;
        }

        .avatar-upload {
            display: flex;
            align-items: center;
            gap: 30px;
            margin-bottom: 30px;
        }

        .current-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid rgba(255, 122, 0, 0.3);
        }

        .avatar-actions {
            flex: 1;
        }

        .avatar-actions h4 {
            color: #fff;
            font-size: 16px;
            margin-bottom: 10px;
        }

        .avatar-actions p {
            color: #888;
            font-size: 13px;
            margin-bottom: 15px;
        }

        .file-upload-btn {
            display: inline-block;
            padding: 10px 20px;
            background: rgba(255, 122, 0, 0.1);
            border: 2px solid #ff7a00;
            color: #ff7a00;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
            font-size: 14px;
        }

        .file-upload-btn:hover {
            background: rgba(255, 122, 0, 0.2);
            transform: translateY(-2px);
        }

        .file-upload-btn input[type="file"] {
            display: none;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
            margin-bottom: 25px;
        }

        /* Input Group avec validation */
        .input-group {
            margin-bottom: 20px;
        }

        .input-group.full-width {
            grid-column: 1 / -1;
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

        .form-input,
        .form-textarea,
        .form-select {
            width: 100%;
            padding: 14px 18px;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: #fff;
            font-size: 15px;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
        }

        /* États de validation */
        .form-input.valid,
        .form-select.valid {
            border-color: #4caf50 !important;
            background: rgba(76, 175, 80, 0.05) !important;
        }

        .form-input.invalid,
        .form-select.invalid {
            border-color: #f44336 !important;
            background: rgba(244, 67, 54, 0.05) !important;
        }

        /* Message de validation */
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

        .form-input:focus,
        .form-textarea:focus,
        .form-select:focus {
            outline: none;
            border-color: #ff7a00;
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 0 20px rgba(255, 122, 0, 0.2);
        }

        .form-textarea {
            min-height: 120px;
            resize: vertical;
        }

        .form-input::placeholder,
        .form-textarea::placeholder {
            color: #666;
        }

        .form-hint {
            color: #666;
            font-size: 12px;
            margin-top: 6px;
        }

        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .btn-cancel {
            padding: 14px 30px;
            background: transparent;
            border: 2px solid rgba(255, 255, 255, 0.2);
            color: #fff;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
            font-size: 15px;
            text-decoration: none;
            display: inline-block;
        }

        .btn-cancel:hover {
            border-color: rgba(255, 255, 255, 0.4);
            background: rgba(255, 255, 255, 0.05);
        }

        .btn-save {
            padding: 14px 30px;
            background: linear-gradient(135deg, #ff7a00, #ff4f00);
            border: none;
            color: #fff;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 700;
            font-size: 15px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(255, 122, 0, 0.4);
        }

        .message-box {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-weight: 600;
        }

        .success-message {
            background: rgba(76, 175, 80, 0.1);
            border: 2px solid #4caf50;
            color: #4caf50;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .error-message {
            background: rgba(244, 67, 54, 0.1);
            border: 2px solid #f44336;
            color: #f44336;
        }

        .error-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .error-list li {
            margin: 5px 0;
        }

        /* Custom Confirmation Modal */
        .confirm-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.85);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            animation: fadeIn 0.3s ease;
        }

        .confirm-modal.show {
            display: flex;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .confirm-box {
            background: linear-gradient(135deg, rgba(20, 20, 20, 0.98) 0%, rgba(10, 10, 10, 0.98) 100%);
            border: 2px solid rgba(255, 122, 0, 0.5);
            border-radius: 20px;
            padding: 40px;
            max-width: 500px;
            text-align: center;
            animation: scaleIn 0.3s ease;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.7);
        }

        @keyframes scaleIn {
            from { transform: scale(0.8); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        .confirm-box h3 {
            color: #fff;
            font-family: 'Orbitron', sans-serif;
            font-size: 24px;
            margin-bottom: 15px;
        }

        .confirm-box p {
            color: #aaa;
            font-size: 16px;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .confirm-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        .btn-confirm-yes, .btn-confirm-no {
            padding: 12px 30px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 700;
            font-size: 14px;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
        }

        .btn-confirm-yes {
            background: linear-gradient(135deg, #ff7a00, #ff4f00);
            color: white;
        }

        .btn-confirm-yes:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255, 122, 0, 0.4);
        }

        .btn-confirm-no {
            background: transparent;
            border: 2px solid rgba(255, 255, 255, 0.3);
            color: #fff;
        }

        .btn-confirm-no:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.5);
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }

            .avatar-upload {
                flex-direction: column;
                text-align: center;
            }

            .form-actions {
                flex-direction: column;
            }

            .btn-cancel,
            .btn-save {
                width: 100%;
            }

            .edit-card {
                padding: 25px;
            }
        }
    </style>
</head>
<body>
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

    <!-- ========== HEADER ========== -->
    <header class="site-header">
        <div class="logo-section">
            <img src="../images/Nine__1_-removebg-preview.png" alt="FoxUnity Logo" class="site-logo">
            <span class="site-name">FoxUnity</span>
        </div>
        
        <nav class="site-nav">
            <a href="index.php">Home</a>
            <a href="events.html">Events</a>
            <a href="shop.html">Shop</a>
            <a href="trading.html">Trading</a>
            <a href="news.html">News</a>
            <a href="reclamation.html">Complaints</a>
            <a href="about.html">About Us</a>
        </nav>
        
        <div class="header-right">
            <div class="user-dropdown" id="userDropdown">
                <div class="username-display">
                    <?php if ($userImage): ?>
                        <img src="<?php echo htmlspecialchars($userImage); ?>" alt="Profile">
                    <?php else: ?>
                        <i class="fas fa-user-circle"></i>
                    <?php endif; ?>
                    <span><?php echo htmlspecialchars($currentUser->getUsername()); ?></span>
                    <i class="fas fa-chevron-down"></i>
                </div>
                
                <div class="dropdown-menu">
                    <a href="profile.php" class="dropdown-item">
                        <i class="fas fa-user"></i>
                        <span>My Profile</span>
                    </a>
                    
                    <?php 
                    $userRole = strtolower($currentUser->getRole());
                    if ($userRole === 'admin' || $userRole === 'superadmin'): 
                    ?>
                    <a href="../back/dashboard.php" class="dropdown-item">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                    <?php endif; ?>
                    
                    <div class="dropdown-divider"></div>
                    
                    <a href="logout.php" class="dropdown-item logout">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </div>
            
            <a href="panier.html" class="cart-icon">
                <i class="fas fa-shopping-cart"></i> Cart
                <span class="cart-count">0</span>
            </a>
        </div>
    </header>

    <!-- ========== EDIT PROFILE SECTION ========== -->
    <main class="main-section">
        <section class="edit-profile-section">
            <div class="edit-container">
                <div class="page-header">
                    <h1 class="page-title"><i class="fas fa-user-edit"></i> Edit Profile</h1>
                    <p class="page-subtitle">Update your profile information and preferences</p>
                </div>

                <?php if (!empty($success)): ?>
                <div class="message-box success-message">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo htmlspecialchars($success); ?></span>
                </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                <div class="message-box error-message">
                    <ul class="error-list">
                        <?php foreach ($errors as $error): ?>
                            <li><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" id="editProfileForm" novalidate>
                    <!-- Profile Picture -->
                    <div class="edit-card">
                        <h3 class="section-title"><i class="fas fa-camera"></i> Profile Picture</h3>
                        <div class="avatar-upload">
                            <?php if ($userImage): ?>
                                <img src="<?php echo htmlspecialchars($userImage); ?>" alt="Avatar" class="current-avatar" id="avatar-preview">
                            <?php else: ?>
                                <div class="current-avatar" id="avatar-preview" style="display: flex; align-items: center; justify-content: center; background: rgba(255, 122, 0, 0.1);">
                                    <i class="fas fa-user-circle" style="font-size: 60px; color: #ff7a00;"></i>
                                </div>
                            <?php endif; ?>
                            <div class="avatar-actions">
                                <h4>Change Avatar</h4>
                                <p>Recommended: Square image, at least 400x400px (Max 5MB)</p>
                                <label class="file-upload-btn">
                                    <i class="fas fa-upload"></i> Upload Photo
                                    <input type="file" accept="image/*" name="avatar" id="avatar-input" onchange="previewAvatar(event)">
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Personal Information -->
                    <div class="edit-card">
                        <h3 class="section-title"><i class="fas fa-user"></i> Personal Information</h3>
                        
                        <div class="form-row">
                            <div class="input-group">
                                <label class="form-label"><i class="fas fa-id-card"></i> Username</label>
                                <input type="text" id="username" class="form-input" name="username" value="<?php echo htmlspecialchars($currentUser->getUsername()); ?>" placeholder="Enter your username">
                                <div class="validation-message" id="username-message"></div>
                            </div>
                            <div class="input-group">
                                <label class="form-label"><i class="fas fa-envelope"></i> Email</label>
                                <input type="text" id="email" class="form-input" name="email" value="<?php echo htmlspecialchars($currentUser->getEmail()); ?>" placeholder="your@email.com">
                                <div class="validation-message" id="email-message"></div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="input-group">
                                <label class="form-label"><i class="fas fa-calendar"></i> Date of Birth</label>
                                <input type="date" id="dob" class="form-input" name="dob" value="<?php echo htmlspecialchars($currentUser->getDob()); ?>">
                                <div class="validation-message" id="dob-message"></div>
                            </div>
                            <div class="input-group">
                                <label class="form-label"><i class="fas fa-user-tag"></i> Role</label>
                                <input type="text" class="form-input" value="<?php echo htmlspecialchars($currentUser->getRole()); ?>" disabled>
                                <p class="form-hint">Your role cannot be changed</p>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="form-actions">
                        <a href="profile.php" class="btn-cancel">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        <button type="submit" class="btn-save">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </section>
    </main>

    <!-- ========== FOOTER ========== -->
    <footer class="site-footer">
        <div class="footer-content">
            <div class="footer-section">
                <h4>FoxUnity</h4>
                <p>Gaming for Good - Every action makes a difference</p>
            </div>
            <div class="footer-section">
                <h4>Back to Top</h4>
                <a href="#" class="back-to-top-link" onclick="window.scrollTo({top: 0, behavior: 'smooth'}); return false;">
                    <i class="fas fa-arrow-up"></i> Scroll to Top
                </a>
            </div>
            <div class="footer-section">
                <h4>Complaints</h4>
                <a href="reclamation.html">File a Complaint</a>
                <a href="#">Refund Policy</a>
                <a href="#">Privacy Policy</a>
            </div>
            <div class="footer-section">
                <h4>Follow Us</h4>
                <div class="social-links">
                    <a href="#"><i class="fab fa-discord"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
            <div class="footer-section">
                <h4>Dashboard</h4>
                <a href="../back/dashboard.html" class="dashboard-link">
                    <i class="fas fa-tachometer-alt"></i> My Dashboard
                </a>
            </div>
        </div>
        <div class="footer-bottom">
            <p>© 2025 FoxUnity. All rights reserved. Made with <span>♥</span> by gamers for gamers</p>
        </div>
    </footer>

    <!-- Custom Confirmation Modal -->
    <div class="confirm-modal" id="confirmModal">
        <div class="confirm-box">
            <h3><i class="fas fa-question-circle" style="color: #ff7a00;"></i> Confirm Changes</h3>
            <p>Are you sure you want to update your profile information?</p>
            <div class="confirm-buttons">
                <button class="btn-confirm-yes" id="confirmYes">
                    <i class="fas fa-check"></i> Yes, Update
                </button>
                <button class="btn-confirm-no" id="confirmNo">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>
        </div>
    </div>

    <script>
        // ========== DROPDOWN MENU TOGGLE ==========
        document.addEventListener('DOMContentLoaded', function() {
            const userDropdown = document.getElementById('userDropdown');
            
            if (userDropdown) {
                const usernameDisplay = userDropdown.querySelector('.username-display');
                
                usernameDisplay.addEventListener('click', function(e) {
                    e.stopPropagation();
                    userDropdown.classList.toggle('active');
                });
                
                document.addEventListener('click', function(e) {
                    if (!userDropdown.contains(e.target)) {
                        userDropdown.classList.remove('active');
                    }
                });
                
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        userDropdown.classList.remove('active');
                    }
                });
            }
            
            // Update cart count from localStorage
            const cart = JSON.parse(localStorage.getItem('cart')) || [];
            const cartCount = document.querySelector('.cart-count');
            if (cartCount) {
                cartCount.textContent = cart.length;
            }
        });

        // ========== VALIDATION FUNCTIONS ==========
        
        // Helper function pour afficher validation
        function showValidation(inputId, messageId, isValid, message) {
            const input = document.getElementById(inputId);
            const messageEl = document.getElementById(messageId);
            
            if (!input || !messageEl) return;
            
            input.classList.remove('valid', 'invalid');
            messageEl.classList.remove('success', 'error');
            
            if (isValid) {
                input.classList.add('valid');
                messageEl.classList.add('success');
                messageEl.innerHTML = '<i class="fas fa-check-circle"></i> ' + message;
            } else if (message) {
                input.classList.add('invalid');
                messageEl.classList.add('error');
                messageEl.innerHTML = '<i class="fas fa-times-circle"></i> ' + message;
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
                showValidation('username', 'username-message', false, 'Min 3 characters');
                return false;
            } else {
                showValidation('username', 'username-message', true, 'Valid');
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
                showValidation('email', 'email-message', false, 'Invalid email');
                return false;
            } else {
                showValidation('email', 'email-message', true, 'Valid');
                return true;
            }
        }

        // Validation Date of Birth
        function validateDOB() {
            const dob = document.getElementById('dob').value;
            
            if (!dob) {
                showValidation('dob', 'dob-message', false, 'Required');
                return false;
            }
            
            // Vérifier que la date n'est pas dans le futur
            const dobDate = new Date(dob);
            const today = new Date();
            today.setHours(0, 0, 0, 0); // Reset time to compare only dates
            
            if (dobDate > today) {
                showValidation('dob', 'dob-message', false, 'Cannot be in the future');
                return false;
            } else {
                showValidation('dob', 'dob-message', true, 'Valid');
                return true;
            }
        }

        // ========== EVENT LISTENERS - REAL-TIME VALIDATION ==========
        document.getElementById('username').addEventListener('input', validateUsername);
        document.getElementById('username').addEventListener('blur', validateUsername);

        document.getElementById('email').addEventListener('input', validateEmail);
        document.getElementById('email').addEventListener('blur', validateEmail);

        document.getElementById('dob').addEventListener('change', validateDOB);
        document.getElementById('dob').addEventListener('blur', validateDOB);

        // ========== PREVIEW AVATAR ==========
        function previewAvatar(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                const preview = document.getElementById('avatar-preview');
                
                reader.onload = function(e) {
                    preview.innerHTML = '';
                    preview.style.backgroundImage = 'url(' + e.target.result + ')';
                    preview.style.backgroundSize = 'cover';
                    preview.style.backgroundPosition = 'center';
                };
                reader.readAsDataURL(file);
            }
        }

        // ========== FORM SUBMISSION WITH VALIDATION ==========
        const form = document.getElementById('editProfileForm');
        const confirmModal = document.getElementById('confirmModal');
        const confirmYes = document.getElementById('confirmYes');
        const confirmNo = document.getElementById('confirmNo');
        let formSubmitPending = false;

        form.addEventListener('submit', function(e) {
            if (!formSubmitPending) {
                e.preventDefault();
                
                // Valider tous les champs
                const isUsernameValid = validateUsername();
                const isEmailValid = validateEmail();
                const isDOBValid = validateDOB();
                
                // Si tout est valide, montrer modal
                if (isUsernameValid && isEmailValid && isDOBValid) {
                    confirmModal.classList.add('show');
                }
            }
        });

        // Confirm changes
        confirmYes.addEventListener('click', function() {
            formSubmitPending = true;
            confirmModal.classList.remove('show');
            form.submit();
        });

        // Cancel changes
        confirmNo.addEventListener('click', function() {
            confirmModal.classList.remove('show');
        });

        // Close modal on outside click
        confirmModal.addEventListener('click', function(e) {
            if (e.target === confirmModal) {
                confirmModal.classList.remove('show');
            }
        });

        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && confirmModal.classList.contains('show')) {
                confirmModal.classList.remove('show');
            }
        });

        // Auto-redirect to profile page after successful update
        <?php if (isset($shouldRedirect) && $shouldRedirect): ?>
        setTimeout(function() {
            window.location.href = 'profile.php';
        }, 3000);
        <?php endif; ?>
    </script>
</body>
</html>