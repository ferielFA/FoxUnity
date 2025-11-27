<?php
require_once __DIR__ . '/../../controller/EvenementController.php';
require_once __DIR__ . '/../../controller/ParticipationController.php';

header('Content-Type: application/json');

try {
    $eventController = new EvenementController();
    $type = $_GET['type'] ?? 'all';
    
    $evenements = $eventController->lireTous();
    $now = new DateTime();
    $result = [];

    foreach ($evenements as $item) {
        $event = $item['evenement'];
        $nbParticipants = $item['nb_participants'];
        
        // Determine status
        $isExpired = $event->getDateFin() < $now;
        
        if ($type === 'expired' && !$isExpired) continue;
        if ($type === 'upcoming' && $isExpired) continue;
        
        $statusClass = $isExpired ? 'status-expired' : 'status-available';
        $statusLabel = $isExpired ? 'Expired' : 'Available';
        
        $result[] = [
            'id' => $event->getIdEvenement(),
            'title' => $event->getTitre(),
            'location' => $event->getLieu(),
            'start_date' => $event->getDateDebut()->format('M d, Y - H:i'),
            'end_date' => $event->getDateFin()->format('M d, Y - H:i'),
            'participants' => $nbParticipants,
            'status' => $statusLabel,
            'status_class' => $statusClass,
            'description' => $event->getDescription()
        ];
    }

    echo json_encode($result);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load events: ' . $e->getMessage()]);
}
?>
