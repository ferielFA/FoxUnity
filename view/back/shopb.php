<?php
require_once __DIR__ . '/../../controller/UserController.php';
require_once __DIR__ . '/../../controller/ProductController.php';

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

$productController = new ProductController();

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

            // Image Upload Handling
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../view/front/uploads/'; // Adjust path as needed
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $fileName = time() . '_' . basename($_FILES['image']['name']);
                $targetPath = $uploadDir . $fileName;

                if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                    // Update the image path in the database (relative to view)
                    $data['image'] = 'front/uploads/' . $fileName;
                } else {
                    $error = "Failed to upload image.";
                }
            }

            // Basic server-side validation check
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

            // Image Upload Handling
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../view/front/uploads/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $fileName = time() . '_' . basename($_FILES['image']['name']);
                $targetPath = $uploadDir . $fileName;

                if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                    $data['image'] = 'front/uploads/' . $fileName;
                } else {
                    $error = "Failed to upload image.";
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
        }
    }
}

$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$perPage = 6;
$search = isset($_GET['search']) ? $_GET['search'] : '';
$categoryFilter = isset($_GET['category']) ? $_GET['category'] : 'all';
$sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
$order = isset($_GET['order']) ? $_GET['order'] : 'DESC';

$filters = [
    'search' => $search,
    'category' => $categoryFilter
];
$totalProducts = $productController->countProducts($filters);
$totalPages = ceil($totalProducts / $perPage);
$offset = ($page - 1) * $perPage;

$products = $productController->getFilteredProducts($filters, $sortBy, $order, $perPage, $offset);
$purchases = $productController->getPurchaseHistory();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Shop Management - FoxUnity</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Poppins:wght@300;600&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .management-section {
            padding: 30px;
            background: rgba(20, 20, 20, 0.6);
            border-radius: 15px;
            margin-bottom: 30px;
            border: 1px solid rgba(255, 122, 0, 0.2);
        }

        .section-title {
            font-family: 'Orbitron', sans-serif;
            color: #ff7a00;
            margin-bottom: 20px;
            border-bottom: 1px solid rgba(255, 122, 0, 0.3);
            padding-bottom: 10px;
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
            color: #fff;
        }

        .admin-table th,
        .admin-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .admin-table th {
            color: #ff7a00;
            font-weight: 600;
        }

        .action-btn {
            padding: 8px 12px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            color: #fff;
            font-weight: 600;
            margin-right: 5px;
            transition: transform 0.2s;
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

        /* Updated Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 2000;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background: #1a1a1a;
            padding: 30px;
            border-radius: 15px;
            border: 1px solid #ff7a00;
            width: 400px;
            box-shadow: 0 0 30px rgba(255, 122, 0, 0.2);
            position: relative;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: #aaa;
            margin-bottom: 8px;
            font-size: 0.9em;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            background: #333;
            border: 1px solid #555;
            color: white;
            border-radius: 8px;
            outline: none;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        .form-group input:focus {
            border-color: #ff7a00;
        }

        /* Error State Styling */
        .form-group input.is-invalid,
        .form-group select.is-invalid {
            border-color: #ff4757 !important;
            box-shadow: 0 0 5px rgba(255, 71, 87, 0.5);
        }

        .error-msg {
            color: #ff4757;
            font-size: 0.8em;
            margin-top: 5px;
            display: none;
        }

        .btn-confirm {
            width: 100%;
            padding: 12px;
            background: #ff7a00;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 700;
            font-family: 'Poppins', sans-serif;
        }

        .btn-cancel {
            width: 100%;
            padding: 12px;
            background: transparent;
            color: #aaa;
            border: 1px solid #555;
            border-radius: 8px;
            cursor: pointer;
            margin-top: 10px;
        }

        .btn-danger {
            background: #e74c3c;
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
    </style>
</head>

<body class="dashboard-body">
    <div class="stars"></div>

    <!-- REQUIRED Toast Notification Element -->
    <div id="toast" class="toast">
        <i class="fas fa-check-circle"></i>
        <span id="toastMessage">Action successful</span>
    </div>

    <!-- SIDEBAR -->
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

    <div class="main">
        <div class="topbar">
            <h1>Shop Management</h1>
            <div class="admin-dropdown" id="adminDropdown">
                <div class="admin-user">
                    <?php
                    $uImg = $currentUser->getImage() ? '../../view/' . $currentUser->getImage() : '';
                    if ($uImg): ?>
                        <img src="<?php echo htmlspecialchars($uImg); ?>" alt="Admin Avatar"
                            style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
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

        <div class="content">

            <!-- Product Management -->
            <div class="management-section">
                <div
                    style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid rgba(255,122,0,0.3); padding-bottom:10px; margin-bottom:20px;">
                    <h2 class="section-title" style="border:none; margin:0;"><i class="fas fa-boxes"></i> Product
                        Inventory</h2>
                    <button class="btn-confirm" style="width:auto; padding:10px 20px;" onclick="openAddModal()">
                        <i class="fas fa-plus"></i> Add New Product
                    </button>
                </div>

                <form method="GET" class="filter-controls"
                    style="margin-bottom: 25px; display: flex; align-items: center; gap: 15px; flex-wrap: wrap; background: rgba(255, 255, 255, 0.02); padding: 15px; border-radius: 10px; border: 1px solid rgba(255, 255, 255, 0.05);">
                    <div style="flex: 1; min-width: 250px;">
                        <input type="text" name="search" placeholder="Search by product name..."
                            value="<?php echo htmlspecialchars($search); ?>" class="filter-input"
                            style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #444; background: #222; color: #fff; outline:none;">
                    </div>

                    <div style="flex: 0 0 auto;">
                        <select name="sort" class="filter-select" onchange="this.form.submit()"
                            style="padding: 12px; border-radius: 8px; border: 1px solid #444; background: #222; color: #fff; cursor: pointer; outline:none; min-width: 180px;">
                            <option value="created_at" <?php echo $sortBy == 'created_at' ? 'selected' : ''; ?>>Date Added
                            </option>
                            <option value="category" <?php echo $sortBy == 'category' ? 'selected' : ''; ?>>Category
                            </option>
                            <option value="stock" <?php echo $sortBy == 'stock' ? 'selected' : ''; ?>>Stock</option>
                            <option value="price_asc" <?php echo ($sortBy == 'price' && $order == 'ASC') ? 'selected' : ''; ?>>Price: Low to High</option>
                            <option value="price_desc" <?php echo ($sortBy == 'price' && $order == 'DESC') ? 'selected' : ''; ?>>Price: High to Low</option>
                        </select>
                    </div>

                    <!-- Hidden Order Input handled by JS -->
                    <input type="hidden" name="order" id="orderInput" value="<?php echo $order; ?>">

                    <div style="display: flex; gap: 10px;">
                        <button type="submit" class="filter-btn"
                            style="padding: 12px 25px; background: #ff7a00; color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 8px; transition: background 0.3s;">
                            <i class="fas fa-search"></i> Search
                        </button>

                        <?php if ($search || $sortBy != 'created_at'): ?>
                            <a href="shopb.php" class="filter-btn"
                                style="padding: 12px 20px; background: transparent; border: 1px solid #555; color: #ccc; border-radius: 8px; text-decoration: none; display: flex; align-items: center; gap: 8px; transition: all 0.3s;">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        <?php endif; ?>
                    </div>
                </form>

                <script>
                    document.querySelector('select[name="sort"]').addEventListener('change', function () {
                        let val = this.value;
                        let orderInput = document.getElementById('orderInput');
                        if (val === 'price_asc') {
                            this.value = 'price';
                            orderInput.value = 'ASC';
                        } else if (val === 'price_desc') {
                            this.value = 'price';
                            orderInput.value = 'DESC';
                        } else {
                            // Default order for others (DESC for date, ASC for stock? or standard DESC)
                            // User asked for "Stock" but didn't specify order. Usually high stock is good or bad? 
                            // Let's default to DESC so big numbers first.
                            orderInput.value = 'DESC';
                        }
                    });
                </script>

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
                            <?php foreach ($products as $p): ?>
                                <tr>
                                    <td>#<?php echo $p->getId(); ?></td>
                                    <td>
                                        <img src="<?php echo $p->getImage() ? '../../view/' . $p->getImage() : '../images/placeholder-product.png'; ?>"
                                            style="width: 40px; height: 40px; object-fit: cover; border-radius: 5px;"
                                            onerror="this.src='../images/placeholder-product.png'">
                                    </td>
                                    <td><?php echo htmlspecialchars($p->getName()); ?></td>
                                    <td><?php echo htmlspecialchars($p->getCategory()); ?></td>
                                    <td>$<?php echo number_format($p->getPrice(), 2); ?></td>
                                    <td><?php echo $p->getStock(); ?></td>
                                    <td>
                                        <button class="action-btn btn-edit" onclick="openEditModal(<?php echo htmlspecialchars(json_encode([
                                            'id' => $p->getId(),
                                            'name' => $p->getName(),
                                            'price' => $p->getPrice(),
                                            'stock' => $p->getStock(),
                                            'category' => $p->getCategory()
                                        ])); ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>

                                        <button type="button" class="action-btn btn-delete"
                                            onclick="openDeleteModal(<?php echo $p->getId(); ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="pagination" style="display: flex; gap: 10px; margin-top: 20px; justify-content: start;">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo $sortBy; ?>&order=<?php echo $order; ?>"
                                    class="page-link"
                                    style="padding: 8px 15px; background: rgba(255,122,0,0.1); border: 1px solid #ff7a00; color: #fff; text-decoration: none; border-radius: 5px;">&laquo;
                                    Prev</a>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo $sortBy; ?>&order=<?php echo $order; ?>"
                                    class="page-link" style="padding: 8px 15px; 
                                           background: <?php echo $i == $page ? '#ff7a00' : 'rgba(255,255,255,0.05)'; ?>; 
                                           border: 1px solid <?php echo $i == $page ? '#ff7a00' : '#444'; ?>; 
                                           color: #fff; 
                                           text-decoration: none; 
                                           border-radius: 5px;
                                           margin: 0 5px;">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo $sortBy; ?>&order=<?php echo $order; ?>"
                                    class="page-link"
                                    style="padding: 8px 15px; background: rgba(255,122,0,0.1); border: 1px solid #ff7a00; color: #fff; text-decoration: none; border-radius: 5px;">Next
                                    &raquo;</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Purchase History -->
            <div class="management-section">
                <h2 class="section-title"><i class="fas fa-history"></i> Purchase History</h2>
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
                                    <td colspan="5" style="text-align:center; color:#888; padding: 30px;">No purchase
                                        history found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($purchases as $ph): ?>
                                    <tr>
                                        <td><?php echo date('M j, Y H:i', strtotime($ph['purchaseDate'])); ?></td>
                                        <td><code
                                                style="color:#ff7a00;"><?php echo htmlspecialchars($ph['transactionId']); ?></code>
                                        </td>
                                        <td><?php echo htmlspecialchars($ph['username']); ?></td>
                                        <td><?php echo htmlspecialchars($ph['product_name']); ?></td>
                                        <td>$<?php echo number_format($ph['amount'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <h3 style="color:#fff; margin-bottom:20px; font-family:'Orbitron', sans-serif;">Add New Product</h3>
            <form method="POST" id="addForm" enctype="multipart/form-data" onsubmit="return validateAddForm()">
                <input type="hidden" name="action" value="create">

                <div class="form-group">
                    <label>Product Name <span style="color:#ff4757">*</span></label>
                    <input type="text" name="name" id="add_name" required>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <input type="text" name="description" id="add_description">
                </div>

                <div class="form-group">
                    <label>Product Image</label>
                    <input type="file" name="image" id="add_image" accept="image/*">
                </div>

                <div class="form-group">
                    <label>Category <span style="color:#ff4757">*</span></label>
                    <select name="category" id="add_category" required>
                        <option value="">Select Category</option>
                        <option value="Hardware">Hardware</option>
                        <option value="Accessory">Accessory</option>
                        <option value="Merchandise">Merchandise</option>
                        <option value="Peripherals">Peripherals</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Price ($) <span style="color:#ff4757">*</span></label>
                    <input type="number" step="0.01" name="price" id="add_price" required>
                </div>

                <div class="form-group">
                    <label>Stock <span style="color:#ff4757">*</span></label>
                    <input type="number" name="stock" id="add_stock" required>
                </div>

                <button type="submit" class="btn-confirm">Add Product</button>
                <button type="button" class="btn-cancel" onclick="closeModal('addModal')">Cancel</button>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <h3 style="color:#fff; margin-bottom:20px; font-family:'Orbitron', sans-serif;">Edit Product</h3>
            <form method="POST" id="editForm" enctype="multipart/form-data" onsubmit="return validateEditForm()">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_id">

                <div class="form-group">
                    <label>Product Name <span style="color:#ff4757">*</span></label>
                    <input type="text" name="name" id="edit_name">
                </div>

                <div class="form-group">
                    <label>Product Image</label>
                    <input type="file" name="image" id="edit_image" accept="image/*">
                    <small style="color:#888;">Leave empty to keep current image</small>
                </div>

                <div class="form-group">
                    <label>Category <span style="color:#ff4757">*</span></label>
                    <select name="category" id="edit_category">
                        <option value="">Select Category</option>
                        <option value="Hardware">Hardware</option>
                        <option value="Accessory">Accessory</option>
                        <option value="Merchandise">Merchandise</option>
                        <option value="Peripherals">Peripherals</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Price ($) <span style="color:#ff4757">*</span></label>
                    <input type="number" step="0.01" name="price" id="edit_price">
                </div>

                <div class="form-group">
                    <label>Stock <span style="color:#ff4757">*</span></label>
                    <input type="number" name="stock" id="edit_stock">
                </div>

                <button type="submit" class="btn-confirm">Save Changes</button>
                <button type="button" class="btn-cancel" onclick="closeModal('editModal')">Cancel</button>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content" style="text-align: center;">
            <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: #ff4757; margin-bottom: 20px;"></i>
            <h3 style="color:#fff; margin-bottom:10px;">Are you sure?</h3>
            <p style="color:#aaa; margin-bottom: 25px;">Do you really want to delete this product? This process cannot
                be undone.</p>

            <form method="POST">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="delete_id">

                <button type="submit" class="btn-confirm btn-danger">Yes, Delete It</button>
                <button type="button" class="btn-cancel" onclick="closeModal('deleteModal')">Cancel</button>
            </form>
        </div>
    </div>

    <!-- Save Confirmation Modal -->
    <div id="saveModal" class="modal">
        <div class="modal-content" style="text-align: center;">
            <i class="fas fa-save" style="font-size: 3rem; color: #2ed573; margin-bottom: 20px;"></i>
            <h3 style="color:#fff; margin-bottom:10px;">Save Changes?</h3>
            <p style="color:#aaa; margin-bottom: 25px;">Are you sure you want to update this product?</p>

            <button type="button" class="btn-confirm" style="background:#2ed573;" onclick="submitEditForm()">Yes,
                Save</button>
            <button type="button" class="btn-cancel" onclick="closeModal('saveModal')">Cancel</button>
        </div>
    </div>

    <script>
        // Admin Dropdown Logic
        const adminDropdown = document.getElementById('adminDropdown');
        if (adminDropdown) {
            const adminUser = adminDropdown.querySelector('.admin-user');
            const dropdownMenu = adminDropdown.querySelector('.admin-dropdown-menu');

            if (adminUser && dropdownMenu) {
                adminUser.addEventListener('click', (e) => {
                    e.stopPropagation();
                    dropdownMenu.classList.toggle('show');
                });

                document.addEventListener('click', (e) => {
                    if (!adminDropdown.contains(e.target)) {
                        dropdownMenu.classList.remove('show');
                    }
                });
            }
        }

        // Modal functions
        function openAddModal() {
            document.getElementById('addModal').style.display = 'flex';
        }

        function validateAddForm() {
            // Re-using same logic concept, or simple check
            const name = document.getElementById('add_name').value;
            const price = document.getElementById('add_price').value;
            const stock = document.getElementById('add_stock').value;
            if (!name || price < 0 || stock < 0) {
                showToast('Please fill all required fields correctly.', 'error');
                return false;
            }
            return true;
        }

        function openEditModal(product) {
            document.getElementById('edit_id').value = product.id;
            document.getElementById('edit_name').value = product.name;
            document.getElementById('edit_price').value = product.price;
            document.getElementById('edit_stock').value = product.stock;
            document.getElementById('edit_category').value = product.category;

            // Clear Validation Styles
            document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));

            document.getElementById('editModal').style.display = 'flex';
        }

        function openDeleteModal(id) {
            document.getElementById('delete_id').value = id;
            document.getElementById('deleteModal').style.display = 'flex';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Close on outside click
        window.onclick = function (event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }

        // Form Submission Logic
        const editForm = document.getElementById('editForm');

        editForm.addEventListener('submit', function (e) {
            e.preventDefault(); // Stop default submission

            if (validateEditForm()) {
                // Open Save Modal instead of submitting immediately
                document.getElementById('saveModal').style.display = 'flex';
            }
        });

        function submitEditForm() {
            // Actual submission
            editForm.submit();
        }

        // JS Validation
        function validateEditForm() {
            let isValid = true;

            // Get Elements
            const nameEl = document.getElementById('edit_name');
            const categoryEl = document.getElementById('edit_category');
            const priceEl = document.getElementById('edit_price');
            const stockEl = document.getElementById('edit_stock');

            // Helper to set error
            const setError = (element, isError) => {
                if (isError) {
                    element.classList.add('is-invalid');
                } else {
                    element.classList.remove('is-invalid');
                }
            };

            // Validate Name
            if (nameEl.value.trim() === '') {
                setError(nameEl, true);
                isValid = false;
            } else {
                setError(nameEl, false);
            }

            // Validate Category
            if (categoryEl.value === '') {
                setError(categoryEl, true);
                isValid = false;
            } else {
                setError(categoryEl, false);
            }

            // Validate Price
            if (priceEl.value === '' || parseFloat(priceEl.value) < 0) {
                setError(priceEl, true);
                isValid = false;
            } else {
                setError(priceEl, false);
            }

            // Validate Stock
            if (stockEl.value === '' || parseInt(stockEl.value) < 0) {
                setError(stockEl, true);
                isValid = false;
            } else {
                setError(stockEl, false);
            }

            if (!isValid) {
                showToast("Please fix the highlighted errors.", "error");
            }

            return isValid;
        }

        // Toast Notification System
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            const msgSpan = document.getElementById('toastMessage');
            const icon = toast.querySelector('i');

            msgSpan.textContent = message;

            // Reset classes
            toast.className = 'toast';
            if (type === 'success') {
                toast.classList.add('success');
                icon.className = 'fas fa-check-circle';
            } else {
                toast.classList.add('error');
                icon.className = 'fas fa-times-circle';
            }

            // Show
            void toast.offsetWidth; // Trigger reflow
            toast.classList.add('show');

            // Hide after 5 seconds
            setTimeout(() => {
                toast.classList.remove('show');
            }, 5000);
        }

        // Trigger toast from PHP
        <?php if ($message): ?>
            showToast("<?php echo addslashes($message); ?>", "success");
        <?php endif; ?>

        <?php if ($error): ?>
            showToast("<?php echo addslashes($error); ?>", "error");
        <?php endif; ?>
    </script>
</body>

</html>