<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../../config.php';
require __DIR__ . '/../../model/CartModel.php';

// Get cart count
$cartCount = 0;
try {
    if (!isset($_SESSION['cart_session_id'])) {
        $_SESSION['cart_session_id'] = session_id();
    }
    $cartModel = new CartModel();
    $cart = $cartModel->getCart($_SESSION['cart_session_id']);
    $cartCount = $cartModel->getCartCount($cart['id']);
} catch (Exception $e) {
    // Silently fail
}

// Get products
try {
    $pdo = getDB();
    try {
        $stmt = $pdo->query("SELECT * FROM produit ORDER BY id DESC");
    } catch (PDOException $e) {
        try {
            $stmt = $pdo->query("SELECT * FROM produit ORDER BY created_at DESC");
        } catch (PDOException $e2) {
            $stmt = $pdo->query("SELECT * FROM produit");
        }
    }
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FoxUnity - Shop</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    
    <style>
        body { 
            background: #0a0a0a;
            margin: 0;
            padding: 0;
        }
        
        .main-section { 
            padding: 120px 5% 50px;
            min-height: 100vh;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .shop-title {
            font-family: 'Orbitron', sans-serif;
            text-align: center;
            font-size: 2.5rem;
            color: #ff3b3b;
            letter-spacing: 2px;
            margin-bottom: 50px;
            text-shadow: 0 0 10px rgba(255, 59, 59, 0.5);
        }
        
        .shop-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 30px;
            padding: 20px 0;
        }
        
        .product-card {
            background: rgba(20, 20, 20, 0.8);
            border: 2px solid rgba(255, 59, 59, 0.3);
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
        }
        
        .product-card:hover {
            transform: translateY(-10px);
            border-color: rgba(255, 106, 0, 0.6);
            box-shadow: 0 10px 30px rgba(255, 59, 59, 0.4);
        }
        
        .product-image {
            width: 100%;
            height: 250px;
            object-fit: cover;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        
        .product-card:hover .product-image {
            transform: scale(1.05);
        }
        
        .product-info {
            padding: 20px;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }
        
        .product-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.2rem;
            color: #ff5722;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
            min-height: 2.4em;
            line-height: 1.2;
            font-weight: 700;
        }
        
        .product-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: #fff;
            margin: 10px 0;
        }
        
        .short-description {
            color: #ccc;
            font-size: 0.9rem;
            line-height: 1.5;
            margin-bottom: 15px;
            flex-grow: 1;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .product-buttons {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: auto;
        }
        
        .read-more-btn,
        .add-to-cart-btn {
            width: 100%;
            padding: 12px 20px;
            border: none;
            border-radius: 25px;
            font-weight: 700;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .read-more-btn {
            background: linear-gradient(135deg, #ff5722, #ff9800);
            color: white;
        }
        
        .read-more-btn:hover {
            background: linear-gradient(135deg, #ff9800, #ff5722);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 87, 34, 0.4);
        }
        
        .add-to-cart-btn {
            background: linear-gradient(135deg, #ff6a00, #ff3b3b);
            color: white;
        }
        
        .add-to-cart-btn:hover {
            background: linear-gradient(135deg, #ff3b3b, #ff6a00);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 59, 59, 0.4);
        }
        
        @media (max-width: 768px) {
            .shop-container {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 20px;
            }
            
            .shop-title {
                font-size: 2rem;
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

    <!-- HEADER -->
    <header class="site-header">
        <div class="logo-section">
            <img src="../images/Nine__1_-removebg-preview.png" alt="FoxUnity Logo" class="site-logo">
            <span class="site-name">FoxUnity</span>
        </div>
        
        <nav class="site-nav">
            <a href="indexf.php">Home</a>
            <a href="events.html">Events</a>
            <a href="shop.php" class="active">Shop</a>
            <a href="trading.html">Trading</a>
            <a href="news.html">News</a>
            <a href="reclamation.html">Support</a>
            <a href="about.html">About Us</a>
        </nav>
        
        <div class="header-right">
            <a href="login.html" class="login-register-link">
                <i class="fas fa-user"></i> Login
            </a>
            <a href="profile.html" class="profile-icon">
                <i class="fas fa-user-circle"></i>
            </a>
            <a href="panier.php" class="cart-icon">
                <i class="fas fa-shopping-cart"></i> Cart
                <span class="cart-count" id="cart-count"><?php echo $cartCount; ?></span>
            </a>
        </div>
    </header>

    <section class="main-section">
        <h1 class="shop-title">SHOP</h1>

        <div class="shop-container">
            <?php if (count($products) > 0): ?>
                <?php foreach ($products as $product): ?>
                    <?php 
                    // Fix image path - use view/images directory
                    $imagePath = '../images/nine1.png'; // Default fallback
                    if (!empty($product['image'])) {
                        $filename = basename($product['image']);
                        // Check if file exists in view/images
                        if (file_exists(__DIR__ . '/../images/' . $filename)) {
                            $imagePath = '../images/' . $filename;
                        }
                    }
                    ?>
                    <div class="product-card" data-product-id="<?php echo $product['id']; ?>">
                        <img src="<?php echo htmlspecialchars($imagePath); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>" 
                             class="product-image"
                             onclick="window.location.href='product_details.php?id=<?php echo $product['id']; ?>'"
                             onerror="this.src='../images/nine1.png'">
                        <div class="product-info">
                            <h3 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p class="product-price"><?php echo number_format($product['price'], 0); ?> DT</p>
                            <p class="short-description"><?php echo htmlspecialchars($product['description']); ?></p>
                            <div class="product-buttons">
                                <button class="read-more-btn" onclick="window.location.href='product_details.php?id=<?php echo $product['id']; ?>'">
                                    <i class="fas fa-info-circle"></i> READ MORE
                                </button>
                                <button class="add-to-cart-btn" onclick="addToCart(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars(addslashes($product['name'])); ?>')">
                                    <i class="fas fa-shopping-cart"></i> ADD TO CART
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align:center; width:100%; color: #ccc; grid-column: 1/-1;">
                    <i class="fas fa-box-open" style="font-size: 3rem; display: block; margin-bottom: 20px; opacity: 0.5;"></i>
                    No products available at the moment.
                </p>
            <?php endif; ?>
        </div>
    </section>

    <footer class="site-footer">
        <div class="footer-content">
            <div class="footer-section">
                <h4>FoxUnity</h4>
                <p>Gaming for Good - Every action makes a difference</p>
            </div>
            <div class="footer-section">
                <h4>Quick Links</h4>
                <a href="indexf.php">Home</a>
                <a href="shop.php">Shop</a>
                <a href="trading.html">Trading</a>
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
            <p>Â© 2025 FoxUnity. All rights reserved. Made with <span>â™¥</span> by gamers for gamers</p>
        </div>
    </footer>

    <script>
        // Add to cart functionality
        function addToCart(productId, productName) {
            const formData = new FormData();
            formData.append('action', 'add');
            formData.append('product_id', productId);
            formData.append('quantity', 1);
            
            fetch('../../controller/CartController.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update cart count
                    document.getElementById('cart-count').textContent = data.data.count;
                    
                    // Show success message
                    showNotification(`${productName} added to cart!`, 'success');
                } else {
                    showNotification('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Failed to add to cart', 'error');
            });
        }
        
        // Show notification
        function showNotification(message, type = 'success') {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 100px;
                right: 20px;
                background: ${type === 'success' ? 'linear-gradient(135deg, #27ae60, #2ecc71)' : 'linear-gradient(135deg, #e74c3c, #c0392b)'};
                color: white;
                padding: 15px 25px;
                border-radius: 10px;
                box-shadow: 0 5px 20px rgba(0,0,0,0.3);
                z-index: 10001;
                animation: slideIn 0.3s ease;
                font-weight: 600;
            `;
            notification.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ${message}`;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
        
        // Load cart count on page load
        window.addEventListener('load', function() {
            fetch('../../controller/CartController.php?action=getCount')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('cart-count').textContent = data.data.count;
                    }
                })
                .catch(error => console.error('Error loading cart count:', error));
        });
        
        // Add CSS animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(400px); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(400px); opacity: 0; }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>

