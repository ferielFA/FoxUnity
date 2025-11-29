<?php
require_once __DIR__ . '/../../controller/UserController.php';
require_once __DIR__ . '/../../model/User.php';

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

$message = '';
$messageType = '';

// Handle Ban/Unban action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id']) && isset($_POST['action'])) {
    $userId = intval($_POST['user_id']);
    $action = $_POST['action'];
    
    $user = User::getById($userId);
    if ($user) {
        // BAN/UNBAN logic
        if ($action === 'ban') {
            // SuperAdmin can ban everyone (Admin + Gamer)
            // Admin can only ban Gamer
            $canBan = false;
            $currentRole = strtolower($currentUser->getRole());
            $targetRole = strtolower($user->getRole());
            
            if ($currentRole === 'superadmin') {
                $canBan = true; // SuperAdmin can ban anyone
            } elseif ($currentRole === 'admin') {
                // Admin can only ban Gamer, not SuperAdmin or other Admins
                if ($targetRole === 'gamer') {
                    $canBan = true;
                }
            }
            
            if ($canBan) {
                $user->setStatus('banned');
                if ($user->update()) {
                    $message = 'User has been banned successfully!';
                    $messageType = 'success';
                }
            } else {
                $message = 'You do not have permission to ban this user!';
                $messageType = 'error';
            }
        } elseif ($action === 'unban') {
            $user->setStatus('active');
            if ($user->update()) {
                $message = 'User has been unbanned successfully!';
                $messageType = 'success';
            }
        } elseif ($action === 'upgrade') {
            // Only SuperAdmin can upgrade Gamer to Admin
            $currentRole = strtolower($currentUser->getRole());
            $targetRole = strtolower($user->getRole());
            
            if ($currentRole === 'superadmin' && $targetRole === 'gamer') {
                $user->setRole('Admin');
                if ($user->update()) {
                    $message = 'User has been upgraded to Admin successfully!';
                    $messageType = 'success';
                }
            } else {
                $message = 'You do not have permission to upgrade this user!';
                $messageType = 'error';
            }
        } elseif ($action === 'downgrade') {
            // Only SuperAdmin can downgrade Admin to Gamer
            $currentRole = strtolower($currentUser->getRole());
            $targetRole = strtolower($user->getRole());
            
            if ($currentRole === 'superadmin' && $targetRole === 'admin') {
                $user->setRole('Gamer');
                if ($user->update()) {
                    $message = 'User has been downgraded to Gamer successfully!';
                    $messageType = 'success';
                }
            } else {
                $message = 'You do not have permission to downgrade this user!';
                $messageType = 'error';
            }
        }
    }
}

// Get all users
$users = User::getAll();

// Get user image - NO DEFAULT IMAGE
$userImage = null;
if ($currentUser->getImage()) {
    $userImage = '../../view/' . $currentUser->getImage();
}
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

    .role-superadmin {
      background: rgba(156, 39, 176, 0.2);
      color: #9c27b0;
      border: 1px solid #9c27b0;
      font-weight: 800;
      text-transform: uppercase;
      box-shadow: 0 0 10px rgba(156, 39, 176, 0.3);
    }

    .role-gamer {
      background: rgba(33, 150, 243, 0.2);
      color: #2196f3;
      border: 1px solid #2196f3;
    }

    .btn-ban, .btn-unban, .btn-upgrade, .btn-downgrade {
      padding: 8px 20px;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 600;
      font-size: 13px;
      transition: all 0.3s ease;
      font-family: 'Poppins', sans-serif;
      margin: 0 5px;
    }

    .btn-ban {
      background: linear-gradient(135deg, #f44336, #d32f2f);
      color: white;
    }

    .btn-ban:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(244, 67, 54, 0.4);
    }

    .btn-ban:disabled {
      background: #666;
      cursor: not-allowed;
      opacity: 0.5;
    }

    .btn-unban {
      background: linear-gradient(135deg, #4caf50, #388e3c);
      color: white;
    }

    .btn-unban:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(76, 175, 80, 0.4);
    }

    .btn-upgrade {
      background: linear-gradient(135deg, #9c27b0, #7b1fa2);
      color: white;
    }

    .btn-upgrade:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(156, 39, 176, 0.4);
    }

    .btn-downgrade {
      background: linear-gradient(135deg, #ff9800, #f57c00);
      color: white;
    }

    .btn-downgrade:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(255, 152, 0, 0.4);
    }

    /* Actions Dropdown */
    .actions-dropdown {
      position: relative;
      display: inline-block;
    }

    .actions-btn {
      background: linear-gradient(135deg, #ff7a00, #ff4f00);
      color: white;
      border: none;
      padding: 8px 16px;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 600;
      font-size: 13px;
      display: flex;
      align-items: center;
      gap: 8px;
      transition: all 0.3s ease;
    }

    .actions-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(255, 122, 0, 0.4);
    }

    .actions-btn i {
      font-size: 14px;
    }

    .actions-menu {
      position: absolute;
      top: 100%;
      right: 0;
      margin-top: 8px;
      background: rgba(20, 20, 20, 0.98);
      border: 2px solid rgba(255, 122, 0, 0.3);
      border-radius: 12px;
      min-width: 180px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
      opacity: 0;
      visibility: hidden;
      transform: translateY(-10px);
      transition: all 0.3s ease;
      z-index: 1000;
      overflow: hidden;
    }

    .actions-dropdown.active .actions-menu {
      opacity: 1;
      visibility: visible;
      transform: translateY(0);
    }

    .action-menu-item {
      padding: 12px 15px;
      color: #fff;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 12px;
      transition: all 0.3s ease;
      border-left: 3px solid transparent;
      font-size: 14px;
      font-weight: 500;
    }

    .action-menu-item:hover {
      background: rgba(255, 122, 0, 0.1);
      border-left-color: #ff7a00;
    }

    .action-menu-item i {
      width: 20px;
      font-size: 16px;
    }

    .action-menu-item.upgrade-action {
      color: #ffc107; /* JAUNE comme badge Admin */
    }

    .action-menu-item.upgrade-action:hover {
      background: rgba(255, 193, 7, 0.1);
      border-left-color: #ffc107;
    }

    .action-menu-item.downgrade-action {
      color: #2196f3; /* BLEU comme badge Gamer */
    }

    .action-menu-item.downgrade-action:hover {
      background: rgba(33, 150, 243, 0.1);
      border-left-color: #2196f3;
    }

    .action-menu-item.ban-action {
      color: #f44336;
    }

    .action-menu-item.ban-action:hover {
      background: rgba(244, 67, 54, 0.1);
      border-left-color: #f44336;
    }

    .action-menu-item.unban-action {
      color: #4caf50;
    }

    .action-menu-item.unban-action:hover {
      background: rgba(76, 175, 80, 0.1);
      border-left-color: #4caf50;
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

    .message-error {
      background: rgba(20, 20, 20, 0.98);
      border: 2px solid #f44336;
      color: #f44336;
    }

    .message-success i,
    .message-error i {
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
    <a href="tradingb.php">Trade History</a>
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

    <div class="content">
      <?php if ($message): ?>
      <div class="message-alert message-<?php echo $messageType; ?>">
        <?php if ($messageType === 'success'): ?>
        <i class="fas fa-check-circle"></i>
        <?php else: ?>
        <i class="fas fa-exclamation-triangle"></i>
        <?php endif; ?>
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
                  
                  <?php
                  // Determine permissions
                  $canBan = false;
                  $canUpgrade = false;
                  $canDowngrade = false;
                  $currentRole = strtolower($currentUser->getRole());
                  $targetRole = strtolower($user->getRole());
                  
                  if ($currentRole === 'superadmin') {
                    $canBan = true; // SuperAdmin can ban anyone
                    $canUpgrade = ($targetRole === 'gamer'); // SuperAdmin can upgrade Gamer to Admin
                    $canDowngrade = ($targetRole === 'admin'); // SuperAdmin can downgrade Admin to Gamer
                  } elseif ($currentRole === 'admin') {
                    $canBan = ($targetRole === 'gamer'); // Admin can only ban Gamer
                  }
                  ?>
                  
                  <!-- Actions Dropdown -->
                  <?php if ($canBan || $canUpgrade || $canDowngrade): ?>
                  <div class="actions-dropdown">
                    <button class="actions-btn" onclick="toggleActionsMenu(this)">
                      <i class="fas fa-ellipsis-v"></i> Actions
                    </button>
                    
                    <div class="actions-menu">
                      <!-- Upgrade Action -->
                      <?php if ($canUpgrade): ?>
                      <div class="action-menu-item upgrade-action" 
                           onclick="performAction(this, 'upgrade', '<?php echo $user->getId(); ?>', '<?php echo htmlspecialchars($user->getUsername()); ?>')">
                        <i class="fas fa-arrow-up"></i>
                        <span>Upgrade to Admin</span>
                      </div>
                      <?php endif; ?>
                      
                      <!-- Downgrade Action -->
                      <?php if ($canDowngrade): ?>
                      <div class="action-menu-item downgrade-action" 
                           onclick="performAction(this, 'downgrade', '<?php echo $user->getId(); ?>', '<?php echo htmlspecialchars($user->getUsername()); ?>')">
                        <i class="fas fa-arrow-down"></i>
                        <span>Downgrade to Gamer</span>
                      </div>
                      <?php endif; ?>
                      
                      <!-- Ban/Unban Action -->
                      <?php if ($canBan): ?>
                        <?php if ($user->getStatus() === 'active'): ?>
                        <div class="action-menu-item ban-action" 
                             onclick="performAction(this, 'ban', '<?php echo $user->getId(); ?>', '<?php echo htmlspecialchars($user->getUsername()); ?>')">
                          <i class="fas fa-ban"></i>
                          <span>Ban User</span>
                        </div>
                        <?php else: ?>
                        <div class="action-menu-item unban-action" 
                             onclick="performAction(this, 'unban', '<?php echo $user->getId(); ?>', '<?php echo htmlspecialchars($user->getUsername()); ?>')">
                          <i class="fas fa-check"></i>
                          <span>Unban User</span>
                        </div>
                        <?php endif; ?>
                      <?php endif; ?>
                    </div>
                  </div>
                  <?php else: ?>
                    <?php 
                    $targetRole = strtolower($user->getRole());
                    if ($targetRole === 'superadmin'): 
                    ?>
                      <span style="color: #9c27b0; font-style: italic; font-weight: 600;">
                        <i class="fas fa-shield-alt"></i> SuperAdmin (Protected)
                      </span>
                    <?php elseif ($targetRole === 'admin'): ?>
                      <span style="color: #888; font-style: italic;">
                        <i class="fas fa-lock"></i> Admin (No Permission)
                      </span>
                    <?php endif; ?>
                  <?php endif; ?>
                  
                <?php else: ?>
                  <span style="color: #888; font-style: italic;">
                    You (<?php echo htmlspecialchars($currentUser->getRole()); ?>)
                  </span>
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

    // Custom confirmation modal for actions
    const confirmModal = document.getElementById('confirmModal');
    const confirmMessage = document.getElementById('confirmMessage');
    const confirmYes = document.getElementById('confirmYes');
    const confirmNo = document.getElementById('confirmNo');
    let pendingAction = null;

    // Toggle actions menu
    function toggleActionsMenu(button) {
      const dropdown = button.closest('.actions-dropdown');
      const allDropdowns = document.querySelectorAll('.actions-dropdown');
      
      // Close all other dropdowns
      allDropdowns.forEach(d => {
        if (d !== dropdown) {
          d.classList.remove('active');
        }
      });
      
      // Toggle current dropdown
      dropdown.classList.toggle('active');
      
      // Stop propagation
      event.stopPropagation();
    }

    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
      if (!e.target.closest('.actions-dropdown')) {
        document.querySelectorAll('.actions-dropdown').forEach(d => {
          d.classList.remove('active');
        });
      }
    });

    // Perform action with confirmation
    function performAction(element, action, userId, username) {
      // Close dropdown
      element.closest('.actions-dropdown').classList.remove('active');
      
      // Set confirmation message based on action
      if (action === 'ban') {
        confirmMessage.innerHTML = `Are you sure you want to <strong style="color: #f44336;">ban</strong> user <strong style="color: #ff7a00;">${username}</strong>?`;
      } else if (action === 'unban') {
        confirmMessage.innerHTML = `Are you sure you want to <strong style="color: #4caf50;">unban</strong> user <strong style="color: #ff7a00;">${username}</strong>?`;
      } else if (action === 'upgrade') {
        confirmMessage.innerHTML = `Are you sure you want to <strong style="color: #ffc107;">upgrade</strong> user <strong style="color: #ff7a00;">${username}</strong> to <strong style="color: #ffc107;">Admin</strong>?`;
      } else if (action === 'downgrade') {
        confirmMessage.innerHTML = `Are you sure you want to <strong style="color: #2196f3;">downgrade</strong> user <strong style="color: #ff7a00;">${username}</strong> to <strong style="color: #2196f3;">Gamer</strong>?`;
      }
      
      // Store pending action
      pendingAction = { action, userId };
      
      // Show modal
      confirmModal.classList.add('show');
    }

    // Confirm action
    confirmYes.addEventListener('click', function() {
      if (pendingAction) {
        // Create and submit form
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
          <input type="hidden" name="user_id" value="${pendingAction.userId}">
          <input type="hidden" name="action" value="${pendingAction.action}">
        `;
        document.body.appendChild(form);
        form.submit();
      }
    });

    // Cancel action
    confirmNo.addEventListener('click', function() {
      confirmModal.classList.remove('show');
      pendingAction = null;
    });

    // Close modal on outside click
    confirmModal.addEventListener('click', function(e) {
      if (e.target === confirmModal) {
        confirmModal.classList.remove('show');
        pendingAction = null;
      }
    });

    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' && confirmModal.classList.contains('show')) {
        confirmModal.classList.remove('show');
        pendingAction = null;
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