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
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
                ";
                $this->db->exec($createTableSQL);
            }
        } catch (PDOException $e) {
            error_log("ConversationModel::ensureConversationsTable error: " . $e->getMessage());
        }
    }
    
    /**
     * Send a message
     * 
     * @param int $skinId
     * @param int $senderId
     * @param int $receiverId
     * @param string $message
     * @return int|null Returns message ID or null on failure
     */
    public function sendMessage(int $skinId, int $senderId, int $receiverId, string $message): ?int {
        try {
            $this->ensureConversationsTable();
            
            $stmt = $this->db->prepare("
                INSERT INTO trade_conversations (skin_id, sender_id, receiver_id, message, created_at)
                VALUES (:skin_id, :sender_id, :receiver_id, :message, NOW())
            ");
            
            $result = $stmt->execute([
                ':skin_id' => $skinId,
                ':sender_id' => $senderId,
                ':receiver_id' => $receiverId,
                ':message' => $message
            ]);
            
            return $result ? (int)$this->db->lastInsertId() : null;
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
                WHERE tc.skin_id = :skin_id
                AND (tc.sender_id = :user_id OR tc.receiver_id = :user_id)
                ORDER BY tc.created_at ASC
            ");
            $stmt->execute([
                ':skin_id' => $skinId,
                ':user_id' => $userId
            ]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                WHERE tc.skin_id = :skin_id
                AND (
                    (tc.sender_id = :user_id1 AND tc.receiver_id = :user_id2)
                    OR (tc.sender_id = :user_id2 AND tc.receiver_id = :user_id1)
                )
                ORDER BY tc.created_at ASC
            ");
            $stmt->execute([
                ':skin_id' => $skinId,
                ':user_id1' => $userId1,
                ':user_id2' => $userId2
            ]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
}

