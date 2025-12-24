<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Inclure les contrôleurs
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../controller/reclamationcontroller.php';
require_once __DIR__ . '/../../controller/SatisfactionController.php';
require_once __DIR__ . '/../../controller/UserController.php';
require_once __DIR__ . '/../../model/Reclamation.php';
require_once __DIR__ . '/../../model/Satisfaction.php';

// Check if user is logged in
if (!UserController::isLoggedIn()) {
    header('Location: ../front/login.php');
    exit();
}

$currentUser = UserController::getCurrentUser();

// Get user image
$userImage = null;
if ($currentUser && $currentUser->getImage()) {
    $userImage = '../../view/' . $currentUser->getImage();
}

$reclamationController = new ReclamationController();
$satisfactionController = new SatisfactionController();

// Récupérer toutes les évaluations
$allSatisfactions = $satisfactionController->getAllSatisfactions();
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FoxUnity - Public Evaluations</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Orbitron:wght@700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">

    <style>
        :root {
            --primary-color: #ff7a00;
            --primary-dark: #ff4f00;
            --bg-dark: #0a0a0a;
            --bg-card: rgba(20, 20, 20, 0.95);
            --text-light: #ffffff;
            --text-gray: #aaaaaa;
            --border-color: rgba(255, 255, 255, 0.1);
            --success-color: #4caf50;
            --warning-color: #ff9800;
            --danger-color: #f44336;
        }

        /* Espacement supplémentaire pour éviter la compression */
        .main {
            padding: 40px 60px !important;
            margin-left: 30px !important;
        }

        .topbar {
            margin-bottom: 40px !important;
            padding: 30px 50px !important;
        }

        .stats-grid {
            margin-bottom: 40px !important;
            gap: 25px !important;
        }

        .reviews-section {
            margin-bottom: 40px !important;
        }

        .reviews-list {
            gap: 30px !important;
        }

        .review-card {
            padding: 30px !important;
        }

        /* Admin Dropdown Active State */
        .admin-dropdown.active .admin-dropdown-menu {
            opacity: 1 !important;
            visibility: visible !important;
            transform: translateY(0) !important;
        }

        .admin-user i.fa-user-circle {
            font-size: 35px;
            color: #fff;
        }

        .admin-user span {
            color: #fff;
            font-weight: 600;
            font-size: 16px;
        }

        .admin-user i.fa-chevron-down {
            font-size: 12px;
            color: #fff;
            transition: transform 0.3s ease;
        }

        .admin-dropdown.active .admin-user i.fa-chevron-down {
            transform: rotate(180deg);
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, var(--bg-card) 0%, rgba(10, 10, 10, 0.95) 100%);
            border: 2px solid var(--border-color);
            border-radius: 15px;
            padding: 25px;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            border-color: rgba(255, 122, 0, 0.3);
            box-shadow: 0 10px 25px rgba(255, 122, 0, 0.2);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
            font-size: 24px;
        }

        .stat-icon.rating {
            background: rgba(76, 175, 80, 0.2);
            color: #4caf50;
        }

        .stat-info h3 {
            font-size: 14px;
            color: var(--text-gray);
            margin-bottom: 5px;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 700;
            font-family: 'Orbitron', sans-serif;
        }

        /* Reviews Section */
        .reviews-section {
            margin-bottom: 30px;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .section-header h2 {
            font-family: 'Orbitron', sans-serif;
            font-size: 22px;
            color: var(--primary-color);
        }

        .section-header h2 span {
            color: var(--primary-color);
        }

        .reviews-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .review-card {
            background: linear-gradient(135deg, var(--bg-card) 0%, rgba(10, 10, 10, 0.95) 100%);
            border: 2px solid var(--border-color);
            border-radius: 15px;
            padding: 25px;
            transition: all 0.3s ease;
        }

        .review-card:hover {
            border-color: rgba(255, 122, 0, 0.3);
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .review-user {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-avatar-small {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #2196f3, #1976d2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }

        .user-info h4 {
            font-size: 16px;
            margin-bottom: 5px;
        }

        .user-info p {
            font-size: 12px;
            color: var(--text-gray);
        }

        .review-content {
            margin-bottom: 20px;
        }

        .review-text {
            color: var(--text-light);
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .review-date {
            font-size: 12px;
            color: var(--text-gray);
        }

        .review-response {
            background: rgba(255, 193, 7, 0.1);
            border-left: 3px solid #ffc107;
            padding: 15px;
            border-radius: 0 10px 10px 0;
            margin-top: 15px;
        }

        .response-header h5 {
            font-size: 14px;
            color: #ffc107;
            margin-bottom: 10px;
        }

        .response-text {
            color: var(--text-light);
            line-height: 1.6;
        }

        .attachment-thumbnail:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 20px rgba(255, 122, 0, 0.4);
        }

        #attachment-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        #attachment-modal .modal-close:hover {
            background: rgba(255, 60, 60, 0.4);
            transform: rotate(90deg);
        }

        #attachment-modal img {
            max-width: 100%;
            max-height: 85vh;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
        }

        #attachment-modal video {
            max-width: 100%;
            max-height: 85vh;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
        }

        .action-btn {
            padding: 8px 15px;
            background: transparent;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-light);
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 14px;
            text-decoration: none;
        }

        .action-btn:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        /* Footer */
        .site-footer {
            text-align: center;
            padding: 20px;
            margin-top: 40px;
            border-top: 1px solid var(--border-color);
            color: var(--text-gray);
        }

        .site-footer span {
            color: var(--primary-color);
        }

        @media (max-width: 1024px) {
            .sidebar {
                width: 250px;
            }

            .main {
                margin-left: 250px;
            }
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }

            .topbar {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .review-header {
                flex-direction: column;
                gap: 15px;
            }
        }

        @media (max-width: 576px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .main {
                padding: 15px;
            }
        }

        /* Footer Styles */
        .site-footer {
            background: linear-gradient(180deg, rgba(4, 8, 20, 0.6), rgba(6, 14, 30, 0.85));
            border-top: 1px solid rgba(255, 255, 255, 0.03);
            position: relative;
            z-index: 10;
            backdrop-filter: blur(6px) saturate(110%);
            color: var(--text-gray);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.02);
            margin-top: 40px;
        }

        .footer-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 50px 48px 30px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 30px;
        }

        .footer-section h4 {
            color: #f5f7fa;
            font-size: 1.1rem;
            margin-bottom: 12px;
            font-family: 'Orbitron', sans-serif;
        }

        .footer-section p {
            color: var(--text-gray);
            line-height: 1.6;
            font-size: 0.95rem;
        }

        .footer-section a {
            display: block;
            color: rgba(245, 247, 250, 0.7);
            text-decoration: none;
            margin-bottom: 10px;
            transition: all 0.18s ease;
            font-size: 0.95rem;
        }

        .footer-section a:hover {
            color: var(--primary-color);
            transform: translateX(6px);
        }

        .back-to-top-link {
            display: inline-flex !important;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            background: rgba(255, 122, 0, 0.06);
            border: 1px solid rgba(255, 122, 0, 0.12);
            border-radius: 20px;
            color: var(--primary-color) !important;
            font-weight: 700;
            cursor: pointer;
        }

        .back-to-top-link:hover {
            background: rgba(255, 122, 0, 0.12);
            border-color: rgba(255, 122, 0, 0.18);
            transform: translateY(-3px);
        }

        .social-links {
            display: flex;
            gap: 14px;
        }

        .social-links a {
            width: 42px;
            height: 42px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #d0d6db;
            font-size: 1.05rem;
            transition: all 0.2s ease;
        }

        .social-links a:hover {
            background: linear-gradient(90deg, var(--primary-color), #ff9500);
            border-color: rgba(255, 122, 0, 0.9);
            color: #000;
            transform: translateY(-3px);
        }

        .dashboard-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            background: rgba(255, 122, 0, 0.06);
            border: 1px solid rgba(255, 122, 0, 0.12);
            border-radius: 20px;
            color: var(--primary-color);
            font-weight: 700;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .dashboard-link:hover {
            background: rgba(255, 122, 0, 0.12);
            border-color: rgba(255, 122, 0, 0.18);
            transform: translateY(-3px);
        }

        .footer-bottom {
            text-align: center;
            padding: 18px 12px;
            border-top: 1px solid rgba(255, 255, 255, 0.03);
            color: rgba(245, 247, 250, 0.6);
            font-size: 0.92rem;
        }

        .footer-bottom span {
            color: var(--primary-color);
        }

        @media (max-width: 768px) {
            .footer-content {
                padding: 40px 30px 20px;
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body class="dashboard-body">
    <div class="stars"></div>
    <?php include __DIR__ . '/includes/transition.php'; ?>
    <!-- Sidebar -->
    <div class="sidebar">
        <img src="../images/Nine__1_-removebg-preview.png" alt="Nine Tailed Fox Logo" class="dashboard-logo">
        <h2>Dashboard</h2>
        <a href="dashboard.php">Overview</a>
        <a href="users.php">Users</a>
        <a href="shopb.php">Shop</a>
        <a href="tradingb.php">Trade History</a>
        <a href="eventsb.php">Events</a>
        <a href="news_admin.php">News</a>
        <a href="news_history.php" id="news-history-link">News History</a>
        <a href="categories.php" id="categories-link">Categories</a>
        <a href="newsletter_admin.php" id="newsletter-link">Newsletter</a>
        <a href="reclamback.php">Support</a>
        <a href="evaluations_publiques.php" class="active">Public Evaluations</a>
        <a href="../front/index.php">← Return Homepage</a>
    </div>

    <!-- Main Content -->
    <div class="main">
        <div class="topbar">
            <h1>Public <span>Evaluations</span></h1>
            <div class="topbar-right" style="display: flex; align-items: center; gap: 20px;">
                <!-- Système de Notifications Tout-en-un -->
                <?php include __DIR__ . '/includes/notifications.php'; ?>

                <div class="admin-dropdown" id="adminDropdown">
                    <div class="user admin-user">
                        <?php if ($userImage): ?>
                            <img src="<?php echo htmlspecialchars($userImage); ?>" alt="Admin Avatar">
                        <?php else: ?>
                            <i class="fas fa-user-circle"></i>
                        <?php endif; ?>
                        <span><?php echo htmlspecialchars($currentUser->getUsername()); ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </div>

                    <div class="admin-dropdown-menu">
                        <a href="admin-profile.php" class="dropdown-item">
                            <i class="fas fa-user"></i>
                            <span>My Profile</span>
                        </a>

                        <div class="dropdown-divider"></div>

                        <a href="../front/logout.php" class="dropdown-item logout">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon rating">
                    <i class="fas fa-star"></i>
                </div>
                <div class="stat-info">
                    <h3>Total Evaluations</h3>
                    <div class="stat-value"><?php echo count($allSatisfactions); ?></div>
                </div>
            </div>
        </div>

        <!-- Section des évaluations publiques -->
        <div class="reviews-section">
            <div class="section-header">
                <h2>Évaluations <span>Publiques</span></h2>
                <div class="review-count">
                    <span><?php echo count($allSatisfactions); ?> évaluation(s)</span>
                </div>
            </div>

            <div class="reviews-list">
                <?php if (empty($allSatisfactions)): ?>
                    <div class="review-card">
                        <p style="text-align: center; color: var(--text-gray); padding: 40px;">
                            <i class="fas fa-star"
                                style="font-size: 48px; margin-bottom: 20px; display: block; opacity: 0.5;"></i>
                            Aucune évaluation publique pour le moment.
                        </p>
                    </div>
                <?php else: ?>
                    <?php foreach ($allSatisfactions as $satisfaction):
                        // Récupérer la réclamation associée avec toutes ses données
                        $reclamation = $reclamationController->getReclamationById($satisfaction->getIdReclamation());
                        if (!$reclamation)
                            continue;
                        // S'assurer que la pièce jointe est bien récupérée
                        // var_dump($reclamation); // Décommenter pour debug si nécessaire
                        ?>
                        <div class="review-card">
                            <div class="review-header">
                                <div class="review-user">
                                    <div class="user-avatar-small">
                                        <?php echo strtoupper(substr($satisfaction->getEmail() ?? 'U', 0, 2)); ?>
                                    </div>
                                    <div class="user-info">
                                        <h4><?php echo htmlspecialchars($satisfaction->getEmail() ?? 'Utilisateur'); ?></h4>
                                        <p><?php echo date('d/m/Y H:i', strtotime($satisfaction->getDateEvaluation())); ?></p>
                                    </div>
                                </div>
                                <div>
                                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                                        <?php
                                        $rating = $satisfaction->getRating();
                                        for ($i = 1; $i <= 5; $i++):
                                            ?>
                                            <i class="fas fa-star"
                                                style="color: <?php echo $i <= $rating ? '#ffc107' : '#444'; ?>; font-size: 20px;"></i>
                                        <?php endfor; ?>
                                        <span
                                            style="color: var(--text-light); font-weight: 700; font-size: 18px; margin-left: 5px;"><?php echo $rating; ?>/5</span>
                                    </div>
                                    <a href="reclamback.php?view_id=<?php echo $reclamation['id_reclamation']; ?>&ajax=1"
                                        class="action-btn view-request" data-id="<?php echo $reclamation['id_reclamation']; ?>"
                                        style="text-decoration: none;">
                                        <i class="fas fa-eye"></i> See the claim
                                    </a>
                                </div>
                            </div>

                            <div class="review-content">
                                <div style="margin-bottom: 15px;">
                                    <h5 style="color: var(--primary-color); margin-bottom: 5px;">
                                        <i class="fas fa-tag"></i>
                                        <?php echo htmlspecialchars($reclamation['sujet'] ?? 'Sujet'); ?>
                                    </h5>
                                    <?php if (!empty($reclamation['categorie'])): ?>
                                        <span
                                            style="background: rgba(255, 122, 0, 0.2); color: #ff7a00; padding: 4px 12px; border-radius: 12px; font-size: 11px; font-weight: 600; border: 1px solid rgba(255, 122, 0, 0.3);">
                                            <?php echo htmlspecialchars($reclamation['categorie']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <?php if ($satisfaction->getCommentaire()): ?>
                                    <div class="review-response">
                                        <div class="response-header">
                                            <h5>
                                                <i class="fas fa-comment"></i> Commentaire de l'utilisateur
                                            </h5>
                                        </div>
                                        <div class="response-text" style="font-style: italic;">
                                            "<?php echo nl2br(htmlspecialchars($satisfaction->getCommentaire())); ?>"
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <?php
                                // Vérifier et afficher les pièces jointes
                                $pieceJointe = isset($reclamation['piece_jointe']) ? trim($reclamation['piece_jointe']) : '';
                                if (!empty($pieceJointe)):
                                    // Utiliser une URL complète pour garantir l'accès
                                    // Le fichier est stocké comme "uploads/reclamations/filename.ext"
                                    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
                                    // Construire le chemin : si piece_jointe commence déjà par uploads/, on l'utilise tel quel
                                    if (strpos($pieceJointe, 'uploads/') === 0) {
                                        $filePath = $baseUrl . '/foxunity/' . $pieceJointe;
                                    } else {
                                        $filePath = $baseUrl . '/foxunity/uploads/reclamations/' . basename($pieceJointe);
                                    }
                                    $fileExt = strtolower(pathinfo($pieceJointe, PATHINFO_EXTENSION));
                                    $isImage = in_array($fileExt, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                                    $isVideo = in_array($fileExt, ['mp4', 'mov', 'avi', 'webm', 'mkv']);
                                    ?>
                                    <div class="attachment-preview"
                                        style="margin-top: 15px; padding: 15px; background: rgba(255,122,0,0.05); border: 1px solid rgba(255,122,0,0.2); border-radius: 8px;">
                                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                                            <i class="fas fa-paperclip" style="color: #ff7a00;"></i>
                                            <strong style="color: #ff7a00;">Attachment:</strong>
                                        </div>
                                        <?php if ($isImage): ?>
                                            <img src="<?php echo htmlspecialchars($filePath); ?>" alt="Attachment"
                                                class="attachment-thumbnail" data-type="image"
                                                data-src="<?php echo htmlspecialchars($filePath); ?>"
                                                style="max-width: 300px; max-height: 200px; border-radius: 8px; cursor: pointer; border: 2px solid rgba(255,122,0,0.3); transition: transform 0.3s ease;"
                                                onclick="openAttachmentModal(this)">
                                        <?php elseif ($isVideo): ?>
                                            <video controls class="attachment-thumbnail" data-type="video"
                                                data-src="<?php echo htmlspecialchars($filePath); ?>"
                                                style="max-width: 500px; max-height: 300px; border-radius: 8px; border: 2px solid rgba(255,122,0,0.3); cursor: pointer;"
                                                onclick="openAttachmentModal(this)">
                                                <source src="<?php echo htmlspecialchars($filePath); ?>"
                                                    type="video/<?php echo $fileExt; ?>">
                                                Your browser does not support the video tag.
                                            </video>
                                        <?php else: ?>
                                            <a href="<?php echo htmlspecialchars($filePath); ?>" target="_blank"
                                                style="color: #ff7a00; text-decoration: none;">
                                                <i class="fas fa-download"></i> Download attachment
                                            </a>
                                        <?php endif; ?>
                                        <div style="margin-top: 10px;">
                                            <a href="<?php echo htmlspecialchars($filePath); ?>" target="_blank"
                                                style="color: #ff7a00; text-decoration: none; font-size: 12px;">
                                                <i class="fas fa-external-link-alt"></i> Open in new tab
                                            </a>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div class="review-date">
                                    <i class="far fa-calendar"></i> Évalué le
                                    <?php echo date('d/m/Y à H:i', strtotime($satisfaction->getDateEvaluation())); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>


            <!-- Attachment Modal -->
            <div id="attachment-modal" class="modal" style="display: none;">
                <div class="modal-content"
                    style="max-width: 90vw; max-height: 90vh; background: rgba(10,10,10,0.98); border: 2px solid rgba(255,122,0,0.3); border-radius: 15px; padding: 20px; position: relative;">
                    <button class="modal-close" onclick="closeAttachmentModal()"
                        style="position: absolute; top: 15px; right: 15px; background: rgba(255,60,60,0.2); color: #ff3c3c; border: none; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; font-size: 20px; display: flex; align-items: center; justify-content: center; z-index: 10; transition: all 0.3s ease;">
                        <i class="fas fa-times"></i>
                    </button>
                    <div id="attachment-modal-content"
                        style="display: flex; align-items: center; justify-content: center; min-height: 400px;">
                        <!-- Content will be inserted here -->
                    </div>
                    <div style="text-align: center; margin-top: 15px;">
                        <a id="attachment-download-link" href="#" target="_blank"
                            style="color: #ff7a00; text-decoration: none; font-size: 14px;">
                            <i class="fas fa-download"></i> Download
                        </a>
                    </div>
                </div>
            </div>

            <footer class="site-footer">
                © 2025 <span>Nine Tailed Fox</span>. All Rights Reserved.
            </footer>
        </div>

        <script>
            // Attachment Modal Functions
            function openAttachmentModal(element) {
                const modal = document.getElementById('attachment-modal');
                const modalContent = document.getElementById('attachment-modal-content');
                const downloadLink = document.getElementById('attachment-download-link');
                const fileSrc = element.getAttribute('data-src');
                const fileType = element.getAttribute('data-type');

                if (!modal || !modalContent) {
                    console.error('Attachment modal elements not found');
                    return;
                }

                modal.style.display = 'flex';
                if (downloadLink) {
                    downloadLink.href = fileSrc;
                }

                if (fileType === 'image') {
                    console.log('Opening image modal with src:', fileSrc);

                    // Afficher directement l'image avec gestion d'erreur
                    modalContent.innerHTML = `
                    <img src="${fileSrc}" 
                         alt="Full size attachment" 
                         style="max-width: 100%; max-height: 85vh; border-radius: 10px; box-shadow: 0 10px 40px rgba(0,0,0,0.5); display: block; margin: 0 auto;"
                         onerror="this.onerror=null; this.parentElement.innerHTML='<div style=\\'text-align: center; padding: 40px; color: #ff7a00;\\'><i class=\\'fas fa-exclamation-triangle\\' style=\\'font-size: 48px; margin-bottom: 20px; display: block;\\'></i><p style=\\'font-size: 18px; margin-bottom: 10px;\\'>Impossible de charger l\\'image</p><p style=\\'font-size: 14px; color: #aaa; word-break: break-all;\\'>Chemin: ${fileSrc}</p><a href=\\'${fileSrc}\\' target=\\'_blank\\' style=\\'color: #ff7a00; text-decoration: none; margin-top: 20px; display: inline-block;\\'><i class=\\'fas fa-external-link-alt\\'></i> Ouvrir dans un nouvel onglet</a></div>';">
                `;
                } else if (fileType === 'video') {
                    const videoExt = fileSrc.split('.').pop();
                    const video = document.createElement('video');
                    video.controls = true;
                    video.autoplay = true;
                    video.style.cssText = 'max-width: 100%; max-height: 85vh; border-radius: 10px; box-shadow: 0 10px 40px rgba(0,0,0,0.5); display: block; margin: 0 auto;';

                    const source = document.createElement('source');
                    source.src = fileSrc;
                    source.type = `video/${videoExt}`;
                    video.appendChild(source);

                    video.onerror = function () {
                        console.error('Erreur de chargement de la vidéo:', fileSrc);
                        modalContent.innerHTML = `
                        <div style="text-align: center; padding: 40px; color: #ff7a00;">
                            <i class="fas fa-exclamation-triangle" style="font-size: 48px; margin-bottom: 20px; display: block;"></i>
                            <p style="font-size: 18px; margin-bottom: 10px;">Impossible de charger la vidéo</p>
                            <p style="font-size: 14px; color: #aaa;">Chemin: ${fileSrc}</p>
                            <a href="${fileSrc}" target="_blank" style="color: #ff7a00; text-decoration: none; margin-top: 20px; display: inline-block;">
                                <i class="fas fa-download"></i> Télécharger la vidéo
                            </a>
                        </div>
                    `;
                    };

                    modalContent.innerHTML = '';
                    modalContent.appendChild(video);
                }

                // Prevent body scroll when modal is open
                document.body.style.overflow = 'hidden';
            }

            function closeAttachmentModal() {
                const modal = document.getElementById('attachment-modal');
                const modalContent = document.getElementById('attachment-modal-content');

                if (!modal) return;

                modal.style.display = 'none';
                if (modalContent) {
                    modalContent.innerHTML = '';
                }

                // Restore body scroll
                document.body.style.overflow = '';
            }

            // Close modal when clicking outside (only if modal exists)
            const attachmentModal = document.getElementById('attachment-modal');
            if (attachmentModal) {
                attachmentModal.addEventListener('click', function (e) {
                    if (e.target === this) {
                        closeAttachmentModal();
                    }
                });
            }

            // Close modal with Escape key
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    const modal = document.getElementById('attachment-modal');
                    if (modal && modal.style.display === 'flex') {
                        closeAttachmentModal();
                    }
                }
            });

            document.querySelectorAll('.sidebar a').forEach(item => {
                item.addEventListener('click', function () {
                    document.querySelectorAll('.sidebar a').forEach(nav => {
                        nav.classList.remove('active');
                    });
                    this.classList.add('active');
                });
            });
            // Admin Dropdown Logic
            const adminDropdown = document.getElementById('adminDropdown');
            if (adminDropdown) {
                const adminUser = adminDropdown.querySelector('.admin-user');
                if (adminUser) {
                    adminUser.addEventListener('click', function (e) {
                        e.stopPropagation();
                        adminDropdown.classList.toggle('active');
                    });
                }

                document.addEventListener('click', function (e) {
                    if (!adminDropdown.contains(e.target)) {
                        adminDropdown.classList.remove('active');
                    }
                });

                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape') {
                        adminDropdown.classList.remove('active');
                    }
                });
            }
        </script>
</body>

</html>