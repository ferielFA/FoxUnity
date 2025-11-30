<?php

declare(strict_types=1);
require_once __DIR__ . '/../../controller/UserController.php';
require_once __DIR__ . '/../../controller/AdminTradingController.php';

// Check if user is logged in and is Admin or SuperAdmin
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

// Get user image - NO DEFAULT IMAGE
$userImage = null;
if ($currentUser->getImage()) {
    $userImage = '../../view/' . $currentUser->getImage();
}

// Get trading data
$controller = new AdminTradingController();
$viewData = $controller->getViewData();
$tradeHistory = $viewData['tradeHistory'];
$stats = $viewData['stats'];
$error = $viewData['error'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Nine Tailed Fox | Trade History</title>
  <link rel="stylesheet" href="style.css">
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Poppins:wght@300;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  
  <style>
    /* Admin Dropdown Styles - COHERENT */
    .admin-dropdown {
      position: relative;
      display: inline-block;
    }

    .admin-user {
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 10px;
      transition: all 0.3s ease;
      padding: 5px 10px;
      border-radius: 8px;
    }

    .admin-user:hover {
      background: rgba(255, 122, 0, 0.1);
    }

    .admin-user img {
      width: 35px;
      height: 35px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid #ff7a00;
    }

    .admin-user i.fa-user-circle {
      font-size: 35px;
      color: #ff7a00;
    }

    .admin-user span {
      color: #fff;
      font-weight: 600;
      font-size: 16px;
    }

    .admin-user i.fa-chevron-down {
      font-size: 12px;
      color: #ff7a00;
      transition: transform 0.3s ease;
    }

    .admin-dropdown.active .admin-user i.fa-chevron-down {
      transform: rotate(180deg);
    }

    .admin-dropdown-menu {
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

    .admin-dropdown.active .admin-dropdown-menu {
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

    /* Stats Grid */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    .stat-card {
        background: rgba(255, 122, 0, 0.1);
        border: 1px solid #ff7a00;
        border-radius: 12px;
        padding: 20px;
        text-align: center;
        transition: transform 0.3s ease;
    }
    .stat-card:hover {
        transform: translateY(-5px);
    }
    .stat-number {
        font-size: 2.5em;
        font-weight: bold;
        color: #ff7a00;
        margin: 10px 0;
        font-family: 'Orbitron', sans-serif;
    }
    .stat-label {
        color: #ccc;
        font-size: 0.9em;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
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
        background: rgba(0, 0, 0, 0.3);
        border-radius: 8px;
        overflow: hidden;
        margin-top: 20px;
    }
    .history-table th,
    .history-table td {
        padding: 15px;
        text-align: left;
        border-bottom: 1px solid #333;
    }
    .history-table th {
        background: rgba(255, 122, 0, 0.2);
        color: #fff;
        font-weight: 600;
        font-family: 'Orbitron', sans-serif;
    }
    .history-table tr:hover {
        background: rgba(255, 255, 255, 0.05);
    }
    .history-table tr:last-child td {
        border-bottom: none;
    }
    .action-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
    }
    .action-created {
        background: rgba(46, 213, 115, 0.2);
        color: #2ed573;
        border: 1px solid #2ed573;
    }
    .action-updated {
        background: rgba(255, 165, 0, 0.2);
        color: #ffa500;
        border: 1px solid #ffa500;
    }
    .action-deleted {
        background: rgba(255, 71, 87, 0.2);
        color: #ff4757;
        border: 1px solid #ff4757;
    }
    .action-finished {
        background: rgba(0, 123, 255, 0.2);
        color: #007bff;
        border: 1px solid #007bff;
    }
    .table-container {
        max-height: 600px;
        overflow-y: auto;
        border-radius: 12px;
    }
    .no-data {
        text-align: center;
        padding: 40px;
        color: #888;
    }
    .search-filter {
        display: flex;
        gap: 15px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }
    .search-filter input,
    .search-filter select {
        padding: 12px 15px;
        border-radius: 8px;
        border: 1px solid #333;
        background: #1a1a1a;
        color: #fff;
        min-width: 200px;
        font-family: 'Poppins', sans-serif;
    }
    .search-filter input:focus,
    .search-filter select:focus {
        outline: none;
        border-color: #ff7a00;
    }
    .price {
        color: #ff7a00;
        font-weight: bold;
        font-family: 'Orbitron', sans-serif;
    }
    .username {
        color: #4fc3f7;
        font-weight: 600;
    }
    .section-title {
        font-family: 'Orbitron', sans-serif;
        font-size: 2em;
        margin-bottom: 20px;
        color: #fff;
        text-align: center;
    }
    .section-title span {
        color: #ff7a00;
    }
    .history-filter {
        display: flex;
        gap: 10px;
        margin-bottom: 15px;
        flex-wrap: wrap;
    }
    .history-filter button {
        background: #333;
        color: #fff;
        border: none;
        padding: 10px 20px;
        border-radius: 6px;
        cursor: pointer;
        transition: background 0.3s;
        font-family: 'Poppins', sans-serif;
    }
    .history-filter button.active {
        background: #ff7a00;
        color: #000;
        font-weight: 600;
    }
    .history-filter button:hover:not(.active) {
        background: #555;
    }
    .skin-game {
        text-transform: capitalize;
        font-weight: 600;
    }
    .skin-game.valorant { color: #ff4655; }
    .skin-game.cs2 { color: #f9a602; }
    .skin-game.fortnite { color: #5bc0f8; }
    .skin-game.apex { color: #ea4335; }
    .skin-game.custom { color: #9c27b0; }
    .value-note {
        text-align: center;
        color: #888;
        font-size: 0.9em;
        margin-top: 10px;
        font-style: italic;
    }
    .stat-note {
        text-align: center;
        color: #888;
        font-size: 0.8em;
        margin-top: 5px;
        font-style: italic;
    }
    .content {
        display: block !important;
        padding: 30px 50px;
        overflow-y: auto;
    }
    .content .stats-grid {
        margin-bottom: 30px;
    }
    .content .trade-history-section {
        width: 100%;
    }
  </style>
</head>

<body class="dashboard-body">
  <!-- Background Effects -->
  <div class="stars"></div>
  <div class="shooting-star"></div>
  <div class="shooting-star"></div>
  <div class="shooting-star"></div>

  <!-- ===== SIDEBAR ===== -->
  <div class="sidebar">
    <img src="../images/Nine__1_-removebg-preview.png" alt="Nine Tailed Fox Logo" class="dashboard-logo">
    <h2>Dashboard</h2>
    <a href="dashboard.php">Overview</a>
    <a href="users.php">Users</a>
    <a href="#">Shop</a>
    <a href="tradingb.php" class="active">Trade History</a>
    <a href="#">Events</a>
    <a href="#">News</a>
    <a href="#">Support</a>
    <a href="../front/index.php">← Return Homepage</a>
  </div>

  <!-- ===== MAIN CONTENT ===== -->
  <div class="main">
    <div class="topbar">
      <h1>Trade History Dashboard</h1>
      <div class="admin-dropdown" id="adminDropdown">
        <div class="user admin-user">
          <?php if ($userImage): ?>
          <img src="<?php echo htmlspecialchars($userImage); ?>" alt="User Avatar">
          <?php else: ?>
          <i class="fas fa-user-circle"></i>
          <?php endif; ?>
          <span><?php echo htmlspecialchars($currentUser->getUsername()); ?></span>
          <i class="fas fa-chevron-down"></i>
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
      <!-- ===== STATISTICS ===== -->
      <div class="stats-grid">
          <div class="stat-card">
              <div class="stat-label">Total Trades</div>
              <div class="stat-number"><?= number_format($stats['total_trades']) ?></div>
              <div class="stat-note">All activities</div>
          </div>
          <div class="stat-card">
              <div class="stat-label">Active Users</div>
              <div class="stat-number"><?= number_format($stats['total_users']) ?></div>
              <div class="stat-note">Unique traders</div>
          </div>
          <div class="stat-card">
              <div class="stat-label">Active Skins</div>
              <div class="stat-number"><?= number_format($stats['total_skins']) ?></div>
              <div class="stat-note">Currently listed</div>
          </div>
          <div class="stat-card">
              <div class="stat-label">Total Value</div>
              <div class="stat-number">$<?= number_format($stats['total_value'], 2) ?></div>
              <div class="value-note">Finished trades only</div>
          </div>
      </div>

      <!-- ===== TRADE HISTORY SECTION ===== -->
      <section class="trade-history-section">
        <h2 class="section-title"><span>Trade</span> History</h2>
        <p style="color:#ccc; margin-bottom: 20px; text-align: center;">Complete overview of all trading activities</p>

        <div class="search-filter">
            <input type="text" id="searchInput" placeholder="Search by skin name or username...">
            <select id="actionFilter">
                <option value="all">All Actions</option>
                <option value="created">Created</option>
                <option value="updated">Updated</option>
                <option value="deleted">Deleted</option>
                <option value="finished">Finished</option>
            </select>
            <select id="gameFilter">
                <option value="all">All Games</option>
                <option value="valorant">Valorant</option>
                <option value="cs2">CS2</option>
                <option value="fortnite">Fortnite</option>
                <option value="apex">Apex Legends</option>
                <option value="custom">Custom</option>
            </select>
        </div>

        <div class="history-filter">
            <button class="filter-btn active" data-filter="all">All Activities</button>
            <button class="filter-btn" data-filter="created">Created</button>
            <button class="filter-btn" data-filter="updated">Updated</button>
            <button class="filter-btn" data-filter="deleted">Deleted</button>
            <button class="filter-btn" data-filter="finished">Finished</button>
        </div>

        <?php if (isset($error)): ?>
            <div style="background: #ff4757; color: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="table-container">
            <table class="history-table">
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Skin Name</th>
                        <th>Price</th>
                        <th>Game</th>
                        <th>Skin ID</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($tradeHistory) === 0): ?>
                        <tr>
                            <td colspan="7" class="no-data">
                                No trade history found. Trades will appear here when users create, update, or delete skins.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($tradeHistory as $history): 
                            $actionClass = '';
                            switch ($history['action']) {
                                case 'created': $actionClass = 'action-created'; break;
                                case 'updated': $actionClass = 'action-updated'; break;
                                case 'deleted': $actionClass = 'action-deleted'; break;
                                case 'finished': $actionClass = 'action-finished'; break;
                            }
                        ?>
                        <tr class="history-row" data-action="<?= $history['action'] ?>" data-game="<?= $history['skin_category'] ?>">
                            <td><?= date('M j, Y g:i A', strtotime($history['created_at'])) ?></td>
                            <td class="username">@<?= htmlspecialchars($history['username']) ?></td>
                            <td><span class="action-badge <?= $actionClass ?>"><?= ucfirst($history['action']) ?></span></td>
                            <td><?= htmlspecialchars($history['skin_name']) ?></td>
                            <td class="price">$<?= number_format((float)$history['skin_price'], 2) ?></td>
                            <td class="skin-game <?= $history['skin_category'] ?>"><?= htmlspecialchars(ucfirst($history['skin_category'])) ?></td>
                            <td>#<?= $history['skin_id'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
      </section>
    </div>

    <footer class="site-footer">
      © 2025 <span>Nine Tailed Fox</span>. All Rights Reserved.
    </footer>
  </div>

  <div class="transition-screen"></div>

  <script>
    // Dropdown Menu Toggle
    document.addEventListener('DOMContentLoaded', function() {
      const adminDropdown = document.getElementById('adminDropdown');
      
      if (adminDropdown) {
        const adminUser = adminDropdown.querySelector('.admin-user');
        
        adminUser.addEventListener('click', function(e) {
          e.stopPropagation();
          adminDropdown.classList.toggle('active');
        });
        
        document.addEventListener('click', function(e) {
          if (!adminDropdown.contains(e.target)) {
            adminDropdown.classList.remove('active');
          }
        });
        
        document.addEventListener('keydown', function(e) {
          if (e.key === 'Escape') {
            adminDropdown.classList.remove('active');
          }
        });
      }
    });

    // Page transitions
    window.addEventListener("load", () => {
      document.querySelector(".transition-screen").classList.add("hidden");
    });

    document.querySelectorAll("a").forEach(link => {
      link.addEventListener("click", e => {
        const href = link.getAttribute("href");
        if (href && !href.startsWith("#") && href !== "") {
          e.preventDefault();
          const transition = document.querySelector(".transition-screen");
          transition.classList.remove("hidden");
          setTimeout(() => {
            window.location.href = href;
          }, 700);
        }
      });
    });

    // Filter History
    const searchInput = document.getElementById('searchInput');
    const actionFilter = document.getElementById('actionFilter');
    const gameFilter = document.getElementById('gameFilter');
    const filterBtns = document.querySelectorAll('.filter-btn');

    function filterHistory() {
        const searchValue = searchInput.value.toLowerCase();
        const selectedAction = actionFilter.value;
        const selectedGame = gameFilter.value;

        document.querySelectorAll('.history-row').forEach(row => {
            const skinName = row.cells[3].textContent.toLowerCase();
            const username = row.cells[1].textContent.toLowerCase();
            const action = row.getAttribute('data-action');
            const game = row.getAttribute('data-game');

            const matchesSearch = skinName.includes(searchValue) || username.includes(searchValue);
            const matchesAction = selectedAction === 'all' || action === selectedAction;
            const matchesGame = selectedGame === 'all' || game === selectedGame;

            row.style.display = (matchesSearch && matchesAction && matchesGame) ? '' : 'none';
        });
    }

    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            filterBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            
            const filter = btn.getAttribute('data-filter');
            actionFilter.value = filter === 'all' ? 'all' : filter;
            filterHistory();
        });
    });

    searchInput.addEventListener('input', filterHistory);
    actionFilter.addEventListener('change', filterHistory);
    gameFilter.addEventListener('change', filterHistory);

    filterHistory();
  </script>
</body>
</html>