<?php
require_once __DIR__ . '/../../controller/EvenementController.php';
require_once __DIR__ . '/../../controller/ParticipationController.php';

$eventController = new EvenementController();
$participationController = new ParticipationController();

$message = '';
$showParticipationForm = false;
$showCreateEventForm = false;
$selectedEvent = null;
$showMyEvents = isset($_GET['view']) && $_GET['view'] === 'my';
$currentUserEmail = isset($_GET['email']) ? htmlspecialchars($_GET['email']) : '';

// Handle create event form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Log all POST data
    error_log("=== POST REQUEST RECEIVED ===");
    error_log("POST data: " . print_r($_POST, true));
    
    if ($_POST['action'] === 'create_event') {
        $evenement = new Evenement(
            null,
            htmlspecialchars($_POST['titre']),
            htmlspecialchars($_POST['description']),
            new DateTime($_POST['date_debut']),
            new DateTime($_POST['date_fin']),
            htmlspecialchars($_POST['lieu']),
            htmlspecialchars($_POST['createur_email']),
            'upcoming'
        );
        
        if ($eventController->creer($evenement)) {
            $message = '<div class="alert success"><i class="fas fa-check-circle"></i> Event created successfully!</div>';
            header("Location: events.php?view=my&email=" . urlencode($_POST['createur_email']) . "&created=1");
            exit;
        } else {
            $message = '<div class="alert error"><i class="fas fa-exclamation-circle"></i> Event already exists or error creating event.</div>';
        }
    } elseif ($_POST['action'] === 'participate') {
        // Debug logging
        error_log("=== PARTICIPATION DEBUG ===");
        error_log("Event ID: " . $_POST['id_evenement']);
        error_log("Nom: " . $_POST['nom_participant']);
        error_log("Email: " . $_POST['email_participant']);
        
        // Check if already registered BEFORE creating object
        $isAlreadyRegistered = $participationController->verifierInscription(
            htmlspecialchars($_POST['email_participant']), 
            (int)$_POST['id_evenement']
        );
        error_log("Already registered? " . ($isAlreadyRegistered ? "YES" : "NO"));
        
        if ($isAlreadyRegistered) {
            $message = '<div class="alert error"><i class="fas fa-exclamation-circle"></i> You are already registered for this event!</div>';
        } else {
            $participation = new Participation(
                null,
                (int)$_POST['id_evenement'],
                htmlspecialchars($_POST['nom_participant']),
                htmlspecialchars($_POST['email_participant']),
                new DateTime()
            );
            
            $result = $participationController->inscrire($participation);
            error_log("Inscrire result: " . ($result ? "TRUE" : "FALSE"));
            
            if ($result) {
                $message = '<div class="alert success"><i class="fas fa-check-circle"></i> Registration confirmed! Welcome aboard!</div>';
                // Redirect to prevent form resubmission
                header("Location: events.php?success=1");
                exit;
            } else {
                $message = '<div class="alert error"><i class="fas fa-exclamation-circle"></i> Error occurred during registration.</div>';
            }
        }
    }
}

// Handle "Join Event" button click
if (isset($_GET['join']) && is_numeric($_GET['join'])) {
    $selectedEvent = $eventController->lireParId((int)$_GET['join']);
    $showParticipationForm = $selectedEvent !== null;
}

// Handle "Create Event" button click
if (isset($_GET['create'])) {
    $showCreateEventForm = true;
}

// Get events based on filter
if ($showMyEvents && !empty($currentUserEmail)) {
    $evenements = $eventController->lireParCreateur($currentUserEmail);
} else {
    $evenements = $eventController->lireTous();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FoxUnity - Events</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Orbitron:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin: 20px auto;
            max-width: 900px;
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

        /* Participation Form Modal */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .participation-modal {
            background: linear-gradient(135deg, #16161a, #1b1b20);
            border-radius: 20px;
            padding: 40px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0,0,0,0.6);
            animation: slideUp 0.4s ease;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(50px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .modal-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .modal-header h2 {
            font-family: 'Orbitron', sans-serif;
            color: #f5c242;
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .modal-header .event-name {
            color: #fff;
            font-size: 1.3rem;
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 24px;
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

        .form-group input[type="datetime-local"] {
            color-scheme: dark;
        }

        .form-group textarea {
            width: 100%;
            padding: 14px 18px;
            background: rgba(255,255,255,0.05);
            border: 2px solid rgba(255,255,255,0.1);
            border-radius: 10px;
            color: #fff;
            font-size: 1rem;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s ease;
            resize: vertical;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #f5c242;
            background: rgba(245,194,66,0.05);
        }

        .form-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .btn-submit {
            flex: 1;
            background: linear-gradient(135deg, #f5c242, #f39c12);
            color: #000;
            font-weight: 700;
            padding: 14px;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(245,194,66,0.4);
        }

        .btn-cancel {
            flex: 1;
            background: transparent;
            color: #fff;
            font-weight: 600;
            padding: 14px;
            border-radius: 10px;
            border: 2px solid rgba(255,255,255,0.2);
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .btn-cancel:hover {
            border-color: rgba(255,255,255,0.4);
            background: rgba(255,255,255,0.05);
        }

        .events-section {
            padding: 48px 24px;
            background: linear-gradient(180deg, #0f0f11 0%, #111216 100%);
            color: #fff;
            min-height: 100vh;
        }

        .events-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .events-header h2 {
            font-family: 'Orbitron', sans-serif;
            font-size: 3rem;
            letter-spacing: 2px;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #f5c242, #f39c12);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .events-header p {
            color: #cfd3d8;
            font-size: 1.1rem;
            margin-bottom: 20px;
        }

        .btn-create-event {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 14px 28px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 700;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }

        .btn-create-event:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.5);
        }

        .btn-view-all {
            background: linear-gradient(135deg, #f5c242, #f39c12);
            color: #000;
            padding: 14px 28px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 700;
            font-size: 1rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 15px rgba(245, 194, 66, 0.3);
        }

        .btn-view-all:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(245, 194, 66, 0.5);
        }

        .events-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .event-card {
            background: linear-gradient(135deg, #16161a, #1b1b20);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 8px 30px rgba(0,0,0,0.6);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
        }

        .event-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(245,194,66,0.3);
        }

        .event-title {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 8px;
            color: #f5c242;
        }

        .event-meta {
            font-size: 0.9rem;
            color: #cfd3d8;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .event-description {
            color: #d7d9dd;
            font-size: 0.95rem;
            margin: 15px 0;
            line-height: 1.6;
        }

        .event-card-content {
            padding: 20px;
        }

        .event-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background: rgba(0,0,0,0.3);
            border-top: 1px solid rgba(255,255,255,0.1);
        }

        .participants-count {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #ffd9a8;
            font-weight: 700;
        }

        .btn-join {
            background: linear-gradient(135deg, #f5c242, #f39c12);
            color: #000;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 700;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-join:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(245,194,66,0.4);
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .badge-upcoming { background: #3b82f6; color: #fff; }
        .badge-ongoing { background: #10b981; color: #fff; }
        .badge-completed { background: #6b7280; color: #fff; }
        .badge-cancelled { background: #ef4444; color: #fff; }

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

        .form-group.error input,
        .form-group.error textarea {
            border-color: #ff6b6b !important;
            background: rgba(255, 107, 107, 0.1) !important;
        }

        .form-group.success input,
        .form-group.success textarea {
            border-color: #10b981 !important;
        }

        /* Language Toggle Button */
        .lang-toggle {
            background: linear-gradient(135deg, rgba(245, 194, 66, 0.2), rgba(243, 156, 18, 0.2));
            border: 2px solid rgba(245, 194, 66, 0.4);
            border-radius: 8px;
            padding: 6px 14px;
            color: #f5c242;
            font-family: 'Orbitron', sans-serif;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s ease;
            font-size: 0.85rem;
        }

        .lang-toggle:hover {
            background: linear-gradient(135deg, rgba(245, 194, 66, 0.3), rgba(243, 156, 18, 0.3));
            border-color: #f5c242;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(245, 194, 66, 0.3);
        }

        .lang-toggle i {
            font-size: 1.1rem;
        }

        #currentLang {
            font-size: 0.85rem;
        }

        /* Pagination Styles */
        .pagination-container {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 15px;
            margin-top: 40px;
            padding: 20px;
        }

        .pagination-info {
            color: #cfd3d8;
            font-size: 1rem;
            font-weight: 600;
        }

        .btn-pagination {
            background: linear-gradient(135deg, #f5c242, #f39c12);
            color: #000;
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 700;
            font-size: 1rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(245, 194, 66, 0.3);
        }

        .btn-pagination:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(245, 194, 66, 0.5);
        }

        .btn-pagination:disabled {
            opacity: 0.4;
            cursor: not-allowed;
            transform: none;
        }

        .btn-pagination.btn-prev {
            background: linear-gradient(135deg, #6b7280, #4b5563);
        }

        .btn-pagination.btn-prev:hover:not(:disabled) {
            box-shadow: 0 6px 20px rgba(107, 114, 128, 0.5);
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
            <a href="index.php" data-lang-en="Home" data-lang-fr="Accueil">Home</a>
            <a href="events.php" class="active" data-lang-en="Events" data-lang-fr="Événements">Events</a>
            <a href="shop.html" data-lang-en="Shop" data-lang-fr="Boutique">Shop</a>
            <a href="trading.html" data-lang-en="Trading" data-lang-fr="Échange">Trading</a>
            <a href="news.html" data-lang-en="News" data-lang-fr="Actualités">News</a>
            <a href="reclamation.html" data-lang-en="Support" data-lang-fr="Support">Support</a>
            <a href="about.html" data-lang-en="About Us" data-lang-fr="À Propos">About Us</a>
        </nav>
        
        <div class="header-right">
            <button id="langToggle" class="lang-toggle" onclick="toggleLanguage()">
                <i class="fas fa-language"></i>
                <span id="currentLang">FR</span>
            </button>
            <a href="login.html" class="login-register-link" data-lang-en="Login / Register" data-lang-fr="Connexion / S'inscrire">
                <i class="fas fa-user"></i> <span>Login / Register</span>
            </a>
        </div>
    </header>

    <main class="main-section">
        <?php if ($message): echo $message; endif; ?>

        <?php if ($showCreateEventForm): ?>
        <div class="modal-overlay" id="createEventModal">
            <div class="participation-modal">
                <div class="modal-header">
                    <h2 data-lang-en="Create New Event" data-lang-fr="Créer un Nouvel Événement"><i class="fas fa-calendar-plus"></i> <span>Create New Event</span></h2>
                    <p style="color: #cfd3d8; font-size: 0.95rem;" data-lang-en="Fill in the details below to create an event" data-lang-fr="Remplissez les détails ci-dessous pour créer un événement">Fill in the details below to create an event</p>
                </div>

                <form method="POST" action="" id="createEventForm" novalidate>
                    <input type="hidden" name="action" value="create_event">
                    
                    <div class="form-group">
                        <label for="titre" data-lang-en="Event Title *" data-lang-fr="Titre de l'Événement *">Event Title *</label>
                        <input type="text" id="titre" name="titre" placeholder="Enter event title" data-lang-en="Enter event title" data-lang-fr="Entrez le titre de l'événement">
                        <div class="error-message" id="error-titre"></div>
                    </div>

                    <div class="form-group">
                        <label for="description" data-lang-en="Description *" data-lang-fr="Description *">Description *</label>
                        <textarea id="description" name="description" placeholder="Describe your event" data-lang-en="Describe your event" data-lang-fr="Décrivez votre événement" rows="4" style="width:100%; padding:14px 18px; background:rgba(255,255,255,0.05); border:2px solid rgba(255,255,255,0.1); border-radius:10px; color:#fff; font-size:1rem; font-family:'Poppins',sans-serif; resize:vertical;"></textarea>
                        <div class="error-message" id="error-description"></div>
                    </div>

                    <div class="form-group">
                        <label for="date_debut" data-lang-en="Start Date & Time *" data-lang-fr="Date & Heure de Début *">Start Date & Time *</label>
                        <input type="datetime-local" id="date_debut" name="date_debut">
                        <div class="error-message" id="error-date_debut"></div>
                    </div>

                    <div class="form-group">
                        <label for="date_fin" data-lang-en="End Date & Time *" data-lang-fr="Date & Heure de Fin *">End Date & Time *</label>
                        <input type="datetime-local" id="date_fin" name="date_fin">
                        <div class="error-message" id="error-date_fin"></div>
                    </div>

                    <div class="form-group">
                        <label for="lieu" data-lang-en="Location *" data-lang-fr="Lieu *">Location *</label>
                        <input type="text" id="lieu" name="lieu" placeholder="Event location" data-lang-en="Event location" data-lang-fr="Lieu de l'événement">
                        <div class="error-message" id="error-lieu"></div>
                    </div>

                    <div class="form-group">
                        <label for="createur_email" data-lang-en="Your Email (Creator) *" data-lang-fr="Votre Email (Créateur) *">Your Email (Creator) *</label>
                        <input type="email" id="createur_email" name="createur_email" placeholder="your.email@example.com" data-lang-en="your.email@example.com" data-lang-fr="votre.email@exemple.com">
                        <div class="error-message" id="error-createur_email"></div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-submit" data-lang-en="Create Event" data-lang-fr="Créer l'Événement">
                            <i class="fas fa-check-circle"></i> <span>Create Event</span>
                        </button>
                        <a href="events.php" class="btn-cancel" style="text-decoration:none; display:flex; align-items:center; justify-content:center;" data-lang-en="Cancel" data-lang-fr="Annuler">
                            <i class="fas fa-times-circle"></i> <span>Cancel</span>
                        </a>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($showParticipationForm && $selectedEvent): ?>
        <div class="modal-overlay" id="participationModal">
            <div class="participation-modal">
                <div class="modal-header">
                    <h2 data-lang-en="Join Event" data-lang-fr="Rejoindre l'Événement"><i class="fas fa-ticket-alt"></i> <span>Join Event</span></h2>
                    <div class="event-name"><?= htmlspecialchars($selectedEvent->getTitre()) ?></div>
                </div>

                <form method="POST" action="" id="participationForm" novalidate>
                    <input type="hidden" name="action" value="participate">
                    <input type="hidden" name="id_evenement" value="<?= $selectedEvent->getIdEvenement() ?>">
                    
                    <div class="form-group">
                        <label for="nom_participant" data-lang-en="Your Name *" data-lang-fr="Votre Nom *">Your Name *</label>
                        <input type="text" id="nom_participant" name="nom_participant" placeholder="Enter your full name" data-lang-en="Enter your full name" data-lang-fr="Entrez votre nom complet">
                        <div class="error-message" id="error-nom_participant"></div>
                    </div>

                    <div class="form-group">
                        <label for="email_participant" data-lang-en="Your Email *" data-lang-fr="Votre Email *">Your Email *</label>
                        <input type="text" id="email_participant" name="email_participant" placeholder="your.email@example.com" data-lang-en="your.email@example.com" data-lang-fr="votre.email@exemple.com">
                        <div class="error-message" id="error-email_participant"></div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-submit" data-lang-en="Confirm Registration" data-lang-fr="Confirmer l'Inscription">
                            <i class="fas fa-check-circle"></i> <span>Confirm Registration</span>
                        </button>
                        <a href="events.php" class="btn-cancel" style="text-decoration:none; display:flex; align-items:center; justify-content:center;" data-lang-en="Cancel" data-lang-fr="Annuler">
                            <i class="fas fa-times-circle"></i> <span>Cancel</span>
                        </a>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <section class="events-section">
            <div class="events-header">
                <h2 data-lang-en="<?= $showMyEvents ? 'My Events' : 'Upcoming Events' ?>" data-lang-fr="<?= $showMyEvents ? 'Mes Événements' : 'Événements À Venir' ?>"><?= $showMyEvents ? 'My Events' : 'Upcoming Events' ?></h2>
                <p data-lang-en="<?= $showMyEvents ? 'Events created by you' : 'Join exciting gaming events and tournaments' ?>" data-lang-fr="<?= $showMyEvents ? 'Événements créés par vous' : 'Rejoignez des événements gaming passionnants' ?>"><?= $showMyEvents ? 'Events created by you' : 'Join exciting gaming events and tournaments' ?></p>
                <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap; margin-top: 20px;">
                    <a href="?create=1" class="btn-create-event" data-lang-en="Create New Event" data-lang-fr="Créer un Nouvel Événement">
                        <i class="fas fa-plus-circle"></i> <span>Create New Event</span>
                    </a>
                    <a href="my_tickets.php" class="btn-create-event" style="background: linear-gradient(135deg, #2ed573, #1abc9c);" data-lang-en="My Tickets" data-lang-fr="Mes Tickets">
                        <i class="fas fa-ticket-alt"></i> <span>My Tickets</span>
                    </a>
                    <?php if ($showMyEvents): ?>
                        <a href="events.php" class="btn-view-all" data-lang-en="Show All Upcoming" data-lang-fr="Afficher Tous les Événements">
                            <i class="fas fa-filter"></i> <span>Show All Upcoming</span>
                        </a>
                    <?php else: ?>
                        <a href="#" onclick="showMyEvents(); return false;" class="btn-view-all" data-lang-en="My Events" data-lang-fr="Mes Événements">
                            <i class="fas fa-user-circle"></i> <span>My Events</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="events-container" id="eventsContainer">
                <?php 
                $displayedEvents = $showMyEvents ? $evenements : array_filter($evenements, function($item) {
                    return $item['evenement']->getStatut() === 'upcoming';
                });
                
                if (empty($displayedEvents)): ?>
                    <div style="grid-column: 1/-1; text-align: center; padding: 60px 20px; color: #969696;">
                        <i class="fas fa-calendar-times" style="font-size: 4rem; margin-bottom: 20px; opacity: 0.3;"></i>
                        <p style="font-size: 1.2rem;" data-lang-en="No events found." data-lang-fr="Aucun événement trouvé.">No events found.</p>
                    </div>
                <?php else: ?>
                    <?php 
                    $eventIndex = 0;
                    foreach ($displayedEvents as $item):
                        $event = $item['evenement'];
                        $nbParticipants = $item['nb_participants'];
                        $statuts = [
                            'upcoming' => 'À venir',
                            'ongoing' => 'En cours',
                            'completed' => 'Terminé',
                            'cancelled' => 'Annulé'
                        ];
                        $eventIndex++;
                    ?>
                <div class="event-card event-item" data-event-index="<?= $eventIndex ?>" style="cursor: pointer; <?= $eventIndex > 3 ? 'display: none;' : '' ?>" onclick="window.location.href='event_details.php?id=<?= $event->getIdEvenement() ?>'">
                    <div class="event-card-content">
                        <span class="badge badge-<?= $event->getStatut() ?>">
                            <?= $statuts[$event->getStatut()] ?>
                        </span>
                        <div class="event-title"><?= htmlspecialchars($event->getTitre()) ?></div>
                        <div class="event-meta">
                            <i class="fas fa-calendar"></i>
                            <?= $event->getDateDebut()->format('M d, Y - H:i') ?>
                        </div>
                        <div class="event-meta">
                            <i class="fas fa-map-marker-alt"></i>
                            <?= htmlspecialchars($event->getLieu()) ?>
                        </div>
                        <div class="event-description">
                            <?= htmlspecialchars($event->getDescription()) ?>
                        </div>
                    </div>
                    <div class="event-footer">
                        <div class="participants-count">
                            <i class="fas fa-users"></i>
                            <?= $nbParticipants ?> <span data-lang-en="participants" data-lang-fr="participants">participants</span>
                        </div>
                        <?php if ($event->getStatut() === 'upcoming'): ?>
                            <a href="?join=<?= $event->getIdEvenement() ?>" class="btn-join" onclick="event.stopPropagation();" data-lang-en="Join Event" data-lang-fr="Rejoindre">
                                <i class="fas fa-user-plus"></i> <span>Join Event</span>
                            </a>
                        <?php else: ?>
                            <button class="btn-join" disabled style="opacity:0.5;cursor:not-allowed" onclick="event.stopPropagation();" data-lang-en="Unavailable" data-lang-fr="Indisponible">
                                <i class="fas fa-ban"></i> <span>Unavailable</span>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if (!empty($displayedEvents) && count($displayedEvents) > 3): ?>
            <div class="pagination-container">
                <button class="btn-pagination btn-prev" id="btnPrev" onclick="previousPage()" disabled>
                    <i class="fas fa-chevron-left"></i>
                    <span data-lang-en="Previous" data-lang-fr="Précédent">Previous</span>
                </button>
                
                <div class="pagination-info">
                    <span data-lang-en="Showing" data-lang-fr="Affichage">Showing</span>
                    <span id="currentRange">1-3</span>
                    <span data-lang-en="of" data-lang-fr="sur">of</span>
                    <span id="totalEvents"><?= count($displayedEvents) ?></span>
                </div>
                
                <button class="btn-pagination btn-next" id="btnNext" onclick="nextPage()">
                    <span data-lang-en="Next" data-lang-fr="Suivant">Next</span>
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
            <?php endif; ?>
            
            <script>
                let currentPage = 1;
                const eventsPerPage = 3;
                const totalEvents = <?= !empty($displayedEvents) ? count($displayedEvents) : 0 ?>;
                const totalPages = Math.ceil(totalEvents / eventsPerPage);

                function showPage(page) {
                    const allEventCards = document.querySelectorAll('.event-item');
                    const start = (page - 1) * eventsPerPage + 1;
                    const end = Math.min(page * eventsPerPage, totalEvents);

                    // Hide all events
                    allEventCards.forEach(card => {
                        card.style.display = 'none';
                    });

                    // Show only events for current page
                    allEventCards.forEach(card => {
                        const index = parseInt(card.getAttribute('data-event-index'));
                        if (index >= start && index <= end) {
                            card.style.display = 'block';
                            // Add fade-in animation
                            card.style.animation = 'fadeIn 0.5s ease';
                        }
                    });

                    // Update pagination info
                    document.getElementById('currentRange').textContent = start + '-' + end;

                    // Update button states
                    document.getElementById('btnPrev').disabled = (page === 1);
                    document.getElementById('btnNext').disabled = (page === totalPages);

                    // Scroll to top of events
                    document.getElementById('eventsContainer').scrollIntoView({ behavior: 'smooth', block: 'start' });
                }

                function nextPage() {
                    if (currentPage < totalPages) {
                        currentPage++;
                        showPage(currentPage);
                    }
                }

                function previousPage() {
                    if (currentPage > 1) {
                        currentPage--;
                        showPage(currentPage);
                    }
                }

                // Add fadeIn animation
                const style = document.createElement('style');
                style.textContent = `
                    @keyframes fadeIn {
                        from {
                            opacity: 0;
                            transform: translateY(20px);
                        }
                        to {
                            opacity: 1;
                            transform: translateY(0);
                        }
                    }
                `;
                document.head.appendChild(style);
            </script>
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
                // Custom email validation without HTML5
                const emailPattern = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
                return emailPattern.test(email.trim());
            },
            
            isValidDateTime: function(dateTimeStr) {
                // Format from datetime-local: YYYY-MM-DDTHH:MM
                if (Validator.isEmpty(dateTimeStr)) {
                    return false;
                }
                
                const inputDate = new Date(dateTimeStr);
                return !isNaN(inputDate.getTime());
            },
            
            isDateAfter: function(date1Str, date2Str) {
                const d1 = new Date(date1Str);
                const d2 = new Date(date2Str);
                return d1 > d2;
            },
            
            isDateInFuture: function(dateTimeStr) {
                const inputDate = new Date(dateTimeStr);
                const now = new Date();
                return inputDate > now;
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
            },
            
            clearAllErrors: function(formId) {
                const form = document.getElementById(formId);
                const errorMessages = form.querySelectorAll('.error-message');
                const formGroups = form.querySelectorAll('.form-group');
                
                errorMessages.forEach(msg => msg.classList.remove('show'));
                formGroups.forEach(group => {
                    group.classList.remove('error');
                    group.classList.remove('success');
                });
            }
        };

        // Create Event Form Validation
        const createEventForm = document.getElementById('createEventForm');
        if (createEventForm) {
            createEventForm.addEventListener('submit', function(e) {
                e.preventDefault();
                Validator.clearAllErrors('createEventForm');
                
                let isValid = true;
                
                // Validate Title
                const titre = document.getElementById('titre').value;
                if (Validator.isEmpty(titre)) {
                    Validator.showError('titre', 'Le titre est obligatoire');
                    isValid = false;
                } else if (!Validator.isValidLength(titre, 5, 200)) {
                    Validator.showError('titre', 'Le titre doit contenir entre 5 et 200 caractères');
                    isValid = false;
                } else {
                    Validator.clearError('titre');
                }
                
                // Validate Description
                const description = document.getElementById('description').value;
                if (Validator.isEmpty(description)) {
                    Validator.showError('description', 'La description est obligatoire');
                    isValid = false;
                } else if (!Validator.isValidLength(description, 10, 1000)) {
                    Validator.showError('description', 'La description doit contenir entre 10 et 1000 caractères');
                    isValid = false;
                } else {
                    Validator.clearError('description');
                }
                
                // Validate Start Date
                const dateDebut = document.getElementById('date_debut').value;
                if (Validator.isEmpty(dateDebut)) {
                    Validator.showError('date_debut', 'La date de début est obligatoire');
                    isValid = false;
                } else if (!Validator.isValidDateTime(dateDebut)) {
                    Validator.showError('date_debut', 'Veuillez sélectionner une date et heure valides');
                    isValid = false;
                } else if (!Validator.isDateInFuture(dateDebut)) {
                    Validator.showError('date_debut', 'La date de début doit être dans le futur');
                    isValid = false;
                } else {
                    Validator.clearError('date_debut');
                }
                
                // Validate End Date
                const dateFin = document.getElementById('date_fin').value;
                if (Validator.isEmpty(dateFin)) {
                    Validator.showError('date_fin', 'La date de fin est obligatoire');
                    isValid = false;
                } else if (!Validator.isValidDateTime(dateFin)) {
                    Validator.showError('date_fin', 'Veuillez sélectionner une date et heure valides');
                    isValid = false;
                } else if (Validator.isValidDateTime(dateDebut) && !Validator.isDateAfter(dateFin, dateDebut)) {
                    Validator.showError('date_fin', 'La date de fin doit être après la date de début');
                    isValid = false;
                } else {
                    Validator.clearError('date_fin');
                }
                
                // Validate Location
                const lieu = document.getElementById('lieu').value;
                if (Validator.isEmpty(lieu)) {
                    Validator.showError('lieu', 'Le lieu est obligatoire');
                    isValid = false;
                } else if (!Validator.isValidLength(lieu, 3, 255)) {
                    Validator.showError('lieu', 'Le lieu doit contenir entre 3 et 255 caractères');
                    isValid = false;
                } else {
                    Validator.clearError('lieu');
                }
                
                // Validate Creator Email
                const createurEmail = document.getElementById('createur_email').value;
                if (Validator.isEmpty(createurEmail)) {
                    Validator.showError('createur_email', 'L\'email est obligatoire');
                    isValid = false;
                } else if (!Validator.isValidEmail(createurEmail)) {
                    Validator.showError('createur_email', 'Format d\'email invalide (ex: exemple@domaine.com)');
                    isValid = false;
                } else {
                    Validator.clearError('createur_email');
                }
                
                if (isValid) {
                    this.submit();
                }
            });
            
            // Real-time validation
            ['titre', 'description', 'date_debut', 'date_fin', 'lieu', 'createur_email'].forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field) {
                    field.addEventListener('blur', function() {
                        // Trigger validation on blur
                        const submitEvent = new Event('submit', { cancelable: true });
                        createEventForm.dispatchEvent(submitEvent);
                    });
                }
            });
        }

        // Participation Form Validation
        const participationForm = document.getElementById('participationForm');
        if (participationForm) {
            participationForm.addEventListener('submit', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                Validator.clearAllErrors('participationForm');
                
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
                
                // If validation passes, submit the form using native DOM method
                if (isValid) {
                    // Use HTMLFormElement.prototype.submit to bypass event listeners
                    HTMLFormElement.prototype.submit.call(participationForm);
                }
            });
            
            // Real-time validation
            ['nom_participant', 'email_participant'].forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field) {
                    field.addEventListener('input', function() {
                        // Clear error on input
                        const errorDiv = document.getElementById('error-' + fieldId);
                        if (errorDiv.classList.contains('show')) {
                            Validator.clearError(fieldId);
                        }
                    });
                }
            });
        }
    </script>

    <script>
        // Function to prompt for email and redirect to My Events
        function showMyEvents() {
            const email = prompt('Enter your email to view your events:');
            if (email && email.trim() !== '') {
                // Basic email validation
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (emailRegex.test(email)) {
                    window.location.href = '?view=my&email=' + encodeURIComponent(email);
                } else {
                    alert('Please enter a valid email address');
                }
            }
        }
    </script>

    <script src="lang-toggle.js"></script>
</body>
</html>