<?php

declare(strict_types=1);

require_once __DIR__ . '/../../model/config.php';
require_once __DIR__ . '/../../controller/TradingController.php';

// Check if user is logged in
if (!isLoggedIn()) {
    // Redirect to login page with return URL
    $returnUrl = urlencode($_SERVER['REQUEST_URI']);
    header('Location: Login.php?redirect=' . $returnUrl);
    exit();
}

// Get current user from session
$currentUsername = getCurrentUsername();
$currentUserId = getCurrentUserId();

// If no username in session, redirect to login
if (empty($currentUsername) || empty($currentUserId)) {
    header('Location: Login.php?error=' . urlencode('Session expired. Please login again.'));
    exit();
}

// Verify user is linked to database
require_once __DIR__ . '/../../model/User.php';
$userFromDb = User::getByUsername($currentUsername);
if (!$userFromDb || $userFromDb->getStatus() !== 'active' || $userFromDb->getId() != $currentUserId) {
    // Session user doesn't match database - clear session and redirect
    session_unset();
    session_destroy();
    header('Location: Login.php?error=' . urlencode('User account verification failed. Please login again.'));
    exit();
}

// Get User object for dropdown display (like index.php)
require_once __DIR__ . '/../../controller/UserController.php';
$currentUserObj = UserController::getCurrentUser();

// Get user image - NO DEFAULT IMAGE, just check if exists
$userImage = null;
if ($currentUserObj && $currentUserObj->getImage()) {
    $userImage = '../../view/' . $currentUserObj->getImage();
}

try {
    $controller = new TradingController($currentUsername);
} catch (Exception $e) {
    // Handle error - redirect to login
    header('Location: Login.php?error=' . urlencode($e->getMessage()));
    exit();
}

// Handle POST requests (clear history)
$jsonResponse = $controller->handlePost();
if ($jsonResponse !== null) {
    header('Content-Type: application/json');
    echo json_encode($jsonResponse);
    exit();
}

$viewData = $controller->getViewData();
$tradeHistoryFull = $viewData['tradeHistory'];
$mySkins = $viewData['mySkins'];
$currentUser = $viewData['currentUser'];

// Split history into standard actions and refused negotiations
$standardHistory = [];
$refusedHistory = [];

foreach ($tradeHistoryFull as $item) {
    if ($item['action'] === 'negotiation_refused') {
        $refusedHistory[] = $item;
    } else {
        $standardHistory[] = $item;
    }
}
?>
<!DOCTYPE html>
<!-- ... existing head ... -->
<!-- I'll skip re-outputting head to match replace_file_content constraints, I just need to replace the logic part -->
<!-- Wait, replace_file_content replaces specific lines. I should do the split in PHP block at top, then modify the view -->

<!-- Splitting the tool call into 2 chunks because lines are far apart -->
<?php
// ... existing PHP code ...
$tradeHistory = $viewData['tradeHistory'];
// REPLACING WITH SPLIT LOGIC
$tradeHistoryFull = $viewData['tradeHistory'];
$standardHistory = [];
$refusedHistory = [];
foreach ($tradeHistoryFull as $item) {
    if ($item['action'] === 'negotiation_refused') {
        $refusedHistory[] = $item;
    } else {
        $standardHistory[] = $item;
    }
}
$mySkins = $viewData['mySkins'];
$currentUser = $viewData['currentUser'];
?>
<!-- ... -->

<!-- Finding the loop for standard history -->
<!-- I will replace the main table rendering part -->

<!-- Finding the place to insert Refused History -->
<!-- I will append it after the main section -->

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>FoxUnity - Trade History</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="styletrade.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Orbitron:wght@700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"/>
  <style>
    .flash { position:fixed; top:18px; right:18px; padding:10px 14px; border-radius:8px; z-index:10000; color:#fff; font-weight:700; }
    .flash.success { background: #2ed573; }
    .flash.error { background: #ff4757; }
    
    /* MODAL FORM STYLES (Copied from trading.php) */
    .trade-modal-content input[type="text"], 
    .trade-modal-content input[type="number"], 
    .trade-modal-content textarea, 
    .trade-modal-content select {
      width:100%; padding:10px; margin:8px 0; border-radius:8px; border:1px solid #333; background:#1a1a1a; color:#fff;
    }
    
    /* Trade History Styles */
    .trade-history-section {
        margin: 40px auto;
        padding: 20px;
        background: rgba(255,122,0,0.1);
        border-radius: 12px;
        border: 1px solid #ff7a00;
        max-width: 1200px;
    }
    .history-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
        background: rgba(0, 0, 0, 0.3);
        border-radius: 8px;
        overflow: hidden;
    }
    .history-table th, .history-table td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid #333;
    }
    .history-table th {
        background: rgba(255,122,0,0.2);
        color: #fff;
        font-weight: 600;
    }
    .history-table tr:last-child td {
        border-bottom: none;
    }
    .history-table tr:hover {
        background: rgba(255, 255, 255, 0.05);
    }
    .action-badge {
        padding: 4px 8px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
    }
    .action-created {
        background: rgba(46, 213, 115, 0.2);
        color: #2ed573;
    }
    .action-updated {
        background: rgba(255, 165, 0, 0.2);
        color: #ffa500;
    }
    .action-deleted {
        background: rgba(255, 71, 87, 0.2);
        color: #ff4757;
    }
    .action-bought {
        background: rgba(155, 89, 182, 0.1);
        color: #9b59b6;
        border: 1px solid rgba(155, 89, 182, 0.2);
    }
    .badge-bought {
        background: rgba(155, 89, 182, 0.1);
        color: #9b59b6;
        border: 1px solid rgba(155, 89, 182, 0.2);
    }
    .no-history {
        text-align: center;
        padding: 40px;
        color: #888;
    }
    .history-filter {
        display: flex;
        gap: 10px;
        margin-bottom: 15px;
        flex-wrap: wrap;
        align-items: center;
    }
    .history-filter button {
        background: #333;
        color: #fff;
        border: none;
        padding: 8px 16px;
        border-radius: 6px;
        cursor: pointer;
        transition: background 0.3s;
    }
    .history-filter button.active {
        background: #ff7a00;
        color: #000;
        font-weight: 600;
    }
    .history-filter button:hover:not(.active) {
        background: #555;
    }
    .clear-history-btn {
        background: #ff4757 !important;
        color: white !important;
        border: none !important;
        padding: 8px 16px !important;
        border-radius: 6px !important;
        cursor: pointer !important;
        margin-left: auto !important;
        transition: background 0.3s !important;
    }
    .clear-history-btn:hover {
        background: #ff3742 !important;
    }
    
    /* My Trades Section Styles */
    .my-trades-section {
        margin: 40px auto;
        padding: 20px;
        background: rgba(255,122,0,0.1);
        border-radius: 12px;
        border: 1px solid #ff7a00;
        max-width: 1200px;
    }
    .skins-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }
    .skin-card {
        background: rgba(0, 0, 0, 0.3);
        border-radius: 12px;
        padding: 15px;
        transition: all 0.3s ease;
        border-left: 4px solid #ff7a00;
    }
    .skin-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(255, 122, 0, 0.3);
    }
    .skin-img {
        width: 100%;
        height: 180px;
        object-fit: cover;
        border-radius: 8px;
        margin-bottom: 10px;
    }
    .skin-name {
        color: #fff;
        font-size: 18px;
        font-weight: 600;
        margin: 10px 0;
    }
    .skin-price {
        color: #ff7a00;
        font-size: 20px;
        font-weight: 700;
        margin: 5px 0;
    }
    .skin-seller, .skin-game {
        color: #aaa;
        font-size: 14px;
        margin: 5px 0;
    }
    .owner-badge {
        background: #ff7a00;
        color: black;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 10px;
        font-weight: bold;
        margin-left: 5px;
    }
    .skin-actions {
        display: flex;
        gap: 10px;
        margin-top: 10px;
    }
    .edit-btn {
        background: #4CAF50;
        color: white;
        border: none;
        padding: 8px 12px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 12px;
        transition: all 0.3s ease;
    }
    .edit-btn:hover {
        background: #45a049;
        transform: translateY(-2px);
    }
    .delete-btn {
        background: #f44336;
        color: white;
        border: none;
        padding: 8px 12px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 12px;
        transition: all 0.3s ease;
    }
    .delete-btn:hover {
        background: #da190b;
        transform: translateY(-2px);
    }
    .desc-btn {
        display: inline-block;
        background: rgba(255, 122, 0, 0.2);
        color: #ff7a00;
        padding: 8px 12px;
        border-radius: 6px;
        margin-top: 10px;
        transition: all 0.3s ease;
        font-size: 12px;
    }
    .desc-btn:hover {
        background: rgba(255, 122, 0, 0.3);
        transform: translateY(-2px);
    }
    .view-all-btn {
        display: inline-block;
        background: linear-gradient(135deg, #ff7a00, #ff4f00);
        color: #fff;
        padding: 12px 30px;
        border-radius: 25px;
        text-decoration: none;
        font-weight: 700;
        transition: all 0.3s ease;
        margin-top: 20px;
    }
    .view-all-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 30px rgba(255, 122, 0, 0.4);
    }
    /* User Dropdown Menu Styles - SAME AS INDEX.PHP */
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

    /* LARGE PROFILE IMAGE - 45px x 45px */
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
    
    /* Discussion Modal Styles (Copied from trading.php) */
    .discussion-modal {
        position: fixed;
        inset: 0;
        display: none;
        justify-content: center;
        align-items: center;
        background: rgba(0,0,0,0.8);
        z-index: 10000;
    }
    .discussion-modal.active {
        display: flex;
    }
    .discussion-content {
        background: #111;
        border-radius: 12px;
        width: 90%;
        max-width: 600px;
        height: 80vh;
        display: flex;
        flex-direction: column;
        box-shadow: 0 8px 40px rgba(0,0,0,0.6);
    }
    .discussion-header {
        padding: 20px;
        border-bottom: 1px solid #333;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .discussion-messages {
        flex: 1;
        padding: 20px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
    .message {
        padding: 12px 16px;
        border-radius: 12px;
        max-width: 80%;
        word-wrap: break-word;
    }
    .message.sent {
        background: #ff7a00;
        color: #000;
        align-self: flex-end;
        border-bottom-right-radius: 4px;
    }
    .message.received {
        background: #333;
        color: #fff;
        align-self: flex-start;
        border-bottom-left-radius: 4px;
    }
    .message-time {
        font-size: 11px;
        opacity: 0.7;
        margin-top: 5px;
    }
  </style>
</head>
<body>

  <div class="transition-screen"></div>
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
      <a href="events.html">Events</a>
      <a href="shop.html">Shop</a>
      <a href="trading.php" class="active">Trading</a>
      <a href="news.html">News</a>
      <a href="reclamation.html">Support</a>
      <a href="about.html">About Us</a>
    </nav>

    <div class="header-right">
      <div class="user-dropdown" id="userDropdown">
        <div class="username-display">
          <?php if ($currentUserObj): ?>
            <?php if ($userImage): ?>
              <img src="<?php echo htmlspecialchars($userImage); ?>" alt="Profile">
            <?php else: ?>
              <i class="fas fa-user-circle"></i>
            <?php endif; ?>
            <span><?php echo htmlspecialchars($currentUserObj->getUsername()); ?></span>
          <?php else: ?>
            <i class="fas fa-user-circle"></i>
            <span><?php echo htmlspecialchars($currentUser); ?></span>
          <?php endif; ?>
          <i class="fas fa-chevron-down"></i>
        </div>
        
        <div class="dropdown-menu">
          <?php if ($currentUserObj): ?>
          <a href="profile.php" class="dropdown-item">
            <i class="fas fa-user"></i>
            <span>My Profile</span>
          </a>
          
          <a href="trading.php" class="dropdown-item">
            <i class="fas fa-exchange-alt"></i>
            <span>Trading</span>
          </a>
          
          <a href="tradehis.php" class="dropdown-item">
            <i class="fas fa-history"></i>
            <span>History</span>
          </a>
          
          <?php 
          $userRole = strtolower($currentUserObj->getRole());
          if ($userRole === 'admin' || $userRole === 'superadmin'): 
          ?>
          <a href="../back/dashboard.php" class="dropdown-item">
            <i class="fas fa-tachometer-alt"></i>
            <span>Dashboard</span>
          </a>
          <?php endif; ?>
          
          <div class="dropdown-divider"></div>
          
          <a href="Logout.php" class="dropdown-item logout">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
          </a>
          <?php else: ?>
          <a href="Login.php" class="dropdown-item">
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
    <section class="hero-intro">
      <div class="intro-content">
        <h1 class="main-title">Trade <span>History</span></h1>
        <p class="intro-description">Track all your <strong>trade activities</strong> in one place.</p>
      </div>
    </section>

    <!-- flash messages -->
    <?php if (isset($_GET['cleared'])): ?>
      <div class="flash success">Trade history cleared successfully!</div>
    <?php elseif (isset($_GET['error'])): ?>
      <div class="flash error">
        <?php 
          $error = $_GET['error'];
          if ($error == '1') echo 'Database error. Please try again.';
          elseif ($error == '6') echo 'User not found. Cannot clear history.';
          else echo 'Error processing request.';
        ?>
      </div>
    <?php endif; ?>

    <!-- MY TRADES SECTION -->
    <section class="my-trades-section">
      <h2 class="section-title"><span>My Active Trades</span> (<?php echo htmlspecialchars($currentUser); ?>)</h2>
      <p style="color:#ccc; margin-bottom: 20px;">Quick view of your current trade listings</p>

      <?php if (count($mySkins) === 0): ?>
        <div style="color:#ccc; text-align:center; width:100%; padding: 40px;">
          <p>You don't have any active trades.</p>
          <p><small>Visit the trading page to add your first trade!</small></p>
          <a href="trading.php" class="view-all-btn">
            <i class="fas fa-plus-circle"></i> Add a Trade
          </a>
        </div>
      <?php else: ?>
        <div class="skins-grid">
          <?php 
          // Show only first 3 trades
          $displaySkins = array_slice($mySkins, 0, 3);
          foreach ($displaySkins as $row): 
            $img = !empty($row['image']) ? '../' . ltrim($row['image'], '/\\') : '../images/skin1.png';
            $username = !empty($row['username']) ? $row['username'] : 'Unknown user';
            $category = $row['category'] ?? 'custom';
            $description = $row['description'] ?? 'No description provided';
          ?>
          <div class="skin-card" data-game="<?= htmlspecialchars($category) ?>" data-id="<?= $row['skin_id'] ?>">
            <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($row['name']) ?>" class="skin-img" onerror="this.src='../images/skin1.png'">
            <h3 class="skin-name"><?= htmlspecialchars($row['name']) ?></h3>
            <p class="skin-price">$<?= number_format((float)$row['price'], 2) ?></p>
            <p class="skin-seller">Seller: <strong>@<?= htmlspecialchars($username) ?></strong> <span class="owner-badge">YOU</span></p>
            <p class="skin-game">Game: <strong><?= htmlspecialchars(ucfirst($category)) ?></strong></p>

            <div class="skin-actions">
              <button class="edit-btn" onclick="openEditModal(<?= $row['skin_id'] ?>, '<?= htmlspecialchars(addslashes($row['name'])) ?>', <?= $row['price'] ?>, '<?= htmlspecialchars($category) ?>', '<?= htmlspecialchars(addslashes($description)) ?>')">
                <i class="fas fa-edit"></i> Edit
              </button>
              <form method="POST" action="trading.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this trade?')">
                <input type="hidden" name="skinId" value="<?= $row['skin_id'] ?>">
                <input type="hidden" name="delete_trade" value="1">
                <button type="submit" class="delete-btn"><i class="fas fa-trash"></i> Delete</button>
              </form>
            </div>

            <a href="description.php?id=<?= $row['skin_id'] ?>" class="desc-btn" style="text-decoration: none; display: inline-block; text-align: center;"><i class="fas fa-info-circle"></i> Description</a>
          </div>
          <?php endforeach; ?>
        </div>

        <?php if (count($mySkins) > 3): ?>
          <div style="text-align: center;">
            <a href="trading.php" class="view-all-btn">
              <i class="fas fa-exchange-alt"></i> View All My Trades (<?= count($mySkins) ?>)
            </a>
          </div>
        <?php endif; ?>
      <?php endif; ?>
    </section>

    <!-- TRADE HISTORY SECTION -->
    <section class="trade-history-section">
      <h2 class="section-title"><span> Trade History</span> (<?php echo htmlspecialchars($currentUser); ?>)</h2>
      <p style="color:#ccc; margin-bottom: 20px;">Track all your trade activities</p>

      <div class="history-filter">
        <button class="filter-btn active" data-filter="all">All Activities</button>
        <button class="filter-btn" data-filter="created">Created</button>
        <button class="filter-btn" data-filter="updated">Updated</button>
        <button class="filter-btn" data-filter="deleted">Deleted</button>
        <button class="filter-btn" data-filter="bought">Bought</button>
        <button class="clear-history-btn" onclick="confirmClearHistory('standard')">
          <i class="fas fa-trash"></i> Clear Activities
        </button>
      </div>

      <?php if (count($standardHistory) === 0): ?>
        <div class="no-history">
          <p>No trade history found. Your trade activities will appear here.</p>
          <p><small>Add, edit, or delete trades to see your history.</small></p>
        </div>
      <?php else: ?>
        <div class="table-container">
          <table class="history-table">
            <thead>
              <tr>
                <th>Date & Time</th>
                <th>Action</th>
                <th>Skin Name</th>
                <th>Price</th>
                <th>Game</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($standardHistory as $history): 
                $actionClass = '';
                switch ($history['action']) {
                  case 'created': $actionClass = 'action-created'; break;
                  case 'updated': $actionClass = 'action-updated'; break;
                  case 'deleted': $actionClass = 'action-deleted'; break;
                  case 'bought':  $actionClass = 'action-bought'; break;
                }
              ?>
              <tr class="history-row" data-action="<?= $history['action'] ?>">
                <td><?= date('M j, Y g:i A', strtotime($history['created_at'])) ?></td>
                <td><span class="action-badge <?= $actionClass ?>"><?= ucfirst($history['action']) ?></span></td>
                <td><?= htmlspecialchars($history['skin_name']) ?></td>
                <td>$<?= number_format((float)$history['skin_price'], 2) ?></td>
                <td><?= htmlspecialchars(ucfirst($history['skin_category'])) ?></td>
                <td></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>

    <!-- REFUSED NEGOTIATIONS SECTION -->
    <section class="trade-history-section" style="border-color: #ff4757; background: rgba(255, 71, 87, 0.05); margin-top: 20px;">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <div>
           <h2 class="section-title" style="margin-bottom:5px;"><span style="color:#ff4757;">Archived Negotiations</span></h2>
           <p style="color:#ccc; margin:0;">Review canceled or refused trade offers</p>
        </div>
        <button class="clear-history-btn" onclick="confirmClearHistory('negotiations')" style="background: rgba(255, 71, 87, 0.1); color: #ff4757; border: 1px solid #ff4757;">
          <i class="fas fa-trash"></i> Clear Negotiations
        </button>
      </div>

      <?php if (count($refusedHistory) === 0): ?>
        <div class="no-history">
          <p>No archived negotiations found.</p>
        </div>
      <?php else: ?>
        <div class="table-container">
          <table class="history-table">
            <thead>
              <tr>
                <th style="background: rgba(255, 71, 87, 0.2);">Date & Time</th>
                <th style="background: rgba(255, 71, 87, 0.2);">Status</th>
                <th style="background: rgba(255, 71, 87, 0.2);">Skin Name</th>
                <th style="background: rgba(255, 71, 87, 0.2);">Price</th>
                <th style="background: rgba(255, 71, 87, 0.2);">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($refusedHistory as $history): ?>
              <tr class="history-row">
                <td><?= date('M j, Y g:i A', strtotime($history['created_at'])) ?></td>
                <td><span class="action-badge action-deleted">Refused</span></td>
                <td><?= htmlspecialchars($history['skin_name']) ?></td>
                <td>$<?= number_format((float)$history['skin_price'], 2) ?></td>
                <td>
                    <button class="desc-btn" onclick="viewArchivedChat(<?= $history['skin_id'] ?>, '<?= isset($history['negotiation_id']) ? htmlspecialchars($history['negotiation_id']) : '' ?>')" style="padding:6px 12px; font-size:12px; background: #333; color: white; border: 1px solid #555;">
                        <i class="fas fa-comments"></i> View Chat
                    </button>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>
  </main>

  <!-- CHAT HISTORY MODAL -->
  <div class="discussion-modal" id="historyModal">
    <div class="discussion-content">
      <div class="discussion-header">
        <div>
          <h3>Archived Chat History</h3>
          <p style="color: #888; font-size: 14px; margin: 0;">Read-only view</p>
        </div>
        <span class="close-modal" id="closeHistoryModalBtn">&times;</span>
      </div>
      
      <div class="discussion-messages" id="historyChatContainer">
        <!-- Messages will load here -->
        <p style="text-align:center; color:#888;">Loading...</p>
      </div>
    </div>
  </div>

  <script>
    // History Modal
    const historyModal = document.getElementById('historyModal');
    const closeHistoryBtn = document.getElementById('closeHistoryModalBtn');
    const currentLoggedInUser = '<?php echo htmlspecialchars($currentUser); ?>';
    
    if (closeHistoryBtn) {
        closeHistoryBtn.addEventListener('click', () => {
            historyModal.classList.remove('active');
        });
    }
    
    window.addEventListener('click', (e) => {
        if (e.target === historyModal) {
            historyModal.classList.remove('active');
        }
    });

    function viewArchivedChat(skinId, negotiationId = null) {
        historyModal.classList.add('active');
        const container = document.getElementById('historyChatContainer');
        container.innerHTML = '<p style="text-align:center; color:#ccc;">Loading messages...</p>';
        
        const formData = new FormData();
        formData.append('get_archived_messages', '1');
        formData.append('skin_id', skinId);
        formData.append('active_user_check', currentLoggedInUser);
        if (negotiationId) {
            formData.append('negotiation_id', negotiationId);
        }
        
        fetch('tradehis.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                container.innerHTML = ''; // Clear loading message
                
                if (data.messages && data.messages.length > 0) {
                    const myName = currentLoggedInUser;
                    
                    data.messages.forEach(message => {
                        const isSent = (message.sender_username === myName);
                        const messageDiv = document.createElement('div');
                        messageDiv.className = `message ${isSent ? 'sent' : 'received'}`;
                        
                        let timeString = '';
                        if (message.created_at) {
                            try {
                                const date = new Date(message.created_at);
                                if (!isNaN(date.getTime())) {
                                    timeString = date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                                }
                            } catch (e) {}
                        }
                        
                        // Safe HTML construction
                        let contentHtml = `
                          <div style="font-size: 12px; color: ${isSent ? '#fff' : '#ff7a00'}; font-weight: 600; margin-bottom: 5px;">
                            ${isSent ? 'You (' + myName + ')' : escapeHtml(message.sender_username || 'Unknown')}
                          </div>
                        `;
                        
                        if (message.message) {
                            contentHtml += `<div>${escapeHtml(message.message)}</div>`;
                        }
                        
                        if (message.image_path) {
                            contentHtml += `<div style="margin: 8px 0;"><img src="../${escapeHtml(message.image_path)}" style="max-width: 100%; max-height: 250px; border-radius: 8px; cursor: pointer;" onclick="window.open(this.src, '_blank');"></div>`;
                        }
                        
                        contentHtml += `<div class="message-time">${timeString || 'Just now'}</div>`;
                        
                        messageDiv.innerHTML = contentHtml;
                        container.appendChild(messageDiv);
                    });
                    
                    // Scroll to bottom
                    setTimeout(() => {
                        container.scrollTop = container.scrollHeight;
                    }, 100);
                    
                } else {
                    container.innerHTML = '<p style="text-align:center; color:#ccc;">No archived messages found.</p>';
                }
            } else {
                 container.innerHTML = '<p style="text-align:center; color:red;">Error: ' + (data.error || 'Failed to load') + '</p>';
            }
        })
        .catch(err => {
            console.error(err);
            container.innerHTML = '<p style="text-align:center; color:red;">Request failed: ' + err.message + '</p>';
        });
    }

    function escapeHtml(text) {
      if (!text) return '';
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }
  </script>
  <div class="trade-modal" id="editModal">
    <div class="trade-modal-content">
      <span class="close-modal" id="closeEditModalBtn">&times;</span>
      <h2>Edit Trade Deal</h2>

      <form id="editForm">
        <input type="hidden" name="skinId" id="editSkinId">
        <input type="text" name="skinName" id="editSkinName" placeholder="Skin Name" required maxlength="100">
        <input type="number" name="skinPrice" id="editSkinPrice" placeholder="Price $" step="0.01" min="0.01" required>

        <select name="skinGame" id="editSkinGame" required>
          <option value="custom">Custom</option>
          <option value="valorant">Valorant</option>
          <option value="cs2">CS2</option>
          <option value="fortnite">Fortnite</option>
          <option value="apex">Apex Legends</option>
        </select>

        <textarea name="skinDescription" id="editSkinDescription" placeholder="Short description (max 100 characters)" maxlength="100"></textarea>

        <input type="hidden" name="update_trade" value="1">
        <div style="text-align:right; margin-top:10px;">
          <button type="submit" class="add-trade-btn">Update Skin</button>
        </div>
      </form>
    </div>
  </div>

  <!-- FOOTER (optional - can add if needed) -->

  <script>
    // User dropdown toggle
    const userDropdown = document.getElementById('userDropdown');
    if (userDropdown) {
      userDropdown.addEventListener('click', function(e) {
        e.stopPropagation();
        this.classList.toggle('active');
      });

      document.addEventListener('click', function() {
        userDropdown.classList.remove('active');
      });
    }

    // Auto-hide flash messages
    setTimeout(() => {
      const flash = document.querySelector('.flash');
      if (flash) flash.style.display = 'none';
    }, 4000);

    // Filter functionality
    const filterBtns = document.querySelectorAll('.filter-btn');
    const historyRows = document.querySelectorAll('.history-row');

    filterBtns.forEach(btn => {
      btn.addEventListener('click', function() {
        // Update active button
        filterBtns.forEach(b => b.classList.remove('active'));
        this.classList.add('active');

        const filter = this.getAttribute('data-filter');

        // Filter rows
        historyRows.forEach(row => {
          const action = row.getAttribute('data-action');
          if (filter === 'all' || action === filter) {
            row.style.display = '';
          } else {
            row.style.display = 'none';
          }
        });
      });
    });

    // Clear history functionality
    function confirmClearHistory(type) {
        const typeName = type === 'standard' ? 'trade activities' : (type === 'negotiations' ? 'archived negotiations' : 'history');
        if (confirm(`Are you sure you want to clear your ${typeName}? This action cannot be undone.`)) {
          // Create a form and submit
          const form = document.createElement('form');
          form.method = 'POST';
          form.action = 'tradehis.php';
          
          const input = document.createElement('input');
          input.type = 'hidden';
          input.name = 'clear_history';
          input.value = '1';
          form.appendChild(input);

          const typeInput = document.createElement('input');
          typeInput.type = 'hidden';
          typeInput.name = 'clear_type';
          typeInput.value = type;
          form.appendChild(typeInput);
          
          document.body.appendChild(form);
          form.submit();
        }
    }

    // Modal functions for edit
    document.getElementById('closeEditModalBtn').addEventListener('click', () => {
      const modal = document.getElementById('editModal');
      modal.classList.remove('active');
    });

    window.addEventListener('click', (e) => {
      if (e.target === document.getElementById('editModal')) {
        document.getElementById('editModal').classList.remove('active');
      }
    });

    function openEditModal(skinId, name, price, category, description) {
      document.getElementById('editSkinId').value = skinId;
      document.getElementById('editSkinName').value = name;
      document.getElementById('editSkinPrice').value = price;
      document.getElementById('editSkinGame').value = category;
      document.getElementById('editSkinDescription').value = description;
      const modal = document.getElementById('editModal');
      modal.classList.add('active');
    }

    // Handle edit form submission with AJAX
    document.getElementById('editForm').addEventListener('submit', function(e) {
      e.preventDefault();
      
      const formData = new FormData(this);
      
      fetch('tradehis.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Close modal without page redirect
          document.getElementById('editModal').classList.remove('active');
          
          // Refresh the page data
          location.reload();
        } else {
          alert('Error updating trade: ' + (data.error || 'Unknown error'));
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('Failed to update trade');
      });
    });
  </script>

</body>
</html>
