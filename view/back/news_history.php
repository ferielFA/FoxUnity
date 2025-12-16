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
      <div class="card modern-card" style="width:100%; border-radius:20px; background: #181818; box-shadow: 0 6px 32px 2px rgba(0,0,0,0.22); padding: 36px 32px 32px 32px; margin-top:28px; max-width:100%; min-height:unset;">
  <span class="modern-section-title" style="font-size:1.17em;"><i class="fas fa-clock-rotate-left" style="margin-right:8px;color:#ff7a00;"></i>Article Edit History</span>
  <div id="history-list" style="margin-top:18px;">
          <?php if (empty($historyData)): ?>
            <p style="color:#bbb">No history available yet.</p>
          <?php else:
            foreach ($historyData as $articleId => $data) {
              $article = $data['article'];
              $history = $data['history'];
              echo '<div class="history-article-box" style="background:#151313;padding:18px 14px;border-radius:15px;margin-bottom:17px;border-left:3px solid #ff7a00;box-shadow:0 3px 16px 0 rgba(255,122,0,0.06);">';
              echo '<strong>' . htmlspecialchars($article['title']) . '</strong> (' . count($history) . ' edit' . (count($history)!==1?'s':'') . ')<br>';
              echo '<table style="width:100%;margin-top:8px;font-size:0.85rem;border-collapse:collapse">';
              echo '<thead><tr style="background:#232323;color:#ff7a00;font-weight:bold;"><th style="text-align:left;padding:10px 8px;">Edited At</th><th style="text-align:left;padding:10px 8px;">Edited By</th><th style="text-align:left;padding:10px 8px;">Title</th></tr></thead><tbody>';
              foreach ($history as $h) {
                echo '<tr style="background:#191919;">';
                echo '<td style="padding:8px 7px;border-bottom:1px solid #242323">' . htmlspecialchars($h['edited_at']) . '</td>';
                echo '<td style="padding:8px 7px;border-bottom:1px solid #242323">' . htmlspecialchars($h['edited_by_name'] ?? 'Unknown') . '</td>';
                echo '<td style="padding:8px 7px;border-bottom:1px solid #242323">' . htmlspecialchars($h['titre']) . '</td>';
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
