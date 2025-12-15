<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Inclure UserController pour l'authentification
require_once __DIR__ . '/../../controller/UserController.php';

// Inclure les contrôleurs de réclamation
require_once __DIR__ . '/../../controller/ReclamationController.php';
require_once __DIR__ . '/../../controller/ResponseController.php';

$isLoggedIn = UserController::isLoggedIn();
$currentUser = null;

if ($isLoggedIn) {
    $currentUser = UserController::getCurrentUser();
}

$userImage = null;
if ($currentUser && $currentUser->getImage()) {
    $userImage = '../../view/' . $currentUser->getImage();
}

$reclamationController = new ReclamationController();
$responseController = new ResponseController();
$userReclamations = [];
$successMessage = '';
$errorMessage = '';

// Récupérer les réclamations si l'utilisateur a soumis un formulaire ou après l'envoi
if (isset($_POST['email']) && !empty($_POST['email'])) {
    $userReclamations = $reclamationController->getReclamationsByEmail($_POST['email']);
} elseif (isset($_SESSION['user_email']) && !empty($_SESSION['user_email'])) {
    $userReclamations = $reclamationController->getReclamationsByEmail($_SESSION['user_email']);
}

// Récupérer les réponses pour chaque réclamation
foreach ($userReclamations as &$reclamation) {
    $reclamation['responses'] = $responseController->getResponsesByReclamationId($reclamation['id_reclamation']);
}
unset($reclamation);

// Traitement de la suppression
if (isset($_GET['delete_id'])) {
    $result = $reclamationController->deleteReclamation($_GET['delete_id']);
    if ($result) {
        $_SESSION['success_message'] = "Request deleted successfully!";
    } else {
        $_SESSION['error_message'] = "Error deleting request.";
    }
    header("Location: reclamation.php");
    exit;
}

// Récupérer les messages de session
if (isset($_SESSION['success_message'])) {
    $successMessage = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    $errorMessage = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Traitement pour récupérer une réclamation par ID (pour View et Edit via AJAX)
if (isset($_GET['view_id']) && isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    $selectedReclamation = $reclamationController->getReclamationById($_GET['view_id']);
    if ($selectedReclamation) {
        $selectedReclamation['responses'] = $responseController->getResponsesByReclamationId($_GET['view_id']);
        echo json_encode($selectedReclamation);
    } else {
        echo json_encode(['error' => 'Reclamation not found']);
    }
    exit;
}

// Traitement pour l'édition d'une réclamation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $reclamation = new Reclamation(
        $_POST['email'] ?? '',
        $_POST['sujet'] ?? $_POST['subject'] ?? '',
        $_POST['description'] ?? $_POST['message'] ?? '',
        null,
        $_POST['statut'] ?? 'nouveau',
        'Other'
    );
    $reclamation->setIdReclamation($_POST['edit_id']);
    
    $result = $reclamationController->updateReclamation($reclamation);
    if ($result) {
        $_SESSION['success_message'] = "Request updated successfully!";
        $_SESSION['user_email'] = $_POST['email'];
    } else {
        $_SESSION['error_message'] = "Error updating request. Please try again.";
    }
    header("Location: reclamation.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FoxUnity - Support Center</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Orbitron:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
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
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            transition: all 0.3s ease;
        }

        .faq-question:hover {
            background: rgba(255, 122, 0, 0.05);
        }

        .faq-question h3 {
            font-family: 'Poppins', sans-serif;
            font-size: 18px;
            font-weight: 600;
            color: #fff;
            margin: 0;
        }

        .faq-icon {
            font-size: 20px;
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

        .message {
            display: none;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            align-items: center;
            gap: 10px;
        }

        .message.show {
            display: flex;
        }

        .success-message {
            background: rgba(76, 175, 80, 0.1);
            border: 2px solid #4caf50;
            color: #4caf50;
        }

        .error-message {
            background: rgba(244, 67, 54, 0.1);
            border: 2px solid #f44336;
            color: #f44336;
        }

        .my-reclamations-section {
            padding: 60px 40px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .my-reclamations-container {
            width: 100%;
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
            font-size: 16px;
        }

        .reclamations-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 25px;
        }

        .reclamation-card {
            background: linear-gradient(135deg, rgba(20, 20, 20, 0.95) 0%, rgba(10, 10, 10, 0.95) 100%);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 30px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .reclamation-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(180deg, #ff7a00, #ff4f00);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .reclamation-card:hover {
            transform: translateY(-5px);
            border-color: rgba(255, 122, 0, 0.3);
            box-shadow: 0 15px 40px rgba(255, 122, 0, 0.15);
        }

        .reclamation-card:hover::before {
            opacity: 1;
        }

        .reclamation-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            gap: 20px;
        }

        .reclamation-subject {
            font-family: 'Poppins', sans-serif;
            font-size: 22px;
            font-weight: 700;
            color: #fff;
            margin: 0;
            flex: 1;
            line-height: 1.4;
        }

        .reclamation-status {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }

        .reclamation-status.status-nouveau,
        .reclamation-status.status-New {
            background: linear-gradient(135deg, rgba(33, 150, 243, 0.2), rgba(33, 150, 243, 0.1));
            color: #2196F3;
            border: 1px solid rgba(33, 150, 243, 0.3);
        }

        .reclamation-status.status-en_cours,
        .reclamation-status.status-In\ Progress {
            background: linear-gradient(135deg, rgba(255, 193, 7, 0.2), rgba(255, 193, 7, 0.1));
            color: #FFC107;
            border: 1px solid rgba(255, 193, 7, 0.3);
        }

        .reclamation-status.status-resolu,
        .reclamation-status.status-Resolved {
            background: linear-gradient(135deg, rgba(76, 175, 80, 0.2), rgba(76, 175, 80, 0.1));
            color: #4CAF50;
            border: 1px solid rgba(76, 175, 80, 0.3);
        }

        .reclamation-meta {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .reclamation-date {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #aaa;
            font-size: 14px;
        }

        .reclamation-date i {
            color: #ff7a00;
        }

        .reclamation-message {
            color: #ccc;
            font-size: 15px;
            line-height: 1.8;
            margin-bottom: 20px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 10px;
            border-left: 3px solid rgba(255, 122, 0, 0.3);
        }

        .reclamation-message.expanded {
            white-space: pre-wrap;
        }

        .read-more {
            background: transparent;
            border: 2px solid rgba(255, 122, 0, 0.3);
            color: #ff7a00;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }

        .read-more:hover {
            background: rgba(255, 122, 0, 0.1);
            border-color: #ff7a00;
            transform: translateX(5px);
        }

        .reclamation-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .action-btn {
            padding: 10px 20px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            border: 2px solid transparent;
        }

        .action-btn i {
            font-size: 16px;
        }

        .btn-view {
            background: rgba(33, 150, 243, 0.1);
            color: #2196F3;
            border-color: rgba(33, 150, 243, 0.3);
        }

        .btn-view:hover {
            background: rgba(33, 150, 243, 0.2);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(33, 150, 243, 0.3);
        }

        .btn-edit {
            background: rgba(255, 193, 7, 0.1);
            color: #FFC107;
            border-color: rgba(255, 193, 7, 0.3);
        }

        .btn-edit:hover {
            background: rgba(255, 193, 7, 0.2);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 193, 7, 0.3);
        }

        .btn-delete {
            background: rgba(244, 67, 54, 0.1);
            color: #f44336;
            border-color: rgba(244, 67, 54, 0.3);
        }

        .btn-delete:hover {
            background: rgba(244, 67, 54, 0.2);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(244, 67, 54, 0.3);
        }

        .no-reclamations {
            text-align: center;
            padding: 80px 20px;
            background: linear-gradient(135deg, rgba(20, 20, 20, 0.95) 0%, rgba(10, 10, 10, 0.95) 100%);
            border: 2px dashed rgba(255, 255, 255, 0.1);
            border-radius: 20px;
        }

        .no-reclamations i {
            font-size: 64px;
            color: #ff7a00;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .no-reclamations h3 {
            font-family: 'Orbitron', sans-serif;
            font-size: 24px;
            color: #fff;
            margin-bottom: 10px;
        }

        .no-reclamations p {
            color: #aaa;
            font-size: 16px;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(5px);
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background: linear-gradient(135deg, rgba(20, 20, 20, 0.98) 0%, rgba(10, 10, 10, 0.98) 100%);
            margin: 5% auto;
            border: 2px solid rgba(255, 122, 0, 0.3);
            border-radius: 20px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 20px 60px rgba(255, 122, 0, 0.3);
            animation: slideDown 0.3s ease;
            max-height: 90vh;
            overflow-y: auto;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 25px 30px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
        }

        .modal-header h3 {
            font-family: 'Orbitron', sans-serif;
            font-size: 24px;
            color: #fff;
            margin: 0;
        }

        .modal-header h3 span {
            color: #ff7a00;
        }

        .close-modal {
            background: transparent;
            border: none;
            color: #fff;
            font-size: 32px;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .close-modal:hover {
            background: rgba(255, 122, 0, 0.2);
            color: #ff7a00;
            transform: rotate(90deg);
        }

        .modal-body {
            padding: 30px;
            color: #fff;
        }

        .modal-body p {
            margin-bottom: 15px;
            line-height: 1.8;
            color: #ccc;
        }

        .modal-body strong {
            color: #fff;
            font-weight: 600;
        }

        .modal-body .form-group {
            margin-bottom: 20px;
        }

        .modal-body .form-label {
            display: block;
            color: #fff;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .modal-body .form-input,
        .modal-body .form-select,
        .modal-body .form-textarea {
            width: 100%;
            padding: 12px 15px;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            color: #fff;
            font-size: 14px;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
        }

        .modal-body .form-textarea {
            min-height: 120px;
            resize: vertical;
        }

        .modal-body .form-input:focus,
        .modal-body .form-select:focus,
        .modal-body .form-textarea:focus {
            outline: none;
            border-color: #ff7a00;
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 0 15px rgba(255, 122, 0, 0.2);
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
       
        @media (max-width: 968px) {
            .support-hero h1 {
                font-size: 36px;
            }

            .quick-links-grid {
                grid-template-columns: 1fr;
            }

            .reclamation-card {
                padding: 20px;
            }

            .reclamation-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .reclamation-subject {
                font-size: 18px;
            }

            .reclamation-actions {
                flex-direction: column;
            }

            .action-btn {
                width: 100%;
                justify-content: center;
            }
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
            <a href="events.php">Events</a>
            <a href="shop.html">Shop</a>
            <a href="trading.php">Trading</a>
            <a href="news.php">News</a>
            <a href="reclamation.php" class="active">Support</a>
            <a href="contact_us.php">New Request</a>
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

        <section class="my-reclamations-section" id="my-reclamations">
            <div class="my-reclamations-container">
                <div class="section-header">
                    <h2>My <span>Requests</span></h2>
                    <p>View and manage all your previous support requests in one place</p>
                </div>

                <?php if ($successMessage): ?>
                    <div class="message success-message show" style="margin-bottom: 30px;">
                        <i class="fas fa-check-circle"></i>
                        <span><?php echo htmlspecialchars($successMessage); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($errorMessage): ?>
                    <div class="message error-message show" style="margin-bottom: 30px;">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo htmlspecialchars($errorMessage); ?></span>
                    </div>
                <?php endif; ?>

                <div style="background: linear-gradient(135deg, rgba(20, 20, 20, 0.95) 0%, rgba(10, 10, 10, 0.95) 100%); border: 2px solid rgba(255, 255, 255, 0.1); border-radius: 15px; padding: 25px; margin-bottom: 30px;">
                    <form method="POST" action="" style="display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap;">
                        <div style="flex: 1; min-width: 250px;">
                            <label class="form-label">Enter your email to view your requests</label>
                            <input type="email" name="email" class="form-input" placeholder="your.email@example.com" 
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : (isset($_SESSION['user_email']) ? htmlspecialchars($_SESSION['user_email']) : ''); ?>" required>
                        </div>
                        <button type="submit" class="submit-btn" style="width: auto; padding: 12px 30px;">
                            <i class="fas fa-search"></i> View Requests
                        </button>
                    </form>
                </div>

                <div class="reclamations-grid" id="reclamations-list">
                    <?php if (empty($userReclamations)): ?>
                        <div class="no-reclamations">
                            <i class="fas fa-inbox"></i>
                            <h3>No Requests Yet</h3>
                            <p>You haven't submitted any support requests yet. <a href="contact_us.php" style="color: #ff7a00; text-decoration: none; font-weight: 600;">Create your first request</a> to get started.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($userReclamations as $reclamation): ?>
                            <div class="reclamation-card">
                                <div class="reclamation-header">
                                    <h3 class="reclamation-subject"><?php echo htmlspecialchars($reclamation['sujet'] ?? $reclamation['subject'] ?? 'No Subject'); ?></h3>
                                    <span class="reclamation-status status-<?php echo $reclamation['statut']; ?>">
                                        <?php 
                                        $statusText = [
                                            'nouveau' => 'New',
                                            'en_cours' => 'In Progress', 
                                            'resolu' => 'Resolved'
                                        ];
                                        echo $statusText[$reclamation['statut']] ?? $reclamation['statut'];
                                        ?>
                                    </span>
                                </div>
                                <div class="reclamation-meta">
                                    <div class="reclamation-date">
                                        <i class="far fa-calendar"></i>
                                        <?php echo date('M j, Y', strtotime($reclamation['date_creation'])); ?>
                                    </div>
                                    <?php if (!empty($reclamation['responses']) && count($reclamation['responses']) > 0): ?>
                                        <div style="display: flex; align-items: center; gap: 5px; color: #ff7a00; font-weight: 600;">
                                            <i class="fas fa-reply"></i>
                                            <span><?php echo count($reclamation['responses']); ?> réponse<?php echo count($reclamation['responses']) > 1 ? 's' : ''; ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="reclamation-message" id="message-<?php echo $reclamation['id_reclamation']; ?>">
                                    <?php 
                                    $message = htmlspecialchars($reclamation['description'] ?? $reclamation['message'] ?? 'No message');
                                    echo strlen($message) > 100 ? substr($message, 0, 100) . '...' : $message;
                                    ?>
                                </div>
                                <?php if (strlen($message) > 100): ?>
                                    <button class="read-more" onclick="toggleMessage(<?php echo $reclamation['id_reclamation']; ?>, '<?php echo addslashes($message); ?>')">
                                        Read more
                                    </button>
                                <?php endif; ?>
                                <div class="reclamation-actions">
                                    <button class="action-btn btn-view" onclick="viewReclamation(<?php echo $reclamation['id_reclamation']; ?>)">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                    <button class="action-btn btn-edit" onclick="editReclamation(<?php echo $reclamation['id_reclamation']; ?>)">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <a href="reclamation.php?delete_id=<?php echo $reclamation['id_reclamation']; ?>" class="action-btn btn-delete" onclick="return confirm('Are you sure you want to delete this request?')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </div>
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
            
            const cart = JSON.parse(localStorage.getItem('cart')) || [];
            const cartCount = document.querySelector('.cart-count');
            if (cartCount) {
                cartCount.textContent = cart.length;
            }
        });

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
                    
                    let responsesHtml = '';
                    if (data.responses && Array.isArray(data.responses) && data.responses.length > 0) {
                        responsesHtml = '<div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid rgba(255, 122, 0, 0.3);">';
                        responsesHtml += '<h4 style="color: #ff7a00; margin-bottom: 15px;"><i class="fas fa-reply"></i> Réponse de l\'équipe (' + data.responses.length + ')</h4>';
                        
                        data.responses.forEach(function(response) {
                            const responseDate = new Date(response.date_reponse || response.date_creation || new Date());
                            const formattedResponseDate = responseDate.toLocaleDateString('fr-FR', { 
                                year: 'numeric', 
                                month: 'long', 
                                day: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit'
                            });
                            
                            responsesHtml += '<div style="background: rgba(255, 122, 0, 0.1); border-left: 3px solid #ff7a00; padding: 15px; margin-bottom: 15px; border-radius: 8px;">';
                            responsesHtml += '<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">';
                            responsesHtml += '<span style="color: #ff7a00; font-weight: 600;"><i class="fas fa-user-shield"></i> Admin</span>';
                            responsesHtml += '<span style="color: #aaa; font-size: 12px;">' + formattedResponseDate + '</span>';
                            responsesHtml += '</div>';
                            responsesHtml += '<div style="color: #fff; line-height: 1.6; white-space: pre-wrap;">' + escapeHtml(response.message || response.response_text || '') + '</div>';
                            responsesHtml += '</div>';
                        });
                        
                        responsesHtml += '</div>';
                    }
                    
                    modalBody.innerHTML = `
                        <p><strong>Email:</strong> ${escapeHtml(data.email)}</p>
                        <p><strong>Subject:</strong> ${escapeHtml(data.sujet || data.subject || 'No Subject')}</p>
                        <p><strong>Status:</strong> <span class="reclamation-status status-${data.statut}">${status}</span></p>
                        <p><strong>Date:</strong> ${formattedDate}</p>
                        <p><strong>Message:</strong></p>
                        <p style="background: rgba(255,255,255,0.05); padding: 15px; border-radius: 8px; margin-top: 10px; white-space: pre-wrap;">${escapeHtml(data.description || data.message || 'No message')}</p>
                        ${responsesHtml}
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
            
            fetch('reclamation.php?view_id=' + id + '&ajax=1')
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
                                <input type="email" name="email" class="form-input" value="${escapeHtml(data.email)}" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Subject *</label>
                                <input type="text" name="sujet" class="form-input" value="${escapeHtml(data.sujet || data.subject || '')}" required>
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
                                <textarea name="description" class="form-textarea" required>${escapeHtml(data.description || data.message || '')}</textarea>
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
    </script>
</body>
</html>