<?php
require_once __DIR__ . '/../../model/db.php';
require_once __DIR__ . '/../../model/Subscriber.php';
require_once __DIR__ . '/../../model/Categorie.php';
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

$subscribers = Subscriber::getAll();
$categories = Categorie::getAll();
$catMap = [];
foreach ($categories as $c) {
    if (is_array($c) && isset($c['idCategorie'])) {
        $catMap[$c['idCategorie']] = $c['nom'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Newsletter Admin | Nine Tailed Fox</title>
  <link rel="stylesheet" href="style.css">
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Poppins:wght@300;600&display=swap" rel="stylesheet">
  <style>
      .admin-panel { padding: 2rem; color: #fff; }
      .table-container { margin-top: 2 rem; background: rgba(0,0,0,0.5); padding: 1rem; border-radius: 8px; }
      table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
      th, td { padding: 12px; text-align: left; border-bottom: 1px solid #444; }
      th { color: #ff9900; }

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
  <div class="sidebar">
    <img src="../images/Nine__1_-removebg-preview.png" alt="Logo" class="dashboard-logo">
    <h2>Dashboard</h2>
    <a href="dashboard.php">Overview</a>
    <a href="users.php">Users</a>
    <a href="#">Shop</a>
    <a href="tradingb.php">Trade History</a>
    <a href="eventsb.php">Events</a>
    <a href="news_admin.php">News</a>
    <a href="news_history.php" id="news-history-link">News History</a>
    <a href="categories.php" id="categories-link">Categories</a>
    <a href="newsletter_admin.php" class="active">Newsletter</a>
    <a href="#">Support</a>
    <a href="../front/index.php">← Return Homepage</a>
  </div>

  <div class="main">
    <div class="topbar">
      <h1>Newsletter Management</h1>
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

    <div class="content admin-panel">
        <div class="card">
            <h3>Subscribers List</h3>
            <p>Total Subscribers: <?php echo count($subscribers); ?></p>
            
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Email</th>
                            <th>Interests IDs</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subscribers as $s): ?>
                        <tr>
                            <td><?php echo $s['id']; ?></td>
                            <td><?php echo htmlspecialchars($s['email']); ?></td>
                            <td><?php 
                                $sIds = array_filter(explode(',', $s['categories'] ?? ''));
                                $sNames = [];
                                foreach($sIds as $sid) { if(isset($catMap[$sid])) $sNames[] = $catMap[$sid]; }
                                echo htmlspecialchars(empty($sNames) ? '-' : implode(', ', $sNames));
                            ?></td>
                            <td><?php echo $s['created_at']; ?></td>
                            <td>
                                <button class="btn-edit" 
                                    onclick='openEditModal(<?php echo json_encode($s); ?>)'>
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="btn-delete" 
                                    onclick='deleteSubscriber("<?php echo htmlspecialchars($s["email"]); ?>")'>
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($subscribers)): ?>
                        <tr><td colspan="5">No subscribers found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Edit Modal -->
        <div id="editModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeEditModal()">&times;</span>
                <h3>Edit Subscription</h3>
                <form action="../../controller/NewsletterController.php" method="POST">
                    <input type="hidden" name="action" value="update_subscription">
                    <input type="hidden" id="edit_original_email" name="original_email">
                    
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" id="edit_email" name="email" required class="form-input">
                    </div>

                    <div class="form-group">
                        <label>Subscribed Categories</label>
                        <div class="checkbox-group">
                            <?php foreach ($categories as $cat): ?>
                            <label class="checkbox-label">
                                <input type="checkbox" name="categories[]" value="<?php echo $cat['idCategorie']; ?>" class="cat-checkbox">
                                <?php echo htmlspecialchars($cat['nom']); ?>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="modal-actions">
                        <button type="submit" class="btn-submit">Save Changes</button>
                        <button type="button" class="btn-cancel" onclick="closeEditModal()">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
  </div>

<style>
/* Modal Styles */
.modal {
    display: none; 
    position: fixed; 
    z-index: 1000; 
    left: 0;
    top: 0;
    width: 100%; 
    height: 100%; 
    overflow: auto; 
    background-color: rgba(0,0,0,0.8); 
    backdrop-filter: blur(5px);
}

.modal-content {
    background: #1a1a1a;
    margin: 10% auto; 
    padding: 2rem;
    border: 1px solid #ff9900;
    width: 90%;
    max-width: 500px;
    border-radius: 10px;
    box-shadow: 0 0 20px rgba(255,153,0,0.2);
    position: relative;
    color: #fff;
}

.close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.close:hover,
.close:focus {
    color: #ff9900;
    text-decoration: none;
    cursor: pointer;
}

.btn-edit {
    background: #333;
    border: 1px solid #ff9900;
    color: #ff9900;
    padding: 5px 10px;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-edit:hover {
    background: #ff9900;
    color: #000;
}

.form-group { margin-bottom: 1.5rem; }
.form-group label { display: block; margin-bottom: 0.5rem; color: #ff9900; }
.form-input { 
    width: 100%; 
    padding: 10px; 
    background: #333; 
    border: 1px solid #555; 
    color: #fff; 
    border-radius: 4px; 
}
.checkbox-group {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    background: #2a2a2a;
    padding: 10px;
    border-radius: 4px;
}
.checkbox-label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
}
.modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 2rem;
}
.btn-submit {
    background: #ff9900;
    color: #000;
    border: none;
    padding: 10px 20px;
    border-radius: 4px;
    cursor: pointer;
    font-weight: bold;
}
.btn-cancel {
    background: transparent;
    border: 1px solid #555;
    color: #ccc;
    padding: 10px 20px;
    border-radius: 4px;
    cursor: pointer;
}
</style>

<script>
function openEditModal(subscriber) {
    document.getElementById('editModal').style.display = 'block';
    document.getElementById('edit_email').value = subscriber.email;
    document.getElementById('edit_original_email').value = subscriber.email;
    
    // Clear checkboxes
    document.querySelectorAll('.cat-checkbox').forEach(cb => cb.checked = false);
    
    // Check subscribed categories
    if (subscriber.categories) {
        const ids = subscriber.categories.split(',');
        ids.forEach(id => {
            const cb = document.querySelector(`.cat-checkbox[value="${id}"]`);
            if (cb) cb.checked = true;
        });
    }
}

function deleteSubscriber(email) {
    if (!confirm('Are you sure you want to delete this subscriber: ' + email + '?')) {
        return;
    }
    
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '../../controller/NewsletterController.php';
    
    const actionInput = document.createElement('input');
    actionInput.type = 'hidden';
    actionInput.name = 'action';
    actionInput.value = 'delete_subscriber';
    form.appendChild(actionInput);
    
    const emailInput = document.createElement('input');
    emailInput.type = 'hidden';
    emailInput.name = 'email';
    emailInput.value = email;
    form.appendChild(emailInput);
    
    document.body.appendChild(form);
    form.submit();
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

// Close when clicking outside
window.onclick = function(event) {
    if (event.target == document.getElementById('editModal')) {
        closeEditModal();
    }
}

// Dropdown Menu Toggle
document.addEventListener('DOMContentLoaded', function() {
  const adminDropdown = document.getElementById('adminDropdown');
  
  if (adminDropdown) {
    const adminUser = adminDropdown.querySelector('.admin-user');
    
    // Toggle dropdown on click
    if (adminUser) {
        adminUser.addEventListener('click', function(e) {
          e.stopPropagation();
          adminDropdown.classList.toggle('active');
        });
    }
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
      if (!adminDropdown.contains(e.target)) {
        adminDropdown.classList.remove('active');
      }
    });
    
    // Close dropdown when pressing Escape
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        adminDropdown.classList.remove('active');
      }
    });
  }
});
</script>
</body>
</html>
