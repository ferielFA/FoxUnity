<?php
require_once __DIR__ . '/../../controller/EvenementController.php';
require_once __DIR__ . '/../../controller/ParticipationController.php';

$eventController = new EvenementController();
$participationController = new ParticipationController();

$message = '';
$showParticipationForm = false;
$showCreateEventForm = false;
$selectedEvent = null;

// Handle create event form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create_event') {
        $evenement = new Evenement(
            null,
            htmlspecialchars($_POST['titre']),
            htmlspecialchars($_POST['description']),
            new DateTime($_POST['date_debut']),
            new DateTime($_POST['date_fin']),
            htmlspecialchars($_POST['lieu']),
            'upcoming'
        );
        
        if ($eventController->creer($evenement)) {
            $message = '<div class="alert success"><i class="fas fa-check-circle"></i> Event created successfully!</div>';
            header("Location: events.php");
            exit;
        } else {
            $message = '<div class="alert error"><i class="fas fa-exclamation-circle"></i> Error creating event.</div>';
        }
    } elseif ($_POST['action'] === 'participate') {
        $participation = new Participation(
            null,
            (int)$_POST['id_evenement'],
            htmlspecialchars($_POST['nom_participant']),
            htmlspecialchars($_POST['email_participant']),
            new DateTime()
        );
        
        if ($participationController->inscrire($participation)) {
            $message = '<div class="alert success"><i class="fas fa-check-circle"></i> Registration confirmed! Welcome aboard!</div>';
        } else {
            $message = '<div class="alert error"><i class="fas fa-exclamation-circle"></i> Already registered or error occurred.</div>';
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

$evenements = $eventController->lireTous();
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
            margin-top: 15px;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
        }

        .btn-create-event:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.5);
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

        <?php if ($showCreateEventForm): ?>
        <div class="modal-overlay" id="createEventModal">
            <div class="participation-modal">
                <div class="modal-header">
                    <h2><i class="fas fa-calendar-plus"></i> Create New Event</h2>
                    <p style="color: #cfd3d8; font-size: 0.95rem;">Fill in the details below to create an event</p>
                </div>

                <form method="POST" action="" id="createEventForm" novalidate>
                    <input type="hidden" name="action" value="create_event">
                    
                    <div class="form-group">
                        <label for="titre">Event Title *</label>
                        <input type="text" id="titre" name="titre" placeholder="Enter event title">
                        <div class="error-message" id="error-titre"></div>
                    </div>

                    <div class="form-group">
                        <label for="description">Description *</label>
                        <textarea id="description" name="description" placeholder="Describe your event" rows="4" style="width:100%; padding:14px 18px; background:rgba(255,255,255,0.05); border:2px solid rgba(255,255,255,0.1); border-radius:10px; color:#fff; font-size:1rem; font-family:'Poppins',sans-serif; resize:vertical;"></textarea>
                        <div class="error-message" id="error-description"></div>
                    </div>

                    <div class="form-group">
                        <label for="date_debut">Start Date & Time *</label>
                        <input type="text" id="date_debut" name="date_debut" placeholder="YYYY-MM-DD HH:MM">
                        <div class="error-message" id="error-date_debut"></div>
                    </div>

                    <div class="form-group">
                        <label for="date_fin">End Date & Time *</label>
                        <input type="text" id="date_fin" name="date_fin" placeholder="YYYY-MM-DD HH:MM">
                        <div class="error-message" id="error-date_fin"></div>
                    </div>

                    <div class="form-group">
                        <label for="lieu">Location *</label>
                        <input type="text" id="lieu" name="lieu" placeholder="Event location">
                        <div class="error-message" id="error-lieu"></div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-check-circle"></i> Create Event
                        </button>
                        <a href="events.php" class="btn-cancel" style="text-decoration:none; display:flex; align-items:center; justify-content:center;">
                            <i class="fas fa-times-circle"></i> Cancel
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
                    <h2><i class="fas fa-ticket-alt"></i> Join Event</h2>
                    <div class="event-name"><?= htmlspecialchars($selectedEvent->getTitre()) ?></div>
                </div>

                <form method="POST" action="" id="participationForm" novalidate>
                    <input type="hidden" name="action" value="participate">
                    <input type="hidden" name="id_evenement" value="<?= $selectedEvent->getIdEvenement() ?>">
                    
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

                    <div class="form-actions">
                        <button type="submit" class="btn-submit">
                            <i class="fas fa-check-circle"></i> Confirm Registration
                        </button>
                        <a href="events.php" class="btn-cancel" style="text-decoration:none; display:flex; align-items:center; justify-content:center;">
                            <i class="fas fa-times-circle"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <section class="events-section">
            <div class="events-header">
                <h2>Upcoming Events</h2>
                <p>Join exciting gaming events and tournaments</p>
                <a href="?create=1" class="btn-create-event">
                    <i class="fas fa-plus-circle"></i> Create New Event
                </a>
            </div>

            <div class="events-container">
                <?php foreach ($evenements as $item):
                    $event = $item['evenement'];
                    $nbParticipants = $item['nb_participants'];
                    $statuts = [
                        'upcoming' => 'À venir',
                        'ongoing' => 'En cours',
                        'completed' => 'Terminé',
                        'cancelled' => 'Annulé'
                    ];
                ?>
                <div class="event-card">
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
                            <?= $nbParticipants ?> participants
                        </div>
                        <?php if ($event->getStatut() === 'upcoming'): ?>
                            <a href="?join=<?= $event->getIdEvenement() ?>" class="btn-join">
                                <i class="fas fa-user-plus"></i> Join Event
                            </a>
                        <?php else: ?>
                            <button class="btn-join" disabled style="opacity:0.5;cursor:not-allowed">
                                <i class="fas fa-ban"></i> Unavailable
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
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
        // Validation Functions (NO HTML5)
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
                // Format: YYYY-MM-DD HH:MM
                const pattern = /^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}$/;
                if (!pattern.test(dateTimeStr.trim())) {
                    return false;
                }
                
                const parts = dateTimeStr.trim().split(' ');
                const dateParts = parts[0].split('-');
                const timeParts = parts[1].split(':');
                
                const year = parseInt(dateParts[0]);
                const month = parseInt(dateParts[1]);
                const day = parseInt(dateParts[2]);
                const hour = parseInt(timeParts[0]);
                const minute = parseInt(timeParts[1]);
                
                if (year < 2025 || year > 2030) return false;
                if (month < 1 || month > 12) return false;
                if (day < 1 || day > 31) return false;
                if (hour < 0 || hour > 23) return false;
                if (minute < 0 || minute > 59) return false;
                
                return true;
            },
            
            isDateAfter: function(date1Str, date2Str) {
                const d1 = new Date(date1Str.replace(' ', 'T'));
                const d2 = new Date(date2Str.replace(' ', 'T'));
                return d1 > d2;
            },
            
            isDateInFuture: function(dateTimeStr) {
                const inputDate = new Date(dateTimeStr.replace(' ', 'T'));
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
                    Validator.showError('date_debut', 'La date de début est obligatoire (Format: YYYY-MM-DD HH:MM)');
                    isValid = false;
                } else if (!Validator.isValidDateTime(dateDebut)) {
                    Validator.showError('date_debut', 'Format invalide. Utilisez: YYYY-MM-DD HH:MM (ex: 2025-12-25 14:30)');
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
                    Validator.showError('date_fin', 'La date de fin est obligatoire (Format: YYYY-MM-DD HH:MM)');
                    isValid = false;
                } else if (!Validator.isValidDateTime(dateFin)) {
                    Validator.showError('date_fin', 'Format invalide. Utilisez: YYYY-MM-DD HH:MM (ex: 2025-12-25 18:30)');
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
                
                if (isValid) {
                    this.submit();
                }
            });
            
            // Real-time validation
            ['titre', 'description', 'date_debut', 'date_fin', 'lieu'].forEach(fieldId => {
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
                
                if (isValid) {
                    this.submit();
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
</body>
</html>
