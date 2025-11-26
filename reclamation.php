<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Inclure les contrôleurs
require_once __DIR__ . '/../../controllers/ReclamationController.php';

$reclamationController = new ReclamationController();
$userReclamations = [];
$successMessage = '';
$errorMessage = '';

// If redirected after successful send or delete, show messages
if (isset($_GET['sent'])) {
    $successMessage = "Message sent successfully! We'll get back to you soon.";
}
if (isset($_GET['deleted'])) {
    $successMessage = "Request archived successfully!";
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['full_name'])) {
    $reclamation = new Reclamation(
        $_POST['full_name'],
        $_POST['email'],
        $_POST['subject'],
        $_POST['message']
    );
    
    $result = $reclamationController->addReclamation($reclamation);
    if ($result) {
        // Sauvegarder l'email en session pour récupérer les réclamations
        $_SESSION['user_email'] = $_POST['email'];
        // Redirect to avoid form resubmission and to clear POST (PRG pattern)
        $base = strtok($_SERVER['REQUEST_URI'], '?');
        header('Location: ' . $base . '?sent=1');
        exit;
    } else {
        $errorMessage = "Something went wrong. Please try again.";
    }
}

// Traitement de la suppression
if (isset($_GET['delete_id'])) {
    $result = $reclamationController->deleteReclamation($_GET['delete_id']);
    if ($result) {
        // Redirect back with a flag to show archive message
        $redirectBase = strtok($_SERVER['REQUEST_URI'], '?');
        header('Location: ' . $redirectBase . '?deleted=1');
        exit;
    } else {
        $errorMessage = "Error archiving request.";
    }
}

// Traitement pour récupérer une réclamation par ID (pour View et Edit via AJAX)
if (isset($_GET['view_id']) && isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    $selectedReclamation = $reclamationController->getReclamationById($_GET['view_id']);
    if ($selectedReclamation) {
        echo json_encode($selectedReclamation);
    } else {
        echo json_encode(['error' => 'Reclamation not found']);
    }
    exit;
}

// Traitement pour l'édition d'une réclamation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $reclamation = new Reclamation(
        $_POST['full_name'],
        $_POST['email'],
        $_POST['subject'],
        $_POST['message'],
        $_POST['statut'] ?? 'nouveau'
    );
    $reclamation->setIdReclamation($_POST['edit_id']);
    
    $result = $reclamationController->updateReclamation($reclamation);
    if ($result) {
        $successMessage = "Request updated successfully!";
        // Recharger les réclamations
        if (isset($_SESSION['user_email']) && !empty($_SESSION['user_email'])) {
            $userReclamations = $reclamationController->getReclamationsByEmail($_SESSION['user_email']);
        } elseif (isset($_POST['email']) && !empty($_POST['email'])) {
            $userReclamations = $reclamationController->getReclamationsByEmail($_POST['email']);
        }
    } else {
        $errorMessage = "Error updating request. Please try again.";
    }
}

// After handling POST actions, load user reclamations from session (if set)
if (isset($_SESSION['user_email']) && !empty($_SESSION['user_email'])) {
    $userReclamations = $reclamationController->getReclamationsByEmail($_SESSION['user_email']);
} elseif (isset($_POST['email']) && !empty($_POST['email'])) {
    $userReclamations = $reclamationController->getReclamationsByEmail($_POST['email']);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FoxUnity - Gaming for Good</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Orbitron:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        .cart-icon {
            color: #ff7a00 !important;
            position: relative;
            font-weight: 600;
            transition: all 0.3s ease;
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

        .support-hero {
            padding: 100px 40px 60px;
            text-align: center;
            background: linear-gradient(135deg, rgba(255, 122, 0, 0.1) 0%, rgba(10, 10, 10, 0.5) 100%);
        }

        .support-hero-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #ff7a00, #ff4f00);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            font-size: 50px;
            color: #fff;
            box-shadow: 0 15px 40px rgba(255, 122, 0, 0.4);
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-15px); }
        }

        .support-hero h1 {
            font-family: 'Orbitron', sans-serif;
            font-size: 48px;
            color: #fff;
            margin-bottom: 20px;
        }

        .support-hero h1 span {
            background: linear-gradient(135deg, #ff7a00, #ff4f00);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .support-hero p {
            color: #aaa;
            font-size: 18px;
            max-width: 700px;
            margin: 0 auto;
            line-height: 1.8;
        }

        .quick-links-section {
            padding: 60px 40px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .quick-links-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
        }

        .quick-link-card {
            background: linear-gradient(135deg, rgba(20, 20, 20, 0.95) 0%, rgba(10, 10, 10, 0.95) 100%);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 35px 30px;
            text-align: center;
            transition: all 0.4s ease;
            cursor: pointer;
        }

        .quick-link-card:hover {
            transform: translateY(-10px);
            border-color: rgba(255, 122, 0, 0.5);
            box-shadow: 0 15px 40px rgba(255, 122, 0, 0.3);
        }

        .quick-link-icon {
            width: 70px;
            height: 70px;
            background: rgba(255, 122, 0, 0.1);
            border: 2px solid rgba(255, 122, 0, 0.3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 32px;
            color: #ff7a00;
            transition: all 0.3s ease;
        }

        .quick-link-card:hover .quick-link-icon {
            background: rgba(255, 122, 0, 0.2);
            border-color: #ff7a00;
            transform: scale(1.1);
        }

        .quick-link-card h3 {
            font-family: 'Orbitron', sans-serif;
            font-size: 20px;
            color: #fff;
            margin-bottom: 12px;
        }

        .quick-link-card p {
            color: #aaa;
            font-size: 14px;
            line-height: 1.6;
        }

        .my-reclamations-section {
            padding: 80px 40px;
            background: linear-gradient(135deg, rgba(10, 10, 10, 0.5) 0%, rgba(255, 122, 0, 0.05) 100%);
        }

        .my-reclamations-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .section-header {
            text-align: center;
            margin-bottom: 50px;
        }

        .section-header h2 {
            font-family: 'Orbitron', sans-serif;
            font-size: 42px;
            color: #fff;
            margin-bottom: 15px;
        }

        .section-header h2 span {
            color: #ff7a00;
        }

        .section-header p {
            color: #aaa;
            font-size: 18px;
            max-width: 600px;
            margin: 0 auto;
        }

        .reclamations-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .reclamation-card {
            background: linear-gradient(135deg, rgba(20, 20, 20, 0.95) 0%, rgba(10, 10, 10, 0.95) 100%);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 25px;
            transition: all 0.3s ease;
        }

        .reclamation-card:hover {
            border-color: rgba(255, 122, 0, 0.3);
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(255, 122, 0, 0.2);
        }

        .reclamation-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .reclamation-subject {
            font-family: 'Orbitron', sans-serif;
            font-size: 18px;
            color: #fff;
            margin: 0;
        }

        .reclamation-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-nouveau, .status-new {
            background: rgba(255, 122, 0, 0.2);
            color: #ff7a00;
            border: 1px solid rgba(255, 122, 0, 0.4);
        }

        .status-en_cours, .status-in-progress {
            background: rgba(255, 193, 7, 0.2);
            color: #ffc107;
            border: 1px solid rgba(255, 193, 7, 0.4);
        }

        .status-resolu, .status-resolved {
            background: rgba(76, 175, 80, 0.2);
            color: #4caf50;
            border: 1px solid rgba(76, 175, 80, 0.4);
        }

        .reclamation-meta {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
            font-size: 14px;
            color: #aaa;
        }

        .reclamation-date {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .reclamation-message {
            color: #ccc;
            line-height: 1.6;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .reclamation-message.expanded {
            max-height: none;
        }

        .read-more {
            background: transparent;
            border: 1px solid rgba(255, 122, 0, 0.3);
            color: #ff7a00;
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-bottom: 15px;
        }

        .read-more:hover {
            background: rgba(255, 122, 0, 0.1);
            border-color: #ff7a00;
        }

        .reclamation-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .action-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
        }

        .btn-view {
            background: rgba(23, 162, 184, 0.2);
            color: #17a2b8;
            border: 1px solid rgba(23, 162, 184, 0.4);
        }

        .btn-edit {
            background: rgba(255, 193, 7, 0.2);
            color: #ffc107;
            border: 1px solid rgba(255, 193, 7, 0.4);
        }

        .btn-delete {
            background: rgba(220, 53, 69, 0.2);
            color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.4);
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 122, 0, 0.3);
        }

        .no-reclamations {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .no-reclamations i {
            font-size: 64px;
            margin-bottom: 20px;
            color: #ff7a00;
            opacity: 0.5;
        }

        .no-reclamations h3 {
            font-family: 'Orbitron', sans-serif;
            color: #fff;
            margin-bottom: 10px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            display: block;
            color: #fff;
            font-weight: 600;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .form-input,
        .form-select,
        .form-textarea {
            width: 100%;
            padding: 15px 20px;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: #fff;
            font-size: 15px;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
            box-sizing: border-box;
        }

        .form-textarea {
            min-height: 150px;
            resize: vertical;
        }

        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            outline: none;
            border-color: #ff7a00;
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 0 20px rgba(255, 122, 0, 0.2);
        }

        .form-input::placeholder,
        .form-textarea::placeholder {
            color: #666;
        }

        .form-select {
            cursor: pointer;
        }

        .form-select option {
            background: #1a1a1a;
            color: #fff;
        }

        .submit-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #ff7a00 0%, #ff4f00 100%);
            color: #fff;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(255, 122, 0, 0.4);
        }

        .submit-btn:active {
            transform: translateY(-1px);
        }

        .submit-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .message {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: none;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            font-weight: 600;
        }

        .message.show {
            display: flex;
        }

        .success-message {
            background: rgba(76, 175, 80, 0.2);
            border: 1px solid rgba(76, 175, 80, 0.4);
            color: #4caf50;
        }

        .error-message {
            background: rgba(220, 53, 69, 0.2);
            border: 1px solid rgba(220, 53, 69, 0.4);
            color: #dc3545;
        }

        .faq-section {
            padding: 80px 40px;
            max-width: 1000px;
            margin: 0 auto;
        }

        .faq-section h2 {
            font-family: 'Orbitron', sans-serif;
            font-size: 42px;
            color: #fff;
            text-align: center;
            margin-bottom: 50px;
        }

        .faq-section h2 span {
            color: #ff7a00;
        }

        .faq-item {
            background: linear-gradient(135deg, rgba(20, 20, 20, 0.95) 0%, rgba(10, 10, 10, 0.95) 100%);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            margin-bottom: 20px;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .faq-item:hover {
            border-color: rgba(255, 122, 0, 0.3);
        }

        .faq-question {
            padding: 25px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .faq-question:hover {
            background: rgba(255, 122, 0, 0.05);
        }

        .faq-question h3 {
            font-family: 'Orbitron', sans-serif;
            font-size: 18px;
            color: #fff;
            margin: 0;
        }

        .faq-icon {
            color: #ff7a00;
            transition: transform 0.3s ease;
        }

        .faq-item.active .faq-icon {
            transform: rotate(180deg);
        }

        .faq-answer {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }

        .faq-item.active .faq-answer {
            max-height: 500px;
        }

        .faq-answer-content {
            padding: 0 30px 25px;
            color: #aaa;
            line-height: 1.8;
            font-size: 15px;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: linear-gradient(135deg, rgba(20, 20, 20, 0.95) 0%, rgba(10, 10, 10, 0.95) 100%);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 40px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .modal-header h3 {
            font-family: 'Orbitron', sans-serif;
            font-size: 24px;
            color: #fff;
        }

        .modal-header h3 span {
            color: #ff7a00;
        }

        .close-modal {
            background: none;
            border: none;
            color: #aaa;
            font-size: 32px;
            cursor: pointer;
            transition: color 0.3s;
            line-height: 1;
        }

        .close-modal:hover {
            color: #ff7a00;
        }

        .modal-body {
            color: #ccc;
            line-height: 1.8;
        }

        .modal-body p {
            margin-bottom: 15px;
        }

        .modal-body strong {
            color: #fff;
        }
    </style>
</head>
<body>
    <!-- Animated red bubbles -->
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
            <a href="indexf.html">Home</a>
            <a href="events.html">Events</a>
            <a href="shop.html">Shop</a>
            <a href="trading.html">Trading</a>
            <a href="news.html">News</a>
            <a href="contactez.php" class="active">Contact</a>
            <a href="about.html">About Us</a>
        </nav>
        
        <div class="header-right">
            <a href="login.html" class="login-register-link">
                <i class="fas fa-user"></i> Login / Register
            </a>
            <a href="profile.html" class="profile-icon">
                <i class="fas fa-user-circle"></i>
            </a>
            <a href="panier.html" class="cart-icon">
                <i class="fas fa-shopping-cart"></i> Cart
                <span class="cart-count">0</span>
            </a>
        </div>
    </header>

        
        <section class="faq-section" id="faq-section">
            <h2>Frequently Asked <span>Questions</span></h2>

            <div class="faq-item">
                <div class="faq-question">
                    <h3>How does the charity donation system work?</h3>
                    <i class="fas fa-chevron-down faq-icon"></i>
                </div>
                <div class="faq-answer">
                    <div class="faq-answer-content">
                        10% of every purchase in our shop and every trade on our platform is automatically donated to verified charitable organizations. 
                        You can track your personal impact and see exactly where your contributions are going in your dashboard.
                    </div>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <h3>How do I create an account?</h3>
                    <i class="fas fa-chevron-down faq-icon"></i>
                </div>
                <div class="faq-answer">
                    <div class="faq-answer-content">
                        Click on "Login / Register" in the top right corner, then select "Sign Up". You can register using your email, 
                        or quickly sign up with Google, Discord, or Steam. It only takes a minute to join our community!
                    </div>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <h3>What payment methods do you accept?</h3>
                    <i class="fas fa-chevron-down faq-icon"></i>
                </div>
                <div class="faq-answer">
                    <div class="faq-answer-content">
                        We accept all major credit cards (Visa, Mastercard, American Express), PayPal, and various cryptocurrency options. 
                        All transactions are secure and encrypted for your protection.
                    </div>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <h3>How does the trading system work?</h3>
                    <i class="fas fa-chevron-down faq-icon"></i>
                </div>
                <div class="faq-answer">
                    <div class="faq-answer-content">
                        Our trading hub allows you to trade gaming skins at negotiable prices. Browse available items, make offers, 
                        and communicate with other traders. All trades are secure, and 10% of each transaction supports charity. 
                        You can track your trade history in your dashboard.
                    </div>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <h3>How do I participate in events and tournaments?</h3>
                    <i class="fas fa-chevron-down faq-icon"></i>
                </div>
                <div class="faq-answer">
                    <div class="faq-answer-content">
                        Visit our Events page to see all upcoming tournaments and community challenges. Click on any event to view details, 
                        rules, and prizes. Registration is usually free, and you can join solo or as a team depending on the event type.
                    </div>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <h3>What is your refund policy?</h3>
                    <i class="fas fa-chevron-down faq-icon"></i>
                </div>
                <div class="faq-answer">
                    <div class="faq-answer-content">
                        We offer refunds within 14 days of purchase for shop items that haven't been used or activated. 
                        For digital goods and gaming items, please review the specific refund terms on each product page. 
                        Contact our support team to initiate a refund request.
                    </div>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <h3>How can I track my charitable impact?</h3>
                    <i class="fas fa-chevron-down faq-icon"></i>
                </div>
                <div class="faq-answer">
                    <div class="faq-answer-content">
                        Your personal dashboard shows your total contribution to charity, which organizations benefited from your purchases, 
                        and detailed breakdowns of your impact. We provide monthly reports and regular updates on how the community's donations are being used.
                    </div>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <h3>Is my personal information secure?</h3>
                    <i class="fas fa-chevron-down faq-icon"></i>
                </div>
                <div class="faq-answer">
                    <div class="faq-answer-content">
                        Absolutely. We use industry-standard encryption and security measures to protect your personal and payment information. 
                        We never sell your data to third parties. Read our Privacy Policy for complete details on how we handle your information.
                    </div>
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
                <h4>Back to Top</h4>
                <a href="#" class="back-to-top-link" onclick="window.scrollTo({top: 0, behavior: 'smooth'}); return false;">
                    <i class="fas fa-arrow-up"></i> Scroll to Top
                </a>
            </div>
            <div class="footer-section">
                <h4>Support</h4>
                <a href="contactez.php">Contact</a>
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
                <a href="../back/dashboard.html" class="dashboard-link">
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

    <div id="view-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>View <span>Request</span></h3>
                <button class="close-modal" onclick="closeModal('view-modal')">&times;</button>
            </div>
            <div class="modal-body" id="view-modal-body">
            </div>
        </div>
    </div>

    <div id="edit-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit <span>Request</span></h3>
                <button class="close-modal" onclick="closeModal('edit-modal')">&times;</button>
            </div>
            <div class="modal-body" id="edit-modal-body">
            </div>
        </div>
    </div>

    <script>
        function toggleMessage(reclamationId, fullMessage) {
            const messageElement = document.getElementById(`message-${reclamationId}`);
            const button = messageElement.nextElementSibling;
            
            if (messageElement.classList.contains('expanded')) {
                messageElement.classList.remove('expanded');
                messageElement.textContent = fullMessage.substring(0, 100) + '...';
                button.textContent = 'Read more';
            } else {
                messageElement.classList.add('expanded');
                messageElement.textContent = fullMessage;
                button.textContent = 'Read less';
            }
        }

        function viewReclamation(id) {
            const modal = document.getElementById('view-modal');
            const modalBody = document.getElementById('view-modal-body');
            
            
            modalBody.innerHTML = '<p style="text-align: center; color: #ff7a00;"><i class="fas fa-spinner fa-spin"></i> Loading...</p>';
            modal.style.display = 'block';
            
            fetch('reclamation.php?view_id=' + id + '&ajax=1')
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        modalBody.innerHTML = '<p style="color: #f44336;">Error: ' + data.error + '</p>';
                        return;
                    }
                    
                    const date = new Date(data.date_creation);
                    const formattedDate = date.toLocaleDateString('en-US', { 
                        year: 'numeric', 
                        month: 'long', 
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                
                    const statusText = {
                        'nouveau': 'New',
                        'en_cours': 'In Progress',
                        'resolu': 'Resolved'
                    };
                    const status = statusText[data.statut] || data.statut;
                    
                    modalBody.innerHTML = `
                        <p><strong>Full Name:</strong> ${escapeHtml(data.full_name)}</p>
                        <p><strong>Email:</strong> ${escapeHtml(data.email)}</p>
                        <p><strong>Subject:</strong> ${escapeHtml(data.subject)}</p>
                        <p><strong>Status:</strong> <span class="reclamation-status status-${data.statut}">${status}</span></p>
                        <p><strong>Date:</strong> ${formattedDate}</p>
                        <p><strong>Message:</strong></p>
                        <p style="background: rgba(255,255,255,0.05); padding: 15px; border-radius: 8px; margin-top: 10px; white-space: pre-wrap;">${escapeHtml(data.message)}</p>
                    `;
                })
                .catch(error => {
                    console.error('Error:', error);
                    modalBody.innerHTML = '<p style="color: #f44336;">Error loading request details. Please try again.</p>';
                });
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function editReclamation(id) {
            const modal = document.getElementById('edit-modal');
            const modalBody = document.getElementById('edit-modal-body');
            
            modalBody.innerHTML = '<p style="text-align: center; color: #ff7a00;"><i class="fas fa-spinner fa-spin"></i> Loading...</p>';
            modal.style.display = 'block';
           
            fetch('?view_id=' + id + '&ajax=1')
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        modalBody.innerHTML = '<p style="color: #f44336;">Error: ' + data.error + '</p>';
                        return;
                    }
                    
                    modalBody.innerHTML = `
                        <form method="POST" action="">
                            <input type="hidden" name="edit_id" value="${data.id_reclamation}">
                            <div class="form-group">
                                <label class="form-label">Full Name *</label>
                                <input type="text" name="full_name" class="form-input" value="${escapeHtml(data.full_name)}" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Email Address *</label>
                                <input type="email" name="email" class="form-input" value="${escapeHtml(data.email)}" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Subject *</label>
                                <input type="text" name="subject" class="form-input" value="${escapeHtml(data.subject)}" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Status</label>
                                <select name="statut" class="form-select">
                                    <option value="nouveau" ${data.statut === 'nouveau' ? 'selected' : ''}>New</option>
                                    <option value="en_cours" ${data.statut === 'en_cours' ? 'selected' : ''}>In Progress</option>
                                    <option value="resolu" ${data.statut === 'resolu' ? 'selected' : ''}>Resolved</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Message *</label>
                                <textarea name="message" class="form-textarea" required>${escapeHtml(data.message)}</textarea>
                            </div>
                            <div style="display: flex; gap: 10px; margin-top: 20px;">
                                <button type="button" class="submit-btn" style="background: rgba(255,255,255,0.1);" onclick="closeModal('edit-modal')">Cancel</button>
                                <button type="submit" class="submit-btn">Update Request</button>
                            </div>
                        </form>
                    `;
                })
                .catch(error => {
                    console.error('Error:', error);
                    modalBody.innerHTML = '<p style="color: #f44336;">Error loading request details. Please try again.</p>';
                });
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        window.onclick = function(event) {
            const viewModal = document.getElementById('view-modal');
            const editModal = document.getElementById('edit-modal');
            if (event.target == viewModal) {
                viewModal.style.display = 'none';
            }
            if (event.target == editModal) {
                editModal.style.display = 'none';
            }
        }

        document.querySelectorAll('.faq-question').forEach(question => {
            question.addEventListener('click', () => {
                const faqItem = question.parentElement;
                const isActive = faqItem.classList.contains('active');
                
                document.querySelectorAll('.faq-item').forEach(item => {
                    item.classList.remove('active');
                });
                
                if (!isActive) {
                    faqItem.classList.add('active');
                }
            });
        });


        window.addEventListener('load', function() {
            const cart = JSON.parse(localStorage.getItem('cart')) || [];
            const cartCount = document.querySelector('.cart-count');
            if (cartCount) {
                cartCount.textContent = cart.length;
            }
        });
    </script>
</body>
</html>