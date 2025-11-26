<?php
require_once __DIR__ . '/../../controller/EvenementController.php';
require_once __DIR__ . '/../../controller/CommentController.php';

$eventController = new EvenementController();
$commentController = new CommentController();

$message = '';

// Get event ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: eventsb.php");
    exit;
}

$eventId = (int)$_GET['id'];
$event = $eventController->lireParId($eventId);

if (!$event) {
    header("Location: eventsb.php");
    exit;
}

// Handle delete comment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_comment') {
    if ($commentController->deleteComment((int)$_POST['id_comment'])) {
        $message = '<div class="alert success"><i class="fas fa-check-circle"></i> Comment deleted successfully!</div>';
    } else {
        $message = '<div class="alert error"><i class="fas fa-exclamation-circle"></i> Error deleting comment.</div>';
    }
}

// Handle unreport comment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'unreport_comment') {
    if ($commentController->unreportComment((int)$_POST['id_comment'])) {
        $message = '<div class="alert success"><i class="fas fa-check-circle"></i> Report dismissed!</div>';
    } else {
        $message = '<div class="alert error"><i class="fas fa-exclamation-circle"></i> Error dismissing report.</div>';
    }
}

// Get comments and rating stats
$comments = $commentController->getEventComments($eventId);
$ratingStats = $commentController->getEventRatingStats($eventId);
$reportedComments = array_filter($comments, fn($c) => $c->getIsReported());
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Event Comments & Ratings - Dashboard</title>
  <link rel="stylesheet" href="style.css">
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Poppins:wght@300;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  
  <style>
    .comments-management {
      padding: 24px;
    }

    .alert {
      padding: 15px 20px;
      border-radius: 12px;
      margin-bottom: 20px;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 12px;
      animation: slideDown 0.5s ease;
    }
    .alert.success { background: linear-gradient(135deg, #10b981, #059669); color: white; }
    .alert.error { background: linear-gradient(135deg, #ef4444, #dc2626); color: white; }

    @keyframes slideDown {
      from { opacity: 0; transform: translateY(-20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .back-button {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      color: #f5c242;
      text-decoration: none;
      font-weight: 600;
      margin-bottom: 24px;
      padding: 10px 16px;
      border: 1px solid rgba(245, 194, 66, 0.3);
      border-radius: 8px;
      transition: all 0.3s ease;
    }

    .back-button:hover {
      background: rgba(245, 194, 66, 0.1);
      transform: translateX(-5px);
    }

    .event-header-admin {
      background: linear-gradient(135deg, #16161a, #1b1b20);
      border: 1px solid rgba(245, 194, 66, 0.2);
      border-radius: 12px;
      padding: 24px;
      margin-bottom: 32px;
    }

    .event-header-admin h2 {
      font-family: 'Orbitron', sans-serif;
      color: #f5c242;
      font-size: 1.8rem;
      margin: 0 0 12px 0;
    }

    .event-meta-admin {
      display: flex;
      gap: 24px;
      color: #969696;
      font-size: 0.9rem;
    }

    .event-meta-admin span {
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
      margin-bottom: 32px;
    }

    .stat-card-admin {
      background: linear-gradient(135deg, rgba(22, 22, 26, 0.9), rgba(27, 27, 32, 0.9));
      border: 1px solid rgba(245, 194, 66, 0.2);
      border-radius: 12px;
      padding: 20px;
      text-align: center;
      transition: all 0.3s ease;
    }

    .stat-card-admin:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 20px rgba(245, 194, 66, 0.2);
      border-color: rgba(245, 194, 66, 0.4);
    }

    .stat-card-admin .stat-icon-admin {
      font-size: 2.5rem;
      margin-bottom: 12px;
    }

    .stat-card-admin.rating { color: #f5c242; }
    .stat-card-admin.total { color: #2ed573; }
    .stat-card-admin.reported { color: #ff6b6b; }

    .stat-card-admin .stat-number {
      font-family: 'Orbitron', sans-serif;
      font-size: 2.5rem;
      font-weight: 700;
      margin-bottom: 8px;
    }

    .stat-card-admin .stat-label {
      color: #969696;
      font-size: 0.9rem;
      font-weight: 600;
    }

    .rating-distribution {
      background: rgba(245, 194, 66, 0.05);
      border: 1px solid rgba(245, 194, 66, 0.2);
      border-radius: 12px;
      padding: 24px;
      margin-bottom: 32px;
    }

    .rating-distribution h3 {
      font-family: 'Orbitron', sans-serif;
      color: #f5c242;
      margin: 0 0 20px 0;
    }

    .rating-bar-item {
      display: flex;
      align-items: center;
      gap: 15px;
      margin-bottom: 12px;
    }

    .rating-bar-label {
      min-width: 60px;
      color: #969696;
      font-size: 0.9rem;
    }

    .rating-bar-container {
      flex: 1;
      height: 10px;
      background: rgba(255,255,255,0.1);
      border-radius: 10px;
      overflow: hidden;
    }

    .rating-bar-fill {
      height: 100%;
      background: linear-gradient(90deg, #f5c242, #f39c12);
      border-radius: 10px;
      transition: width 0.5s ease;
    }

    .rating-bar-count {
      min-width: 50px;
      color: #969696;
      font-size: 0.9rem;
      text-align: right;
    }

    .comments-section-admin {
      margin-bottom: 32px;
    }

    .section-title-admin {
      font-family: 'Orbitron', sans-serif;
      color: #f5c242;
      font-size: 1.5rem;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .comment-card {
      background: rgba(255, 255, 255, 0.03);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 12px;
      padding: 24px;
      margin-bottom: 16px;
      transition: all 0.3s ease;
    }

    .comment-card:hover {
      background: rgba(255, 255, 255, 0.05);
      border-color: rgba(245, 194, 66, 0.3);
    }

    .comment-card.reported {
      border-left: 4px solid #ff6b6b;
      background: rgba(255, 107, 107, 0.05);
    }

    .comment-header-admin {
      display: flex;
      justify-content: space-between;
      align-items: start;
      margin-bottom: 16px;
    }

    .comment-author-admin {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .comment-avatar-admin {
      width: 48px;
      height: 48px;
      border-radius: 50%;
      background: linear-gradient(135deg, #f5c242, #f39c12);
      display: flex;
      align-items: center;
      justify-content: center;
      color: #000;
      font-weight: 700;
      font-size: 1.2rem;
    }

    .comment-author-info-admin h4 {
      color: #fff;
      font-size: 1rem;
      margin: 0 0 4px 0;
    }

    .comment-meta-admin {
      display: flex;
      gap: 16px;
      color: #969696;
      font-size: 0.85rem;
    }

    .comment-rating-admin {
      color: #f5c242;
      font-size: 1rem;
    }

    .comment-badges {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
    }

    .badge {
      padding: 4px 12px;
      border-radius: 6px;
      font-size: 0.75rem;
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }

    .badge.reported {
      background: rgba(255, 107, 107, 0.2);
      color: #ff6b6b;
      border: 1px solid #ff6b6b;
    }

    .badge.positive {
      background: rgba(46, 213, 115, 0.2);
      color: #2ed573;
      border: 1px solid #2ed573;
    }

    .badge.negative {
      background: rgba(255, 107, 107, 0.2);
      color: #ff6b6b;
      border: 1px solid #ff6b6b;
    }

    .comment-content-admin {
      color: #cfd3d8;
      line-height: 1.7;
      margin-bottom: 16px;
      padding: 16px;
      background: rgba(255,255,255,0.02);
      border-radius: 8px;
    }

    .comment-stats {
      display: flex;
      gap: 24px;
      margin-bottom: 16px;
      color: #969696;
      font-size: 0.9rem;
    }

    .comment-stat-item {
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .report-reason {
      background: rgba(255, 107, 107, 0.1);
      border-left: 3px solid #ff6b6b;
      padding: 12px 16px;
      border-radius: 6px;
      margin-bottom: 16px;
    }

    .report-reason strong {
      color: #ff6b6b;
      display: block;
      margin-bottom: 4px;
    }

    .report-reason p {
      color: #cfd3d8;
      margin: 0;
    }

    .comment-actions-admin {
      display: flex;
      gap: 10px;
    }

    .btn-admin {
      background: transparent;
      border: 1px solid rgba(255, 255, 255, 0.2);
      color: #fff;
      padding: 8px 16px;
      border-radius: 6px;
      cursor: pointer;
      font-size: 0.85rem;
      transition: all 0.3s ease;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }

    .btn-admin:hover {
      border-color: #f5c242;
      background: rgba(245, 194, 66, 0.1);
      transform: translateY(-2px);
    }

    .btn-admin.delete {
      border-color: rgba(255, 107, 107, 0.4);
      color: #ff6b6b;
    }

    .btn-admin.delete:hover {
      border-color: #ff6b6b;
      background: rgba(255, 107, 107, 0.1);
    }

    .btn-admin.approve {
      border-color: rgba(46, 213, 115, 0.4);
      color: #2ed573;
    }

    .btn-admin.approve:hover {
      border-color: #2ed573;
      background: rgba(46, 213, 115, 0.1);
    }

    .empty-state {
      text-align: center;
      padding: 60px 20px;
      color: #969696;
    }

    .empty-state i {
      font-size: 64px;
      margin-bottom: 16px;
      opacity: 0.3;
    }

    .filter-tabs {
      display: flex;
      gap: 12px;
      margin-bottom: 24px;
      border-bottom: 2px solid rgba(255,255,255,0.1);
      padding-bottom: 12px;
    }

    .filter-tab {
      background: transparent;
      border: none;
      color: #969696;
      padding: 8px 16px;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 600;
      transition: all 0.3s ease;
    }

    .filter-tab.active {
      background: rgba(245, 194, 66, 0.1);
      color: #f5c242;
    }

    .filter-tab:hover {
      background: rgba(255,255,255,0.05);
      color: #fff;
    }
  </style>
</head>

<body class="dashboard-body">
  <div class="stars"></div>
  <div class="shooting-star"></div>
  <div class="shooting-star"></div>
  <div class="shooting-star"></div>

  <!-- ===== SIDEBAR ===== -->
  <div class="sidebar">
    <img src="../images/Nine__1_-removebg-preview.png" alt="Nine Tailed Fox Logo" class="dashboard-logo">
    <h2>Dashboard</h2>
    <a href="dashboard.php">Overview</a>
    <a href="#">Users</a>
    <a href="#">Shop</a>
    <a href="#">Trade History</a>
    <a href="eventsb.php" class="active">Events</a>
    <a href="#">News</a>
    <a href="#">Support</a>
    <a href="../front/index.php">← Return Homepage</a>
  </div>

  <!-- ===== MAIN ===== -->
  <div class="main">
    <div class="topbar">
      <h1>Event Comments & Ratings</h1>
      <div class="user">
        <img src="../images/fery.jpg" alt="User Avatar">
        <span>FoxLeader</span>
      </div>
    </div>

    <div class="content">
      <div class="comments-management">
        <?php if ($message): echo $message; endif; ?>

        <a href="eventsb.php" class="back-button">
          <i class="fas fa-arrow-left"></i> Back to Events
        </a>

        <!-- Event Header -->
        <div class="event-header-admin">
          <h2><?= htmlspecialchars($event->getTitre()) ?></h2>
          <div class="event-meta-admin">
            <span><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($event->getLieu()) ?></span>
            <span><i class="fas fa-calendar"></i> <?= $event->getDateDebut()->format('M d, Y - H:i') ?></span>
          </div>
        </div>

        <!-- Statistics Grid -->
        <div class="stats-grid">
          <div class="stat-card-admin rating">
            <div class="stat-icon-admin"><i class="fas fa-star"></i></div>
            <div class="stat-number"><?= number_format($ratingStats['average'], 1) ?></div>
            <div class="stat-label">Average Rating</div>
          </div>

          <div class="stat-card-admin total">
            <div class="stat-icon-admin"><i class="fas fa-comments"></i></div>
            <div class="stat-number"><?= $ratingStats['total'] ?></div>
            <div class="stat-label">Total Comments</div>
          </div>

          <div class="stat-card-admin reported">
            <div class="stat-icon-admin"><i class="fas fa-flag"></i></div>
            <div class="stat-number"><?= count($reportedComments) ?></div>
            <div class="stat-label">Reported Comments</div>
          </div>
        </div>

        <!-- Rating Distribution -->
        <?php if ($ratingStats['total'] > 0): ?>
        <div class="rating-distribution">
          <h3><i class="fas fa-chart-bar"></i> Rating Distribution</h3>
          <?php foreach ([5, 4, 3, 2, 1] as $stars): ?>
            <?php 
            $count = $ratingStats['distribution'][$stars];
            $percentage = $ratingStats['total'] > 0 ? ($count / $ratingStats['total']) * 100 : 0;
            ?>
            <div class="rating-bar-item">
              <div class="rating-bar-label"><?= $stars ?> <i class="fas fa-star"></i></div>
              <div class="rating-bar-container">
                <div class="rating-bar-fill" style="width: <?= $percentage ?>%"></div>
              </div>
              <div class="rating-bar-count"><?= $count ?> (<?= round($percentage) ?>%)</div>
            </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Filter Tabs -->
        <div class="filter-tabs">
          <button class="filter-tab active" onclick="filterComments('all')">
            All Comments (<?= count($comments) ?>)
          </button>
          <button class="filter-tab" onclick="filterComments('reported')">
            Reported (<?= count($reportedComments) ?>)
          </button>
          <button class="filter-tab" onclick="filterComments('5stars')">
            5 Stars (<?= $ratingStats['distribution'][5] ?? 0 ?>)
          </button>
        </div>

        <!-- Comments List -->
        <div class="comments-section-admin">
          <h3 class="section-title-admin">
            <i class="fas fa-list"></i> All Comments
          </h3>

          <?php if (empty($comments)): ?>
            <div class="empty-state">
              <i class="fas fa-comment-slash"></i>
              <p>No comments yet for this event.</p>
            </div>
          <?php else: ?>
            <?php foreach ($comments as $comment): ?>
              <div class="comment-card <?= $comment->getIsReported() ? 'reported' : '' ?>" 
                   data-rating="<?= $comment->getRating() ?>" 
                   data-reported="<?= $comment->getIsReported() ? 'true' : 'false' ?>">
                
                <div class="comment-header-admin">
                  <div class="comment-author-admin">
                    <div class="comment-avatar-admin">
                      <?= $comment->getUserInitials() ?>
                    </div>
                    <div class="comment-author-info-admin">
                      <h4><?= htmlspecialchars($comment->getUserName()) ?></h4>
                      <div class="comment-meta-admin">
                        <span class="comment-rating-admin">
                          <?= str_repeat('★', $comment->getRating()) ?><?= str_repeat('☆', 5 - $comment->getRating()) ?>
                        </span>
                        <span><i class="far fa-clock"></i> <?= $comment->getTimeAgo() ?></span>
                        <span><i class="far fa-envelope"></i> <?= htmlspecialchars($comment->getUserEmail()) ?></span>
                      </div>
                    </div>
                  </div>

                  <div class="comment-badges">
                    <?php if ($comment->getIsReported()): ?>
                      <span class="badge reported">
                        <i class="fas fa-flag"></i> Reported
                      </span>
                    <?php endif; ?>
                    
                    <?php if ($comment->getNetScore() > 0): ?>
                      <span class="badge positive">
                        <i class="fas fa-thumbs-up"></i> Popular
                      </span>
                    <?php elseif ($comment->getNetScore() < -2): ?>
                      <span class="badge negative">
                        <i class="fas fa-thumbs-down"></i> Negative
                      </span>
                    <?php endif; ?>
                  </div>
                </div>

                <?php if ($comment->getIsReported()): ?>
                  <div class="report-reason">
                    <strong><i class="fas fa-exclamation-triangle"></i> Report Reason:</strong>
                    <p><?= htmlspecialchars($comment->getReportReason() ?? 'No reason provided') ?></p>
                  </div>
                <?php endif; ?>

                <div class="comment-content-admin">
                  <?= nl2br(htmlspecialchars($comment->getContent())) ?>
                </div>

                <div class="comment-stats">
                  <div class="comment-stat-item">
                    <i class="fas fa-thumbs-up"></i> <?= $comment->getLikes() ?> Likes
                  </div>
                  <div class="comment-stat-item">
                    <i class="fas fa-thumbs-down"></i> <?= $comment->getDislikes() ?> Dislikes
                  </div>
                  <div class="comment-stat-item">
                    <i class="fas fa-balance-scale"></i> Net Score: <?= $comment->getNetScore() ?>
                  </div>
                </div>

                <div class="comment-actions-admin">
                  <?php if ($comment->getIsReported()): ?>
                    <form method="POST" style="display:inline;">
                      <input type="hidden" name="action" value="unreport_comment">
                      <input type="hidden" name="id_comment" value="<?= $comment->getIdComment() ?>">
                      <button type="submit" class="btn-admin approve">
                        <i class="fas fa-check"></i> Dismiss Report
                      </button>
                    </form>
                  <?php endif; ?>
                  
                  <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this comment?');">
                    <input type="hidden" name="action" value="delete_comment">
                    <input type="hidden" name="id_comment" value="<?= $comment->getIdComment() ?>">
                    <button type="submit" class="btn-admin delete">
                      <i class="fas fa-trash"></i> Delete
                    </button>
                  </form>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <footer class="site-footer">
      © 2025 <span>Nine Tailed Fox</span>. All Rights Reserved.
    </footer>
  </div>

  <!-- ===== PAGE TRANSITION OVERLAY ===== -->
  <div class="transition-screen"></div>

  <script>
    // Page transition
    window.addEventListener("load", () => {
      document.querySelector(".transition-screen").classList.add("hidden");
    });

    document.querySelectorAll("a").forEach(link => {
      link.addEventListener("click", e => {
        const href = link.getAttribute("href");
        if (href && !href.startsWith("#") && href !== "") {
          e.preventDefault();
          const transition = document.querySelector(".transition-screen");
          transition.classList.remove("hidden");
          setTimeout(() => {
            window.location.href = href;
          }, 700);
        }
      });
    });

    // Filter comments
    function filterComments(filter) {
      const cards = document.querySelectorAll('.comment-card');
      const tabs = document.querySelectorAll('.filter-tab');
      
      // Update active tab
      tabs.forEach(tab => tab.classList.remove('active'));
      event.target.classList.add('active');
      
      // Filter cards
      cards.forEach(card => {
        const rating = parseInt(card.getAttribute('data-rating'));
        const isReported = card.getAttribute('data-reported') === 'true';
        
        let show = false;
        
        if (filter === 'all') {
          show = true;
        } else if (filter === 'reported') {
          show = isReported;
        } else if (filter === '5stars') {
          show = rating === 5;
        }
        
        card.style.display = show ? 'block' : 'none';
      });
    }
  </script>
  
</body>
</html>
