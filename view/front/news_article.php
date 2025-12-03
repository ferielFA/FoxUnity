<?php
// news_article.php
// Shows a single news article with comments

require __DIR__ . '/../back/db.php';
require_once __DIR__ . '/../../model/comment_model.php';

// Helper to resolve image paths and check if they exist
function getImagePath($imagePath) {
  if (empty($imagePath)) return '../images/nopic.png';
  // If path starts with uploads/, prefer front uploads then back uploads
  if (strpos($imagePath, 'uploads/') === 0) {
    $frontFull = __DIR__ . '/' . $imagePath;
    if (file_exists($frontFull)) return $imagePath;
    $backFull = __DIR__ . '/../back/' . $imagePath;
    if (file_exists($backFull)) return '../back/' . $imagePath;
  }
  // If path starts with ../, it's already relative to this file
  if (strpos($imagePath, '../') === 0) {
    $fullPath = __DIR__ . '/' . $imagePath;
    if (file_exists($fullPath)) return $imagePath;
  }
  // If path looks like images/ (relative to view), check view/images
  if (strpos($imagePath, 'images/') === 0) {
    $fullPath = __DIR__ . '/../' . $imagePath;
    if (file_exists($fullPath)) return '../' . $imagePath;
  }
  return '../images/nopic.png';
}

function findCategoryName($id, $categories){
  foreach($categories as $c) if (($c['idCategorie'] ?? 0) == $id) return $c['nom'];
  return null;
}

// Load categories for display names
$catStmt = $pdo->query("SELECT idCategorie, nom, description FROM categorie ORDER BY nom");
$categories = $catStmt->fetchAll();

// Get article by slug
$slug = $_GET['id'] ?? '';
if (empty($slug)) {
  header('HTTP/1.0 404 Not Found');
  echo '<h1>Article not found</h1>';
  exit;
}

$stmt = $pdo->prepare("SELECT
  idArticle,
  slug       AS id,
  titre      AS title,
  datePublication AS date,
  datePublication,
  image,
  idCategorie,
  excerpt,
  contenu    AS content,
  hot
FROM article WHERE slug = ? LIMIT 1");
$stmt->execute([$slug]);
$a = $stmt->fetch();

if (!$a) {
  header('HTTP/1.0 404 Not Found');
  echo '<h1>Article not found</h1>';
  exit;
}

// Admin flag: articles are public-facing; admin editing should be done in admin pages.
// Keep a defined variable to avoid undefined warnings. Set to true only when explicit admin mode is required.
$isAdmin = false;

// Comments handling: use embedded comments in article.comments when available,
// otherwise fall back to file-backed comments in view/front/uploads/comments
$mutesFile = __DIR__ . '/uploads/comments/mutes.json';
@mkdir(dirname($mutesFile), 0755, true);
if (!file_exists($mutesFile)) file_put_contents($mutesFile, json_encode(new stdClass()));
$comments = getEmbeddedCommentsBySlug($slug);

// Handle public comment POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_submit'])) {
  $name = trim((string)($_POST['name'] ?? ''));
  $text = trim((string)($_POST['comment'] ?? ''));
  $errors = [];

  if ($name === '') $errors[] = 'Name is required.';
  if ($text === '') $errors[] = 'Comment is required.';

  // check mutes (global by lowercase name)
  $mutes = json_decode(file_get_contents($mutesFile), true) ?: [];
  $lower = strtolower($name);
  if ($lower && isset($mutes[$lower]) && intval($mutes[$lower]) > time()) {
    $errors[] = 'You are muted until ' . date('Y-m-d H:i:s', intval($mutes[$lower])) . '. You cannot post comments.';
  }

    if (empty($errors)) {
      // Save via model (embedded or fallback)
      addEmbeddedCommentBySlug($slug, $name, '', $text);
      // redirect to avoid repost
      header('Location: news_article.php?id=' . urlencode($slug) . '#comments');
      exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($a['title']); ?> - FoxUnity News</title>
  <link rel="stylesheet" href="../front/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    .article-container {
      max-width: 900px;
      margin: 120px auto 60px;
      padding: 0 40px;
    }
    .article-header {
      margin-bottom: 40px;
    }
    .article-title {
      font-family: Orbitron, system-ui;
      font-size: 3rem;
      color: var(--accent);
      margin: 0 0 20px;
      text-shadow: 0 0 30px rgba(255,120,0,0.3);
      line-height: 1.2;
    }
    .article-meta {
      display: flex;
      align-items: center;
      gap: 20px;
      color: #888;
      font-size: 0.9rem;
      text-transform: uppercase;
      letter-spacing: 1px;
    }
    .article-category {
      background: linear-gradient(135deg, rgba(255,122,0,0.2), rgba(255,122,0,0.1));
      color: #ff9900;
      padding: 4px 12px;
      border-radius: 20px;
      font-weight: 600;
    }
    .article-image {
      width: 100%;
      height: 400px;
      background: linear-gradient(135deg, rgba(255,120,0,0.15), rgba(255,120,0,0.05));
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 4rem;
      color: var(--accent);
      opacity: 0.8;
      border-radius: 16px;
      overflow: hidden;
      margin-bottom: 40px;
      position: relative;
    }
    .article-image img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }
    .article-image::after {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: radial-gradient(circle at center, rgba(255,120,0,0.1), transparent);
      pointer-events: none;
    }
    .article-content {
      font-size: 1.1rem;
      line-height: 1.8;
      color: #ddd;
      margin-bottom: 40px;
    }
    .article-content h2 {
      font-family: Orbitron, system-ui;
      color: var(--accent);
      margin: 30px 0 15px;
      font-size: 1.8rem;
    }
    .article-content h3 {
      font-family: Orbitron, system-ui;
      color: var(--accent);
      margin: 25px 0 12px;
      font-size: 1.4rem;
    }
    .article-content p {
      margin-bottom: 20px;
    }
    .article-content ul, .article-content ol {
      margin: 20px 0;
      padding-left: 30px;
    }
    .article-content li {
      margin-bottom: 10px;
    }
    .article-content blockquote {
      border-left: 3px solid var(--accent);
      padding-left: 20px;
      margin: 20px 0;
      font-style: italic;
      color: #bbb;
    }
    .article-content code {
      background: #222;
      padding: 2px 6px;
      border-radius: 4px;
      font-family: 'Courier New', monospace;
    }
    .article-content pre {
      background: #1a1a1a;
      padding: 20px;
      border-radius: 8px;
      overflow-x: auto;
      margin: 20px 0;
    }
    .back-to-news {
      display: inline-block;
      color: var(--accent);
      text-decoration: none;
      font-weight: 700;
      margin-bottom: 30px;
      transition: all 0.3s ease;
    }
    .back-to-news:hover {
      color: #ffaa00;
      text-shadow: 0 0 10px rgba(255,120,0,0.3);
    }
    @media (max-width: 768px) {
      .article-container {
        margin: 100px auto 40px;
        padding: 0 20px;
      }
      .article-title {
        font-size: 2.2rem;
      }
      .article-image {
        height: 250px;
      }
      .article-content {
        font-size: 1rem;
      }
    }
  </style>
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
      <a href="http://localhost/projet_web/view/front/indexf.php">Home</a>
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
    <div class="article-container">
      <a href="news.php" class="back-to-news">← Back to News</a>
      
      <div class="article-header">
        <h1 class="article-title"><?php echo htmlspecialchars($a['title']); ?></h1>
        <?php
        // Calculate reading time (words / 200 wpm)
        $plainText = strip_tags($a['content'] ?? '');
        $wordCount = str_word_count($plainText);
        $readingMinutes = max(1, (int)ceil($wordCount / 200));

        // Prepare Table of Contents from headings if content contains HTML headings
        $tocHtml = '';
        $contentHtml = $a['content'] ?? '';
        if (strip_tags($contentHtml) !== $contentHtml) {
          libxml_use_internal_errors(true);
          $doc = new DOMDocument();
          // ensure proper encoding
          $doc->loadHTML('<?xml encoding="utf-8" ?>' . $contentHtml);
          $xpath = new DOMXPath($doc);
          $headings = $xpath->query('//h2 | //h3');
          if ($headings && $headings->length) {
            $tocParts = [];
            foreach ($headings as $h) {
              $text = trim($h->textContent);
              $id = preg_replace('/[^a-z0-9_-]+/i', '-', strtolower($text));
              // ensure unique id
              $base = $id; $i = 1; while ($doc->getElementById($id)) { $id = $base . '-' . $i; $i++; }
              $h->setAttribute('id', $id);
              $tocParts[] = ['tag' => $h->nodeName, 'id' => $id, 'text' => $text];
            }
            if (!empty($tocParts)) {
              $tocHtml = '<nav class="article-toc"><strong>On this page</strong><ul>';
              foreach ($tocParts as $t) {
                $indent = $t['tag'] === 'h3' ? ' class="toc-sub"' : '';
                $tocHtml .= '<li' . $indent . '><a href="#' . htmlspecialchars($t['id']) . '">' . htmlspecialchars($t['text']) . '</a></li>';
              }
              $tocHtml .= '</ul></nav>';
            }
            // Export back modified HTML
            $body = $doc->getElementsByTagName('body')->item(0);
            $newContent = '';
            foreach ($body->childNodes as $cn) { $newContent .= $doc->saveHTML($cn); }
            $contentHtml = $newContent;
          }
          libxml_clear_errors();
        }
        ?>
        <div class="article-meta">
          <span class="article-category"><?php echo htmlspecialchars(findCategoryName($a['idCategorie'] ?? 0, $categories) ?? 'Uncategorized'); ?></span>
          <span><?php echo htmlspecialchars($a['date'] ?? ''); ?></span>
          <span class="reading-time"><?php echo $readingMinutes; ?> min read</span>
          <?php if ($a['hot']): ?>
            <span style="color: #ff7a00;">🔥 Hot News</span>
          <?php endif; ?>
        </div>
      </div>

      <div class="article-image">
        <img src="<?php echo htmlspecialchars(getImagePath($a['image'] ?? '')); ?>" alt="<?php echo htmlspecialchars($a['title'] ?? ''); ?>" onerror="this.src='../images/nopic.png'">
      </div>

        <div class="article-content">
        <?php 
        // Use processed HTML content if available (with heading ids), otherwise fallback to original handling
        if (!empty($contentHtml)) {
          echo $contentHtml;
        } else {
          $content = $a['content'] ?? '';
          if (strip_tags($content) !== $content) {
            echo $content;
          } else {
            $paragraphs = explode("\n\n", $content);
            foreach ($paragraphs as $paragraph) {
              if (trim($paragraph)) {
                echo '<p>' . htmlspecialchars(trim($paragraph)) . '</p>';
              }
            }
          }
        }
        ?>
      </div>
        <?php if (!empty($tocHtml)): ?>
        <aside class="toc-container">
          <?php echo $tocHtml; ?>
        </aside>
        <?php endif; ?>
      
      <!-- Comments -->
      <div id="comments" class="comments-section" style="max-width:900px;margin:0 auto 60px;padding:0 40px">
        <h3 style="color:#fff;margin-bottom:12px">Comments (<?php echo count($comments); ?>)</h3>

        <?php if (!empty($errors)): ?>
          <div style="color:#ffd6d6;background:#2b0b0b;padding:10px;border-radius:6px;margin-bottom:12px">
            <?php foreach ($errors as $err) echo '<div>' . htmlspecialchars($err) . '</div>'; ?>
          </div>
        <?php endif; ?>

        <?php if (empty($comments)): ?>
          <p style="color:#bbb;margin:8px 0">Be the first to comment on this article.</p>
        <?php else: ?>
          <?php foreach ($comments as $c): ?>
            <div class="comment" style="background:#111;padding:12px;border-radius:8px;margin-bottom:10px">
              <div class="comment-meta" style="font-weight:700;color:#fff">
                <?php echo htmlspecialchars($c['name']); ?>
                <span class="comment-date" style="font-weight:400;color:#999;margin-left:8px;font-size:0.9rem"><?php echo htmlspecialchars($c['date']); ?></span>
              </div>
              <div class="comment-text" style="margin-top:8px;color:#ddd"><?php echo nl2br(htmlspecialchars($c['text'])); ?></div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>

        <form class="comment-form" method="post" action="news_article.php?id=<?php echo urlencode($slug); ?>#comments" style="margin-top:12px">
          <input type="text" name="name" placeholder="Your name" style="width:100%;padding:8px;margin:6px 0;border-radius:4px;border:1px solid #333;background:#0b0b0b;color:#fff">
          <textarea name="comment" rows="4" placeholder="Your comment" style="width:100%;padding:8px;margin:6px 0;border-radius:4px;border:1px solid #333;background:#0b0b0b;color:#fff"></textarea>
          <button type="submit" name="comment_submit" style="background:#ff7a00;color:#000;padding:8px 12px;border-radius:6px;border:0;cursor:pointer">Post Comment</button>
        </form>
      </div>
    </div>
  </main>

  <?php if ($isAdmin): ?>
  <script>
    // AJAX handlers for admin comment edit/delete/clear
    (function(){
      function postAjax(url, formData){
        formData.append('ajax', '1');
        return fetch(url, { method: 'POST', body: formData, credentials: 'same-origin' }).then(function(r){ return r.json(); });
      }

      // Update forms
      document.querySelectorAll('.comment-update-form').forEach(function(f){
        f.addEventListener('submit', function(e){
          e.preventDefault();
          var idx = f.querySelector('input[name="index"]').value;
          var fd = new FormData(f);
          postAjax(f.action, fd).then(function(json){
            if(json && json.ok){
              var container = document.querySelector('.comment-admin[data-index="'+json.index+'"]');
              if(container){
                // update date and displayed text
                var dateEl = container.querySelector('.comment-date');
                if(dateEl && json.comment && json.comment.date) dateEl.textContent = json.comment.date;
                // maybe flash success
                adminToast('Comment saved');
              }
            } else {
              adminToast('Save failed');
            }
          }).catch(function(){ adminToast('Save failed'); });
        });
      });

      // Delete forms
      document.querySelectorAll('.comment-delete-form').forEach(function(f){
        f.addEventListener('submit', function(e){
          e.preventDefault();
          if(!confirm('Delete this comment?')) return;
          var idx = f.querySelector('input[name="index"]').value;
          var fd = new FormData(f);
          postAjax(f.action, fd).then(function(json){
            if(json && json.ok){
              var container = document.querySelector('.comment-admin[data-index="'+json.index+'"]');
              if(container) container.parentNode.removeChild(container);
              adminToast('Comment deleted');
            } else {
              adminToast('Delete failed');
            }
          }).catch(function(){ adminToast('Delete failed'); });
        });
      });

      // Clear all comments button (form)
      var clearForm = document.querySelector('form[action*="clear_comments"]');
      if(clearForm){
        clearForm.addEventListener('submit', function(e){
          e.preventDefault();
          if(!confirm('Clear all comments for this article?')) return;
          var fd = new FormData(clearForm);
          postAjax(clearForm.action, fd).then(function(json){
            if(json && json.ok){
              // remove all comment-admin elements
              document.querySelectorAll('.comment-admin').forEach(function(el){ el.parentNode.removeChild(el); });
              adminToast('All comments cleared');
            } else {
              adminToast('Clear failed');
            }
          }).catch(function(){ adminToast('Clear failed'); });
        });
      }

      // Cancel edit buttons - simply reload admin fragment
      document.querySelectorAll('.cancel-edit').forEach(function(btn){
        btn.addEventListener('click', function(){ window.location.href = window.location.pathname + '?id=' + encodeURIComponent('<?php echo addslashes($slug); ?>') + '&admin=1#comments'; });
      });
    })();
  </script>
  <?php endif; ?>

  <script>
    // Improved share buttons: Twitter, Facebook, Instagram (copy fallback)
    (function(){
      function copyToClipboard(text){
        if (navigator.clipboard && navigator.clipboard.writeText) {
          return navigator.clipboard.writeText(text).then(function(){ alert('Link copied to clipboard'); });
        }
        // fallback
        var ta = document.createElement('textarea');
        ta.value = text; document.body.appendChild(ta); ta.select();
        try { document.execCommand('copy'); alert('Link copied to clipboard'); } catch(e) { prompt('Copy this link', text); }
        ta.parentNode.removeChild(ta);
        return Promise.resolve();
      }

      var header = document.querySelector('.article-header');
      if (!header) return;
      var share = document.createElement('div');
      share.className = 'article-share';
      share.style.marginTop = '8px';
      var url = window.location.href;
      var title = document.title || document.querySelector('.article-title')?.textContent || '';

      function openPopup(u){ window.open(u, '_blank', 'noopener,noreferrer,width=700,height=500'); }

      var tBtn = document.createElement('button');
      tBtn.className = 'share-btn twitter'; tBtn.innerHTML = '<i class="fab fa-twitter" aria-hidden="true"></i><span>Tweet</span>';
      tBtn.setAttribute('aria-label','Share on Twitter');
      tBtn.addEventListener('click', function(){
        var u = 'https://twitter.com/intent/tweet?text=' + encodeURIComponent(title) + '&url=' + encodeURIComponent(url);
        openPopup(u);
      });

      var fBtn = document.createElement('button');
      fBtn.className = 'share-btn facebook'; fBtn.innerHTML = '<i class="fab fa-facebook-f" aria-hidden="true"></i><span>Facebook</span>';
      fBtn.setAttribute('aria-label','Share on Facebook');
      fBtn.addEventListener('click', function(){
        var u = 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(url);
        openPopup(u);
      });

      var igBtn = document.createElement('button');
      igBtn.className = 'share-btn instagram'; igBtn.innerHTML = '<i class="fab fa-instagram" aria-hidden="true"></i><span>Instagram</span>';
      igBtn.title = 'Instagram: copy link then open app';
      igBtn.setAttribute('aria-label','Share to Instagram (copy link)');
      igBtn.addEventListener('click', function(){
        copyToClipboard(url).then(function(){ window.open('https://www.instagram.com/', '_blank'); });
      });

      var copyBtn = document.createElement('button');
      copyBtn.className = 'share-btn copy'; copyBtn.innerHTML = '<i class="fas fa-link" aria-hidden="true"></i><span>Copy link</span>';
      copyBtn.setAttribute('aria-label','Copy link');
      copyBtn.addEventListener('click', function(){ copyToClipboard(url); });

      share.appendChild(tBtn); share.appendChild(fBtn); share.appendChild(igBtn); share.appendChild(copyBtn);
      header.appendChild(share);
    })();
  </script>

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
