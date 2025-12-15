<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
// Inclure UserController pour l'authentification
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
// Inclure le modèle et le contrôleur pour ajouter une réclamation
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../model/Reclamation.php';
require_once __DIR__ . '/../../controller/reclamationcontroller.php';

$reclamationController = new ReclamationController();
$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    try {
        // Validation des champs
        if (empty($_POST['email']) || empty($_POST['subject']) || empty($_POST['message'])) {
            $errorMessage = "Please fill in all required fields.";
        } else {
            // Gérer l'upload de fichier (photo ou vidéo)
            $pieceJointe = null;
            if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../../uploads/reclamations/';
                
                // Créer le dossier s'il n'existe pas
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $file = $_FILES['attachment'];
                $fileName = $file['name'];
                $fileTmpName = $file['tmp_name'];
                $fileSize = $file['size'];
                $fileError = $file['error'];
                $fileType = $file['type'];
                
                // Extraire l'extension
                $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                
                // Types de fichiers autorisés
                $allowedImageTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $allowedVideoTypes = ['mp4', 'mov', 'avi', 'webm', 'mkv'];
                $allowedTypes = array_merge($allowedImageTypes, $allowedVideoTypes);
                
                // Vérifier le type de fichier
                if (in_array($fileExt, $allowedTypes)) {
                    // Limite de taille : 10MB pour images, 50MB pour vidéos
                    $maxSize = in_array($fileExt, $allowedVideoTypes) ? 50 * 1024 * 1024 : 10 * 1024 * 1024;
                    
                    if ($fileSize <= $maxSize) {
                        // Générer un nom unique
                        $newFileName = uniqid('req_', true) . '.' . $fileExt;
                        $destination = $uploadDir . $newFileName;
                        
                        if (move_uploaded_file($fileTmpName, $destination)) {
                            $pieceJointe = 'uploads/reclamations/' . $newFileName;
                        } else {
                            $errorMessage = "Error uploading file. Please try again.";
                        }
                    } else {
                        $errorMessage = "File size too large. Maximum size: " . ($maxSize / (1024 * 1024)) . "MB";
                    }
                } else {
                    $errorMessage = "Invalid file type. Allowed: " . implode(', ', $allowedTypes);
                }
            }
            
            // Créer la réclamation avec les bons paramètres : email, sujet, description, id_utilisateur, statut, categorie
            // Le formulaire utilise 'subject' (catégorie) et 'message' (description)
            // Pour le sujet, on utilise le subject comme titre
            $categorie = isset($_POST['subject']) && !empty($_POST['subject']) ? trim($_POST['subject']) : 'Other';
            $sujet = isset($_POST['subject']) && !empty($_POST['subject']) ? trim($_POST['subject']) : 'Other';
            
            $reclamation = new Reclamation(
                trim($_POST['email']),
                $sujet, // sujet (titre de la réclamation)
                trim($_POST['message']), // description du formulaire
                null, // id_utilisateur (NULL si pas d'utilisateur connecté)
                'nouveau',  // statut par défaut 'nouveau' pour les nouvelles réclamations
                $categorie  // catégorie basée sur le sujet sélectionné
            );
            
            // Définir la pièce jointe si elle existe
            if ($pieceJointe) {
                $reclamation->setPieceJointe($pieceJointe);
            }

            // Vérifier que tous les champs sont valides avant l'insertion
            if (!$reclamation->getEmail() || !$reclamation->getSujet() || !$reclamation->getDescription()) {
                $errorMessage = "All fields must be filled correctly.";
            } else {
                $result = $reclamationController->addReclamation($reclamation);
                    if ($result && $result !== false && $result > 0) {
                        $successMessage = "Message sent successfully! We'll get back to you soon.";
                        $_SESSION['user_email'] = $_POST['email'];
                        // Réinitialiser les valeurs POST pour éviter la réaffichage
                        $_POST = array();
                    } else {
                        // Log serveur uniquement, message simple pour l'utilisateur
                        $debugInfo = "Résultat: " . var_export($result, true);
                        error_log('Erreur insertion reclamation (contact_us.php): ' . $debugInfo);
                        $errorMessage = "An error occurred while sending your request. Please try again later.";
                    }
            }
        }
    } catch (PDOException $e) {
        error_log('Erreur PDO contact_us.php: ' . $e->getMessage());
        error_log('Code erreur: ' . $e->getCode());
        $errorMessage = "Erreur de base de données: " . $e->getMessage();
    } catch (Exception $e) {
        error_log('Erreur contact_us.php: ' . $e->getMessage());
        $errorMessage = "Une erreur s'est produite. Veuillez réessayer plus tard.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Request - FoxUnity</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Orbitron:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header class="site-header">
        <div class="logo-section">
            <img src="../images/Nine__1_-removebg-preview.png" alt="FoxUnity Logo" class="site-logo">
            <span class="site-name">FoxUnity</span>
        </div>
        <nav class="site-nav">
            <a href="indexf.php">Home</a>
            <a href="events.php">Events</a>
            <a href="shop.php">Shop</a>
            <a href="trading.php">Trading</a>
            <a href="news.php">News</a>
            <a href="reclamation.php">Support</a>
            <a href="contact_us.php" class="active">New Request</a>
            <a href="public_reclamations.php"><i class="fas fa-star"></i> Public Evaluations</a>
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
                <span>History</span>
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
        <style>
            /* Page-specific styles for New Request (header left unchanged) */
            .site-header { position: relative; z-index: 5; }

            .support-hero {
                background: linear-gradient(135deg, rgba(34,34,34,0.06), rgba(255,122,0,0.03));
                padding: 48px 20px;
                text-align: center;
                border-bottom: 1px solid rgba(255,255,255,0.03);
                position: relative;
                overflow: hidden;
            }
            
            /* Gaming particles background */
            .support-hero::before {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-image: 
                    radial-gradient(2px 2px at 20% 30%, rgba(255,122,0,0.3), transparent),
                    radial-gradient(2px 2px at 60% 70%, rgba(255,79,0,0.2), transparent),
                    radial-gradient(1px 1px at 50% 50%, rgba(255,122,0,0.4), transparent),
                    radial-gradient(1px 1px at 80% 10%, rgba(255,79,0,0.3), transparent),
                    radial-gradient(2px 2px at 90% 80%, rgba(255,122,0,0.2), transparent);
                background-size: 200% 200%;
                animation: particleMove 20s ease infinite;
                opacity: 0.6;
                z-index: 0;
            }
            
            @keyframes particleMove {
                0%, 100% { background-position: 0% 0%, 50% 50%, 100% 100%, 0% 100%, 100% 0%; }
                50% { background-position: 100% 100%, 0% 0%, 50% 50%, 100% 0%, 0% 100%; }
            }
            
            .support-hero h1 {
                font-size: 36px;
                margin: 0 0 10px;
                font-weight: 800;
                letter-spacing: -0.5px;
                position: relative;
                z-index: 1;
                animation: titleGlow 3s ease-in-out infinite;
                text-shadow: 
                    0 0 10px rgba(255,122,0,0.5),
                    0 0 20px rgba(255,122,0,0.3),
                    0 0 30px rgba(255,122,0,0.2);
            }
            
            @keyframes titleGlow {
                0%, 100% { 
                    text-shadow: 
                        0 0 10px rgba(255,122,0,0.5),
                        0 0 20px rgba(255,122,0,0.3),
                        0 0 30px rgba(255,122,0,0.2);
                }

            /* Message character counter */
            .message-counter {
                margin-top: 6px;
                font-size: 12px;
                color: #aaaaaa;
                display: flex;
                justify-content: flex-end;
                gap: 6px;
                align-items: center;
            }

            .message-counter strong {
                color: #ffd9b8;
            }

            .message-counter.warning strong {
                color: #ffc107;
            }

            .message-counter.danger strong {
                color: #ff4f4f;
            }
                50% { 
                    text-shadow: 
                        0 0 20px rgba(255,122,0,0.8),
                        0 0 30px rgba(255,122,0,0.6),
                        0 0 40px rgba(255,122,0,0.4),
                        0 0 50px rgba(255,122,0,0.2);
                }
            }
            
            .support-hero h1 span { 
                color: #ff7a00;
                position: relative;
                display: inline-block;
                animation: glitch 4s infinite;
            }
            
            @keyframes glitch {
                0%, 90%, 100% {
                    transform: translate(0);
                    text-shadow: 
                        0 0 10px rgba(255,122,0,0.5),
                        0 0 20px rgba(255,122,0,0.3);
                }
                91% {
                    transform: translate(-2px, 2px);
                    text-shadow: 
                        -2px 2px 0 rgba(255,0,0,0.8),
                        2px -2px 0 rgba(0,255,255,0.8);
                }
                92% {
                    transform: translate(2px, -2px);
                    text-shadow: 
                        2px -2px 0 rgba(255,0,0,0.8),
                        -2px 2px 0 rgba(0,255,255,0.8);
                }
                93% {
                    transform: translate(-2px, -2px);
                }
                94% {
                    transform: translate(2px, 2px);
                }
            }
            
            .support-hero p { 
                margin: 0; 
                color: #cfcfcf; 
                font-size: 15px;
                position: relative;
                z-index: 1;
                animation: fadeInUp 1s ease-out;
            }
            
            @keyframes fadeInUp {
                from {
                    opacity: 0;
                    transform: translateY(20px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            .contact-form-section { padding: 56px 20px; }
            /* Center the contact grid horizontally and constrain width */
            .contact-container { max-width: 1300px; margin: 0 auto; display:flex; justify-content:center; }
            .contact-grid { width:100%; max-width:1100px; display: grid; grid-template-columns: 40% 60%; gap: 32px; align-items: start; margin: 0 auto; }

            .contact-card { 
                background: rgba(10,10,10,0.6); 
                padding: 32px; 
                border-radius: 12px; 
                box-shadow: 0 10px 30px rgba(0,0,0,0.6); 
                color:#fff;
                position: relative;
                overflow: hidden;
                border: 1px solid rgba(255,122,0,0.2);
                animation: cardPulse 4s ease-in-out infinite;
            }
            
            .contact-card::before {
                content: '';
                position: absolute;
                top: -50%;
                left: -50%;
                width: 200%;
                height: 200%;
                background: linear-gradient(
                    45deg,
                    transparent 30%,
                    rgba(255,122,0,0.1) 50%,
                    transparent 70%
                );
                animation: scanLine 3s linear infinite;
                z-index: 0;
            }
            
            @keyframes scanLine {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            
            @keyframes cardPulse {
                0%, 100% { 
                    border-color: rgba(255,122,0,0.2);
                    box-shadow: 0 10px 30px rgba(0,0,0,0.6), 0 0 20px rgba(255,122,0,0.1);
                }
                50% { 
                    border-color: rgba(255,122,0,0.4);
                    box-shadow: 0 10px 30px rgba(0,0,0,0.6), 0 0 30px rgba(255,122,0,0.3);
                }
            }
            
            .contact-card h3 { 
                margin-top: 0; 
                color:#fff; 
                font-size:20px;
                position: relative;
                z-index: 1;
                animation: slideInLeft 0.8s ease-out;
            }
            
            @keyframes slideInLeft {
                from {
                    opacity: 0;
                    transform: translateX(-30px);
                }
                to {
                    opacity: 1;
                    transform: translateX(0);
                }
            }
            
            .contact-list { 
                list-style:none; 
                padding:0; 
                margin: 14px 0 0;
                position: relative;
                z-index: 1;
            }
            
            .contact-list li { 
                display:flex; 
                gap:14px; 
                align-items:flex-start; 
                margin-bottom:16px; 
                color:#d0d0d0;
                transition: all 0.3s ease;
                animation: fadeInStagger 0.6s ease-out backwards;
            }
            
            .contact-list li:nth-child(1) { animation-delay: 0.1s; }
            .contact-list li:nth-child(2) { animation-delay: 0.2s; }
            .contact-list li:nth-child(3) { animation-delay: 0.3s; }
            
            @keyframes fadeInStagger {
                from {
                    opacity: 0;
                    transform: translateY(10px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            .contact-list li:hover {
                transform: translateX(5px);
                color: #ff7a00;
            }
            
            .contact-list i { 
                background: linear-gradient(135deg,#ff7a00,#ff4f00); 
                color:#fff; 
                padding:12px; 
                border-radius:8px; 
                min-width:48px; 
                text-align:center; 
                font-size:16px;
                transition: all 0.3s ease;
                box-shadow: 0 0 15px rgba(255,122,0,0.3);
                animation: iconPulse 2s ease-in-out infinite;
            }
            
            .contact-list li:hover i {
                transform: scale(1.1) rotate(5deg);
                box-shadow: 0 0 25px rgba(255,122,0,0.6);
                animation: iconSpin 0.5s ease;
            }
            
            @keyframes iconPulse {
                0%, 100% { box-shadow: 0 0 15px rgba(255,122,0,0.3); }
                50% { box-shadow: 0 0 25px rgba(255,122,0,0.6); }
            }
            
            @keyframes iconSpin {
                from { transform: scale(1.1) rotate(0deg); }
                to { transform: scale(1.1) rotate(360deg); }
            }

            .form-wrapper { 
                background: linear-gradient(180deg, rgba(255,255,255,0.02), rgba(255,255,255,0.01)); 
                padding:32px; 
                border-radius:12px;
                position: relative;
                border: 1px solid rgba(255,122,0,0.1);
                animation: formSlideIn 0.8s ease-out;
            }
            
            @keyframes formSlideIn {
                from {
                    opacity: 0;
                    transform: translateY(30px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            .form-group { 
                margin-bottom:16px;
                animation: fadeInUp 0.6s ease-out backwards;
            }
            
            .form-group:nth-child(1) { animation-delay: 0.1s; }
            .form-group:nth-child(2) { animation-delay: 0.2s; }
            .form-group:nth-child(3) { animation-delay: 0.3s; }
            .form-group:nth-child(4) { animation-delay: 0.4s; }
            .form-group:nth-child(5) { animation-delay: 0.5s; }
            
            .form-label { 
                display:block; 
                margin-bottom:8px; 
                color:#e6e6e6; 
                font-weight:600;
                transition: color 0.3s ease;
            }
            
            .form-group:focus-within .form-label {
                color: #ff7a00;
                text-shadow: 0 0 10px rgba(255,122,0,0.5);
            }
            
            .form-input, .form-textarea, .form-select { 
                width:100%; 
                padding:14px 16px; 
                border-radius:10px; 
                border:1px solid rgba(255,255,255,0.06); 
                background: rgba(255,255,255,0.02); 
                color:#fff; 
                font-size:15px;
                transition: all 0.3s ease;
            }
            
            .form-input:focus, .form-textarea:focus, .form-select:focus {
                outline: none;
                border-color: rgba(255,122,0,0.5);
                background: rgba(255,255,255,0.05);
                box-shadow: 
                    0 0 15px rgba(255,122,0,0.2),
                    inset 0 0 15px rgba(255,122,0,0.1);
                transform: translateY(-2px);
            }
            
            .form-textarea { 
                min-height:200px; 
                resize:vertical; 
                font-size:15px;
            }
            
            /* Style pour les options du select - texte noir sur fond blanc */
            .form-select option {
                background: #ffffff !important;
                color: #000000 !important;
                padding: 10px;
            }
            
            /* Pour toutes les options de select dans la page */
            select option {
                background: #ffffff !important;
                color: #000000 !important;
                padding: 8px 12px;
            }
            
            /* Options au survol */
            select option:hover {
                background: #f0f0f0 !important;
                color: #000000 !important;
            }
            
            /* Option sélectionnée */
            select option:checked {
                background: #e0e0e0 !important;
                color: #000000 !important;
            }
            .submit-btn { 
                background: linear-gradient(90deg,#ff7a00,#ff4f00); 
                color:#fff; 
                border:none; 
                padding:14px 20px; 
                border-radius:12px; 
                font-weight:800; 
                cursor:pointer; 
                box-shadow: 0 8px 20px rgba(255,122,0,0.16); 
                font-size:15px;
                position: relative;
                overflow: hidden;
                transition: all 0.3s ease;
                text-transform: uppercase;
                letter-spacing: 1px;
            }
            
            .submit-btn::before {
                content: '';
                position: absolute;
                top: 50%;
                left: 50%;
                width: 0;
                height: 0;
                border-radius: 50%;
                background: rgba(255,255,255,0.3);
                transform: translate(-50%, -50%);
                transition: width 0.6s, height 0.6s;
            }
            
            .submit-btn:hover::before {
                width: 300px;
                height: 300px;
            }
            
            .submit-btn:hover { 
                transform: translateY(-3px) scale(1.05);
                box-shadow: 
                    0 12px 30px rgba(255,122,0,0.4),
                    0 0 30px rgba(255,122,0,0.3);
                text-shadow: 0 0 10px rgba(255,255,255,0.5);
            }
            
            .submit-btn:active {
                transform: translateY(-1px) scale(1.02);
            }
            
            .submit-btn span {
                position: relative;
                z-index: 1;
            }

            /* Message styles */
            .message { display:flex; align-items:center; gap:12px; padding:12px 14px; border-radius:10px; font-weight:700; box-shadow: 0 6px 18px rgba(0,0,0,0.45); }
            .message i { font-size:18px; padding:6px; border-radius:6px; }
            .message .message-text { color: #fff; font-weight:600; }

            .success-message { background: linear-gradient(90deg, rgba(46,204,113,0.12), rgba(46,204,113,0.06)); border:1px solid rgba(46,204,113,0.25); color:#e9fff3; }
            .success-message i { background: rgba(46,204,113,1); color:#fff; padding:8px; border-radius:8px; box-shadow: 0 6px 16px rgba(46,204,113,0.16); }

            .error-message { background: linear-gradient(90deg, rgba(255,60,60,0.08), rgba(255,60,60,0.03)); border:1px solid rgba(255,60,60,0.18); color:#ffdede; }
            .error-message i { background: rgba(255,60,60,1); color:#fff; padding:8px; border-radius:8px; box-shadow: 0 6px 16px rgba(255,60,60,0.12); }

            /* Gaming-style message animations */
            .message.show{ 
                animation: slideDownFade .42s ease both, messageGlow 2s ease-in-out infinite;
            }
            
            @keyframes slideDownFade { 
                from { 
                    transform: translateY(-20px) scale(0.9); 
                    opacity:0; 
                } 
                to { 
                    transform: translateY(0) scale(1); 
                    opacity:1; 
                } 
            }
            
            @keyframes messageGlow {
                0%, 100% {
                    box-shadow: 0 6px 18px rgba(0,0,0,0.45);
                }
                50% {
                    box-shadow: 0 6px 25px rgba(0,0,0,0.6), 0 0 20px rgba(255,122,0,0.2);
                }
            }
            
            .success-message.show {
                animation: slideDownFade .42s ease both, successPulse 2s ease-in-out infinite;
            }
            
            @keyframes successPulse {
                0%, 100% {
                    box-shadow: 0 6px 18px rgba(0,0,0,0.45), 0 0 15px rgba(46,204,113,0.2);
                }
                50% {
                    box-shadow: 0 6px 25px rgba(0,0,0,0.6), 0 0 30px rgba(46,204,113,0.4);
                }
            }
            
            .error-message.show {
                animation: slideDownFade .42s ease both, errorShake 0.5s ease;
            }
            
            @keyframes errorShake {
                0%, 100% { transform: translateX(0); }
                10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
                20%, 40%, 60%, 80% { transform: translateX(5px); }
            }

            @media (max-width:900px){
                .contact-grid{ grid-template-columns: 1fr; }
                .support-hero h1{ font-size:28px; }
                .contact-card, .form-wrapper { padding:20px; }
            }
            /* User Dropdown Menu Styles */
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
    font-size: 16px;
    color: #ff7a00;
    width: 20px;
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
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 8px;
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
        </style>

        <section class="support-hero">
            <h1>New <span>Request</span></h1>
            <p>Submit a new request or reach our support team — we typically reply within 24 hours.</p>
        </section>

        <section class="contact-form-section">
            <div class="contact-container">
                <div class="contact-grid">
                    <aside class="contact-card" aria-labelledby="contact-info">
                        <h3 id="contact-info">Support Information</h3>
                        <p style="color:#d0d0d0;">Prefer other ways to reach us? Use the links below or submit the form and we'll respond quickly.</p>
                        <ul class="contact-list">
                            <li><i class="fab fa-discord"></i><div><strong>Discord</strong><br><a href="#" style="color:#ffd9b8;">Join our server</a></div></li>
                            <li><i class="fas fa-clock"></i><div><strong>Response time</strong><br>Usually within 24 hours</div></li>
                            <li><i class="fas fa-map-marker-alt"></i><div><strong>Location</strong><br>Global support team</div></li>
                        </ul>
                    </aside>

                    <div class="form-wrapper">
                        <?php if ($successMessage): ?>
                            <div class="message success-message show" role="status" aria-live="polite" style="margin-bottom:14px;">
                                <i class="fas fa-check-circle" aria-hidden="true"></i>
                                <div class="message-text"><?php echo $successMessage; ?></div>
                            </div>
                        <?php endif; ?>
                        <?php if ($errorMessage): ?>
                            <div class="message error-message show" role="alert" aria-live="assertive" style="margin-bottom:14px;">
                                <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
                                <div class="message-text"><?php echo $errorMessage; ?></div>
                            </div>
                        <?php endif; ?>

                        <div id="client-error" class="message error-message" style="display:none; margin-bottom:14px;">
                            <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
                            <div class="message-text" id="client-error-text"></div>
                        </div>

                        <form id="contact-form" method="POST" action="contact_us.php" enctype="multipart/form-data" novalidate>
                            <div class="form-group">
                                <label class="form-label">Full Name *</label>
                                <input type="text" name="full_name" class="form-input" placeholder="Enter your full name" required
                                       value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
                            </div>

                            <div class="form-group">
                                <label class="form-label">Email Address *</label>
                                <input type="email" name="email" class="form-input" placeholder="your.email@example.com" required
                                       value="<?php 
                                           echo isset($_POST['email']) 
                                               ? htmlspecialchars($_POST['email']) 
                                               : (isset($_SESSION['user_email']) ? htmlspecialchars($_SESSION['user_email']) : ''); 
                                       ?>">
                            </div>

                            <div class="form-group">
                                <label class="form-label">Subject *</label>
                                <select name="subject" class="form-select" required>
                                    <option value="">Select a subject</option>
                                    <option value="Account Issues">Account Issues</option>
                                    <option value="Payment & Billing">Payment & Billing</option>
                                    <option value="Technical Support">Technical Support</option>
                                    <option value="Shop & Orders">Shop & Orders</option>
                                    <option value="Trading Issues">Trading Issues</option>
                                    <option value="Events & Tournaments">Events & Tournaments</option>
                                    <option value="Charity & Donations">Charity & Donations</option>
                                    <option value="Feedback & Suggestions">Feedback & Suggestions</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Message *</label>
                                <textarea name="message" class="form-textarea" placeholder="Describe your issue or question in detail..." required><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                                <div id="message-counter" class="message-counter">
                                    <span>Characters:</span>
                                    <strong>0 / 1000</strong>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-camera"></i> Attachment (Photo or Video)
                                </label>
                                <input type="file" name="attachment" id="attachment" class="form-input" accept="image/*,video/*" style="padding: 10px; cursor: pointer;">
                                <small style="color: #999; font-size: 12px; display: block; margin-top: 5px;">
                                    <i class="fas fa-info-circle"></i> 
                                    Supported: Images (JPG, PNG, GIF, WEBP - max 10MB) | Videos (MP4, MOV, AVI, WEBM, MKV - max 50MB)
                                </small>
                                <div id="file-preview" style="margin-top: 10px; display: none;">
                                    <div style="background: rgba(255,122,0,0.1); border: 1px solid rgba(255,122,0,0.3); border-radius: 8px; padding: 10px; display: flex; align-items: center; gap: 10px;">
                                        <i class="fas fa-file" style="color: #ff7a00; font-size: 20px;"></i>
                                        <div style="flex: 1;">
                                            <div id="file-name" style="color: #fff; font-weight: 600;"></div>
                                            <div id="file-size" style="color: #999; font-size: 12px;"></div>
                                        </div>
                                        <button type="button" onclick="clearFile()" style="background: rgba(255,60,60,0.2); color: #ff3c3c; border: none; padding: 5px 10px; border-radius: 5px; cursor: pointer;">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="submit-btn"><span><i class="fas fa-paper-plane"></i> Send Message</span></button>
                        </form>
                    </div>
                </div>
            </div>
        </section>
        <script>
            (function(){
                const form = document.getElementById('contact-form');
                const clientError = document.getElementById('client-error');
                const clientErrorText = document.getElementById('client-error-text');

                function showClientError(msg, field){
                    clientErrorText.textContent = msg;
                    clientError.style.display = 'flex';
                    if (field) field.focus();
                }

                function hideClientError(){
                    clientErrorText.textContent = '';
                    clientError.style.display = 'none';
                }

                function validName(name){
                    // Allow letters (including accents), spaces, hyphens and apostrophes
                    return /^[A-Za-zÀ-ÖØ-öø-ÿ\s'\-]+$/.test(name.trim());
                }

                function validEmail(email){
                    if(!email) return false;
                    const basic = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    return basic.test(email.trim());
                }

                // File upload preview + validation avancée
                const fileInput = document.getElementById('attachment');
                const filePreview = document.getElementById('file-preview');
                const fileName = document.getElementById('file-name');
                const fileSize = document.getElementById('file-size');
                
                fileInput.addEventListener('change', function(e) {
                    hideClientError();
                    const file = e.target.files[0];
                    if (file) {
                        const sizeInMB = file.size / (1024 * 1024);
                        const type = file.type || '';
                        const isVideo = type.startsWith('video/');
                        const isImage = type.startsWith('image/');

                        const maxImageMB = 10;
                        const maxVideoMB = 50;

                        let maxAllowed = isVideo ? maxVideoMB : maxImageMB;

                        if (!isVideo && !isImage) {
                            showClientError('Format de fichier non supporté. Veuillez choisir une image ou une vidéo.');
                            clearFile();
                            return;
                        }

                        if (sizeInMB > maxAllowed) {
                            const limitText = isVideo ? maxVideoMB + 'MB pour les vidéos.' : maxImageMB + 'MB pour les images.';
                            showClientError('Fichier trop volumineux. Limite: ' + limitText);
                            clearFile();
                            return;
                        }

                        fileName.textContent = file.name;
                        fileSize.textContent = sizeInMB.toFixed(2) + ' MB';
                        filePreview.style.display = 'block';
                    } else {
                        filePreview.style.display = 'none';
                    }
                });
                
                function clearFile() {
                    fileInput.value = '';
                    filePreview.style.display = 'none';
                }
                
                // Make clearFile available globally
                window.clearFile = clearFile;
                
                // Compteur de caractères pour le message
                const messageElGlobal = form.querySelector('textarea[name="message"]');
                const messageCounter = document.getElementById('message-counter');
                const MESSAGE_MAX = 1000;

                function updateMessageCounter() {
                    if (!messageElGlobal || !messageCounter) return;
                    const length = (messageElGlobal.value || '').length;
                    const strongEl = messageCounter.querySelector('strong');
                    if (strongEl) {
                        strongEl.textContent = length + ' / ' + MESSAGE_MAX;
                    }

                    messageCounter.classList.remove('warning', 'danger');
                    const ratio = length / MESSAGE_MAX;
                    if (ratio >= 0.9) {
                        messageCounter.classList.add('danger');
                    } else if (ratio >= 0.7) {
                        messageCounter.classList.add('warning');
                    }
                }

                if (messageElGlobal) {
                    messageElGlobal.addEventListener('input', updateMessageCounter);
                    // Initial update on load
                    updateMessageCounter();
                }

                form.addEventListener('submit', function(e){
                    hideClientError();
                    const nameEl = form.querySelector('input[name="full_name"]');
                    const emailEl = form.querySelector('input[name="email"]');
                    const subjectEl = form.querySelector('select[name="subject"]');
                    const messageEl = form.querySelector('textarea[name="message"]');

                    const name = nameEl ? nameEl.value || '' : '';
                    const email = emailEl ? emailEl.value || '' : '';
                    const subject = subjectEl ? subjectEl.value || '' : '';
                    const message = messageEl ? messageEl.value || '' : '';

                    if(!name.trim() || !email.trim() || !subject.trim() || !message.trim()){
                        e.preventDefault();
                        showClientError('Veuillez remplir tous les champs requis.', (nameEl && !name.trim()) ? nameEl : (emailEl && !email.trim()) ? emailEl : (subjectEl && !subject.trim()) ? subjectEl : messageEl);
                        return;
                    }

                    if(!validName(name)){
                        e.preventDefault();
                        showClientError('Le nom doit contenir uniquement des lettres, espaces, tirets ou apostrophes.', nameEl);
                        return;
                    }

                    if(!validEmail(email)){
                        e.preventDefault();
                        showClientError('Veuillez entrer une adresse email valide.', emailEl);
                        return;
                    }

                    if(message.trim().length < 5){
                        e.preventDefault();
                        showClientError('Le message doit contenir au moins 5 caractères.', messageEl);
                        return;
                    }

                    if(message.length > MESSAGE_MAX){
                        e.preventDefault();
                        showClientError('Le message est trop long. Limite: ' + MESSAGE_MAX + ' caractères.', messageEl);
                        return;
                    }

                    // Animation de chargement sur le bouton d'envoi
                    const submitBtn = form.querySelector('.submit-btn');
                    if (submitBtn && !submitBtn.disabled) {
                        submitBtn.disabled = true;
                        const originalHtml = submitBtn.innerHTML;
                        submitBtn.setAttribute('data-original', originalHtml);
                        submitBtn.innerHTML = '<span><i class="fas fa-spinner fa-spin"></i> Sending...</span>';
                    }
                });

                ['input[name="full_name"]','input[name="email"]','select[name="subject"]','textarea[name="message"]'].forEach(sel => {
                    const el = form.querySelector(sel);
                    if (!el) return;
                    el.addEventListener('input', function(){ hideClientError(); });
                });

            })();
            // Dropdown Menu Toggle
document.addEventListener('DOMContentLoaded', function() {
    const userDropdown = document.getElementById('userDropdown');
    
    if (userDropdown) {
        const usernameDisplay = userDropdown.querySelector('.username-display');
        
        usernameDisplay.addEventListener('click', function(e) {
            e.stopPropagation();
            userDropdown.classList.toggle('active');
        });
        
        document.addEventListener('click', function(e) {
            if (!userDropdown.contains(e.target)) {
                userDropdown.classList.remove('active');
            }
        });
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                userDropdown.classList.remove('active');
            }
        });
    }
    
    // Code existant pour le cart...
    const cart = JSON.parse(localStorage.getItem('cart')) || [];
    const cartCount = document.querySelector('.cart-count');
    if (cartCount) {
        cartCount.textContent = cart.length;
    }
});
        </script>
    </main>

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
                <a href="reclamation.php">Contact Support</a>
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
                <a href="../back/dashboard.php" class="dashboard-link">
                    <i class="fas fa-tachometer-alt"></i> My Dashboard
                </a>
                <a href="../back/reclamback.php" class="dashboard-link" style="margin-top: 10px; display: block;">
                    <i class="fas fa-headset"></i> Dashboard Support
                </a>
            </div>
        </div>
        <div class="footer-bottom">
            <p>© 2025 FoxUnity. All rights reserved. Made with <span>♥</span> by gamers for gamers</p>
        </div>
    </footer>
</body>
</html>
