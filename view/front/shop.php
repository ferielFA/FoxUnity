<?php
ob_start();
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

// Handle AJAX Request
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    if (ob_get_length()) ob_clean(); // Clear any previous output safely
    header('Content-Type: application/json');
    
    $productsHtml = '';
    if (empty($products)) {
        $productsHtml = '
            <div class="no-products">
                <i class="fas fa-box-open"></i>
                <h3 style="color: #666; margin-bottom: 10px;">No products found</h3>
                <p>Try adjusting your filters or search terms</p>
                ' . ($search || $categoryFilter != 'all' ? '<a href="shop.php" class="filter-btn" style="display: inline-block; margin-top: 20px;"><i class="fas fa-refresh"></i> View All Products</a>' : '') . '
            </div>';
    } else {
        foreach ($products as $product) {
            $desc = $product->getDescription();
            $shortDesc = htmlspecialchars(substr($desc, 0, 100)) . (strlen($desc) > 100 ? '...' : '');
            $img = !empty($product->getImage()) ? '../' . htmlspecialchars($product->getImage()) : '../images/placeholder-product.png';
            $name = htmlspecialchars($product->getName());
            $safeName = htmlspecialchars(addslashes($product->getName()));
            $safeDesc = htmlspecialchars(addslashes($desc));
            $price = number_format($product->getPrice(), 2);
            $stock = $product->getStock();
            $stockClass = $stock < 5 ? 'low-stock' : '';
            $stockText = $stock == 0 ? 'Out of stock' : ($stock < 5 ? "Only $stock left!" : "$stock in stock");
            
            $productsHtml .= '
                <div class="product-card">
                    <img src="' . $img . '" alt="' . $name . '" class="product-image" onerror="this.src=\'https://via.placeholder.com/300x300?text=No+Image\'">
                    <div class="product-content">
                        <span class="product-category">' . htmlspecialchars($product->getCategory()) . '</span>
                        <h3 class="product-title">' . $name . '</h3>
                        <p class="product-description">' . $shortDesc . '</p>
                        ' . (!empty($desc) && strlen($desc) > 100 ? '<button class="btn-description" onclick="openDescriptionModal(\'' . $safeDesc . '\', \'' . $safeName . '\')"><i class="fas fa-info-circle"></i> View Full Description</button>' : '') . '
                        <div class="product-footer">
                            <div>
                                <div class="product-price">$' . $price . '</div>
                                <div class="product-stock ' . $stockClass . '">
                                    <i class="fas fa-box"></i> ' . $stockText . '
                                </div>
                            </div>
                            ' . ($stock > 0 ? '<button class="add-to-cart-btn" onclick="openQuantityModal(' . $product->getId() . ', \'' . $safeName . '\', ' . $product->getPrice() . ', ' . $stock . ')"><i class="fas fa-cart-plus"></i> Add</button>' : '<button class="add-to-cart-btn" disabled><i class="fas fa-ban"></i> Out of Stock</button>') . '
                        </div>
                    </div>
                </div>';
        }
    }

    $paginationHtml = '';
    if ($totalPages > 1) {
        if ($page > 1) {
            $paginationHtml .= '<a href="#" data-page="' . ($page - 1) . '" class="page-link nav-btn"><i class="fas fa-chevron-left"></i> Previous</a>';
        }

        $startPage = max(1, $page - 2);
        $endPage = min($totalPages, $page + 2);

        if ($startPage > 1) {
            $paginationHtml .= '<a href="#" data-page="1" class="page-link">1</a>';
            if ($startPage > 2) $paginationHtml .= '<span style="color: #666;">...</span>';
        }

        for ($i = $startPage; $i <= $endPage; $i++) {
            $activeClass = $i == $page ? 'active' : '';
            $paginationHtml .= '<a href="#" data-page="' . $i . '" class="page-link ' . $activeClass . '">' . $i . '</a>';
        }

        if ($endPage < $totalPages) {
            if ($endPage < $totalPages - 1) $paginationHtml .= '<span style="color: #666;">...</span>';
            $paginationHtml .= '<a href="#" data-page="' . $totalPages . '" class="page-link">' . $totalPages . '</a>';
        }

        if ($page < $totalPages) {
            $paginationHtml .= '<a href="#" data-page="' . ($page + 1) . '" class="page-link nav-btn">Next <i class="fas fa-chevron-right"></i></a>';
        }
    }

    $activeFiltersHtml = '';
    if ($search || $categoryFilter != 'all') {
        $activeFiltersHtml = '
            <small style="color: #aaa;">Active filters:</small>
            ' . ($search ? '<span class="filter-badge">🔍 Search: "' . htmlspecialchars($search) . '"</span>' : '') . '
            ' . ($categoryFilter != 'all' ? '<span class="filter-badge">📁 ' . htmlspecialchars($categoryFilter) . '</span>' : '') . '
            <small style="color: #666; margin-left: 10px;">(' . $totalProducts . ' result' . ($totalProducts != 1 ? 's' : '') . ')</small>';
    }

    echo json_encode([
        'products' => $productsHtml,
        'pagination' => $paginationHtml,
        'totalProducts' => $totalProducts,
        'activeFilters' => $activeFiltersHtml,
        'totalText' => $totalProducts > 0 ? '<span style="color: #ff7a00; font-weight: 600;"> • ' . $totalProducts . ' products available</span>' : '',
        'showClear' => ($search || $categoryFilter != 'all' || $sortParam != 'created_at')
    ]);
    exit();
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
            gap: 15px;
        }

        .product-price {
            color: #2ed573;
            font-family: 'Orbitron', sans-serif;
            font-size: 1.6rem;
            font-weight: 700;
            flex-shrink: 0;
        }

        .product-stock {
            color: #aaa;
            font-size: 0.85rem;
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .product-stock i {
            color: #ff7a00;
            font-size: 0.9rem;
        }

        .product-stock.low-stock {
            color: #ff4444;
        }

        .product-stock.low-stock i {
            color: #ff4444;
        }

        .add-to-cart-btn {
            background: transparent;
            color: #ff7a00;
            border: 2px solid #ff7a00;
            padding: 8px 16px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s ease;
            text-transform: none;
            letter-spacing: 0.3px;
        }

        .add-to-cart-btn:hover {
            background: rgba(255, 122, 0, 0.1);
            border-color: #ff9933;
            color: #ff9933;
            transform: translateY(-2px);
        }

        .add-to-cart-btn:active {
            transform: translateY(0);
        }

        .add-to-cart-btn i {
            font-size: 14px;
        }

        .add-to-cart-btn:disabled {
            background: transparent;
            border-color: #555;
            color: #555;
            cursor: not-allowed;
            opacity: 0.5;
        }

        .add-to-cart-btn:disabled:hover {
            transform: none;
        }

        /* Quantity Modal Styles */
        .quantity-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            z-index: 10000;
            justify-content: center;
            align-items: center;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        .quantity-modal.show {
            display: flex;
        }

        .quantity-modal-content {
            background: linear-gradient(145deg, #1a1a1a 0%, #111 100%);
            padding: 40px;
            border-radius: 20px;
            border: 2px solid #ff7a00;
            max-width: 500px;
            width: 90%;
            position: relative;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.8);
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .quantity-modal-content.error {
            border-color: #ff4444;
            animation: shake 0.5s ease;
        }

        @keyframes shake {
            0%, 100% {
                transform: translateX(0);
            }
            25% {
                transform: translateX(-10px);
            }
            75% {
                transform: translateX(10px);
            }
        }

        .modal-close {
            position: absolute;
            top: 15px;
            right: 20px;
            color: #ff7a00;
            font-size: 28px;
            cursor: pointer;
            transition: all 0.3s;
            line-height: 1;
        }

        .modal-close:hover {
            transform: scale(1.2) rotate(90deg);
            color: #ff4444;
        }

        .modal-product-info {
            text-align: center;
            margin-bottom: 30px;
        }

        .modal-product-name {
            color: #fff;
            font-family: 'Orbitron', sans-serif;
            font-size: 1.8rem;
            margin-bottom: 10px;
        }

        .modal-product-price {
            color: #2ed573;
            font-size: 1.4rem;
            font-weight: 700;
        }

        .modal-stock-info {
            text-align: center;
            margin: 15px 0;
            padding: 10px;
            background: rgba(255, 122, 0, 0.1);
            border-radius: 10px;
            color: #aaa;
            font-size: 0.95rem;
        }

        .modal-stock-info.error {
            background: rgba(255, 68, 68, 0.2);
            color: #ff4444;
            border: 1px solid rgba(255, 68, 68, 0.5);
        }

        .quantity-selector {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 20px;
            margin: 30px 0;
        }

        .quantity-btn {
            background: rgba(255, 122, 0, 0.1);
            border: 2px solid #ff7a00;
            color: #ff7a00;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            font-size: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .quantity-btn:hover:not(:disabled) {
            background: #ff7a00;
            color: #fff;
            transform: scale(1.1);
        }

        .quantity-btn:disabled {
            opacity: 0.3;
            cursor: not-allowed;
        }

        .quantity-display {
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 122, 0, 0.3);
            color: #fff;
            font-size: 24px;
            font-weight: 700;
            min-width: 80px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Orbitron', sans-serif;
        }

        .modal-total {
            text-align: center;
            margin: 20px 0;
            padding: 15px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 10px;
            border: 1px solid rgba(255, 122, 0, 0.2);
        }

        .modal-total-label {
            color: #aaa;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }

        .modal-total-price {
            color: #2ed573;
            font-size: 2rem;
            font-weight: 700;
            font-family: 'Orbitron', sans-serif;
        }

        .modal-actions {
            display: flex;
            gap: 15px;
            margin-top: 25px;
        }

        .modal-btn {
            flex: 1;
            padding: 15px;
            border-radius: 10px;
            border: none;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .modal-btn-confirm {
            background: linear-gradient(135deg, #ff7a00, #ff4f00);
            color: #fff;
        }

        .modal-btn-confirm:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(255, 122, 0, 0.4);
        }

        .modal-btn-confirm:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .modal-btn-cancel {
            background: transparent;
            border: 2px solid #555;
            color: #aaa;
        }

        .modal-btn-cancel:hover {
            border-color: #ff7a00;
            color: #ff7a00;
        }

        .error-message {
            color: #ff4444;
            text-align: center;
            margin-top: 15px;
            font-weight: 600;
            animation: pulse 1s infinite;
        }

        .success-message {
            color: #2ed573;
            text-align: center;
            margin-top: 15px;
            font-weight: 600;
            padding: 15px;
            background: rgba(46, 213, 115, 0.1);
            border-radius: 10px;
            border: 2px solid #2ed573;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            animation: successPulse 2s ease;
        }

        .success-message i {
            font-size: 1.5rem;
        }

        @keyframes successPulse {
            0% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(46, 213, 115, 0.7);
            }
            50% {
                transform: scale(1);
                box-shadow: 0 0 20px 10px rgba(46, 213, 115, 0);
            }
            100% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(46, 213, 115, 0);
            }
        }

        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.6;
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

        @media (max-width: 768px) {
            .product-footer {
                flex-direction: column;
                align-items: stretch;
                gap: 12px;
            }
            
            .add-to-cart-btn {
                width: 100%;
                justify-content: center;
                padding: 10px 20px;
            }
            
            .product-price {
                text-align: center;
                font-size: 1.8rem;
            }

            .quantity-modal-content {
                padding: 30px 20px;
            }
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
                <p style="color: #aaa; font-size: 1.1rem; margin-bottom: 30px;" id="productStats">
                    Level up your setup with premium equipment
                    <?php if ($totalProducts > 0): ?>
                        <span style="color: #ff7a00; font-weight: 600;"> • <?php echo $totalProducts; ?> products
                            available</span>
                    <?php endif; ?>
                </p>

                <form method="GET" class="shop-filters" id="searchForm">
                    <input type="text" name="search" id="searchInput" placeholder="🔍 Search products..."
                        value="<?php echo htmlspecialchars($search); ?>" class="filter-input">

                    <select name="category" id="categorySelect" class="filter-select">
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

                    <select name="sort" id="sortSelect" class="filter-select">
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

                    <div id="clearBtnContainer" style="display: <?php echo ($search || $categoryFilter != 'all' || $sortParam != 'created_at') ? 'inline-block' : 'none'; ?>;">
                        <a href="shop.php" class="clear-btn">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    </div>
                </form>

                <?php if ($search || $categoryFilter != 'all'): ?>
                    <div class="active-filters" id="activeFilters">
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
                <?php else: ?>
                    <div class="active-filters" id="activeFilters" style="display: none;"></div>
                <?php endif; ?>
            </div>

            <div class="products-grid" id="productsGrid">
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
                                    <div>
                                        <div class="product-price">$<?php echo number_format($product->getPrice(), 2); ?></div>
                                        <div class="product-stock <?php echo $product->getStock() < 5 ? 'low-stock' : ''; ?>">
                                            <i class="fas fa-box"></i>
                                            <?php 
                                            if ($product->getStock() == 0) {
                                                echo 'Out of stock';
                                            } elseif ($product->getStock() < 5) {
                                                echo 'Only ' . $product->getStock() . ' left!';
                                            } else {
                                                echo $product->getStock() . ' in stock';
                                            }
                                            ?>
                                        </div>
                                    </div>
                                    <?php if ($product->getStock() > 0): ?>
                                        <button class="add-to-cart-btn" 
                                            onclick="openQuantityModal(<?php echo $product->getId(); ?>, '<?php echo htmlspecialchars(addslashes($product->getName())); ?>', <?php echo $product->getPrice(); ?>, <?php echo $product->getStock(); ?>)">
                                            <i class="fas fa-cart-plus"></i>
                                            Add
                                        </button>
                                    <?php else: ?>
                                        <button class="add-to-cart-btn" disabled>
                                            <i class="fas fa-ban"></i>
                                            Out of Stock
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div id="paginationContainer">
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="#" data-page="<?php echo $page - 1; ?>"
                            class="page-link nav-btn">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                    <?php endif; ?>

                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);

                    if ($startPage > 1): ?>
                        <a href="#" data-page="1"
                            class="page-link">1</a>
                        <?php if ($startPage > 2): ?>
                            <span style="color: #666;">...</span>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <a href="#" data-page="<?php echo $i; ?>"
                            class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($endPage < $totalPages): ?>
                        <?php if ($endPage < $totalPages - 1): ?>
                            <span style="color: #666;">...</span>
                        <?php endif; ?>
                        <a href="#" data-page="<?php echo $totalPages; ?>"
                            class="page-link"><?php echo $totalPages; ?></a>
                    <?php endif; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="#" data-page="<?php echo $page + 1; ?>"
                            class="page-link nav-btn">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            </div>

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

            <!-- Quantity Modal -->
            <div id="quantityModal" class="quantity-modal">
                <div class="quantity-modal-content" id="quantityModalContent">
                    <span class="modal-close" onclick="closeQuantityModal()">&times;</span>
                    
                    <div class="modal-product-info">
                        <h3 class="modal-product-name" id="modalProductName"></h3>
                        <div class="modal-product-price" id="modalProductPrice"></div>
                    </div>

                    <div class="modal-stock-info" id="modalStockInfo">
                        Available stock: <span id="modalAvailableStock"></span>
                    </div>

                    <div class="quantity-selector">
                        <button class="quantity-btn" id="decreaseBtn" onclick="decreaseQuantity()">
                            <i class="fas fa-minus"></i>
                        </button>
                        <div class="quantity-display" id="quantityDisplay">1</div>
                        <button class="quantity-btn" id="increaseBtn" onclick="increaseQuantity()">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>

                    <div class="modal-total">
                        <div class="modal-total-label">Total Price</div>
                        <div class="modal-total-price" id="modalTotalPrice">$0.00</div>
                    </div>

                    <div class="error-message" id="errorMessage" style="display: none;"></div>
                    <div class="success-message" id="successMessage" style="display: none;">
                        <i class="fas fa-check-circle"></i>
                        <span>Item added to cart successfully!</span>
                    </div>

                    <div class="modal-actions">
                        <button class="modal-btn modal-btn-cancel" onclick="closeQuantityModal()">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button class="modal-btn modal-btn-confirm" id="confirmAddBtn" onclick="confirmAddToCart()">
                            <i class="fas fa-check"></i> Add to Cart
                        </button>
                    </div>
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

            // Update cart count on load
            updateCartCount();

            // AJAX Search and Filter Logic
            const searchInput = document.getElementById('searchInput');
            const categorySelect = document.getElementById('categorySelect');
            const sortSelect = document.getElementById('sortSelect');
            const productsGrid = document.getElementById('productsGrid');
            const paginationContainer = document.getElementById('paginationContainer');
            const activeFilters = document.getElementById('activeFilters');
            const searchForm = document.getElementById('searchForm');
            const productStats = document.getElementById('productStats');
            const clearBtnContainer = document.getElementById('clearBtnContainer');

            let searchTimeout;

            function updateResults(page = 1) {
                const search = searchInput.value;
                const category = categorySelect.value;
                const sort = sortSelect.value;

                // Update URL without reload
                const params = new URLSearchParams({
                    search: search,
                    category: category,
                    sort: sort,
                    page: page
                });
                const newUrl = `${window.location.pathname}?${params.toString()}`;
                window.history.pushState({ path: newUrl }, '', newUrl);

                // Add loading state
                productsGrid.style.opacity = '0.5';
                productsGrid.style.pointerEvents = 'none';

                fetch(`${newUrl}&ajax=1`)
                    .then(response => response.json())
                    .then(data => {
                        productsGrid.innerHTML = data.products;
                        paginationContainer.innerHTML = data.pagination;
                        activeFilters.innerHTML = data.activeFilters;
                        activeFilters.style.display = data.activeFilters ? 'block' : 'none';
                        
                        // Update clear button visibility
                        if (clearBtnContainer) {
                            clearBtnContainer.style.display = data.showClear ? 'inline-block' : 'none';
                        }
                        
                        // Update stats paragraph
                        if (productStats) {
                            const statsText = data.totalProducts > 0 
                                ? `Level up your setup with premium equipment ${data.totalText}`
                                : 'Level up your setup with premium equipment';
                            productStats.innerHTML = statsText;
                        }

                        productsGrid.style.opacity = '1';
                        productsGrid.style.pointerEvents = 'auto';
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    })
                    .catch(error => {
                        console.error('Error fetching search results:', error);
                        productsGrid.style.opacity = '1';
                        productsGrid.style.pointerEvents = 'auto';
                    });
            }

            // Debounced Search Input
            searchInput.addEventListener('input', () => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => updateResults(1), 300);
            });

            // Select changes
            categorySelect.addEventListener('change', () => updateResults(1));
            sortSelect.addEventListener('change', () => updateResults(1));

            // Prevent form submit
            searchForm.addEventListener('submit', (e) => {
                e.preventDefault();
                updateResults(1);
            });

            // Pagination delegation
            paginationContainer.addEventListener('click', (e) => {
                const pageLink = e.target.closest('.page-link');
                if (pageLink && pageLink.hasAttribute('data-page')) {
                    e.preventDefault();
                    const page = pageLink.getAttribute('data-page');
                    updateResults(page);
                }
            });

            // Handle browser back/forward buttons
            window.addEventListener('popstate', () => {
                const urlParams = new URLSearchParams(window.location.search);
                searchInput.value = urlParams.get('search') || '';
                categorySelect.value = urlParams.get('category') || 'all';
                sortSelect.value = urlParams.get('sort') || 'created_at';
                updateResults(urlParams.get('page') || 1);
            });
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

        // Quantity Modal Variables
        let currentProduct = {
            id: null,
            name: '',
            price: 0,
            stock: 0
        };
        let selectedQuantity = 1;

        function openQuantityModal(productId, productName, productPrice, productStock) {
            currentProduct = {
                id: productId,
                name: productName,
                price: parseFloat(productPrice),
                stock: parseInt(productStock)
            };
            selectedQuantity = 1;

            document.getElementById('modalProductName').textContent = productName;
            document.getElementById('modalProductPrice').textContent = '$' + productPrice;
            document.getElementById('modalAvailableStock').textContent = productStock;
            document.getElementById('quantityDisplay').textContent = selectedQuantity;
            updateModalTotal();
            resetModalError();

            const modal = document.getElementById('quantityModal');
            modal.classList.add('show');
        }

        function closeQuantityModal() {
            const modal = document.getElementById('quantityModal');
            modal.classList.remove('show');
            resetModalError();
        }

        function decreaseQuantity() {
            if (selectedQuantity > 1) {
                selectedQuantity--;
                document.getElementById('quantityDisplay').textContent = selectedQuantity;
                updateModalTotal();
                resetModalError();
            }
        }

        function increaseQuantity() {
            if (selectedQuantity < currentProduct.stock) {
                selectedQuantity++;
                document.getElementById('quantityDisplay').textContent = selectedQuantity;
                updateModalTotal();
                resetModalError();
            } else {
                showModalError('Maximum stock reached!');
            }
        }

        function updateModalTotal() {
            const total = selectedQuantity * currentProduct.price;
            document.getElementById('modalTotalPrice').textContent = '$' + total.toFixed(2);
            
            // Update button states
            document.getElementById('decreaseBtn').disabled = selectedQuantity <= 1;
            document.getElementById('increaseBtn').disabled = selectedQuantity >= currentProduct.stock;
        }

        function showModalError(message) {
            const modalContent = document.getElementById('quantityModalContent');
            const stockInfo = document.getElementById('modalStockInfo');
            const errorMsg = document.getElementById('errorMessage');
            
            modalContent.classList.add('error');
            stockInfo.classList.add('error');
            errorMsg.textContent = message;
            errorMsg.style.display = 'block';
            
            // Vibrate if supported
            if (navigator.vibrate) {
                navigator.vibrate(200);
            }
        }

        function resetModalError() {
            const modalContent = document.getElementById('quantityModalContent');
            const stockInfo = document.getElementById('modalStockInfo');
            const errorMsg = document.getElementById('errorMessage');
            const successMsg = document.getElementById('successMessage');
            const confirmBtn = document.getElementById('confirmAddBtn');
            
            modalContent.classList.remove('error');
            stockInfo.classList.remove('error');
            errorMsg.style.display = 'none';
            successMsg.style.display = 'none';
            confirmBtn.disabled = false;
            
            // Reset modal colors
            modalContent.style.borderColor = '#ff7a00';
            modalContent.style.boxShadow = '0 20px 60px rgba(0, 0, 0, 0.8)';
        }

        function confirmAddToCart() {
            if (selectedQuantity > currentProduct.stock) {
                showModalError('Quantity exceeds available stock!');
                return;
            }

            let cart = JSON.parse(localStorage.getItem('cart')) || [];
            
            // Check if product already exists in cart
            const existingIndex = cart.findIndex(item => 
                item.id === currentProduct.id && item.type === 'product'
            );

            if (existingIndex !== -1) {
                // Update quantity
                cart[existingIndex].quantity = (cart[existingIndex].quantity || 1) + selectedQuantity;
            } else {
                // Add new item
                cart.push({
                    id: currentProduct.id,
                    type: 'product',
                    quantity: selectedQuantity
                });
            }

            localStorage.setItem('cart', JSON.stringify(cart));
            updateCartCount();
            
            // Show success message in modal
            showModalSuccess();
            
            // Close modal after 2 seconds (removed redirect)
            setTimeout(() => {
                closeQuantityModal();
            }, 2000);
        }

        function showModalSuccess() {
            const successMsg = document.getElementById('successMessage');
            const errorMsg = document.getElementById('errorMessage');
            const confirmBtn = document.getElementById('confirmAddBtn');
            const modalContent = document.getElementById('quantityModalContent');
            
            // Hide error and show success
            errorMsg.style.display = 'none';
            successMsg.style.display = 'flex';
            confirmBtn.disabled = true;
            modalContent.classList.remove('error');
            
            // Add success border to modal
            modalContent.style.borderColor = '#2ed573';
            modalContent.style.boxShadow = '0 20px 60px rgba(46, 213, 115, 0.3)';
        }

        function updateCartCount() {
            const cart = JSON.parse(localStorage.getItem('cart')) || [];
            let totalQuantity = 0;
            
            cart.forEach(item => {
                totalQuantity += item.quantity || 1;
            });
            
            const cartCount = document.querySelector('.cart-count');
            if (cartCount) {
                cartCount.textContent = totalQuantity;
            }
        }

        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeQuantityModal();
                closeDescriptionModal();
            }
        });

        // Close modal when clicking outside
        window.onclick = function(event) {
            const descModal = document.getElementById('descriptionModal');
            const qtyModal = document.getElementById('quantityModal');
            
            if (event.target === descModal) {
                closeDescriptionModal();
            }
            if (event.target === qtyModal) {
                closeQuantityModal();
            }
        }
    </script>
</body>

</html>