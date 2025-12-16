<?php
require_once __DIR__ . '/../../controller/TicketController.php';
require_once __DIR__ . '/../../controller/ParticipationController.php';
require_once __DIR__ . '/../../controller/EvenementController.php';

// Get parameters
$idParticipation = isset($_GET['p']) ? (int)$_GET['p'] : 0;
$idEvenement = isset($_GET['e']) ? (int)$_GET['e'] : 0;

if (!$idParticipation || !$idEvenement) {
    die("Invalid ticket parameters");
}

// Get ticket info
$ticketController = new TicketController();
$participationController = new ParticipationController();
$eventController = new EvenementController();

$ticket = $ticketController->getTicketByParticipationAndEvent($idParticipation, $idEvenement);
$participation = $participationController->lireParId($idParticipation);
$event = $eventController->lireParId($idEvenement);

if (!$ticket || !$participation || !$event) {
    die("Ticket not found");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Ticket</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .ticket-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 500px;
            width: 100%;
            overflow: hidden;
        }
        
        .ticket-header {
            background: linear-gradient(135deg, #FF7A00 0%, #FF9A00 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .ticket-header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .ticket-number {
            background: rgba(255,255,255,0.2);
            padding: 10px 20px;
            border-radius: 25px;
            display: inline-block;
            margin-top: 10px;
            font-weight: bold;
            letter-spacing: 2px;
        }
        
        .ticket-body {
            padding: 30px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: bold;
            color: #666;
        }
        
        .info-value {
            color: #333;
            text-align: right;
        }
        
        .event-title {
            font-size: 24px;
            color: #333;
            margin-bottom: 20px;
            text-align: center;
            font-weight: bold;
        }
        
        .status-badge {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 12px;
        }
        
        .status-active {
            background: #4CAF50;
            color: white;
        }
        
        .status-used {
            background: #999;
            color: white;
        }
        
        .qr-section {
            text-align: center;
            padding: 20px;
            background: #f9f9f9;
            margin-top: 20px;
            border-radius: 10px;
        }
        
        .qr-section img {
            max-width: 200px;
            height: auto;
        }
        
        @media (max-width: 600px) {
            .ticket-header h1 {
                font-size: 22px;
            }
            
            .event-title {
                font-size: 20px;
            }
            
            .info-row {
                flex-direction: column;
                gap: 5px;
            }
            
            .info-value {
                text-align: left;
            }
        }
    </style>
</head>
<body>
    <div class="ticket-container">
        <div class="ticket-header">
            <h1>🎫 Event Ticket</h1>
            <div class="ticket-number"><?php echo htmlspecialchars($ticket->getToken()); ?></div>
        </div>
        
        <div class="ticket-body">
            <div class="event-title"><?php echo htmlspecialchars($event->getTitre()); ?></div>
            
            <div class="info-row">
                <span class="info-label">📅 Date:</span>
                <span class="info-value"><?php echo $event->getDateDebut()->format('F j, Y'); ?></span>
            </div>
            
            <div class="info-row">
                <span class="info-label">🕐 Time:</span>
                <span class="info-value"><?php echo $event->getDateDebut()->format('g:i A'); ?> - <?php echo $event->getDateFin()->format('g:i A'); ?></span>
            </div>
            
            <div class="info-row">
                <span class="info-label">📍 Location:</span>
                <span class="info-value"><?php echo htmlspecialchars($event->getLieu()); ?></span>
            </div>
            
            <div class="info-row">
                <span class="info-label">👤 Participant:</span>
                <span class="info-value"><?php echo htmlspecialchars($participation->getNomParticipant()); ?></span>
            </div>
            
            <div class="info-row">
                <span class="info-label">📧 Email:</span>
                <span class="info-value"><?php echo htmlspecialchars($participation->getEmailParticipant()); ?></span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Status:</span>
                <span class="info-value">
                    <span class="status-badge status-<?php echo strtolower($ticket->getStatus()); ?>">
                        <?php echo htmlspecialchars($ticket->getStatus()); ?>
                    </span>
                </span>
            </div>
            
            <div class="qr-section">
                <p style="color: #666; margin-bottom: 10px;">Scan this QR code for verification:</p>
                <?php if ($ticket->getQrCodePath()): ?>
                    <img src="<?php echo htmlspecialchars($ticket->getQrCodePath()); ?>" alt="QR Code">
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
