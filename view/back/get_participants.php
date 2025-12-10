<?php
require_once __DIR__ . '/../../controller/ParticipationController.php';
require_once __DIR__ . '/../../controller/EvenementController.php';

header('Content-Type: application/json');

try {
    $participationController = new ParticipationController();
    $eventController = new EvenementController();
    
    // Get all events
    $evenements = $eventController->lireTous();
    
    $participants = [];
    
    foreach ($evenements as $item) {
        $event = $item['evenement'];
        
        // Get participants for this event
        $eventParticipants = $participationController->lireParEvenement($event->getIdEvenement());
        
        foreach ($eventParticipants as $participant) {
            $participants[] = [
                'event_title' => $event->getTitre(),
                'event_id' => $event->getIdEvenement(),
                'nom_participant' => $participant->getNomParticipant(),
                'email_participant' => $participant->getEmailParticipant(),
                'date_participation' => $participant->getDateParticipation()->format('Y-m-d H:i:s')
            ];
        }
    }
    
    echo json_encode($participants);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>