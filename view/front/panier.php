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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Orbitron:wght@700&display=swap" rel="stylesheet">
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
            padding: 15px;
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .cart-item img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            margin-right: 20px;
        }

        .item-details {
            flex: 1;
        }

        .item-name {
            color: #fff;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .item-price {
            color: #ff7a00;
            font-weight: 700;
        }

        .remove-btn {
            color: #ff4444;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 18px;
            padding: 10px;
            transition: color 0.3s;
        }

        .remove-btn:hover {
            color: #ff0000;
        }

        .checkout-btn {
            background: #2ed573;
            color: #fff;
            padding: 15px 40px;
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
        }

        .checkout-btn:hover {
            background: #26af61;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(46, 213, 115, 0.3);
        }

        .cart-summary {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            text-align: right;
        }

        .total-price {
            font-size: 24px;
            color: #fff;
            font-weight: 700;
        }

        .total-price span {
            color: #ff7a00;
        }
    </style>
</head>
<body>
    <!-- Animated red bubbles -->
    <div class="bubbles">
        <div class="bubble"></div><div class="bubble"></div><div class="bubble"></div><div class="bubble"></div>
        <div class="bubble"></div><div class="bubble"></div><div class="bubble"></div><div class="bubble"></div>
    </div>

    <!-- HEADER -->
    <header class="site-header">
        <div class="logo-section">
            <img src="../images/Nine__1_-removebg-preview.png" alt="FoxUnity Logo" class="site-logo">
            <span class="site-name">FoxUnity</span>
        </div>
        
        <nav class="site-nav">
            <a href="index.php">Home</a>
            <a href="events.html">Events</a>
            <a href="shop.html">Shop</a>
            <a href="trading.php">Trading</a>
            <a href="news.html">News</a>
            <a href="reclamation.html">Support</a>
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
                
                <div id="cartSummary" class="cart-summary" style="display: none;">
                    <div class="total-price">Total: <span id="cartTotal">$0.00</span></div>
                    <button id="checkoutBtn" class="checkout-btn">
                        <i class="fas fa-check-circle"></i> Checkout
                    </button>
                </div>

                <div id="emptyCartMessage" class="empty-cart" style="display: none;">
                    <i class="fas fa-shopping-cart"></i>
                    <h3>Your cart is empty</h3>
                    <p>Looks like you haven't added anything yet.</p>
                    <a href="trading.php" class="shop-btn">Start Trading</a>
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
                <a href="#" class="back-to-top-link" onclick="window.scrollTo({top: 0, behavior: 'smooth'}); return false;">
                    <i class="fas fa-arrow-up"></i> Scroll to Top
                </a>
            </div>
            <div class="footer-section">
                <h4>Support</h4>
                <a href="reclamation.html">Contact Support</a>
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
        // Dropdown Menu Toggle
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
            
            // Cart Logic
            const cart = JSON.parse(localStorage.getItem('cart')) || [];
            const cartCount = document.querySelector('.cart-count');
            const cartContent = document.getElementById('cartContent');
            const emptyCartMessage = document.getElementById('emptyCartMessage');
            const cartSummary = document.getElementById('cartSummary');
            const cartTotal = document.getElementById('cartTotal');
            const checkoutBtn = document.getElementById('checkoutBtn');
            
            if (cartCount) {
                cartCount.textContent = cart.length;
            }

            async function renderCart() {
                if (cart.length === 0) {
                    cartContent.style.display = 'none';
                    cartSummary.style.display = 'none';
                    emptyCartMessage.style.display = 'block';
                    return;
                }
                
                // Fetch exact prices from database
                const skinIds = cart.map(item => item.id);
                
                try {
                    const formData = new FormData();
                    formData.append('skin_ids', JSON.stringify(skinIds));
                    
                    const response = await fetch('get_cart_prices.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (!data.success) {
                        console.error('Failed to fetch prices:', data.error);
                        alert('Failed to load cart prices. Please refresh the page.');
                        return;
                    }
                    
                    const prices = data.prices;
                    
                    cartContent.style.display = 'flex';
                    cartSummary.style.display = 'block';
                    emptyCartMessage.style.display = 'none';
                    cartContent.innerHTML = '';
                    
                    let total = 0;
                    
                    cart.forEach((item, index) => {
                        const priceData = prices[item.id];
                        if (!priceData) {
                            // Item no longer exists, skip it
                            return;
                        }
                        
                        const price = priceData.price;
                        total += price;
                        
                        const itemEl = document.createElement('div');
                        itemEl.className = 'cart-item';
                        itemEl.innerHTML = `
                            <img src="${priceData.image}" alt="${priceData.name}">
                            <div class="item-details">
                                <div class="item-name">${priceData.name}</div>
                                <div class="item-price">$${price.toFixed(2)}</div>
                            </div>
                            <button class="remove-btn" onclick="removeFromCart(${index})">
                                <i class="fas fa-trash"></i>
                            </button>
                        `;
                        cartContent.appendChild(itemEl);
                    });
                    
                    cartTotal.textContent = '$' + total.toFixed(2);
                } catch (error) {
                    console.error('Error fetching prices:', error);
                    alert('Failed to load cart prices. Please refresh the page.');
                }
            }
            
            window.removeFromCart = function(index) {
                cart.splice(index, 1);
                localStorage.setItem('cart', JSON.stringify(cart));
                if (cartCount) cartCount.textContent = cart.length;
                renderCart();
            };

            if (checkoutBtn) {
                checkoutBtn.addEventListener('click', function() {
                    if (cart.length === 0) return;
                    
                    const skinIds = cart.map(item => item.id);
                    
                    const formData = new FormData();
                    formData.append('buy_skins', '1');
                    formData.append('skin_ids', JSON.stringify(skinIds));
                    
                    fetch('trading.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Purchase successful! You bought ' + data.count + ' items.');
                            localStorage.removeItem('cart');
                            window.location.href = 'trading.php';
                        } else {
                            alert('Purchase failed: ' + (data.error || 'Unknown error'));
                            if (data.details) console.error(data.details);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred during checkout.');
                    });
                });
            }

            renderCart();
        });
    </script>
</body>
</html>
