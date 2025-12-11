<?php
require_once __DIR__ . '/../../controller/TicketController.php';
require_once __DIR__ . '/../../controller/EvenementController.php';

$ticketController = new TicketController();
$eventController = new EvenementController();

$message = '';
$messageType = '';
$ticketData = null;
$eventData = null;

// Debug: Log all request parameters
error_log("verify_ticket.php accessed - GET params: " . print_r($_GET, true));
error_log("verify_ticket.php accessed - POST params: " . print_r($_POST, true));

// Handle QR code scan or manual token input
$token = $_GET['token'] ?? $_POST['token'] ?? '';
error_log("Token extracted: " . $token);

if (!empty($token)) {
    // Extract token from QR code content if needed
    if (strpos($token, 'TICKET:') !== false) {
        preg_match('/TICKET:([^|]+)/', $token, $matches);
        if (isset($matches[1])) {
            $token = $matches[1];
        }
    }
    
    // Verify ticket
    $ticket = $ticketController->getTicketByToken($token);
    
    if ($ticket) {
        $ticketData = [
            'id' => $ticket->getIdTicket(),
            'token' => $ticket->getToken(),
            'status' => $ticket->getStatus(),
            'created_at' => $ticket->getCreatedAt() // DateTime object
        ];
        
        // Get event details
        $event = $eventController->lireParId($ticket->getIdEvenement());
        if ($event) {
            $eventData = [
                'titre' => $event->getTitre(),
                'description' => $event->getDescription(),
                'date_debut' => $event->getDateDebut(),
                'date_fin' => $event->getDateFin(),
                'lieu' => $event->getLieu(),
                'statut' => $event->getStatut()
            ];
        }
        
        // Get participant info
        $participantInfo = $ticketController->getParticipantInfo($ticket->getIdParticipation());
        if ($participantInfo) {
            $ticketData['participant_name'] = $participantInfo['nom_participant'];
            $ticketData['participant_email'] = $participantInfo['email_participant'];
        }
        
        if ($ticket->getStatus() === 'active') {
            $messageType = 'success';
            $message = 'Valid Ticket! Access Granted.';
        } elseif ($ticket->getStatus() === 'used') {
            $messageType = 'warning';
            $message = 'Ticket Already Used!';
        } else {
            $messageType = 'error';
            $message = 'Invalid Ticket Status!';
        }
    } else {
        $messageType = 'error';
        $message = 'Invalid Ticket! Ticket not found.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Verification - FoxUnity</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #16161a 0%, #1b1b20 100%);
            color: #fff;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #f5c242, #f39c12);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
        }

        .subtitle {
            color: #969696;
            font-size: 1rem;
        }

        .verification-card {
            background: rgba(27, 27, 32, 0.8);
            border: 2px solid rgba(245, 194, 66, 0.3);
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 20px;
        }

        .alert {
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
            font-weight: 600;
            font-size: 1.1rem;
            animation: slideIn 0.5s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert i {
            font-size: 2rem;
        }

        .alert.success {
            background: linear-gradient(135deg, #10b981, #059669);
            border: 2px solid #10b981;
        }

        .alert.warning {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            border: 2px solid #f59e0b;
        }

        .alert.error {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            border: 2px solid #ef4444;
        }

        .ticket-info {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            color: #969696;
            font-weight: 600;
        }

        .info-value {
            color: #fff;
            text-align: right;
            max-width: 60%;
            word-break: break-word;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .status-active {
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
            border: 1px solid #10b981;
        }

        .status-used {
            background: rgba(150, 150, 150, 0.2);
            color: #969696;
            border: 1px solid #969696;
        }

        .search-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .input-group {
            position: relative;
        }

        .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #969696;
        }

        input[type="text"] {
            width: 100%;
            padding: 15px 15px 15px 45px;
            background: rgba(0, 0, 0, 0.3);
            border: 2px solid rgba(245, 194, 66, 0.3);
            border-radius: 12px;
            color: #fff;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        input[type="text"]:focus {
            outline: none;
            border-color: #f5c242;
            background: rgba(0, 0, 0, 0.5);
        }

        .btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #f5c242, #f39c12);
            color: #000;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(245, 194, 66, 0.4);
        }

        .btn:active {
            transform: translateY(0);
        }

        .event-details {
            background: rgba(0, 0, 0, 0.3);
            border-radius: 12px;
            padding: 20px;
            margin-top: 20px;
        }

        .event-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #f5c242;
            margin-bottom: 15px;
        }

        .event-info {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            color: #fff;
        }

        .event-info i {
            color: #f5c242;
            width: 20px;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #f5c242;
            text-decoration: none;
            font-weight: 600;
            margin-top: 20px;
            padding: 10px 16px;
            border: 1px solid rgba(245, 194, 66, 0.3);
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            background: rgba(245, 194, 66, 0.1);
            transform: translateX(-5px);
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }

            .verification-card {
                padding: 20px;
            }

            .logo {
                font-size: 2rem;
            }

            .alert {
                font-size: 1rem;
                padding: 15px;
            }

            .info-value {
                max-width: 55%;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo"><i class="fas fa-shield-alt"></i> FoxUnity</div>
            <div class="subtitle">Event Ticket Verification</div>
        </div>

        <?php if (!empty($token)): ?>
            <div class="verification-card">
                <?php if ($messageType): ?>
                    <div class="alert <?= $messageType ?>">
                        <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : ($messageType === 'warning' ? 'exclamation-triangle' : 'times-circle') ?>"></i>
                        <span><?= $message ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($ticketData): ?>
                    <div class="ticket-info">
                        <div class="info-row">
                            <span class="info-label">Ticket ID:</span>
                            <span class="info-value">#<?= str_pad($ticketData['id'], 6, '0', STR_PAD_LEFT) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Token:</span>
                            <span class="info-value"><?= htmlspecialchars($ticketData['token']) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Status:</span>
                            <span class="info-value">
                                <span class="status-badge status-<?= $ticketData['status'] ?>">
                                    <?= ucfirst($ticketData['status']) ?>
                                </span>
                            </span>
                        </div>
                        <?php if (isset($ticketData['participant_name'])): ?>
                            <div class="info-row">
                                <span class="info-label">Participant:</span>
                                <span class="info-value"><?= htmlspecialchars($ticketData['participant_name']) ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if (isset($ticketData['participant_email'])): ?>
                            <div class="info-row">
                                <span class="info-label">Email:</span>
                                <span class="info-value"><?= htmlspecialchars($ticketData['participant_email']) ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="info-row">
                            <span class="info-label">Created:</span>
                            <span class="info-value"><?= $ticketData['created_at'] instanceof DateTime ? $ticketData['created_at']->format('d/m/Y H:i') : date('d/m/Y H:i', strtotime($ticketData['created_at'])) ?></span>
                        </div>
                    </div>

                    <?php if ($eventData): ?>
                        <div class="event-details">
                            <div class="event-title"><?= htmlspecialchars($eventData['titre']) ?></div>
                            <?php if ($eventData['description']): ?>
                                <div class="event-info">
                                    <i class="fas fa-info-circle"></i>
                                    <span><?= htmlspecialchars($eventData['description']) ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="event-info">
                                <i class="fas fa-map-marker-alt"></i>
                                <span><?= htmlspecialchars($eventData['lieu']) ?></span>
                            </div>
                            <div class="event-info">
                                <i class="fas fa-calendar"></i>
                                <span><?= $eventData['date_debut']->format('d/m/Y H:i') ?> - <?= $eventData['date_fin']->format('d/m/Y H:i') ?></span>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <a href="verify_ticket.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Verify Another Ticket
                </a>
            </div>
        <?php else: ?>
            <div class="verification-card">
                <h2 style="margin-bottom: 20px; color: #f5c242;"><i class="fas fa-qrcode"></i> Verify Ticket</h2>
                <p style="color: #969696; margin-bottom: 25px;">
                    Scan the QR code on your ticket or enter the ticket token manually to verify your access.
                </p>

                <form method="GET" class="search-form">
                    <div class="input-group">
                        <i class="fas fa-ticket-alt"></i>
                        <input type="text" name="token" placeholder="Enter ticket token (e.g., TKT-XXXXXXXXXX)" required>
                    </div>
                    <button type="submit" class="btn">
                        <i class="fas fa-search"></i> Verify Ticket
                    </button>
                </form>

                <div style="margin-top: 30px; padding: 20px; background: rgba(94, 196, 255, 0.1); border-left: 4px solid #5ec4ff; border-radius: 8px;">
                    <strong style="color: #5ec4ff; display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                        <i class="fas fa-info-circle"></i> How to Use
                    </strong>
                    <ul style="color: #fff; line-height: 1.8; margin-left: 20px;">
                        <li>Scan the QR code using any QR scanner app</li>
                        <li>The scanner will open this verification page automatically</li>
                        <li>Or manually enter your ticket token above</li>
                        <li>Your ticket will be validated instantly</li>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <div style="text-align: center; margin-top: 30px;">
            <a href="index.php" class="back-link" style="display: inline-flex;">
                <i class="fas fa-home"></i> Back to Homepage
            </a>
        </div>
    </div>
</body>
</html>
