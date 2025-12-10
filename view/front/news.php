<?php
// news.php
// Loads all news using NewsPublicController (MVC)

require_once __DIR__ . '/../../controller/NewsPublicController.php';

// Image path helper is now in model/helpers.php
// Variables provided by NewsPublicController:
// $categories, $articles (non-hot), $hotNews
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

</head>
<body>
  <!-- Bulles animées rouges -->
  <div class="bubbles">
    <div class="bubble"></div><div class="bubble"></div><div class="bubble"></div><div class="bubble"></div><div class="bubble"></div>
  </div>

  <header class="site-header">
    <div class="logo-section">
      <img src="../images/Nine__1_-removebg-preview.png" alt="FoxUnity Logo" class="site-logo">
      <span class="site-name">FoxUnity</span>
    </div>
    <nav class="site-nav">
      <a href="http://localhost/projet_web/view/front/indexf.php">Home</a>
      <a href="../front/events.html">Events</a>
      <a href="../front/shop.html">Shop</a>
      <a href="../front/trading.html">Trading</a>
      <a href="news.php" class="active">News</a>
      <a href="../front/reclamation.html">Support</a>
      <a href="../front/about.html">About Us</a>
    </nav>
    <div class="header-right">
      <a href="../front/login.html" class="login-register-link"><i class="fas fa-user"></i> Login / Register</a>
      <a href="../front/profile.html" class="profile-icon"><i class="fas fa-user-circle"></i></a>
    </div>
  </header>

  <main class="main-section">
    <div class="news-container">
        
        <div class="news-header-area">
            <div>
                <h1 class="news-title">Gaming News <span id="saved-count" class="saved-count" style="display:none">0</span></h1>
                <div class="news-controls">
                  <input id="news-search" class="news-search" placeholder="Search news by title..." style="padding:10px; border-radius:6px; border:1px solid #444; background:#222; color:#fff; width:300px;">
                  <select id="news-perpage" class="news-perpage" style="padding:10px; border-radius:6px; border:1px solid #444; background:#222; color:#fff;">
                    <option value="6">6 per page</option>
                    <option value="9" selected>9 per page</option>
                    <option value="12">12 per page</option>
                  </select>
                </div>
            </div>

            <!-- Moved Subscribe Box -->
            <div class="subscribe-hero" id="newsletter">
               <h3><i class="fas fa-bell"></i> Get Updates</h3>
               <?php if (!empty($_GET['msg'])): ?>
                   <div style="background:#0f03; color:#eff; padding:6px; border-radius:4px; font-size:0.8rem;"><?php echo htmlspecialchars($_GET['msg']); ?></div>
               <?php endif; ?>
               <?php if (!empty($_GET['err'])): ?>
                   <div style="background:#f003; color:#fee; padding:6px; border-radius:4px; font-size:0.8rem;"><?php echo htmlspecialchars($_GET['err']); ?></div>
               <?php endif; ?>
               <form action="/projet_web/controller/NewsletterController.php" method="POST">
                   <input type="hidden" name="action" value="subscribe">
                   <input type="email" name="email" class="hero-input" placeholder="Your Email Address" required>
                   
                   <div style="position:relative">
                       <div class="hero-cat-btn" onclick="this.nextElementSibling.classList.toggle('show');">
                           Select Categories <i class="fas fa-chevron-down"></i>
                       </div>
                       <div class="cat-dropdown-content">
                           <?php 
                           if (!isset($allCategories)) {
                               $allCategories = $pdo->query("SELECT * FROM categorie ORDER BY nom")->fetchAll();
                           }
                           foreach ($allCategories as $c): ?>
                               <label><input type="checkbox" name="categories[]" value="<?php echo $c['idCategorie']; ?>"> <?php echo htmlspecialchars($c['nom']); ?></label>
                           <?php endforeach; ?>
                       </div>
                   </div>
                   <button type="submit" class="hero-submit">Subscribe</button>
               </form>
            </div>
        </div>
        
        <div class="saved-controls" id="saved-controls" style="display:none; margin-bottom:20px;">
            <button id="view-saved">View Saved</button>
            <button id="copy-saved">Copy Saved Links</button>
            <button id="clear-saved">Clear Saved</button>
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

        <!-- Main News Grid (Full Width) -->
        <div class="news-grid" id="news-grid">
          <?php foreach ($articles as $a): ?>
            <?php 
              $dispCat = !empty($a['idCategorie']) ? ($a['category'] ?? '') : ($a['category'] ?? '');
              $imgPath = getImagePath($a['image'] ?? '');
              $isNew = (($a['date'] ?? '') === date('Y-m-d')) ? 'new-article' : '';
              $plain = strip_tags($a['content'] ?? '');
              $words = str_word_count($plain);
              $rt = max(1, (int)ceil($words/200));
            ?>
            <div id="news-<?php echo htmlspecialchars($a['id']); ?>" class="news-card <?php echo $isNew; ?>">
              <div class="news-card-image">
                <img src="<?php echo htmlspecialchars($imgPath); ?>" alt="<?php echo htmlspecialchars($a['title'] ?? ''); ?>" onerror="this.src='../images/nopic.png'">
              </div>
              <div class="news-card-content">
                <div class="news-card-date"><?php echo htmlspecialchars($a['date'] ?? ''); ?> • <?php echo htmlspecialchars($dispCat); ?> <span class="reading-time-badge"><?php echo $rt; ?> min</span></div>
                <h2 class="news-card-title"><?php echo htmlspecialchars($a['title'] ?? ''); ?></h2>
                <p class="news-card-excerpt"><?php echo htmlspecialchars($a['excerpt'] ?? ''); ?></p>
                <div style="display:flex;gap:8px;align-items:center;justify-content:flex-start">
                  <a class="read-more" href="news_article.php?id=<?php echo urlencode($a['id']); ?>">Read More →</a>
                  <button class="read-later-btn" data-slug="<?php echo htmlspecialchars($a['id']); ?>">Save</button>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

    <div class="pagination" id="news-pagination" style="justify-content:center"></div>
    
    <!-- Saved articles -->
    <section id="saved-articles" class="saved-articles" style="display:none;margin-top:28px">
      <h3>Saved Articles</h3>
      <div class="news-grid" id="saved-grid"></div>
    </section>
    
    <script>
      // Client-side search + pagination for news cards
      (function(){
        var grid = document.getElementById('news-grid');
        if(!grid) return;
        var cards = Array.from(grid.querySelectorAll('.news-card'));
        var perSelect = document.getElementById('news-perpage');
        var search = document.getElementById('news-search');
        var pager = document.getElementById('news-pagination');
        var per = parseInt(perSelect.value,10) || 9; // Default 9 for 3-grid match
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
      // Read later functionality + render saved articles
        (function(){
        function getSaved(){ try{ return JSON.parse(localStorage.getItem('read_later')||'[]'); }catch(e){return []} }
        function setSaved(arr){ localStorage.setItem('read_later', JSON.stringify(arr)); }

        // Toggle save button state and persist
        function toggleSaveButton(btn){
          var slug = btn.dataset.slug;
          var s = getSaved();
          var idx = s.indexOf(slug);
          if(idx === -1){ s.push(slug); btn.classList.add('saved'); btn.textContent = 'Saved'; }
          else { s.splice(idx,1); btn.classList.remove('saved'); btn.textContent = 'Save'; }
          setSaved(s);
          renderSaved();
        }

        // Render saved articles into #saved-grid and update UI
        function renderSaved(){
          var saved = getSaved();
          var container = document.getElementById('saved-grid');
          var section = document.getElementById('saved-articles');
          var countEl = document.getElementById('saved-count');
          var controls = document.getElementById('saved-controls');
          if(!container || !section) return;
          container.innerHTML = '';
          if(!saved || saved.length === 0){ section.style.display = 'none'; if(countEl) countEl.style.display='none'; if(controls) controls.style.display='none'; return; }
          section.style.display = 'block';
          if(countEl){ countEl.style.display='inline-block'; countEl.textContent = saved.length + ' saved'; }
          if(controls) controls.style.display='inline-flex';
          saved.forEach(function(slug){
            var src = document.getElementById('news-' + slug);
            if(!src) return; // article not on this page
            var clone = src.cloneNode(true);
            // replace Save button in clone with a remove button
            var btn = clone.querySelector('.read-later-btn');
            if(btn){
              btn.textContent = 'Remove';
              btn.classList.add('saved');
              btn.addEventListener('click', function(e){ e.preventDefault();
                // remove from saved list
                var orig = findOriginalButton(slug);
                if(orig) toggleSaveButton(orig);
              });
            }
            container.appendChild(clone);
          });
        }

        function findOriginalButton(slug){ return document.querySelector('.read-later-btn[data-slug="'+slug+'"]'); }

        // Initialize buttons on the main grid
        document.querySelectorAll('.read-later-btn').forEach(function(btn){
          var slug = btn.dataset.slug;
          var saved = getSaved();
          if(saved.indexOf(slug) !== -1){ btn.classList.add('saved'); btn.textContent = 'Saved'; }
          btn.addEventListener('click', function(e){ e.preventDefault(); toggleSaveButton(btn); });
        });

        // Controls: clear, copy, view toggle
        var clearBtn = document.getElementById('clear-saved');
        var copyBtn = document.getElementById('copy-saved');
        var viewBtn = document.getElementById('view-saved');
        function clearSaved(){ setSaved([]); // reset UI
          document.querySelectorAll('.read-later-btn.saved').forEach(function(b){ b.classList.remove('saved'); b.textContent='Save'; });
          renderSaved();
        }
        function copySavedLinks(){ var saved = getSaved(); if(!saved || saved.length===0){ alert('No saved articles'); return; } var list = saved.map(function(s){ return window.location.origin + window.location.pathname.replace(/\/[^\/]*$/, '') + '/news_article.php?id='+encodeURIComponent(s); }); navigator.clipboard && navigator.clipboard.writeText(list.join('\n')).then(function(){ alert('Saved links copied'); }, function(){ prompt('Saved links', list.join('\n')); }); }
        function toggleViewSaved(){
          // when viewing saved, hide non-saved cards
          var saved = getSaved(); var grid = document.getElementById('news-grid'); if(!grid) return;
          if(viewBtn.classList.contains('active')){ // turn off
            viewBtn.classList.remove('active'); viewBtn.textContent='View Saved'; document.querySelectorAll('#news-grid .news-card').forEach(function(c){ c.style.display='flex'; }); renderPage(1);
          } else {
            viewBtn.classList.add('active'); viewBtn.textContent='Show All'; document.querySelectorAll('#news-grid .news-card').forEach(function(c){ var id = c.id.replace('news-',''); if(saved.indexOf(id) === -1) c.style.display='none'; else c.style.display='flex'; });
          }
        }

        if(clearBtn) clearBtn.addEventListener('click', function(e){ e.preventDefault(); if(confirm('Clear all saved articles?')) clearSaved(); });
        if(copyBtn) copyBtn.addEventListener('click', function(e){ e.preventDefault(); copySavedLinks(); });
        if(viewBtn) viewBtn.addEventListener('click', function(e){ e.preventDefault(); toggleViewSaved(); });

        // Initial render of saved section
        renderSaved();
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
<style>
    .news-container{max-width:95%;margin:120px auto 60px;padding:0 40px; position: relative;}
    .news-header-area {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 50px;
        position: relative;
    }
    .news-title{
      font-family:Orbitron,system-ui;
      font-size:3.6rem;
      color:var(--accent);
      margin:0;
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
    
    /* Premium Subscribe Box - Top Right */
    .subscribe-hero {
        background: linear-gradient(135deg, rgba(30,30,30,0.8), rgba(20,20,20,0.9));
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255,120,0,0.2);
        border-radius: 12px;
        padding: 20px;
        max-width: 400px;
        width: 100%;
        box-shadow: 0 8px 32px rgba(0,0,0,0.3);
        z-index: 10;
        transition: transform 0.3s ease;
    }
    .subscribe-hero:hover {
        transform: translateY(-2px);
        border-color: rgba(255,120,0,0.4);
    }
    .subscribe-hero h3 {
        margin: 0 0 8px 0;
        color: #f90;
        font-family: Orbitron, sans-serif;
        font-size: 1.1rem;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .subscribe-hero h3 i { font-size: 0.9em; }
    .subscribe-hero form { display: flex; flex-direction: column; gap: 10px; }
    .hero-input {
        background: rgba(0,0,0,0.4);
        border: 1px solid #444;
        color: #fff;
        padding: 8px 12px;
        border-radius: 6px;
        font-size: 0.9rem;
        outline: none;
        transition: border-color 0.3s;
    }
    .hero-input:focus { border-color: #f90; }
    .hero-cat-btn {
        background: #2a2a2a; border: 1px solid #444; color: #ccc;
        padding: 8px; border-radius: 6px; cursor: pointer; text-align: left;
        font-size: 0.85rem; display: flex; justify-content: space-between;
    }
    .cat-dropdown-content {
        display: none; background: #222; border: 1px solid #444;
        max-height: 150px; overflow-y: auto; padding: 5px;
        margin-top: 5px; border-radius: 6px;
    }
    .cat-dropdown-content.show { display: block; }
    .cat-dropdown-content label { display: block; padding: 4px; color: #ddd; font-size: 0.85rem; cursor: pointer; }
    .cat-dropdown-content label:hover { background: #333; }
    
    .hero-submit {
        background: linear-gradient(90deg, #ff7a00, #ff5500);
        color: white; border: none; padding: 10px; border-radius: 6px;
        font-weight: bold; cursor: pointer; text-transform: uppercase;
        font-size: 0.85rem; letter-spacing: 1px; transition: 0.3s;
    }
    .hero-submit:hover {
        box-shadow: 0 0 15px rgba(255,122,0,0.4);
    }

    /* Hot news strip */
    .hot-news{display:flex;flex-direction:column;gap:16px;margin-bottom:40px}
    .hot-news h3{margin:0;color:#ffb86b;font-family:Orbitron,system-ui;font-size:1.2rem}
    .hot-list{display:flex;gap:16px;flex-wrap:wrap}
    .hot-card{flex:1 1 320px;background:linear-gradient(135deg, rgba(40,40,40,0.9), rgba(20,20,20,0.9));border-radius:12px;display:flex;gap:12px;align-items:center;padding:10px;border:1px solid rgba(255,120,0,0.08);text-decoration:none}
    .hot-card img{width:140px;height:80px;object-fit:cover;border-radius:8px}
    .hot-card .hot-meta{color:#ddd}
    .hot-card .hot-meta h4{margin:0;font-size:1rem;color:var(--accent);font-weight:700}
    .hot-card .hot-meta p{margin:4px 0 0;font-size:0.9rem;color:#bbb}
    
    /* 3-Column Grid */
    .news-grid{
      display:grid;
      grid-template-columns: repeat(3, 1fr);
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
    /* Rest of card styles */
    .news-card-image{width:100%;height:220px;background:linear-gradient(135deg, rgba(255,120,0,0.15), rgba(255,120,0,0.05));position:relative;overflow:hidden;display:flex;align-items:center;justify-content:center;color:var(--accent);font-size:3rem;}
    .news-card-image img{width:100%;height:100%;object-fit:cover;transition:0.4s ease;}
    .news-card:hover .news-card-image img{transform:scale(1.05);}
    .news-card-content{padding:28px;flex:1;display:flex;flex-direction:column;}
    .news-card-date{font-size:0.82rem;color:#888;margin-bottom:12px;text-transform:uppercase;font-weight:600;}
    .news-card-title{font-family:Orbitron,system-ui;font-size:1.3rem;color:var(--accent);margin:0 0 16px;line-height:1.4;transition:0.3s ease;font-weight:700;}
    .news-card:hover .news-card-title{text-shadow:0 0 15px rgba(255,120,0,0.4);}
    .news-card-excerpt{color:#bbb;font-size:0.96rem;line-height:1.6;margin:0 0 20px;flex:1;font-weight:400;}
    .read-more{display:inline-block;color:var(--accent);text-decoration:none;font-weight:700;font-size:0.88rem;letter-spacing:0.5px;position:relative;width:fit-content;text-transform:uppercase;}
    .read-more::after{content:'';position:absolute;bottom:-2px;left:0;width:0;height:2px;background:linear-gradient(90deg,#ff7a00,transparent);transition:width 0.35s ease;}
    .news-card:hover .read-more::after{width:100%}

    @media (max-width:1100px){
      .news-grid{grid-template-columns:repeat(2,1fr);}
      .news-header-area{flex-direction:column; gap:30px;}
    }
    @media (max-width:820px){
      .news-container{margin:100px auto 40px;padding:0 24px}
      .news-title{font-size:2.6rem;margin-bottom:0}
    }
    @media (max-width:700px){
        .news-grid{grid-template-columns:1fr;}
    }
  </style>
</html>
