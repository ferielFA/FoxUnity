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
            <a href="http://localhost/projet_web/view/front/indexf.php" class="active">Home</a>
            <a href="events.html">Events</a>
            <a href="shop.html">Shop</a>
            <a href="trading.html">Trading</a>
            <a href="news.php">News</a>
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
            <a href="panier.html" class="cart-icon">
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
                    <a href="shop.html" class="feature-btn">
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

        <!-- Events Section -->
        <section class="feature-intro">
            <div class="feature-content">
                <div class="feature-icon-large">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="feature-text">
                    <h2>Community <span>Events</span></h2>
                    <p class="feature-description">
                        Join exciting gaming tournaments, challenges, and community events. Compete, have fun, and support charities.
                    </p>
                    <ul class="feature-list">
                        <li><i class="fas fa-check"></i> Gaming Tournaments</li>
                        <li><i class="fas fa-check"></i> Community Challenges</li>
                        <li><i class="fas fa-check"></i> Special Events</li>
                        <li><i class="fas fa-check"></i> Prize Pools & Rewards</li>
                    </ul>
                    <a href="events.html" class="feature-btn">
                        <i class="fas fa-trophy"></i> View Events
                    </a>
                </div>
            </div>
        </section>

        <!-- News Section -->
        <section class="feature-intro alternate">
            <div class="feature-content">
                <div class="feature-text">
                    <h2>Latest <span>News</span></h2>
                    <p class="feature-description">
                        Stay updated with the latest announcements, platform updates, community achievements, and gaming news from FoxUnity.
                    </p>
                    <ul class="feature-list">
                        <li><i class="fas fa-check"></i> Platform Updates</li>
                        <li><i class="fas fa-check"></i> Community Highlights</li>
                        <li><i class="fas fa-check"></i> Charity Milestones</li>
                        <li><i class="fas fa-check"></i> Gaming Industry News</li>
                    </ul>
                    <a href="news.php#news-cs2" class="feature-btn">
                        <i class="fas fa-newspaper"></i> Read Latest News
                    </a>
                </div>
                <div class="feature-icon-large">
                    <i class="fas fa-newspaper"></i>
                </div>
            </div>
        </section>

        <!-- Our Impact Section -->
        <section class="impact-section">
            <h2 class="section-title">Our <span>Impact</span></h2>
            <p class="section-subtitle">Together, we're making a real difference in communities worldwide through gaming</p>
            
            <div class="impact-stats-container">
                <div class="impact-stat-card">
                    <div class="impact-stat-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <span class="impact-stat-number">$125,000+</span>
                    <span class="impact-stat-label">Donated to Charities</span>
                </div>

                <div class="impact-stat-card">
                    <div class="impact-stat-icon">
                        <i class="fas fa-hands-helping"></i>
                    </div>
                    <span class="impact-stat-number">15+</span>
                    <span class="impact-stat-label">Organizations Supported</span>
                </div>

                <div class="impact-stat-card">
                    <div class="impact-stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <span class="impact-stat-number">5,000+</span>
                    <span class="impact-stat-label">Active Members</span>
                </div>

                <div class="impact-stat-card">
                    <div class="impact-stat-icon">
                        <i class="fas fa-globe"></i>
                    </div>
                    <span class="impact-stat-number">30+</span>
                    <span class="impact-stat-label">Countries Reached</span>
                </div>
            </div>

            <div class="supported-causes-section">
                <h3>Causes We Support</h3>
                <div class="supported-causes">
                    <span class="cause-tag"><i class="fas fa-graduation-cap"></i> Education</span>
                    <span class="cause-tag"><i class="fas fa-heartbeat"></i> Healthcare</span>
                    <span class="cause-tag"><i class="fas fa-tree"></i> Environment</span>
                    <span class="cause-tag"><i class="fas fa-hands-helping"></i> Disaster Relief</span>
                    <span class="cause-tag"><i class="fas fa-home"></i> Housing</span>
                    <span class="cause-tag"><i class="fas fa-paw"></i> Animal Welfare</span>
                </div>
            </div>
        </section>

        <!-- Support Section -->
        <section class="support-section">
            <div class="support-content">
                <div class="support-text-area">
                    <h2 class="section-title">Need <span>Support?</span></h2>
                    <p class="support-description">
                        Our dedicated support team is here to help you with any questions, issues, or feedback. 
                        Whether you need technical assistance, have questions about donations, or want to learn more about our platform.
                    </p>
                    <div class="support-features">
                        <div class="support-feature">
                            <i class="fas fa-clock"></i>
                            <div>
                                <h4>24/7 Support</h4>
                                <p>We're always here to help</p>
                            </div>
                        </div>
                        <div class="support-feature">
                            <i class="fas fa-comments"></i>
                            <div>
                                <h4>Quick Response</h4>
                                <p>Average response time: 2 hours</p>
                            </div>
                        </div>
                        <div class="support-feature">
                            <i class="fas fa-shield-alt"></i>
                            <div>
                                <h4>Secure & Private</h4>
                                <p>Your data is protected</p>
                            </div>
                        </div>
                    </div>
                    <a href="reclamation.html" class="feature-btn support-btn">
                        <i class="fas fa-headset"></i> Contact Support
                    </a>
                </div>
                <div class="support-image">
                    <i class="fas fa-life-ring"></i>
                </div>
            </div>
        </section>
    </main>

    <style>
        /* Our Impact Section */
        .impact-section {
            padding: 80px 40px;
            max-width: 1400px;
            margin: 0 auto;
            text-align: center;
        }

        .section-subtitle {
            color: #aaa;
            font-size: 18px;
            max-width: 700px;
            margin: 20px auto 60px;
            line-height: 1.8;
        }

        .impact-stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-bottom: 60px;
        }

        .impact-stat-card {
            background: linear-gradient(135deg, rgba(20, 20, 20, 0.95) 0%, rgba(10, 10, 10, 0.95) 100%);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 40px 30px;
            text-align: center;
            transition: all 0.4s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
        }

        .impact-stat-card:hover {
            transform: translateY(-10px);
            border-color: rgba(255, 122, 0, 0.5);
            box-shadow: 0 15px 40px rgba(255, 122, 0, 0.3);
        }

        .impact-stat-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #ff7a00, #ff4f00);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            color: #fff;
            box-shadow: 0 10px 30px rgba(255, 122, 0, 0.4);
        }

        .impact-stat-number {
            font-family: 'Orbitron', sans-serif;
            font-size: 42px;
            font-weight: 700;
            background: linear-gradient(135deg, #ff7a00, #ff4f00);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .impact-stat-label {
            color: #aaa;
            font-size: 16px;
            font-weight: 600;
        }

        .supported-causes-section {
            background: linear-gradient(135deg, rgba(255, 122, 0, 0.05) 0%, rgba(10, 10, 10, 0.5) 100%);
            border-radius: 25px;
            padding: 50px 40px;
            border: 2px solid rgba(255, 122, 0, 0.2);
        }

        .supported-causes-section h3 {
            font-family: 'Orbitron', sans-serif;
            font-size: 28px;
            color: #fff;
            margin-bottom: 30px;
        }

        .supported-causes {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            justify-content: center;
        }

        .cause-tag {
            background: rgba(255, 122, 0, 0.1);
            border: 2px solid rgba(255, 122, 0, 0.3);
            color: #ff7a00;
            padding: 12px 24px;
            border-radius: 25px;
            font-size: 15px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
        }

        .cause-tag:hover {
            background: rgba(255, 122, 0, 0.2);
            border-color: #ff7a00;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(255, 122, 0, 0.3);
        }

        .cause-tag i {
            font-size: 18px;
        }

        /* Support Section */
        .support-section {
            padding: 80px 40px;
            background: linear-gradient(135deg, rgba(255, 122, 0, 0.05) 0%, rgba(10, 10, 10, 0.5) 100%);
            margin-top: 50px;
        }

        .support-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
        }

        .support-text-area {
            text-align: left;
        }

        .support-description {
            color: #aaa;
            font-size: 16px;
            line-height: 1.8;
            margin-bottom: 40px;
        }

        .support-features {
            display: flex;
            flex-direction: column;
            gap: 25px;
            margin-bottom: 40px;
        }

        .support-feature {
            display: flex;
            align-items: flex-start;
            gap: 20px;
            padding: 20px;
            background: rgba(255, 255, 255, 0.02);
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: all 0.3s ease;
        }

        .support-feature:hover {
            background: rgba(255, 122, 0, 0.05);
            border-color: rgba(255, 122, 0, 0.2);
            transform: translateX(10px);
        }

        .support-feature i {
            font-size: 32px;
            color: #ff7a00;
            min-width: 40px;
        }

        .support-feature h4 {
            font-family: 'Orbitron', sans-serif;
            font-size: 18px;
            color: #fff;
            margin-bottom: 5px;
        }

        .support-feature p {
            color: #aaa;
            font-size: 14px;
        }

        .support-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(135deg, #ff7a00, #ff4f00);
            color: #fff;
            padding: 15px 40px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 700;
            font-size: 16px;
            transition: all 0.3s ease;
            border: none;
        }

        .support-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(255, 122, 0, 0.4);
        }

        .support-image {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .support-image i {
            font-size: 250px;
            color: rgba(255, 122, 0, 0.2);
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }

        /* Responsive Design */
        @media (max-width: 968px) {
            .impact-stats-container {
                grid-template-columns: 1fr;
            }

            .support-content {
                grid-template-columns: 1fr;
                gap: 40px;
            }

            .support-image i {
                font-size: 150px;
            }
        }
    </style>
    <style>
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
    </style>

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