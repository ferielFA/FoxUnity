<?php


require_once __DIR__ . '/../model/config.php';
require_once __DIR__ . '/../model/User.php';
require_once __DIR__ . '/../model/SkinModel.php';
require_once __DIR__ . '/TradeHistoryController.php';
require_once __DIR__ . '/../model/ConversationModel.php';

class TradingController {
    private $skinModel;
    private $tradeHistoryModel;
    private $conversationModel;
    private $currentUser;
    private $db;
    
    public function __construct(?string $currentUser = null) {
        $this->db = getDB();
        // User operations are handled by the `User` class (model/User.php)
        $this->skinModel = new SkinModel();
        $this->tradeHistoryModel = new TradeHistoryController();
        $this->conversationModel = new ConversationModel();
        
        // Get current user from session if not provided
        if ($currentUser === null) {
            $this->currentUser = getCurrentUsername();
        } else {
            $this->currentUser = $currentUser;
        }
        
        // If still no user, throw exception or handle gracefully
        if (empty($this->currentUser)) {
            throw new Exception("No user logged in. Please login first.");
        }
        
        // Verify user is linked to database and active using User model
        $userObj = User::getByUsername($this->currentUser);
        if (!$userObj || $userObj->getStatus() !== 'active') {
            throw new Exception("User account not found or inactive. Please login again.");
        }
    }
    
    /**
     * Handle POST requests
     * 
     * @return array|null Returns response data for JSON requests, or null for redirects
     */
    public function handlePost(): ?array {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return null;
        }

        // Validate session user for AJAX requests to prevent confusion
        if (isset($_POST['active_user_check'])) {
            if ($_POST['active_user_check'] !== $this->currentUser) {
                return ['success' => false, 'error' => 'Session mismatch. Please refresh the page.'];
            }
        }
        
        if (isset($_POST['add_trade'])) {
            return $this->handleAddTrade();
        } elseif (isset($_POST['update_trade'])) {
            return $this->handleUpdateTrade();
        } elseif (isset($_POST['delete_trade'])) {
            return $this->handleDeleteTrade();
        } elseif (isset($_POST['clear_history'])) {
            return $this->handleClearHistory();
        } elseif (isset($_POST['send_message'])) {
            return $this->handleSendMessage();
        } elseif (isset($_POST['get_messages'])) {
            return $this->handleGetMessages();
        } elseif (isset($_POST['refuse_offer'])) {
            return $this->handleRefuseOffer();
        } elseif (isset($_POST['accept_offer'])) {
            return $this->handleAcceptOffer();
        } elseif (isset($_POST['get_archived_messages'])) {
            return $this->handleGetArchivedMessages();
        } elseif (isset($_POST['buy_skins'])) {
            return $this->handleBuy();
        }
        
        return null;
    }

    private function handleBuy(): ?array {
        $skinIds = isset($_POST['skin_ids']) ? json_decode($_POST['skin_ids'], true) : [];
        
        if (empty($skinIds) || !is_array($skinIds)) {
            return ['success' => false, 'error' => 'No items selected'];
        }
        
        $buyer = User::getByUsername($this->currentUser);
        $buyerId = $buyer ? $buyer->getId() : null;
        if (!$buyerId) {
            return ['success' => false, 'error' => 'User not found'];
        }
        
        $successCount = 0;
        $errors = [];
        
        foreach ($skinIds as $skinData) {
            // Support both simple ID array or object array
            $skinId = is_array($skinData) ? ($skinData['id'] ?? 0) : $skinData;
            
            if (!$skinId) continue;
            
            $skin = $this->skinModel->getSkinById($skinId);
            if (!$skin) {
                $errors[] = "Skin ID $skinId not found";
                continue;
            }
            
            // Prevent buying own skin
            if ($skin['seller_username'] === $this->currentUser) {
                $errors[] = "You cannot buy your own skin: {$skin['name']}";
                continue;
            }
            
            // Transfer ownership
            if ($this->skinModel->transferOwnership($skinId, $buyerId)) {
                // Log history
                $this->tradeHistoryModel->logTradeHistory(
                    $buyerId,
                    $skinId,
                    'bought',
                    $skin['name'],
                    $skin['price'],
                    $skin['category']
                );
                
                // Insert into trade table
                try {
                    $stmt = $this->db->prepare("
                        INSERT INTO trade (buyer_id, seller_id, skin_id, trade_type, trade_date)
                        VALUES (:buyer_id, :seller_id, :skin_id, 'buy', NOW())
                    ");
                    $stmt->execute([
                        ':buyer_id' => $buyerId,
                        ':seller_id' => $skin['seller_id'],
                        ':skin_id' => $skinId
                    ]);
                } catch (PDOException $e) {
                    error_log("Failed to insert trade record: " . $e->getMessage());
                }
                
                $successCount++;
            } else {
                $errors[] = "Failed to purchase {$skin['name']}";
            }
        }
        
        if ($successCount > 0) {
            return ['success' => true, 'count' => $successCount, 'errors' => $errors];
        } else {
            return ['success' => false, 'error' => 'Purchase failed', 'details' => $errors];
        }
    }
    

    private function handleAddTrade(): ?array {
        $name = isset($_POST['skinName']) ? trim((string)$_POST['skinName']) : '';
        $price = isset($_POST['skinPrice']) ? (float)$_POST['skinPrice'] : 0.0;
        $description = isset($_POST['skinDescription']) ? trim((string)$_POST['skinDescription']) : '';
        $category = isset($_POST['skinGame']) ? trim((string)$_POST['skinGame']) : 'custom';
        $sellerUsername = isset($_POST['sellerUsername']) ? trim((string)$_POST['sellerUsername']) : '';
        

        if (empty($name) || $price <= 0 || empty($sellerUsername)) {
            return ['success' => false, 'error' => 'Invalid trade data'];
        }
        
        // Security: Ensure user can only add trades as themselves
        if ($sellerUsername !== $this->currentUser) {
            return ['success' => false, 'error' => 'Unauthorized'];
        }
        

        $owner = User::getByUsername($sellerUsername);
        $ownerId = $owner ? $owner->getId() : null;
        if (!$ownerId) {
            return ['success' => false, 'error' => 'User not found'];
        }
        

        $imagePath = $this->handleImageUpload();
        if ($imagePath === null) {
            return ['success' => false, 'error' => 'Failed to upload image'];
        }
        

        $skinId = $this->skinModel->createSkin($ownerId, $name, $price, $imagePath, $description, $category);
        if (!$skinId) {
            return ['success' => false, 'error' => 'Failed to create trade'];
        }
        
 
        $this->tradeHistoryModel->logTradeHistory($ownerId, $skinId, 'created', $name, $price, $category);
        
        return ['success' => true, 'message' => 'Trade created successfully', 'skinId' => $skinId];
    }
    

    private function handleUpdateTrade(): ?array {
        $skinId = isset($_POST['skinId']) ? (int)$_POST['skinId'] : 0;
        $name = isset($_POST['skinName']) ? trim((string)$_POST['skinName']) : '';
        $price = isset($_POST['skinPrice']) ? (float)$_POST['skinPrice'] : 0.0;
        $description = isset($_POST['skinDescription']) ? trim((string)$_POST['skinDescription']) : '';
        $category = isset($_POST['skinGame']) ? trim((string)$_POST['skinGame']) : 'custom';
        
        if (empty($name) || $price <= 0 || $skinId <= 0) {
            return ['success' => false, 'error' => 'Invalid trade data'];
        }
        

        $skin = $this->skinModel->getSkinByOwner($skinId, $this->currentUser);
        if (!$skin) {
            return ['success' => false, 'error' => 'Trade not found or not owned by you'];
        }
        

        if (!$this->skinModel->updateSkin($skinId, $name, $price, $description, $category)) {
            return ['success' => false, 'error' => 'Failed to update trade'];
        }

        $this->tradeHistoryModel->logTradeHistory($skin['owner_id'], $skinId, 'updated', $name, $price, $category);
        
        return ['success' => true, 'message' => 'Trade updated successfully'];
    }
    

    private function handleDeleteTrade(): ?array {
        $skinId = isset($_POST['skinId']) ? (int)$_POST['skinId'] : (isset($_POST['skin_id']) ? (int)$_POST['skin_id'] : 0);
        
        if ($skinId <= 0) {
            return ['success' => false, 'error' => 'Invalid skin'];
        }
        
        $skin = $this->skinModel->getSkinById($skinId);
        if (!$skin) {
            return ['success' => false, 'error' => 'Skin not found'];
        }
        
        // Security check
        if ($skin['seller_username'] !== $this->currentUser) {
            return ['success' => false, 'error' => 'Unauthorized'];
        }

        $currentUserObj = User::getByUsername($this->currentUser);
        $currentUserId = $currentUserObj ? $currentUserObj->getId() : null;

        if ($currentUserId) {
            // New Logic: Close and Archive all active negotiations before deleting
            $activeBuyers = $this->conversationModel->getActiveConversationsForSkin($skinId, $currentUserId);
            
            foreach ($activeBuyers as $buyerId) {
                // Generate a negotiation ID for this forced closure
                $negotiationId = uniqid('neg_del_'); // distinguishable ID
                
                // Close conversation
                if ($this->conversationModel->closeConversation($skinId, $currentUserId, $buyerId, $negotiationId)) {
                    // Log for Buyer: Negotiation Refused (or maybe we should name it 'listing_deleted' but keeping it 'negotiation_refused' ensures it shows in the archive tab easily)
                    // Let's stick to 'negotiation_refused' for consistency in the archive view, 
                    // or maybe we could use 'deleted' action but with negotiation_id?
                    // The TradeHistoryController handles 'negotiation_refused' specially.
                    // Let's use 'negotiation_refused' so the buyer sees it in their archive.
                    
                    $this->tradeHistoryModel->logTradeHistory(
                        $buyerId,
                        $skinId,
                        'negotiation_refused',
                        $skin['name'],
                        $skin['price'],
                        $skin['category'],
                        $negotiationId
                    );
                    
                    // Log for Seller: Negotiation Refused (Closed due to deletion)
                    $this->tradeHistoryModel->logTradeHistory(
                        $currentUserId,
                        $skinId,
                        'negotiation_refused',
                        $skin['name'],
                        $skin['price'],
                        $skin['category'],
                        $negotiationId
                    );
                }
            }
        }
        
        if ($this->skinModel->deleteSkin($skinId)) {
             // Log the deletion itself for the seller (Standard history)
            if ($currentUserId) {
                $this->tradeHistoryModel->logTradeHistory(
                    $currentUserId,
                    $skinId,
                    'deleted',
                    $skin['name'],
                    $skin['price'],
                    $skin['category']
                );
            }
            return ['success' => true, 'message' => 'Trade deleted successfully'];
        } else {
            return ['success' => false, 'error' => 'Failed to delete trade'];
        }
    }
    

    private function handleRefuseOffer(): ?array {
        $skinId = isset($_POST['skin_id']) ? (int)$_POST['skin_id'] : 0;
        
        if ($skinId <= 0) {
            return ['success' => false, 'error' => 'Invalid skin'];
        }
        
        $skin = $this->skinModel->getSkinById($skinId);
        if (!$skin) {
            return ['success' => false, 'error' => 'Skin not found'];
        }

        $currentUserObj = User::getByUsername($this->currentUser);
        $currentUserId = $currentUserObj ? $currentUserObj->getId() : null;
        if (!$currentUserId) {
            return ['success' => false, 'error' => 'User not found'];
        }

        // Security: Only seller can refuse/reset
        if ($skin['seller_username'] !== $this->currentUser) {
            return ['success' => false, 'error' => 'Only the seller can refuse/reset the offer.'];
        }

        // Identify the conversation partner
        $partnerId = $this->conversationModel->getConversationPartner($skinId, $currentUserId);
        
        if (!$partnerId) {
             // Fallback: Check if there are any messages where I am the receiver (Seller)
             $partnerId = $this->conversationModel->getAnyBuyerForSkin($skinId, $currentUserId);
             
             if (!$partnerId) {
                 error_log("handleRefuseOffer: No partner found for skinId=$skinId, userId=$currentUserId");
                 return ['success' => false, 'error' => 'No active negotiation found to refuse.'];
             }
        }

        error_log("handleRefuseOffer: Closing conversation skin=$skinId, user=$currentUserId, partner=$partnerId");

        try {
            // Generate unique negotiation ID
            $negotiationId = uniqid('neg_');

            // Close the conversation (soft delete) with the new negotiation ID
            if ($this->conversationModel->closeConversation($skinId, $currentUserId, $partnerId, $negotiationId)) {
                // Log history for both parties with negotiation ID
                $this->tradeHistoryModel->logTradeHistory(
                    $currentUserId,
                    $skinId,
                    'negotiation_refused',
                    $skin['name'],
                    $skin['price'],
                    $skin['category'],
                    $negotiationId // Pass negotiation ID
                );
                
                $this->tradeHistoryModel->logTradeHistory(
                    $partnerId,
                    $skinId,
                    'negotiation_refused',
                    $skin['name'],
                    $skin['price'],
                    $skin['category'],
                    $negotiationId // Pass negotiation ID
                );

                return ['success' => true, 'message' => 'Negotiation ended and conversation reset.'];
            } else {
                return ['success' => false, 'error' => 'Failed to refuse offer (Database executed but returned false).'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Database Error: ' . $e->getMessage()];
        }
    }

    private function handleAcceptOffer(): ?array {
        $skinId = isset($_POST['skin_id']) ? (int)$_POST['skin_id'] : 0;

        if ($skinId <= 0) {
            return ['success' => false, 'error' => 'Invalid skin'];
        }

        $skin = $this->skinModel->getSkinById($skinId);
        if (!$skin) {
            return ['success' => false, 'error' => 'Skin not found'];
        }

        $sellerObj = User::getByUsername($this->currentUser);
        $sellerId = $sellerObj ? $sellerObj->getId() : null;
        if (!$sellerId) {
            return ['success' => false, 'error' => 'User not found'];
        }

        if ($skin['seller_username'] !== $this->currentUser) {
            return ['success' => false, 'error' => 'Only the seller can accept the offer.'];
        }

        $buyerId = $this->conversationModel->getConversationPartner($skinId, $sellerId);
        if (!$buyerId) {
            $buyerId = $this->conversationModel->getAnyBuyerForSkin($skinId, $sellerId);
            if (!$buyerId) {
                return ['success' => false, 'error' => 'No active negotiation found to accept.'];
            }
        }

        try {
            $this->db->beginTransaction();

            if (!$this->skinModel->transferOwnership($skinId, $buyerId)) {
                $this->db->rollBack();
                return ['success' => false, 'error' => 'Failed to transfer ownership'];
            }

            try {
                $stmt = $this->db->prepare("\n                    INSERT INTO trade (buyer_id, seller_id, skin_id, trade_type, trade_date)\n                    VALUES (:buyer_id, :seller_id, :skin_id, 'buy', NOW())\n                ");
                $stmt->execute([
                    ':buyer_id' => $buyerId,
                    ':seller_id' => $sellerId,
                    ':skin_id' => $skinId
                ]);
            } catch (PDOException $e) {
                $this->db->rollBack();
                error_log("Failed to insert trade record: " . $e->getMessage());
                return ['success' => false, 'error' => 'Failed to record trade'];
            }

            $negotiationId = uniqid('neg_ok_');
            $this->conversationModel->closeConversation($skinId, $sellerId, $buyerId, $negotiationId);

            $this->tradeHistoryModel->logTradeHistory(
                $buyerId,
                $skinId,
                'trade',
                $skin['name'],
                (float)$skin['price'],
                (string)$skin['category'],
                $negotiationId
            );

            $this->tradeHistoryModel->logTradeHistory(
                $sellerId,
                $skinId,
                'trade',
                $skin['name'],
                (float)$skin['price'],
                (string)$skin['category'],
                $negotiationId
            );

            $this->db->commit();
            return ['success' => true, 'message' => 'Offer accepted. Trade completed.'];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['success' => false, 'error' => 'Database Error: ' . $e->getMessage()];
        }
    }

    private function handleClearHistory(): ?array {
        $userObj = User::getByUsername($this->currentUser);
        if (!$userObj) {
            return ['success' => false, 'error' => 'User not found'];
        }
        
        $type = isset($_POST['clear_type']) ? trim($_POST['clear_type']) : 'all';
        $allowedTypes = ['all', 'standard', 'negotiations'];
        
        if (!in_array($type, $allowedTypes)) {
            $type = 'all';
        }
        
        if (!$this->tradeHistoryModel->clearTradeHistory($userObj->getId(), $type)) {
            return ['success' => false, 'error' => 'Failed to clear history'];
        }
        
        return ['success' => true, 'message' => 'Trade history cleared successfully'];
    }
    

    private function handleSendMessage(): ?array {
        $skinId = isset($_POST['skin_id']) ? (int)$_POST['skin_id'] : 0;
        $message = isset($_POST['message']) ? trim((string)$_POST['message']) : '';
        
        // Allow either message or image (or both)
        if ((empty($message) && (!isset($_FILES['image']) || $_FILES['image']['size'] == 0)) || $skinId <= 0) {
            return ['success' => false, 'error' => 'Please provide a message or image'];
        }
                $skin = $this->skinModel->getSkinById($skinId);
        if (!$skin) {
            return ['success' => false, 'error' => 'Skin not found'];
        }
        

        $sender = User::getByUsername($this->currentUser);
        $senderId = $sender ? $sender->getId() : null;
        if (!$senderId) {
            return ['success' => false, 'error' => 'Sender not found'];
        }

        $receiverId = null;
        $isSeller = ($skin['seller_username'] === $this->currentUser);
        
        if ($isSeller) {
            // Seller sending - first try to get conversation partner
            $receiverId = $this->conversationModel->getConversationPartner($skinId, $senderId);
            if (!$receiverId) {
                // No existing conversation partner - try to get any buyer who messaged about this skin
                $receiverId = $this->conversationModel->getAnyBuyerForSkin($skinId, $senderId);
            }
            if (!$receiverId) {
                // Still no buyer found - seller cannot message without someone to send to
                return ['success' => false, 'error' => 'No buyers have messaged you about this skin yet.'];
            }
        } else {
            // Buyer is sending - receiver is the seller
            $receiverId = $skin['seller_id'];

            // Check if this trade is exclusive to another buyer
            if ($this->conversationModel->isConversationLocked($skinId, $senderId)) {
                return ['success' => false, 'error' => 'This trade is currently being negotiated by someone else.'];
            }
        }
        
        // Prevent sending to yourself (shouldn't happen, but safety check)
        if ($senderId === $receiverId) {
            return ['success' => false, 'error' => 'You cannot send messages to yourself'];
        }

        // Handle image upload
        $imagePath = null;
        if (isset($_FILES['image']) && $_FILES['image']['size'] > 0) {
            $imagePath = $this->handleMessageImageUpload($_FILES['image']);
            if (!$imagePath) {
                return ['success' => false, 'error' => 'Failed to upload image'];
            }
        }
        
        // Send message
        $messageId = $this->conversationModel->sendMessage(
            $skinId,
            $senderId,
            $receiverId,
            $message,
            $imagePath
        );
        
        if ($messageId) {
            return ['success' => true, 'message_id' => $messageId];
        } else {
            return ['success' => false, 'error' => 'Failed to send message'];
        }
    }
    

    private function handleGetMessages(): ?array {
        $skinId = isset($_POST['skin_id']) ? (int)$_POST['skin_id'] : 0;
        
        if ($skinId <= 0) {
            return ['success' => false, 'error' => 'Invalid skin'];
        }
        
        // Get skin information
        $skin = $this->skinModel->getSkinById($skinId);
        if (!$skin) {
            return ['success' => false, 'error' => 'Skin not found'];
        }

        $userObj = User::getByUsername($this->currentUser);
        $userId = $userObj ? $userObj->getId() : null;
        if (!$userId) {
            return ['success' => false, 'error' => 'User not found'];
        }
        
        // Determine the conversation partner
        $isSeller = ($skin['seller_username'] === $this->currentUser);
        $partnerId = null;
        $canMessage = true;  // By default, can message (both buyers and sellers can message)
        
        if ($isSeller) {
            // If seller, get the conversation partner (buyer) from existing messages
            $partnerId = $this->conversationModel->getConversationPartner($skinId, $userId);
            // Seller can now message anytime (removed the restriction)
            $canMessage = true;
            if ($partnerId) {
                // Get messages between seller and specific buyer
                $messages = $this->conversationModel->getMessagesBetweenUsers($skinId, $userId, $partnerId);
            } else {
                // If no partner yet, get all messages for this skin where seller is involved
                // This shows messages from any buyer who has messaged
                $messages = $this->conversationModel->getMessages($skinId, $userId);
            }
        } else {
            // If buyer, always get messages with the seller
            $partnerId = $skin['seller_id'];
            // Buyer can always message
            $canMessage = true;
            if ($partnerId) {
                $messages = $this->conversationModel->getMessagesBetweenUsers($skinId, $userId, $partnerId);
            } else {
                // Fallback: get all messages where buyer is involved
                $messages = $this->conversationModel->getMessages($skinId, $userId);
            }
        }
        
        return ['success' => true, 'messages' => $messages, 'isSeller' => $isSeller, 'canMessage' => $canMessage];
    }

    private function handleGetArchivedMessages(): ?array {
        $skinId = isset($_POST['skin_id']) ? (int)$_POST['skin_id'] : 0;
        $negotiationId = isset($_POST['negotiation_id']) ? trim($_POST['negotiation_id']) : null;
        
        if ($skinId <= 0) {
            return ['success' => false, 'error' => 'Invalid skin'];
        }

        $userObj = User::getByUsername($this->currentUser);
        $userId = $userObj ? $userObj->getId() : null;
        if (!$userId) {
            return ['success' => false, 'error' => 'User not found'];
        }
        
        $messages = [];
        if (!empty($negotiationId)) {
            // Get messages for specific negotiation session
            $messages = $this->conversationModel->getArchivedMessagesByNegotiationId($negotiationId, $userId);
        } else {
            // Fallback for old records without negotiation_id
            $messages = $this->conversationModel->getAllArchivedMessagesForUser($skinId, $userId);
        }
        
        return ['success' => true, 'messages' => $messages];
    }
    
    /**
     * Handle image upload
     * 
     * @return string|null Returns relative image path or null on failure
     */
    private function handleImageUpload(): ?string {
        if (!isset($_FILES['skinImage']) || $_FILES['skinImage']['error'] !== UPLOAD_ERR_OK) {
            return null;
        }
        
        $uploadDirRelative = 'images/skins/';
        $uploadDirFS = __DIR__ . '/../view/' . $uploadDirRelative;
        

        if (!is_dir($uploadDirFS)) {
            mkdir($uploadDirFS, 0777, true);
        }
        
  
        $origName = basename((string)$_FILES['skinImage']['name']);
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (!in_array($ext, $allowed, true)) {
            return null;
        }
        
        $newFileName = uniqid('skin_') . '.' . $ext;
        $targetFullPath = $uploadDirFS . $newFileName;
        
        if (move_uploaded_file($_FILES['skinImage']['tmp_name'], $targetFullPath)) {
            return $uploadDirRelative . $newFileName;
        }
        
        return null;
    }

    /**
     * Handle image upload for messages
     * 
     * @param array $file
     * @return string|null Returns the relative path to the uploaded image or null on failure
     */
    private function handleMessageImageUpload(array $file): ?string {
        // Validate file
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file['type'], $allowed)) {
            error_log("Invalid image type: " . $file['type']);
            return null;
        }

        $maxSize = 5 * 1024 * 1024; // 5MB
        if ($file['size'] > $maxSize) {
            error_log("Image too large: " . $file['size']);
            return null;
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            error_log("Upload error: " . $file['error']);
            return null;
        }

        // Create uploads/messages directory if it doesn't exist
        $uploadDir = __DIR__ . '/../view/uploads/messages';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Generate unique filename
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $newFileName = uniqid('msg_', true) . '.' . $ext;
        $targetPath = $uploadDir . '/' . $newFileName;

        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return 'uploads/messages/' . $newFileName;
        }

        return null;
    }
    
    /**
     * Get all data needed for the view
     * 
     * @return array
     */
    public function getViewData(): array {
        return [
            'skins' => $this->skinModel->getAllSkins(),
            'mySkins' => $this->skinModel->getSkinsByUsername($this->currentUser),
            'tradeHistory' => $this->tradeHistoryModel->getTradeHistoryByUsername($this->currentUser),
            'currentUser' => $this->currentUser
        ];
    }
}

