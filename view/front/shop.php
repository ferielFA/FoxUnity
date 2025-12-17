<?php
require_once __DIR__ . '/../../controller/ProductController.php';
require_once __DIR__ . '/../../controller/UserController.php';

$productController = new ProductController();

$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$perPage = 8;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$categoryFilter = isset($_GET['category']) ? $_GET['category'] : 'all';

// Handle sort parameter - convert compound values to simple ones
$sortParam = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
$order = 'DESC';

// Parse compound sort values
if ($sortParam === 'price_asc') {
    $sortBy = 'price';
    $order = 'ASC';
} elseif ($sortParam === 'price_desc') {
    $sortBy = 'price';
    $order = 'DESC';
} elseif ($sortParam === 'stock_asc') {
    $sortBy = 'stock';
    $order = 'ASC';
} elseif ($sortParam === 'stock_desc') {
    $sortBy = 'stock';
    $order = 'DESC';
} elseif ($sortParam === 'name') {
    $sortBy = 'name';
    $order = 'ASC';
} elseif ($sortParam === 'category') {
    $sortBy = 'category';
    $order = 'ASC';
} else {
    $sortBy = $sortParam;
    $order = 'DESC';
}

$filters = [
    'search' => $search,
    'category' => $categoryFilter
];

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

        /* IMPROVED FILTERS */
        .shop-filters {
            display: flex;
            justify-content: center;
            gap: 12px;
            flex-wrap: wrap;
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            background: rgba(255, 255, 255, 0.02);
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .filter-input,
        .filter-select {
            padding: 12px 20px;
            border-radius: 30px;
            border: 1px solid rgba(255, 122, 0, 0.2);
            background: rgba(255, 255, 255, 0.05);
            color: #fff;
            outline: none;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
        }

        .filter-input {
            min-width: 280px;
            flex: 1;
        }

        .filter-input:focus,
        .filter-select:focus {
            border-color: #ff7a00;
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 0 15px rgba(255, 122, 0, 0.2);
        }

        .filter-select {
            cursor: pointer;
            min-width: 180px;
        }

        .filter-select option {
            background: #1a1a1a;
            color: #fff;
        }

        .filter-btn {
            background: linear-gradient(135deg, #ff7a00, #ff4f00);
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 30px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 122, 0, 0.3);
        }

        .filter-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 122, 0, 0.4);
        }

        .clear-btn {
            background: transparent;
            border: 1px solid #555;
            color: #aaa;
            padding: 12px 25px;
            border-radius: 30px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .clear-btn:hover {
            border-color: #ff7a00;
            color: #ff7a00;
        }

        /* Active Filters Badge */
        .active-filters {
            text-align: center;
            margin: 20px 0;
            padding: 12px;
            background: rgba(255, 122, 0, 0.05);
            border-radius: 10px;
            border-left: 3px solid #ff7a00;
        }

        .filter-badge {
            display: inline-block;
            background: rgba(255, 122, 0, 0.2);
            color: #ff7a00;
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 0.9em;
            margin: 5px;
            border: 1px solid rgba(255, 122, 0, 0.4);
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 40px;
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
            display: inline-block;
            background: rgba(255, 122, 0, 0.1);
            color: #ff7a00;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 0.85rem;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            border: 1px solid rgba(255, 122, 0, 0.3);
            width: fit-content;
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
            margin-bottom: 15px;
            line-height: 1.5;
            flex-grow: 1;
        }

        .btn-description {
            background: none;
            border: none;
            color: #ff7a00;
            text-decoration: underline;
            cursor: pointer;
            margin-bottom: 15px;
            text-align: left;
            padding: 0;
            transition: color 0.3s ease;
        }

        .btn-description:hover {
            color: #ff9933;
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
            font-size: 1.6rem;
            font-weight: 700;
        }

        .product-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: auto;
    padding-top: 20px;
    border-top: 1px solid rgba(255, 255, 255, 0.05);
    gap: 15px;
}

.product-price {
    color: #ff7a00;
    font-family: 'Orbitron', sans-serif;
    font-size: 1.6rem;
    font-weight: 700;
    flex-shrink: 0;
}

.add-to-cart-btn {
    background: linear-gradient(135deg, #ff7a00, #ff4f00);
    border: none;
    color: #fff;
    padding: 12px 20px;
    border-radius: 12px;
    cursor: pointer;
    font-weight: 600;
    font-size: 0.95rem;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    box-shadow: 0 4px 15px rgba(255, 122, 0, 0.3);
    flex-shrink: 0;
    white-space: nowrap;
    position: relative;
    overflow: hidden;
}

.add-to-cart-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s;
}

.add-to-cart-btn:hover::before {
    left: 100%;
}

.add-to-cart-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 25px rgba(255, 122, 0, 0.5);
    background: linear-gradient(135deg, #ff8c1a, #ff6600);
}

.add-to-cart-btn:active {
    transform: translateY(-1px);
    box-shadow: 0 3px 15px rgba(255, 122, 0, 0.4);
}

.add-to-cart-btn i {
    font-size: 1.1rem;
    transition: transform 0.3s ease;
}

.add-to-cart-btn:hover i {
    transform: scale(1.15);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .product-footer {
        flex-direction: column;
        align-items: stretch;
        gap: 12px;
    }
    
    .add-to-cart-btn {
        width: 100%;
        justify-content: center;
        padding: 14px 20px;
    }
    
    .product-price {
        text-align: center;
        font-size: 1.8rem;
    }
}

        .no-products {
            grid-column: 1 / -1;
            text-align: center;
            padding: 60px 20px;
            color: #aaa;
        }

        .no-products i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #333;
            display: block;
        }

        /* IMPROVED PAGINATION */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 50px;
            flex-wrap: wrap;
        }

        .page-link {
            padding: 12px 18px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            color: #fff;
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .page-link:hover {
            background: rgba(255, 122, 0, 0.2);
            border-color: #ff7a00;
            transform: translateY(-2px);
        }

        .page-link.active {
            background: linear-gradient(135deg, #ff7a00, #ff4f00);
            border-color: #ff7a00;
            font-weight: 700;
            box-shadow: 0 4px 15px rgba(255, 122, 0, 0.4);
        }

        .page-link.nav-btn {
            padding: 12px 20px;
            background: rgba(255, 122, 0, 0.1);
            border-color: #ff7a00;
        }
    </style>
</head>

<body>
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
                <p style="color: #aaa; font-size: 1.1rem; margin-bottom: 30px;">
                    Level up your setup with premium equipment
                    <?php if ($totalProducts > 0): ?>
                        <span style="color: #ff7a00; font-weight: 600;"> • <?php echo $totalProducts; ?> products
                            available</span>
                    <?php endif; ?>
                </p>

                <!-- IMPROVED FILTER FORM -->
                <form method="GET" class="shop-filters">
                    <input type="text" name="search" placeholder="🔍 Search products..."
                        value="<?php echo htmlspecialchars($search); ?>" class="filter-input">

                    <select name="category" class="filter-select">
                        <option value="all" <?php echo $categoryFilter == 'all' ? 'selected' : ''; ?>>📁 All Categories
                        </option>
                        <option value="Hardware" <?php echo $categoryFilter == 'Hardware' ? 'selected' : ''; ?>>🖥️
                            Hardware</option>
                        <option value="Accessory" <?php echo $categoryFilter == 'Accessory' ? 'selected' : ''; ?>>🎮
                            Accessory</option>
                        <option value="Merchandise" <?php echo $categoryFilter == 'Merchandise' ? 'selected' : ''; ?>>👕
                            Merchandise</option>
                        <option value="Peripherals" <?php echo $categoryFilter == 'Peripherals' ? 'selected' : ''; ?>>⌨️
                            Peripherals</option>
                    </select>

                    <select name="sort" class="filter-select">
                        <option value="created_at" <?php echo $sortParam == 'created_at' ? 'selected' : ''; ?>>📅 Newest
                            First</option>
                        <option value="price_asc" <?php echo $sortParam == 'price_asc' ? 'selected' : ''; ?>>💰 Price:
                            Low → High</option>
                        <option value="price_desc" <?php echo $sortParam == 'price_desc' ? 'selected' : ''; ?>>💰 Price:
                            High → Low</option>
                        <option value="name" <?php echo $sortParam == 'name' ? 'selected' : ''; ?>>🔤 Name (A-Z)
                        </option>
                    </select>

                    <button type="submit" class="filter-btn">
                        <i class="fas fa-search"></i> Search
                    </button>

                    <?php if ($search || $categoryFilter != 'all' || $sortParam != 'created_at'): ?>
                        <a href="shop.php" class="clear-btn">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    <?php endif; ?>
                </form>

                <!-- Active Filters Display -->
                <?php if ($search || $categoryFilter != 'all'): ?>
                    <div class="active-filters">
                        <small style="color: #aaa;">Active filters:</small>
                        <?php if ($search): ?>
                            <span class="filter-badge">🔍 Search: "<?php echo htmlspecialchars($search); ?>"</span>
                        <?php endif; ?>
                        <?php if ($categoryFilter != 'all'): ?>
                            <span class="filter-badge">📁 <?php echo htmlspecialchars($categoryFilter); ?></span>
                        <?php endif; ?>
                        <small style="color: #666; margin-left: 10px;">
                            (<?php echo $totalProducts; ?> result<?php echo $totalProducts != 1 ? 's' : ''; ?>)
                        </small>
                    </div>
                <?php endif; ?>
            </div>

            <div class="products-grid">
                <?php if (empty($products)): ?>
                    <div class="no-products">
                        <i class="fas fa-box-open"></i>
                        <h3 style="color: #666; margin-bottom: 10px;">No products found</h3>
                        <p>Try adjusting your filters or search terms</p>
                        <?php if ($search || $categoryFilter != 'all'): ?>
                            <a href="shop.php" class="filter-btn" style="display: inline-block; margin-top: 20px;">
                                <i class="fas fa-refresh"></i> View All Products
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                        <div class="product-card">
                            <img src="<?php echo !empty($product->getImage()) ? '../' . htmlspecialchars($product->getImage()) : '../images/placeholder-product.png'; ?>"
                                alt="<?php echo htmlspecialchars($product->getName()); ?>" class="product-image"
                                onerror="this.src='https://via.placeholder.com/300x300?text=No+Image'">

                            <div class="product-content">
                                <span class="product-category"><?php echo htmlspecialchars($product->getCategory()); ?></span>
                                <h3 class="product-title"><?php echo htmlspecialchars($product->getName()); ?></h3>
                                <p class="product-description">
                                    <?php 
                                    $desc = $product->getDescription();
                                    echo htmlspecialchars(substr($desc, 0, 100)) . (strlen($desc) > 100 ? '...' : ''); 
                                    ?>
                                </p>

                                <?php if (!empty($desc) && strlen($desc) > 100): ?>
                                    <button class="btn-description"
                                        onclick="openDescriptionModal('<?php echo htmlspecialchars(addslashes($desc)); ?>', '<?php echo htmlspecialchars(addslashes($product->getName())); ?>')">
                                        <i class="fas fa-info-circle"></i> View Full Description
                                    </button>
                                <?php endif; ?>

                                <div class="product-footer">
                                    <div class="product-price">$<?php echo number_format($product->getPrice(), 2); ?>
                                    </div>
                                    <button class="add-to-cart-btn"
                                        onclick="addToCart(<?php echo $product->getId(); ?>)">
                                        <i class="fas fa-cart-plus"></i> Add to Cart
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- IMPROVED PAGINATION -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($categoryFilter); ?>&sort=<?php echo urlencode($sortParam); ?>"
                            class="page-link nav-btn">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                    <?php endif; ?>

                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);

                    if ($startPage > 1): ?>
                        <a href="?page=1&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($categoryFilter); ?>&sort=<?php echo urlencode($sortParam); ?>"
                            class="page-link">1</a>
                        <?php if ($startPage > 2): ?>
                            <span style="color: #666;">...</span>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($categoryFilter); ?>&sort=<?php echo urlencode($sortParam); ?>"
                            class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($endPage < $totalPages): ?>
                        <?php if ($endPage < $totalPages - 1): ?>
                            <span style="color: #666;">...</span>
                        <?php endif; ?>
                        <a href="?page=<?php echo $totalPages; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($categoryFilter); ?>&sort=<?php echo urlencode($sortParam); ?>"
                            class="page-link"><?php echo $totalPages; ?></a>
                    <?php endif; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($categoryFilter); ?>&sort=<?php echo urlencode($sortParam); ?>"
                            class="page-link nav-btn">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Description Modal -->
            <div id="descriptionModal" class="modal"
                style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.9); z-index:10000; justify-content:center; align-items:center;">
                <div class="modal-content"
                    style="background:#1a1a1a; padding:35px; border-radius:20px; border:2px solid #ff7a00; max-width:600px; width:90%; position:relative; box-shadow: 0 20px 60px rgba(0,0,0,0.5);">
                    <span class="close-modal" onclick="closeDescriptionModal()"
                        style="position:absolute; top:20px; right:25px; color:#ff7a00; font-size:28px; cursor:pointer; transition: transform 0.3s;"
                        onmouseover="this.style.transform='scale(1.2)'"
                        onmouseout="this.style.transform='scale(1)'">&times;</span>
                    <h3 id="modalTitle"
                        style="color:#fff; margin-bottom:20px; font-family:'Orbitron', sans-serif; font-size:1.8rem;">
                    </h3>
                    <p id="modalDescription" style="color:#ccc; line-height:1.8; font-size:1.05rem;"></p>
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

        // Description Modal
        function openDescriptionModal(description, title) {
            document.getElementById('modalTitle').textContent = title;
            document.getElementById('modalDescription').textContent = description || 'No description available.';
            document.getElementById('descriptionModal').style.display = 'flex';
        }

        function closeDescriptionModal() {
            document.getElementById('descriptionModal').style.display = 'none';
        }

        window.onclick = function (event) {
            const modal = document.getElementById('descriptionModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }

        // Add to Cart Logic
        function addToCart(productId) {
            let cart = JSON.parse(localStorage.getItem('cart')) || [];
            const exists = cart.some(item => item.id === productId && item.type === 'product');

            if (!exists) {
                cart.push({ id: productId, type: 'product' });
                localStorage.setItem('cart', JSON.stringify(cart));

                const cartCount = document.querySelector('.cart-count');
                if (cartCount) cartCount.textContent = cart.length;

                window.location.href = 'panier.php';
            } else {
                alert('This item is already in your cart!');
                window.location.href = 'panier.php';
            }
        }
    </script>
</body>

</html>