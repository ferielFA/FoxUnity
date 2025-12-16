<?php
require_once __DIR__ . '/../../controller/ProductController.php';
require_once __DIR__ . '/../../controller/UserController.php';

$productController = new ProductController();

$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$perPage = 8; // Grid items per page
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
$order = isset($_GET['order']) ? $_GET['order'] : 'DESC';

$filters = ['search' => $search];
$totalProducts = $productController->countProducts($filters);
$totalPages = ceil($totalProducts / $perPage);
$offset = ($page - 1) * $perPage;

$products = $productController->getFilteredProducts($filters, $sortBy, $order, $perPage, $offset);

// Get current logged-in user
$isLoggedIn = UserController::isLoggedIn();
$currentUser = UserController::getCurrentUser();

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
    <title>FoxUnity - Shop</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Orbitron:wght@700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Reusing User Dropdown Styles from other pages to maintain consistency */
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

        /* Cart Icon */
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

        /* Shop Layout */
        .shop-section {
            padding: 120px 40px 60px;
            max-width: 1400px;
            margin: 0 auto;
            min-height: 80vh;
        }

        .section-header {
            text-align: center;
            margin-bottom: 60px;
        }

        .section-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 3rem;
            margin-bottom: 15px;
            background: linear-gradient(135deg, #fff 0%, #aaa 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 0 30px rgba(255, 255, 255, 0.1);
        }

        .section-title span {
            color: #ff7a00;
            background: none;
            -webkit-text-fill-color: #ff7a00;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
        }

        .product-card {
            background: linear-gradient(145deg, #1a1a1a 0%, #111 100%);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.3s ease;
            position: relative;
            display: flex;
            flex-direction: column;
        }

        .product-card:hover {
            transform: translateY(-10px);
            border-color: rgba(255, 122, 0, 0.3);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.5);
        }

        .product-image {
            height: 250px;
            width: 100%;
            object-fit: contain;
            background: #0f0f0f;
            padding: 20px;
            transition: transform 0.5s ease;
        }

        .product-card:hover .product-image {
            transform: scale(1.05);
        }

        .product-content {
            padding: 25px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .product-category {
            color: #888;
            font-size: 0.9rem;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .product-title {
            color: #fff;
            font-size: 1.4rem;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .product-description {
            color: #aaa;
            font-size: 0.95rem;
            margin-bottom: 20px;
            line-height: 1.5;
            flex-grow: 1;
        }

        .product-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: auto;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }

        .product-price {
            color: #ff7a00;
            font-family: 'Orbitron', sans-serif;
            font-size: 1.5rem;
            font-weight: 700;
        }

        .add-to-cart-btn {
            background: transparent;
            border: 2px solid #ff7a00;
            color: #ff7a00;
            padding: 10px 20px;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .add-to-cart-btn:hover {
            background: #ff7a00;
            color: #fff;
            box-shadow: 0 0 15px rgba(255, 122, 0, 0.4);
        }

        .no-products {
            grid-column: 1 / -1;
            text-align: center;
            padding: 50px;
            color: #aaa;
            font-size: 1.2rem;
        }
    </style>
</head>

<body>
    <!-- HEADER -->
    <header class="site-header">
        <div class="logo-section">
            <img src="../images/Nine__1_-removebg-preview.png" alt="FoxUnity Logo" class="site-logo">
            <span class="site-name">FoxUnity</span>
        </div>

        <nav class="site-nav">
            <a href="index.php">Home</a>
            <a href="events.php">Events</a>
            <a href="shop.php" class="active">Shop</a>
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

            <a href="panier.php" class="cart-icon">
                <i class="fas fa-shopping-cart"></i> Cart
                <span class="cart-count">0</span>
            </a>
        </div>
    </header>

    <main>
        <section class="shop-section">
            <div class="section-header">
                <h1 class="section-title">Gaming <span>Gear</span></h1>
                <p style="color: #aaa; font-size: 1.1rem; margin-bottom: 30px;">Level up your setup with premium
                    equipment.</p>

                <form method="GET" class="shop-filters"
                    style="display:flex; justify-content:center; gap:15px; flex-wrap:wrap; max-width:800px; margin:0 auto;">
                    <input type="text" name="search" placeholder="Search..."
                        value="<?php echo htmlspecialchars($search); ?>"
                        style="padding:10px 20px; border-radius:30px; border:none; background:rgba(255,255,255,0.1); color:#fff; width:250px; outline:none;">

                    <select name="sort"
                        style="padding:10px 20px; border-radius:30px; border:none; background:rgba(255,255,255,0.1); color:#fff; outline:none; cursor:pointer;">
                        <option value="created_at" <?php echo $sortBy == 'created_at' ? 'selected' : ''; ?>
                            style="background:#222;">Newest</option>
                        <option value="price" <?php echo $sortBy == 'price' ? 'selected' : ''; ?>
                            style="background:#222;">
                            Price</option>
                        <option value="name" <?php echo $sortBy == 'name' ? 'selected' : ''; ?> style="background:#222;">
                            Name
                        </option>
                    </select>

                    <select name="order"
                        style="padding:10px 20px; border-radius:30px; border:none; background:rgba(255,255,255,0.1); color:#fff; outline:none; cursor:pointer;">
                        <option value="DESC" <?php echo $order == 'DESC' ? 'selected' : ''; ?> style="background:#222;">
                            Desc
                        </option>
                        <option value="ASC" <?php echo $order == 'ASC' ? 'selected' : ''; ?> style="background:#222;">Asc
                        </option>
                    </select>

                    <button type="submit"
                        style="background:#ff7a00; border:none; color:white; padding:10px 25px; border-radius:30px; cursor:pointer; font-weight:600;">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>

            <div class="products-grid">
                <?php if (empty($products)): ?>
                    <div class="no-products">
                        <i class="fas fa-box-open" style="font-size: 4rem; margin-bottom: 20px; color: #333;"></i>
                        <p>No products available at the moment.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                        <div class="product-card">
                            <img src="<?php echo !empty($product->getImage()) ? '../' . htmlspecialchars($product->getImage()) : '../images/placeholder-product.png'; ?>"
                                alt="<?php echo htmlspecialchars($product->getName()); ?>" class="product-image"
                                onerror="this.src='https://via.placeholder.com/300x300?text=No+Image'">

                            <div class="product-content">
                                <div class="product-category"><?php echo htmlspecialchars($product->getCategory()); ?></div>
                                <h3 class="product-title"><?php echo htmlspecialchars($product->getName()); ?></h3>
                                <p class="product-description">
                                    <?php echo htmlspecialchars(substr($product->getDescription(), 0, 100)) . (strlen($product->getDescription()) > 100 ? '...' : ''); ?>
                                </p>

                                <button class="btn-description"
                                    onclick="openDescriptionModal('<?php echo htmlspecialchars(addslashes($product->getDescription())); ?>', '<?php echo htmlspecialchars(addslashes($product->getName())); ?>')"
                                    style="background:none; border:none; color:#aaa; text-decoration:underline; cursor:pointer; margin-bottom:15px; text-align:left; padding:0;">
                                    View Description
                                </button>

                                <div class="product-footer">
                                    <div class="product-price">$<?php echo number_format($product->getPrice(), 2); ?></div>
                                    <button class="add-to-cart-btn" onclick="addToCart(<?php echo $product->getId(); ?>)">
                                        <i class="fas fa-cart-plus"></i> Add
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination" style="display:flex; justify-content:center; gap:10px; margin-top:50px;">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo $sortBy; ?>&order=<?php echo $order; ?>"
                            style="padding:10px 20px; background:rgba(255,255,255,0.1); border-radius:20px; color:#fff; text-decoration:none;">&laquo;
                            Prev</a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo $sortBy; ?>&order=<?php echo $order; ?>"
                            style="padding:10px 15px; border-radius:50%; color:#fff; text-decoration:none; 
                              <?php echo $i == $page ? 'background:#ff7a00; font-weight:bold;' : 'background:rgba(255,255,255,0.1);'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo $sortBy; ?>&order=<?php echo $order; ?>"
                            style="padding:10px 20px; background:rgba(255,255,255,0.1); border-radius:20px; color:#fff; text-decoration:none;">Next
                            &raquo;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Description Modal -->
            <div id="descriptionModal" class="modal"
                style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:10000; justify-content:center; align-items:center;">
                <div class="modal-content"
                    style="background:#1a1a1a; padding:30px; border-radius:15px; border:1px solid #ff7a00; max-width:500px; width:90%; position:relative;">
                    <span class="close-modal" onclick="closeDescriptionModal()"
                        style="position:absolute; top:15px; right:15px; color:#ff7a00; font-size:24px; cursor:pointer;">&times;</span>
                    <h3 id="modalTitle" style="color:#fff; margin-bottom:15px; font-family:'Orbitron', sans-serif;">
                    </h3>
                    <p id="modalDescription" style="color:#ccc; line-height:1.6;"></p>
                </div>
            </div>
        </section>
    </main>

    <script>
        function openDescriptionModal(description, title) {
            document.getElementById('modalTitle').textContent = title;
            document.getElementById('modalDescription').textContent = description || 'No description available.';
            document.getElementById('descriptionModal').style.display = 'flex';
        }

        function closeDescriptionModal() {
            document.getElementById('descriptionModal').style.display = 'none';
        }

        // Close on outside click
        window.onclick = function (event) {
            const modal = document.getElementById('descriptionModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }
    </script>

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
        // Dropdown Logic
        document.addEventListener('DOMContentLoaded', function () {
            const userDropdown = document.getElementById('userDropdown');
            if (userDropdown) {
                const usernameDisplay = userDropdown.querySelector('.username-display');
                usernameDisplay.addEventListener('click', function (e) {
                    e.stopPropagation();
                    userDropdown.classList.toggle('active');
                });
                document.addEventListener('click', function () {
                    userDropdown.classList.remove('active');
                });
            }
        });

        // Add to Cart Logic
        function addToCart(productId) {
            let cart = JSON.parse(localStorage.getItem('cart')) || [];

            // Check if item already exists (optional, maybe allow multiples?)
            // Assuming for now we allow multiples or just add it.
            // But usually we check duplicates.
            const exists = cart.some(item => item.id === productId && item.type === 'product');

            if (!exists) {
                cart.push({ id: productId, type: 'product' });
                localStorage.setItem('cart', JSON.stringify(cart));

                // Update Badge
                const cartCount = document.querySelector('.cart-count');
                if (cartCount) cartCount.textContent = cart.length;

                // Redirect to cart
                window.location.href = 'panier.php';
            } else {
                alert('This item is already in your cart!');
                window.location.href = 'panier.php';
            }
        }
    </script>
</body>

</html>