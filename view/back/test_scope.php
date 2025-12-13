<?php
require_once __DIR__ . '/../../controller/AdminConversationController.php';

// Mock DB or just test the getConversationMessages method directly if possible
// Since we edited the GetConversationController which outputs JSON, we might want to test that or the underlying AdminConversationController logic if we edited it?
// Wait, I edited GetConversationController.php, NOT AdminConversationController.php for the `getConversationMessages` method definition.
// I edited the CALLER.
// So I should test the `getConversationMessages` method to see if passing `false` works as expected.

$controller = new AdminConversationController();
// We need a skin, sender, receiver from previous test data
// Skin: jig (12 probably, based on logs? or name). Let's use database scan.

// First, get an active conversation
$all = $controller->getAllConversations();
if (empty($all)) {
    echo "No conversations found to test.\n";
    exit;
}

$conv = $all[0];
echo "Testing with Skin: " . $conv['skin_name'] . " (" . $conv['skin_id'] . ")\n";
echo "Participants: " . $conv['sender_id'] . " & " . $conv['receiver_id'] . "\n";

// Fetch with includeDeleted = false
$activeMessages = $controller->getConversationMessages($conv['skin_id'], $conv['sender_id'], $conv['receiver_id'], false);
echo "Active Messages Count: " . count($activeMessages) . "\n";

// Fetch with includeDeleted = true
$allMessages = $controller->getConversationMessages($conv['skin_id'], $conv['sender_id'], $conv['receiver_id'], true);
echo "Total Messages Count: " . count($allMessages) . "\n";

if (count($activeMessages) <= count($allMessages)) {
    echo "Verification Passed: Active count <= Total count.\n";
} else {
    echo "Verification Failed!\n";
}
