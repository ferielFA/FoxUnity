<?php
// Categories management page (admin view)
require_once __DIR__ . '/../../model/db.php';
require_once __DIR__ . '/../../controller/CategoryController.php';
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
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Categories - Dashboard</title>
  <link rel="stylesheet" href="style.css">
  <style>
    /* Modal matching admin theme */
    #cat-modal{display:none;position:fixed;inset:0;background:rgba(3,3,3,0.75);align-items:center;justify-content:center;z-index:9999}
    #cat-modal .modal-content{background:linear-gradient(180deg,rgba(255,255,255,0.02),rgba(255,255,255,0.01));border:1px solid rgba(255,122,0,0.08);padding:20px;border-radius:10px;width:560px;max-width:95%;}
    #cat-modal h3{font-family:'Orbitron',sans-serif;color:#ff7a00;margin-bottom:10px}
    .msg-success{background:#0b2b10;color:#b6ffb3;padding:10px;border-radius:8px;margin-bottom:10px;border-left:4px solid #2db34a}
    .msg-error{background:#2b0b0b;color:#ffd6d6;padding:10px;border-radius:8px;margin-bottom:10px;border-left:4px solid #c33}
    .table-actions .btn{margin-right:6px}

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
    
    .topbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-right: 20px;
    }
  </style>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="dashboard-body">
  <div class="sidebar">
    <img src="../images/Nine__1_-removebg-preview.png" alt="Nine Tailed Fox Logo" class="dashboard-logo">
    <h2>Dashboard</h2>
    <a href="dashboard.php">Overview</a>
    <a href="users.php">Users</a>
    <a href="#">Shop</a>
    <a href="tradingb.php">Trade History</a>
    <a href="eventsb.php">Events</a>
    <a href="news_admin.php">News</a>
    <a href="news_history.php">News History</a>
    <a href="categories.php" class="active">Categories</a>
    <a href="reclamback.php">Support</a>
    <a href="evaluations_publiques.php">Évaluations Publiques</a>
    <a href="../front/index.php">← Return Homepage</a>
  </div>

  <div class="main">
    <div class="topbar">
      <h1>Categories Management</h1>
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
      <div class="card">
        <?php if(!empty($messages) || !empty($errors)): ?>
          <div style="margin-bottom:12px">
            <?php foreach($messages as $m): ?><div class="msg-success"><?php echo htmlspecialchars($m); ?></div><?php endforeach; ?>
            <?php foreach($errors as $e): ?><div class="msg-error"><?php echo htmlspecialchars($e); ?></div><?php endforeach; ?>
          </div>
        <?php endif; ?>

        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
          <h2 style="margin:0">Categories (<?php echo count($categories); ?>)</h2>
          <a class="btn" href="#" id="btn-add">+ Add Category</a>
        </div>

        <table class="admin-table" style="width:100%">
          <thead><tr><th>ID</th><th>Name</th><th>Slug</th><th>Description</th><th>Articles</th><th>Status</th><th>Position</th><th>Actions</th></tr></thead>
          <tbody>
            <?php foreach($categories as $c): ?>
              <tr>
                <td><?php echo intval($c['idCategorie']); ?></td>
                <td><?php echo htmlspecialchars($c['nom']); ?></td>
                <td><?php echo htmlspecialchars($c['slug']); ?></td>
                <td><?php echo htmlspecialchars($c['description']); ?></td>
                <td><?php echo intval($counts[$c['idCategorie']] ?? 0); ?></td>
                <td><?php echo $c['active']? 'Active':'Inactive'; ?></td>
                <td><?php echo intval($c['position']); ?></td>
                <td class="table-actions">
                  <form style="display:inline" method="post" action="categories.php">
                    <input type="hidden" name="action" value="toggle">
                    <input type="hidden" name="id" value="<?php echo intval($c['idCategorie']); ?>">
                    <input type="hidden" name="active" value="<?php echo $c['active']?0:1; ?>">
                    <button class="btn" type="submit"><?php echo $c['active']? 'Disable':'Enable'; ?></button>
                  </form>
                  <a class="btn" href="#" data-edit='<?php echo json_encode($c); ?>'>Edit</a>
                  <form style="display:inline" method="post" action="categories.php" onsubmit="return confirm('Delete this category?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?php echo intval($c['idCategorie']); ?>">
                    <button class="btn" type="submit">Delete</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Add/Edit Modal (simple) -->
  <div id="cat-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.6);align-items:center;justify-content:center">
    <div style="background:#0b0b0b;padding:20px;border-radius:8px;max-width:560px;width:100%">
      <h3 id="modal-title">Add Category</h3>
      <form id="cat-form" class="admin-form" method="post" action="categories.php">
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="id" value="">
        <label>Name</label>
        <input name="name" id="cat-name">
        <label>Slug (optional)</label>
        <input name="slug" id="cat-slug">
        <label>Description</label>
        <textarea name="description" id="cat-desc" rows="3"></textarea>
        <label style="display:flex;align-items:center;gap:8px"><input type="checkbox" name="active" id="cat-active" checked> Active</label>
        <p><button class="btn" type="submit">Save</button> <button class="btn" type="button" id="cat-cancel">Cancel</button></p>
      </form>
    </div>
  </div>

  <script>
    document.getElementById('btn-add').addEventListener('click', function(e){ e.preventDefault(); openModal(); });
    document.getElementById('cat-cancel').addEventListener('click', function(){ closeModal(); });
    function openModal(data){
      document.getElementById('cat-modal').style.display='flex';
      if(data){ document.getElementById('modal-title').textContent='Edit Category'; document.querySelector('input[name=action]').value='edit'; document.querySelector('input[name=id]').value=data.idCategorie; document.getElementById('cat-name').value=data.nom; document.getElementById('cat-slug').value=data.slug; document.getElementById('cat-desc').value=data.description; document.getElementById('cat-active').checked = data.active?true:false; } else { document.getElementById('modal-title').textContent='Add Category'; document.querySelector('input[name=action]').value='add'; document.querySelector('input[name=id]').value=''; document.getElementById('cat-name').value=''; document.getElementById('cat-slug').value=''; document.getElementById('cat-desc').value=''; document.getElementById('cat-active').checked=true; }
    }
    function closeModal(){ document.getElementById('cat-modal').style.display='none'; }
    Array.from(document.querySelectorAll('a[data-edit]')).forEach(function(el){ el.addEventListener('click', function(e){ e.preventDefault(); var d = JSON.parse(this.getAttribute('data-edit')); openModal(d); }); });

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
