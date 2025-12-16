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
<<<<<<< Updated upstream
      .admin-panel { padding: 2rem; color: #fff; }
      .table-container { margin-top: 2 rem; background: rgba(0,0,0,0.5); padding: 1rem; border-radius: 8px; }
      table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
      th, td { padding: 12px; text-align: left; border-bottom: 1px solid #444; }
      th { color: #ff9900; }
<<<<<<< HEAD
=======
    .admin-panel { color:#fff; }
    .newsletter-card{
      border-radius:18px;
      background:#181818;
      box-shadow:0 6px 24px 1px rgba(0,0,0,0.18);
      padding:18px 18px 14px 18px;
      width:100%;
      max-width:1400px;
    }
    .newsletter-hero h3{ margin:0; font-size:1.2rem; }
    .newsletter-hero p{ margin:4px 0 0 0; color:#b5b5b5; }
    .newsletter-table{
      width:100%;
      border-radius:14px;
      overflow:hidden;
      background:#191919;
      box-shadow:0 1px 8px 0 rgba(0,0,0,0.10);
      border-collapse:separate;
      border-spacing:0;
    }
    .newsletter-table thead tr{
      background:#232323;
      color:#ff7a00;
      font-weight:700;
      letter-spacing:.02em;
    }
    .newsletter-table th,
    .newsletter-table td{
      padding:12px 10px;
      text-align:left;
      border-bottom:1px solid #222;
      font-size:0.98rem;
    }
    .newsletter-table tbody tr:nth-child(even){ background:#1b1b1b; }
    .newsletter-table tbody tr:hover{ background:#202020; }
    .pill-btn{
      border-radius:999px;
      font-weight:700;
      padding:9px 14px;
      line-height:1.1;
      border:2px solid transparent;
      box-shadow:0 2px 10px rgba(0,0,0,0.12);
      cursor:pointer;
      transition:all 0.15s ease;
    }
    .pill-btn:hover{ filter:brightness(1.05); transform:translateY(-1px); }
    .pill-primary{
      background:linear-gradient(90deg,#ff7a00 10%,#ffb76b 100%);
      color:#1a0d00;
      border-color:#ff7a00;
    }
    .pill-ghost{
      background:transparent;
      color:#ffb76b;
      border-color:rgba(255,183,107,0.35);
    }
    .pill-danger{
      background:rgba(255,68,68,0.08);
      color:#ff6868;
      border-color:rgba(255,68,68,0.4);
    }
>>>>>>> Stashed changes
=======

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
>>>>>>> 1a0165571eb6996c378fbe1752f04eaedfe9c7d6
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

<<<<<<< Updated upstream
  <div class="main">
    <div class="topbar">
=======

  <div class="main" style="padding:12px 1vw 10px 2vw;max-width:1800px;margin:0 auto;display:flex;flex-direction:column;gap:10px;background:radial-gradient(1200px at 20% 10%, rgba(255,122,0,0.05), transparent 55%), radial-gradient(900px at 80% 5%, rgba(255,122,0,0.06), transparent 50%);">
    <div class="topbar" style="margin-bottom:4px;">
>>>>>>> Stashed changes
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

<<<<<<< Updated upstream
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
=======
    <div class="content admin-panel" style="padding:0;margin:0;display:flex;flex-direction:column;align-items:center;gap:10px;">
      <div class="card modern-card newsletter-card" style="margin-top:6px;">
        <div class="newsletter-hero">
          <div>
            <h3>Subscribers List</h3>
            <p><?php echo count($subscribers); ?> total subscribers</p>
          </div>
        </div>

        <div class="table-container" style="margin-top:6px;">
          <table class="modern-table newsletter-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Email</th>
                <th>Interests</th>
                <th>Subscribed</th>
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
                  foreach ($sIds as $sid) {
                    if (isset($catMap[$sid]))
                      $sNames[] = $catMap[$sid];
                  }
                  echo htmlspecialchars(empty($sNames) ? '-' : implode(', ', $sNames));
                  ?></td>
                  <td><?php echo $s['created_at']; ?></td>
                  <td style="white-space:nowrap;">
                    <button class="btn-edit pill-btn pill-ghost" onclick='openEditModal(<?php echo json_encode($s); ?>)'>
                      <i class="fas fa-edit"></i> Edit
                    </button>
                    <button class="btn-delete pill-btn pill-danger" data-email="<?php echo htmlspecialchars($s["email"]); ?>">
                      <i class="fas fa-trash"></i> Delete
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (empty($subscribers)): ?>
                <tr>
                  <td colspan="5">No subscribers found.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
>>>>>>> Stashed changes
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

      <!-- Delete confirm modal -->
      <div id="deleteModal" class="modal">
        <div class="modal-content" style="max-width:420px;">
          <span class="close" onclick="closeDeleteModal()">&times;</span>
          <h3>Delete Subscriber</h3>
          <p style="color:#ddd;">Are you sure you want to delete <strong id="deleteEmailLabel"></strong>?</p>
          <div class="modal-actions" style="margin-top:1.4rem;">
            <button type="button" class="btn-cancel" onclick="closeDeleteModal()">Cancel</button>
            <button type="button" class="btn-submit" onclick="confirmDeleteSubscriber()">Delete</button>
          </div>
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

    .btn-edit, .btn-delete { border:none; background:transparent; padding:0; }

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
=======
    function deleteSubscriber(email) {
      window.__deleteEmail = email;
      const lbl = document.getElementById('deleteEmailLabel');
      if (lbl) lbl.textContent = email;
      const modal = document.getElementById('deleteModal');
      if (modal) modal.style.display = 'block';
    }

    function closeEditModal() {
      document.getElementById('editModal').style.display = 'none';
    }

    // Close when clicking outside
    window.onclick = function (event) {
      if (event.target == document.getElementById('editModal')) {
        closeEditModal();
      }
      if (event.target == document.getElementById('deleteModal')) {
        closeDeleteModal();
      }
    }

    function closeDeleteModal(){
      const modal = document.getElementById('deleteModal');
      if(modal) modal.style.display = 'none';
      window.__deleteEmail = null;
    }

    function confirmDeleteSubscriber(){
      const email = window.__deleteEmail;
      if(!email) { closeDeleteModal(); return; }
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

    // bind delete buttons
    document.addEventListener('DOMContentLoaded', function () {
      document.querySelectorAll('.btn-delete[data-email]').forEach(function(btn){
        btn.addEventListener('click', function(e){
          e.preventDefault();
          const email = this.getAttribute('data-email');
          deleteSubscriber(email);
        });
      });
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
>>>>>>> Stashed changes
</body>
</html>
