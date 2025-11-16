<?php
require_once __DIR__ . '/../../controller/UserController.php';
require_once __DIR__ . '/../../model/User.php';

// Check if user is logged in and is Admin
if (!UserController::isLoggedIn()) {
    header('Location: ../front/login.php');
    exit();
}

$currentUser = UserController::getCurrentUser();
if (!$currentUser || $currentUser->getRole() !== 'Admin') {
    header('Location: ../front/index.php');
    exit();
}

$message = '';
$messageType = '';

// Handle Ban/Unban action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id']) && isset($_POST['action'])) {
    $userId = intval($_POST['user_id']);
    $action = $_POST['action'];
    
    $user = User::getById($userId);
    if ($user) {
        if ($action === 'ban') {
            $user->setStatus('banned');
            if ($user->update()) {
                $message = 'User has been banned successfully!';
                $messageType = 'success';
            }
        } elseif ($action === 'unban') {
            $user->setStatus('active');
            if ($user->update()) {
                $message = 'User has been unbanned successfully!';
                $messageType = 'success';
            }
        }
    }
}

// Get all users
$users = User::getAll();

// Get user image
$userImage = $currentUser->getImage() 
    ? '../../view/' . $currentUser->getImage() 
    : '../images/meriem.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>User Management - FoxUnity Dashboard</title>
  <link rel="stylesheet" href="style.css">
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Poppins:wght@300;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  
  <style>
    .user-table-container {
      background: rgba(20, 20, 20, 0.95);
      border-radius: 15px;
      padding: 30px;
      margin: 20px 0;
      border: 1px solid rgba(255, 122, 0, 0.3);
    }

    .user-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }

    .user-table th {
      background: linear-gradient(135deg, #ff7a00, #ff4f00);
      color: white;
      padding: 15px;
      text-align: left;
      font-weight: 700;
      font-family: 'Orbitron', sans-serif;
      border: none;
    }

    .user-table th:first-child {
      border-radius: 10px 0 0 0;
    }

    .user-table th:last-child {
      border-radius: 0 10px 0 0;
    }

    .user-table td {
      padding: 15px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      color: #fff;
    }

    .user-table tr:hover {
      background: rgba(255, 122, 0, 0.05);
    }

    .status-badge {
      padding: 5px 15px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 700;
      text-transform: uppercase;
      display: inline-block;
    }

    .status-active {
      background: rgba(76, 175, 80, 0.2);
      color: #4caf50;
      border: 1px solid #4caf50;
    }

    .status-banned {
      background: rgba(244, 67, 54, 0.2);
      color: #f44336;
      border: 1px solid #f44336;
    }

    .role-badge {
      padding: 5px 15px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 700;
      display: inline-block;
    }

    .role-admin {
      background: rgba(255, 193, 7, 0.2);
      color: #ffc107;
      border: 1px solid #ffc107;
    }

    .role-gamer {
      background: rgba(33, 150, 243, 0.2);
      color: #2196f3;
      border: 1px solid #2196f3;
    }

    .btn-ban, .btn-unban {
      padding: 8px 20px;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 600;
      font-size: 13px;
      transition: all 0.3s ease;
      font-family: 'Poppins', sans-serif;
    }

    .btn-ban {
      background: linear-gradient(135deg, #f44336, #d32f2f);
      color: white;
    }

    .btn-ban:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(244, 67, 54, 0.4);
    }

    .btn-unban {
      background: linear-gradient(135deg, #4caf50, #388e3c);
      color: white;
    }

    .btn-unban:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(76, 175, 80, 0.4);
    }

    .message-alert {
      position: fixed;
      top: 80px;
      left: 50%;
      transform: translateX(-50%);
      padding: 20px 30px;
      border-radius: 12px;
      margin-bottom: 20px;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 12px;
      animation: slideDown 0.3s ease;
      z-index: 9999;
      min-width: 400px;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
    }

    .message-success {
      background: rgba(20, 20, 20, 0.98);
      border: 2px solid #4caf50;
      color: #4caf50;
    }

    .message-success i {
      font-size: 24px;
    }

    @keyframes slideDown {
      from {
        transform: translate(-50%, -20px);
        opacity: 0;
      }
      to {
        transform: translate(-50%, 0);
        opacity: 1;
      }
    }

    .page-title {
      font-family: 'Orbitron', sans-serif;
      font-size: 32px;
      color: #fff;
      margin-bottom: 10px;
    }

    .page-subtitle {
      color: #888;
      font-size: 16px;
      margin-bottom: 30px;
    }

    .user-count {
      background: rgba(255, 122, 0, 0.1);
      border: 2px solid rgba(255, 122, 0, 0.3);
      color: #ff7a00;
      padding: 10px 20px;
      border-radius: 10px;
      display: inline-block;
      font-weight: 700;
      margin-bottom: 20px;
    }

    /* Admin Dropdown in Dashboard */
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

    .admin-user i.fa-chevron-down {
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
      color: #ff7a00;
      width: 20px;
    }

    .dropdown-item.logout {
      color: #ff4444;
    }

    .dropdown-item.logout i {
      color: #ff4444;
    }

    .dropdown-divider {
      height: 1px;
      background: rgba(255, 122, 0, 0.2);
      margin: 5px 0;
    }

    /* Custom Confirmation Modal */
    .confirm-modal {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.85);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 10000;
      animation: fadeIn 0.3s ease;
    }

    .confirm-modal.show {
      display: flex;
    }

    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    .confirm-box {
      background: linear-gradient(135deg, rgba(20, 20, 20, 0.98) 0%, rgba(10, 10, 10, 0.98) 100%);
      border: 2px solid rgba(255, 122, 0, 0.5);
      border-radius: 20px;
      padding: 40px;
      max-width: 500px;
      text-align: center;
      animation: scaleIn 0.3s ease;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.7);
    }

    @keyframes scaleIn {
      from { transform: scale(0.8); opacity: 0; }
      to { transform: scale(1); opacity: 1; }
    }

    .confirm-box h3 {
      color: #fff;
      font-family: 'Orbitron', sans-serif;
      font-size: 24px;
      margin-bottom: 15px;
    }

    .confirm-box p {
      color: #aaa;
      font-size: 16px;
      margin-bottom: 30px;
      line-height: 1.6;
    }

    .confirm-buttons {
      display: flex;
      gap: 15px;
      justify-content: center;
    }

    .btn-confirm-yes, .btn-confirm-no {
      padding: 12px 30px;
      border: none;
      border-radius: 10px;
      cursor: pointer;
      font-weight: 700;
      font-size: 14px;
      transition: all 0.3s ease;
      font-family: 'Poppins', sans-serif;
    }

    .btn-confirm-yes {
      background: linear-gradient(135deg, #f44336, #d32f2f);
      color: white;
    }

    .btn-confirm-yes:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(244, 67, 54, 0.4);
    }

    .btn-confirm-no {
      background: transparent;
      border: 2px solid rgba(255, 255, 255, 0.3);
      color: #fff;
    }

    .btn-confirm-no:hover {
      background: rgba(255, 255, 255, 0.1);
      border-color: rgba(255, 255, 255, 0.5);
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
    <a href="dashboard.php">Overview</a>
    <a href="users.php" class="active">Users</a>
    <a href="#">Shop</a>
    <a href="#">Trade History</a>
    <a href="#">Events</a>
    <a href="#">News</a>
    <a href="#">Support</a>
    <a href="../front/index.php">← Return Homepage</a>
  </div>

  <!-- ===== MAIN ===== -->
  <div class="main">
    <div class="topbar">
      <h1>User Management</h1>
      <div class="admin-dropdown" id="adminDropdown">
        <div class="user admin-user">
          <img src="<?php echo htmlspecialchars($userImage); ?>" alt="Admin Avatar">
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

    <div class="content">
      <?php if ($message): ?>
      <div class="message-alert message-<?php echo $messageType; ?>">
        <i class="fas fa-check-circle"></i>
        <span><?php echo htmlspecialchars($message); ?></span>
      </div>
      <?php endif; ?>

      <div class="user-table-container">
        <h2 class="page-title"><i class="fas fa-users"></i> All Users</h2>
        <p class="page-subtitle">Manage user accounts, roles, and status</p>
        
        <div class="user-count">
          <i class="fas fa-users"></i> Total Users: <?php echo count($users); ?>
        </div>

        <table class="user-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Username</th>
              <th>Email</th>
              <th>Role</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($users as $user): ?>
            <tr>
              <td>#<?php echo str_pad($user->getId(), 4, '0', STR_PAD_LEFT); ?></td>
              <td><?php echo htmlspecialchars($user->getUsername()); ?></td>
              <td><?php echo htmlspecialchars($user->getEmail()); ?></td>
              <td>
                <span class="role-badge role-<?php echo strtolower($user->getRole()); ?>">
                  <?php echo htmlspecialchars($user->getRole()); ?>
                </span>
              </td>
              <td>
                <span class="status-badge status-<?php echo strtolower($user->getStatus()); ?>">
                  <?php echo htmlspecialchars($user->getStatus()); ?>
                </span>
              </td>
              <td>
                <?php if ($user->getId() !== $currentUser->getId()): ?>
                  <form method="POST" class="action-form" style="display: inline;">
                    <input type="hidden" name="user_id" value="<?php echo $user->getId(); ?>">
                    <?php if ($user->getStatus() === 'active'): ?>
                      <input type="hidden" name="action" value="ban">
                      <button type="button" class="btn-ban action-btn" data-action="ban" data-username="<?php echo htmlspecialchars($user->getUsername()); ?>">
                        <i class="fas fa-ban"></i> Ban
                      </button>
                    <?php else: ?>
                      <input type="hidden" name="action" value="unban">
                      <button type="button" class="btn-unban action-btn" data-action="unban" data-username="<?php echo htmlspecialchars($user->getUsername()); ?>">
                        <i class="fas fa-check"></i> Unban
                      </button>
                    <?php endif; ?>
                  </form>
                <?php else: ?>
                  <span style="color: #888; font-style: italic;">You (Admin)</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <footer class="site-footer">
      © 2025 <span>Nine Tailed Fox</span>. All Rights Reserved.
    </footer>
  </div>

  <!-- Custom Confirmation Modal -->
  <div class="confirm-modal" id="confirmModal">
    <div class="confirm-box">
      <h3><i class="fas fa-exclamation-triangle" style="color: #ff7a00;"></i> Confirm Action</h3>
      <p id="confirmMessage">Are you sure you want to perform this action?</p>
      <div class="confirm-buttons">
        <button class="btn-confirm-yes" id="confirmYes">
          <i class="fas fa-check"></i> Yes, Continue
        </button>
        <button class="btn-confirm-no" id="confirmNo">
          <i class="fas fa-times"></i> Cancel
        </button>
      </div>
    </div>
  </div>

  <!-- ===== PAGE TRANSITION OVERLAY ===== -->
  <div class="transition-screen"></div>

  <script>
    // Admin Dropdown Toggle
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
      }
    });

    // Custom confirmation modal for Ban/Unban actions
    const confirmModal = document.getElementById('confirmModal');
    const confirmMessage = document.getElementById('confirmMessage');
    const confirmYes = document.getElementById('confirmYes');
    const confirmNo = document.getElementById('confirmNo');
    let pendingForm = null;

    // Handle action button clicks
    document.querySelectorAll('.action-btn').forEach(button => {
      button.addEventListener('click', function(e) {
        e.preventDefault();
        
        const action = this.dataset.action;
        const username = this.dataset.username;
        const form = this.closest('form');
        
        // Set confirmation message based on action
        if (action === 'ban') {
          confirmMessage.innerHTML = `Are you sure you want to <strong style="color: #f44336;">ban</strong> user <strong style="color: #ff7a00;">${username}</strong>?`;
        } else {
          confirmMessage.innerHTML = `Are you sure you want to <strong style="color: #4caf50;">unban</strong> user <strong style="color: #ff7a00;">${username}</strong>?`;
        }
        
        pendingForm = form;
        confirmModal.classList.add('show');
      });
    });

    // Confirm action
    confirmYes.addEventListener('click', function() {
      if (pendingForm) {
        confirmModal.classList.remove('show');
        pendingForm.submit();
      }
    });

    // Cancel action
    confirmNo.addEventListener('click', function() {
      confirmModal.classList.remove('show');
      pendingForm = null;
    });

    // Close modal on outside click
    confirmModal.addEventListener('click', function(e) {
      if (e.target === confirmModal) {
        confirmModal.classList.remove('show');
        pendingForm = null;
      }
    });

    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' && confirmModal.classList.contains('show')) {
        confirmModal.classList.remove('show');
        pendingForm = null;
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

    // Auto-hide success message after 5 seconds
    setTimeout(function() {
      const message = document.querySelector('.message-alert');
      if (message) {
        message.style.opacity = '0';
        setTimeout(() => message.remove(), 300);
      }
    }, 5000);
  </script>
  
</body>
</html>