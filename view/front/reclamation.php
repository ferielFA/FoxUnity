<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Inclure les contrôleurs
require_once __DIR__ . '/../../controllers/ReclamationController.php';
require_once __DIR__ . '/../../controllers/SatisfactionController.php';

$reclamationController = new ReclamationController();
$satisfactionController = new SatisfactionController();
$userReclamations = [];
$successMessage = '';
$errorMessage = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
            $reclamation = new Reclamation(
                $_POST['email'],
                $_POST['subject'],
                $_POST['message'],
                null, // id_utilisateur
                'nouveau'  // statut par défaut 'nouveau' pour les nouvelles réclamations
            );
    
    $result = $reclamationController->addReclamation($reclamation);
    if ($result) {
        $successMessage = "Message sent successfully! We'll get back to you soon.";
        // Sauvegarder l'email en session ET dans localStorage (via JavaScript)
        $_SESSION['user_email'] = $_POST['email'];
        // Recharger les réclamations
        $userReclamations = $reclamationController->getReclamationsByEmail($_POST['email']);
        
        // Ajouter un script pour sauvegarder dans localStorage et recharger
        echo '<script>
            localStorage.setItem("user_email_for_reclamations", "' . htmlspecialchars($_POST['email'], ENT_QUOTES) . '");
            // Recharger les réclamations après un court délai pour laisser le temps à la base de données
            setTimeout(function() {
                loadReclamationsByEmail("' . htmlspecialchars($_POST['email'], ENT_QUOTES) . '");
            }, 500);
        </script>';
    } else {
        $errorMessage = "Something went wrong. Please try again.";
    }
}

// Traitement de la suppression
if (isset($_GET['delete_id'])) {
    // Récupérer l'email de la réclamation avant suppression pour recharger après
    $reclamationToDelete = $reclamationController->getReclamationById($_GET['delete_id']);
    $emailToReload = $reclamationToDelete ? $reclamationToDelete['email'] : null;
    
    $result = $reclamationController->deleteReclamation($_GET['delete_id']);
    if ($result) {
        $successMessage = "Request deleted successfully!";
        
        // Si on a l'email, recharger les réclamations via AJAX (pas de redirection)
        if ($emailToReload) {
            // Sauvegarder l'email dans localStorage si pas déjà fait
            echo '<script>
                localStorage.setItem("user_email_for_reclamations", "' . htmlspecialchars($emailToReload, ENT_QUOTES) . '");
                // Recharger les réclamations après suppression
                setTimeout(function() {
                    loadReclamationsByEmail("' . htmlspecialchars($emailToReload, ENT_QUOTES) . '");
                }, 300);
            </script>';
        }
        
        // Recharger les réclamations si email en session
        if (isset($_SESSION['user_email']) && !empty($_SESSION['user_email'])) {
            $userReclamations = $reclamationController->getReclamationsByEmail($_SESSION['user_email']);
        } elseif ($emailToReload) {
            $userReclamations = $reclamationController->getReclamationsByEmail($emailToReload);
        }
    } else {
        $errorMessage = "Error deleting request.";
    }
    
    // Ne pas rediriger, laisser le JavaScript recharger les réclamations
    // Mais si JavaScript est désactivé, rediriger quand même
    if (!isset($_GET['ajax'])) {
        $redirectUrl = str_replace("?delete_id=" . $_GET['delete_id'], "", $_SERVER['REQUEST_URI']);
        header("Location: " . $redirectUrl);
        exit;
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

// Traitement pour récupérer les réclamations par email (AJAX)
if (isset($_GET['get_reclamations_by_email']) && isset($_GET['ajax']) && isset($_GET['email'])) {
    header('Content-Type: application/json');
    $email = $_GET['email'] ?? '';
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['error' => 'Email invalide']);
        exit;
    }
    
    $reclamations = $reclamationController->getReclamationsByEmail($email);
    
    // Récupérer aussi les évaluations de satisfaction pour chaque réclamation
    require_once __DIR__ . '/../../controllers/SatisfactionController.php';
    $satisfactionController = new SatisfactionController();
    
    foreach ($reclamations as &$reclamation) {
        $satisfaction = $satisfactionController->getSatisfactionByReclamationId($reclamation['id_reclamation']);
        if ($satisfaction) {
            $reclamation['satisfaction'] = [
                'rating' => $satisfaction->getRating(),
                'commentaire' => $satisfaction->getCommentaire()
            ];
        }
    }
    unset($reclamation);
    
    echo json_encode(['reclamations' => $reclamations, 'count' => count($reclamations)]);
    exit;
}

// Traitement pour l'édition d'une réclamation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $reclamation = new Reclamation(
        $_POST['email'],
        $_POST['subject'],
        $_POST['message'],
        null, // id_utilisateur
        $_POST['statut'] ?? null
    );
    $reclamation->setIdReclamation($_POST['edit_id']);
    
    $result = $reclamationController->updateReclamation($reclamation);
    if ($result) {
        // Mettre à jour la session avec le nouvel email pour recharger les réclamations
        if (isset($_POST['email']) && !empty($_POST['email'])) {
            $_SESSION['user_email'] = $_POST['email'];
        }
        // Rediriger pour éviter la resoumission et recharger correctement les données
        $base = strtok($_SERVER['REQUEST_URI'], '?');
        header('Location: ' . $base . '?updated=1');
        exit;
    } else {
        $errorMessage = "Error updating request. Please try again.";
    }
}

// Si redirection après mise à jour réussie
if (isset($_GET['updated'])) {
    $successMessage = "Request updated successfully!";
}

// Charger les réclamations après tous les traitements (pour avoir les données à jour)
// Note: Les réclamations sont maintenant chargées via AJAX depuis localStorage
// On garde ce code pour l'affichage initial si un email est fourni
$userReclamations = [];
if (isset($_SESSION['user_email']) && !empty($_SESSION['user_email'])) {
    $userReclamations = $reclamationController->getReclamationsByEmail($_SESSION['user_email']);
} elseif (isset($_POST['email']) && !empty($_POST['email']) && !isset($_POST['edit_id'])) {
    // Seulement si ce n'est pas une édition (car l'édition redirige)
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

        .contact-form-section {
            padding: 60px 40px;
            background: linear-gradient(135deg, rgba(10, 10, 10, 0.5) 0%, rgba(255, 122, 0, 0.05) 100%);
        }

        .contact-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
        }

        @media (max-width: 968px) {
            .contact-container {
                grid-template-columns: 1fr;
            }
        }

        .contact-info {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }

        .contact-info h2 {
            font-family: 'Orbitron', sans-serif;
            font-size: 36px;
            color: #fff;
            margin-bottom: 10px;
        }

        .contact-info h2 span {
            color: #ff7a00;
        }

        .contact-info-text {
            color: #aaa;
            font-size: 16px;
            line-height: 1.8;
        }

        .contact-method {
            display: flex;
            align-items: flex-start;
            gap: 20px;
            padding: 25px;
            background: rgba(255, 255, 255, 0.02);
            border-radius: 15px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: all 0.3s ease;
        }

        .contact-method:hover {
            background: rgba(255, 122, 0, 0.05);
            border-color: rgba(255, 122, 0, 0.2);
            transform: translateX(10px);
        }

        .contact-method-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #ff7a00, #ff4f00);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #fff;
            flex-shrink: 0;
        }

        .contact-method-details h4 {
            font-family: 'Orbitron', sans-serif;
            font-size: 18px;
            color: #fff;
            margin-bottom: 5px;
        }

        .contact-method-details p {
            color: #aaa;
            font-size: 14px;
        }

        .contact-method-details a {
            color: #ff7a00;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }

        .contact-method-details a:hover {
            color: #fff;
        }

        .contact-form-wrapper {
            background: linear-gradient(135deg, rgba(20, 20, 20, 0.95) 0%, rgba(10, 10, 10, 0.95) 100%);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 25px;
            padding: 50px 40px;
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

    <!-- HEADER -->
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
            <a href="reclamation.php" class="active">Support</a>
            <a href="contact_us.php">New Request</a>
            <a href="public_reclamations.php"><i class="fas fa-star"></i> Public Evaluations</a>
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

    <main class="main-section">
      
        <section class="support-hero">
            <div class="support-hero-icon">
                <i class="fas fa-headset"></i>
            </div>
            <h1>Support <span>Center</span></h1>
            <p>We're here to help! Get answers to your questions or reach out to our support team directly.</p>
        </section>

        <section class="quick-links-section">
            <div class="quick-links-grid">

                <div class="quick-link-card" onclick="document.getElementById('my-reclamations').scrollIntoView({behavior: 'smooth'})">
                    <div class="quick-link-icon">
                        <i class="fas fa-list-alt"></i>
                    </div>
                    <h3>My Requests</h3>
                    <p>View and manage your previous support requests</p>
                </div>

                <div class="quick-link-card" onclick="document.getElementById('faq-section').scrollIntoView({behavior: 'smooth'})">
                    <div class="quick-link-icon">
                        <i class="fas fa-question-circle"></i>
                    </div>
                    <h3>FAQ</h3>
                    <p>Find answers to commonly asked questions</p>
                </div>
            </div>
        </section>

        <!-- NOUVELLE SECTION : MES RÉCLAMATIONS -->
        <section class="my-reclamations-section" id="my-reclamations">
            <div class="my-reclamations-container">
                <div class="section-header">
                    <h2>My <span>Requests</span></h2>
                    <p>View and manage all your previous support requests in one place</p>
                </div>

                <!-- Recherche par email -->
                <div class="email-search-container" style="max-width: 600px; margin: 0 auto 40px; padding: 25px; background: linear-gradient(135deg, rgba(20, 20, 20, 0.95) 0%, rgba(10, 10, 10, 0.95) 100%); border: 2px solid rgba(255, 122, 0, 0.2); border-radius: 15px;">
                    <h3 style="color: #ff7a00; margin-bottom: 15px; font-family: 'Orbitron', sans-serif; text-align: center;">
                        <i class="fas fa-envelope"></i> Rechercher vos réclamations
                    </h3>
                    <p style="color: #aaa; text-align: center; margin-bottom: 20px; font-size: 14px;">
                        Entrez votre adresse email pour voir toutes vos réclamations
                    </p>
                    <div style="display: flex; gap: 10px;">
                        <input 
                            type="email" 
                            id="search-email-input" 
                            placeholder="votre@email.com" 
                            style="flex: 1; padding: 12px 15px; background: rgba(255, 255, 255, 0.05); border: 2px solid rgba(255, 122, 0, 0.3); border-radius: 8px; color: #fff; font-family: 'Poppins', sans-serif; font-size: 14px;"
                        >
                        <button 
                            id="search-email-btn" 
                            style="padding: 12px 25px; background: linear-gradient(135deg, #ff7a00, #ff4f00); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; transition: all 0.3s ease; white-space: nowrap;"
                            onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 5px 15px rgba(255, 122, 0, 0.3)'"
                            onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none'"
                        >
                            <i class="fas fa-search"></i> Rechercher
                        </button>
                    </div>
                    <div id="email-status" style="margin-top: 15px; text-align: center; font-size: 13px; color: #aaa; display: none;">
                        <i class="fas fa-info-circle"></i> <span id="email-status-text"></span>
                    </div>
                </div>

                <div class="reclamations-grid" id="reclamations-list">
                    <?php if (empty($userReclamations)): ?>
                        <div class="no-reclamations" style="grid-column: 1 / -1;">
                            <i class="fas fa-inbox"></i>
                            <h3>No Requests Yet</h3>
                            <p>Entrez votre email ci-dessus pour voir vos réclamations, ou soumettez votre première demande en utilisant le formulaire ci-dessous</p>
                        </div>
                    <?php else: ?>
                        <script>
                            // Pré-remplir le champ email si des réclamations sont chargées
                            document.addEventListener('DOMContentLoaded', function() {
                                const email = '<?php echo htmlspecialchars($_SESSION['user_email'] ?? $_POST['email'] ?? '', ENT_QUOTES); ?>';
                                if (email) {
                                    document.getElementById('search-email-input').value = email;
                                    localStorage.setItem('user_email_for_reclamations', email);
                                }
                            });
                        </script>
                        <?php foreach ($userReclamations as $reclamation): ?>
                            <div class="reclamation-card">
                                <div class="reclamation-header">
                                    <h3 class="reclamation-subject"><?php echo htmlspecialchars($reclamation['sujet'] ?? ''); ?></h3>
                                    <span class="reclamation-status status-<?php echo $reclamation['statut'] ?? 'nouveau'; ?>">
                                        <?php 
                                        $status = $reclamation['statut'] ?? 'nouveau';
                                        $statusText = [
                                            'nouveau' => 'New',
                                            'en_cours' => 'In Progress', 
                                            'resolu' => 'Resolved',
                                            'pending' => 'Pending' // Pour compatibilité
                                        ];
                                        echo $statusText[$status] ?? $status;
                                        ?>
                                    </span>
                                </div>
                                <div class="reclamation-meta">
                                    <div class="reclamation-date">
                                        <i class="far fa-calendar"></i>
                                        <?php echo date('M j, Y', strtotime($reclamation['date_creation'])); ?>
                                    </div>
                                </div>
                                <div class="reclamation-message" id="message-<?php echo $reclamation['id_reclamation']; ?>">
                                    <?php 
                                    $description = htmlspecialchars($reclamation['description'] ?? '');
                                    echo strlen($description) > 100 ? substr($description, 0, 100) . '...' : $description;
                                    ?>
                                </div>
                                <?php if (strlen($description) > 100): ?>
                                    <button class="read-more" onclick="toggleMessage(<?php echo $reclamation['id_reclamation']; ?>, '<?php echo addslashes($description); ?>')">
                                        Read more
                                    </button>
                                <?php endif; ?>
                                
                                <?php if (!empty($reclamation['piece_jointe'])): 
                                    // Utiliser une URL complète pour garantir l'accès
                                    // Le fichier est stocké comme "uploads/reclamations/filename.ext"
                                    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
                                    $filePath = $baseUrl . '/foxunity/' . ltrim($reclamation['piece_jointe'], '/');
                                    $fileExt = strtolower(pathinfo($reclamation['piece_jointe'], PATHINFO_EXTENSION));
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
                                <div class="reclamation-actions">
                                    <button class="action-btn btn-view" onclick="viewReclamation(<?php echo $reclamation['id_reclamation']; ?>)">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                    <button class="action-btn btn-edit" onclick="editReclamation(<?php echo $reclamation['id_reclamation']; ?>)">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <a href="reclamation.php?delete_id=<?php echo $reclamation['id_reclamation']; ?>" class="action-btn btn-delete" onclick="deleteReclamationWithReload(event, <?php echo $reclamation['id_reclamation']; ?>, '<?php echo htmlspecialchars($reclamation['email'] ?? '', ENT_QUOTES); ?>'); return false;">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </div>
                                
                                <?php
                                // Afficher l'enquête de satisfaction pour les réclamations résolues
                                if ($status === 'resolu') {
                                    $satisfaction = $satisfactionController->getSatisfactionByReclamationId($reclamation['id_reclamation']);
                                    if (!$satisfaction) {
                                        // Afficher le formulaire d'évaluation
                                        ?>
                                        <div class="satisfaction-survey" id="satisfaction-<?php echo $reclamation['id_reclamation']; ?>" style="margin-top: 20px; padding: 20px; background: rgba(255, 122, 0, 0.05); border: 2px solid rgba(255, 122, 0, 0.2); border-radius: 15px;">
                                            <h4 style="color: #ff7a00; margin-bottom: 15px; font-family: 'Orbitron', sans-serif;">
                                                <i class="fas fa-star"></i> Comment avez-vous trouvé notre service ?
                                            </h4>
                                            <div class="star-rating" data-reclamation-id="<?php echo $reclamation['id_reclamation']; ?>" data-email="<?php echo htmlspecialchars($reclamation['email'] ?? ''); ?>">
                                                <div class="stars-container" style="display: flex; gap: 10px; margin-bottom: 15px; justify-content: center;">
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <i class="far fa-star star-icon" data-rating="<?php echo $i; ?>" style="font-size: 32px; color: #ffc107; cursor: pointer; transition: all 0.2s ease;"></i>
                                                    <?php endfor; ?>
                                                </div>
                                                <input type="hidden" name="rating" id="rating-<?php echo $reclamation['id_reclamation']; ?>" value="0">
                                                <textarea 
                                                    id="comment-<?php echo $reclamation['id_reclamation']; ?>" 
                                                    placeholder="Votre commentaire (optionnel)..." 
                                                    style="width: 100%; padding: 12px; background: rgba(255, 255, 255, 0.05); border: 2px solid rgba(255, 122, 0, 0.3); border-radius: 8px; color: #fff; font-family: 'Poppins', sans-serif; resize: vertical; min-height: 80px; margin-bottom: 15px;"></textarea>
                                                <button 
                                                    class="btn-submit-satisfaction" 
                                                    data-reclamation-id="<?php echo $reclamation['id_reclamation']; ?>"
                                                    style="padding: 12px 25px; background: linear-gradient(135deg, #ff7a00, #ff4f00); color: white; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; transition: all 0.3s ease; width: 100%;">
                                                    <i class="fas fa-paper-plane"></i> Envoyer l'évaluation
                                                </button>
                                            </div>
                                        </div>
                                        <?php
                                    } else {
                                        // Afficher l'évaluation déjà soumise
                                        ?>
                                        <div class="satisfaction-completed" style="margin-top: 20px; padding: 15px; background: rgba(76, 175, 80, 0.1); border: 2px solid rgba(76, 175, 80, 0.3); border-radius: 15px;">
                                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                                                <i class="fas fa-check-circle" style="color: #4caf50; font-size: 20px;"></i>
                                                <strong style="color: #4caf50;">Merci pour votre évaluation !</strong>
                                            </div>
                                            <div style="display: flex; align-items: center; gap: 5px; margin-bottom: 10px;">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <i class="<?php echo $i <= $satisfaction->getRating() ? 'fas' : 'far'; ?> fa-star" style="color: #ffc107; font-size: 18px;"></i>
                                                <?php endfor; ?>
                                                <span style="margin-left: 10px; color: #fff; font-weight: 600;"><?php echo $satisfaction->getRating(); ?>/5</span>
                                            </div>
                                            <?php if ($satisfaction->getCommentaire()): ?>
                                                <p style="color: #aaa; font-style: italic; margin: 0;">"<?php echo htmlspecialchars($satisfaction->getCommentaire()); ?>"</p>
                                            <?php endif; ?>
                                        </div>
                                        <?php
                                    }
                                }
                                ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        

        
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

    <!-- Modal pour View -->
    <div id="view-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>View <span>Request</span></h3>
                <button class="close-modal" onclick="closeModal('view-modal')">&times;</button>
            </div>
            <div class="modal-body" id="view-modal-body">
                <!-- Le contenu sera chargé via JavaScript -->
            </div>
        </div>
    </div>

    <!-- Modal pour Edit -->
    <div id="edit-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit <span>Request</span></h3>
                <button class="close-modal" onclick="closeModal('edit-modal')">&times;</button>
            </div>
            <div class="modal-body" id="edit-modal-body">
                <!-- Le formulaire d'édition sera chargé via JavaScript -->
            </div>
        </div>
    </div>

    <script>
        // Fonction pour basculer l'affichage du message complet
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

        // Fonctions pour View et Edit avec données de la base
        function viewReclamation(id) {
            const modal = document.getElementById('view-modal');
            const modalBody = document.getElementById('view-modal-body');
            
            // Afficher un loader
            modalBody.innerHTML = '<p style="text-align: center; color: #ff7a00;"><i class="fas fa-spinner fa-spin"></i> Loading...</p>';
            modal.style.display = 'block';
            
            // Récupérer les données via AJAX depuis la base de données
            fetch('reclamation.php?view_id=' + id + '&ajax=1')
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        modalBody.innerHTML = '<p style="color: #f44336;">Error: ' + data.error + '</p>';
                        return;
                    }
                    
                    // Formater la date
                    const date = new Date(data.date_creation);
                    const formattedDate = date.toLocaleDateString('en-US', { 
                        year: 'numeric', 
                        month: 'long', 
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                    
                    // Formater le statut
                    const statusText = {
                        'nouveau': 'New',
                        'en_cours': 'In Progress',
                        'resolu': 'Resolved'
                    };
                    const status = statusText[data.statut] || data.statut;
                    
                    modalBody.innerHTML = `
                        <p><strong>Email:</strong> ${escapeHtml(data.email || '')}</p>
                        <p><strong>Subject:</strong> ${escapeHtml(data.sujet || '')}</p>
                        <p><strong>Status:</strong> <span class="reclamation-status status-${data.statut || 'pending'}">${status}</span></p>
                        <p><strong>Date:</strong> ${formattedDate}</p>
                        <p><strong>Description:</strong></p>
                        <p style="background: rgba(255,255,255,0.05); padding: 15px; border-radius: 8px; margin-top: 10px; white-space: pre-wrap;">${escapeHtml(data.description || '')}</p>
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
            
            // Afficher un loader
            modalBody.innerHTML = '<p style="text-align: center; color: #ff7a00;"><i class="fas fa-spinner fa-spin"></i> Loading...</p>';
            modal.style.display = 'block';
            
            // Récupérer les données complètes depuis la base de données via AJAX
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
                                <label class="form-label">Email Address *</label>
                                <input type="email" name="email" class="form-input" value="${escapeHtml(data.email || '')}" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Subject *</label>
                                <input type="text" name="subject" class="form-input" value="${escapeHtml(data.sujet || '')}" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Status</label>
                                <select name="statut" class="form-select">
                                    <option value="pending" ${(data.statut || 'pending') === 'pending' ? 'selected' : ''}>Pending</option>
                                    <option value="nouveau" ${data.statut === 'nouveau' ? 'selected' : ''}>New</option>
                                    <option value="en_cours" ${data.statut === 'en_cours' ? 'selected' : ''}>In Progress</option>
                                    <option value="resolu" ${data.statut === 'resolu' ? 'selected' : ''}>Resolved</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Description *</label>
                                <textarea name="message" class="form-textarea" required>${escapeHtml(data.description || '')}</textarea>
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
        
        // Close modal when clicking outside
        document.addEventListener('DOMContentLoaded', function() {
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
        });
        
        // Fermer les modals en cliquant en dehors
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

        // Code existant pour les FAQ
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

        // Code existant pour le panier
        window.addEventListener('load', function() {
            const cart = JSON.parse(localStorage.getItem('cart')) || [];
            const cartCount = document.querySelector('.cart-count');
            if (cartCount) {
                cartCount.textContent = cart.length;
            }

            // Charger automatiquement les réclamations si l'email est dans localStorage
            const savedEmail = localStorage.getItem('user_email_for_reclamations');
            if (savedEmail) {
                document.getElementById('search-email-input').value = savedEmail;
                loadReclamationsByEmail(savedEmail);
            } else if (document.getElementById('search-email-input').value) {
                // Si un email est déjà dans le champ (depuis PHP), le sauvegarder
                const email = document.getElementById('search-email-input').value;
                if (email) {
                    localStorage.setItem('user_email_for_reclamations', email);
                }
            }
        });

        // Fonction pour charger les réclamations par email via AJAX
        function loadReclamationsByEmail(email) {
            if (!email || !email.includes('@')) {
                showEmailStatus('Veuillez entrer une adresse email valide', 'error');
                return;
            }

            // Sauvegarder l'email dans localStorage
            localStorage.setItem('user_email_for_reclamations', email);
            
            // Afficher le chargement
            const reclamationsList = document.getElementById('reclamations-list');
            reclamationsList.innerHTML = `
                <div style="grid-column: 1 / -1; text-align: center; padding: 40px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 48px; color: #ff7a00; margin-bottom: 20px;"></i>
                    <p style="color: #aaa;">Chargement de vos réclamations...</p>
                </div>
            `;

            // Requête AJAX pour charger les réclamations
            fetch(`reclamation.php?get_reclamations_by_email=1&email=${encodeURIComponent(email)}&ajax=1`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        reclamationsList.innerHTML = `
                            <div class="no-reclamations" style="grid-column: 1 / -1;">
                                <i class="fas fa-exclamation-triangle" style="color: #ff7a00;"></i>
                                <h3>Erreur</h3>
                                <p>${data.error}</p>
                            </div>
                        `;
                        showEmailStatus('Erreur lors du chargement', 'error');
                    } else if (data.reclamations && data.reclamations.length > 0) {
                        displayReclamations(data.reclamations);
                        showEmailStatus(`Chargement réussi : ${data.reclamations.length} réclamation(s) trouvée(s)`, 'success');
                    } else {
                        reclamationsList.innerHTML = `
                            <div class="no-reclamations" style="grid-column: 1 / -1;">
                                <i class="fas fa-inbox"></i>
                                <h3>Aucune réclamation</h3>
                                <p>Aucune réclamation trouvée pour cet email</p>
                            </div>
                        `;
                        showEmailStatus('Aucune réclamation trouvée pour cet email', 'info');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    reclamationsList.innerHTML = `
                        <div class="no-reclamations" style="grid-column: 1 / -1;">
                            <i class="fas fa-exclamation-triangle" style="color: #ff7a00;"></i>
                            <h3>Erreur de connexion</h3>
                            <p>Impossible de charger les réclamations. Veuillez réessayer.</p>
                        </div>
                    `;
                    showEmailStatus('Erreur de connexion', 'error');
                });
        }

        // Fonction pour afficher les réclamations
        function displayReclamations(reclamations) {
            const reclamationsList = document.getElementById('reclamations-list');
            
            if (reclamations.length === 0) {
                reclamationsList.innerHTML = `
                    <div class="no-reclamations" style="grid-column: 1 / -1;">
                        <i class="fas fa-inbox"></i>
                        <h3>Aucune réclamation</h3>
                        <p>Aucune réclamation trouvée</p>
                    </div>
                `;
                return;
            }

            let html = '';
            reclamations.forEach(reclamation => {
                const status = reclamation.statut || 'nouveau';
                const statusText = {
                    'nouveau': 'New',
                    'en_cours': 'In Progress',
                    'resolu': 'Resolved',
                    'pending': 'Pending'
                };
                const statusLabel = statusText[status] || status;
                const date = new Date(reclamation.date_creation).toLocaleDateString('fr-FR', { year: 'numeric', month: 'short', day: 'numeric' });
                const description = reclamation.description || '';
                const shortDescription = description.length > 100 ? description.substring(0, 100) + '...' : description;

                html += `
                    <div class="reclamation-card">
                        <div class="reclamation-header">
                            <h3 class="reclamation-subject">${escapeHtml(reclamation.sujet || '')}</h3>
                            <span class="reclamation-status status-${status}">${statusLabel}</span>
                        </div>
                        <div class="reclamation-meta">
                            <div class="reclamation-date">
                                <i class="far fa-calendar"></i> ${date}
                            </div>
                        </div>
                        <div class="reclamation-message" id="message-${reclamation.id_reclamation}">
                            ${escapeHtml(shortDescription)}
                        </div>
                        ${description.length > 100 ? `<button class="read-more" onclick="toggleMessage(${reclamation.id_reclamation}, '${escapeHtml(description).replace(/'/g, "\\'")}')">Read more</button>` : ''}
                        <div class="reclamation-actions">
                            <button class="action-btn btn-view" onclick="viewReclamation(${reclamation.id_reclamation})">
                                <i class="fas fa-eye"></i> View
                            </button>
                            <button class="action-btn btn-edit" onclick="editReclamation(${reclamation.id_reclamation})">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <a href="reclamation.php?delete_id=${reclamation.id_reclamation}" class="action-btn btn-delete" onclick="deleteReclamationWithReload(event, ${reclamation.id_reclamation}, '${escapeHtml(reclamation.email || '')}'); return false;">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                        </div>
                    </div>
                `;
            });
            
            reclamationsList.innerHTML = html;
        }

        // Fonction pour afficher le statut de l'email
        function showEmailStatus(message, type) {
            const statusDiv = document.getElementById('email-status');
            const statusText = document.getElementById('email-status-text');
            
            statusDiv.style.display = 'block';
            statusText.textContent = message;
            
            // Couleurs selon le type
            if (type === 'success') {
                statusDiv.style.color = '#4caf50';
            } else if (type === 'error') {
                statusDiv.style.color = '#f44336';
            } else {
                statusDiv.style.color = '#ff7a00';
            }
            
            // Masquer après 5 secondes pour les messages de succès
            if (type === 'success') {
                setTimeout(() => {
                    statusDiv.style.display = 'none';
                }, 5000);
            }
        }

        // Fonction pour échapper le HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Bouton de recherche
        document.getElementById('search-email-btn').addEventListener('click', function() {
            const email = document.getElementById('search-email-input').value.trim();
            if (email) {
                loadReclamationsByEmail(email);
            } else {
                showEmailStatus('Veuillez entrer une adresse email', 'error');
            }
        });

        // Recherche au clavier (Enter)
        document.getElementById('search-email-input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const email = this.value.trim();
                if (email) {
                    loadReclamationsByEmail(email);
                }
            }
        });

        // Fonction pour supprimer une réclamation et recharger automatiquement
        function deleteReclamationWithReload(event, reclamationId, email) {
            event.preventDefault();
            
            if (!confirm('Are you sure you want to delete this request?')) {
                return false;
            }
            
            // Supprimer via AJAX
            fetch(`reclamation.php?delete_id=${reclamationId}&ajax=1`)
                .then(response => response.text())
                .then(data => {
                    // Recharger les réclamations après suppression
                    if (email) {
                        loadReclamationsByEmail(email);
                    } else {
                        // Si pas d'email, recharger depuis localStorage
                        const savedEmail = localStorage.getItem('user_email_for_reclamations');
                        if (savedEmail) {
                            loadReclamationsByEmail(savedEmail);
                        }
                    }
                    
                    // Afficher un message de succès
                    showEmailStatus('Réclamation supprimée avec succès', 'success');
                })
                .catch(error => {
                    console.error('Error:', error);
                    showEmailStatus('Erreur lors de la suppression', 'error');
                });
            
            return false;
        }

        // Système d'évaluation de satisfaction
        document.querySelectorAll('.star-icon').forEach(star => {
            star.addEventListener('mouseenter', function() {
                const rating = parseInt(this.getAttribute('data-rating'));
                const container = this.closest('.star-rating');
                const stars = container.querySelectorAll('.star-icon');
                stars.forEach((s, index) => {
                    if (index < rating) {
                        s.classList.remove('far');
                        s.classList.add('fas');
                    } else {
                        s.classList.remove('fas');
                        s.classList.add('far');
                    }
                });
            });
        });

        document.querySelectorAll('.star-rating').forEach(ratingContainer => {
            ratingContainer.addEventListener('mouseleave', function() {
                const hiddenInput = this.querySelector('input[type="hidden"]');
                const currentRating = parseInt(hiddenInput.value) || 0;
                const stars = this.querySelectorAll('.star-icon');
                stars.forEach((star, index) => {
                    if (index < currentRating) {
                        star.classList.remove('far');
                        star.classList.add('fas');
                    } else {
                        star.classList.remove('fas');
                        star.classList.add('far');
                    }
                });
            });
        });

        document.querySelectorAll('.star-icon').forEach(star => {
            star.addEventListener('click', function() {
                const rating = parseInt(this.getAttribute('data-rating'));
                const container = this.closest('.star-rating');
                const hiddenInput = container.querySelector('input[type="hidden"]');
                hiddenInput.value = rating;
                
                const stars = container.querySelectorAll('.star-icon');
                stars.forEach((s, index) => {
                    if (index < rating) {
                        s.classList.remove('far');
                        s.classList.add('fas');
                        s.style.transform = 'scale(1.1)';
                        setTimeout(() => s.style.transform = 'scale(1)', 200);
                    } else {
                        s.classList.remove('fas');
                        s.classList.add('far');
                    }
                });
            });
        });

        // Soumettre l'évaluation
        document.querySelectorAll('.btn-submit-satisfaction').forEach(button => {
            button.addEventListener('click', function() {
                const reclamationId = this.getAttribute('data-reclamation-id');
                const ratingContainer = this.closest('.star-rating');
                const email = ratingContainer.getAttribute('data-email');
                const rating = parseInt(ratingContainer.querySelector('input[type="hidden"]').value);
                const commentaire = ratingContainer.querySelector('textarea').value.trim();
                
                if (rating === 0) {
                    alert('Veuillez sélectionner une note (1-5 étoiles)');
                    return;
                }
                
                // Désactiver le bouton
                this.disabled = true;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Envoi en cours...';
                
                // Envoyer la requête AJAX
                const formData = new FormData();
                formData.append('action', 'submit_satisfaction');
                formData.append('id_reclamation', reclamationId);
                formData.append('email', email);
                formData.append('rating', rating);
                formData.append('commentaire', commentaire);
                
                fetch('../back/reclamback.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Afficher un message de succès
                        const surveyDiv = document.getElementById('satisfaction-' + reclamationId);
                        surveyDiv.innerHTML = `
                            <div style="padding: 15px; background: rgba(76, 175, 80, 0.1); border: 2px solid rgba(76, 175, 80, 0.3); border-radius: 15px;">
                                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                                    <i class="fas fa-check-circle" style="color: #4caf50; font-size: 20px;"></i>
                                    <strong style="color: #4caf50;">Merci pour votre évaluation !</strong>
                                </div>
                                <div style="display: flex; align-items: center; gap: 5px; margin-bottom: 10px;">
                                    ${Array.from({length: 5}, (_, i) => 
                                        `<i class="${i < rating ? 'fas' : 'far'} fa-star" style="color: #ffc107; font-size: 18px;"></i>`
                                    ).join('')}
                                    <span style="margin-left: 10px; color: #fff; font-weight: 600;">${rating}/5</span>
                                </div>
                                ${commentaire ? `<p style="color: #aaa; font-style: italic; margin: 0;">"${commentaire}"</p>` : ''}
                            </div>
                        `;
                        
                        // Notification de succès
                        const notification = document.createElement('div');
                        notification.style.cssText = 'position: fixed; top: 20px; right: 20px; background: rgba(76, 175, 80, 0.9); color: white; padding: 15px 20px; border-radius: 10px; z-index: 10000; box-shadow: 0 4px 15px rgba(0,0,0,0.3);';
                        notification.innerHTML = '<i class="fas fa-check-circle"></i> Évaluation enregistrée avec succès !';
                        document.body.appendChild(notification);
                        setTimeout(() => notification.remove(), 3000);
                    } else {
                        alert('Erreur: ' + (data.message || 'Impossible d\'enregistrer l\'évaluation'));
                        this.disabled = false;
                        this.innerHTML = '<i class="fas fa-paper-plane"></i> Envoyer l\'évaluation';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Une erreur est survenue. Veuillez réessayer.');
                    this.disabled = false;
                    this.innerHTML = '<i class="fas fa-paper-plane"></i> Envoyer l\'évaluation';
                });
            });
        });
    </script>
    
    <!-- Attachment Modal -->
    <div id="attachment-modal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 10000; align-items: center; justify-content: center;">
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
    
    <style>
        .attachment-thumbnail:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 20px rgba(255,122,0,0.4);
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
    </style>
</body>
</html>