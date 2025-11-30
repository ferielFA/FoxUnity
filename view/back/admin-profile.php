<?php
require_once __DIR__ . '/../../controller/UserController.php';

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Profile - FoxUnity Dashboard</title>
  <link rel="stylesheet" href="style.css">
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Poppins:wght@300;600&display=swap" rel="stylesheet">
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
      color: #ff7a00;
      width: 20px;
      font-size: 16px;
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

    .dropdown-divider {
      height: 1px;
      background: rgba(255, 122, 0, 0.2);
      margin: 5px 0;
    }

    /* Profile Card Styles */
    .profile-card {
      background: rgba(20, 20, 20, 0.95);
      border-radius: 15px;
      padding: 40px;
      margin: 20px 0;
      border: 1px solid rgba(255, 122, 0, 0.3);
    }

    .profile-header {
      display: flex;
      align-items: center;
      gap: 30px;
      margin-bottom: 40px;
      padding-bottom: 30px;
      border-bottom: 2px solid rgba(255, 122, 0, 0.3);
    }

    .profile-avatar {
      width: 150px;
      height: 150px;
      border-radius: 50%;
      object-fit: cover;
      border: 4px solid #ff7a00;
      box-shadow: 0 10px 30px rgba(255, 122, 0, 0.4);
    }

    .profile-info h2 {
      font-family: 'Orbitron', sans-serif;
      font-size: 32px;
      color: #fff;
      margin: 0 0 10px;
    }

    .profile-info p {
      color: #888;
      font-size: 16px;
      margin: 5px 0;
    }

    .admin-badge {
      display: inline-block;
      background: rgba(255, 193, 7, 0.2);
      color: #ffc107;
      border: 1px solid #ffc107;
      padding: 5px 15px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 700;
      margin-top: 10px;
    }

    .profile-details {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 25px;
    }

    .detail-item {
      background: rgba(255, 122, 0, 0.05);
      padding: 20px;
      border-radius: 10px;
      border: 1px solid rgba(255, 122, 0, 0.2);
    }

    .detail-label {
      color: #888;
      font-size: 13px;
      font-weight: 600;
      margin-bottom: 8px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .detail-label i {
      color: #ff7a00;
    }

    .detail-value {
      color: #fff;
      font-size: 16px;
      font-weight: 600;
    }

    .btn-edit {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      background: linear-gradient(135deg, #ff7a00, #ff4f00);
      color: #fff;
      padding: 12px 30px;
      border-radius: 25px;
      text-decoration: none;
      font-weight: 700;
      transition: all 0.3s ease;
      margin-top: 20px;
      border: none;
      cursor: pointer;
    }

    .btn-edit:hover {
      transform: translateY(-3px);
      box-shadow: 0 10px 30px rgba(255, 122, 0, 0.4);
    }

    .btn-delete {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      background: linear-gradient(135deg, #f44336, #d32f2f);
      color: #fff;
      padding: 12px 30px;
      border-radius: 25px;
      font-weight: 700;
      transition: all 0.3s ease;
      border: none;
      cursor: pointer;
      font-family: 'Poppins', sans-serif;
      font-size: 14px;
    }

    .btn-delete:hover {
      transform: translateY(-3px);
      box-shadow: 0 10px 30px rgba(244, 67, 54, 0.4);
    }

    /* Delete Confirmation Modal */
    .delete-modal {
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

    .delete-modal.show {
      display: flex;
    }

    .delete-box {
      background: linear-gradient(135deg, rgba(20, 20, 20, 0.98) 0%, rgba(10, 10, 10, 0.98) 100%);
      border: 2px solid rgba(244, 67, 54, 0.5);
      border-radius: 20px;
      padding: 40px;
      max-width: 500px;
      text-align: center;
      animation: scaleIn 0.3s ease;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.7);
    }

    .delete-box h3 {
      color: #f44336;
      font-family: 'Orbitron', sans-serif;
      font-size: 24px;
      margin-bottom: 15px;
    }

    .delete-box p {
      color: #aaa;
      font-size: 16px;
      margin-bottom: 30px;
      line-height: 1.6;
    }

    .delete-box .warning-text {
      background: rgba(244, 67, 54, 0.1);
      border: 2px solid rgba(244, 67, 54, 0.3);
      padding: 15px;
      border-radius: 10px;
      color: #f44336;
      font-weight: 600;
      margin-bottom: 30px;
    }

    .delete-buttons {
      display: flex;
      gap: 15px;
      justify-content: center;
    }

    .btn-delete-confirm, .btn-delete-cancel {
      padding: 12px 30px;
      border: none;
      border-radius: 10px;
      cursor: pointer;
      font-weight: 700;
      font-size: 14px;
      transition: all 0.3s ease;
      font-family: 'Poppins', sans-serif;
    }

    .btn-delete-confirm {
      background: linear-gradient(135deg, #f44336, #d32f2f);
      color: white;
    }

    .btn-delete-confirm:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(244, 67, 54, 0.4);
    }

    .btn-delete-cancel {
      background: transparent;
      border: 2px solid rgba(255, 255, 255, 0.3);
      color: #fff;
    }

    .btn-delete-cancel:hover {
      background: rgba(255, 255, 255, 0.1);
      border-color: rgba(255, 255, 255, 0.5);
    }

    /* Success Message */
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

    @media (max-width: 768px) {
      .profile-header {
        flex-direction: column;
        text-align: center;
      }

      .profile-details {
        grid-template-columns: 1fr;
      }
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
    <a href="users.php">Users</a>
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
      <h1>Admin Profile</h1>
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
      <div class="profile-card">
        <div class="profile-header">
          <?php if ($userImage): ?>
          <img src="<?php echo htmlspecialchars($userImage); ?>" alt="Admin Avatar" class="profile-avatar">
          <?php else: ?>
          <div class="profile-avatar" style="display: flex; align-items: center; justify-content: center; background: rgba(255, 122, 0, 0.1);">
            <i class="fas fa-user-circle" style="font-size: 80px; color: #ff7a00;"></i>
          </div>
          <?php endif; ?>
          <div class="profile-info">
            <h2><?php echo htmlspecialchars($currentUser->getUsername()); ?></h2>
            <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($currentUser->getEmail()); ?></p>
            <span class="admin-badge"><i class="fas fa-shield-alt"></i> ADMINISTRATOR</span>
            <br>
            <a href="admin-edit-profile.php" class="btn-edit">
              <i class="fas fa-edit"></i> Edit Profile
            </a>
          </div>
        </div>

        <div class="profile-details">
          <div class="detail-item">
            <div class="detail-label">
              <i class="fas fa-id-card"></i> Username
            </div>
            <div class="detail-value"><?php echo htmlspecialchars($currentUser->getUsername()); ?></div>
          </div>

          <div class="detail-item">
            <div class="detail-label">
              <i class="fas fa-envelope"></i> Email
            </div>
            <div class="detail-value"><?php echo htmlspecialchars($currentUser->getEmail()); ?></div>
          </div>

          <div class="detail-item">
            <div class="detail-label">
              <i class="fas fa-calendar"></i> Date of Birth
            </div>
            <div class="detail-value"><?php echo htmlspecialchars($currentUser->getDob()); ?></div>
          </div>

          <div class="detail-item">
            <div class="detail-label">
              <i class="fas fa-venus-mars"></i> Gender
            </div>
            <div class="detail-value"><?php echo htmlspecialchars($currentUser->getGender()); ?></div>
          </div>

          <div class="detail-item">
            <div class="detail-label">
              <i class="fas fa-user-tag"></i> Role
            </div>
            <div class="detail-value"><?php echo htmlspecialchars($currentUser->getRole()); ?></div>
          </div>

          <div class="detail-item">
            <div class="detail-label">
              <i class="fas fa-check-circle"></i> Status
            </div>
            <div class="detail-value" style="color: #4caf50;"><?php echo htmlspecialchars($currentUser->getStatus()); ?></div>
          </div>

          <div class="detail-item">
            <div class="detail-label">
              <i class="fas fa-id-badge"></i> User ID
            </div>
            <div class="detail-value">#<?php echo str_pad($currentUser->getId(), 6, '0', STR_PAD_LEFT); ?></div>
          </div>
        </div>

        <!-- Delete Profile Section -->
        <div style="margin-top: 40px; padding-top: 30px; border-top: 2px solid rgba(255, 122, 0, 0.3);">
          <h3 style="color: #f44336; font-family: 'Orbitron', sans-serif; margin-bottom: 15px;">
            <i class="fas fa-exclamation-triangle"></i> Danger Zone
          </h3>
          <p style="color: #888; margin-bottom: 20px;">
            Once you delete your account, there is no going back. Please be certain.
          </p>
          <button class="btn-delete" id="deleteProfileBtn">
            <i class="fas fa-trash-alt"></i> Delete My Account
          </button>
        </div>
      </div>
    </div>

    <footer class="site-footer">
      © 2025 <span>Nine Tailed Fox</span>. All Rights Reserved.
    </footer>
  </div>

  <!-- ===== PAGE TRANSITION OVERLAY ===== -->
  <div class="transition-screen"></div>

  <!-- Delete Confirmation Modal -->
  <div class="delete-modal" id="deleteModal">
    <div class="delete-box">
      <h3><i class="fas fa-exclamation-triangle"></i> Delete Account?</h3>
      <p>This action cannot be undone. This will permanently delete your account and remove all your data from our servers.</p>
      <div class="warning-text">
        <i class="fas fa-skull-crossbones"></i> All your data will be lost forever!
      </div>
      <div class="delete-buttons">
        <button class="btn-delete-confirm" id="confirmDelete">
          <i class="fas fa-trash-alt"></i> Yes, Delete Forever
        </button>
        <button class="btn-delete-cancel" id="cancelDelete">
          <i class="fas fa-times"></i> Cancel
        </button>
      </div>
    </div>
  </div>

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

    // Delete Profile Modal
    const deleteBtn = document.getElementById('deleteProfileBtn');
    const deleteModal = document.getElementById('deleteModal');
    const confirmDelete = document.getElementById('confirmDelete');
    const cancelDelete = document.getElementById('cancelDelete');

    if (deleteBtn) {
      deleteBtn.addEventListener('click', function() {
        deleteModal.classList.add('show');
      });
    }

    if (cancelDelete) {
      cancelDelete.addEventListener('click', function() {
        deleteModal.classList.remove('show');
      });
    }

    if (confirmDelete) {
      confirmDelete.addEventListener('click', function() {
        // Create a form to submit the delete request
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'delete-account.php';
        document.body.appendChild(form);
        form.submit();
      });
    }

    // Close modal on outside click
    deleteModal.addEventListener('click', function(e) {
      if (e.target === deleteModal) {
        deleteModal.classList.remove('show');
      }
    });

    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape' && deleteModal.classList.contains('show')) {
        deleteModal.classList.remove('show');
      }
    });
  </script>
  
</body>
</html>