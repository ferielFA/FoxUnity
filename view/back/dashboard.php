<?php
// Charger d'abord config/database.php pour définir la classe Database "officielle"
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../model/config.php';

require_once __DIR__ . '/../../controller/UserController.php';
require_once __DIR__ . '/../../controller/AdminTradingController.php';
require_once __DIR__ . '/../../controller/ReclamationController.php';
require_once __DIR__ . '/../../controller/EvenementController.php';

// Vérification de l'authentification et des droits
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

// Récupération de l'image utilisateur
$userImage = null;
if ($currentUser->getImage()) {
  $userImage = '../../view/' . $currentUser->getImage();
}

// --- RECUPERATION DES STATISTIQUES ---
$allUsers = User::getAll();
$totalUsers = count($allUsers);

require_once __DIR__ . '/../../controller/TradeHistoryController.php';
$tradeHistoryController = new TradeHistoryController();
$tradingStats = $tradeHistoryController->getStatistics();

$reclamationController = new ReclamationController();
$pendingReclamations = count($reclamationController->getAllReclamations('nouveau'));
$resolvedReclamations = count($reclamationController->getAllReclamations('resolu'));

$evenementController = new EvenementController();
$allEvents = $evenementController->lireTous();
$totalEvents = count($allEvents);
$upcomingEvents = 0;
$today = new DateTime();
foreach ($allEvents as $evtData) {
  if ($evtData['evenement']->getDateDebut() > $today) {
    $upcomingEvents++;
  }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Nine Tailed Fox - Admin Dashboard</title>
  <link rel="stylesheet" href="style.css">
  <link
    href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Poppins:wght@300;400;600;700&display=swap"
    rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <style>
    /* ========== LAYOUT VERTICAL AVEC SCROLL ========== */

    .content {
      max-width: 1200px;
      margin: 0 auto;
    }

    /* Section Container */
    .dashboard-section {
      margin-bottom: 40px;
      animation: fadeInUp 0.5s ease;
    }

    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(20px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .section-header {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 20px;
      padding-bottom: 12px;
      border-bottom: 2px solid rgba(255, 122, 0, 0.2);
    }

    .section-icon {
      width: 40px;
      height: 40px;
      border-radius: 10px;
      background: linear-gradient(135deg, rgba(255, 122, 0, 0.2), rgba(255, 79, 0, 0.1));
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 20px;
      color: #ff7a00;
    }

    .section-title {
      font-family: 'Orbitron', sans-serif;
      font-size: 20px;
      font-weight: 700;
      color: white;
      flex: 1;
    }

    .section-subtitle {
      color: #666;
      font-size: 13px;
      margin-left: 52px;
      margin-top: -12px;
      margin-bottom: 20px;
    }

    /* Stat Card - Une seule carte par section */
    .stat-card-single {
      background: linear-gradient(135deg, rgba(20, 20, 35, 0.95) 0%, rgba(10, 10, 20, 0.9) 100%);
      border: 1px solid rgba(255, 122, 0, 0.3);
      border-radius: 20px;
      padding: 32px;
      display: flex;
      align-items: center;
      gap: 30px;
      transition: all 0.3s ease;
      cursor: pointer;
      position: relative;
      overflow: hidden;
    }

    .stat-card-single::before {
      content: '';
      position: absolute;
      left: 0;
      top: 0;
      bottom: 0;
      width: 5px;
      background: linear-gradient(180deg, #ff7a00, #ff4f00);
    }

    .stat-card-single:hover {
      border-color: #ff7a00;
      box-shadow: 0 10px 40px rgba(255, 122, 0, 0.3);
      transform: translateX(8px);
    }

    .stat-icon-large {
      width: 90px;
      height: 90px;
      min-width: 90px;
      border-radius: 20px;
      background: linear-gradient(135deg, rgba(255, 122, 0, 0.15), rgba(255, 79, 0, 0.05));
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 42px;
      color: #ff7a00;
      border: 2px solid rgba(255, 122, 0, 0.2);
    }

    .stat-content-main {
      flex: 1;
    }

    .stat-label-main {
      color: #888;
      font-size: 13px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 1.5px;
      margin-bottom: 12px;
    }

    .stat-value-main {
      font-size: 48px;
      font-weight: 900;
      color: white;
      font-family: 'Orbitron', sans-serif;
      line-height: 1;
      margin-bottom: 12px;
    }

    .stat-details {
      display: flex;
      gap: 16px;
      flex-wrap: wrap;
    }

    .stat-badge {
      padding: 6px 14px;
      border-radius: 10px;
      font-size: 12px;
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }

    .badge-success {
      background: rgba(76, 175, 80, 0.15);
      color: #4caf50;
      border: 1px solid rgba(76, 175, 80, 0.3);
    }

    .badge-info {
      background: rgba(33, 150, 243, 0.15);
      color: #2196f3;
      border: 1px solid rgba(33, 150, 243, 0.3);
    }

    .badge-warning {
      background: rgba(255, 152, 0, 0.15);
      color: #ff9800;
      border: 1px solid rgba(255, 152, 0, 0.3);
    }

    .badge-purple {
      background: rgba(156, 39, 176, 0.15);
      color: #9c27b0;
      border: 1px solid rgba(156, 39, 176, 0.3);
    }

    /* Charts Section - Full Width */
    .charts-section {
      margin-top: 50px;
      margin-bottom: 40px;
    }

    .chart-container {
      background: linear-gradient(135deg, rgba(20, 20, 35, 0.95) 0%, rgba(10, 10, 20, 0.9) 100%);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 20px;
      padding: 32px;
      margin-bottom: 30px;
    }

    .chart-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 30px;
    }

    .chart-title {
      font-family: 'Orbitron', sans-serif;
      font-size: 20px;
      font-weight: 700;
      color: white;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .chart-title i {
      color: #ff7a00;
      font-size: 24px;
    }

    .chart-subtitle {
      color: #666;
      font-size: 13px;
      margin-top: 6px;
    }

    /* Quick Actions */
    .actions-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 20px;
    }

    .action-card {
      background: rgba(255, 255, 255, 0.03);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 16px;
      padding: 24px;
      text-align: center;
      transition: all 0.3s ease;
      cursor: pointer;
      text-decoration: none;
      color: white;
    }

    .action-card:hover {
      background: rgba(255, 122, 0, 0.1);
      border-color: rgba(255, 122, 0, 0.4);
      transform: translateY(-5px);
      box-shadow: 0 10px 30px rgba(255, 122, 0, 0.25);
    }

    .action-icon {
      width: 60px;
      height: 60px;
      margin: 0 auto 16px;
      border-radius: 15px;
      background: linear-gradient(135deg, rgba(255, 122, 0, 0.15), rgba(255, 79, 0, 0.05));
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 28px;
      color: #ff7a00;
    }

    .action-label {
      font-size: 14px;
      font-weight: 600;
      color: #ccc;
    }

    /* Responsive */
    @media (max-width: 768px) {
      .stat-card-single {
        flex-direction: column;
        text-align: center;
      }

      .stat-icon-large {
        width: 80px;
        height: 80px;
        min-width: 80px;
        font-size: 36px;
      }

      .stat-value-main {
        font-size: 40px;
      }

      .actions-grid {
        grid-template-columns: repeat(2, 1fr);
      }
    }

    @media (max-width: 480px) {
      .actions-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>

<body class="dashboard-body">
  <div class="stars"></div>

  <!-- ===== SIDEBAR ===== -->
  <div class="sidebar">
    <img src="../images/Nine__1_-removebg-preview.png" alt="Nine Tailed Fox Logo" class="dashboard-logo">
    <h2>Dashboard</h2>
    <a href="dashboard.php" class="active">Overview</a>
    <a href="users.php">Users</a>
    <a href="shopb.php">Shop</a>
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
    <!-- TOPBAR -->
    <div class="topbar">
      <h1>Dashboard Overview</h1>
      <div class="topbar-right" style="display: flex; align-items: center; gap: 20px;">
        <?php include __DIR__ . '/includes/notifications.php'; ?>

        <div class="admin-dropdown" id="adminDropdown">
          <div class="user admin-user">
            <?php if ($userImage): ?>
              <img src="<?php echo htmlspecialchars($userImage); ?>" alt="Admin Avatar">
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

      <!-- ========== SECTION 1: USERS ========== -->
      <div class="dashboard-section">
        <div class="section-header">
          <div class="section-icon">
            <i class="fas fa-users"></i>
          </div>
          <div class="section-title">Users Management</div>
        </div>
        <div class="section-subtitle">Total registered users and community statistics</div>

        <div class="stat-card-single" onclick="window.location.href='users.php'">
          <div class="stat-icon-large">
            <i class="fas fa-users"></i>
          </div>
          <div class="stat-content-main">
            <div class="stat-label-main">Total Users</div>
            <div class="stat-value-main"><?= number_format($totalUsers) ?></div>
            <div class="stat-details">
              <span class="stat-badge badge-success">
                <i class="fas fa-arrow-up"></i> Active Community
              </span>
              <span class="stat-badge badge-info">
                <i class="fas fa-user-check"></i> All Verified
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- ========== SECTION 2: TRADING ========== -->
      <div class="dashboard-section">
        <div class="section-header">
          <div class="section-icon">
            <i class="fas fa-exchange-alt"></i>
          </div>
          <div class="section-title">Trading Activity</div>
        </div>
        <div class="section-subtitle">Total trading volume and transaction statistics</div>

        <div class="stat-card-single" onclick="window.location.href='tradingb.php'">
          <div class="stat-icon-large">
            <i class="fas fa-exchange-alt"></i>
          </div>
          <div class="stat-content-main">
            <div class="stat-label-main">Trading Volume</div>
            <div class="stat-value-main">$<?= number_format($tradingStats['total_value'], 0) ?></div>
            <div class="stat-details">
              <span class="stat-badge badge-info">
                <i class="fas fa-handshake"></i> <?= $tradingStats['total_trades'] ?> Transactions
              </span>
              <span class="stat-badge badge-purple">
                <i class="fas fa-users"></i> <?= $tradingStats['total_users'] ?> Active Traders
              </span>
              <span class="stat-badge badge-success">
                <i class="fas fa-box"></i> <?= $tradingStats['total_skins'] ?> Items Traded
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- ========== SECTION 3: SHOP ========== -->
      <div class="dashboard-section">
        <div class="section-header">
          <div class="section-icon">
            <i class="fas fa-store"></i>
          </div>
          <div class="section-title">Shop & Marketplace</div>
        </div>
        <div class="section-subtitle">Active listings and marketplace statistics</div>

        <div class="stat-card-single" onclick="window.location.href='shopb.php'">
          <div class="stat-icon-large">
            <i class="fas fa-store"></i>
          </div>
          <div class="stat-content-main">
            <div class="stat-label-main">Active Listings</div>
            <div class="stat-value-main"><?= number_format($tradingStats['total_skins']) ?></div>
            <div class="stat-details">
              <span class="stat-badge badge-info">
                <i class="fas fa-tags"></i> Marketplace Items
              </span>
              <span class="stat-badge badge-success">
                <i class="fas fa-check-circle"></i> Available Now
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- ========== SECTION 4: SUPPORT ========== -->
      <div class="dashboard-section">
        <div class="section-header">
          <div class="section-icon">
            <i class="fas fa-headset"></i>
          </div>
          <div class="section-title">Support & Tickets</div>
        </div>
        <div class="section-subtitle">Customer support tickets and resolution status</div>

        <div class="stat-card-single" onclick="window.location.href='reclamback.php'">
          <div class="stat-icon-large">
            <i class="fas fa-headset"></i>
          </div>
          <div class="stat-content-main">
            <div class="stat-label-main">Total Support Tickets</div>
            <div class="stat-value-main"><?= $pendingReclamations + $resolvedReclamations ?></div>
            <div class="stat-details">
              <?php if ($pendingReclamations > 0): ?>
                <span class="stat-badge badge-warning">
                  <i class="fas fa-clock"></i> <?= $pendingReclamations ?> Pending
                </span>
              <?php endif; ?>
              <span class="stat-badge badge-success">
                <i class="fas fa-check-double"></i> <?= $resolvedReclamations ?> Resolved
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- ========== SECTION 5: EVENTS ========== -->
      <div class="dashboard-section">
        <div class="section-header">
          <div class="section-icon">
            <i class="fas fa-calendar-alt"></i>
          </div>
          <div class="section-title">Events & Tournaments</div>
        </div>
        <div class="section-subtitle">Total events created and upcoming schedules</div>

        <div class="stat-card-single" onclick="window.location.href='eventsb.php'">
          <div class="stat-icon-large">
            <i class="fas fa-calendar-alt"></i>
          </div>
          <div class="stat-content-main">
            <div class="stat-label-main">Total Events</div>
            <div class="stat-value-main"><?= $totalEvents ?></div>
            <div class="stat-details">
              <span class="stat-badge badge-info">
                <i class="fas fa-fire"></i> <?= $upcomingEvents ?> Upcoming
              </span>
              <span class="stat-badge badge-success">
                <i class="fas fa-trophy"></i> Community Tournaments
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- ========== CHARTS SECTION ========== -->
      <div class="charts-section">
        <div class="section-header">
          <div class="section-icon">
            <i class="fas fa-chart-line"></i>
          </div>
          <div class="section-title">Analytics & Statistics</div>
        </div>
        <div class="section-subtitle">Platform activity distribution and trading trends</div>

        <!-- Activity Distribution Chart -->
        <div class="chart-container">
          <div class="chart-header">
            <div>
              <div class="chart-title">
                <i class="fas fa-chart-pie"></i>
                Activity Distribution
              </div>
              <div class="chart-subtitle">Platform usage breakdown by category</div>
            </div>
          </div>
          <canvas id="distributionChart" style="max-height: 350px;"></canvas>
        </div>

        <!-- Trading Volume Chart -->
        <div class="chart-container">
          <div class="chart-header">
            <div>
              <div class="chart-title">
                <i class="fas fa-chart-area"></i>
                Trading Volume Trend
              </div>
              <div class="chart-subtitle">Last 6 months trading performance</div>
            </div>
          </div>
          <canvas id="volumeChart" style="max-height: 350px;"></canvas>
        </div>
      </div>

      <!-- ========== QUICK ACTIONS ========== -->
      <div class="dashboard-section">
        <div class="section-header">
          <div class="section-icon">
            <i class="fas fa-bolt"></i>
          </div>
          <div class="section-title">Quick Actions</div>
        </div>
        <div class="section-subtitle">Common administrative tasks and shortcuts</div>

        <div class="actions-grid">
          <a href="users.php" class="action-card">
            <div class="action-icon">
              <i class="fas fa-user-plus"></i>
            </div>
            <div class="action-label">Manage Users</div>
          </a>

          <a href="shopb.php" class="action-card">
            <div class="action-icon">
              <i class="fas fa-plus-circle"></i>
            </div>
            <div class="action-label">Add Product</div>
          </a>

          <a href="eventsb.php" class="action-card">
            <div class="action-icon">
              <i class="fas fa-calendar-plus"></i>
            </div>
            <div class="action-label">Create Event</div>
          </a>

          <a href="reclamback.php" class="action-card">
            <div class="action-icon">
              <i class="fas fa-comments"></i>
            </div>
            <div class="action-label">View Support</div>
          </a>
        </div>
      </div>

    </div>
  </div>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script>
    // Toggle Admin Dropdown
    const adminUser = document.querySelector('.admin-user');
    const adminDropdownMenu = document.querySelector('.admin-dropdown-menu');

    if (adminUser && adminDropdownMenu) {
      adminUser.addEventListener('click', (e) => {
        e.stopPropagation();
        adminDropdownMenu.classList.toggle('show');
      });

      document.addEventListener('click', (e) => {
        if (!adminDropdownMenu.contains(e.target) && !adminUser.contains(e.target)) {
          adminDropdownMenu.classList.remove('show');
        }
      });
    }

    // Chart 1: Activity Distribution
    const ctxDist = document.getElementById('distributionChart').getContext('2d');
    new Chart(ctxDist, {
      type: 'doughnut',
      data: {
        labels: ['Users', 'Trades', 'Events', 'Support'],
        datasets: [{
          data: [
            <?= $totalUsers ?>,
            <?= $tradingStats['total_trades'] ?>,
            <?= $totalEvents ?>,
            <?= $pendingReclamations + $resolvedReclamations ?>
          ],
          backgroundColor: [
            'rgba(33, 150, 243, 0.85)',
            'rgba(156, 39, 176, 0.85)',
            'rgba(76, 175, 80, 0.85)',
            'rgba(255, 122, 0, 0.85)'
          ],
          borderColor: 'rgba(255, 255, 255, 0.1)',
          borderWidth: 2
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              color: '#fff',
              padding: 20,
              font: { size: 13, family: "'Poppins', sans-serif" }
            }
          }
        }
      }
    });

    // Chart 2: Trading Volume
    const ctxVol = document.getElementById('volumeChart').getContext('2d');
    new Chart(ctxVol, {
      type: 'line',
      data: {
        labels: ['Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        datasets: [{
          label: 'Trading Volume ($)',
          data: [1200, 1900, 3000, 5000, 2300, <?= $tradingStats['total_value'] > 0 ? $tradingStats['total_value'] : 4500 ?>],
          borderColor: '#ff7a00',
          backgroundColor: 'rgba(255, 122, 0, 0.15)',
          tension: 0.4,
          fill: true,
          pointBackgroundColor: '#ff7a00',
          pointBorderColor: '#fff',
          pointBorderWidth: 2,
          pointRadius: 6,
          pointHoverRadius: 8
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
          legend: {
            labels: {
              color: '#fff',
              font: { size: 13, family: "'Poppins', sans-serif" }
            }
          }
        },
        scales: {
          y: {
            ticks: { color: '#aaa', font: { size: 12 } },
            grid: { color: 'rgba(255, 255, 255, 0.05)' }
          },
          x: {
            ticks: { color: '#aaa', font: { size: 12 } },
            grid: { color: 'rgba(255, 255, 255, 0.05)' }
          }
        }
      }
    });
  </script>
</body>

</html>