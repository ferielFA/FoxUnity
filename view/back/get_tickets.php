
<?php
require_once __DIR__ . '/../../controller/TicketController.php';

header('Content-Type: application/json');

if (!isset($_GET['id_evenement']) || !is_numeric($_GET['id_evenement'])) {
    echo json_encode(['error' => 'Invalid event ID']);
    exit;
}

$ticketController = new TicketController();
$idEvenement = (int)$_GET['id_evenement'];
$tickets = $ticketController->getTicketsByEvent($idEvenement);

$response = [];
foreach ($tickets as $ticketData) {
    $ticket = $ticketData['ticket'];
    $response[] = [
        'id_ticket' => $ticket->getIdTicket(),
        'participant_name' => $ticketData['participant_name'],
        'participant_email' => $ticketData['participant_email'],
        'token' => substr($ticket->getToken(), 0, 20) . '...', // Shortened for display
        'status' => $ticket->getStatus(),
        'qr_code_path' => $ticket->getQrCodePath(),
        'created_at' => $ticket->getCreatedAt()->format('Y-m-d H:i:s')
    ];
}

echo json_encode($response);
