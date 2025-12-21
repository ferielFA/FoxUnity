<?php
/**
 * Header commun pour toutes les pages
 * Utilisation: require_once __DIR__ . '/includes/header.php';
 * 
 * Variables à définir avant d'inclure ce fichier:
 * - $pageTitle : Titre de la page (optionnel, défaut: "FoxUnity")
 * - $activeNav : Lien actif dans la navigation (optionnel)
 */

// Définir le titre par défaut si non défini
if (!isset($pageTitle)) {
    $pageTitle = "FoxUnity";
}

// Déterminer le lien actif
$activeSupport = (isset($activeNav) && $activeNav === 'support') ? 'active' : '';
$activeNewRequest = (isset($activeNav) && $activeNav === 'new-request') ? 'active' : '';
$activePublicEval = (isset($activeNav) && $activeNav === 'public-eval') ? 'active' : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="site-header">
        <div class="header-container">
            <a href="indexf.html" class="logo">
                <img src="https://via.placeholder.com/40x40/FF7A00/FFFFFF?text=F" alt="FoxUnity Logo" class="logo-img">
                <span class="logo-text">FoxUnity</span>
            </a>
            
            <nav class="site-nav">
                <a href="indexf.html">Home</a>
                <a href="events.html">Events</a>
                <a href="shop.php">Shop</a>
                <a href="trading.html">Trading</a>
                <a href="news.html">News</a>
                <a href="reclamation.php" class="<?php echo $activeSupport; ?>">Support</a>
                <a href="contact_us.php" class="<?php echo $activeNewRequest; ?>">New Request</a>
                <a href="public_reclamations.php" class="<?php echo $activePublicEval; ?>">
                    <i class="fas fa-star"></i> Public Evaluations
                </a>
                <a href="about.html">About Us</a>
            </nav>
            
            <div class="header-right">
                <a href="profile.html" class="profile-icon">
                    <i class="fas fa-user-circle"></i>
                </a>
                <a href="panier.html" class="cart-icon">
                    <i class="fas fa-shopping-cart"></i> Cart
                    <span class="cart-count">0</span>
                </a>
            </div>
        </div>
    </header>








