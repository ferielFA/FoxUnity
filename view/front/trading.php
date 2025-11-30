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
require_once __DIR__ . '/../../model/UserModel.php';
$userModel = new UserModel();
if (!$userModel->verifySessionUserLinked()) {
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


$jsonResponse = $controller->handlePost();
if ($jsonResponse !== null) {

    header('Content-Type: application/json');
    echo json_encode($jsonResponse);
    exit();
}

$viewData = $controller->getViewData();
$skins = $viewData['skins'];
$mySkins = $viewData['mySkins'];
$tradeHistory = $viewData['tradeHistory'];
$currentUser = $viewData['currentUser'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>FoxUnity - Trading</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="styletrade.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Orbitron:wght@700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"/>
  <style>
    .trade-modal { position: fixed; inset:0; display:none; justify-content:center; align-items:center; background: rgba(0,0,0,0.6); z-index:9999; }
    .trade-modal.active { display:flex; }
    .trade-modal-content { background:#111; padding:24px; border-radius:12px; width:400px; max-width:92%; box-shadow:0 8px 40px rgba(0,0,0,0.6); position: relative; }
    .trade-modal-content input[type="text"], .trade-modal-content input[type="number"], .trade-modal-content textarea, .trade-modal-content select {
      width:100%; padding:10px; margin:8px 0; border-radius:8px; border:1px solid #333; background:#1a1a1a; color:#fff;
    }
    .trade-modal-content .add-trade-btn{ background:#ff7a00; color:#000; border:none; padding:10px 16px; border-radius:10px; cursor:pointer; font-weight:700; }
    .close-modal { position:absolute; right:12px; top:8px; color:#ff7a00; font-size:22px; cursor:pointer; }
    .flash { position:fixed; top:18px; right:18px; padding:10px 14px; border-radius:8px; z-index:10000; color:#fff; font-weight:700; }
    .flash.success { background: #2ed573; }
    .flash.error { background: #ff4757; }
    .skin-card[data-game="valorant"] { border-left: 4px solid #ff4655; }
    .skin-card[data-game="cs2"] { border-left: 4px solid #f9a602; }
    .skin-card[data-game="fortnite"] { border-left: 4px solid #5bc0f8; }
    .skin-card[data-game="apex"] { border-left: 4px solid #ea4335; }
    .skin-card[data-game="custom"] { border-left: 4px solid #9c27b0; }
    .username-help { 
        color: #888; 
        font-size: 12px; 
        margin-top: 4px; 
        display: block; 
    }
    .my-trades-section {
        margin: 40px 0;
        padding: 20px;
        background: rgba(255,122,0,0.1);
        border-radius: 12px;
        border: 1px solid #ff7a00;
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
    }
    .delete-btn {
        background: #f44336;
        color: white;
        border: none;
        padding: 8px 12px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 12px;
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
    /* Discussion Modal Styles */
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
    .discussion-input {
        padding: 20px;
        border-top: 1px solid #333;
        display: flex;
        gap: 10px;
    }
    .discussion-input input {
        flex: 1;
        padding: 12px;
        border-radius: 8px;
        border: 1px solid #333;
        background: #1a1a1a;
        color: #fff;
    }
    .discussion-input button {
        background: #ff7a00;
        color: #000;
        border: none;
        padding: 12px 20px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 700;
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
    .offer-section {
        background: rgba(255,122,0,0.1);
        padding: 15px;
        border-radius: 8px;
        margin: 10px 0;
        border-left: 4px solid #ff7a00;
    }
    .chat-error {
        background: #ff4757;
        color: white;
        padding: 10px;
        border-radius: 8px;
        margin: 10px 0;
        text-align: center;
    }
    /* Trade History Styles */
    .trade-history-section {
        margin: 40px 0;
        padding: 20px;
        background: rgba(255,122,0,0.1);
        border-radius: 12px;
        border: 1px solid #ff7a00;
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
      
      <a href="panier.html" class="cart-icon">
        <i class="fas fa-shopping-cart"></i> Cart
        <span class="cart-count">0</span>
      </a>
    </div>
  </header>

  <main class="main-section">
    <section class="hero-intro">
      <div class="intro-content">
        <h1 class="main-title">Trade <span>Exclusive Skins</span></h1>
        <p class="intro-description">Browse, buy, or chat directly with sellers. <strong>Secure trading</strong> made easy with FoxUnity.</p>
      </div>
    </section>

    <!-- flash messages -->
    <?php if (isset($_GET['added'])): ?>
      <div class="flash success">Trade added successfully!</div>
    <?php elseif (isset($_GET['updated'])): ?>
      <div class="flash success">Trade updated successfully!</div>
    <?php elseif (isset($_GET['deleted'])): ?>
      <div class="flash success">Trade deleted successfully!</div>
    <?php elseif (isset($_GET['cleared'])): ?>
      <div class="flash success">Trade history cleared successfully!</div>
    <?php elseif (isset($_GET['error'])): ?>
      <div class="flash error">
        <?php 
          $error = $_GET['error'];
          if ($error == '1') echo 'Database error. Please try again.';
          elseif ($error == '2') echo 'Please provide valid information.';
          elseif ($error == '3') echo 'Please upload an image.';
          elseif ($error == '4') echo 'Unknown user. Please enter a valid username.';
          elseif ($error == '5') echo 'You can only modify your own trades.';
          elseif ($error == '6') echo 'User not found. Cannot clear history.';
          elseif ($error == '7') echo 'You can only add trades as yourself.';
          else echo 'Error processing request.';
        ?>
      </div>
    <?php endif; ?>

    <!-- MY TRADES SECTION -->
    <section class="my-trades-section">
      <h2 class="section-title"><span>My Trades</span> (<?php echo htmlspecialchars($currentUser); ?>)</h2>
      <p style="color:#ccc; margin-bottom: 20px;">Manage your active trade listings</p>

      <div class="add-trade-container">
        <button class="add-trade-btn" id="openModalBtn"><i class="fas fa-plus-circle"></i> Add a Trade Deal</button>
      </div>

      <div class="skins-grid">
        <?php if (count($mySkins) === 0): ?>
          <div style="color:#ccc; text-align:center; width:100%; padding: 40px;">
            <p>You don't have any active trades. Add your first one!</p>
            <p><small>Click "Add a Trade Deal" above to get started.</small></p>
          </div>
        <?php else: ?>
          <?php foreach ($mySkins as $row): 
            $img = !empty($row['image']) ? '../' . ltrim($row['image'], '/\\') : '../images/skin1.png';
            $username = !empty($row['username']) ? $row['username'] : 'Unknown user';
            $category = $row['category'] ?? 'custom';
            $description = $row['description'] ?? 'No description provided';
          ?>
          <div class="skin-card" data-game="<?= htmlspecialchars($category) ?>">
            <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($row['name']) ?>" class="skin-img" onerror="this.src='../images/skin1.png'">
            <h3 class="skin-name"><?= htmlspecialchars($row['name']) ?></h3>
            <p class="skin-price">$<?= number_format((float)$row['price'], 2) ?></p>
            <p class="skin-seller">Seller: <strong>@<?= htmlspecialchars($username) ?></strong> <span class="owner-badge">YOU</span></p>
            <p class="skin-game">Game: <strong><?= htmlspecialchars(ucfirst($category)) ?></strong></p>

            <div class="skin-actions">
              <button class="edit-btn" onclick="openEditModal(<?= $row['skin_id'] ?>, '<?= htmlspecialchars(addslashes($row['name'])) ?>', <?= $row['price'] ?>, '<?= htmlspecialchars(addslashes($category)) ?>', '<?= htmlspecialchars(addslashes($description)) ?>')">
                <i class="fas fa-edit"></i> Edit
              </button>
              <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this trade?')">
                <input type="hidden" name="skinId" value="<?= $row['skin_id'] ?>">
                <input type="hidden" name="delete_trade" value="1">
                <button type="submit" class="delete-btn"><i class="fas fa-trash"></i> Delete</button>
              </form>
            </div>

            <button class="desc-btn"><i class="fas fa-info-circle"></i> Description</button>
            <div class="description-box"><p><?= htmlspecialchars($description) ?></p></div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </section>

    <!-- TRADE HISTORY SECTION -->
    <section class="trade-history-section">
      <h2 class="section-title"><span>Trade History</span> (<?php echo htmlspecialchars($currentUser); ?>)</h2>
      <p style="color:#ccc; margin-bottom: 20px;">Track all your trade activities</p>

      <div class="history-filter">
        <button class="filter-btn active" data-filter="all">All Activities</button>
        <button class="filter-btn" data-filter="created">Created</button>
        <button class="filter-btn" data-filter="updated">Updated</button>
        <button class="filter-btn" data-filter="deleted">Deleted</button>
        <button class="clear-history-btn" id="clearHistoryBtn">
          <i class="fas fa-trash"></i> Clear History
        </button>
      </div>

      <?php if (count($tradeHistory) === 0): ?>
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
              <?php foreach ($tradeHistory as $history): 
                $actionClass = '';
                switch ($history['action']) {
                  case 'created': $actionClass = 'action-created'; break;
                  case 'updated': $actionClass = 'action-updated'; break;
                  case 'deleted': $actionClass = 'action-deleted'; break;
                }
              ?>
              <tr class="history-row" data-action="<?= $history['action'] ?>">
                <td><?= date('M j, Y g:i A', strtotime($history['created_at'])) ?></td>
                <td><span class="action-badge <?= $actionClass ?>"><?= ucfirst($history['action']) ?></span></td>
                <td><?= htmlspecialchars($history['skin_name']) ?></td>
                <td>$<?= number_format((float)$history['skin_price'], 2) ?></td>
                <td><?= htmlspecialchars(ucfirst($history['skin_category'])) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>

    <!-- ALL TRADES SECTION -->
    <section class="trading-section">
      <h2 class="section-title"><span>Available</span> Skins</h2>

      <div class="filter-bar">
        <input type="text" id="searchInput" placeholder="Search for a skin..." />
        <select id="gameFilter">
          <option value="all">All Games</option>
          <option value="valorant">Valorant</option>
          <option value="cs2">CS2</option>
          <option value="fortnite">Fortnite</option>
          <option value="apex">Apex Legends</option>
          <option value="custom">Custom</option>
        </select>
      </div>

      <div class="skins-grid">
        <?php if (count($skins) === 0): ?>
          <p style="color:#ccc; text-align:center; width:100%;">No skins listed yet. Be the first to add one!</p>
        <?php else: ?>
          <?php foreach ($skins as $row): 
            $img = !empty($row['image']) ? '../' . ltrim($row['image'], '/\\') : '../images/skin1.png';
            $username = !empty($row['username']) ? $row['username'] : 'Unknown user';
            $category = $row['category'] ?? 'custom';
            $description = $row['description'] ?? 'No description provided';
            $isOwner = ($username === $currentUser);
          ?>
          <div class="skin-card" data-game="<?= htmlspecialchars($category) ?>">
            <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($row['name']) ?>" class="skin-img" onerror="this.src='../images/skin1.png'">
            <h3 class="skin-name"><?= htmlspecialchars($row['name']) ?></h3>
            <p class="skin-price">$<?= number_format((float)$row['price'], 2) ?></p>
            <p class="skin-seller">Seller: <strong>@<?= htmlspecialchars($username) ?></strong>
              <?php if ($isOwner): ?>
                <span class="owner-badge">YOU</span>
              <?php endif; ?>
            </p>
            <p class="skin-game">Game: <strong><?= htmlspecialchars(ucfirst($category)) ?></strong></p>

            <div class="skin-buttons">
              <button class="buy-btn"><i class="fas fa-shopping-cart"></i> Buy</button>
              <button class="chat-btn" onclick="openDiscussionModal('<?= htmlspecialchars(addslashes($row['name'])) ?>', '<?= htmlspecialchars(addslashes($username)) ?>', <?= $row['skin_id'] ?>, <?= $isOwner ? 'true' : 'false' ?>)">
                <i class="fas fa-comments"></i> Offer a Trade
              </button>
            </div>

            <button class="desc-btn"><i class="fas fa-info-circle"></i> Description</button>
            <div class="description-box"><p><?= htmlspecialchars($description) ?></p></div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </section>
  </main>

  <!-- ADD TRADE MODAL -->
  <div class="trade-modal" id="tradeModal">
    <div class="trade-modal-content">
      <span class="close-modal" id="closeModalBtn">&times;</span>
      <h2>Add a Trade Deal</h2>

      <form id="tradeForm" method="POST" action="trading.php" enctype="multipart/form-data">
        <input type="text" name="skinName" id="skinName" placeholder="Skin Name" required maxlength="100">
        <input type="number" name="skinPrice" id="skinPrice" placeholder="Price $" step="0.01" min="0.01" required>
        
        <input type="text" name="sellerUsername" id="sellerUsername" value="<?= htmlspecialchars($currentUser) ?>" placeholder="Seller Username" required maxlength="100" readonly style="background: #333;">
        <span class="username-help">Your username is pre-filled</span>

        <select name="skinGame" id="skinGame" required>
          <option value="custom">Custom</option>
          <option value="valorant">Valorant</option>
          <option value="cs2">CS2</option>
          <option value="fortnite">Fortnite</option>
          <option value="apex">Apex Legends</option>
        </select>

        <textarea name="skinDescription" id="skinDescription" placeholder="Short description (max 100 characters)" maxlength="100"></textarea>

        <label for="skinImage" style="color: #fff; margin: 8px 0; display: block;">Skin Image (required):</label>
        <input type="file" name="skinImage" id="skinImage" accept="image/*" required>

        <input type="hidden" name="add_trade" value="1">
        <div style="text-align:right; margin-top:10px;">
          <button type="submit" class="add-trade-btn">Add Skin</button>
        </div>
      </form>
    </div>
  </div>

  <!-- EDIT TRADE MODAL -->
  <div class="trade-modal" id="editModal">
    <div class="trade-modal-content">
      <span class="close-modal" id="closeEditModalBtn">&times;</span>
      <h2>Edit Trade Deal</h2>

      <form id="editForm" method="POST" action="trading.php">
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

  <!-- DISCUSSION MODAL -->
  <div class="discussion-modal" id="discussionModal">
    <div class="discussion-content">
      <div class="discussion-header">
        <div>
          <h3 id="discussionTitle">Trade Discussion</h3>
          <p style="color: #888; font-size: 14px; margin: 0;">With: <span id="sellerName"></span></p>
        </div>
        <span class="close-modal" id="closeDiscussionBtn">&times;</span>
      </div>
      
      <div class="discussion-messages" id="discussionMessages">
        <!-- Messages will be loaded here -->
      </div>
      
      <div class="discussion-input">
        <input type="text" id="discussionInput" placeholder="Type your message or make an offer...">
        <button id="sendDiscussionBtn">Send</button>
      </div>
    </div>
  </div>

  <!-- FOOTER -->
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
        <a href="../back/dashboard.html" class="dashboard-link">
          <i class="fas fa-tachometer-alt"></i> My Dashboard
        </a>
      </div>
    </div>
    <div class="footer-bottom">
      <p>© 2025 FoxUnity. All rights reserved. Made with <span>♥</span> by gamers for gamers</p>
    </div>
  </footer>

  <!-- SCRIPTS -->
  <script src="trading-validation.js"></script>
  <script>
    // Dropdown Menu Toggle
    document.addEventListener('DOMContentLoaded', function() {
      const userDropdown = document.getElementById('userDropdown');
      
      if (userDropdown) {
        const usernameDisplay = userDropdown.querySelector('.username-display');
        
        // Toggle dropdown on click
        usernameDisplay.addEventListener('click', function(e) {
          e.stopPropagation();
          userDropdown.classList.toggle('active');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
          if (!userDropdown.contains(e.target)) {
            userDropdown.classList.remove('active');
          }
        });
        
        // Close dropdown when pressing Escape
        document.addEventListener('keydown', function(e) {
          if (e.key === 'Escape') {
            userDropdown.classList.remove('active');
          }
        });
      }
      
      // Update cart count from localStorage
      const cart = JSON.parse(localStorage.getItem('cart')) || [];
      const cartCount = document.querySelector('.cart-count');
      if (cartCount) {
        cartCount.textContent = cart.length;
      }
    });

    document.getElementById('openModalBtn').addEventListener('click', () => {
      const modal = document.getElementById('tradeModal');
      modal.classList.add('active');

      if (typeof clearFormErrors === 'function') {
        clearFormErrors(document.getElementById('tradeForm'));
      }
    });

    document.getElementById('closeModalBtn').addEventListener('click', () => {
      const modal = document.getElementById('tradeModal');
      modal.classList.remove('active');

      if (typeof clearFormErrors === 'function') {
        clearFormErrors(document.getElementById('tradeForm'));
      }
    });

    document.getElementById('closeEditModalBtn').addEventListener('click', () => {
      const modal = document.getElementById('editModal');
      modal.classList.remove('active');

      if (typeof clearFormErrors === 'function') {
        clearFormErrors(document.getElementById('editForm'));
      }
    });



    window.addEventListener('click', (e) => {
      if (e.target === document.getElementById('tradeModal')) {
        document.getElementById('tradeModal').classList.remove('active');
      }
      if (e.target === document.getElementById('editModal')) {
        document.getElementById('editModal').classList.remove('active');
      }
      if (e.target === document.getElementById('discussionModal')) {
        if (messageRefreshInterval) {
          clearInterval(messageRefreshInterval);
          messageRefreshInterval = null;
        }
        document.getElementById('discussionModal').classList.remove('active');
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

      if (typeof clearFormErrors === 'function') {
        clearFormErrors(document.getElementById('editForm'));
      }
    }


    let currentSkinId = null;
    let messageRefreshInterval = null;
    
    function openDiscussionModal(skinName, sellerName, skinId, isOwner) {

      
      document.getElementById('discussionTitle').textContent = `Discussing: ${skinName}`;
      document.getElementById('sellerName').textContent = sellerName;
      document.getElementById('discussionModal').classList.add('active');
      currentSkinId = skinId;
      

      loadMessages();
      

      if (messageRefreshInterval) {
        clearInterval(messageRefreshInterval);
      }
      messageRefreshInterval = setInterval(() => {
        if (document.getElementById('discussionModal').classList.contains('active')) {
          loadMessages();
        }
      }, 3000);
      

      document.getElementById('discussionInput').focus();
    }
    

    document.getElementById('closeDiscussionBtn').addEventListener('click', () => {
      if (messageRefreshInterval) {
        clearInterval(messageRefreshInterval);
        messageRefreshInterval = null;
      }
      document.getElementById('discussionModal').classList.remove('active');
    });
    


    function loadMessages() {
      if (!currentSkinId) {
        console.error('No skin ID set');
        return;
      }
      
      const formData = new FormData();
      formData.append('get_messages', '1');
      formData.append('skin_id', currentSkinId);
      
      fetch('trading.php', {
        method: 'POST',
        body: formData
      })
      .then(response => {
        if (!response.ok) {
          throw new Error('Network response was not ok');
        }
        return response.text();
      })
      .then(text => {
        console.log('Raw response:', text);
        try {
          const data = JSON.parse(text);
          console.log('Parsed response:', data);
          console.log('Messages array:', data.messages);
          console.log('Messages count:', data.messages ? data.messages.length : 0);
          
          if (data.success) {
            displayMessages(data.messages || []);
          } else {
            showChatError(data.error || 'Failed to load messages');
          }
        } catch (e) {
          console.error('JSON parse error:', e);
          console.error('Response text:', text);
          showChatError('Invalid response from server');
        }
      })
      .catch(error => {
        console.error('Error loading messages:', error);
        showChatError('Failed to load messages: ' + error.message);
      });
    }


    function displayMessages(messages) {
      const messagesContainer = document.getElementById('discussionMessages');
      if (!messagesContainer) return;
      
      const wasAtBottom = messagesContainer.scrollHeight - messagesContainer.scrollTop <= messagesContainer.clientHeight + 50;
      
      messagesContainer.innerHTML = '';
      
      // Ensure messages is an array
      if (!Array.isArray(messages)) {
        console.error('Messages is not an array:', messages);
        messages = [];
      }
      
      if (messages.length === 0) {
        const welcomeDiv = document.createElement('div');
        welcomeDiv.className = 'offer-section';
        welcomeDiv.innerHTML = `
          <strong>📦 Trade Offer</strong>
          <p>Start the conversation by making an offer or asking a question!</p>
        `;
        messagesContainer.appendChild(welcomeDiv);
        return;
      }
      
      messages.forEach(message => {
        if (!message || !message.message) {
          console.error('Invalid message object:', message);
          return;
        }
        
        const messageDiv = document.createElement('div');
        const isSent = message.sender_username === '<?php echo $currentUser; ?>';
        messageDiv.className = `message ${isSent ? 'sent' : 'received'}`;
        
        let timeString = '';
        if (message.created_at) {
          try {
            const date = new Date(message.created_at);
            if (!isNaN(date.getTime())) {
              timeString = date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            }
          } catch (e) {
            console.error('Error parsing date:', e);
          }
        }
        
        messageDiv.innerHTML = `
          <div>${escapeHtml(message.message)}</div>
          <div class="message-time">${timeString ? timeString + ' - ' : ''}${escapeHtml(message.sender_username || 'Unknown')}</div>
        `;
        
        messagesContainer.appendChild(messageDiv);
      });
      

      if (wasAtBottom) {
        setTimeout(() => {
          messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }, 100);
      }
    }
    

    function escapeHtml(text) {
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }


    function showChatError(message) {
      const messagesContainer = document.getElementById('discussionMessages');
      const errorDiv = document.createElement('div');
      errorDiv.className = 'chat-error';
      errorDiv.textContent = message;
      messagesContainer.appendChild(errorDiv);
    }


    document.getElementById('sendDiscussionBtn').addEventListener('click', sendDiscussionMessage);
    document.getElementById('discussionInput').addEventListener('keypress', (e) => {
      if (e.key === 'Enter') {
        sendDiscussionMessage();
      }
    });

    function sendDiscussionMessage() {
      const input = document.getElementById('discussionInput');
      const message = input.value.trim();
      

      if (!validateDiscussionMessage(input)) {
        return;
      }
      
      if (!currentSkinId) {
        return;
      }
      
      const formData = new FormData();
      formData.append('send_message', '1');
      formData.append('skin_id', currentSkinId);
      formData.append('message', message);
      
      fetch('trading.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        console.log('Send message response:', data); 
        if (data.success) {
          input.value = '';

          if (typeof removeError === 'function') {
            removeError(input);
          }

          loadMessages();
        } else {
          showChatError(data.error || 'Failed to send message');
        }
      })
      .catch(error => {
        console.error('Error sending message:', error);
        showChatError('Failed to send message: ' + error.message);
      });
    }


    const searchInput = document.getElementById('searchInput');
    const gameFilter = document.getElementById('gameFilter');
    
    function filterSkins() {
      const searchValue = searchInput.value.toLowerCase();
      const selectedGame = gameFilter.value;
      
      document.querySelectorAll('.skin-card').forEach(card => {
        const name = card.querySelector('.skin-name').textContent.toLowerCase();
        const game = card.getAttribute('data-game') || '';
        const matchesSearch = name.includes(searchValue);
        const matchesGame = selectedGame === 'all' || game === selectedGame;
        card.style.display = (matchesSearch && matchesGame) ? 'block' : 'none';
      });
    }
    
    searchInput.addEventListener('input', filterSkins);
    gameFilter.addEventListener('change', filterSkins);


    const filterBtns = document.querySelectorAll('.filter-btn');
    
    filterBtns.forEach(btn => {
      btn.addEventListener('click', () => {

        filterBtns.forEach(b => b.classList.remove('active'));

        btn.classList.add('active');
        
        const filter = btn.getAttribute('data-filter');
        filterHistory(filter);
      });
    });
    
    function filterHistory(filter) {
      const rows = document.querySelectorAll('.history-row');
      
      rows.forEach(row => {
        if (filter === 'all' || row.getAttribute('data-action') === filter) {
          row.style.display = '';
        } else {
          row.style.display = 'none';
        }
      });
    }


    document.getElementById('clearHistoryBtn').addEventListener('click', function() {
      if (confirm('Are you sure you want to clear your entire trade history? This action cannot be undone.')) {

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'trading.php';
        
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'clear_history';
        input.value = '1';
        
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
      }
    });


    
    let activeCard = null;
    const grid = document.querySelector('.skins-grid');
    
    document.querySelectorAll('.desc-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        const card = btn.closest('.skin-card');
        const desc = card.querySelector('.description-box');
        
        if (activeCard && activeCard !== card) {
          activeCard.classList.remove('focused');
          activeCard.querySelector('.description-box').classList.remove('active');
        }
        
        const isActive = card.classList.contains('focused');
        card.classList.toggle('focused', !isActive);
        desc.classList.toggle('active', !isActive);
        grid.classList.toggle('focused-mode', !isActive);
        activeCard = !isActive ? card : null;
      });
    });


    const flash = document.querySelector('.flash');
    if (flash) {
      setTimeout(() => {
        flash.style.opacity = '0';
        setTimeout(() => flash.remove(), 400);
      }, 3500);
    }


  </script>
</body>
</html>