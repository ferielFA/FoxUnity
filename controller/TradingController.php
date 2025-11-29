<?php


require_once __DIR__ . '/../model/config.php';
require_once __DIR__ . '/../model/UserModel.php';
require_once __DIR__ . '/../model/SkinModel.php';
require_once __DIR__ . '/../model/TradeHistoryModel.php';
require_once __DIR__ . '/../model/ConversationModel.php';

class TradingController {
    private $userModel;
    private $skinModel;
    private $tradeHistoryModel;
    private $conversationModel;
    private $currentUser;
    private $db;
    
    public function __construct(?string $currentUser = null) {
        $this->db = getDB();
        $this->userModel = new UserModel();
        $this->skinModel = new SkinModel();
        $this->tradeHistoryModel = new TradeHistoryModel();
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
        
        // Verify user is linked to database and active
        if (!$this->userModel->userExistsAndActive($this->currentUser)) {
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
        
        $buyerId = $this->userModel->getUserIdByUsername($this->currentUser);
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
            header('Location: trading.php?error=2');
            exit();
        }
        
        // Security: Ensure user can only add trades as themselves
        if ($sellerUsername !== $this->currentUser) {
            header('Location: trading.php?error=7');
            exit();
        }
        

        $ownerId = $this->userModel->getUserIdByUsername($sellerUsername);
        if (!$ownerId) {
            header('Location: trading.php?error=4');
            exit();
        }
        

        $imagePath = $this->handleImageUpload();
        if ($imagePath === null) {
            header('Location: trading.php?error=3');
            exit();
        }
        

        $skinId = $this->skinModel->createSkin($ownerId, $name, $price, $imagePath, $description, $category);
        if (!$skinId) {
            header('Location: trading.php?error=1');
            exit();
        }
        
 
        $this->tradeHistoryModel->logTradeHistory($ownerId, $skinId, 'created', $name, $price, $category);
        
        header('Location: trading.php?added=1');
        exit();
    }
    

    private function handleUpdateTrade(): ?array {
        $skinId = isset($_POST['skinId']) ? (int)$_POST['skinId'] : 0;
        $name = isset($_POST['skinName']) ? trim((string)$_POST['skinName']) : '';
        $price = isset($_POST['skinPrice']) ? (float)$_POST['skinPrice'] : 0.0;
        $description = isset($_POST['skinDescription']) ? trim((string)$_POST['skinDescription']) : '';
        $category = isset($_POST['skinGame']) ? trim((string)$_POST['skinGame']) : 'custom';
        
        if (empty($name) || $price <= 0 || $skinId <= 0) {
            header('Location: trading.php?error=2');
            exit();
        }
        

        $skin = $this->skinModel->getSkinByOwner($skinId, $this->currentUser);
        if (!$skin) {
            header('Location: trading.php?error=5');
            exit();
        }
        

        if (!$this->skinModel->updateSkin($skinId, $name, $price, $description, $category)) {
            header('Location: trading.php?error=1');
            exit();
        }

        $this->tradeHistoryModel->logTradeHistory($skin['owner_id'], $skinId, 'updated', $name, $price, $category);
        
        header('Location: trading.php?updated=1');
        exit();
    }
    

    private function handleDeleteTrade(): ?array {
        $skinId = isset($_POST['skinId']) ? (int)$_POST['skinId'] : 0;
        
        if ($skinId <= 0) {
            header('Location: trading.php?error=2');
            exit();
        }
        

        $skin = $this->skinModel->getSkinByOwner($skinId, $this->currentUser);
        if (!$skin) {
            header('Location: trading.php?error=5');
            exit();
        }
        

        $this->tradeHistoryModel->logTradeHistory(
            $skin['owner_id'],
            $skinId,
            'deleted',
            $skin['name'],
            $skin['price'],
            $skin['category']
        );
        

        if (!$this->skinModel->deleteSkin($skinId)) {
            header('Location: trading.php?error=1');
            exit();
        }
        
        header('Location: trading.php?deleted=1');
        exit();
    }
    

    private function handleClearHistory(): ?array {
        $user = $this->userModel->getUserByUsername($this->currentUser);
        if (!$user) {
            header('Location: trading.php?error=6');
            exit();
        }
        
        if (!$this->tradeHistoryModel->clearTradeHistory($user['id'])) {
            header('Location: trading.php?error=1');
            exit();
        }
        
        header('Location: trading.php?cleared=1');
        exit();
    }
    

    private function handleSendMessage(): ?array {
        $skinId = isset($_POST['skin_id']) ? (int)$_POST['skin_id'] : 0;
        $message = isset($_POST['message']) ? trim((string)$_POST['message']) : '';
        
        if (empty($message) || $skinId <= 0) {
            return ['success' => false, 'error' => 'Invalid message or skin'];
        }
                $skin = $this->skinModel->getSkinById($skinId);
        if (!$skin) {
            return ['success' => false, 'error' => 'Skin not found'];
        }
        

        $senderId = $this->userModel->getUserIdByUsername($this->currentUser);
        if (!$senderId) {
            return ['success' => false, 'error' => 'Sender not found'];
        }

        $receiverId = null;
        $isSeller = ($skin['seller_username'] === $this->currentUser);
        
        if ($isSeller) {

            $receiverId = $this->conversationModel->getConversationPartner($skinId, $senderId);

            if (!$receiverId) {
                return ['success' => false, 'error' => 'No conversation partner found. Please wait for a buyer to start the conversation.'];
            }
        } else {
            // Buyer is sending - receiver is the seller
            $receiverId = $skin['seller_id'];
        }
        
        // Prevent sending to yourself (shouldn't happen, but safety check)
        if ($senderId === $receiverId) {
            return ['success' => false, 'error' => 'You cannot send messages to yourself'];
        }
        
        // Send message
        $messageId = $this->conversationModel->sendMessage(
            $skinId,
            $senderId,
            $receiverId,
            $message
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

        $userId = $this->userModel->getUserIdByUsername($this->currentUser);
        if (!$userId) {
            return ['success' => false, 'error' => 'User not found'];
        }
        
        // Determine the conversation partner
        $isSeller = ($skin['seller_username'] === $this->currentUser);
        $partnerId = null;
        
        if ($isSeller) {
            // If seller, get the conversation partner (buyer) from existing messages
            $partnerId = $this->conversationModel->getConversationPartner($skinId, $userId);
            if ($partnerId) {
                // Get messages between seller and specific buyer
                $messages = $this->conversationModel->getMessagesBetweenUsers($skinId, $userId, $partnerId);
            } else {
                // If no partner yet, get all messages for this skin where seller is involved
                // This shows messages from any buyer who has messaged
                $messages = $this->conversationModel->getMessages($skinId, $userId);
            }
        } else {
            // If buyer, always show messages between buyer and seller
            $partnerId = $skin['seller_id'];
            if ($partnerId) {
                $messages = $this->conversationModel->getMessagesBetweenUsers($skinId, $userId, $partnerId);
            } else {
                // Fallback: get all messages where buyer is involved
                $messages = $this->conversationModel->getMessages($skinId, $userId);
            }
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

