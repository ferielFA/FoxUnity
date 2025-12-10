<?php
require_once __DIR__ . '/../../controller/UserController.php';
require_once __DIR__ . '/../../model/SkinModel.php';

// Check if user is logged in
$isLoggedIn = UserController::isLoggedIn();
$currentUser = null;

if ($isLoggedIn) {
    $currentUser = UserController::getCurrentUser();
}

// Get user image
$userImage = null;
if ($currentUser && $currentUser->getImage()) {
    $userImage = '../../view/' . $currentUser->getImage();
}

// Get skin ID
$skinId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$skin = null;

if ($skinId > 0) {
    $skinModel = new SkinModel();
    $skin = $skinModel->getSkinById($skinId);
}

if (!$skin) {
    header('Location: trading.php');
    exit;
}

$img = !empty($skin['image']) ? '../' . ltrim($skin['image'], '/\\') : '../images/skin1.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FoxUnity - Skin Description</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Orbitron:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .description-section {
            padding: 120px 40px 80px;
            max-width: 1000px;
            margin: 0 auto;
            min-height: 60vh;
        }
        
        .skin-detail-card {
            background: rgba(20, 20, 20, 0.8);
            border: 1px solid rgba(255, 122, 0, 0.2);
            border-radius: 20px;
            padding: 40px;
            display: flex;
            gap: 40px;
            align-items: flex-start;
        }
        
        .skin-detail-img {
            width: 400px;
            height: 400px;
            object-fit: cover;
            border-radius: 15px;
            border: 2px solid rgba(255, 122, 0, 0.3);
        }
        
        .skin-info {
            flex: 1;
        }
        
        .skin-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 36px;
            color: #fff;
            margin-bottom: 10px;
        }
        
        .skin-category {
            color: #ff7a00;
            font-size: 18px;
            margin-bottom: 20px;
            text-transform: uppercase;
            font-weight: 600;
        }
        
        .skin-price {
            font-size: 32px;
            color: #2ed573;
            font-weight: 700;
            margin-bottom: 30px;
        }
        
        .description-content {
            background: rgba(255, 255, 255, 0.05);
            padding: 20px;
            border-radius: 10px;
            color: #ccc;
            line-height: 1.6;
            margin-bottom: 30px;
            border-left: 4px solid #ff7a00;
        }
        
        .back-btn {
            display: inline-block;
            padding: 12px 25px;
            background: transparent;
            border: 2px solid #ff7a00;
            color: #ff7a00;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .back-btn:hover {
            background: #ff7a00;
            color: #fff;
        }

        /* User Dropdown Menu Styles (Copied from other pages) */
        .user-dropdown { position: relative; display: inline-block; }
        .username-display { cursor: pointer; display: flex; align-items: center; gap: 10px; transition: all 0.3s ease; padding: 5px 10px; border-radius: 8px; }
        .username-display:hover { background: rgba(255, 122, 0, 0.1); }
        .username-display img { width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 2px solid #ff7a00; }
        .username-display span { color: #ff7a00; font-weight: 600; font-size: 16px; }
        .username-display i.fa-chevron-down { font-size: 12px; color: #ff7a00; transition: transform 0.3s ease; }
        .username-display i.fa-user-circle { font-size: 24px; color: #ff7a00; }
        .user-dropdown.active .username-display i.fa-chevron-down { transform: rotate(180deg); }
        .dropdown-menu { position: absolute; top: 100%; right: 0; margin-top: 10px; background: rgba(20, 20, 20, 0.98); border: 2px solid rgba(255, 122, 0, 0.3); border-radius: 12px; min-width: 200px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5); opacity: 0; visibility: hidden; transform: translateY(-10px); transition: all 0.3s ease; z-index: 1000; overflow: hidden; }
        .user-dropdown.active .dropdown-menu { opacity: 1; visibility: visible; transform: translateY(0); }
        .dropdown-item { padding: 12px 15px; color: #fff; text-decoration: none; display: flex; align-items: center; gap: 10px; transition: all 0.3s ease; border-left: 3px solid transparent; }
        .dropdown-item:hover { background: rgba(255, 122, 0, 0.1); border-left-color: #ff7a00; }
        .dropdown-item i { font-size: 16px; color: #ff7a00; width: 20px; }
        .dropdown-divider { height: 1px; background: rgba(255, 122, 0, 0.2); margin: 5px 0; }
        .dropdown-item.logout { color: #ff4444; }
        .dropdown-item.logout i { color: #ff4444; }
        .dropdown-item.logout:hover { background: rgba(255, 68, 68, 0.1); border-left-color: #ff4444; }
        
        .cart-icon { color: #ff7a00 !important; position: relative; font-weight: 600; transition: all 0.3s ease; }
        .cart-icon:hover { color: #ff9933 !important; transform: translateY(-2px); }
        .cart-icon i { color: #ff7a00; font-size: 18px; }
        .cart-count { background: linear-gradient(135deg, #ff7a00, #ff4f00); color: white; border-radius: 50%; padding: 2px 6px; font-size: 11px; font-weight: 700; position: absolute; top: -8px; right: -8px; min-width: 18px; text-align: center; box-shadow: 0 2px 8px rgba(255, 122, 0, 0.4); }

        @media (max-width: 768px) {
            .skin-detail-card { flex-direction: column; align-items: center; }
            .skin-detail-img { width: 100%; height: 300px; }
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
            <a href="events.php">Events</a>
            <a href="shop.html">Shop</a>
            <a href="trading.php">Trading</a>
            <a href="news.html">News</a>
            <a href="reclamation.html">Support</a>
            <a href="about.html">About Us</a>
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
            
            <a href="panier.php" class="cart-icon">
                <i class="fas fa-shopping-cart"></i> Cart
                <span class="cart-count">0</span>
            </a>
        </div>
    </header>

    <main class="main-section">
        <section class="description-section">
            <div class="skin-detail-card">
                <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($skin['name']) ?>" class="skin-detail-img" onerror="this.src='../images/skin1.png'">
                
                <div class="skin-info">
                    <h1 class="skin-title"><?= htmlspecialchars($skin['name']) ?></h1>
                    <div class="skin-category"><?= htmlspecialchars($skin['category']) ?></div>
                    <div class="skin-price">$<?= number_format((float)$skin['price'], 2) ?></div>
                    
                    <h3>Description</h3>
                    <div class="description-content">
                        <?= nl2br(htmlspecialchars($skin['description'])) ?>
                    </div>
                    
                    <a href="trading.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Trading</a>
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
            if (cartCount) {
                cartCount.textContent = cart.length;
            }
        });
    </script>
</body>
</html>
