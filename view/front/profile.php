<?php
require_once __DIR__ . '/../../controller/UserController.php';

// Check if user is logged in
if (!UserController::isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$currentUser = UserController::getCurrentUser();
if (!$currentUser) {
    header('Location: login.php');
    exit();
}

// Get user image - NO DEFAULT IMAGE
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
    <title>FoxUnity - My Profile</title>
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

        /* Profile Section Styles */
        .profile-section {
            padding: 60px 0;
            position: relative;
            z-index: 10;
        }

        .profile-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .profile-header {
            background: linear-gradient(135deg, rgba(255, 122, 0, 0.1) 0%, rgba(10, 10, 10, 0.5) 100%);
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 40px;
            border: 2px solid rgba(255, 122, 0, 0.2);
        }

        .profile-avatar-large {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #ff7a00;
            box-shadow: 0 10px 30px rgba(255, 122, 0, 0.4);
        }

        .profile-info {
            flex: 1;
        }

        .profile-username {
            font-family: 'Orbitron', sans-serif;
            font-size: 36px;
            color: #fff;
            margin: 0 0 10px;
        }

        .profile-email {
            color: #888;
            font-size: 18px;
            margin-bottom: 15px;
        }

        .profile-role {
            display: inline-block;
            background: rgba(255, 122, 0, 0.2);
            color: #ff7a00;
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
        }

        .profile-actions {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .btn-edit-profile {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(135deg, #ff7a00, #ff4f00);
            color: #fff;
            padding: 12px 30px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 700;
            transition: all 0.3s ease;
        }

        .btn-edit-profile:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(255, 122, 0, 0.4);
        }

        .profile-details {
            background: linear-gradient(135deg, rgba(20, 20, 20, 0.9) 0%, rgba(10, 10, 10, 0.9) 100%);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 30px;
        }

        .detail-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 24px;
            color: #fff;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #ff7a00;
        }

        .detail-row {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 20px;
            padding: 15px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            color: #888;
            font-weight: 600;
        }

        .detail-value {
            color: #fff;
        }

        .btn-delete {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(135deg, #f44336, #d32f2f);
            color: #fff;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 700;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
        }

        .btn-delete:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(244, 67, 54, 0.4);
        }

        /* Delete Confirmation Modal */
        .delete-modal {
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

        .delete-modal.show {
            display: flex;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .delete-box {
            background: linear-gradient(135deg, rgba(20, 20, 20, 0.98) 0%, rgba(10, 10, 10, 0.98) 100%);
            border: 2px solid rgba(244, 67, 54, 0.5);
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

        .delete-box h3 {
            color: #f44336;
            font-family: 'Orbitron', sans-serif;
            font-size: 24px;
            margin-bottom: 15px;
        }

        .delete-box p {
            color: #aaa;
            font-size: 16px;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .delete-box .warning-text {
            background: rgba(244, 67, 54, 0.1);
            border: 2px solid rgba(244, 67, 54, 0.3);
            padding: 15px;
            border-radius: 10px;
            color: #f44336;
            font-weight: 600;
            margin-bottom: 30px;
        }

        .delete-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        .btn-delete-confirm, .btn-delete-cancel {
            padding: 12px 30px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 700;
            font-size: 14px;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
        }

        .btn-delete-confirm {
            background: linear-gradient(135deg, #f44336, #d32f2f);
            color: white;
        }

        .btn-delete-confirm:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(244, 67, 54, 0.4);
        }

        .btn-delete-cancel {
            background: transparent;
            border: 2px solid rgba(255, 255, 255, 0.3);
            color: #fff;
        }

        .btn-delete-cancel:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.5);
        }

        @media (max-width: 768px) {
            .profile-header {
                flex-direction: column;
                text-align: center;
            }

            .profile-username {
                font-size: 28px;
            }

            .detail-row {
                grid-template-columns: 1fr;
                gap: 10px;
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
                    
                    <a href="tradehis.php" class="dropdown-item">
                        <i class="fas fa-history"></i>
                        <span>History</span>
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

    <!-- ========== PROFILE SECTION ========== -->
    <main class="main-section">
        <section class="profile-section">
            <div class="profile-container">
                <!-- Profile Header -->
                <div class="profile-header">
                    <?php if ($userImage): ?>
                        <img src="<?php echo htmlspecialchars($userImage); ?>" alt="Profile Avatar" class="profile-avatar-large">
                    <?php else: ?>
                        <div class="profile-avatar-large" style="display: flex; align-items: center; justify-content: center; background: rgba(255, 122, 0, 0.1);">
                            <i class="fas fa-user-circle" style="font-size: 80px; color: #ff7a00;"></i>
                        </div>
                    <?php endif; ?>
                    <div class="profile-info">
                        <h1 class="profile-username"><?php echo htmlspecialchars($currentUser->getUsername()); ?></h1>
                        <p class="profile-email"><?php echo htmlspecialchars($currentUser->getEmail()); ?></p>
                        <span class="profile-role"><i class="fas fa-gamepad"></i> <?php echo htmlspecialchars($currentUser->getRole()); ?></span>
                        <div class="profile-actions">
                            <a href="edit_profile.php" class="btn-edit-profile">
                                <i class="fas fa-edit"></i> Edit Profile
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Profile Details -->
                <div class="profile-details">
                    <h2 class="detail-title"><i class="fas fa-info-circle"></i> Account Information</h2>
                    
                    <div class="detail-row">
                        <div class="detail-label"><i class="fas fa-user"></i> Username:</div>
                        <div class="detail-value"><?php echo htmlspecialchars($currentUser->getUsername()); ?></div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label"><i class="fas fa-envelope"></i> Email:</div>
                        <div class="detail-value"><?php echo htmlspecialchars($currentUser->getEmail()); ?></div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label"><i class="fas fa-calendar"></i> Date of Birth:</div>
                        <div class="detail-value"><?php echo htmlspecialchars($currentUser->getDob()); ?></div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label"><i class="fas fa-user-tag"></i> Role:</div>
                        <div class="detail-value"><?php echo htmlspecialchars($currentUser->getRole()); ?></div>
                    </div>
                    
                    <div class="detail-row">
                        <div class="detail-label"><i class="fas fa-id-badge"></i> Member ID:</div>
                        <div class="detail-value">#<?php echo str_pad($currentUser->getId(), 6, '0', STR_PAD_LEFT); ?></div>
                    </div>

                    <!-- Delete Profile Section -->
                    <div style="margin-top: 40px; padding-top: 30px; border-top: 2px solid rgba(244, 67, 54, 0.3);">
                        <h3 style="color: #f44336; font-family: 'Orbitron', sans-serif; margin-bottom: 15px;">
                            <i class="fas fa-exclamation-triangle"></i> Danger Zone
                        </h3>
                        <p style="color: #888; margin-bottom: 20px;">
                            Once you delete your account, there is no going back. Please be certain.
                        </p>
                        <button class="btn-delete" id="deleteProfileBtn">
                            <i class="fas fa-trash-alt"></i> Delete My Account
                        </button>
                    </div>
                </div>
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

        </div>
        <div class="footer-bottom">
            <p>© 2025 FoxUnity. All rights reserved. Made with <span>♥</span> by gamers for gamers</p>
        </div>
    </footer>

    <!-- Delete Confirmation Modal -->
    <div class="delete-modal" id="deleteModal">
        <div class="delete-box">
            <h3><i class="fas fa-exclamation-triangle"></i> Delete Account?</h3>
            <p>This action cannot be undone. This will permanently delete your account and remove all your data from our servers.</p>
            <div class="warning-text">
                <i class="fas fa-skull-crossbones"></i> All your data will be lost forever!
            </div>
            <div class="delete-buttons">
                <button class="btn-delete-confirm" id="confirmDelete">
                    <i class="fas fa-trash-alt"></i> Yes, Delete Forever
                </button>
                <button class="btn-delete-cancel" id="cancelDelete">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>
        </div>
    </div>

    <script>
        // Dropdown Menu Toggle
        document.addEventListener('DOMContentLoaded', function() {
            const userDropdown = document.getElementById('userDropdown');
            
            if (userDropdown) {
                const usernameDisplay = userDropdown.querySelector('.username-display');
                
                // Toggle dropdown on click
                usernameDisplay.addEventListener('click', function(e) {
                    e.stopPropagation();
                    userDropdown.classList.toggle('active');
                });
                
                // Close dropdown when clicking outside
                document.addEventListener('click', function(e) {
                    if (!userDropdown.contains(e.target)) {
                        userDropdown.classList.remove('active');
                    }
                });
                
                // Close dropdown when pressing Escape
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

        // Delete Profile Modal
        const deleteBtn = document.getElementById('deleteProfileBtn');
        const deleteModal = document.getElementById('deleteModal');
        const confirmDelete = document.getElementById('confirmDelete');
        const cancelDelete = document.getElementById('cancelDelete');

        if (deleteBtn) {
            deleteBtn.addEventListener('click', function() {
                deleteModal.classList.add('show');
            });
        }

        if (cancelDelete) {
            cancelDelete.addEventListener('click', function() {
                deleteModal.classList.remove('show');
            });
        }

        if (confirmDelete) {
            confirmDelete.addEventListener('click', function() {
                // Create a form to submit the delete request
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'delete-account.php';
                document.body.appendChild(form);
                form.submit();
            });
        }

        // Close modal on outside click
        deleteModal.addEventListener('click', function(e) {
            if (e.target === deleteModal) {
                deleteModal.classList.remove('show');
            }
        });

        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && deleteModal.classList.contains('show')) {
                deleteModal.classList.remove('show');
            }
        });
    </script>
</body>
</html>