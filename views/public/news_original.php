<?php
// news.php
// Loads all news from MySQL (article / categorie tables) and shows them

require __DIR__ . '/../back/db.php';

// Helper to resolve image paths and check if they exist
function getImagePath($imagePath) {
    if (empty($imagePath)) return '../images/nopic.png';
    // If path starts with uploads/, it's a relative path from this file
    if (strpos($imagePath, 'uploads/') === 0) {
        $fullPath = __DIR__ . '/../back/' . $imagePath;
        if (file_exists($fullPath)) return $imagePath;
    }
    // If path starts with ../, it's already relative to this file
    if (strpos($imagePath, '../') === 0) {
        $fullPath = __DIR__ . '/' . $imagePath;
        if (file_exists($fullPath)) return $imagePath;
    }
    // Check if it's an absolute-ish path in images/
    if (strpos($imagePath, 'images/') === 0 || strpos($imagePath, '../images/') === 0) {
        return $imagePath;
    }
    return '../images/nopic.png';
}

// Load articles from DB with hot column handling
try {
  $stmt = $pdo->query("SELECT
    a.idArticle,
    a.slug        AS id,
    a.titre       AS title,
    a.datePublication AS date,
    a.datePublication,
    a.idCategorie,
    c.nom         AS category,
    a.excerpt,
    a.contenu     AS content,
    a.image,
    a.hot
  FROM article a
  LEFT JOIN categorie c ON c.idCategorie = a.idCategorie
  ORDER BY a.hot DESC, a.datePublication DESC, a.idArticle DESC");
} catch (PDOException $e) {
  // If hot column doesn't exist, add it and retry
  if (strpos($e->getMessage(), 'Unknown column') !== false) {
    $pdo->exec("ALTER TABLE article ADD COLUMN hot TINYINT(1) NOT NULL DEFAULT 0");
    $stmt = $pdo->query("SELECT
      a.idArticle,
      a.slug        AS id,
      a.titre       AS title,
      a.datePublication AS date,
      a.datePublication,
      a.idCategorie,
      c.nom         AS category,
      a.excerpt,
      a.contenu     AS content,
      a.image,
      a.hot
    FROM article a
    LEFT JOIN categorie c ON c.idCategorie = a.idCategorie
    ORDER BY a.hot DESC, a.datePublication DESC, a.idArticle DESC");
  } else {
    throw $e;
  }
}
$articles = $stmt->fetchAll();

// Separate hot news and regular news
$hotNews = array_filter($articles, function($a) { return $a['hot'] == 1; });
$articles = array_filter($articles, function($a) { return $a['hot'] == 0 || $a['hot'] == null; });

function findCategoryName($id, $categories){
  foreach($categories as $c) if (($c['idCategorie'] ?? 0) == $id) return $c['nom'];
  return null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gaming News — FoxUnity</title>
  <link rel="stylesheet" href="../front/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    .news-container{max-width:1280px;margin:120px auto 60px;padding:0 40px}
    .news-title{
      font-family:Orbitron,system-ui;
      font-size:3.6rem;
      color:var(--accent);
      margin:0 0 50px;
      text-shadow:0 0 30px rgba(255,120,0,0.3), 0 0 60px rgba(255,120,0,0.15);
      letter-spacing:2px;
      font-weight:700;
      position:relative;
      padding-bottom:20px;
    }
    .news-title::after{
      content:'';
      position:absolute;
      bottom:0;
      left:0;
      width:60px;
      height:3px;
      background:linear-gradient(90deg, #ff7a00, rgba(255,122,0,0));
      border-radius:2px;
    }
    /* Hot news strip */
    .hot-news{display:flex;flex-direction:column;gap:16px;margin-bottom:28px}
    .hot-news h3{margin:0;color:#ffb86b;font-family:Orbitron,system-ui;font-size:1.2rem}
    .hot-list{display:flex;gap:16px;flex-wrap:wrap}
    .hot-card{flex:1 1 320px;background:linear-gradient(135deg, rgba(40,40,40,0.9), rgba(20,20,20,0.9));border-radius:12px;display:flex;gap:12px;align-items:center;padding:10px;border:1px solid rgba(255,120,0,0.08);text-decoration:none}
    .hot-card img{width:140px;height:80px;object-fit:cover;border-radius:8px}
    .hot-card .hot-meta{color:#ddd}
    .hot-card .hot-meta h4{margin:0;font-size:1rem;color:var(--accent);font-weight:700}
    .hot-card .hot-meta p{margin:4px 0 0;font-size:0.9rem;color:#bbb}
    .news-grid{
      display:grid;
      grid-template-columns:repeat(auto-fit,minmax(360px,1fr));
      gap:32px;
      margin-bottom:40px;
    }
    .news-card{
      background:linear-gradient(135deg, rgba(30,30,30,0.9), rgba(20,20,20,0.95));
      border:1px solid rgba(255,120,0,0.1);
      border-radius:16px;
      overflow:hidden;
      transition:0.35s cubic-bezier(0.34,1.56,0.64,1);
      cursor:pointer;
      position:relative;
      display:flex;
      flex-direction:column;
      height:100%;
      backdrop-filter:blur(10px);
    }
    .news-card::before{
      content:'';
      position:absolute;
      top:0;
      left:0;
      right:0;
      height:3px;
      background:linear-gradient(90deg, #ff7a00, #ff9900, transparent);
      opacity:0;
      transition:opacity 0.35s ease;
    }
    .news-card.new-article::after {
      content: 'NEW';
      position: absolute;
      top: 12px;
      right: 12px;
      background: linear-gradient(135deg, #ff7a00, #ff4f00);
      color: #fff;
      padding: 4px 10px;
      border-radius: 20px;
      font-size: 0.7rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      z-index: 10;
      box-shadow: 0 4px 12px rgba(255, 122, 0, 0.5);
    }
    .news-card:hover{
      border-color:rgba(255,120,0,0.5);
      box-shadow:0 0 30px rgba(255,120,0,0.2), inset 0 0 40px rgba(255,120,0,0.05);
      transform:translateY(-8px);
      background:linear-gradient(135deg, rgba(35,35,35,0.95), rgba(25,25,25,0.98));
    }
    .news-card:hover::before{opacity:1}
    .news-card-category{
      display:inline-block;
      background:linear-gradient(135deg, rgba(255,122,0,0.2), rgba(255,122,0,0.1));
      color:#ff9900;
      font-size:0.75rem;
      font-weight:700;
      padding:5px 12px;
      border-radius:20px;
      text-transform:uppercase;
      letter-spacing:0.5px;
      border:1px solid rgba(255,122,0,0.3);
      margin-bottom:12px;
      width:fit-content;
    }
    .news-card-image{
      width:100%;
      height:220px;
      background:linear-gradient(135deg, rgba(255,120,0,0.15), rgba(255,120,0,0.05));
      display:flex;
      align-items:center;
      justify-content:center;
      font-size:3.2rem;
      color:var(--accent);
      opacity:0.8;
      transition:0.4s ease;
      border-bottom:1px solid rgba(255,120,0,0.1);
      position:relative;
      overflow:hidden;
    }
    .news-card-image img{
      width:100%;
      height:100%;
      object-fit:cover;
      display:block;
      transition:0.4s ease;
    }
    .news-card-image::after{
      content:'';
      position:absolute;
      top:0;
      left:0;
      right:0;
      bottom:0;
      background:radial-gradient(circle at center, rgba(255,120,0,0.1), transparent);
      pointer-events:none;
    }
    .news-card:hover .news-card-image{
      opacity:1;
    }
    .news-card:hover .news-card-image img{
      transform:scale(1.05);
    }
    .news-card-content{
      padding:28px;
      flex:1;
      display:flex;
      flex-direction:column;
    }
    .news-card-date{
      font-size:0.82rem;
      color:#888;
      margin-bottom:12px;
      text-transform:uppercase;
      letter-spacing:0.5px;
      font-weight:600;
    }
    .news-card-title{
      font-family:Orbitron,system-ui;
      font-size:1.3rem;
      color:var(--accent);
      margin:0 0 16px;
      line-height:1.4;
      transition:0.3s ease;
      font-weight:700;
    }
    .news-card:hover .news-card-title{
      text-shadow:0 0 15px rgba(255,120,0,0.4);
    }
    .news-card-excerpt{
      color:#bbb;
      font-size:0.96rem;
      line-height:1.6;
      margin:0 0 20px;
      flex:1;
      font-weight:400;
    }
    .read-more{
      display:inline-block;
      color:var(--accent);
      text-decoration:none;
      font-weight:700;
      font-size:0.88rem;
      letter-spacing:0.5px;
      transition:0.3s ease;
      position:relative;
      width:fit-content;
      text-transform:uppercase;
    }
    .read-more::after{
      content:'';
      position:absolute;
      bottom:-2px;
      left:0;
      width:0;
      height:2px;
      background:linear-gradient(90deg, #ff7a00, transparent);
      transition:width 0.35s ease;
    }
    .news-card:hover .read-more::after{width:100%}
    .news-card:hover .read-more{
      color:#ffaa00;
      text-shadow:0 0 10px rgba(255,120,0,0.3);
    }
    @media (max-width:820px){
      .news-container{margin:100px auto 40px;padding:0 24px}
      .news-title{font-size:2.6rem;margin-bottom:36px}
      .news-grid{grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:24px}
      .news-card-image{height:180px;font-size:2.6rem}
      .news-card-content{padding:20px}
    }
    @media (max-width:480px){
      .news-title{font-size:1.8rem;margin-bottom:28px;letter-spacing:1px}
      .news-grid{grid-template-columns:1fr;gap:16px}
      .news-card-image{height:160px;font-size:2.2rem}
      .news-card-content{padding:16px}
      .news-card-title{font-size:1.1rem}
      .news-card-excerpt{font-size:0.9rem;line-height:1.5}
    }
  </style>
</head>
<body>
  <!-- Bulles animées rouges -->
  <div class="bubbles">
    <div class="bubble"></div>
    <div class="bubble"></div>
    <div class="bubble"></div>
    <div class="bubble"></div>
    <div class="bubble"></div>
    <div class="bubble"></div>
    <div class="bubble"></div>
    <div class="bubble"></div>
  </div>

  <header class="site-header">
    <div class="logo-section">
  <img src="../images/Nine__1_-removebg-preview.png" alt="FoxUnity Logo" class="site-logo">
      <span class="site-name">FoxUnity</span>
    </div>
    
    <nav class="site-nav">
      <a href="../front/indexf.html">Home</a>
      <a href="../front/events.html">Events</a>
      <a href="../front/shop.html">Shop</a>
      <a href="../front/trading.html">Trading</a>
      <a href="news.php" class="active">News</a>
      <a href="../front/reclamation.html">Support</a>
      <a href="../front/about.html">About Us</a>
    </nav>
    
    <div class="header-right">
      <a href="../front/login.html" class="login-register-link">
        <i class="fas fa-user"></i> Login / Register
      </a>
      <a href="../front/profile.html" class="profile-icon">
        <i class="fas fa-user-circle"></i>
      </a>
    </div>
  </header>

  <main class="main-section">
    <div class="news-container">
        <h1 class="news-title">Gaming News</h1>
        <div class="news-controls">
          <input id="news-search" class="news-search" placeholder="Search news by title or excerpt...">
          <select id="news-perpage" class="news-perpage">
            <option value="6">6 per page</option>
            <option value="9">9 per page</option>
            <option value="12">12 per page</option>
          </select>
        </div>

      <div class="hot-news">
        <h3>🔥 Hot News</h3>
        <div class="hot-list">
          <?php if (!empty($hotNews)): ?>
            <?php foreach ($hotNews as $h): ?>
              <a class="hot-card" href="news_article.php?id=<?php echo urlencode($h['id']); ?>">
                <img src="<?php echo htmlspecialchars(getImagePath($h['image'] ?? '')); ?>" alt="<?php echo htmlspecialchars($h['title'] ?? ''); ?>" onerror="this.src='../images/nopic.png'">
                <div class="hot-meta">
                  <h4><?php echo htmlspecialchars($h['title'] ?? ''); ?></h4>
                  <p><?php echo htmlspecialchars(($h['date'] ?? '') . ' — ' . substr($h['excerpt'] ?? '', 0, 80)); ?></p>
                </div>
              </a>
            <?php endforeach; ?>
          <?php else: ?>
            <p style="color:#888; font-style:italic;">No hot news at the moment.</p>
          <?php endif; ?>
        </div>
      </div>

    <div class="news-grid" id="news-grid">
      <?php foreach ($articles as $a): ?>
        <?php 
          $dispCat = !empty($a['idCategorie']) ? ($a['category'] ?? '') : ($a['category'] ?? '');
          $imgPath = getImagePath($a['image'] ?? '');
          // Mark as new if datePublication is today
          $isNew = (($a['date'] ?? '') === date('Y-m-d')) ? 'new-article' : '';
        ?>
        <div id="news-<?php echo htmlspecialchars($a['id']); ?>" class="news-card <?php echo $isNew; ?>">
          <div class="news-card-image">
            <img src="<?php echo htmlspecialchars($imgPath); ?>" alt="<?php echo htmlspecialchars($a['title'] ?? ''); ?>" onerror="this.src='../images/nopic.png'">
          </div>
          <div class="news-card-content">
            <div class="news-card-date"><?php echo htmlspecialchars($a['date'] ?? ''); ?> • <?php echo htmlspecialchars($dispCat); ?></div>
            <h2 class="news-card-title"><?php echo htmlspecialchars($a['title'] ?? ''); ?></h2>
            <p class="news-card-excerpt"><?php echo htmlspecialchars($a['excerpt'] ?? ''); ?></p>
            <a class="read-more" href="news_article.php?id=<?php echo urlencode($a['id']); ?>">Read More →</a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="pagination" id="news-pagination" style="justify-content:center"></div>
    <script>
      // Client-side search + pagination for news cards
      (function(){
        var grid = document.getElementById('news-grid');
        if(!grid) return;
        var cards = Array.from(grid.querySelectorAll('.news-card'));
        var perSelect = document.getElementById('news-perpage');
        var search = document.getElementById('news-search');
        var pager = document.getElementById('news-pagination');
        var per = parseInt(perSelect.value,10) || 6;
        var filtered = cards.slice();
        function renderPage(page){
          var start = (page-1)*per; var end = start+per;
          cards.forEach(function(c){ c.style.display='none'; });
          filtered.slice(start,end).forEach(function(c){ c.style.display='flex'; });
          renderPager(Math.ceil(filtered.length/per), page);
        }
        function renderPager(totalPages, current){
          pager.innerHTML='';
          if(totalPages<=1) return;

          // Prev button
          var prev = document.createElement('button');
          prev.textContent = '‹';
          prev.setAttribute('aria-label','Previous page');
          prev.dataset.page = Math.max(1, current-1);
          if(current === 1) prev.disabled = true;
          prev.addEventListener('click', function(){ renderPage(parseInt(this.dataset.page,10)); });
          pager.appendChild(prev);

          // Page number buttons
          for(var i=1;i<=totalPages;i++){
            var btn = document.createElement('button'); btn.textContent=i; btn.dataset.page=i;
            if(i===current) btn.className='active';
            btn.addEventListener('click', function(){ renderPage(parseInt(this.dataset.page,10)); });
            pager.appendChild(btn);
          }

          // Next button
          var next = document.createElement('button');
          next.textContent = '›';
          next.setAttribute('aria-label','Next page');
          next.dataset.page = Math.min(totalPages, current+1);
          if(current === totalPages) next.disabled = true;
          next.addEventListener('click', function(){ renderPage(parseInt(this.dataset.page,10)); });
          pager.appendChild(next);
        }
        function applyFilter(){
          var q = (search.value||'').trim().toLowerCase();
          filtered = cards.filter(function(c){
            if(!q) return true;
            var txt = (c.textContent||'').toLowerCase();
            return txt.indexOf(q) !== -1;
          });
          per = parseInt(perSelect.value,10) || 6;
          renderPage(1);
        }
        perSelect.addEventListener('change', applyFilter);
        search.addEventListener('input', function(){ setTimeout(applyFilter,150); });
        // initial layout: ensure flex display on cards
        cards.forEach(function(c){ c.style.display='flex'; });
        applyFilter();
      })();
    </script>
    </div>
  </main>

  <!-- ========== FOOTER ========== -->
  <footer class="site-footer">
    <div class="footer-content">
      <div class="footer-section">
        <h4>FoxUnity</h4>
        <p>Gaming for Good - Every action makes a difference</p>
      </div>
      <div class="footer-section">
        <h4>Back to Top</h4>
        <a href="#" class="back-to-top-link" onclick="window.scrollTo({top: 0, behavior: 'smooth'}); return false;">
          <i class="fas fa-arrow-up"></i> Scroll to Top
        </a>
      </div>
      <div class="footer-section">
        <h4>Support</h4>
        <a href="../front/reclamation.html">Contact Support</a>
        <a href="#">FAQ</a>
        <a href="#">Privacy Policy</a>
      </div>
      <div class="footer-section">
        <h4>Follow Us</h4>
        <div class="social-links">
          <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
          <a href="#" aria-label="Discord"><i class="fab fa-discord"></i></a>
          <a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
        </div>
      </div>
    </div>
    <div class="footer-bottom">
      <p>&copy; 2025 FoxUnity. All rights reserved. Gaming for Good.</p>
    </div>
  </footer>
</body>
</html>
