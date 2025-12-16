<?php

declare(strict_types=1);
require_once __DIR__ . '/../model/config.php';
require_once __DIR__ . '/UserController.php';
require_once __DIR__ . '/AdminConversationController.php';

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in and is Admin or SuperAdmin
if (!UserController::isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

$currentUser = UserController::getCurrentUser();
$userRole = strtolower($currentUser ? $currentUser->getRole() : '');
if (!$currentUser || ($userRole !== 'admin' && $userRole !== 'superadmin')) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

// Handle conversation request
if (isset($_POST['get_conversation'])) {
    $skinId = isset($_POST['skin_id']) ? (int)$_POST['skin_id'] : 0;
    $senderId = isset($_POST['sender_id']) ? (int)$_POST['sender_id'] : 0;
    $receiverId = isset($_POST['receiver_id']) ? (int)$_POST['receiver_id'] : 0;
    $negotiationId = isset($_POST['negotiation_id']) ? trim($_POST['negotiation_id']) : '';
    
    if ($skinId <= 0 || $senderId <= 0 || $receiverId <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
        exit();
    }
    
    $controller = new AdminConversationController();
    
    if (!empty($negotiationId)) {
        // Fetch specific refused/archived session
        // Using a new method or custom logic to get messages by negotiation_id
        // Since we don't have a direct method for this in AdminConvController that limits by negotiation_id AND participants easily,
        // we can use the model directly or add a filter.
        // Let's rely on ConversationModel or ad-hoc query here for simplicity, or modify AdminController.
        
        // Actually, let's use the DB instance here for direct query as this is a specific filter
        // Or better, let's add `getArchivedMessagesByNegotiationId` to the controller if reused, but here is fine.
        
        $db = getDB();
        $stmt = $db->prepare("
            SELECT tc.*, u.username as sender_username
            FROM trade_conversations tc
            JOIN users u ON tc.sender_id = u.id
            WHERE tc.negotiation_id = ?
            ORDER BY tc.created_at ASC
        ");
        $stmt->execute([$negotiationId]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } else {
        // Fetch ONLY active messages (is_deleted=0)
        // This effectively separates the "Active" session from the "Refused" sessions.
        $messages = $controller->getConversationMessages($skinId, $senderId, $receiverId, false);
    }
    
    echo json_encode([
        'success' => true,
        'messages' => $messages
    ]);
    exit();
}

// Handle archived conversation request (by negotiation_id)
if (isset($_POST['get_archived_conversation'])) {
    $skinId = isset($_POST['skin_id']) ? (int)$_POST['skin_id'] : 0;
    $negotiationId = isset($_POST['negotiation_id']) ? trim($_POST['negotiation_id']) : '';
    
    if ($skinId <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid skin ID']);
        exit();
    }
    
    // Get archived messages by negotiation_id
    require_once __DIR__ . '/../model/ConversationModel.php';
    $conversationModel = new ConversationModel();
    
    if (!empty($negotiationId)) {
        // Get messages by negotiation_id (doesn't need user_id for admin)
        $stmt = getDB()->prepare("
            SELECT tc.*, u.username as sender_username
            FROM trade_conversations tc
            JOIN users u ON tc.sender_id = u.id
            WHERE tc.negotiation_id = ?
            AND tc.is_deleted = 1
            ORDER BY tc.created_at ASC
        ");
        $stmt->execute([$negotiationId]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Fallback: get all archived messages for this skin
        $stmt = getDB()->prepare("
            SELECT tc.*, u.username as sender_username
            FROM trade_conversations tc
            JOIN users u ON tc.sender_id = u.id
            WHERE tc.skin_id = ?
            AND tc.is_deleted = 1
            ORDER BY tc.created_at ASC
        ");
        $stmt->execute([$skinId]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    echo json_encode([
        'success' => true,
        'messages' => $messages
    ]);
    exit();
}

// Handle delete conversation request
if (isset($_POST['delete_conversation'])) {
    $negotiationId = isset($_POST['negotiation_id']) ? trim($_POST['negotiation_id']) : '';
    
    if (empty($negotiationId)) {
        echo json_encode(['success' => false, 'error' => 'Invalid negotiation ID']);
        exit();
    }
    
    require_once __DIR__ . '/../model/ConversationModel.php';
    $conversationModel = new ConversationModel();
    
    if ($conversationModel->deleteConversationPermanently($negotiationId)) {
        echo json_encode(['success' => true, 'message' => 'Conversation deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to delete conversation']);
    }
    exit();
}

echo json_encode(['success' => false, 'error' => 'Invalid request']);
