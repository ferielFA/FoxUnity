<?php
require_once __DIR__ . '/../../controller/AdminConversationController.php';

try {
    $controller = new AdminConversationController();
    $conversations = $controller->getAllConversations();
    
    echo "Successfully retrieved " . count($conversations) . " conversations.\n";
    foreach ($conversations as $conv) {
        echo "Status: " . $conv['status'] . "\n";
        echo "Buyer: " . $conv['buyer_name'] . "\n";
        echo "Seller: " . $conv['seller_name'] . "\n";
        echo "P1User: " . ($conv['sender_username'] ?? 'N/A') . "\n";
        echo "P2User: " . ($conv['receiver_username'] ?? 'N/A') . "\n";
        echo "Messages: " . $conv['message_count'] . "\n";
        echo "-------------------\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
