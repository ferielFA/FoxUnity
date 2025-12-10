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
        align-items: center;
    }
    .discussion-input input {
        flex: 1;
        padding: 12px;
        border-radius: 8px;
        border: 1px solid #333;
        background: #1a1a1a;
        color: #fff;
    }
    .discussion-input input:disabled {
        background: #0d0d0d;
        color: #666;
        cursor: not-allowed;
    }
    .discussion-input button {
        background: #ff7a00;
        color: #000;
        border: none;
        padding: 12px 20px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        min-width: 44px;
    }
    .discussion-input button:hover {
        background: #ff9000;
    }
    #attachImageBtn {
        background: #333;
        color: #ff7a00;
        padding: 12px 16px;
    }
    #attachImageBtn:hover {
        background: #444;
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


    <!-- ALL TRADES SECTION -->
    <section class="trading-section">
      <div style="text-align: center; margin-bottom: 20px;">
        <h2 class="section-title"><span>Available</span> Skins</h2>
        <button id="openModalBtn" style="background: linear-gradient(135deg, #ff7a00, #ff4f00); color: #fff; padding: 12px 30px; border: none; border-radius: 25px; font-weight: 700; cursor: pointer; transition: all 0.3s ease; font-size: 14px; margin-top: 15px;">
          <i class="fas fa-plus-circle"></i> Add Trade
        </button>
      </div>

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
          <div class="skin-card" data-game="<?= htmlspecialchars($category) ?>" data-id="<?= $row['skin_id'] ?>">
            <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($row['name']) ?>" class="skin-img" onerror="this.src='../images/skin1.png'">
            <h3 class="skin-name"><?= htmlspecialchars($row['name']) ?></h3>
            <p class="skin-price">$<?= number_format((float)$row['price'], 2) ?></p>
            <p class="skin-seller">Seller: <strong>@<?= htmlspecialchars($username) ?></strong>
              <?php if ($isOwner): ?>
                <span class="owner-badge">YOU</span>
              <?php endif; ?>
            </p>
            <p class="skin-game">Game: <strong><?= htmlspecialchars(ucfirst($category)) ?></strong></p>

            <?php
            // Logic for Exclusive Trade Offers
            $activeBuyerId = !empty($row['active_buyer_id']) ? $row['active_buyer_id'] : null;
            $isLocked = false;
            
            // Check if locked: A conversation exists (activeBuyerId set) and I am neither the buyer nor the seller
            if ($activeBuyerId && ($currentUserObj && $activeBuyerId != $currentUserObj->getId()) && !$isOwner) {
                $isLocked = true;
            }
            ?>
            <div class="skin-buttons">
              <?php if ($isLocked): ?>
                  <button class="buy-btn disabled" disabled style="background:#555; cursor:not-allowed; opacity:0.8;" title="This trade is being negotiated"><i class="fas fa-shopping-cart"></i> Buy</button>
                  <button class="chat-btn disabled" style="background:#555; cursor:not-allowed; opacity:0.8; width: 100%;" disabled title="This trade is being negotiated by another user">
                    <i class="fas fa-user-lock"></i> Someone is offering
                  </button>
              <?php else: ?>
                  <button class="buy-btn"><i class="fas fa-shopping-cart"></i> Buy</button>
                  <button class="chat-btn" onclick="openDiscussionModal('<?= htmlspecialchars(addslashes($row['name'])) ?>', '<?= htmlspecialchars(addslashes($username)) ?>', <?= $row['skin_id'] ?>, <?= $isOwner ? 'true' : 'false' ?>)">
                    <i class="fas fa-comments"></i> Offer a Trade
                  </button>
              <?php endif; ?>
            </div>

            <a href="description.php?id=<?= $row['skin_id'] ?>" class="desc-btn" style="text-decoration: none; display: inline-block; text-align: center;"><i class="fas fa-info-circle"></i> Description</a>
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

  <!-- DISCUSSION MODAL -->
  <div class="discussion-modal" id="discussionModal">
    <div class="discussion-content">
      <div class="discussion-header">
        <div>
          <h3 id="discussionTitle">Trade Discussion</h3>
          <p style="color: #888; font-size: 14px; margin: 0;">With: <span id="sellerName"></span></p>
        </div>
        <div style="display:flex; gap:10px; align-items:center;">
          <button id="refuseOfferBtn" style="display:none; background: #ff4757; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 12px; font-weight: bold;">
            <i class="fas fa-times-circle"></i> Refuse Offer
          </button>
          <span class="close-modal" id="closeDiscussionBtn">&times;</span>
        </div>
      </div>
      
      <div class="discussion-messages" id="discussionMessages">
        <!-- Messages will be loaded here -->
      </div>
      
      <div class="discussion-input">
        <input type="text" id="discussionInput" placeholder="Type your message or make an offer...">
        <input type="file" id="discussionImageInput" accept="image/*" style="display: none;">
        <button id="attachImageBtn" title="Attach image"><i class="fas fa-image"></i></button>
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

    // Handle edit form submission with AJAX
    document.getElementById('editForm').addEventListener('submit', function(e) {
      e.preventDefault();
      
      const formData = new FormData(this);
      
      fetch('trading.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Close modal without page redirect
          document.getElementById('editModal').classList.remove('active');
          
          // Refresh the page data (or update the specific skin card)
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


    let currentSkinId = null;
    let messageRefreshInterval = null;
    const currentLoggedInUser = '<?php echo htmlspecialchars($currentUser); ?>';
    
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
      formData.append('active_user_check', currentLoggedInUser);
      
      console.log('Loading messages for skin ID:', currentSkinId);
      
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
          
          if (data.messages && data.messages.length > 0) {
            console.log('First message object:', data.messages[0]);
          }
          
          if (data.success) {
            displayMessages(data.messages || []);
            console.log('isSeller:', data.isSeller, 'canMessage:', data.canMessage);
            updateDiscussionInputState(data.canMessage);

            // Show Refuse button if there are messages and user is seller
            const refuseBtn = document.getElementById('refuseOfferBtn');
            if (data.messages && data.messages.length > 0 && data.isSeller) {
                refuseBtn.style.display = 'block';
            } else {
                refuseBtn.style.display = 'none';
            }

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

    // Handle Refuse Offer Logic
    function handleRefuseOffer() {
        if (!currentSkinId) return;
        if (!confirm('Are you sure you want to refuse/cancel this negotiation? This will reset the conversation and unlock the skin for others.')) {
            return;
        }

        const formData = new FormData();
        formData.append('refuse_offer', '1');
        formData.append('skin_id', currentSkinId);
        formData.append('active_user_check', currentLoggedInUser);

        fetch('trading.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Negotiation refused/cancelled. Skin is now unlocked.');
                document.getElementById('discussionModal').classList.remove('active');
                location.reload();
            } else {
                alert('Error: ' + (data.error || 'Failed to refuse offer'));
            }
        })
        .catch(error => {
            console.error('Error reusing offer:', error);
            alert('Failed to refuse offer');
        });
    }

    document.getElementById('refuseOfferBtn').addEventListener('click', handleRefuseOffer);
    
    function updateDiscussionInputState(canMessage) {
      const discussionInput = document.getElementById('discussionInput');
      const sendBtn = document.getElementById('sendDiscussionBtn');
      
      if (!discussionInput || !sendBtn) return;
      
      // Convert to boolean to handle both true/false and 1/0 and truthy/falsy
      const canMessageBool = Boolean(canMessage);
      
      console.log('updateDiscussionInputState - canMessage:', canMessageBool);
      
      if (!canMessageBool) {
        // Cannot message - disable input
        console.log('Disabling input: seller waiting for buyer to message first');
        discussionInput.disabled = true;
        discussionInput.placeholder = 'Wait for buyers to message you first...';
        sendBtn.disabled = true;
        sendBtn.style.opacity = '0.5';
        sendBtn.style.cursor = 'not-allowed';
      } else {
        // Can message - enable input
        console.log('Enabling input: ready to message');
        discussionInput.disabled = false;
        discussionInput.placeholder = 'Type your message or make an offer...';
        sendBtn.disabled = false;
        sendBtn.style.opacity = '1';
        sendBtn.style.cursor = 'pointer';
      }
    }


    function displayMessages(messages) {
      const messagesContainer = document.getElementById('discussionMessages');
      if (!messagesContainer) {
        console.error('Messages container not found');
        return;
      }
      
      const wasAtBottom = messagesContainer.scrollHeight - messagesContainer.scrollTop <= messagesContainer.clientHeight + 50;
      
      messagesContainer.innerHTML = '';
      
      // Ensure messages is an array
      if (!Array.isArray(messages)) {
        console.error('Messages is not an array:', messages, 'typeof:', typeof messages);
        messages = [];
      }
      
      console.log('displayMessages called with', messages.length, 'messages');
      
      if (messages.length === 0) {
        console.log('No messages to display, showing welcome message');
        const welcomeDiv = document.createElement('div');
        welcomeDiv.className = 'offer-section';
        welcomeDiv.innerHTML = `
          <strong>📦 Trade Offer</strong>
          <p>Start the conversation by making an offer or asking a question!</p>
        `;
        messagesContainer.appendChild(welcomeDiv);
        return;
      }
      
      console.log('Displaying', messages.length, 'messages');
      messages.forEach((message, index) => {
        console.log('Processing message', index, ':', message);
        // Allow either message text or image (or both)
        if (!message || (!message.message && !message.image_path)) {
          console.error('Invalid message object at index', index, ':', message);
          console.log('Message keys:', message ? Object.keys(message) : 'null');
          return;
        }
        
        const messageDiv = document.createElement('div');
        const isSent = message.sender_username === currentLoggedInUser;
        messageDiv.className = `message ${isSent ? 'sent' : 'received'}`;
        console.log('Message from', message.sender_username, '- isSent:', isSent, '- currentUser:', currentLoggedInUser);
        
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
          <div style="font-size: 12px; color: ${isSent ? '#fff' : '#ff7a00'}; font-weight: 600; margin-bottom: 5px;">
            ${isSent ? 'You (' + currentLoggedInUser + ')' : escapeHtml(message.sender_username || 'Unknown')}
          </div>
          ${message.message ? '<div>' + escapeHtml(message.message) + '</div>' : ''}
          ${message.image_path ? '<div style="margin: 8px 0;"><img src="../' + escapeHtml(message.image_path) + '" style="max-width: 100%; max-height: 250px; border-radius: 8px; cursor: pointer;" onclick="window.open(this.src, \'_blank\');"></div>' : ''}
          <div class="message-time">${timeString || 'Just now'}</div>
        `;
        
        messagesContainer.appendChild(messageDiv);
        console.log('Message appended to container');
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

    // Image attachment
    document.getElementById('attachImageBtn').addEventListener('click', () => {
      document.getElementById('discussionImageInput').click();
    });

    document.getElementById('discussionImageInput').addEventListener('change', (e) => {
      const file = e.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = (event) => {
          // Show preview
          let preview = document.getElementById('discussionImagePreview');
          if (!preview) {
            preview = document.createElement('div');
            preview.id = 'discussionImagePreview';
            preview.style.cssText = 'margin-top: 10px; position: relative;';
            document.querySelector('.discussion-input').parentElement.insertBefore(preview, document.querySelector('.discussion-input'));
          }
          preview.innerHTML = `
            <img src="${event.target.result}" style="max-width: 150px; max-height: 150px; border-radius: 8px;">
            <button type="button" onclick="document.getElementById('discussionImageInput').value = ''; document.getElementById('discussionImagePreview').style.display = 'none';" style="position: absolute; top: 0; right: 0; background: #ff4757; color: white; border: none; border-radius: 50%; width: 24px; height: 24px; cursor: pointer; font-size: 12px;">✕</button>
          `;
          preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
      }
    });

    function sendDiscussionMessage() {
      const input = document.getElementById('discussionInput');
      const imageInput = document.getElementById('discussionImageInput');
      const message = input.value.trim();
      const hasImage = imageInput.files && imageInput.files.length > 0;
      
      // Allow either message or image
      if (!message && !hasImage) {
        return;
      }

      // Only validate message if there is one
      if (message && !validateDiscussionMessage(input)) {
        return;
      }
      
      if (!currentSkinId) {
        return;
      }
      
      const formData = new FormData();
      formData.append('send_message', '1');
      formData.append('skin_id', currentSkinId);
      formData.append('message', message);
      formData.append('active_user_check', currentLoggedInUser);
      
      // Add image if present
      if (hasImage) {
        formData.append('image', imageInput.files[0]);
      }
      
      fetch('trading.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        console.log('Send message response:', data); 
        if (data.success) {
          input.value = '';
          imageInput.value = '';
          
          // Update image preview if any
          const imagePreview = document.getElementById('discussionImagePreview');
          if (imagePreview) {
            imagePreview.style.display = 'none';
          }

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

    

    


    



    const flash = document.querySelector('.flash');
    if (flash) {
      setTimeout(() => {
        flash.style.opacity = '0';
        setTimeout(() => flash.remove(), 400);
      }, 3500);
    }


    // Cart Logic
    function updateCartCount() {
        const cart = JSON.parse(localStorage.getItem('cart')) || [];
        const cartCount = document.querySelector('.cart-count');
        if (cartCount) {
            cartCount.textContent = cart.length;
        }
    }

    // Initialize cart count
    updateCartCount();

    // Handle Buy Button Click
    document.querySelectorAll('.buy-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            if (this.disabled || this.classList.contains('disabled')) return;
            
            const card = this.closest('.skin-card');
            const name = card.querySelector('.skin-name').textContent;
            const price = card.querySelector('.skin-price').textContent.replace('$', '');
            const image = card.querySelector('.skin-img').src;
            // Extract ID from data-id attribute
            const id = card.getAttribute('data-id');

            const item = {
                id: id,
                name: name,
                price: price,
                image: image
            };
            
            const cart = JSON.parse(localStorage.getItem('cart')) || [];
            
            // Check if item already exists in cart
            const existingItem = cart.find(cartItem => cartItem.id === id);
            if (existingItem) {
                // Item already in cart
                this.innerHTML = '<i class="fas fa-exclamation-circle"></i> Already in Cart';
                this.style.background = '#ffa500';
                setTimeout(() => {
                    this.innerHTML = originalText;
                    this.style.background = '';
                }, 1500);
                return;
            }
            
            cart.push(item);
            localStorage.setItem('cart', JSON.stringify(cart));
            
            updateCartCount();
            
            // Optional: Show feedback
            const originalText = this.innerHTML;
            this.innerHTML = '<i class="fas fa-check"></i> Added';
            this.style.background = '#2ed573';
            setTimeout(() => {
                this.innerHTML = originalText;
                this.style.background = '';
            }, 1000);
        });
    });

  </script>
</body>
</html>