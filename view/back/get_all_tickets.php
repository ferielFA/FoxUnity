<?php
require_once __DIR__ . '/../../controller/TicketController.php';

header('Content-Type: application/json');

try {
    $ticketController = new TicketController();
    $tickets = $ticketController->getAllTickets();
    
    echo json_encode($tickets);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
