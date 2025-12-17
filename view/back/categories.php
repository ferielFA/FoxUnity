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

// Fetch categories for display
// Ideally this should be handled by a controller method that returns data, but for this view we might need to fetch directly or use model
$categories = Categorie::getAll();
// Fetch article counts per category (optional optimization, for now we can just show 0 or implement a count method)
$counts = [];
foreach ($categories as $c) {
  // Basic counting if needed or just skip
  $counts[$c['idCategorie']] = 0; // Placeholder
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
    #cat-modal {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(3, 3, 3, 0.75);
      align-items: center;
      justify-content: center;
      z-index: 9999;
      backdrop-filter: blur(4px);
    }

    #cat-modal .modal-content {
      background: linear-gradient(180deg, rgba(255, 255, 255, 0.02), rgba(255, 255, 255, 0.01));
      border: 1px solid rgba(255, 122, 0, 0.08);
      padding: 20px;
      border-radius: 10px;
      width: 560px;
      max-width: 95%;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
    }

    #cat-modal h3 {
      font-family: 'Orbitron', sans-serif;
      color: #ff7a00;
      margin-bottom: 10px
    }

    .msg-success {
      background: #0b2b10;
      color: #b6ffb3;
      padding: 10px;
      border-radius: 8px;
      margin-bottom: 10px;
      border-left: 4px solid #2db34a
    }

    .msg-error {
      background: #2b0b0b;
      color: #ffd6d6;
      padding: 10px;
      border-radius: 8px;
      margin-bottom: 10px;
      border-left: 4px solid #c33
    }

    .table-actions .btn {
      margin-right: 6px
    }

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

    /* Modern styling for Categories page */
    .cat-hero {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 12px;
      flex-wrap: wrap;
      margin-bottom: 20px;
    }

    .cat-hero h2 {
      margin: 0;
      font-size: 1.18rem;
      letter-spacing: .02em;
    }

    .cat-hero p {
      margin: 4px 0 0 0;
      color: #b4b4b4;
      font-size: 0.96rem;
    }

    .modern-card.cat-card {
      border-radius: 18px;
      background: #181818;
      box-shadow: 0 6px 18px 0 rgba(0, 0, 0, 0.16);
      padding: 24px;
      margin: 8px auto 0 auto;
      max-width: 1400px;
      width: 100%;
      display: flex;
      flex-direction: column;
      gap: 10px;
    }

    .cat-table {
      width: 100%;
      border-radius: 14px;
      overflow: hidden;
      background: #191919;
      box-shadow: 0 1px 6px 0 rgba(0, 0, 0, 0.08);
      border-collapse: separate;
      border-spacing: 0;
    }

    .cat-table thead tr {
      background: #232323;
      color: #ff7a00;
      font-weight: 700;
      letter-spacing: .02em;
    }

    .cat-table th,
    .cat-table td {
      padding: 12px 10px;
      text-align: left;
      border-bottom: 1px solid #222;
      font-size: 0.98rem;
    }

    .cat-table tbody tr:nth-child(even) {
      background: #1b1b1b;
    }

    .cat-table tbody tr:hover {
      background: #202020;
    }

    .status-pill {
      padding: 6px 12px;
      border-radius: 999px;
      font-weight: 700;
      font-size: 0.92rem;
      border: 1px solid transparent;
      display: inline-block;
    }

    .status-active {
      background: rgba(46, 204, 113, 0.12);
      color: #2ecc71;
      border-color: rgba(46, 204, 113, 0.35);
    }

    .status-inactive {
      background: rgba(255, 68, 68, 0.12);
      color: #ff4444;
      border-color: rgba(255, 68, 68, 0.35);
    }

    .pill-btn {
      border-radius: 999px;
      font-weight: 700;
      padding: 9px 16px;
      line-height: 1.1;
      border: 2px solid transparent;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.12);
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      cursor: pointer;
    }

    .pill-primary {
      background: linear-gradient(90deg, #ff7a00 10%, #ffb76b 100%);
      color: #1a0d00;
      border-color: #ff7a00;
    }

    .pill-secondary {
      background: #151515;
      color: #ff7a00;
      border-color: rgba(255, 122, 0, 0.4);
    }

    .pill-ghost {
      background: transparent;
      color: #ffb76b;
      border-color: rgba(255, 183, 107, 0.35);
    }

    .pill-danger {
      background: rgba(255, 68, 68, 0.08);
      color: #ff6868;
      border-color: rgba(255, 68, 68, 0.4);
    }

    .pill-btn:hover {
      filter: brightness(1.05);
      transform: translateY(-1px);
      transition: all 0.15s ease;
    }

    .category-badge {
      color: #b0b0b0;
      /* Faded gray text */
      background: rgba(255, 255, 255, 0.05);
      /* Very subtle background */
      border: 1px solid rgba(255, 255, 255, 0.1);
      padding: 4px 10px;
      border-radius: 6px;
      font-size: 0.9em;
      font-weight: 500;
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
    <a href="shopb.php">Shop</a>
    <a href="tradingb.php">Trade History</a>
    <a href="eventsb.php">Events</a>
    <a href="news_admin.php">News</a>
    <a href="news_history.php">News History</a>
    <a href="categories.php" class="active">Categories</a>
    <a href="newsletter_admin.php">Newsletter</a>
    <a href="evaluations_publiques.php">Évaluations Publiques</a>
    <a href="../front/index.php">← Return Homepage</a>
  </div>

  <div class="main"
    style="padding:18px 1vw 14px 2vw;max-width:1800px;margin:0 auto;min-height:calc(100vh - 70px);display:flex;flex-direction:column;justify-content:flex-start;align-items:stretch;background:radial-gradient(1200px at 20% 20%, rgba(255,122,0,0.05), transparent 55%), radial-gradient(900px at 80% 10%, rgba(255,122,0,0.06), transparent 50%);">
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

    <div class="content"
      style="padding:0;margin:0;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:10px;width:100%;">
      <div class="card modern-card cat-card" style="margin:8px auto 0 auto;">

        <!-- Messages -->
        <div id="msg-container">
          <?php if (isset($_GET['msg'])): ?>
            <div class="msg-success"><?php echo htmlspecialchars($_GET['msg']); ?></div>
          <?php endif; ?>
          <?php if (isset($_GET['err'])): ?>
            <div class="msg-error"><?php echo htmlspecialchars($_GET['err']); ?></div>
          <?php endif; ?>
        </div>

        <div class="cat-hero">
          <div>
            <h2>Categories List</h2>
            <p>Organize and control visibility for each section.</p>
          </div>
          <button class="pill-btn pill-primary" type="button" id="btn-add">
            <i class="fas fa-plus-circle"></i> Add Category
          </button>
        </div>

        <div style="overflow-x:auto;">
          <table class="admin-table modern-table cat-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Slug</th>
                <th>Description</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($categories as $c): ?>
                <tr>
                  <td><?php echo intval($c['idCategorie']); ?></td>
                  <td><span class="category-badge"><?php echo htmlspecialchars($c['nom']); ?></span></td>
                  <td><?php echo htmlspecialchars($c['slug']); ?></td>
                  <td><span style="color:#888;"><?php echo htmlspecialchars($c['description']); ?></span></td>
                  <td><span
                      class="status-pill <?php echo $c['active'] ? 'status-active' : 'status-inactive'; ?>"><?php echo $c['active'] ? 'Active' : 'Inactive'; ?></span>
                  </td>
                  <td class="table-actions" style="white-space:nowrap;">

                    <!-- Toggle Status Form -->
                    <form style="display:inline" method="post" action="../../controller/CategoryController.php">
                      <input type="hidden" name="action" value="toggle">
                      <input type="hidden" name="id" value="<?php echo intval($c['idCategorie']); ?>">
                      <input type="hidden" name="current_status" value="<?php echo $c['active'] ? '1' : '0'; ?>">
                      <button class="btn pill-btn pill-secondary" style="font-size:0.8rem; padding:6px 10px;"
                        type="submit">
                        <?php echo $c['active'] ? '<i class="fas fa-eye-slash"></i> Disable' : '<i class="fas fa-eye"></i> Enable'; ?>
                      </button>
                    </form>

                    <!-- Edit Button -->
                    <button class="btn pill-btn pill-ghost" style="font-size:0.8rem; padding:6px 10px;" type="button"
                      data-edit='<?php echo json_encode($c); ?>'>
                      <i class="fas fa-edit"></i> Edit
                    </button>

                    <!-- Delete Form -->
                    <form style="display:inline" method="post" action="../../controller/CategoryController.php"
                      onsubmit="return confirm('Delete this category?');">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?php echo intval($c['idCategorie']); ?>">
                      <button class="btn pill-btn pill-danger" style="font-size:0.8rem; padding:6px 10px;" type="submit">
                        <i class="fas fa-trash"></i>
                      </button>
                    </form>

                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (empty($categories)): ?>
                <tr>
                  <td colspan="6" style="text-align:center; padding:20px; color:#666;">No categories found.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Add/Edit Modal -->
  <div id="cat-modal">
    <div class="modal-content">
      <h3 id="modal-title">Add Category</h3>
      <form id="cat-form" class="admin-form" method="post" action="../../controller/CategoryController.php">
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="id" value="">

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;">
          <div style="display:flex;flex-direction:column;gap:6px;">
            <label style="margin:0;color:#aaa;font-size:0.9rem;">Name</label>
            <input name="name" id="cat-name" required
              style="width:100%;padding:10px;border-radius:8px;border:1px solid #333;background:#1a1a1a;color:#fff;">
          </div>
          <div style="display:flex;flex-direction:column;gap:6px;">
            <label style="margin:0;color:#aaa;font-size:0.9rem;">Slug (optional)</label>
            <input name="slug" id="cat-slug"
              style="width:100%;padding:10px;border-radius:8px;border:1px solid #333;background:#1a1a1a;color:#fff;">
          </div>
        </div>

        <div style="display:flex;flex-direction:column;gap:6px;margin-bottom:15px;">
          <label style="margin:0;color:#aaa;font-size:0.9rem;">Description</label>
          <textarea name="description" id="cat-desc" required rows="3"
            style="width:100%;padding:10px;border-radius:8px;border:1px solid #333;background:#1a1a1a;color:#fff;resize:vertical;"></textarea>
        </div>

        <div style="margin-bottom:20px;">
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer;color:#fff;">
            <input type="checkbox" name="active" id="cat-active" value="1" checked>
            Active Status
          </label>
        </div>

        <div style="display:flex;gap:10px;justify-content:flex-end;">
          <button class="pill-btn pill-ghost" type="button" id="cat-cancel">Cancel</button>
          <button class="pill-btn pill-primary" type="submit">Save Category</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    // Modal Logic
    const modal = document.getElementById('cat-modal');
    const btnAdd = document.getElementById('btn-add');
    const btnCancel = document.getElementById('cat-cancel');
    const form = document.getElementById('cat-form');
    const title = document.getElementById('modal-title');
    const inputAction = form.querySelector('input[name=action]');
    const inputId = form.querySelector('input[name=id]');

    btnAdd.addEventListener('click', function (e) {
      e.preventDefault();
      openModal();
    });

    btnCancel.addEventListener('click', function () {
      closeModal();
    });

    function openModal(data = null) {
      modal.style.display = 'flex';
      if (data) {
        title.textContent = 'Edit Category';
        inputAction.value = 'update'; // Controller expects 'update' or 'edit'
        inputId.value = data.idCategorie;
        document.getElementById('cat-name').value = data.nom;
        document.getElementById('cat-slug').value = data.slug;
        document.getElementById('cat-desc').value = data.description;
        document.getElementById('cat-active').checked = (data.active == 1);
      } else {
        title.textContent = 'Add Category';
        inputAction.value = 'add';
        inputId.value = '';
        form.reset();
        document.getElementById('cat-active').checked = true;
      }
    }

    function closeModal() {
      modal.style.display = 'none';
    }

    // Attach Edit Listeners
    document.querySelectorAll('button[data-edit]').forEach(btn => {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        const data = JSON.parse(this.getAttribute('data-edit'));
        openModal(data);
      });
    });

    // Close on outside click
    window.onclick = function (e) {
      if (e.target === modal) closeModal();
    }

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