<?php
// Front office news page - Integrated with FoxUnity
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

      <div class="hot-news">
        <h3>Hot News</h3>
        <div class="hot-list">
          <a class="hot-card" href="news_article.php?id=cs2">
            <img src="../images/cs2.png" alt="Dragon Lore">
            <div class="hot-meta">
              <h4>Dragon Lore Skyrockets</h4>
              <p>Collectors driving bids, volatility spikes — Nov 11, 2025</p>
            </div>
          </a>

          <a class="hot-card" href="news_article.php?id=valorant">
            <img src="../images/valo.jpg" alt="Valorant Final">
            <div class="hot-meta">
              <h4>Fnatic Takes Champions</h4>
              <p>Thrilling final in Seoul — Nov 8, 2025</p>
            </div>
          </a>
        </div>
      </div>

    <div class="news-grid">
      <!-- News Card 1: CS2 Market -->
  <div id="news-cs2" class="news-card">
        <div class="news-card-image">
          <img src="../images/cs2.png" alt="CS2 Skin Market">
        </div>
        <div class="news-card-content">
          <div class="news-card-date">November 11, 2025</div>
          <h2 class="news-card-title">CS2 Skin Market Surge: Dragon Lore Reaches $750K</h2>
          <p class="news-card-excerpt">The AWP Dragon Lore (Factory New) hits record highs on third-party markets. M4A1-S Knight factory listed at $450+. AK-47 Neon Rider soars to $280 as demand peaks.</p>
          <a class="read-more" href="news_article.php?id=cs2">Read More →</a>
        </div>
      </div>

      <!-- News Card 2: LCS Championship -->
  <div id="news-lcs" class="news-card">
        <div class="news-card-image">
          <img src="../images/lcs.jpg" alt="LCS Championship">
        </div>
        <div class="news-card-content">
          <div class="news-card-date">November 9, 2025</div>
          <h2 class="news-card-title">LCS 2026 Spring Split: New Dynasty Emerges</h2>
          <p class="news-card-excerpt">FlyQuest secures first seed after upset victory over 100 Thieves. With a new roster featuring Caps and Zven, NA's premier league promises competitive excellence this season.</p>
          <a class="read-more" href="news_article.php?id=lcs">Read More →</a>
        </div>
      </div>

      <!-- News Card 3: Valorant Masters -->
  <div id="news-valorant" class="news-card">
        <div class="news-card-image">
          <img src="../images/valo.jpg" alt="Valorant Champions">
        </div>
        <div class="news-card-content">
          <div class="news-card-date">November 8, 2025</div>
          <h2 class="news-card-title">Valorant Champions Seoul: FNC Claims Glory</h2>
          <p class="news-card-excerpt">Fnatic defeats PRX 3-2 in an epic Grand Final, securing their second world title and $500K prize pool. Boaster named tournament MVP with dominant smokes gameplay.</p>
          <a class="read-more" href="news_article.php?id=valorant">Read More →</a>
        </div>
      </div>

      <!-- News Card 4: Major Winners -->
  <div id="news-major" class="news-card">
        <div class="news-card-image">
          <img src="../images/major.png" alt="CS2 Major">
        </div>
        <div class="news-card-content">
          <div class="news-card-date">November 6, 2025</div>
          <h2 class="news-card-title">CS2 Major Stockholm: Vitality Dominates Tournament</h2>
          <p class="news-card-excerpt">Vitality defeats Navi 3-0 in the finals, securing their third Major title. ZywOo MVP with 1.42 rating across the tournament. $1M prize distributed among top 8 teams.</p>
          <a class="read-more" href="news_article.php?id=major">Read More →</a>
        </div>
      </div>

      <!-- News Card 5: Upcoming Events -->
  <div id="news-intel" class="news-card">
        <div class="news-card-image">
          <img src="../images/intel.jpg" alt="Intel Extreme Masters">
        </div>
        <div class="news-card-content">
          <div class="news-card-date">November 5, 2025</div>
          <h2 class="news-card-title">Intel Extreme Masters XV Returns in December</h2>
          <p class="news-card-excerpt">IEM Katowice Qualifiers kick off Nov 25. Top 8 CS2 teams battle for $3M prize pool across Valorant, Dota 2, and StarCraft II. Register your team now before slots fill up.</p>
          <a class="read-more" href="news_article.php?id=intel">Read More →</a>
        </div>
      </div>

      <!-- News Card 6: Market Watch -->
  <div id="news-dota" class="news-card">
        <div class="news-card-image">
          <img src="../images/dota.jpg" alt="Dota 2 International">
        </div>
        <div class="news-card-content">
          <div class="news-card-date">November 3, 2025</div>
          <h2 class="news-card-title">Dota 2 International 2026: Qualifiers Begin</h2>
          <p class="news-card-excerpt">Regional qualifiers for The International 2026 launch globally with $50M prize pool confirmed. Team Liquid, OG, and Secret poised to dominate Western qualifiers. Live on Twitch 24/7.</p>
          <a class="read-more" href="news_article.php?id=dota">Read More →</a>
        </div>
      </div>
    </div>
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
