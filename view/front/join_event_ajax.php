<?php
require_once __DIR__ . '/../../controller/ParticipationController.php';
require_once __DIR__ . '/../../controller/UserController.php';
require_once __DIR__ . '/../../model/Participation.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['event_id'])) {
    echo json_encode(['success' => false, 'message' => 'Event ID is required']);
    exit;
}

$eventId = (int) $data['event_id'];
$participationController = new ParticipationController();

// Get current user
$isLoggedIn = UserController::isLoggedIn();
$currentUser = UserController::getCurrentUser();

if (!$isLoggedIn) {
    // Guest participation
    if (!isset($data['participant_name']) || !isset($data['participant_email'])) {
        echo json_encode(['success' => false, 'message' => 'Name and email are required for guest registration']);
        exit;
    }

    $participantName = htmlspecialchars($data['participant_name']);
    $participantEmail = htmlspecialchars($data['participant_email']);
    $participantId = null;

    // Check if already registered
    $isAlreadyRegistered = $participationController->verifierInscription($participantEmail, $eventId);

    if ($isAlreadyRegistered) {
        echo json_encode(['success' => false, 'message' => 'You are already registered for this event!']);
        exit;
    }
} else {
    // Logged-in user
    $participantId = $currentUser->getId();
    $participantName = $currentUser->getUsername();
    $participantEmail = $currentUser->getEmail();

    // Check if already registered
    $isAlreadyRegistered = $participationController->verifierInscription($participantEmail, $eventId);

    if ($isAlreadyRegistered) {
        echo json_encode(['success' => false, 'message' => 'You are already registered for this event!']);
        exit;
    }
}

// Create participation
$participation = new Participation(
    null,
    $eventId,
    $participantId,
    $participantName,
    $participantEmail,
    new DateTime()
);

if ($participationController->inscrire($participation)) {
    // Get updated participant count
    $participants = $participationController->lireParEvenement($eventId);
    $nbParticipants = count($participants);

    echo json_encode([
        'success' => true,
        'message' => 'Registration confirmed! Welcome aboard!',
        'participant_count' => $nbParticipants
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error occurred during registration. Please try again.']);
}
