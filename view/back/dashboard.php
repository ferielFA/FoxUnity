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
// 1. Users
$allUsers = User::getAll();
$totalUsers = count($allUsers);

// 2. Trading Stats (Skins)
require_once __DIR__ . '/../../controller/TradeHistoryController.php';
$tradeHistoryController = new TradeHistoryController();
$tradingStats = $tradeHistoryController->getStatistics();

// 3. Shop Stats (Products)
require_once __DIR__ . '/../../controller/ProductController.php';
$productController = new ProductController();
$totalProducts = $productController->countProducts();
$shopPurchases = $productController->getPurchaseHistory();
$totalShopRevenue = 0;
foreach ($shopPurchases as $purchase) {
    $totalShopRevenue += (float)$purchase['amount'];
}

// 4. Combined Volume
$combinedVolume = $tradingStats['total_value'] + $totalShopRevenue;

// 5. Support Stats
$reclamationController = new ReclamationController();
$pendingTickets = $reclamationController->getAllReclamations('nouveau');
$inProgressTickets = $reclamationController->getAllReclamations('en_cours');
$pendingReclamations = count($pendingTickets) + count($inProgressTickets);
$resolvedReclamations = count($reclamationController->getAllReclamations('resolu'));

// 6. Events Stats
$evenementController = new EvenementController();
$allEvents = $evenementController->lireTous();
$totalEvents = count($allEvents);
$upcomingEvents = 0;
$today = new DateTime();
foreach ($allEvents as $evtData) {
    // Check if it's an array or object based on lireTous return
    $evtDateStr = is_array($evtData) && isset($evtData['evenement']) 
        ? $evtData['evenement']->getDateDebut() 
        : (isset($evtData['date_debut']) ? $evtData['date_debut'] : null);
    
    if ($evtDateStr) {
        $evtDate = ($evtDateStr instanceof DateTimeInterface) ? $evtDateStr : new DateTime($evtDateStr);
        if ($evtDate > $today) {
            $upcomingEvents++;
        }
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

    /* ========== LAYOUT GRID MODERN ========== */
    .dashboard-overview {
      display: flex;
      flex-direction: column;
      gap: 30px;
    }

    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }

    /* Stat Card - Compact for Grid */
    .stat-card-compact {
      background: linear-gradient(135deg, rgba(20, 20, 35, 0.9) 0%, rgba(10, 10, 20, 0.8) 100%);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 16px;
      padding: 24px;
      display: flex;
      flex-direction: column;
      gap: 15px;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      cursor: pointer;
      position: relative;
      overflow: hidden;
    }

    .stat-card-compact::before {
      content: '';
      position: absolute;
      left: 0;
      top: 0;
      bottom: 0;
      width: 4px;
      background: #ff7a00;
      opacity: 0.5;
      transition: opacity 0.3s ease;
    }

    .stat-card-compact:hover {
      border-color: #ff7a00;
      background: linear-gradient(135deg, rgba(30, 30, 50, 0.95) 0%, rgba(15, 15, 30, 0.9) 100%);
      transform: translateY(-5px);
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    }

    .stat-card-compact:hover::before {
      opacity: 1;
    }

    .stat-header-compact {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .stat-icon-compact {
      width: 45px;
      height: 45px;
      border-radius: 12px;
      background: rgba(255, 122, 0, 0.1);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 20px;
      color: #ff7a00;
    }

    .stat-info-compact {
      display: flex;
      flex-direction: column;
    }

    .stat-label-compact {
      color: #888;
      font-size: 12px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 1px;
    }

    .stat-value-compact {
      font-size: 32px;
      font-weight: 800;
      color: white;
      font-family: 'Orbitron', sans-serif;
      margin: 5px 0;
    }

    /* Charts Row */
    .charts-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 30px;
      margin-bottom: 30px;
    }

    .chart-box {
      background: linear-gradient(135deg, rgba(20, 20, 35, 0.95) 0%, rgba(10, 10, 20, 0.9) 100%);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 20px;
      padding: 24px;
    }

    @media (max-width: 1024px) {
      .charts-grid {
        grid-template-columns: 1fr;
      }
    }

    @media (max-width: 480px) {
      .stats-grid {
        grid-template-columns: 1fr;
      }
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
      <div class="dashboard-overview">
        <!-- ========== TOP METRICS GRID ========== -->
        <div class="stats-grid">
          <!-- Users -->
          <div class="stat-card-compact" onclick="window.location.href='users.php'">
            <div class="stat-header-compact">
              <div class="stat-label-compact">Users</div>
              <div class="stat-icon-compact"><i class="fas fa-users"></i></div>
            </div>
            <div class="stat-value-compact"><?= number_format($totalUsers) ?></div>
            <div class="stat-details">
              <span class="stat-badge badge-success" style="font-size: 10px; padding: 2px 8px;">
                <i class="fas fa-arrow-up"></i> Active
              </span>
            </div>
          </div>

          <!-- Trades -->
          <div class="stat-card-compact" onclick="window.location.href='tradingb.php'">
            <div class="stat-header-compact">
              <div class="stat-label-compact">Volume</div>
              <div class="stat-icon-compact"><i class="fas fa-money-bill-wave"></i></div>
            </div>
            <div class="stat-value-compact">$<?= number_format($combinedVolume, 0) ?></div>
            <div class="stat-details">
              <span class="stat-badge badge-info" style="font-size: 10px; padding: 2px 8px;">
                Combined Sales
              </span>
            </div>
          </div>

          <!-- Shop -->
          <div class="stat-card-compact" onclick="window.location.href='shopb.php'">
            <div class="stat-header-compact">
              <div class="stat-label-compact">Shop Listings</div>
              <div class="stat-icon-compact"><i class="fas fa-store"></i></div>
            </div>
            <div class="stat-value-compact"><?= number_format($totalProducts) ?></div>
            <div class="stat-details">
              <span class="stat-badge badge-success" style="font-size: 10px; padding: 2px 8px;">
                Active Products
              </span>
            </div>
          </div>

          <!-- Support -->
          <div class="stat-card-compact" onclick="window.location.href='reclamback.php'">
            <div class="stat-header-compact">
              <div class="stat-label-compact">Support</div>
              <div class="stat-icon-compact"><i class="fas fa-headset"></i></div>
            </div>
            <div class="stat-value-compact"><?= $pendingReclamations ?></div>
            <div class="stat-details">
              <span class="stat-badge <?= $pendingReclamations > 0 ? 'badge-warning' : 'badge-success' ?>" style="font-size: 10px; padding: 2px 8px;">
                <?= $pendingReclamations > 0 ? 'Pending Tickets' : 'All Resolved' ?>
              </span>
            </div>
          </div>

          <!-- Events -->
          <div class="stat-card-compact" onclick="window.location.href='eventsb.php'">
            <div class="stat-header-compact">
              <div class="stat-label-compact">Events</div>
              <div class="stat-icon-compact"><i class="fas fa-calendar-alt"></i></div>
            </div>
            <div class="stat-value-compact"><?= $upcomingEvents ?></div>
            <div class="stat-details">
              <span class="stat-badge badge-purple" style="font-size: 10px; padding: 2px 8px;">
                Upcoming
              </span>
            </div>
          </div>
        </div>

        <!-- ========== ANALYTICS GRID ========== -->
        <div class="charts-grid">
          <!-- Activity Distribution -->
          <div class="chart-box">
            <div class="chart-header">
              <div class="chart-title"><i class="fas fa-chart-pie"></i> Distribution</div>
            </div>
            <canvas id="distributionChart" style="max-height: 250px;"></canvas>
          </div>

          <!-- Trading Volume Trend -->
          <div class="chart-box">
            <div class="chart-header">
              <div class="chart-title"><i class="fas fa-chart-line"></i> Performance</div>
            </div>
            <canvas id="volumeChart" style="max-height: 250px;"></canvas>
          </div>
        </div>

        <!-- ========== QUICK ACTIONS ========== -->
        <div class="dashboard-section">
          <div class="section-header">
            <div class="section-icon"><i class="fas fa-bolt"></i></div>
            <div class="section-title">Quick Actions</div>
          </div>
          <div class="actions-grid">
            <a href="users.php" class="action-card">
              <div class="action-icon"><i class="fas fa-user-plus"></i></div>
              <div class="action-label">Users</div>
            </a>
            <a href="shopb.php" class="action-card">
              <div class="action-icon"><i class="fas fa-plus-circle"></i></div>
              <div class="action-label">Product</div>
            </a>
            <a href="eventsb.php" class="action-card">
              <div class="action-icon"><i class="fas fa-calendar-plus"></i></div>
              <div class="action-label">Event</div>
            </a>
            <a href="reclamback.php" class="action-card">
              <div class="action-icon"><i class="fas fa-comments"></i></div>
              <div class="action-label">Support</div>
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
          label: 'Platform Revenue ($)',
          data: [1200, 1900, 3000, 5000, 2300, <?= $combinedVolume > 0 ? $combinedVolume : 4500 ?>],
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