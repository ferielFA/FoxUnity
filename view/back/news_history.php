<?php
// News History page - Dedicated page for viewing article edit history
// Usage: Access via dashboard sidebar "News History" link

require_once __DIR__ . '/../../model/db.php';
require_once __DIR__ . '/../../model/Article.php';

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
    <a href="#">Users</a>
    <a href="#">Shop</a>
    <a href="#">Trade History</a>
    <a href="#">Events</a>
    <a href="news_admin.php">News</a>
    <a href="news_history.php" class="active">News History</a>
    <a href="#">Support</a>
    <a href="../front/index.php">← Return Homepage</a>
  </div>

  <div class="main">
    <div class="topbar">
      <h1>News History</h1>
      <div class="user">
        <img src="../images/rayen.png" alt="User Avatar">
        <span>FoxAdmin</span>
      </div>
    </div>

    <div class="content">
      <div class="card" style="width:100%">
        <h2 style="margin:0 0 16px">Article Edit History</h2>
        <div id="history-list" style="max-height:600px;overflow-y:auto">
          <?php if (empty($historyData)): ?>
            <p style="color:#bbb">No history available yet.</p>
          <?php else:
            foreach ($historyData as $articleId => $data) {
              $article = $data['article'];
              $history = $data['history'];
              echo '<div style="background:#111;padding:12px;border-radius:8px;margin-bottom:12px;border-left:3px solid #ff7a00">';
              echo '<strong>' . htmlspecialchars($article['title']) . '</strong> (' . count($history) . ' edit' . (count($history)!==1?'s':'') . ')<br>';
              echo '<table style="width:100%;margin-top:8px;font-size:0.85rem;border-collapse:collapse">';
              echo '<thead><tr style="border-bottom:1px solid #333"><th style="text-align:left;padding:6px">Edited At</th><th style="text-align:left;padding:6px">Edited By</th><th style="text-align:left;padding:6px">Title</th></tr></thead><tbody>';
              foreach ($history as $h) {
                echo '<tr style="border-bottom:1px solid #222">';
                echo '<td style="padding:6px">' . htmlspecialchars($h['edited_at']) . '</td>';
                echo '<td style="padding:6px">' . htmlspecialchars($h['edited_by_name'] ?? 'Unknown') . '</td>';
                echo '<td style="padding:6px">' . htmlspecialchars($h['titre']) . '</td>';
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
    window.addEventListener('load', function(){
      try{
        var t = document.querySelector('.transition-screen');
        if(t) t.classList.add('hidden');
      }catch(e){}
    });
  </script>
</body>
</html>
