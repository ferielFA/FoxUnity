<?php


require_once __DIR__ . '/config.php';

class ConversationModel {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Ensure trade_conversations table exists
     */
    private function ensureConversationsTable(): void {
        try {
            $tableCheck = $this->db->query("SHOW TABLES LIKE 'trade_conversations'");
            if ($tableCheck->rowCount() == 0) {
                $createTableSQL = "
                    CREATE TABLE trade_conversations (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        skin_id INT NOT NULL,
                        sender_id INT NOT NULL,
                        receiver_id INT NOT NULL,
                        message TEXT NOT NULL,
                        image_path VARCHAR(255),
                        negotiation_id VARCHAR(50) DEFAULT NULL,
                        is_deleted TINYINT(1) DEFAULT 0,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        INDEX idx_negotiation_id (negotiation_id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
                ";
                $this->db->exec($createTableSQL);
            } else {
                // Check if image_path column exists, if not add it
                $columnCheck = $this->db->query("SHOW COLUMNS FROM trade_conversations LIKE 'image_path'");
                if ($columnCheck->rowCount() == 0) {
                    $this->db->exec("ALTER TABLE trade_conversations ADD COLUMN image_path VARCHAR(255) AFTER message");
                }
                
                // Check if is_deleted column exists, if not add it
                $colCheck2 = $this->db->query("SHOW COLUMNS FROM trade_conversations LIKE 'is_deleted'");
                if ($colCheck2->rowCount() == 0) {
                    $this->db->exec("ALTER TABLE trade_conversations ADD COLUMN is_deleted TINYINT(1) DEFAULT 0");
                }

                // Check if negotiation_id column exists, if not add it
                $colCheck3 = $this->db->query("SHOW COLUMNS FROM trade_conversations LIKE 'negotiation_id'");
                if ($colCheck3->rowCount() == 0) {
                    $this->db->exec("ALTER TABLE trade_conversations ADD COLUMN negotiation_id VARCHAR(50) DEFAULT NULL AFTER image_path");
                    $this->db->exec("ALTER TABLE trade_conversations ADD INDEX idx_negotiation_id (negotiation_id)");
                }
            }
        } catch (PDOException $e) {
            error_log("ConversationModel::ensureConversationsTable error: " . $e->getMessage());
        }
    }
    
    // ... existing methods ...

    /**
     * Get archived (deleted) messages between two specific users for a skin
     */
    public function getArchivedMessages(int $skinId, int $userId1, int $userId2): array {
        try {
            $this->ensureConversationsTable();
            
            $stmt = $this->db->prepare("
                SELECT tc.*, u.username as sender_username
                FROM trade_conversations tc
                JOIN users u ON tc.sender_id = u.id
                WHERE tc.skin_id = ?
                AND (
                    (tc.sender_id = ? AND tc.receiver_id = ?)
                    OR (tc.sender_id = ? AND tc.receiver_id = ?)
                )
                AND tc.is_deleted = 1
                ORDER BY tc.created_at ASC
            ");
            $stmt->execute([$skinId, $userId1, $userId2, $userId2, $userId1]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("ConversationModel::getArchivedMessages error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get archived (deleted) messages by negotiation ID
     */
    public function getArchivedMessagesByNegotiationId(string $negotiationId, int $userId): array {
        try {
            $this->ensureConversationsTable();
            
            $stmt = $this->db->prepare("
                SELECT tc.*, u.username as sender_username
                FROM trade_conversations tc
                JOIN users u ON tc.sender_id = u.id
                WHERE tc.negotiation_id = ?
                AND (tc.sender_id = ? OR tc.receiver_id = ?)
                AND tc.is_deleted = 1
                ORDER BY tc.created_at ASC
            ");
            $stmt->execute([$negotiationId, $userId, $userId]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("ConversationModel::getArchivedMessagesByNegotiationId error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Close a conversation (soft delete all messages)
     * This assigns a negotiation_id to group these messages.
     * 
     * @param int $skinId
     * @param int $userId1
     * @param int $userId2
     * @param string|null $negotiationId Unique ID for this closed session
     * @return bool
     */
    public function closeConversation(int $skinId, int $userId1, int $userId2, ?string $negotiationId = null): bool {
        // Removed try-catch to propagate error for debugging
        $this->ensureConversationsTable();
        
        $sql = "UPDATE trade_conversations 
                SET is_deleted = 1";
        
        $params = [
            ':skin_id' => $skinId,
            ':u1_1' => $userId1,
            ':u2_1' => $userId2,
            ':u2_2' => $userId2,
            ':u1_2' => $userId1
        ];

        if ($negotiationId) {
            $sql .= ", negotiation_id = :negotiation_id";
            $params[':negotiation_id'] = $negotiationId;
        }

        $sql .= " WHERE skin_id = :skin_id 
                  AND (
                      (sender_id = :u1_1 AND receiver_id = :u2_1) OR 
                      (sender_id = :u2_2 AND receiver_id = :u1_2)
                  )
                  AND is_deleted = 0"; // Only close active messages
        
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute($params);
    }
    
    /**
     * Get list of buyers who have active conversations for a skin
     */
    public function getActiveConversationsForSkin(int $skinId, int $sellerId): array {
        try {
            $this->ensureConversationsTable();
            
            // Find distinct partners where messages exist and are not deleted
            $stmt = $this->db->prepare("
                SELECT DISTINCT 
                    CASE 
                        WHEN sender_id = :seller_id THEN receiver_id 
                        ELSE sender_id 
                    END as partner_id
                FROM trade_conversations 
                WHERE skin_id = :skin_id 
                AND (sender_id = :seller_id OR receiver_id = :seller_id)
                AND is_deleted = 0
            ");
            
            $stmt->execute([
                ':seller_id' => $sellerId,
                ':skin_id' => $skinId
            ]);
            
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            error_log("ConversationModel::getActiveConversationsForSkin error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all archived messages for a user and skin (regardless of partner)
     * DEPRECATED: Use getArchivedMessagesByNegotiationId instead for specific history
     */
    public function getAllArchivedMessagesForUser(int $skinId, int $userId): array {
        try {
            $this->ensureConversationsTable();
            
            $stmt = $this->db->prepare("
                SELECT tc.*, u.username as sender_username
                FROM trade_conversations tc
                JOIN users u ON tc.sender_id = u.id
                WHERE tc.skin_id = ?
                AND (tc.sender_id = ? OR tc.receiver_id = ?)
                AND tc.is_deleted = 1
                ORDER BY tc.created_at ASC
            ");
            $stmt->execute([$skinId, $userId, $userId]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("ConversationModel::getAllArchivedMessagesForUser error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Send a message
     * 
     * @param int $skinId
     * @param int $senderId
     * @param int $receiverId
     * @param string $message
     * @param string|null $imagePath
     * @return int|null Returns message ID or null on failure
     */
    public function sendMessage(int $skinId, int $senderId, int $receiverId, string $message, ?string $imagePath = null): ?int {
        try {
            $this->ensureConversationsTable();
            
            // Validate inputs - allow empty message if there's an image
            if ($skinId <= 0 || $senderId <= 0 || $receiverId <= 0 || (empty(trim($message)) && !$imagePath)) {
                error_log("ConversationModel::sendMessage - Invalid inputs: skinId=$skinId, senderId=$senderId, receiverId=$receiverId, message empty=" . (empty(trim($message)) ? 'yes' : 'no') . ", imagePath=" . ($imagePath ? 'yes' : 'no'));
                return null;
            }
            
            $stmt = $this->db->prepare("
                INSERT INTO trade_conversations (skin_id, sender_id, receiver_id, message, image_path, created_at)
                VALUES (:skin_id, :sender_id, :receiver_id, :message, :image_path, NOW())
            ");
            
            $result = $stmt->execute([
                ':skin_id' => $skinId,
                ':sender_id' => $senderId,
                ':receiver_id' => $receiverId,
                ':message' => trim($message),
                ':image_path' => $imagePath
            ]);
            
            if ($result) {
                $lastId = $this->db->lastInsertId();
                error_log("ConversationModel::sendMessage - Message saved successfully, ID: $lastId");
                return (int)$lastId;
            } else {
                error_log("ConversationModel::sendMessage - Execute failed");
                return null;
            }
        } catch (PDOException $e) {
            error_log("ConversationModel::sendMessage error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get messages for a skin and user
     * 
     * @param int $skinId
     * @param int $userId
     * @return array
     */
    public function getMessages(int $skinId, int $userId): array {
        try {
            $this->ensureConversationsTable();
            
            $stmt = $this->db->prepare("
                SELECT tc.*, u.username as sender_username
                FROM trade_conversations tc
                JOIN users u ON tc.sender_id = u.id
                WHERE tc.skin_id = ?
                AND (tc.sender_id = ? OR tc.receiver_id = ?)
                AND (tc.is_deleted = 0 OR tc.is_deleted IS NULL)
                ORDER BY tc.created_at ASC
            ");
            $stmt->execute([$skinId, $userId, $userId]);
            
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("ConversationModel::getMessages - Retrieved " . count($messages) . " messages for skinId=$skinId, userId=$userId");
            return $messages;
        } catch (PDOException $e) {
            error_log("ConversationModel::getMessages error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get messages between two specific users for a skin
     * 
     * @param int $skinId
     * @param int $userId1
     * @param int $userId2
     * @return array
     */
    public function getMessagesBetweenUsers(int $skinId, int $userId1, int $userId2): array {
        try {
            $this->ensureConversationsTable();
            
            $stmt = $this->db->prepare("
                SELECT tc.*, u.username as sender_username
                FROM trade_conversations tc
                JOIN users u ON tc.sender_id = u.id
                WHERE tc.skin_id = ?
                AND (
                    (tc.sender_id = ? AND tc.receiver_id = ?)
                    OR (tc.sender_id = ? AND tc.receiver_id = ?)
                )
                AND (tc.is_deleted = 0 OR tc.is_deleted IS NULL)
                ORDER BY tc.created_at ASC
            ");
            $stmt->execute([$skinId, $userId1, $userId2, $userId2, $userId1]);
            
            $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("ConversationModel::getMessagesBetweenUsers - Retrieved " . count($messages) . " messages for skinId=$skinId, userId1=$userId1, userId2=$userId2");
            return $messages;
        } catch (PDOException $e) {
            error_log("ConversationModel::getMessagesBetweenUsers error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get the other participant in a conversation
     * 
     * @param int $skinId
     * @param int $currentUserId
     * @return int|null Returns the other user's ID or null if no conversation exists
     */
    public function getConversationPartner(int $skinId, int $currentUserId): ?int {
        try {
            $this->ensureConversationsTable();
            

            $stmt = $this->db->prepare("
                SELECT DISTINCT 
                    CASE 
                        WHEN sender_id = :user_id THEN receiver_id
                        WHEN receiver_id = :user_id THEN sender_id
                    END as partner_id
                FROM trade_conversations
                WHERE skin_id = :skin_id
                AND (sender_id = :user_id OR receiver_id = :user_id)
                AND is_deleted = 0
                LIMIT 1
            ");
            $stmt->execute([
                ':skin_id' => $skinId,
                ':user_id' => $currentUserId
            ]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result && $result['partner_id'] ? (int)$result['partner_id'] : null;
        } catch (PDOException $e) {
            error_log("ConversationModel::getConversationPartner error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get any buyer who has messaged about a skin (for seller to message back)
     * 
     * @param int $skinId
     * @param int $sellerId
     * @return int|null Returns a buyer's ID or null if no one has messaged
     */
    public function getAnyBuyerForSkin(int $skinId, int $sellerId): ?int {
        try {
            $this->ensureConversationsTable();

            $stmt = $this->db->prepare("
                SELECT DISTINCT sender_id as buyer_id
                FROM trade_conversations
                WHERE skin_id = ? AND receiver_id = ?
                AND is_deleted = 0
                LIMIT 1
            ");
            $stmt->execute([$skinId, $sellerId]);

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result && $result['buyer_id'] ? (int)$result['buyer_id'] : null;
        } catch (PDOException $e) {
            error_log("ConversationModel::getAnyBuyerForSkin error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if a conversation is locked for a user (i.e. another conversation exists they are not part of)
     * 
     * @param int $skinId
     * @param int $userId
     * @return bool
     */
    public function isConversationLocked(int $skinId, int $userId): bool {
        try {
            $this->ensureConversationsTable();
            // Check if there is a conversation for this skin
            // where the user is NEITHER sender NOR receiver.
            // If such a conversation exists, it means two other people are talking, so it's locked.
            
            $stmt = $this->db->prepare("
                SELECT COUNT(*) 
                FROM trade_conversations 
                WHERE skin_id = :skin_id 
                AND sender_id != :user_id 
                AND receiver_id != :user_id
                AND is_deleted = 0
            ");
            $stmt->execute([':skin_id' => $skinId, ':user_id' => $userId]);
            
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("ConversationModel::isConversationLocked error: " . $e->getMessage());
            return false;
        }
    }
}

