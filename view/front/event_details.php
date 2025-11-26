<?php
require_once __DIR__ . '/../../controller/EvenementController.php';
require_once __DIR__ . '/../../controller/ParticipationController.php';
require_once __DIR__ . '/../../controller/CommentController.php';

$eventController = new EvenementController();
$participationController = new ParticipationController();
$commentController = new CommentController();

$message = '';
$event = null;
$participants = [];
$nbParticipants = 0;

// Get event ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: events.php");
    exit;
}

$eventId = (int)$_GET['id'];
$event = $eventController->lireParId($eventId);

if (!$event) {
    header("Location: events.php");
    exit;
}

// Get participants for this event
$participants = $participationController->lireParEvenement($eventId);
$nbParticipants = count($participants);

// Get comments and ratings
$comments = $commentController->getEventComments($eventId);
$ratingStats = $commentController->getEventRatingStats($eventId);

// Handle participation form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'participate') {
    $participation = new Participation(
        null,
        $eventId,
        htmlspecialchars($_POST['nom_participant']),
        htmlspecialchars($_POST['email_participant']),
        new DateTime()
    );
    
    if ($participationController->inscrire($participation)) {
        $message = '<div class="alert success"><i class="fas fa-check-circle"></i> Registration confirmed! Welcome aboard!</div>';
        // Refresh participants list
        $participants = $participationController->lireParEvenement($eventId);
        $nbParticipants = count($participants);
    } else {
        $message = '<div class="alert error"><i class="fas fa-exclamation-circle"></i> Already registered or error occurred.</div>';
    }
}

// Handle comment form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_comment') {
    $comment = new Comment(
        null,
        $eventId,
        htmlspecialchars($_POST['user_name']),
        htmlspecialchars($_POST['user_email']),
        htmlspecialchars($_POST['comment_content']),
        (int)$_POST['rating']
    );
    
    if ($commentController->addComment($comment)) {
        $message = '<div class="alert success"><i class="fas fa-star"></i> Merci pour votre avis !</div>';
        // Refresh comments and stats
        $comments = $commentController->getEventComments($eventId);
        $ratingStats = $commentController->getEventRatingStats($eventId);
    } else {
        $message = '<div class="alert error"><i class="fas fa-exclamation-circle"></i> Erreur lors de l\'ajout du commentaire.</div>';
    }
}

// Handle comment interactions (like/dislike/report) via GET
if (isset($_GET['action']) && isset($_GET['comment_id'])) {
    $commentId = (int)$_GET['comment_id'];
    $action = $_GET['action'];
    
    if ($action === 'like' && isset($_GET['user_email'])) {
        $userEmail = $_GET['user_email'];
        if ($commentController->likeComment($commentId, $userEmail)) {
            $message = '<div class="alert success"><i class="fas fa-thumbs-up"></i> Vote enregistré !</div>';
        }
        // Refresh and redirect to clean URL
        header("Location: event_details.php?id=$eventId");
        exit;
    }
    
    if ($action === 'dislike' && isset($_GET['user_email'])) {
        $userEmail = $_GET['user_email'];
        if ($commentController->dislikeComment($commentId, $userEmail)) {
            $message = '<div class="alert success"><i class="fas fa-thumbs-down"></i> Vote enregistré !</div>';
        }
        header("Location: event_details.php?id=$eventId");
        exit;
    }
    
    if ($action === 'report' && isset($_GET['reason'])) {
        $reason = htmlspecialchars($_GET['reason']);
        if ($commentController->reportComment($commentId, $reason)) {
            $message = '<div class="alert success"><i class="fas fa-flag"></i> Commentaire signalé. Merci pour votre vigilance.</div>';
        }
        header("Location: event_details.php?id=$eventId");
        exit;
    }
}

$statuts = [
    'upcoming' => 'À venir',
    'ongoing' => 'En cours',
    'completed' => 'Terminé',
    'cancelled' => 'Annulé'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($event->getTitre()) ?> - FoxUnity</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Orbitron:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin: 20px auto;
            max-width: 1200px;
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

        .event-details-section {
            padding: 48px 24px;
            background: linear-gradient(180deg, #0f0f11 0%, #111216 100%);
            color: #fff;
            min-height: 100vh;
        }

        .event-details-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #f5c242;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 30px;
            transition: all 0.3s ease;
        }

        .back-button:hover {
            transform: translateX(-5px);
            color: #f39c12;
        }

        .event-header-detail {
            background: linear-gradient(135deg, #16161a, #1b1b20);
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.5);
        }

        .event-status-badge {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .badge-upcoming { background: #3b82f6; color: #fff; }
        .badge-ongoing { background: #10b981; color: #fff; }
        .badge-completed { background: #6b7280; color: #fff; }
        .badge-cancelled { background: #ef4444; color: #fff; }

        .event-title-detail {
            font-family: 'Orbitron', sans-serif;
            font-size: 2.5rem;
            color: #f5c242;
            margin-bottom: 20px;
            line-height: 1.2;
        }

        .event-meta-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(245, 194, 66, 0.1);
            padding: 15px;
            border-radius: 10px;
            border-left: 4px solid #f5c242;
        }

        .meta-item i {
            font-size: 1.5rem;
            color: #f5c242;
        }

        .meta-content {
            flex: 1;
        }

        .meta-label {
            font-size: 0.85rem;
            color: #969696;
            margin-bottom: 4px;
        }

        .meta-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: #fff;
        }

        .event-content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .event-description-detail {
            background: linear-gradient(135deg, #16161a, #1b1b20);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.5);
        }

        .section-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.8rem;
            color: #f5c242;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .description-text {
            color: #d7d9dd;
            font-size: 1.1rem;
            line-height: 1.8;
        }

        .event-sidebar {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .join-card {
            background: linear-gradient(135deg, #16161a, #1b1b20);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.5);
            text-align: center;
        }

        .participants-display {
            background: linear-gradient(135deg, #f5c242, #f39c12);
            color: #000;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
        }

        .participants-number {
            font-family: 'Orbitron', sans-serif;
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .participants-label {
            font-size: 1.1rem;
            font-weight: 600;
        }

        .btn-join-detail {
            width: 100%;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: none;
            padding: 18px;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 700;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
        }

        .btn-join-detail:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.5);
        }

        .btn-join-detail:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .participants-list-card {
            background: linear-gradient(135deg, #16161a, #1b1b20);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.5);
        }

        .participant-item {
            background: rgba(245, 194, 66, 0.1);
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
            border-left: 4px solid #f5c242;
        }

        .participant-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, #f5c242, #f39c12);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #000;
            font-weight: 700;
            font-size: 1.2rem;
        }

        .participant-info {
            flex: 1;
        }

        .participant-name {
            font-weight: 600;
            color: #fff;
            margin-bottom: 4px;
        }

        .participant-email {
            font-size: 0.9rem;
            color: #969696;
        }

        .empty-participants {
            text-align: center;
            padding: 40px;
            color: #969696;
        }

        .empty-participants i {
            font-size: 4rem;
            margin-bottom: 15px;
            opacity: 0.3;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #f5c242;
            font-weight: 600;
        }

        .form-group input {
            width: 100%;
            padding: 14px 18px;
            background: rgba(255,255,255,0.05);
            border: 2px solid rgba(255,255,255,0.1);
            border-radius: 10px;
            color: #fff;
            font-size: 1rem;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #f5c242;
            background: rgba(245,194,66,0.05);
        }

        .error-message {
            color: #ff6b6b;
            font-size: 0.85rem;
            margin-top: 6px;
            display: none;
            font-weight: 600;
        }

        .error-message.show {
            display: block;
        }

        .form-group.error input {
            border-color: #ff6b6b !important;
            background: rgba(255, 107, 107, 0.1) !important;
        }

        .form-group.success input {
            border-color: #10b981 !important;
        }

        @media (max-width: 968px) {
            .event-content-grid {
                grid-template-columns: 1fr;
            }
        }

        /* ========================================== */
        /* COMMENTS & RATINGS STYLES */
        /* ========================================== */

        .comments-section {
            background: linear-gradient(135deg, #16161a, #1b1b20);
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.5);
        }

        .rating-overview {
            display: flex;
            align-items: center;
            gap: 40px;
            padding: 30px;
            background: rgba(245, 194, 66, 0.1);
            border-radius: 15px;
            margin-bottom: 40px;
            border-left: 5px solid #f5c242;
        }

        .rating-score {
            text-align: center;
            min-width: 150px;
        }

        .rating-number {
            font-family: 'Orbitron', sans-serif;
            font-size: 4rem;
            font-weight: 700;
            color: #f5c242;
            line-height: 1;
        }

        .rating-stars {
            font-size: 1.5rem;
            color: #f5c242;
            margin: 10px 0;
        }

        .rating-count {
            color: #969696;
            font-size: 0.95rem;
        }

        .rating-bars {
            flex: 1;
        }

        .rating-bar-item {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 10px;
        }

        .rating-bar-label {
            min-width: 50px;
            color: #969696;
            font-size: 0.9rem;
        }

        .rating-bar-container {
            flex: 1;
            height: 8px;
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
            min-width: 40px;
            color: #969696;
            font-size: 0.9rem;
            text-align: right;
        }

        /* Star Rating Input */
        .star-rating-input {
            display: flex;
            gap: 8px;
            font-size: 2rem;
            margin: 15px 0;
        }

        .star-rating-input .star {
            cursor: pointer;
            color: rgba(255,255,255,0.2);
            transition: all 0.2s ease;
        }

        .star-rating-input .star:hover,
        .star-rating-input .star.active {
            color: #f5c242;
            transform: scale(1.2);
        }

        .form-group textarea {
            width: 100%;
            min-height: 120px;
            padding: 14px 18px;
            background: rgba(255,255,255,0.05);
            border: 2px solid rgba(255,255,255,0.1);
            border-radius: 10px;
            color: #fff;
            font-size: 1rem;
            font-family: 'Poppins', sans-serif;
            resize: vertical;
            transition: all 0.3s ease;
        }

        .form-group textarea:focus {
            outline: none;
            border-color: #f5c242;
            background: rgba(245,194,66,0.05);
        }

        .btn-submit-comment {
            width: 100%;
            background: linear-gradient(135deg, #f5c242, #f39c12);
            color: #000;
            border: none;
            padding: 16px;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 700;
            font-size: 1.05rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-submit-comment:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(245, 194, 66, 0.4);
        }

        /* Comment Item */
        .comment-item {
            background: rgba(255,255,255,0.03);
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 20px;
            border-left: 4px solid rgba(245, 194, 66, 0.3);
            transition: all 0.3s ease;
        }

        .comment-item:hover {
            background: rgba(255,255,255,0.05);
            border-left-color: #f5c242;
        }

        .comment-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
        }

        .comment-author {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .comment-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #f5c242, #f39c12);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #000;
            font-weight: 700;
            font-size: 1.3rem;
        }

        .comment-author-info h4 {
            color: #fff;
            font-size: 1.05rem;
            margin-bottom: 4px;
        }

        .comment-meta {
            display: flex;
            align-items: center;
            gap: 15px;
            color: #969696;
            font-size: 0.85rem;
        }

        .comment-rating {
            color: #f5c242;
            font-size: 1.1rem;
        }

        .comment-time {
            color: #969696;
        }

        .comment-content {
            color: #d7d9dd;
            line-height: 1.7;
            margin-bottom: 15px;
            font-size: 1rem;
        }

        .comment-actions {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .comment-action-btn {
            background: none;
            border: none;
            color: #969696;
            cursor: pointer;
            padding: 8px 15px;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .comment-action-btn:hover {
            background: rgba(255,255,255,0.05);
            color: #fff;
        }

        .comment-action-btn.liked {
            color: #10b981;
        }

        .comment-action-btn.disliked {
            color: #ef4444;
        }

        .comment-action-btn.report-btn:hover {
            color: #ef4444;
        }

        .empty-comments {
            text-align: center;
            padding: 60px 20px;
            color: #969696;
        }

        .empty-comments i {
            font-size: 5rem;
            margin-bottom: 20px;
            opacity: 0.2;
        }

        .empty-comments h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: #fff;
        }

        .reported-badge {
            background: #ef4444;
            color: white;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="bubbles">
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
            <a href="index.php">Home</a>
            <a href="events.php" class="active">Events</a>
            <a href="shop.html">Shop</a>
            <a href="trading.html">Trading</a>
            <a href="news.html">News</a>
            <a href="reclamation.html">Support</a>
            <a href="about.html">About Us</a>
        </nav>
        
        <div class="header-right">
            <a href="login.html" class="login-register-link">
                <i class="fas fa-user"></i> Login / Register
            </a>
        </div>
    </header>

    <main class="main-section">
        <?php if ($message): echo $message; endif; ?>

        <section class="event-details-section">
            <div class="event-details-container">
                <a href="events.php" class="back-button">
                    <i class="fas fa-arrow-left"></i> Back to Events
                </a>

                <div class="event-header-detail">
                    <span class="event-status-badge badge-<?= $event->getStatut() ?>">
                        <?= $statuts[$event->getStatut()] ?>
                    </span>
                    <h1 class="event-title-detail"><?= htmlspecialchars($event->getTitre()) ?></h1>
                    
                    <div class="event-meta-grid">
                        <div class="meta-item">
                            <i class="fas fa-calendar-check"></i>
                            <div class="meta-content">
                                <div class="meta-label">Start Date</div>
                                <div class="meta-value"><?= $event->getDateDebut()->format('M d, Y') ?></div>
                            </div>
                        </div>
                        
                        <div class="meta-item">
                            <i class="fas fa-clock"></i>
                            <div class="meta-content">
                                <div class="meta-label">Start Time</div>
                                <div class="meta-value"><?= $event->getDateDebut()->format('H:i') ?></div>
                            </div>
                        </div>
                        
                        <div class="meta-item">
                            <i class="fas fa-calendar-times"></i>
                            <div class="meta-content">
                                <div class="meta-label">End Date</div>
                                <div class="meta-value"><?= $event->getDateFin()->format('M d, Y') ?></div>
                            </div>
                        </div>
                        
                        <div class="meta-item">
                            <i class="fas fa-clock"></i>
                            <div class="meta-content">
                                <div class="meta-label">End Time</div>
                                <div class="meta-value"><?= $event->getDateFin()->format('H:i') ?></div>
                            </div>
                        </div>
                        
                        <div class="meta-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <div class="meta-content">
                                <div class="meta-label">Location</div>
                                <div class="meta-value"><?= htmlspecialchars($event->getLieu()) ?></div>
                            </div>
                        </div>
                        
                        <div class="meta-item">
                            <i class="fas fa-users"></i>
                            <div class="meta-content">
                                <div class="meta-label">Participants</div>
                                <div class="meta-value"><?= $nbParticipants ?> Registered</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="event-content-grid">
                    <div class="event-description-detail">
                        <h2 class="section-title">
                            <i class="fas fa-info-circle"></i>
                            Event Description
                        </h2>
                        <p class="description-text">
                            <?= nl2br(htmlspecialchars($event->getDescription())) ?>
                        </p>
                    </div>

                    <div class="event-sidebar">
                        <div class="join-card">
                            <div class="participants-display">
                                <div class="participants-number"><?= $nbParticipants ?></div>
                                <div class="participants-label">Participants Registered</div>
                            </div>
                            
                            <?php if ($event->getStatut() === 'upcoming'): ?>
                                <form method="POST" id="participationForm" novalidate>
                                    <input type="hidden" name="action" value="participate">
                                    
                                    <div class="form-group">
                                        <label for="nom_participant">Your Name *</label>
                                        <input type="text" id="nom_participant" name="nom_participant" placeholder="Enter your full name">
                                        <div class="error-message" id="error-nom_participant"></div>
                                    </div>

                                    <div class="form-group">
                                        <label for="email_participant">Your Email *</label>
                                        <input type="text" id="email_participant" name="email_participant" placeholder="your.email@example.com">
                                        <div class="error-message" id="error-email_participant"></div>
                                    </div>
                                    
                                    <button type="submit" class="btn-join-detail">
                                        <i class="fas fa-user-plus"></i> Join This Event
                                    </button>
                                </form>
                            <?php else: ?>
                                <button class="btn-join-detail" disabled>
                                    <i class="fas fa-ban"></i> Registration Closed
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- ========================================== -->
                <!-- COMMENTS & RATINGS SECTION -->
                <!-- ========================================== -->
                <div class="comments-section">
                    <h2 class="section-title">
                        <i class="fas fa-comments"></i>
                        Avis & Évaluations
                    </h2>

                    <!-- Rating Overview -->
                    <?php if ($ratingStats['total'] > 0): ?>
                    <div class="rating-overview">
                        <div class="rating-score">
                            <div class="rating-number"><?= number_format($ratingStats['average'], 1) ?></div>
                            <div class="rating-stars">
                                <?php
                                $fullStars = floor($ratingStats['average']);
                                $hasHalfStar = ($ratingStats['average'] - $fullStars) >= 0.5;
                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= $fullStars) {
                                        echo '<i class="fas fa-star"></i>';
                                    } elseif ($i == $fullStars + 1 && $hasHalfStar) {
                                        echo '<i class="fas fa-star-half-alt"></i>';
                                    } else {
                                        echo '<i class="far fa-star"></i>';
                                    }
                                }
                                ?>
                            </div>
                            <div class="rating-count"><?= $ratingStats['total'] ?> avis</div>
                        </div>

                        <div class="rating-bars">
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
                                    <div class="rating-bar-count"><?= $count ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Comment Form -->
                    <div style="background: rgba(245,194,66,0.05); padding: 30px; border-radius: 15px; margin-bottom: 30px;">
                        <h3 style="color: #f5c242; margin-bottom: 20px; font-size: 1.3rem;">
                            <i class="fas fa-pen"></i> Partagez votre expérience
                        </h3>
                        
                        <form method="POST" action="" id="commentForm" novalidate>
                            <input type="hidden" name="action" value="add_comment">
                            
                            <div class="form-group">
                                <label for="user_name_comment">Votre nom *</label>
                                <input type="text" id="user_name_comment" name="user_name" placeholder="Nom complet" required>
                                <div class="error-message" id="error-user_name_comment"></div>
                            </div>

                            <div class="form-group">
                                <label for="user_email_comment">Votre email *</label>
                                <input type="email" id="user_email_comment" name="user_email" placeholder="votre.email@exemple.com" required>
                                <div class="error-message" id="error-user_email_comment"></div>
                            </div>

                            <div class="form-group">
                                <label>Note *</label>
                                <div class="star-rating-input" id="starRating">
                                    <i class="fas fa-star star" data-rating="1"></i>
                                    <i class="fas fa-star star" data-rating="2"></i>
                                    <i class="fas fa-star star" data-rating="3"></i>
                                    <i class="fas fa-star star" data-rating="4"></i>
                                    <i class="fas fa-star star" data-rating="5"></i>
                                </div>
                                <input type="hidden" id="rating" name="rating" value="5">
                                <div class="error-message" id="error-rating"></div>
                            </div>

                            <div class="form-group">
                                <label for="comment_content">Votre avis *</label>
                                <textarea id="comment_content" name="comment_content" placeholder="Partagez votre expérience sur cet événement..." required></textarea>
                                <div class="error-message" id="error-comment_content"></div>
                            </div>

                            <button type="submit" class="btn-submit-comment">
                                <i class="fas fa-paper-plane"></i> Publier mon avis
                            </button>
                        </form>
                    </div>

                    <!-- Comments List -->
                    <h3 style="color: #fff; margin-bottom: 25px; font-size: 1.3rem;">
                        <i class="fas fa-list"></i> Tous les avis (<?= count($comments) ?>)
                    </h3>

                    <?php if (empty($comments)): ?>
                        <div class="empty-comments">
                            <i class="fas fa-comment-slash"></i>
                            <h3>Aucun avis pour le moment</h3>
                            <p>Soyez le premier à partager votre expérience !</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($comments as $comment): ?>
                            <div class="comment-item">
                                <div class="comment-header">
                                    <div class="comment-author">
                                        <div class="comment-avatar">
                                            <?= $comment->getUserInitials() ?>
                                        </div>
                                        <div class="comment-author-info">
                                            <h4><?= htmlspecialchars($comment->getUserName()) ?></h4>
                                            <div class="comment-meta">
                                                <span class="comment-rating">
                                                    <?= str_repeat('★', $comment->getRating()) ?><?= str_repeat('☆', 5 - $comment->getRating()) ?>
                                                </span>
                                                <span class="comment-time">
                                                    <i class="far fa-clock"></i> Il y a <?= $comment->getTimeAgo() ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <?php if ($comment->getIsReported()): ?>
                                        <span class="reported-badge">
                                            <i class="fas fa-flag"></i> Signalé
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <div class="comment-content">
                                    <?= nl2br(htmlspecialchars($comment->getContent())) ?>
                                </div>

                                <div class="comment-actions">
                                    <button class="comment-action-btn like-btn" data-comment-id="<?= $comment->getIdComment() ?>">
                                        <i class="fas fa-thumbs-up"></i> 
                                        <span><?= $comment->getLikes() ?></span>
                                    </button>
                                    <button class="comment-action-btn dislike-btn" data-comment-id="<?= $comment->getIdComment() ?>">
                                        <i class="fas fa-thumbs-down"></i> 
                                        <span><?= $comment->getDislikes() ?></span>
                                    </button>
                                    <?php if (!$comment->getIsReported()): ?>
                                    <button class="comment-action-btn report-btn" data-comment-id="<?= $comment->getIdComment() ?>">
                                        <i class="fas fa-flag"></i> Signaler
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="participants-list-card">
                    <h2 class="section-title">
                        <i class="fas fa-user-friends"></i>
                        Registered Participants
                    </h2>
                    
                    <?php if (empty($participants)): ?>
                        <div class="empty-participants">
                            <i class="fas fa-user-slash"></i>
                            <p>No participants yet. Be the first to join!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($participants as $participant): ?>
                            <div class="participant-item">
                                <div class="participant-avatar">
                                    <?= strtoupper(substr($participant->getNomParticipant(), 0, 1)) ?>
                                </div>
                                <div class="participant-info">
                                    <div class="participant-name"><?= htmlspecialchars($participant->getNomParticipant()) ?></div>
                                    <div class="participant-email"><?= htmlspecialchars($participant->getEmailParticipant()) ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <div class="footer-content">
            <div class="footer-section">
                <h4>FoxUnity</h4>
                <p>Gaming for Good - Every action makes a difference</p>
            </div>
            <div class="footer-section">
                <h4>Support</h4>
                <a href="reclamation.html">Contact Support</a>
            </div>
            <div class="footer-section">
                <h4>Follow Us</h4>
                <div class="social-links">
                    <a href="#"><i class="fab fa-discord"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>© 2025 FoxUnity. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Validation Functions 
        const Validator = {
            isEmpty: function(value) {
                return value.trim() === '';
            },
            
            isValidLength: function(value, min, max) {
                const length = value.trim().length;
                return length >= min && length <= max;
            },
            
            isValidEmail: function(email) {
                const emailPattern = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
                return emailPattern.test(email.trim());
            },
            
            showError: function(fieldId, message) {
                const field = document.getElementById(fieldId);
                const errorDiv = document.getElementById('error-' + fieldId);
                const formGroup = field.closest('.form-group');
                
                formGroup.classList.add('error');
                formGroup.classList.remove('success');
                errorDiv.textContent = message;
                errorDiv.classList.add('show');
            },
            
            clearError: function(fieldId) {
                const field = document.getElementById(fieldId);
                const errorDiv = document.getElementById('error-' + fieldId);
                const formGroup = field.closest('.form-group');
                
                formGroup.classList.remove('error');
                formGroup.classList.add('success');
                errorDiv.classList.remove('show');
            }
        };

        // Participation Form Validation
        const participationForm = document.getElementById('participationForm');
        if (participationForm) {
            participationForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                let isValid = true;
                
                // Validate Name
                const nom = document.getElementById('nom_participant').value;
                if (Validator.isEmpty(nom)) {
                    Validator.showError('nom_participant', 'Le nom est obligatoire');
                    isValid = false;
                } else if (!Validator.isValidLength(nom, 2, 100)) {
                    Validator.showError('nom_participant', 'Le nom doit contenir entre 2 et 100 caractères');
                    isValid = false;
                } else if (!/^[a-zA-ZÀ-ÿ\s'-]+$/.test(nom.trim())) {
                    Validator.showError('nom_participant', 'Le nom ne peut contenir que des lettres, espaces, apostrophes et tirets');
                    isValid = false;
                } else {
                    Validator.clearError('nom_participant');
                }
                
                // Validate Email
                const email = document.getElementById('email_participant').value;
                if (Validator.isEmpty(email)) {
                    Validator.showError('email_participant', 'L\'email est obligatoire');
                    isValid = false;
                } else if (!Validator.isValidEmail(email)) {
                    Validator.showError('email_participant', 'Format d\'email invalide (ex: exemple@domaine.com)');
                    isValid = false;
                } else {
                    Validator.clearError('email_participant');
                }
                
                if (isValid) {
                    this.submit();
                }
            });
            
            // Real-time validation
            ['nom_participant', 'email_participant'].forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field) {
                    field.addEventListener('input', function() {
                        const errorDiv = document.getElementById('error-' + fieldId);
                        if (errorDiv.classList.contains('show')) {
                            Validator.clearError(fieldId);
                        }
                    });
                }
            });
        }

        // ========================================== 
        // COMMENT FORM & STAR RATING
        // ========================================== 

        // Star Rating Interactive Selector
        const starRating = document.getElementById('starRating');
        const ratingInput = document.getElementById('rating');
        
        if (starRating) {
            const stars = starRating.querySelectorAll('.star');
            let currentRating = 5; // Default 5 stars
            
            // Set all stars active by default
            stars.forEach(star => star.classList.add('active'));
            
            stars.forEach((star, index) => {
                // Click to select rating
                star.addEventListener('click', function() {
                    currentRating = parseInt(this.getAttribute('data-rating'));
                    ratingInput.value = currentRating;
                    updateStars(currentRating);
                });
                
                // Hover preview
                star.addEventListener('mouseenter', function() {
                    const hoverRating = parseInt(this.getAttribute('data-rating'));
                    updateStars(hoverRating);
                });
            });
            
            // Reset to current rating on mouse leave
            starRating.addEventListener('mouseleave', function() {
                updateStars(currentRating);
            });
            
            function updateStars(rating) {
                stars.forEach((star, index) => {
                    if (index < rating) {
                        star.classList.add('active');
                    } else {
                        star.classList.remove('active');
                    }
                });
            }
        }

        // Comment Form Validation
        const commentForm = document.getElementById('commentForm');
        if (commentForm) {
            commentForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                let isValid = true;
                
                // Validate Name
                const userName = document.getElementById('user_name_comment').value;
                if (Validator.isEmpty(userName)) {
                    Validator.showError('user_name_comment', 'Le nom est obligatoire');
                    isValid = false;
                } else if (!Validator.isValidLength(userName, 2, 100)) {
                    Validator.showError('user_name_comment', 'Le nom doit contenir entre 2 et 100 caractères');
                    isValid = false;
                } else {
                    Validator.clearError('user_name_comment');
                }
                
                // Validate Email
                const userEmail = document.getElementById('user_email_comment').value;
                if (Validator.isEmpty(userEmail)) {
                    Validator.showError('user_email_comment', 'L\'email est obligatoire');
                    isValid = false;
                } else if (!Validator.isValidEmail(userEmail)) {
                    Validator.showError('user_email_comment', 'Format d\'email invalide');
                    isValid = false;
                } else {
                    Validator.clearError('user_email_comment');
                }
                
                // Validate Rating
                const rating = parseInt(ratingInput.value);
                if (!rating || rating < 1 || rating > 5) {
                    Validator.showError('rating', 'Veuillez sélectionner une note');
                    isValid = false;
                } else {
                    Validator.clearError('rating');
                }
                
                // Validate Comment Content
                const commentContent = document.getElementById('comment_content').value;
                if (Validator.isEmpty(commentContent)) {
                    Validator.showError('comment_content', 'Le commentaire est obligatoire');
                    isValid = false;
                } else if (!Validator.isValidLength(commentContent, 10, 1000)) {
                    Validator.showError('comment_content', 'Le commentaire doit contenir entre 10 et 1000 caractères');
                    isValid = false;
                } else {
                    Validator.clearError('comment_content');
                }
                
                if (isValid) {
                    this.submit();
                }
            });
            
            // Real-time validation
            ['user_name_comment', 'user_email_comment', 'comment_content'].forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field) {
                    field.addEventListener('input', function() {
                        const errorDiv = document.getElementById('error-' + fieldId);
                        if (errorDiv && errorDiv.classList.contains('show')) {
                            Validator.clearError(fieldId);
                        }
                    });
                }
            });
        }

        // ========================================== 
        // COMMENT INTERACTIONS (Like/Dislike/Report)
        // ========================================== 

        // Like buttons
        document.querySelectorAll('.like-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const commentId = this.getAttribute('data-comment-id');
                const userEmail = prompt('Entrez votre email pour liker ce commentaire:');
                
                if (userEmail && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(userEmail)) {
                    // Redirect to same page with action parameters
                    window.location.href = `?id=<?= $eventId ?>&action=like&comment_id=${commentId}&user_email=${encodeURIComponent(userEmail)}`;
                } else if (userEmail) {
                    alert('Email invalide');
                }
            });
        });

        // Dislike buttons
        document.querySelectorAll('.dislike-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const commentId = this.getAttribute('data-comment-id');
                const userEmail = prompt('Entrez votre email pour disliker ce commentaire:');
                
                if (userEmail && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(userEmail)) {
                    window.location.href = `?id=<?= $eventId ?>&action=dislike&comment_id=${commentId}&user_email=${encodeURIComponent(userEmail)}`;
                } else if (userEmail) {
                    alert('Email invalide');
                }
            });
        });

        // Report buttons
        document.querySelectorAll('.report-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const commentId = this.getAttribute('data-comment-id');
                const reason = prompt('Pourquoi signalez-vous ce commentaire?\n(spam, contenu inapproprié, hors sujet, etc.)');
                
                if (reason && reason.trim().length > 0) {
                    if (confirm('Êtes-vous sûr de vouloir signaler ce commentaire ?')) {
                        window.location.href = `?id=<?= $eventId ?>&action=report&comment_id=${commentId}&reason=${encodeURIComponent(reason)}`;
                    }
                }
            });
        });
    </script>
</body>
</html>
