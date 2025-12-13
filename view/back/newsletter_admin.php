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
                            <th>Subscribed At</th>
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
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($subscribers)): ?>
                        <tr><td colspan="4">No subscribers found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        
    </div>
  </div>
</body>
</html>
