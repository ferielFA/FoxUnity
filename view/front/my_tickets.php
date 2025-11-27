<?php
session_start();
require_once __DIR__ . '/../../controller/TicketController.php';

$ticketController = new TicketController();

// Security: Require email in session or POST
$userEmail = $_SESSION['user_email'] ?? $_POST['email'] ?? null;

// Handle email submission for ticket access
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['access_email'])) {
    $_SESSION['user_email'] = filter_var($_POST['access_email'], FILTER_VALIDATE_EMAIL);
    $userEmail = $_SESSION['user_email'];
    header("Location: my_tickets.php");
    exit;
}

// Handle logout
if (isset($_GET['logout'])) {
    unset($_SESSION['user_email']);
    header("Location: my_tickets.php");
    exit;
}

// Fetch tickets if email is provided
$tickets = [];
if ($userEmail) {
    $tickets = $ticketController->getTicketsByEmail($userEmail);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Tickets - Nine Tailed Fox</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Page Styles */
        .tickets-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .page-header {
            text-align: center;
            margin-bottom: 60px;
            position: relative;
        }

        .page-header h1 {
            font-family: 'Orbitron', sans-serif;
            font-size: 3rem;
            color: #f5c242;
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .page-header p {
            color: #969696;
            font-size: 1.1rem;
        }

        /* Email Access Form */
        .email-access-card {
            background: linear-gradient(135deg, rgba(22, 22, 26, 0.95), rgba(27, 27, 32, 0.95));
            border: 2px solid rgba(245, 194, 66, 0.3);
            border-radius: 16px;
            padding: 60px 40px;
            max-width: 500px;
            margin: 0 auto;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
        }

        .email-access-card h2 {
            font-family: 'Orbitron', sans-serif;
            color: #f5c242;
            text-align: center;
            margin-bottom: 12px;
            font-size: 1.8rem;
        }

        .email-access-card p {
            color: #969696;
            text-align: center;
            margin-bottom: 32px;
            line-height: 1.6;
        }

        .form-group-ticket {
            margin-bottom: 24px;
        }

        .form-group-ticket label {
            display: block;
            color: #f5c242;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 0.95rem;
        }

        .form-group-ticket input {
            width: 100%;
            padding: 14px 16px;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(245, 194, 66, 0.2);
            border-radius: 8px;
            color: #fff;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group-ticket input:focus {
            outline: none;
            border-color: #f5c242;
            background: rgba(245, 194, 66, 0.05);
        }

        .btn-access {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #f5c242, #f39c12);
            border: none;
            border-radius: 8px;
            color: #000;
            font-family: 'Orbitron', sans-serif;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-access:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(245, 194, 66, 0.4);
        }

        /* User Info Bar */
        .user-info-bar {
            background: rgba(245, 194, 66, 0.1);
            border: 1px solid rgba(245, 194, 66, 0.3);
            border-radius: 12px;
            padding: 20px 30px;
            margin-bottom: 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .user-info-bar .email-display {
            color: #f5c242;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-logout {
            background: transparent;
            border: 2px solid rgba(255, 107, 107, 0.4);
            color: #ff6b6b;
            padding: 8px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-logout:hover {
            background: rgba(255, 107, 107, 0.1);
            border-color: #ff6b6b;
        }

        /* Ticket Card */
        .ticket-card {
            background: linear-gradient(135deg, rgba(22, 22, 26, 0.95), rgba(27, 27, 32, 0.95));
            border: 2px solid rgba(245, 194, 66, 0.3);
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 30px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .ticket-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 6px;
            height: 100%;
            background: linear-gradient(180deg, #f5c242, #f39c12);
        }

        .ticket-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(245, 194, 66, 0.2);
            border-color: #f5c242;
        }

        .ticket-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 24px;
            padding-bottom: 20px;
            border-bottom: 2px solid rgba(245, 194, 66, 0.2);
        }

        .ticket-event-info h2 {
            font-family: 'Orbitron', sans-serif;
            color: #f5c242;
            font-size: 1.8rem;
            margin-bottom: 8px;
        }

        .ticket-event-info .event-meta {
            display: flex;
            gap: 20px;
            color: #969696;
            font-size: 0.9rem;
        }

        .event-meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .event-meta-item i {
            color: #f5c242;
        }

        .ticket-status {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.85rem;
            text-transform: uppercase;
        }

        .status-active {
            background: rgba(46, 213, 115, 0.2);
            color: #2ed573;
            border: 2px solid #2ed573;
        }

        .status-used {
            background: rgba(150, 150, 150, 0.2);
            color: #969696;
            border: 2px solid #969696;
        }

        .status-cancelled {
            background: rgba(255, 107, 107, 0.2);
            color: #ff6b6b;
            border: 2px solid #ff6b6b;
        }

        .ticket-body {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 40px;
            align-items: center;
        }

        .ticket-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .detail-item {
            background: rgba(255, 255, 255, 0.03);
            padding: 16px;
            border-radius: 8px;
            border: 1px solid rgba(245, 194, 66, 0.1);
        }

        .detail-item label {
            display: block;
            color: #969696;
            font-size: 0.85rem;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .detail-item .value {
            color: #fff;
            font-weight: 600;
            font-size: 1rem;
        }

        .qr-code-section {
            text-align: center;
            background: rgba(255, 255, 255, 0.95);
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        .qr-code-section img {
            width: 200px;
            height: 200px;
            margin-bottom: 12px;
        }

        .qr-code-section .qr-label {
            color: #16161a;
            font-weight: 700;
            font-size: 0.9rem;
            font-family: 'Orbitron', sans-serif;
        }

        .btn-download-ticket {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, #f5c242, #f39c12);
            color: #000;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-family: 'Orbitron', sans-serif;
            font-weight: 700;
            font-size: 0.9rem;
            margin-top: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .btn-download-ticket:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(245, 194, 66, 0.4);
        }

        .btn-download-ticket i {
            font-size: 1rem;
        }

        /* Empty State */
        .empty-tickets {
            text-align: center;
            padding: 80px 20px;
            color: #969696;
        }

        .empty-tickets i {
            font-size: 80px;
            margin-bottom: 24px;
            opacity: 0.3;
        }

        .empty-tickets h3 {
            font-family: 'Orbitron', sans-serif;
            color: #f5c242;
            font-size: 1.8rem;
            margin-bottom: 12px;
        }

        .empty-tickets p {
            font-size: 1.1rem;
            margin-bottom: 30px;
        }

        .btn-browse-events {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(135deg, #f5c242, #f39c12);
            color: #000;
            padding: 14px 32px;
            border-radius: 8px;
            text-decoration: none;
            font-family: 'Orbitron', sans-serif;
            font-weight: 700;
            transition: all 0.3s ease;
        }

        .btn-browse-events:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(245, 194, 66, 0.4);
        }

        /* Language Toggle */
        .lang-toggle {
            position: fixed;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, rgba(245, 194, 66, 0.2), rgba(243, 156, 18, 0.2));
            border: 2px solid rgba(245, 194, 66, 0.4);
            border-radius: 8px;
            padding: 8px 16px;
            color: #f5c242;
            font-family: 'Orbitron', sans-serif;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .lang-toggle:hover {
            background: linear-gradient(135deg, rgba(245, 194, 66, 0.3), rgba(243, 156, 18, 0.3));
            border-color: #f5c242;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(245, 194, 66, 0.3);
        }

        /* Back Button */
        .btn-back {
            position: fixed;
            top: 20px;
            left: 20px;
            background: rgba(22, 22, 26, 0.9);
            border: 2px solid rgba(245, 194, 66, 0.4);
            color: #f5c242;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            z-index: 1000;
        }

        .btn-back:hover {
            background: rgba(245, 194, 66, 0.1);
            border-color: #f5c242;
            transform: translateX(-3px);
        }

        @media (max-width: 768px) {
            .ticket-body {
                grid-template-columns: 1fr;
            }

            .qr-code-section {
                order: -1;
            }

            .page-header h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="stars"></div>
    <div class="shooting-star"></div>
    <div class="shooting-star"></div>
    <div class="shooting-star"></div>

    <!-- Back Button -->
    <a href="events.php" class="btn-back">
        <i class="fas fa-arrow-left"></i>
        <span data-lang-en="Back to Events" data-lang-fr="Retour aux Événements">Back to Events</span>
    </a>

    <!-- Language Toggle -->
    <button id="langToggle" class="lang-toggle" onclick="toggleLanguage()">
        <i class="fas fa-language"></i>
        <span id="currentLang">FR</span>
    </button>

    <div class="tickets-container">
        <div class="page-header">
            <h1 data-lang-en="My Tickets" data-lang-fr="Mes Tickets">My Tickets</h1>
            <p data-lang-en="Access and manage your event tickets" data-lang-fr="Accédez et gérez vos tickets d'événements">Access and manage your event tickets</p>
        </div>

        <?php if (!$userEmail): ?>
            <!-- Email Access Form -->
            <div class="email-access-card">
                <h2 data-lang-en="Access Your Tickets" data-lang-fr="Accédez à vos Tickets">Access Your Tickets</h2>
                <p data-lang-en="Enter the email address you used to register for events" data-lang-fr="Entrez l'adresse e-mail que vous avez utilisée pour vous inscrire aux événements">
                    Enter the email address you used to register for events
                </p>
                <form method="POST" action="">
                    <div class="form-group-ticket">
                        <label for="access_email" data-lang-en="Email Address" data-lang-fr="Adresse E-mail">Email Address</label>
                        <input type="email" id="access_email" name="access_email" placeholder="your.email@example.com" required>
                    </div>
                    <button type="submit" class="btn-access">
                        <i class="fas fa-ticket-alt"></i>
                        <span data-lang-en="View My Tickets" data-lang-fr="Voir Mes Tickets">View My Tickets</span>
                    </button>
                </form>
            </div>
        <?php else: ?>
            <!-- User Info Bar -->
            <div class="user-info-bar">
                <div class="email-display">
                    <i class="fas fa-user-circle"></i>
                    <span><?= htmlspecialchars($userEmail) ?></span>
                </div>
                <a href="?logout" class="btn-logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span data-lang-en="Change Email" data-lang-fr="Changer E-mail">Change Email</span>
                </a>
            </div>

            <!-- Tickets List -->
            <?php if (empty($tickets)): ?>
                <div class="empty-tickets">
                    <i class="fas fa-ticket-alt"></i>
                    <h3 data-lang-en="No Tickets Found" data-lang-fr="Aucun Ticket Trouvé">No Tickets Found</h3>
                    <p data-lang-en="You haven't joined any events yet" data-lang-fr="Vous n'avez rejoint aucun événement pour le moment">
                        You haven't joined any events yet
                    </p>
                    <a href="events.php" class="btn-browse-events">
                        <i class="fas fa-calendar-alt"></i>
                        <span data-lang-en="Browse Events" data-lang-fr="Parcourir les Événements">Browse Events</span>
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($tickets as $ticketData): 
                    $ticket = $ticketData['ticket'];
                    $now = new DateTime();
                    $eventEnd = $ticketData['event_end'];
                    
                    // Determine event status
                    $isExpired = $eventEnd < $now;
                    $statusText = $ticket->getStatus();
                    $statusClass = 'status-' . $statusText;
                ?>
                    <div class="ticket-card">
                        <div class="ticket-header">
                            <div class="ticket-event-info">
                                <h2><?= htmlspecialchars($ticketData['event_title']) ?></h2>
                                <div class="event-meta">
                                    <div class="event-meta-item">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span><?= htmlspecialchars($ticketData['event_location']) ?></span>
                                    </div>
                                    <div class="event-meta-item">
                                        <i class="fas fa-calendar"></i>
                                        <span><?= $ticketData['event_start']->format('M d, Y - H:i') ?></span>
                                    </div>
                                </div>
                            </div>
                            <div class="ticket-status <?= $statusClass ?>">
                                <span data-lang-en="<?= ucfirst($statusText) ?>" data-lang-fr="<?= $statusText === 'active' ? 'Actif' : ($statusText === 'used' ? 'Utilisé' : 'Annulé') ?>">
                                    <?= ucfirst($statusText) ?>
                                </span>
                            </div>
                        </div>

                        <div class="ticket-body">
                            <div class="ticket-details">
                                <div class="detail-item">
                                    <label data-lang-en="Participant" data-lang-fr="Participant">Participant</label>
                                    <div class="value"><?= htmlspecialchars($ticketData['participant_name']) ?></div>
                                </div>
                                <div class="detail-item">
                                    <label data-lang-en="Email" data-lang-fr="E-mail">Email</label>
                                    <div class="value"><?= htmlspecialchars($ticketData['participant_email']) ?></div>
                                </div>
                                <div class="detail-item">
                                    <label data-lang-en="Event End Time" data-lang-fr="Heure de Fin">Event End Time</label>
                                    <div class="value"><?= $ticketData['event_end']->format('M d, Y - H:i') ?></div>
                                </div>
                                <div class="detail-item">
                                    <label data-lang-en="Ticket Generated" data-lang-fr="Ticket Généré">Ticket Generated</label>
                                    <div class="value"><?= $ticket->getCreatedAt()->format('M d, Y - H:i') ?></div>
                                </div>
                            </div>

                            <?php if ($ticket->getQrCodePath()): ?>
                            <div class="qr-code-section">
                                <img src="<?= htmlspecialchars($ticket->getQrCodePath()) ?>" alt="QR Code">
                                <div class="qr-label" data-lang-en="Scan at Entrance" data-lang-fr="Scanner à l'Entrée">Scan at Entrance</div>
                                <a href="../../controller/generate_ticket_image.php?id=<?= $ticket->getIdTicket() ?>" download class="btn-download-ticket">
                                    <i class="fas fa-download"></i>
                                    <span data-lang-en="Download Ticket" data-lang-fr="Télécharger Ticket">Download Ticket</span>
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <footer class="site-footer">
        © 2025 <span>Nine Tailed Fox</span>. All Rights Reserved.
    </footer>

    <script src="lang-toggle.js"></script>
    <script>
        // Initialize language
        window.addEventListener('DOMContentLoaded', () => {
            updateLanguage();
        });
    </script>
</body>
</html>
