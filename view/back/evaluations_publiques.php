<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Inclure les contrôleurs
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../controllers/reclamationcontroller.php';
require_once __DIR__ . '/../../controllers/SatisfactionController.php';
require_once __DIR__ . '/../../models/Reclamation.php';
require_once __DIR__ . '/../../models/Satisfaction.php';

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
    <title>FoxUnity - Évaluations Publiques</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Orbitron:wght@700&display=swap" rel="stylesheet">
    
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
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: var(--bg-dark);
            color: var(--text-light);
            line-height: 1.6;
            overflow-x: hidden;
        }
        
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar Styles */
        .sidebar {
            width: 250px;
            background: linear-gradient(135deg, rgba(20, 20, 20, 0.95) 0%, rgba(10, 10, 10, 0.95) 100%);
            border-right: 2px solid var(--border-color);
            padding: 20px 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 100;
        }
        
        .dashboard-logo {
            width: 60px;
            height: 60px;
            margin: 0 auto 20px;
            display: block;image.png
        }
        
        .sidebar h2 {
            font-family: 'Orbitron', sans-serif;
            font-size: 24px;
            text-align: center;
            margin-bottom: 30px;
            color: var(--text-light);
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .sidebar a {
            display: block;
            padding: 15px 25px;
            color: var(--text-light);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
            font-weight: 500;
            border-radius: 12px;
            margin: 5px 15px;
        }
        
        .sidebar a:hover, .sidebar a.active {
            background: linear-gradient(90deg, #ff7a00, #ff4f81);
            color: #fff;
            box-shadow: 0 0 15px rgba(255, 122, 0, 0.5);
            transform: translateX(4px);
            border-left: 3px solid transparent;
        }
        
        .sidebar a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        /* Main Content Styles */
        .main {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
        }
        
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            margin-bottom: 30px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .topbar h1 {
            font-family: 'Orbitron', sans-serif;
            font-size: 28px;
            color: var(--primary-color);
        }
        
        .topbar h1 span {
            color: var(--primary-color);
        }
        
        .user {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user img {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            border: 2px solid var(--primary-color);
        }
        
        .user span {
            font-weight: 600;
            color: var(--text-light);
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
            box-shadow: 0 8px 20px rgba(255,122,0,0.4);
        }
        
        #attachment-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.9);
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        #attachment-modal .modal-close:hover {
            background: rgba(255,60,60,0.4);
            transform: rotate(90deg);
        }
        
        #attachment-modal img {
            max-width: 100%;
            max-height: 85vh;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.5);
        }
        
        #attachment-modal video {
            max-width: 100%;
            max-height: 85vh;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.5);
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
            background: linear-gradient(180deg, rgba(4,8,20,0.6), rgba(6,14,30,0.85));
            border-top: 1px solid rgba(255, 255, 255, 0.03);
            position: relative;
            z-index: 10;
            backdrop-filter: blur(6px) saturate(110%);
            color: var(--text-gray);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.02);
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
            color: rgba(245,247,250,0.7);
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
            background: rgba(255,122,0,0.06);
            border: 1px solid rgba(255,122,0,0.12);
            border-radius: 20px;
            color: var(--primary-color) !important;
            font-weight: 700;
            cursor: pointer;
        }
        
        .back-to-top-link:hover {
            background: rgba(255,122,0,0.12);
            border-color: rgba(255,122,0,0.18);
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
            border-color: rgba(255,122,0,0.9);
            color: #000;
            transform: translateY(-3px);
        }
        
        .dashboard-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            background: rgba(255,122,0,0.06);
            border: 1px solid rgba(255,122,0,0.12);
            border-radius: 20px;
            color: var(--primary-color);
            font-weight: 700;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .dashboard-link:hover {
            background: rgba(255,122,0,0.12);
            border-color: rgba(255,122,0,0.18);
            transform: translateY(-3px);
        }
        
        .footer-bottom {
            text-align: center;
            padding: 18px 12px;
            border-top: 1px solid rgba(255, 255, 255, 0.03);
            color: rgba(245,247,250,0.6);
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
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <img src="../images/Nine__1_-removebg-preview.png" alt="Nine Tailed Fox Logo" class="dashboard-logo">
            <h2>Dashboard</h2>
            <a href="dashboard.php">Overview</a>
            <a href="#">Users</a>
            <a href="#">Shop</a>
            <a href="#">Trade History</a>
            <a href="#">Events</a>
            <a href="#">News</a>
            <a href="reclamback.php"><i class="fas fa-headset"></i> <span>Support</span></a>
            <a href="evaluations_publiques.php" class="active"><i class="fas fa-star"></i> <span>Évaluations Publiques</span></a>
            <a href="../front/indexf.html"><i class="fas fa-home"></i> <span>Homepage</span></a>
        </div>

        <!-- Main Content -->
        <div class="main">
            <div class="topbar">
                <h1>Évaluations <span>Publiques</span></h1>
                <div class="user">
                    <img src="../images/meriem.png" alt="User Avatar">
                    <span>FoxLeader</span>
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
                                <i class="fas fa-star" style="font-size: 48px; margin-bottom: 20px; display: block; opacity: 0.5;"></i>
                                Aucune évaluation publique pour le moment.
                            </p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($allSatisfactions as $satisfaction): 
                            // Récupérer la réclamation associée avec toutes ses données
                            $reclamation = $reclamationController->getReclamationById($satisfaction->getIdReclamation());
                            if (!$reclamation) continue;
                            // S'assurer que la pièce jointe est bien récupérée
                            // var_dump($reclamation); // Décommenter pour debug si nécessaire
                        ?>
                            <div class="review-card">
                                <div class="review-header">
                                    <div class="review-user">
                                        <div class="user-avatar-small"><?php echo strtoupper(substr($satisfaction->getEmail() ?? 'U', 0, 2)); ?></div>
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
                                                <i class="fas fa-star" style="color: <?php echo $i <= $rating ? '#ffc107' : '#444'; ?>; font-size: 20px;"></i>
                                            <?php endfor; ?>
                                            <span style="color: var(--text-light); font-weight: 700; font-size: 18px; margin-left: 5px;"><?php echo $rating; ?>/5</span>
                                        </div>
                                        <a href="reclamback.php?view_id=<?php echo $reclamation['id_reclamation']; ?>&ajax=1" 
                                           class="action-btn view-request" 
                                           data-id="<?php echo $reclamation['id_reclamation']; ?>"
                                           style="text-decoration: none;">
                                            <i class="fas fa-eye"></i> See the claim
                                        </a>
                                    </div>
                                </div>
                                
                                <div class="review-content">
                                    <div style="margin-bottom: 15px;">
                                        <h5 style="color: var(--primary-color); margin-bottom: 5px;">
                                            <i class="fas fa-tag"></i> <?php echo htmlspecialchars($reclamation['sujet'] ?? 'Sujet'); ?>
                                        </h5>
                                        <?php if (!empty($reclamation['categorie'])): ?>
                                            <span style="background: rgba(255, 122, 0, 0.2); color: #ff7a00; padding: 4px 12px; border-radius: 12px; font-size: 11px; font-weight: 600; border: 1px solid rgba(255, 122, 0, 0.3);">
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
                                        <div class="attachment-preview" style="margin-top: 15px; padding: 15px; background: rgba(255,122,0,0.05); border: 1px solid rgba(255,122,0,0.2); border-radius: 8px;">
                                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                                                <i class="fas fa-paperclip" style="color: #ff7a00;"></i>
                                                <strong style="color: #ff7a00;">Attachment:</strong>
                                            </div>
                                            <?php if ($isImage): ?>
                                                <img src="<?php echo htmlspecialchars($filePath); ?>" 
                                                     alt="Attachment" 
                                                     class="attachment-thumbnail"
                                                     data-type="image"
                                                     data-src="<?php echo htmlspecialchars($filePath); ?>"
                                                     style="max-width: 300px; max-height: 200px; border-radius: 8px; cursor: pointer; border: 2px solid rgba(255,122,0,0.3); transition: transform 0.3s ease;"
                                                     onclick="openAttachmentModal(this)">
                                            <?php elseif ($isVideo): ?>
                                                <video controls 
                                                       class="attachment-thumbnail"
                                                       data-type="video"
                                                       data-src="<?php echo htmlspecialchars($filePath); ?>"
                                                       style="max-width: 500px; max-height: 300px; border-radius: 8px; border: 2px solid rgba(255,122,0,0.3); cursor: pointer;"
                                                       onclick="openAttachmentModal(this)">
                                                    <source src="<?php echo htmlspecialchars($filePath); ?>" type="video/<?php echo $fileExt; ?>">
                                                    Your browser does not support the video tag.
                                                </video>
                                            <?php else: ?>
                                                <a href="<?php echo htmlspecialchars($filePath); ?>" target="_blank" style="color: #ff7a00; text-decoration: none;">
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
                                        <i class="far fa-calendar"></i> Évalué le <?php echo date('d/m/Y à H:i', strtotime($satisfaction->getDateEvaluation())); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

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
                        <a href="../front/reclamation.php">Contact Support</a>
                        <a href="#">FAQ</a>
                        <a href="#">Privacy Policy</a>
                    </div>
                    <div class="footer-section">
                        <h4>Follow Us</h4>
                        <div class="social-links">
                            <a href="#"><i class="fab fa-discord"></i></a>
                            <a href="#"><i class="fab fa-instagram"></i></a>
                            <a href="#"><i class="fab fa-youtube"></i></a>
                        </div>
                    </div>
                    <div class="footer-section">
                        <h4>Dashboard</h4>
                        <a href="dashboard.php" class="dashboard-link">
                            <i class="fas fa-tachometer-alt"></i> My Dashboard
                        </a>
                        <a href="reclamback.php" class="dashboard-link" style="margin-top: 10px; display: block;">
                            <i class="fas fa-headset"></i> Dashboard Support
                        </a>
                    </div>
                </div>
                <div class="footer-bottom">
                    <p>© 2025 FoxUnity. All rights reserved. Made with <span>♥</span> by gamers for gamers</p>
                </div>
            </footer>
        </div>
    </div>
    
    <!-- Attachment Modal -->
    <div id="attachment-modal" class="modal" style="display: none;">
        <div class="modal-content" style="max-width: 90vw; max-height: 90vh; background: rgba(10,10,10,0.98); border: 2px solid rgba(255,122,0,0.3); border-radius: 15px; padding: 20px; position: relative;">
            <button class="modal-close" onclick="closeAttachmentModal()" style="position: absolute; top: 15px; right: 15px; background: rgba(255,60,60,0.2); color: #ff3c3c; border: none; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; font-size: 20px; display: flex; align-items: center; justify-content: center; z-index: 10; transition: all 0.3s ease;">
                <i class="fas fa-times"></i>
            </button>
            <div id="attachment-modal-content" style="display: flex; align-items: center; justify-content: center; min-height: 400px;">
                <!-- Content will be inserted here -->
            </div>
            <div style="text-align: center; margin-top: 15px;">
                <a id="attachment-download-link" href="#" target="_blank" style="color: #ff7a00; text-decoration: none; font-size: 14px;">
                    <i class="fas fa-download"></i> Download
                </a>
            </div>
        </div>
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
                
                video.onerror = function() {
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
            attachmentModal.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeAttachmentModal();
                }
            });
        }
        
        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const modal = document.getElementById('attachment-modal');
                if (modal && modal.style.display === 'flex') {
                    closeAttachmentModal();
                }
            }
        });
        
        document.querySelectorAll('.sidebar a').forEach(item => {
            item.addEventListener('click', function() {
                document.querySelectorAll('.sidebar a').forEach(nav => {
                    nav.classList.remove('active');
                });
                this.classList.add('active');
            });
        });
    </script>
</body>
</html>

