<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FoxUnity - Gaming for Good</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Orbitron:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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

    <!-- HEADER -->
    <header class="site-header">
        <div class="logo-section">
            <img src="../images/Nine__1_-removebg-preview.png" alt="FoxUnity Logo" class="site-logo">
            <span class="site-name">FoxUnity</span>
        </div>
        
        <nav class="site-nav">
            <a href="indexf.php" class="active">Home</a>
            <a href="events.html">Events</a>
            <a href="shop.php">Shop</a>
            <a href="trading.html">Trading</a>
            <a href="news.html">News</a>
            <a href="reclamation.html">Support</a>
            <a href="about.html">About Us</a>
        </nav>
        
        <div class="header-right">
            <a href="login.html" class="login-register-link">
                <i class="fas fa-user"></i> Login / Register
            </a>
            <a href="profile.html" class="profile-icon">
                <i class="fas fa-user-circle"></i>
            </a>
            <a href="panier.php" class="cart-icon">
                <i class="fas fa-shopping-cart"></i> Cart
                <span class="cart-count">0</span>
            </a>
        </div>
    </header>

    <main class="main-section">
        <!-- Hero Introduction Section -->
        <section class="hero-intro">
            <div class="intro-content">
                <div class="intro-badge">
                    <i class="fas fa-heart"></i> Gaming for Good
                </div>
                <h1 class="main-title">
                    Unite. <span>Buy.</span> Give Back.
                </h1>
                <p class="intro-description">
                    Welcome to FoxUnity, where gaming meets charity. Buy gaming gear in our shop, 
                    trade Skins at negotiable prices, and participate in community events. 
                    <strong>10% of every purchase and trade</strong> goes directly to verified charitable organizations, 
                    helping communities worldwide. Together, we're proving that gaming can change the world.
                </p>
            </div>
        </section>

        <!-- How It Works -->
        <section class="how-it-works">
            <h2 class="section-title">How It <span>Works</span></h2>
            <div class="steps-container">
                <div class="step-card">
                    <div class="step-number">1</div>
                    <div class="step-icon"><i class="fas fa-user-plus"></i></div>
                    <h3>Create Account</h3>
                    <p>Join our community of gamers making a difference</p>
                </div>
                <div class="step-card">
                    <div class="step-number">2</div>
                    <div class="step-icon"><i class="fas fa-shopping-bag"></i></div>
                    <h3>Shop, Trade & Play</h3>
                    <p>Buy gear, trade assets, join events</p>
                </div>
                <div class="step-card">
                    <div class="step-number">3</div>
                    <div class="step-icon"><i class="fas fa-hand-holding-heart"></i></div>
                    <h3>Automatic Donation</h3>
                    <p>10% of every action supports charities</p>
                </div>
            </div>
        </section>

        <!-- Feature Sections: Shop / Trading / Events -->
        <section class="feature-intro">
            <div class="feature-content">
                <div class="feature-icon-large">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="feature-text">
                    <h2>Shop <span>Marketplace</span></h2>
                    <p class="feature-description">
                        Browse high-quality gaming gear and equipment. Every purchase contributes 10% to charitable causes.
                    </p>
                    <ul class="feature-list">
                        <li><i class="fas fa-check"></i> Gaming Gear</li>
                        <li><i class="fas fa-check"></i> Premium Peripherals</li>
                        <li><i class="fas fa-check"></i> Accessories & More</li>
                        <li><i class="fas fa-check"></i> 10% Goes to Charity</li>
                    </ul>
                    <a href="shop.php" class="feature-btn">
                        <i class="fas fa-store"></i> Explore Shop
                    </a>
                </div>
            </div>
        </section>

        <section class="feature-intro alternate">
            <div class="feature-content">
                <div class="feature-text">
                    <h2>Trading <span>Hub</span></h2>
                    <p class="feature-description">
                        Trade Skins at negotiable prices. Every trade contributes 10% to charity.
                    </p>
                    <ul class="feature-list">
                        <li><i class="fas fa-check"></i> Skins</li>
                        <li><i class="fas fa-check"></i> Negotiable Prices</li>
                        <li><i class="fas fa-check"></i> Secure Trading System</li>
                        <li><i class="fas fa-check"></i> 10% Goes to Charity</li>
                    </ul>
                    <a href="trading.html" class="feature-btn">
                        <i class="fas fa-exchange-alt"></i> Start Trading
                    </a>
                </div>
                <div class="feature-icon-large">
                    <i class="fas fa-exchange-alt"></i>
                </div>
            </div>
        </section>

        <!-- Events & Impact Section omitted for brevity -->
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
            <div class="footer-section">
                <h4>Dashboard</h4>
                <a href="../back/dashboard.php" class="dashboard-link">
                    <i class="fas fa-tachometer-alt"></i> My Dashboard
                </a>
            </div>
        </div>
        <div class="footer-bottom">
            <p>© 2025 FoxUnity. All rights reserved. Made with <span>♥</span> by gamers for gamers</p>
        </div>
    </footer>
</body>
</html>
