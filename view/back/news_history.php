<?php
// News History page - Dedicated page for viewing article edit history
// Usage: Access via dashboard sidebar "News History" link

require_once __DIR__ . '/../../model/db.php';
require_once __DIR__ . '/../../model/Article.php';
require_once __DIR__ . '/../../controller/UserController.php';

// Check if user is logged in and is Admin
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

// Get user image
$userImage = null;
if ($currentUser->getImage()) {
  $userImage = '../../view/' . $currentUser->getImage();
}

// Load categories for display names
$catStmt = $pdo->query("SELECT idCategorie, nom, description FROM categorie ORDER BY nom");
$categories = $catStmt->fetchAll();

// Get all articles with their history
$articles = Article::getAll();
$historyData = [];
foreach ($articles as $article) {
  $history = Article::getHistoryByArticleId($article['idArticle']);
  if (!empty($history)) {
    $historyData[$article['idArticle']] = [
      'article' => $article,
      'history' => $history
    ];
  }
}
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>News History - Dashboard</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    /* Admin Dropdown Styles */
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
  </style>
</head>

<body class="dashboard-body">
  <div class="stars"></div>
  <div class="shooting-star"></div>
  <div class="shooting-star"></div>
  <div class="shooting-star"></div>

  <div class="sidebar">
    <img src="../images/Nine__1_-removebg-preview.png" alt="Nine Tailed Fox Logo" class="dashboard-logo">
    <h2>Dashboard</h2>
    <a href="dashboard.php" class="">Overview</a>
    <a href="users.php">Users</a>
    <a href="shopb.php">Shop</a>
    <a href="tradingb.php">Trade History</a>
    <a href="eventsb.php">Events</a>
    <a href="news_admin.php">News</a>
    <a href="news_history.php" class="active">News History</a>
    <a href="categories.php">Categories</a>
    <a href="newsletter_admin.php">Newsletter</a>
    <a href="#">Support</a>
    <a href="../front/index.php">← Return Homepage</a>
  </div>

  <div class="main">
    <div class="topbar">
      <h1>News History</h1>
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

    <div class="content">
      <div class="card modern-card"
        style="width:100%; border-radius:20px; background: #181818; box-shadow: 0 6px 32px 2px rgba(0,0,0,0.22); padding: 36px 32px 32px 32px; margin-top:28px; max-width:100%; min-height:unset;">
        <span class="modern-section-title" style="font-size:1.17em;"><i class="fas fa-clock-rotate-left"
            style="margin-right:8px;color:#ff7a00;"></i>Article Edit History</span>
        <div id="history-list" style="margin-top:18px;">
          <?php if (empty($historyData)): ?>
            <p style="color:#bbb">No history available yet.</p>
          <?php else:
            foreach ($historyData as $articleId => $data) {
              $article = $data['article'];
              $history = $data['history'];
              echo '<div class="history-article-box" style="background:#151313;padding:18px 14px;border-radius:15px;margin-bottom:17px;border-left:3px solid #ff7a00;box-shadow:0 3px 16px 0 rgba(255,122,0,0.06);">';
              echo '<strong>' . htmlspecialchars($article['title']) . '</strong> (' . count($history) . ' edit' . (count($history) !== 1 ? 's' : '') . ')<br>';
              echo '<table style="width:100%;margin-top:8px;font-size:0.85rem;border-collapse:collapse">';
              echo '<thead><tr style="background:#232323;color:#ff7a00;font-weight:bold;"><th style="text-align:left;padding:10px 8px;">Edited At</th><th style="text-align:left;padding:10px 8px;">Edited By</th><th style="text-align:left;padding:10px 8px;">Title</th></tr></thead><tbody>';
              foreach ($history as $h) {
                echo '<tr style="background:#191919;">';
                echo '<td style="padding:8px 7px;border-bottom:1px solid #242323">' . htmlspecialchars($h['edited_at']) . '</td>';
                echo '<td style="padding:8px 7px;border-bottom:1px solid #242323">' . htmlspecialchars($h['edited_by_name'] ?? 'Unknown') . '</td>';
                echo '<td style="padding:8px 7px;border-bottom:1px solid #242323">' . htmlspecialchars($h['titre']) . '</td>';
                echo '</tr>';
              }
              echo '</tbody></table>';
              echo '</div>';
            }
          endif; ?>
        </div>
      </div>
    </div>

    <footer class="site-footer">
      © 2025 <span>Nine Tailed Fox</span>. All Rights Reserved.
    </footer>
  </div>

  <div class="transition-screen"></div>
  <script>
    // Ensure the transition overlay is hidden after page load
    window.addEventListener('load', function () {
      try {
        var t = document.querySelector('.transition-screen');
        if (t) t.classList.add('hidden');
      } catch (e) { }
    });

    // Dropdown Menu Toggle
    document.addEventListener('DOMContentLoaded', function () {
      const adminDropdown = document.getElementById('adminDropdown');

      if (adminDropdown) {
        const adminUser = adminDropdown.querySelector('.admin-user');

        // Toggle dropdown on click
        if (adminUser) {
          adminUser.addEventListener('click', function (e) {
            e.stopPropagation();
            adminDropdown.classList.toggle('active');
          });
        }

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
  </script>
</body>

</html>