<?php
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
$tradingController = new AdminTradingController();
$tradingData = $tradingController->getViewData();
$tradeHistory = $tradingData['tradeHistory'];
$stats = $tradingData['stats'];
$tradingError = $tradingData['error'];

// Check if we should show trade history (from URL parameter or default)
$showTradeHistory = isset($_GET['section']) && $_GET['section'] === 'trades';
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Nine Tailed Fox Dashboard</title>
  <link rel="stylesheet" href="style.css">
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Poppins:wght@300;600&display=swap"
    rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

  <style>
    .overview-section {
      display:
        <?= !$showTradeHistory ? 'grid' : 'none' ?>
        !important;
    }

    .trades-section {
      display:
        <?= $showTradeHistory ? 'block' : 'none' ?>
        !important;
    }
  </style>
</head>

<body class="dashboard-body">
  <div class="stars"></div>
  <div class="shooting-star"></div>
  <div class="shooting-star"></div>
  <div class="shooting-star"></div>

  <!-- ===== SIDEBAR ===== -->
  <div class="sidebar">
    <img src="../images/Nine__1_-removebg-preview.png" alt="Nine Tailed Fox Logo" class="dashboard-logo">
    <h2>Dashboard</h2>
    <a href="dashboard.php" class="<?= !$showTradeHistory ? 'active' : '' ?>">Overview</a>
    <a href="users.php">Users</a>
    <a href="#">Shop</a>
    <a href="tradingb.php" class="<?= $showTradeHistory ? 'active' : '' ?>">Trade History</a>
    <a href="eventsb.php">Events</a>
    <a href="news_admin.php">News</a>
    <a href="news_history.php">News History</a>
    <a href="categories.php">Categories</a>
    <a href="newsletter_admin.php">Newsletter</a>
    <a href="reclamback.php">Support</a>
    <a href="evaluations_publiques.php">Évaluations Publiques</a>
    <a href="../front/index.php">← Return Homepage</a>
  </div>

  <!-- ===== MAIN ===== -->
  <div class="main">
    <div class="topbar">
      <h1><?= $showTradeHistory ? 'Trade History Dashboard' : 'Welcome, Commander' ?></h1>
      <div class="admin-dropdown" id="adminDropdown">
        <div class="user admin-user">
          <?php if ($userImage): ?>
            <img src="<?php echo htmlspecialchars($userImage); ?>" alt="Admin Avatar">
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

    <!-- Overview Section -->
    <div class="content overview-section">
      <div class="card" onclick="window.location.href='users.php'">
        <h3>Users</h3>
        <p>Manage player accounts, view activity levels, and assign roles. Monitor active members in real time.</p>
      </div>

      <div class="card">
        <h3>Shop Overview</h3>
        <p>View current stock, promotions, and trade offers. Adjust pricing and featured items instantly.</p>
      </div>

      <div class="card" onclick="window.location.href='tradingb.php'">
        <h3>Trade History</h3>
        <p>Review completed trades, pending exchanges, and item transactions between players.</p>
      </div>

      <div class="card">
        <h3>Events</h3>
        <p>Track current and upcoming tournaments, seasonal events, and community missions.</p>
      </div>

      <div class="card">
        <h3>News Feed</h3>
        <p>Stay updated with game patches, esports news, and upcoming tournaments.</p>
      </div>

      <div class="card">
        <h3>Support</h3>
        <p>Check user feedback, analyze satisfaction trends, and respond to the community.</p>
      </div>
    </div>

    <!-- Trading History Section -->
    <div class="content trades-section">
      <!-- Statistics -->
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

      <!-- Trade History Table -->
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

        <?php if (isset($tradingError)): ?>
          <div
            style="background: #ff4757; color: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center;">
            <?= htmlspecialchars($tradingError) ?>
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
                    case 'created':
                      $actionClass = 'action-created';
                      break;
                    case 'updated':
                      $actionClass = 'action-updated';
                      break;
                    case 'deleted':
                      $actionClass = 'action-deleted';
                      break;
                    case 'finished':
                      $actionClass = 'action-finished';
                      break;
                  }
                  ?>
                  <tr class="history-row" data-action="<?= $history['action'] ?>"
                    data-game="<?= $history['skin_category'] ?>">
                    <td><?= date('M j, Y g:i A', strtotime($history['created_at'])) ?></td>
                    <td class="username">@<?= htmlspecialchars($history['username']) ?></td>
                    <td><span class="action-badge <?= $actionClass ?>"><?= ucfirst($history['action']) ?></span></td>
                    <td><?= htmlspecialchars($history['skin_name']) ?></td>
                    <td class="price">$<?= number_format((float) $history['skin_price'], 2) ?></td>
                    <td class="skin-game <?= $history['skin_category'] ?>">
                      <?= htmlspecialchars(ucfirst($history['skin_category'])) ?></td>
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

  <!-- ===== PAGE TRANSITION OVERLAY ===== -->
  <div class="transition-screen"></div>

  <script>
    // Dropdown Menu Toggle
    document.addEventListener('DOMContentLoaded', function () {
      const adminDropdown = document.getElementById('adminDropdown');

      if (adminDropdown) {
        const adminUser = adminDropdown.querySelector('.admin-user');

        // Toggle dropdown on click
        adminUser.addEventListener('click', function (e) {
          e.stopPropagation();
          adminDropdown.classList.toggle('active');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function (e) {
          if (!adminDropdown.contains(e.target)) {
            adminDropdown.classList.remove('active');
          }
        });

        // Close dropdown when pressing Escape
        document.addEventListener('keydown', function (e) {
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


    // Trading History Filtering (only if trades section is visible)
    <?php if ($showTradeHistory): ?>
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

      if (searchInput) searchInput.addEventListener('input', filterHistory);
      if (actionFilter) actionFilter.addEventListener('change', filterHistory);
      if (gameFilter) gameFilter.addEventListener('change', filterHistory);

      filterHistory();
    <?php endif; ?>
  </script>

</body>

</html>