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

// Handle POST requests (clear history)
$jsonResponse = $controller->handlePost();
if ($jsonResponse !== null) {
    header('Content-Type: application/json');
    echo json_encode($jsonResponse);
    exit();
}

$viewData = $controller->getViewData();
$tradeHistory = $viewData['tradeHistory'];
$mySkins = $viewData['mySkins'];
$currentUser = $viewData['currentUser'];
?>
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
              <button class="edit-btn" onclick="window.location.href='trading.php#edit-<?= $row['skin_id'] ?>'">
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
      <h2 class="section-title"><span>Trade History</span> (<?php echo htmlspecialchars($currentUser); ?>)</h2>
      <p style="color:#ccc; margin-bottom: 20px;">Track all your trade activities</p>

      <div class="history-filter">
        <button class="filter-btn active" data-filter="all">All Activities</button>
        <button class="filter-btn" data-filter="created">Created</button>
        <button class="filter-btn" data-filter="updated">Updated</button>
        <button class="filter-btn" data-filter="deleted">Deleted</button>
        <button class="filter-btn" data-filter="bought">Bought</button>
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
                  case 'bought':  $actionClass = 'action-bought'; break;
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
  </main>

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
    const clearHistoryBtn = document.getElementById('clearHistoryBtn');
    if (clearHistoryBtn) {
      clearHistoryBtn.addEventListener('click', function() {
        if (confirm('Are you sure you want to clear your entire trade history? This action cannot be undone.')) {
          // Create a form and submit
          const form = document.createElement('form');
          form.method = 'POST';
          form.action = 'tradehis.php';
          
          const input = document.createElement('input');
          input.type = 'hidden';
          input.name = 'clear_history';
          input.value = '1';
          
          form.appendChild(input);
          document.body.appendChild(form);
          form.submit();
        }
      });
    }
  </script>

</body>
</html>
