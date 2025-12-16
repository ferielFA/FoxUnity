<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '/../../config.php';
require __DIR__ . '/../../model/CartModel.php';

// Get product ID
$productId = $_GET['id'] ?? 0;

if (!$productId) {
    header('Location: shop.php');
    exit;
}

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

// Get product details
try {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM produit WHERE produit_id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();
    
    if (!$product) {
        header('Location: shop.php');
        exit;
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Fix image path
$imagePath = '../images/nine1.png';
if (!empty($product['image'])) {
    $filename = basename($product['image']);
    if (file_exists(__DIR__ . '/../images/' . $filename)) {
        $imagePath = '../images/' . $filename;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - FoxUnity</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    
    <style>
        body { background: #0a0a0a; }
        
        .product-details-section {
            max-width: 1200px;
            margin: 0 auto;
            padding: 120px 5% 50px;
            min-height: 100vh;
        }
        
        .product-details-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 0 30px rgba(255, 59, 59, 0.2);
        }
        
        .product-image-large {
            width: 100%;
            height: 500px;
            object-fit: cover;
            border-radius: 15px;
            border: 2px solid rgba(255, 59, 59, 0.3);
        }
        
        .product-details-info h1 {
            font-family: 'Orbitron', sans-serif;
            color: #ff3b3b;
            font-size: 2.5rem;
            margin-bottom: 20px;
            line-height: 1.3;
        }
        
        .product-price-large {
            font-size: 3rem;
            color: #fff;
            font-weight: bold;
            margin: 20px 0;
        }
        
        .product-description-full {
            color: #ccc;
            line-height: 1.8;
            font-size: 1.1rem;
            margin: 30px 0;
        }
        
        .quantity-selector {
            display: flex;
            align-items: center;
            gap: 15px;
            margin: 30px 0;
        }
        
        .quantity-selector button {
            width: 50px;
            height: 50px;
            border: 2px solid #ff3b3b;
            background: transparent;
            color: #ff3b3b;
            border-radius: 10px;
            font-size: 1.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .quantity-selector button:hover {
            background: #ff3b3b;
            color: white;
        }
        
        .quantity-selector input {
            width: 80px;
            text-align: center;
            padding: 12px;
            border: 2px solid rgba(255, 59, 59, 0.3);
            background: rgba(255, 255, 255, 0.05);
            color: white;
            border-radius: 10px;
            font-size: 1.3rem;
            font-weight: bold;
        }
        
        .purchase-btn {
            width: 100%;
            padding: 20px;
            background: linear-gradient(90deg, #ff3b3b, #ff6a00);
            color: white;
            border: none;
            border-radius: 15px;
            font-size: 1.3rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .purchase-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(255, 59, 59, 0.4);
        }
        
        .back-to-shop {
            display: inline-block;
            margin-bottom: 30px;
            color: #ff3b3b;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .back-to-shop:hover {
            color: #ff6a00;
            transform: translateX(-5px);
        }
        
        @media (max-width: 768px) {
            .product-details-container {
                grid-template-columns: 1fr;
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
                <i class="fas fa-user"></i> Login / Register
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

    <section class="product-details-section">
        <a href="shop.php" class="back-to-shop">
            <i class="fas fa-arrow-left"></i> Back to Shop
        </a>
        
        <div class="product-details-container">
            <div>
                <img src="<?php echo htmlspecialchars($imagePath); ?>" 
                     alt="<?php echo htmlspecialchars($product['name']); ?>" 
                     class="product-image-large"
                     onerror="this.src='../images/nine1.png'">
            </div>
            
            <div class="product-details-info">
                <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                
                <p class="product-price-large">$<?php echo number_format($product['price'], 2); ?></p>
                
                <p class="product-description-full"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                
                <div class="quantity-selector">
                    <label style="color: #ccc; font-size: 1.1rem; font-weight: 600;">Quantity:</label>
                    <button onclick="changeQuantity(-1)">-</button>
                    <input type="number" id="quantity" value="1" min="1" readonly>
                    <button onclick="changeQuantity(1)">+</button>
                </div>
                
                <button class="purchase-btn" onclick="addToCart()">
                    <i class="fas fa-shopping-cart"></i> Add to Cart
                </button>
            </div>
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
            <p>© 2025 FoxUnity. All rights reserved. Made with <span>♥</span> by gamers for gamers</p>
        </div>
    </footer>

    <script>
        const productId = <?php echo $productId; ?>;
        const productName = <?php echo json_encode($product['name']); ?>;
        
        // Change quantity
        function changeQuantity(delta) {
            const input = document.getElementById('quantity');
            let value = parseInt(input.value) + delta;
            if (value < 1) value = 1;
            input.value = value;
        }
        
        // Add to cart
        function addToCart() {
            const quantity = parseInt(document.getElementById('quantity').value);
            const formData = new FormData();
            formData.append('action', 'add');
            formData.append('product_id', productId);
            formData.append('quantity', quantity);
            
            fetch('../../controller/CartController.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('cart-count').textContent = data.data.count;
                    showNotification(`${productName} (x${quantity}) added to cart!`, 'success');
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
        
        // Load cart count
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
