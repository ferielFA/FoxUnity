<?php
// news_article.php
// Shows a single news article with comments
// Uses NewsArticleController (MVC)

require_once __DIR__ . '/../../controller/NewsArticleController.php';
require_once __DIR__ . '/../../controller/UserController.php';

$isLoggedIn = UserController::isLoggedIn();
$currentUser = null;

if ($isLoggedIn) {
    $currentUser = UserController::getCurrentUser();
}

$userImage = null;
if ($currentUser && $currentUser->getImage()) {
    $userImage = '../../view/' . $currentUser->getImage();
}

// NewsArticleController ($__newsArticleController) already handles:
// - 404 check
// - Article loading
// - Comment fetching
// - Post submission (Comment)
// - Exposes: $a, $categories, $slug, $comments, $errors

// Admin flag: articles are public-facing; admin editing should be done in admin pages.
$isAdmin = false;
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

</head>
    <style>
        /* User Dropdown Menu Styles - LARGE PHOTO LIKE PROFILE.PHP */
        .user-dropdown {
            position: relative;
            display: inline-block;
        }

        .username-display {
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            padding: 5px 10px;
            border-radius: 8px;
        }

        .username-display:hover {
            background: rgba(255, 122, 0, 0.1);
        }

        /* LARGE PROFILE IMAGE - 45px x 45px */
        .username-display img {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #ff7a00;
        }

        .username-display span {
            color: #ff7a00;
            font-weight: 600;
            font-size: 16px;
        }

        .username-display i.fa-chevron-down {
            font-size: 12px;
            color: #ff7a00;
            transition: transform 0.3s ease;
        }

        .username-display i.fa-user-circle {
            font-size: 24px;
            color: #ff7a00;
        }

        .user-dropdown.active .username-display i.fa-chevron-down {
            transform: rotate(180deg);
        }

        .dropdown-menu {
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

        .user-dropdown.active .dropdown-menu {
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
            display: inline-block; /* Fix for missing icons sometimes */
            font-size: 16px;
            color: #ff7a00;
            width: 20px;
            text-align: center;
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

        /* Cart icon styling */
        .cart-icon {
            color: #ff7a00 !important;
            position: relative;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-left: 15px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .cart-icon:hover {
            color: #ff9933 !important;
            transform: translateY(-2px);
        }
        
        .cart-icon i {
            color: #ff7a00;
            font-size: 18px;
        }
        
        .cart-count {
            background: linear-gradient(135deg, #ff7a00, #ff4f00);
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 11px;
            font-weight: 700;
            position: absolute;
            top: -8px;
            right: -8px;
            min-width: 18px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(255, 122, 0, 0.4);
        }

        /* Social Share Buttons */
        .share-buttons {
            display: flex;
            gap: 10px;
            margin: 30px 0;
            flex-wrap: wrap;
            justify-content: center;
        }

        .share-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border: none;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: white;
        }

        .share-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.4);
        }

        .share-btn i {
            font-size: 16px;
        }

        .share-btn.twitter {
            background: linear-gradient(135deg, #1DA1F2, #0d8bd9);
        }

        .share-btn.facebook {
            background: linear-gradient(135deg, #1877F2, #0e5fc7);
        }

        .share-btn.instagram {
            background: linear-gradient(135deg, #E1306C, #C13584, #833AB4);
        }

        .share-btn.copy {
            background: linear-gradient(135deg, #6c757d, #495057);
        }

        .share-btn.copy.copied {
            background: linear-gradient(135deg, #28a745, #1e7e34);
        }

        /* Comment Avatar Styles */
        .comment-item {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }

        .comment-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #ff7a00;
            flex-shrink: 0;
        }

        .comment-avatar-placeholder {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, rgba(255, 122, 0, 0.2), rgba(255, 122, 0, 0.1));
            border: 2px solid rgba(255, 122, 0, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .comment-avatar-placeholder i {
            font-size: 24px;
            color: #ff7a00;
        }

        .comment-body {
            flex: 1;
        }

        /* Comment Action Buttons */
        .comment-actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }

        .comment-action-btn {
            background: transparent;
            border: 1px solid rgba(255, 122, 0, 0.3);
            color: #ff7a00;
            padding: 6px 14px;
            border-radius: 15px;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .comment-action-btn:hover {
            background: rgba(255, 122, 0, 0.1);
            border-color: #ff7a00;
            transform: translateY(-2px);
        }

        .comment-action-btn i {
            font-size: 12px;
        }

        /* Reply Form */
        .reply-form {
            margin-top: 15px;
            padding: 15px;
            background: rgba(255, 122, 0, 0.05);
            border-left: 3px solid #ff7a00;
            border-radius: 8px;
            display: none;
        }

        .reply-form.active {
            display: block;
        }

        .reply-form textarea {
            width: 100%;
            padding: 10px;
            background: #0b0b0b;
            border: 1px solid #333;
            border-radius: 6px;
            color: #fff;
            font-family: inherit;
            resize: vertical;
            min-height: 80px;
        }

        .reply-form-actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .btn-submit-reply {
            background: linear-gradient(135deg, #ff7a00, #ff4f00);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-submit-reply:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 122, 0, 0.4);
        }

        .btn-cancel-reply {
            background: transparent;
            border: 1px solid #666;
            color: #999;
            padding: 8px 16px;
            border-radius: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-cancel-reply:hover {
            border-color: #ff7a00;
            color: #ff7a00;
        }

        /* Edit Mode */
        .comment-text.editing {
            display: none;
        }

        .edit-form {
            display: none;
            margin-top: 10px;
        }

        .edit-form.active {
            display: block;
        }

        /* Reply Display */
        .replies-container {
            margin-top: 15px;
            padding-left: 20px;
            border-left: 2px solid rgba(255, 122, 0, 0.2);
        }

        .reply-item {
            background: rgba(255, 122, 0, 0.03);
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 10px;
            border-left: 2px solid #ff7a00;
        }

        .reply-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
        }

        .reply-author {
            font-weight: 600;
            color: #ff7a00;
            font-size: 14px;
        }

        .reply-date {
            color: #999;
            font-size: 12px;
        }

        .reply-text {
            color: #ddd;
            font-size: 14px;
            line-height: 1.5;
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
      <a href="http://localhost/projet_web/view/front/index.php">Home</a>
      <a href="events.php">Events</a>
      <a href="shop.html">Shop</a>
      <a href="trading.php">Trading</a>
      <a href="news.php" class="active">News</a>
      <a href="reclamation.html">Support</a>
      <a href="about.php">About Us</a>
    </nav>
    
    <div class="header-right">
            <div class="user-dropdown" id="userDropdown">
                <div class="username-display">
                    <?php if ($isLoggedIn && $currentUser): ?>
                        <?php if ($userImage): ?>
                            <img src="<?php echo htmlspecialchars($userImage); ?>" alt="Profile">
                        <?php else: ?>
                            <i class="fas fa-user-circle"></i>
                        <?php endif; ?>
                        <span><?php echo htmlspecialchars($currentUser->getUsername()); ?></span>
                    <?php else: ?>
                        <i class="fas fa-user-circle"></i>
                        <span>Guest</span>
                    <?php endif; ?>
                    <i class="fas fa-chevron-down"></i>
                </div>
                
                <div class="dropdown-menu">
                    <?php if ($isLoggedIn && $currentUser): ?>
                    <a href="profile.php" class="dropdown-item">
                        <i class="fas fa-user"></i>
                        <span>My Profile</span>
                    </a>
                    
                    <a href="tradehis.php" class="dropdown-item">
                        <i class="fas fa-history"></i>
                        <span>Trade History</span>
                    </a>
                    
                    <a href="events.php?view=history" class="dropdown-item">
                        <i class="fas fa-ticket-alt"></i>
                        <span>Event History</span>
                    </a>
                    
                    <?php 
                    $userRole = strtolower($currentUser->getRole());
                    if ($userRole === 'admin' || $userRole === 'superadmin'): 
                    ?>
                    <a href="../back/dashboard.php" class="dropdown-item">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                    <?php endif; ?>
                    
                    <div class="dropdown-divider"></div>
                    
                    <a href="logout.php" class="dropdown-item logout">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                    <?php else: ?>
                    <a href="login.php" class="dropdown-item">
                        <i class="fas fa-sign-in-alt"></i>
                        <span>Login/Register</span>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <a href="panier.php" class="cart-icon">
                <i class="fas fa-shopping-cart"></i> Cart
                <span class="cart-count">0</span>
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
      
      <!-- Comments -->
      <div id="comments" class="comments-section" style="max-width:900px;margin:0 auto 60px;padding:0 40px">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
          <h3 style="color:#fff;margin:0">Comments (<?php echo count($comments); ?>)</h3>
          <?php if(isset($a['verdict']) && $a['verdict'] !== 'Neutral'): ?>
            <span style="background:<?php echo $a['verdict'] === 'Mostly Positive' ? 'rgba(40,167,69,0.2)' : ($a['verdict'] === 'Mostly Negative' ? 'rgba(220,53,69,0.2)' : 'rgba(255,193,7,0.2)'); ?>; color:<?php echo $a['verdict'] === 'Mostly Positive' ? '#28a745' : ($a['verdict'] === 'Mostly Negative' ? '#dc3545' : '#ffc107'); ?>; padding:4px 10px; border-radius:20px; font-weight:600; font-size:0.9rem;">
              Community Verdict: <?php echo htmlspecialchars($a['verdict']); ?>
            </span>
          <?php endif; ?>
        </div>

        <form class="comment-form" method="post" action="news_article.php?id=<?php echo urlencode($slug); ?>#comments" style="margin-bottom:20px;padding-bottom:20px;border-bottom:1px solid #333">
          <div class="star-rating-container" style="margin-top:0">
             <span style="color:#bbb;margin-right:10px">Rate this article:</span>
             <div class="star-rating">
               <input type="radio" id="star5" name="rating" value="5" /><label for="star5" title="5 stars"></label>
               <input type="radio" id="star4" name="rating" value="4" /><label for="star4" title="4 stars"></label>
               <input type="radio" id="star3" name="rating" value="3" /><label for="star3" title="3 stars"></label>
               <input type="radio" id="star2" name="rating" value="2" /><label for="star2" title="2 stars"></label>
               <input type="radio" id="star1" name="rating" value="1" /><label for="star1" title="1 star"></label>
             </div>
          </div>
          <input type="text" name="name" placeholder="Your name" value="<?php echo $isLoggedIn && $currentUser ? htmlspecialchars($currentUser->getUsername()) : ''; ?>" <?php echo $isLoggedIn && $currentUser ? 'readonly' : ''; ?> style="width:100%;padding:8px;margin:6px 0;border-radius:4px;border:1px solid #333;background:#0b0b0b;color:#fff">
          <input type="hidden" name="email" value="<?php echo $isLoggedIn && $currentUser ? htmlspecialchars($currentUser->getEmail()) : 'guest@foxunity.com'; ?>">
          <textarea name="comment" rows="4" placeholder="Your comment" style="width:100%;padding:8px;margin:6px 0;border-radius:4px;border:1px solid #333;background:#0b0b0b;color:#fff"></textarea>
          <button type="submit" name="comment_submit" style="background:#ff9900;color:#000;padding:8px 12px;border-radius:6px;border:0;cursor:pointer;font-weight:600">Post Comment</button>
        </form>

        <?php if (!empty($errors)): ?>
          <div style="color:#ffd6d6;background:#2b0b0b;padding:10px;border-radius:6px;margin-bottom:12px">
            <?php foreach ($errors as $err) echo '<div>' . htmlspecialchars($err) . '</div>'; ?>
          </div>
        <?php endif; ?>

        <?php if (empty($comments)): ?>
          <p style="color:#bbb;margin:8px 0">Be the first to comment on this article.</p>
        <?php else: ?>
          <?php foreach ($comments as $c): ?>
            <div class="comment-item">
              <?php
              // Get commenter's profile picture
              $commenterImage = null;
              if (!empty($c['email'])) {
                try {
                  require_once __DIR__ . '/../../model/User.php';
                  $commenterUser = User::findByEmail($c['email']);
                  if ($commenterUser && $commenterUser->getImage()) {
                    $commenterImage = '../../view/' . $commenterUser->getImage();
                  }
                } catch (Exception $e) {}
              }
              ?>
              <?php if ($commenterImage): ?>
                <img src="<?php echo htmlspecialchars($commenterImage); ?>" alt="<?php echo htmlspecialchars($c['name']); ?>" class="comment-avatar">
              <?php else: ?>
                <div class="comment-avatar-placeholder">
                  <i class="fas fa-user"></i>
                </div>
              <?php endif; ?>
              <div class="comment-body">
            <div class="comment" style="background:#111;padding:12px;border-radius:8px;margin-bottom:10px">
              <div class="comment-header" style="display:flex;justify-content:space-between;align-items:center">
                <div class="comment-meta" style="font-weight:700;color:#fff">
                <?php echo htmlspecialchars($c['name']); ?>
                <?php 
                $sentiment = strtolower($c['sentiment'] ?? 'neutral');
                if($sentiment === 'positive'): 
                ?>
                  <span title="Positive Vibes" style="margin-left:8px; background:rgba(40,167,69,0.2); color:#28a745; padding:2px 6px; border-radius:4px; font-size:0.75rem;">
                    <i class="fas fa-heart"></i> Positive Vibes
                  </span>
                <?php elseif($sentiment === 'negative'): ?>
                  <span title="Negative Sentiment" style="margin-left:8px; background:rgba(220,53,69,0.2); color:#dc3545; padding:2px 6px; border-radius:4px; font-size:0.75rem;">
                    <i class="fas fa-frown"></i> Negative
                  </span>
                <?php endif; ?>
                <span class="comment-date" style="font-weight:400;color:#999;margin-left:8px;font-size:0.9rem"><?php echo htmlspecialchars($c['date']); ?></span>
              </div>
              <?php if (!empty($c['rating'])): ?>
                <div style="color:#ffc107;font-size:0.9rem">
                  <?php for($i=1; $i<=5; $i++) echo $i <= $c['rating'] ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>'; ?>
                </div>
              <?php endif; ?>
              </div>
              <div class="comment-text" style="margin-top:8px;color:#ddd" data-comment-id="<?php echo $c['id'] ?? ''; ?>"><?php echo nl2br(htmlspecialchars($c['text'])); ?></div>
              
              <!-- Comment Actions -->
              <div class="comment-actions">
                <button class="comment-action-btn" onclick="toggleReplyForm(<?php echo $c['id'] ?? 0; ?>)">
                  <i class="fas fa-reply"></i> Reply
                </button>
                <?php if ($isLoggedIn && $currentUser && strtolower($c['email'] ?? '') === strtolower($currentUser->getEmail())): ?>
                <button class="comment-action-btn" onclick="toggleEditForm(<?php echo $c['id'] ?? 0; ?>)">
                  <i class="fas fa-edit"></i> Edit
                </button>
                <?php endif; ?>
              </div>

              <!-- Reply Form -->
              <div class="reply-form" id="reply-form-<?php echo $c['id'] ?? 0; ?>">
                <textarea placeholder="Write your reply..." id="reply-text-<?php echo $c['id'] ?? 0; ?>"></textarea>
                <div class="reply-form-actions">
                  <button class="btn-submit-reply" onclick="submitReply(<?php echo $c['id'] ?? 0; ?>)">Post Reply</button>
                  <button class="btn-cancel-reply" onclick="cancelReply(<?php echo $c['id'] ?? 0; ?>)">Cancel</button>
                </div>
              </div>

              <!-- Edit Form -->
              <div class="edit-form" id="edit-form-<?php echo $c['id'] ?? 0; ?>">
                <textarea id="edit-text-<?php echo $c['id'] ?? 0; ?>"><?php echo htmlspecialchars($c['text']); ?></textarea>
                <div class="reply-form-actions">
                  <button class="btn-submit-reply" onclick="submitEdit(<?php echo $c['id'] ?? 0; ?>)">Save Changes</button>
                  <button class="btn-cancel-reply" onclick="cancelEdit(<?php echo $c['id'] ?? 0; ?>)">Cancel</button>
                </div>
              </div>

              <!-- Replies Container -->
              <div class="replies-container" id="replies-<?php echo $c['id'] ?? 0; ?>"></div>
              </div>
              </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>


      </div>

  <script>
    // Social Sharing Functions
    function shareOnTwitter() {
      const url = window.location.href;
      const title = document.querySelector('h1').textContent;
      const text = encodeURIComponent(title + ' - FoxUnity Gaming News');
      window.open('https://twitter.com/intent/tweet?text=' + text + '&url=' + encodeURIComponent(url), '_blank', 'width=600,height=400');
    }

    function shareOnFacebook() {
      const url = window.location.href;
      window.open('https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(url), '_blank', 'width=600,height=400');
    }

    function openInstagram() {
      window.open('https://www.instagram.com/', '_blank');
    }

    function copyArticleLink(button) {
      const url = window.location.href;
      navigator.clipboard.writeText(url).then(function() {
        const textSpan = button.querySelector('.copy-text');
        const originalText = textSpan.textContent;
        textSpan.textContent = 'Copied!';
        button.classList.add('copied');
        setTimeout(function() {
          textSpan.textContent = originalText;
          button.classList.remove('copied');
        }, 2000);
      }).catch(function(err) {
        alert('Failed to copy link');
      });
    }

    // Comment Reply and Edit Functions
    function toggleReplyForm(commentId) {
      const replyForm = document.getElementById('reply-form-' + commentId);
      const allReplyForms = document.querySelectorAll('.reply-form');
      
      // Close all other reply forms
      allReplyForms.forEach(form => {
        if (form.id !== 'reply-form-' + commentId) {
          form.classList.remove('active');
        }
      });
      
      // Toggle current form
      replyForm.classList.toggle('active');
    }

    function cancelReply(commentId) {
      const replyForm = document.getElementById('reply-form-' + commentId);
      const replyText = document.getElementById('reply-text-' + commentId);
      replyForm.classList.remove('active');
      replyText.value = '';
    }

    function submitReply(commentId) {
      const replyText = document.getElementById('reply-text-' + commentId).value.trim();
      
      if (!replyText) {
        return;
      }
      
      // Get current user name (from the comment form)
      const userName = document.querySelector('input[name="name"]').value || 'Guest';
      
      // Create reply element
      const repliesContainer = document.getElementById('replies-' + commentId);
      const replyItem = document.createElement('div');
      replyItem.className = 'reply-item';
      
      const now = new Date();
      const dateStr = now.getFullYear() + '-' + 
                      String(now.getMonth() + 1).padStart(2, '0') + '-' + 
                      String(now.getDate()).padStart(2, '0') + ' ' +
                      String(now.getHours()).padStart(2, '0') + ':' + 
                      String(now.getMinutes()).padStart(2, '0');
      
      replyItem.innerHTML = `
        <div class="reply-header">
          <span class="reply-author">${escapeHtml(userName)}</span>
          <span class="reply-date">${dateStr}</span>
        </div>
        <div class="reply-text">${escapeHtml(replyText).replace(/\n/g, '<br>')}</div>
      `;
      
      repliesContainer.appendChild(replyItem);
      cancelReply(commentId);
    }

    function escapeHtml(text) {
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    }

    function toggleEditForm(commentId) {
      const editForm = document.getElementById('edit-form-' + commentId);
      const commentText = document.querySelector('[data-comment-id="' + commentId + '"]');
      const allEditForms = document.querySelectorAll('.edit-form');
      
      // Close all other edit forms
      allEditForms.forEach(form => {
        if (form.id !== 'edit-form-' + commentId) {
          form.classList.remove('active');
        }
      });
      
      // Toggle current form
      if (editForm.classList.contains('active')) {
        editForm.classList.remove('active');
        commentText.classList.remove('editing');
      } else {
        editForm.classList.add('active');
        commentText.classList.add('editing');
      }
    }

    function cancelEdit(commentId) {
      const editForm = document.getElementById('edit-form-' + commentId);
      const commentText = document.querySelector('[data-comment-id="' + commentId + '"]');
      editForm.classList.remove('active');
      commentText.classList.remove('editing');
    }

    function submitEdit(commentId) {
      const editText = document.getElementById('edit-text-' + commentId).value.trim();
      
      if (!editText) {
        alert('Comment cannot be empty');
        return;
      }
      
      // Submit edit via form
      const form = document.createElement('form');
      form.method = 'POST';
      form.action = window.location.href;
      
      const actionInput = document.createElement('input');
      actionInput.type = 'hidden';
      actionInput.name = 'edit_comment';
      actionInput.value = '1';
      form.appendChild(actionInput);
      
      const idInput = document.createElement('input');
      idInput.type = 'hidden';
      idInput.name = 'comment_id';
      idInput.value = commentId;
      form.appendChild(idInput);
      
      const textInput = document.createElement('input');
      textInput.type = 'hidden';
      textInput.name = 'comment_text';
      textInput.value = editText;
      form.appendChild(textInput);
      
      document.body.appendChild(form);
      form.submit();
    }
  </script>

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
    .summary-btn:hover { box-shadow: 0 8px 20px rgba(255,153,0,0.2); transform: translateY(-1px); }
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
    
    /* Star Rating CSS */
    .star-rating-container {
      display: flex;
      align-items: center;
      margin: 10px 0;
      background: rgba(255,255,255,0.05);
      padding: 10px 15px;
      border-radius: 8px;
    }
    .star-rating {
      display: flex;
      flex-direction: row-reverse;
      gap: 5px;
    }
    .star-rating input {
      display: none;
    }
    .star-rating label {
      cursor: pointer;
      font-size: 1.4rem;
      color: #444;
      transition: all 0.2s ease;
    }
    .star-rating label:before {
      content: '\f005';
      font-family: 'Font Awesome 5 Free';
      font-weight: 900;
    }
    .star-rating input:checked ~ label,
    .star-rating label:hover,
    .star-rating label:hover ~ label {
      color: #ffc107;
      text-shadow: 0 0 15px rgba(255,193,7,0.6);
      transform: scale(1.1);
    }
  </style>
</body>
<script>
    // Dropdown Menu Toggle
    document.addEventListener('DOMContentLoaded', function() {
        const userDropdown = document.getElementById('userDropdown');
        
        if (userDropdown) {
            const usernameDisplay = userDropdown.querySelector('.username-display');
            
            // Toggle dropdown on click
            usernameDisplay.addEventListener('click', function(e) {
                e.stopPropagation();
                userDropdown.classList.toggle('active');
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!userDropdown.contains(e.target)) {
                    userDropdown.classList.remove('active');
                }
            });
            
            // Close dropdown when pressing Escape
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    userDropdown.classList.remove('active');
                }
            });
        }
    });
</script>
</html>
