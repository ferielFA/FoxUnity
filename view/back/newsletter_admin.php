<?php
require_once __DIR__ . '/../../model/db.php';
require_once __DIR__ . '/../../model/Subscriber.php';
require_once __DIR__ . '/../../model/Categorie.php';

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
  </style>
</head>
<body class="dashboard-body">
  <div class="sidebar">
    <img src="../images/Nine__1_-removebg-preview.png" alt="Logo" class="dashboard-logo">
    <h2>Dashboard</h2>
    <a href="dashboard.php">Overview</a>
    <a href="news_admin.php">News</a>
    <a href="newsletter_admin.php" class="active">Newsletter</a>
    <a href="../front/index.php">Return Homepage</a>
  </div>

  <div class="main">
    <div class="topbar">
      <h1>Newsletter Management</h1>
      <div class="user">
        <img src="../images/meriem.png" alt="Admin">
        <span>FoxLeader</span>
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
                                    Edit
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

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}

// Close when clicking outside
window.onclick = function(event) {
    if (event.target == document.getElementById('editModal')) {
        closeEditModal();
    }
}
</script>
</body>
</html>
