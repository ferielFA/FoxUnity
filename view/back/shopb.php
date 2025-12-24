<?php
require_once __DIR__ . '/../../controller/UserController.php';
require_once __DIR__ . '/../../controller/ProductController.php';
require_once __DIR__ . '/../../controller/Couponcontroller.php';

// Initialiser les contrôleurs
$productController = new ProductController();
$couponController = new CouponController();

// Check Admin Access
if (!UserController::isLoggedIn()) {
    header('Location: ../front/login.php');
    exit();
}
$currentUser = UserController::getCurrentUser();
$userRole = strtolower($currentUser ? $currentUser->getRole() : '');
if (!$currentUser || ($userRole !== 'admin' && $userRole !== 'superadmin')) {
    header('Location: ../front/index.php');
    exit();
}

// Handle Actions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'delete' && isset($_POST['id'])) {
            if ($productController->deleteProduct($_POST['id'])) {
                $message = "Product deleted successfully.";
            } else {
                $error = "Failed to delete product.";
            }
        } elseif ($_POST['action'] === 'update' && isset($_POST['id'])) {
            $data = [
                'name' => $_POST['name'],
                'price' => $_POST['price'],
                'stock' => $_POST['stock'],
                'category' => $_POST['category']
            ];

            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../view/front/uploads/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $fileName = time() . '_' . basename($_FILES['image']['name']);
                $targetPath = $uploadDir . $fileName;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                    $data['image'] = 'front/uploads/' . $fileName;
                }
            }

            if ($data['price'] < 0 || $data['stock'] < 0) {
                $error = "Price and Stock cannot be negative.";
            } else {
                if (!$error && $productController->updateProduct($_POST['id'], $data)) {
                    $message = "Product updated successfully.";
                } elseif (!$error) {
                    $error = "Failed to update product.";
                }
            }
        } elseif ($_POST['action'] === 'create') {
            $data = [
                'name' => $_POST['name'],
                'description' => $_POST['description'],
                'price' => $_POST['price'],
                'stock' => $_POST['stock'],
                'category' => $_POST['category'],
                'publisher_id' => $currentUser->getId()
            ];

            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../view/front/uploads/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $fileName = time() . '_' . basename($_FILES['image']['name']);
                $targetPath = $uploadDir . $fileName;
                if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                    $data['image'] = 'front/uploads/' . $fileName;
                }
            }

            if ($data['price'] < 0 || $data['stock'] < 0) {
                $error = "Price and Stock cannot be negative.";
            } else {
                if (!$error && $productController->addProduct($data)) {
                    $message = "Product added successfully.";
                } elseif (!$error) {
                    $error = "Failed to add product.";
                }
            }
        } elseif ($_POST['action'] === 'add_coupon') {
            $couponData = [
                'code' => strtoupper(trim($_POST['code'])),
                'discount_type' => $_POST['discount_type'],
                'discount_value' => $_POST['discount_value'],
                'min_purchase' => $_POST['min_purchase'] ?? 0,
                'max_discount' => !empty($_POST['max_discount']) ? $_POST['max_discount'] : null,
                'usage_limit' => !empty($_POST['usage_limit']) ? $_POST['usage_limit'] : null,
                'expires_at' => $_POST['expires_at'],
                'publisher_id' => $currentUser->getId(),
                'is_active' => 1
            ];

            $result = $couponController->createCoupon($couponData);
            if ($result['success']) {
                header('Location: shopb.php?success=1&message=' . urlencode('Coupon created successfully'));
                exit;
            } else {
                $error = $result['error'];
            }
        }
    }
}

// Filters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 6;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$categoryFilter = isset($_GET['category']) ? $_GET['category'] : 'all';
$sortParam = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
$order = 'DESC';

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
$purchases = $productController->getPurchaseHistory();
$coupons = $couponController->getAllCoupons();

$userImage = $currentUser->getImage() ? '../../view/' . $currentUser->getImage() : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Shop Management - FoxUnity</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Poppins:wght@300;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
       
/* FORCE LAYOUT VERTICAL */
.content,
.content > *,
.management-section {
    display: block !important;
    width: 100% !important;
    max-width: 100% !important;
    float: none !important;
    grid-column: unset !important;
    flex: none !important;
}

.content {
    padding: 30px !important;
}

.management-section {
    margin-bottom: 30px !important;
}

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(255, 122, 0, 0.3);
        }

        .section-title {
            font-family: 'Orbitron', sans-serif;
            color: #ff7a00;
            font-size: 24px;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .section-title i {
            font-size: 28px;
        }

        .section-title .count {
            font-size: 16px;
            color: #aaa;
            font-weight: normal;
            margin-left: 10px;
        }

        .add-btn {
            padding: 12px 24px;
            background: linear-gradient(135deg, #ff7a00, #ff4f00);
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 700;
            font-size: 15px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 15px rgba(255, 122, 0, 0.3);
        }

        .add-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 122, 0, 0.5);
        }

        /* Filtres */
        .filter-controls {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-input,
        .filter-select {
            padding: 12px 16px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: #fff;
            font-size: 14px;
            outline: none;
            transition: all 0.3s;
        }

        .filter-input {
            flex: 1;
            min-width: 250px;
        }

        .filter-select {
            min-width: 180px;
        }

        .filter-input:focus,
        .filter-select:focus {
            border-color: #ff7a00;
            background: rgba(255, 255, 255, 0.08);
        }

        .apply-filters-btn {
            padding: 12px 24px;
            background: #ff7a00;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
        }

        .apply-filters-btn:hover {
            background: #ff9933;
            transform: translateY(-2px);
        }

        .clear-filters-btn {
            padding: 12px 24px;
            background: transparent;
            color: #aaa;
            border: 1px solid #555;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .clear-filters-btn:hover {
            border-color: #ff7a00;
            color: #ff7a00;
        }

        /* Tables */
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            color: #fff;
        }

        .admin-table th,
        .admin-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .admin-table th {
            color: #ff7a00;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            background: rgba(255, 122, 0, 0.05);
        }

        .admin-table tbody tr:hover {
            background: rgba(255, 122, 0, 0.03);
        }

        .admin-table img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 8px;
        }

        .action-btn {
            padding: 8px 12px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            color: #fff;
            font-weight: 600;
            margin-right: 8px;
            transition: all 0.2s;
            font-size: 13px;
        }

        .action-btn:hover {
            transform: scale(1.05);
        }

        .btn-edit {
            background: #3498db;
        }

        .btn-delete {
            background: #e74c3c;
        }

        /* Modales */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.85);
            z-index: 2000;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(5px);
        }

        .modal.show {
            display: flex !important;
        }

        .modal-content {
            background: linear-gradient(145deg, #1a1a1a 0%, #0f0f0f 100%);
            padding: 35px;
            border-radius: 15px;
            border: 2px solid #ff7a00;
            max-width: 550px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.8);
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                transform: scale(0.9) translateY(-20px);
                opacity: 0;
            }
            to {
                transform: scale(1) translateY(0);
                opacity: 1;
            }
        }

        .modal-content h3 {
            color: #fff;
            margin-bottom: 25px;
            font-family: 'Orbitron', sans-serif;
            font-size: 22px;
            border-bottom: 2px solid rgba(255, 122, 0, 0.3);
            padding-bottom: 15px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: #aaa;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 600;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
            border-radius: 8px;
            outline: none;
            box-sizing: border-box;
            font-size: 14px;
            transition: all 0.3s;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
            font-family: 'Poppins', sans-serif;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #ff7a00;
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 0 10px rgba(255, 122, 0, 0.2);
        }

        .btn-confirm {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #ff7a00, #ff4f00);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 700;
            font-size: 16px;
            margin-bottom: 12px;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(255, 122, 0, 0.3);
        }

        .btn-confirm:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 122, 0, 0.5);
        }

        .btn-cancel {
            width: 100%;
            padding: 14px;
            background: transparent;
            color: #aaa;
            border: 1px solid #555;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 15px;
            transition: all 0.3s;
        }

        .btn-cancel:hover {
            border-color: #ff7a00;
            color: #ff7a00;
        }

        /* Toast */
        .toast {
            position: fixed;
            top: 30px;
            right: 30px;
            background: rgba(20, 20, 20, 0.95);
            color: #fff;
            padding: 18px 30px;
            border-radius: 10px;
            border: 2px solid #ff7a00;
            box-shadow: 0 5px 25px rgba(0, 0, 0, 0.5);
            z-index: 3000;
            display: none;
            align-items: center;
            gap: 12px;
            font-size: 15px;
            font-weight: 600;
        }

        .toast.show {
            display: flex;
            animation: slideInRight 0.4s ease;
        }

        @keyframes slideInRight {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .toast.success {
            border-color: #2ed573;
        }

        .toast.success i {
            color: #2ed573;
        }

        .toast.error {
            border-color: #ff4757;
        }

        .toast.error i {
            color: #ff4757;
        }

        /* Pagination */
        .pagination {
            display: flex;
            gap: 10px;
            margin-top: 25px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .page-link {
            padding: 10px 16px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s;
        }

        .page-link:hover {
            background: rgba(255, 122, 0, 0.2);
            border-color: #ff7a00;
        }

        .page-link.active {
            background: linear-gradient(135deg, #ff7a00, #ff4f00);
            border-color: #ff7a00;
            font-weight: 700;
        }
    </style>
</head>
<body class="dashboard-body">
    <div class="stars"></div>
    <?php include __DIR__ . '/includes/transition.php'; ?>

    <!-- Toast Notification -->
    <div id="toast" class="toast">
        <i class="fas fa-check-circle"></i>
        <span id="toastMessage">Action successful</span>
    </div>

    <!-- Sidebar -->
    <div class="sidebar">
        <img src="../images/Nine__1_-removebg-preview.png" alt="Logo" class="dashboard-logo">
        <h2>Dashboard</h2>
        <a href="dashboard.php">Overview</a>
        <a href="users.php">Users</a>
        <a href="shopb.php" class="active">Shop</a>
        <a href="tradingb.php">Trade History</a>
        <a href="eventsb.php">Events</a>
        <a href="news_admin.php">News</a>
        <a href="news_history.php">News History</a>
        <a href="categories.php">Categories</a>
        <a href="newsletter_admin.php">Newsletter</a>
        <a href="reclamback.php">Support</a>
        <a href="evaluations_publiques.php">Public Evaluations</a>
        <a href="../front/index.php">← Return Homepage</a>
    </div>

    <!-- Main Content -->
    <div class="main">
        <div class="topbar">
            <h1>Shop Management</h1>
            <div class="topbar-right" style="display: flex; align-items: center; gap: 20px;">
                <?php include __DIR__ . '/includes/notifications.php'; ?>
                <div class="admin-dropdown" id="adminDropdown">
                    <div class="admin-user">
                        <?php if ($userImage): ?>
                            <img src="<?php echo htmlspecialchars($userImage); ?>" alt="Admin Avatar" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                        <?php else: ?>
                            <i class="fas fa-user-circle" style="font-size: 35px; color: #ff7a00;"></i>
                        <?php endif; ?>
                        <span><?php echo htmlspecialchars($currentUser->getUsername()); ?></span>
                        <i class="fas fa-chevron-down" style="font-size: 12px;"></i>
                    </div>
                    <div class="admin-dropdown-menu">
                        <a href="admin-profile.php" class="dropdown-item">
                            <i class="fas fa-user"></i>
                            <span>My Profile</span>
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="../front/logout.php" class="dropdown-item logout">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="content">
            
            <!-- ========== SECTION 1: PRODUCT INVENTORY ========== -->
            <div class="management-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-boxes"></i>
                        Product Inventory
                        <span class="count">(<?php echo $totalProducts; ?> products)</span>
                    </h2>
                    <button class="add-btn" onclick="openAddProductModal()">
                        <i class="fas fa-plus"></i> Add New Product
                    </button>
                </div>

                <!-- Filtres -->
                <form method="GET" class="filter-controls">
                    <input type="text" name="search" placeholder="🔍 Search by product name..." 
                           value="<?php echo htmlspecialchars($search); ?>" class="filter-input">
                    
                    <select name="category" class="filter-select">
                        <option value="all">📁 All Categories</option>
                        <option value="Hardware" <?php echo $categoryFilter == 'Hardware' ? 'selected' : ''; ?>>🖥️ Hardware</option>
                        <option value="Accessory" <?php echo $categoryFilter == 'Accessory' ? 'selected' : ''; ?>>🎮 Accessory</option>
                        <option value="Merchandise" <?php echo $categoryFilter == 'Merchandise' ? 'selected' : ''; ?>>👕 Merchandise</option>
                        <option value="Peripherals" <?php echo $categoryFilter == 'Peripherals' ? 'selected' : ''; ?>>⌨️ Peripherals</option>
                    </select>
                    
                    <select name="sort" class="filter-select">
                        <option value="created_at">📅 Recent First</option>
                        <option value="name" <?php echo $sortParam == 'name' ? 'selected' : ''; ?>>🔤 Name (A-Z)</option>
                        <option value="price_asc" <?php echo $sortParam == 'price_asc' ? 'selected' : ''; ?>>💰 Price: Low → High</option>
                        <option value="price_desc" <?php echo $sortParam == 'price_desc' ? 'selected' : ''; ?>>💰 Price: High → Low</option>
                    </select>
                    
                    <button type="submit" class="apply-filters-btn">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>

                    <?php if ($search || $categoryFilter != 'all'): ?>
                        <a href="shopb.php" class="clear-filters-btn">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    <?php endif; ?>
                </form>

                <!-- Table -->
                <div style="overflow-x: auto;">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($products)): ?>
                                <tr>
                                    <td colspan="7" style="text-align:center; color:#888; padding: 40px;">
                                        <i class="fas fa-inbox" style="font-size: 50px; opacity: 0.3; display: block; margin-bottom: 15px;"></i>
                                        No products found.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($products as $p): ?>
                                    <tr>
                                        <td><strong>#<?php echo $p->getId(); ?></strong></td>
                                        <td>
                                            <img src="<?php echo $p->getImage() ? '../../view/' . $p->getImage() : '../images/placeholder-product.png'; ?>" alt="">
                                        </td>
                                        <td><?php echo htmlspecialchars($p->getName()); ?></td>
                                        <td>
                                            <span style="background: rgba(255,122,0,0.1); padding: 5px 12px; border-radius: 5px; font-size: 12px; border: 1px solid rgba(255,122,0,0.3);">
                                                <?php echo htmlspecialchars($p->getCategory()); ?>
                                            </span>
                                        </td>
                                        <td style="color: #2ed573; font-weight: 700; font-size: 16px;">$<?php echo number_format($p->getPrice(), 2); ?></td>
                                        <td style="color: <?php echo $p->getStock() > 10 ? '#2ed573' : ($p->getStock() > 0 ? '#ffa502' : '#ff4757'); ?>; font-weight: 600;">
                                            <?php echo $p->getStock(); ?>
                                        </td>
                                        <td>
                                            <button class="action-btn btn-edit" onclick='openEditProductModal(<?php echo json_encode([
                                                "id" => $p->getId(),
                                                "name" => $p->getName(),
                                                "price" => $p->getPrice(),
                                                "stock" => $p->getStock(),
                                                "category" => $p->getCategory()
                                            ]); ?>)'>
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <button class="action-btn btn-delete" onclick="openDeleteProductModal(<?php echo $p->getId(); ?>)">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($categoryFilter); ?>&sort=<?php echo urlencode($sortParam); ?>" class="page-link">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($categoryFilter); ?>&sort=<?php echo urlencode($sortParam); ?>" 
                               class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($categoryFilter); ?>&sort=<?php echo urlencode($sortParam); ?>" class="page-link">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ========== SECTION 2: COUPONS ========== -->
            <div class="management-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-tags"></i>
                        Active Coupons
                        <span class="count">(<?php echo count($coupons); ?> coupons)</span>
                    </h2>
                    <button class="add-btn" onclick="openAddCouponModal()">
                        <i class="fas fa-plus"></i> Add Coupon
                    </button>
                </div>

                <div style="overflow-x: auto;">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Discount</th>
                                <th>Min Purchase</th>
                                <th>Usage</th>
                                <th>Expires</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($coupons)): ?>
                                <tr>
                                    <td colspan="7" style="text-align:center; color:#888; padding: 40px;">
                                        <i class="fas fa-ticket-alt" style="font-size: 50px; opacity: 0.3; display: block; margin-bottom: 15px;"></i>
                                        No coupons found.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($coupons as $item): 
                                    $coupon = $item['coupon'];
                                ?>
                                        <tr id="coupon-row-<?php echo $coupon->getCouponId(); ?>">
                                            <td style="font-weight:bold; color:#ff7a00; font-size: 15px;"><?php echo htmlspecialchars($coupon->getCode()); ?></td>
                                            <td style="font-weight: 600;">
                                                <?php echo $coupon->getDiscountType() == 'percentage' ? $coupon->getDiscountValue() . '%' : '$' . number_format($coupon->getDiscountValue(), 2); ?>
                                            </td>
                                            <td>$<?php echo number_format($coupon->getMinPurchase(), 2); ?></td>
                                            <td>
                                                <?php echo $coupon->getUsedCount(); ?> / <?php echo $coupon->getUsageLimit() ?: '∞'; ?>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($coupon->getExpiresAt())); ?></td>
                                            <td>
                                                <?php if ($coupon->isValid()): ?>
                                                    <span style="color: #2ed573; font-weight: 600;">✓ Active</span>
                                                <?php else: ?>
                                                    <span style="color: #ff4757; font-weight: 600;">✗ Expired</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button class="action-btn btn-delete" onclick="deleteCouponAjax(<?php echo $coupon->getCouponId(); ?>)">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </td>
                                        </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ========== SECTION 3: PURCHASE HISTORY ========== -->
            <div class="management-section">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-history"></i>
                        Purchase History
                        <span class="count">(<?php echo count($purchases); ?> transactions)</span>
                    </h2>
                </div>

                <div style="overflow-x: auto;">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Transaction ID</th>
                                <th>User</th>
                                <th>Product</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($purchases)): ?>
                                <tr>
                                    <td colspan="5" style="text-align:center; color:#888; padding: 40px;">
                                        <i class="fas fa-receipt" style="font-size: 50px; opacity: 0.3; display: block; margin-bottom: 15px;"></i>
                                        No purchase history.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($purchases as $ph): ?>
                                    <tr>
                                        <td><?php echo date('M j, Y H:i', strtotime($ph['purchaseDate'])); ?></td>
                                        <td><code style="color:#ff7a00; font-size: 12px; background: rgba(255,122,0,0.1); padding: 4px 8px; border-radius: 4px;"><?php echo htmlspecialchars($ph['transactionId']); ?></code></td>
                                        <td><?php echo htmlspecialchars($ph['username']); ?></td>
                                        <td><?php echo htmlspecialchars($ph['product_name']); ?></td>
                                        <td style="color: #2ed573; font-weight: 700; font-size: 16px;">$<?php echo number_format($ph['amount'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <footer class="site-footer">
            © 2025 <span>Nine Tailed Fox</span>. All Rights Reserved.
        </footer>
    </div>

    <!-- ========== MODAL: ADD PRODUCT ========== -->
    <div id="addProductModal" class="modal">
        <div class="modal-content">
            <h3><i class="fas fa-plus-circle"></i> Add New Product</h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="create">

                <div class="form-group">
                    <label>Product Name *</label>
                    <input type="text" name="name" required placeholder="e.g., Gaming Mouse">
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" placeholder="Product description..."></textarea>
                </div>

                <div class="form-group">
                    <label>Product Image</label>
                    <input type="file" name="image" accept="image/*">
                </div>

                <div class="form-group">
                    <label>Category *</label>
                    <select name="category" required>
                        <option value="">Select Category</option>
                        <option value="Hardware">Hardware</option>
                        <option value="Accessory">Accessory</option>
                        <option value="Merchandise">Merchandise</option>
                        <option value="Peripherals">Peripherals</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Price ($) *</label>
                    <input type="number" step="0.01" name="price" required placeholder="0.00">
                </div>

                <div class="form-group">
                    <label>Stock *</label>
                    <input type="number" name="stock" required placeholder="0">
                </div>

                <button type="submit" class="btn-confirm">
                    <i class="fas fa-check"></i> Add Product
                </button>
                <button type="button" class="btn-cancel" onclick="closeModal('addProductModal')">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </form>
        </div>
    </div>

    <!-- ========== MODAL: EDIT PRODUCT ========== -->
    <div id="editProductModal" class="modal">
        <div class="modal-content">
            <h3><i class="fas fa-edit"></i> Edit Product</h3>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_product_id">

                <div class="form-group">
                    <label>Product Name *</label>
                    <input type="text" name="name" id="edit_product_name" required>
                </div>

                <div class="form-group">
                    <label>Product Image</label>
                    <input type="file" name="image" accept="image/*">
                    <small style="color:#888;">Leave empty to keep current image</small>
                </div>

                <div class="form-group">
                    <label>Category *</label>
                    <select name="category" id="edit_product_category" required>
                        <option value="Hardware">Hardware</option>
                        <option value="Accessory">Accessory</option>
                        <option value="Merchandise">Merchandise</option>
                        <option value="Peripherals">Peripherals</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Price ($) *</label>
                    <input type="number" step="0.01" name="price" id="edit_product_price" required>
                </div>

                <div class="form-group">
                    <label>Stock *</label>
                    <input type="number" name="stock" id="edit_product_stock" required>
                </div>

                <button type="submit" class="btn-confirm">
                    <i class="fas fa-save"></i> Save Changes
                </button>
                <button type="button" class="btn-cancel" onclick="closeModal('editProductModal')">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </form>
        </div>
    </div>

    <!-- ========== MODAL: DELETE PRODUCT ========== -->
    <div id="deleteProductModal" class="modal">
        <div class="modal-content" style="text-align: center;">
            <i class="fas fa-exclamation-triangle" style="font-size: 60px; color: #ff4757; margin-bottom: 20px;"></i>
            <h3>Delete Product?</h3>
            <p style="color:#aaa; margin: 20px 0;">This action cannot be undone.</p>

            <form method="POST">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="delete_product_id">
                <button type="submit" class="btn-confirm" style="background: linear-gradient(135deg, #e74c3c, #c0392b);">
                    <i class="fas fa-trash"></i> Yes, Delete
                </button>
                <button type="button" class="btn-cancel" onclick="closeModal('deleteProductModal')">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </form>
        </div>
    </div>

    <!-- ========== MODAL: ADD COUPON ========== -->
    <div id="addCouponModal" class="modal">
        <div class="modal-content">
            <h3><i class="fas fa-ticket-alt"></i> Create New Coupon</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add_coupon">

                <div class="form-group">
                    <label>Coupon Code *</label>
                    <input type="text" name="code" required placeholder="e.g., WELCOME10" style="text-transform: uppercase;">
                </div>

                <div class="form-group">
                    <label>Discount Type *</label>
                    <select name="discount_type" required>
                        <option value="percentage">Percentage (%)</option>
                        <option value="fixed">Fixed Amount ($)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Discount Value *</label>
                    <input type="number" step="0.01" name="discount_value" required placeholder="e.g., 10">
                </div>

                <div class="form-group">
                    <label>Minimum Purchase ($)</label>
                    <input type="number" step="0.01" name="min_purchase" value="0" placeholder="0.00">
                </div>

                <div class="form-group">
                    <label>Max Discount ($) (optional)</label>
                    <input type="number" step="0.01" name="max_discount" placeholder="Leave empty for no limit">
                </div>

                <div class="form-group">
                    <label>Usage Limit (optional)</label>
                    <input type="number" name="usage_limit" placeholder="Leave empty for unlimited">
                </div>

                <div class="form-group">
                    <label>Expiration Date *</label>
                    <input type="datetime-local" name="expires_at" required>
                </div>

                <button type="submit" class="btn-confirm">
                    <i class="fas fa-check"></i> Create Coupon
                </button>
                <button type="button" class="btn-cancel" onclick="closeModal('addCouponModal')">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </form>
        </div>
    </div>

    <script>
        // ========== MODAL FUNCTIONS ==========
        function openAddProductModal() {
            document.getElementById('addProductModal').classList.add('show');
        }

        function openEditProductModal(product) {
            document.getElementById('edit_product_id').value = product.id;
            document.getElementById('edit_product_name').value = product.name;
            document.getElementById('edit_product_price').value = product.price;
            document.getElementById('edit_product_stock').value = product.stock;
            document.getElementById('edit_product_category').value = product.category;
            document.getElementById('editProductModal').classList.add('show');
        }

        function openDeleteProductModal(id) {
            document.getElementById('delete_product_id').value = id;
            document.getElementById('deleteProductModal').classList.add('show');
        }

        function openAddCouponModal() {
            document.getElementById('addCouponModal').classList.add('show');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('show');
        }

        // ========== AJAX FUNCTIONS ==========
        async function deleteCouponAjax(id) {
            if (!confirm('Are you sure you want to delete this coupon?')) return;

            try {
                const response = await fetch(`api/delete_coupon.php?id=${id}`, {
                    method: 'GET'
                });
                const data = await response.json();

                if (data.success) {
                    const row = document.getElementById(`coupon-row-${id}`);
                    if (row) {
                        row.style.opacity = '0';
                        setTimeout(() => {
                            row.remove();
                            showToast(data.message, 'success');
                        }, 300);
                    }
                } else {
                    showToast(data.error || 'Failed to delete coupon', 'error');
                }
            } catch (error) {
                console.error('Delete error:', error);
                showToast('Error connecting to server', 'error');
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('show');
            }
        }

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal.show').forEach(modal => {
                    modal.classList.remove('show');
                });
            }
        });

        // ========== TOAST NOTIFICATION ==========
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            const msgSpan = document.getElementById('toastMessage');
            const icon = toast.querySelector('i');

            msgSpan.textContent = message;
            toast.className = 'toast ' + type;
            
            if (type === 'success') {
                icon.className = 'fas fa-check-circle';
            } else {
                icon.className = 'fas fa-times-circle';
            }

            toast.classList.add('show');
            setTimeout(() => {
                toast.classList.remove('show');
            }, 4000);
        }

        // Show PHP messages
        <?php if ($message): ?>
            showToast("<?php echo addslashes($message); ?>", "success");
        <?php endif; ?>

        <?php if ($error): ?>
            showToast("<?php echo addslashes($error); ?>", "error");
        <?php endif; ?>

        <?php if (isset($_GET['success']) && isset($_GET['message'])): ?>
            showToast("<?php echo addslashes($_GET['message']); ?>", "success");
        <?php endif; ?>

        // ========== ADMIN DROPDOWN ==========
        const adminDropdown = document.getElementById('adminDropdown');
        if (adminDropdown) {
            const adminUser = adminDropdown.querySelector('.admin-user');
            adminUser.addEventListener('click', (e) => {
                e.stopPropagation();
                adminDropdown.classList.toggle('active');
            });
            document.addEventListener('click', () => {
                adminDropdown.classList.remove('active');
            });
        }

        console.log('✅ Shop Management page loaded successfully');
    </script>
</body>
</html>