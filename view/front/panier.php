<?php
require_once __DIR__ . '/../../controller/UserController.php';

// Check if user is logged in
$isLoggedIn = UserController::isLoggedIn();
$currentUser = null;

if ($isLoggedIn) {
    $currentUser = UserController::getCurrentUser();
}

// Get user image - NO DEFAULT IMAGE, just check if exists
$userImage = null;
if ($currentUser && $currentUser->getImage()) {
    $userImage = '../../view/' . $currentUser->getImage();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FoxUnity - Your Cart</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Orbitron:wght@700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* User Dropdown Menu Styles */
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
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #ff7a00;
        }

        .username-display span {
            color: #ff7a00;
            font-weight: 600;
            font-size: 16px;
        }

        .username-display i.fa-chevron-down {
            font-size: 12px;
            color: #ff7a00;
            transition: transform 0.3s ease;
        }

        .username-display i.fa-user-circle {
            font-size: 24px;
            color: #ff7a00;
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

        /* Cart Page Specific Styles */
        .cart-section {
            padding: 120px 40px 80px;
            max-width: 1200px;
            margin: 0 auto;
            min-height: 60vh;
        }

        .cart-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 36px;
            color: #fff;
            margin-bottom: 40px;
            text-align: center;
        }

        .cart-title span {
            color: #ff7a00;
        }

        .cart-container {
            background: rgba(20, 20, 20, 0.8);
            border: 1px solid rgba(255, 122, 0, 0.2);
            border-radius: 20px;
            padding: 30px;
        }

        .empty-cart {
            text-align: center;
            padding: 60px 20px;
            color: #aaa;
        }

        .empty-cart i {
            font-size: 60px;
            color: #ff7a00;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-cart h3 {
            font-size: 24px;
            color: #fff;
            margin-bottom: 10px;
        }

        .empty-cart p {
            margin-bottom: 30px;
        }

        .shop-btn {
            background: linear-gradient(135deg, #ff7a00, #ff4f00);
            color: #fff;
            padding: 12px 30px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .shop-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 122, 0, 0.3);
        }

        .cart-items {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .cart-item {
            display: flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.05);
            padding: 20px;
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            gap: 20px;
            transition: all 0.3s ease;
        }

        .cart-item:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 122, 0, 0.3);
        }

        .cart-item img {
            width: 100px;
            height: 100px;
            object-fit: contain;
            border-radius: 10px;
            background: rgba(0, 0, 0, 0.3);
            padding: 10px;
        }

        .item-details {
            flex: 1;
        }

        .item-name {
            color: #fff;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .item-type {
            color: #aaa;
            font-size: 0.85rem;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .item-price {
            color: #2ed573;
            font-weight: 700;
            font-size: 1.3rem;
            font-family: 'Orbitron', sans-serif;
        }

        .item-quantity-controls {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .quantity-control {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255, 255, 255, 0.05);
            padding: 8px 15px;
            border-radius: 25px;
            border: 1px solid rgba(255, 122, 0, 0.3);
        }

        .quantity-btn {
            background: transparent;
            border: none;
            color: #ff7a00;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .quantity-btn:hover:not(:disabled) {
            background: rgba(255, 122, 0, 0.2);
            transform: scale(1.1);
        }

        .quantity-btn:disabled {
            opacity: 0.3;
            cursor: not-allowed;
        }

        .quantity-value {
            color: #fff;
            font-weight: 600;
            min-width: 30px;
            text-align: center;
            font-size: 16px;
        }

        .item-subtotal {
            text-align: right;
            min-width: 120px;
        }

        .subtotal-label {
            color: #aaa;
            font-size: 0.85rem;
            margin-bottom: 5px;
        }

        .subtotal-price {
            color: #2ed573;
            font-weight: 700;
            font-size: 1.4rem;
            font-family: 'Orbitron', sans-serif;
        }

        .remove-btn {
            color: #ff4444;
            background: transparent;
            border: 2px solid #ff4444;
            cursor: pointer;
            font-size: 16px;
            padding: 10px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .remove-btn:hover {
            background: rgba(255, 68, 68, 0.2);
            transform: scale(1.1);
        }

        /* ============================================ */
        /* COUPON SECTION STYLES                        */
        /* ============================================ */
        .coupon-section {
            background: linear-gradient(135deg, rgba(20, 20, 35, 0.95) 0%, rgba(10, 10, 20, 0.9) 100%);
            border: 2px solid rgba(255, 122, 0, 0.2);
            border-radius: 16px;
            padding: 25px;
            margin: 25px 0;
            transition: all 0.3s ease;
        }

        .coupon-section:hover {
            border-color: rgba(255, 122, 0, 0.4);
            box-shadow: 0 8px 25px rgba(255, 122, 0, 0.15);
        }

        .coupon-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 18px;
            color: #ff7a00;
            font-weight: 600;
            font-size: 16px;
        }

        .coupon-header i {
            font-size: 20px;
        }

        .coupon-input-group {
            display: flex;
            gap: 12px;
            margin-bottom: 15px;
        }

        .coupon-input {
            flex: 1;
            padding: 14px 18px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            color: #fff;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            outline: none;
            transition: all 0.3s ease;
        }

        .coupon-input:focus {
            border-color: #ff7a00;
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 0 15px rgba(255, 122, 0, 0.2);
        }

        .coupon-input:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .coupon-btn {
            padding: 14px 28px;
            background: linear-gradient(135deg, #ff7a00, #ff4f00);
            border: none;
            border-radius: 10px;
            color: white;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
            box-shadow: 0 4px 15px rgba(255, 122, 0, 0.3);
        }

        .coupon-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 122, 0, 0.4);
        }

        .coupon-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .coupon-btn-remove {
            background: transparent;
            border: 2px solid #ff4444;
            color: #ff4444;
            box-shadow: none;
        }

        .coupon-btn-remove:hover:not(:disabled) {
            background: rgba(255, 68, 68, 0.1);
            border-color: #ff6666;
            color: #ff6666;
        }

        .coupon-message {
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            display: none;
            align-items: center;
            gap: 10px;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .coupon-success {
            background: rgba(46, 213, 115, 0.15);
            border: 1px solid rgba(46, 213, 115, 0.4);
            color: #2ed573;
        }

        .coupon-success i {
            font-size: 18px;
        }

        .coupon-error {
            background: rgba(255, 68, 68, 0.15);
            border: 1px solid rgba(255, 68, 68, 0.4);
            color: #ff4444;
        }

        .coupon-error i {
            font-size: 18px;
        }

        .coupon-spinner {
            display: none;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin-left: 8px;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .checkout-btn {
            background: linear-gradient(135deg, #2ed573, #26af61);
            color: #fff;
            padding: 18px 50px;
            border-radius: 30px;
            border: none;
            font-weight: 700;
            font-size: 18px;
            cursor: pointer;
            width: 100%;
            margin-top: 20px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 5px 20px rgba(46, 213, 115, 0.3);
        }

        .checkout-btn:hover {
            background: linear-gradient(135deg, #26af61, #2ed573);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(46, 213, 115, 0.4);
        }

        .cart-summary {
            margin-top: 30px;
            padding-top: 25px;
            border-top: 2px solid rgba(255, 122, 0, 0.3);
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            color: #aaa;
            font-size: 16px;
        }

        .summary-row.discount-row {
            color: #2ed573;
            display: none;
        }

        .summary-row.discount-row.show {
            display: flex;
        }

        .summary-row.total {
            font-size: 28px;
            color: #fff;
            font-weight: 700;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .summary-row.total .summary-value {
            color: #2ed573;
            font-family: 'Orbitron', sans-serif;
        }

        /* Toast Notification */
        .toast {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(20, 20, 20, 0.95);
            color: #fff;
            padding: 15px 30px;
            border-radius: 50px;
            border: 1px solid #ff7a00;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.5);
            z-index: 3000;
            display: flex;
            align-items: center;
            gap: 15px;
            visibility: hidden;
            opacity: 0;
            transition: all 0.5s ease;
        }

        .toast.show {
            visibility: visible;
            opacity: 1;
            top: 30px;
        }

        .toast i {
            font-size: 1.2em;
        }

        .toast.success i {
            color: #2ed573;
        }

        .toast.error i {
            color: #ff4757;
        }

        @media (max-width: 768px) {
            .cart-item {
                flex-direction: column;
                text-align: center;
            }

            .item-quantity-controls {
                flex-direction: column;
                width: 100%;
            }

            .quantity-control {
                width: 100%;
                justify-content: center;
            }

            .item-subtotal {
                text-align: center;
            }

            .coupon-input-group {
                flex-direction: column;
            }

            .coupon-btn {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <!-- Animated red bubbles -->
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

    <!-- Toast Notification Element -->
    <div id="toast" class="toast">
        <i class="fas fa-check-circle"></i>
        <span id="toastMessage">Action successful</span>
    </div>

    <!-- HEADER -->
    <header class="site-header">
        <div class="logo-section">
            <img src="../images/Nine__1_-removebg-preview.png" alt="FoxUnity Logo" class="site-logo">
            <span class="site-name">FoxUnity</span>
        </div>

        <nav class="site-nav">
            <a href="index.php">Home</a>
            <a href="events.php">Events</a>
            <a href="shop.php">Shop</a>
            <a href="trading.php">Trading</a>
            <a href="news.php">News</a>
            <a href="reclamation.php">Support</a>
            <a href="contact_us.php">New Request</a>
            <a href="public_reclamations.php"><i class="fas fa-star"></i> Public Evaluations</a>
            <a href="about.php">About Us</a>
        </nav>

        <div class="header-right">
            <div class="user-dropdown" id="userDropdown">
                <div class="username-display">
                    <?php if ($isLoggedIn && $currentUser): ?>
                        <?php if ($userImage): ?>
                            <img src="<?php echo htmlspecialchars($userImage); ?>" alt="Profile">
                        <?php else: ?>
                            <i class="fas fa-user-circle"></i>
                        <?php endif; ?>
                        <span><?php echo htmlspecialchars($currentUser->getUsername()); ?></span>
                    <?php else: ?>
                        <i class="fas fa-user-circle"></i>
                        <span>Guest</span>
                    <?php endif; ?>
                    <i class="fas fa-chevron-down"></i>
                </div>

                <div class="dropdown-menu">
                    <?php if ($isLoggedIn && $currentUser): ?>
                        <a href="profile.php" class="dropdown-item">
                            <i class="fas fa-user"></i>
                            <span>My Profile</span>
                        </a>

                        <a href="tradehis.php" class="dropdown-item">
                            <i class="fas fa-history"></i>
                            <span>Trade History</span>
                        </a>

                        <a href="events.php?view=history" class="dropdown-item">
                            <i class="fas fa-ticket-alt"></i>
                            <span>Event History</span>
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
                    <?php else: ?>
                        <a href="login.php" class="dropdown-item">
                            <i class="fas fa-sign-in-alt"></i>
                            <span>Login/Register</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <a href="panier.php" class="cart-icon active">
                <i class="fas fa-shopping-cart"></i> Cart
                <span class="cart-count">0</span>
            </a>
        </div>
    </header>

    <main class="main-section">
        <section class="cart-section">
            <h1 class="cart-title">Your <span>Cart</span></h1>

            <div class="cart-container">
                <div id="cartContent" class="cart-items">
                    <!-- Cart items will be injected here via JS -->
                </div>

                <!-- ============================================ -->
                <!-- COUPON SECTION                               -->
                <!-- ============================================ -->
                <div id="couponSectionContainer" style="display: none;">
                    <div class="coupon-section">
                        <div class="coupon-header">
                            <i class="fas fa-tag"></i>
                            <span>Have a coupon code?</span>
                        </div>

                        <div class="coupon-input-group">
                            <input type="text" id="couponCode" class="coupon-input"
                                placeholder="Enter coupon code (e.g., WELCOME10)" maxlength="50">
                            <button id="applyCouponBtn" class="coupon-btn" onclick="applyCoupon()">
                                <i class="fas fa-check"></i> Apply
                                <span id="couponSpinner" class="coupon-spinner"></span>
                            </button>
                            <button id="removeCouponBtn" class="coupon-btn coupon-btn-remove" onclick="removeCoupon()"
                                style="display: none;">
                                <i class="fas fa-times"></i> Remove
                            </button>
                        </div>

                        <div id="couponSuccess" class="coupon-message coupon-success">
                            <i class="fas fa-check-circle"></i>
                            <span>Coupon applied successfully!</span>
                        </div>

                        <div id="couponError" class="coupon-message coupon-error">
                            <i class="fas fa-exclamation-circle"></i>
                            <span>Invalid coupon code</span>
                        </div>
                    </div>
                </div>

                <div id="cartSummary" class="cart-summary" style="display: none;">
                    <div class="summary-row">
                        <span>Items:</span>
                        <span class="summary-value" id="totalItems">0</span>
                    </div>
                    <div class="summary-row">
                        <span>Subtotal:</span>
                        <span class="summary-value" id="subtotalPrice">$0.00</span>
                    </div>
                    <div class="summary-row total">
                        <span>Total:</span>
                        <span class="summary-value" id="cartTotal">$0.00</span>
                    </div>
                    <button id="checkoutBtn" class="checkout-btn">
                        <i class="fas fa-check-circle"></i> Proceed to Checkout
                    </button>
                </div>

                <div id="emptyCartMessage" class="empty-cart" style="display: none;">
                    <i class="fas fa-shopping-cart"></i>
                    <h3>Your cart is empty</h3>
                    <p>Looks like you haven't added anything yet.</p>
                    <a href="shop.php" class="shop-btn">Browse Products</a>
                </div>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <div class="footer-content">
            <div class="footer-section">
                <h4>FoxUnity</h4>
                <p>Gaming for Good - Every action makes a difference</p>
            </div>
            <div class="footer-section">
                <h4>Back to Top</h4>
                <a href="#" class="back-to-top-link"
                    onclick="window.scrollTo({top: 0, behavior: 'smooth'}); return false;">
                    <i class="fas fa-arrow-up"></i> Scroll to Top
                </a>
            </div>
            <div class="footer-section">
                <h4>Support</h4>
                <a href="reclamation.php">Contact Support</a>
                <a href="#">FAQ</a>
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

    <script>
        // ============================================
        // GLOBAL VARIABLES
        // ============================================
        let cart = JSON.parse(localStorage.getItem('cart')) || [];
        let appliedCoupon = null;

        // ============================================
        // INITIALIZATION
        // ============================================
        document.addEventListener('DOMContentLoaded', function () {
            const userDropdown = document.getElementById('userDropdown');

            if (userDropdown) {
                const usernameDisplay = userDropdown.querySelector('.username-display');

                usernameDisplay.addEventListener('click', function (e) {
                    e.stopPropagation();
                    userDropdown.classList.toggle('active');
                });

                document.addEventListener('click', function (e) {
                    if (!userDropdown.contains(e.target)) {
                        userDropdown.classList.remove('active');
                    }
                });

                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape') {
                        userDropdown.classList.remove('active');
                    }
                });
            }

            // Coupon enter key support
            const couponInput = document.getElementById('couponCode');
            if (couponInput) {
                couponInput.addEventListener('keypress', function (e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        applyCoupon();
                    }
                });
            }

            // Initialize cart
            updateCartCount();
            renderCart();
        });

        // ============================================
        // CART FUNCTIONS
        // ============================================
        function updateCartCount() {
            let totalQuantity = 0;
            cart.forEach(item => {
                totalQuantity += item.quantity || 1;
            });

            const cartCount = document.querySelector('.cart-count');
            if (cartCount) {
                cartCount.textContent = totalQuantity;
            }
        }

        async function renderCart() {
            const cartContent = document.getElementById('cartContent');
            const emptyCartMessage = document.getElementById('emptyCartMessage');
            const cartSummary = document.getElementById('cartSummary');
            const couponSection = document.getElementById('couponSectionContainer');

            if (cart.length === 0) {
                cartContent.style.display = 'none';
                cartSummary.style.display = 'none';
                couponSection.style.display = 'none';
                emptyCartMessage.style.display = 'block';
                return;
            }

            const cartItems = cart.map(item => ({
                id: item.id,
                type: item.type || 'skin',
                quantity: item.quantity || 1
            }));

            try {
                const formData = new FormData();
                formData.append('cart_items', JSON.stringify(cartItems));

                const response = await fetch('get_cart_prices.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (!data.success) {
                    console.error('Failed to fetch prices:', data.error);
                    showToast('Error loading cart items', 'error');
                    return;
                }

                const prices = data.prices;

                cartContent.style.display = 'flex';
                cartSummary.style.display = 'block';
                couponSection.style.display = 'block';
                emptyCartMessage.style.display = 'none';
                cartContent.innerHTML = '';

                let total = 0;
                let totalItems = 0;

                cart.forEach((item, index) => {
                    const type = item.type || 'skin';
                    const quantity = item.quantity || 1;
                    let key = item.id;
                    if (type === 'product') {
                        key = item.id + '_product';
                    }

                    const priceData = prices[key];
                    if (!priceData) return;

                    const unitPrice = parseFloat(priceData.price);
                    const subtotal = unitPrice * quantity;
                    total += subtotal;
                    totalItems += quantity;

                    const itemEl = document.createElement('div');
                    itemEl.className = 'cart-item';
                    itemEl.innerHTML = `
                        <img src="${priceData.image}" alt="${priceData.name}" onerror="this.src='https://via.placeholder.com/100x100?text=No+Image'">
                        <div class="item-details">
                            <div class="item-name">${priceData.name}</div>
                            <div class="item-type">${type}</div>
                            <div class="item-price">$${unitPrice.toFixed(2)} each</div>
                        </div>
                        <div class="item-quantity-controls">
                            <div class="quantity-control">
                                <button class="quantity-btn" onclick="decreaseQuantity(${index})" ${quantity <= 1 ? 'disabled' : ''}>
                                    <i class="fas fa-minus"></i>
                                </button>
                                <span class="quantity-value">${quantity}</span>
                                <button class="quantity-btn" onclick="increaseQuantity(${index})">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="item-subtotal">
                            <div class="subtotal-label">Subtotal</div>
                            <div class="subtotal-price">$${subtotal.toFixed(2)}</div>
                        </div>
                        <button class="remove-btn" onclick="removeFromCart(${index})" title="Remove item">
                            <i class="fas fa-trash"></i>
                        </button>
                    `;
                    cartContent.appendChild(itemEl);
                });

                document.getElementById('totalItems').textContent = totalItems;
                document.getElementById('subtotalPrice').textContent = '$' + total.toFixed(2);
                document.getElementById('cartTotal').textContent = '$' + total.toFixed(2);

                // Update with coupon if applied
                updateCartDisplayWithCoupon();

            } catch (error) {
                console.error('Error fetching prices:', error);
                showToast('Error loading cart', 'error');
            }
        }

        function increaseQuantity(index) {
            cart[index].quantity = (cart[index].quantity || 1) + 1;
            localStorage.setItem('cart', JSON.stringify(cart));
            appliedCoupon = null; // Reset coupon on cart change
            updateCartCount();
            renderCart();
        }

        function decreaseQuantity(index) {
            if ((cart[index].quantity || 1) > 1) {
                cart[index].quantity--;
                localStorage.setItem('cart', JSON.stringify(cart));
                appliedCoupon = null; // Reset coupon on cart change
                updateCartCount();
                renderCart();
            }
        }

        function removeFromCart(index) {
            cart.splice(index, 1);
            localStorage.setItem('cart', JSON.stringify(cart));
            appliedCoupon = null; // Reset coupon on cart change
            updateCartCount();
            renderCart();
            showToast('Item removed from cart', 'success');
        }

        // ============================================
        // COUPON MANAGEMENT SYSTEM
        // ============================================
        async function applyCoupon() {
            const couponInput = document.getElementById('couponCode');
            const couponCode = couponInput.value.trim().toUpperCase();

            if (!couponCode) {
                showCouponError('Please enter a coupon code');
                return;
            }

            const cartTotal = getCartTotalForCoupon();

            if (cartTotal <= 0) {
                showCouponError('Your cart is empty');
                return;
            }

            showCouponLoading(true);

            try {
                const response = await fetch('api/validate_coupon.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        coupon_code: couponCode,
                        total_amount: cartTotal
                    })
                });

                const data = await response.json();

                if (data.success) {
                    appliedCoupon = {
                        id: data.coupon_id,
                        code: data.code,
                        discount_amount: data.discount_amount,
                        original_amount: data.original_amount,
                        final_amount: data.final_amount
                    };

                    showCouponSuccess(data.message);
                    updateCartDisplayWithCoupon();

                    couponInput.disabled = true;
                    document.getElementById('applyCouponBtn').style.display = 'none';
                    document.getElementById('removeCouponBtn').style.display = 'inline-block';

                } else {
                    showCouponError(data.error);
                }

            } catch (error) {
                console.error('Coupon validation error:', error);
                showCouponError('Error validating coupon. Please try again.');
            } finally {
                showCouponLoading(false);
            }
        }

        function removeCoupon() {
            appliedCoupon = null;

            document.getElementById('couponCode').value = '';
            document.getElementById('couponCode').disabled = false;
            document.getElementById('applyCouponBtn').style.display = 'inline-block';
            document.getElementById('removeCouponBtn').style.display = 'none';

            document.getElementById('couponSuccess').style.display = 'none';
            document.getElementById('couponError').style.display = 'none';

            updateCartDisplayWithCoupon();
        }

        function updateCartDisplayWithCoupon() {
            if (!appliedCoupon) {
                const discountRow = document.querySelector('.summary-row.discount-row');
                if (discountRow) {
                    discountRow.classList.remove('show');
                }

                const totalEl = document.getElementById('cartTotal');
                const subtotalEl = document.getElementById('subtotalPrice');
                if (totalEl && subtotalEl) {
                    totalEl.textContent = subtotalEl.textContent;
                }
            } else {
                let discountRow = document.querySelector('.summary-row.discount-row');

                if (!discountRow) {
                    const summaryDiv = document.querySelector('.cart-summary');
                    const totalRow = summaryDiv.querySelector('.summary-row.total');

                    discountRow = document.createElement('div');
                    discountRow.className = 'summary-row discount-row';
                    summaryDiv.insertBefore(discountRow, totalRow);
                }

                discountRow.innerHTML = `
                    <span>Discount (${appliedCoupon.code}):</span>
                    <span class="summary-value" style="color: #2ed573; font-weight: 700;">-$${Number(appliedCoupon.discount_amount).toFixed(2)}</span>
                `;
                discountRow.classList.add('show');

                const totalEl = document.getElementById('cartTotal');
                if (totalEl) {
                    totalEl.textContent = '$' + Number(appliedCoupon.final_amount).toFixed(2);
                }
            }
        }

        function getCartTotalForCoupon() {
            const totalEl = document.getElementById('subtotalPrice');
            if (totalEl) {
                const totalText = totalEl.textContent.replace('$', '').replace(',', '');
                return parseFloat(totalText) || 0;
            }
            return 0;
        }

        function showCouponSuccess(message) {
            const successEl = document.getElementById('couponSuccess');
            const errorEl = document.getElementById('couponError');

            errorEl.style.display = 'none';
            successEl.querySelector('span').textContent = message;
            successEl.style.display = 'flex';
        }

        function showCouponError(message) {
            const successEl = document.getElementById('couponSuccess');
            const errorEl = document.getElementById('couponError');

            successEl.style.display = 'none';
            errorEl.querySelector('span').textContent = message;
            errorEl.style.display = 'flex';
        }

        function showCouponLoading(loading) {
            const btn = document.getElementById('applyCouponBtn');
            const spinner = document.getElementById('couponSpinner');

            if (loading) {
                btn.disabled = true;
                spinner.style.display = 'inline-block';
            } else {
                btn.disabled = false;
                spinner.style.display = 'none';
            }
        }

        function getAppliedCouponData() {
            return appliedCoupon;
        }

        // ============================================
        // CHECKOUT WITH COUPON SUPPORT
        // ============================================
        const checkoutBtn = document.getElementById('checkoutBtn');
        if (checkoutBtn) {
            checkoutBtn.addEventListener('click', function () {
                if (cart.length === 0) return;

                // Get coupon data if applied
                const couponData = getAppliedCouponData();

                const productsData = cart.filter(item => item.type === 'product').map(item => ({
                    id: item.id,
                    quantity: item.quantity || 1
                }));

                const skinsData = cart.filter(item => !item.type || item.type === 'skin').map(item => ({
                    id: item.id,
                    quantity: item.quantity || 1
                }));

                const formData = new FormData();
                formData.append('buy_skins', '1');
                formData.append('skin_ids', JSON.stringify(skinsData.map(s => s.id)));
                formData.append('product_ids', JSON.stringify(productsData.map(p => p.id)));
                formData.append('quantities', JSON.stringify({
                    products: productsData,
                    skins: skinsData
                }));

                // ADD COUPON DATA
                if (couponData) {
                    formData.append('coupon_id', couponData.id);
                    formData.append('coupon_code', couponData.code);
                    formData.append('discount_amount', couponData.discount_amount);
                    formData.append('final_amount', couponData.final_amount);
                }

                fetch('trading.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showToast('Purchase successful! Redirecting...', 'success');
                            localStorage.removeItem('cart');
                            setTimeout(() => {
                                window.location.href = 'trading.php';
                            }, 2000);
                        } else {
                            showToast('Purchase failed: ' + (data.error || 'Unknown error'), 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showToast('An error occurred during checkout.', 'error');
                    });
            });
        }

        // ============================================
        // TOAST NOTIFICATION
        // ============================================
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            if (!toast) return;

            const msgSpan = document.getElementById('toastMessage');
            const icon = toast.querySelector('i');

            msgSpan.textContent = message;

            toast.className = 'toast';
            if (type === 'success') {
                toast.classList.add('success');
                icon.className = 'fas fa-check-circle';
            } else {
                toast.classList.add('error');
                icon.className = 'fas fa-times-circle';
            }

            void toast.offsetWidth;
            toast.classList.add('show');

            setTimeout(() => {
                toast.classList.remove('show');
            }, 3000);
        }
    </script>
</body>

</html>