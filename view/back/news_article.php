<?php
// news_article.php
// Shows a single news article with comments

require __DIR__ . '/db.php';

// Helper to resolve image paths and check if they exist
function getImagePath($imagePath) {
  if (empty($imagePath)) return '../images/nopic.png';
  // If path starts with uploads/, it's a relative path from this file
  if (strpos($imagePath, 'uploads/') === 0) {
    $fullPath = __DIR__ . '/' . $imagePath;
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

// --- Comments handling (file-backed, per-article) ---
$commentsDir = __DIR__ . '/uploads/comments';
@mkdir($commentsDir, 0755, true);
$commentsFile = $commentsDir . '/' . md5($slug) . '.json';
$comments = [];
if (file_exists($commentsFile)) {
  $comments = json_decode(file_get_contents($commentsFile), true) ?: [];
}

// Handle comment POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_submit'])) {
  $name = trim((string)($_POST['name'] ?? '')); 
  $text = trim((string)($_POST['comment'] ?? ''));
  $errors = [];
  if ($name === '') $errors[] = 'Name is required.';
  if ($text === '') $errors[] = 'Comment is required.';
  if (empty($errors)) {
    $entry = [
      'name' => $name,
      'text' => $text,
      'date' => date('Y-m-d H:i:s')
    ];
    array_unshift($comments, $entry);
    // save (atomic)
    file_put_contents($commentsFile . '.tmp', json_encode($comments, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    rename($commentsFile . '.tmp', $commentsFile);
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
    <div class="article-container">
      <a href="news.php" class="back-to-news">← Back to News</a>
      
      <div class="article-header">
        <h1 class="article-title"><?php echo htmlspecialchars($a['title']); ?></h1>
        <div class="article-meta">
          <span class="article-category"><?php echo htmlspecialchars(findCategoryName($a['idCategorie'] ?? 0, $categories) ?? 'Uncategorized'); ?></span>
          <span><?php echo htmlspecialchars($a['date'] ?? ''); ?></span>
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
        // Handle different content formats
        $content = $a['content'] ?? '';
        
        // If content contains HTML tags, display as-is (assuming it's from TinyMCE)
        if (strip_tags($content) !== $content) {
            echo $content;
        } else {
            // If plain text, convert newlines to <p> tags
            $paragraphs = explode("\n\n", $content);
            foreach ($paragraphs as $paragraph) {
                if (trim($paragraph)) {
                    echo '<p>' . htmlspecialchars(trim($paragraph)) . '</p>';
                }
            }
        }
        ?>
      </div>
      
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
