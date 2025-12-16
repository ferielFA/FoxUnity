<?php

require_once __DIR__ . '/../model/config.php';
require_once __DIR__ . '/../model/ConversationModel.php';
require_once __DIR__ . '/../model/User.php';

class AdminConversationController {
    private $conversationModel;
    private $db;
    
    public function __construct() {
        $this->db = getDB();
        $this->conversationModel = new ConversationModel();
    }
    
    /**
     * Get all ACTIVE (non-deleted) conversations grouped by participants and skin
     * Returns array of conversation summaries
     */
    public function getActiveConversations(): array {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    tc.skin_id,
                    tc.sender_id,
                    tc.receiver_id,
                    COUNT(*) as message_count,
                    MAX(tc.created_at) as last_message_at,
                    MIN(tc.created_at) as first_message_at,
                    s.name as skin_name,
                    u1.username as sender_username,
                    u2.username as receiver_username
                FROM trade_conversations tc
                LEFT JOIN skins s ON tc.skin_id = s.id
                LEFT JOIN users u1 ON tc.sender_id = u1.id
                LEFT JOIN users u2 ON tc.receiver_id = u2.id
                WHERE tc.is_deleted = 0
                GROUP BY tc.skin_id, tc.sender_id, tc.receiver_id
                ORDER BY last_message_at DESC
            ");
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("AdminConversationController::getActiveConversations error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get all ARCHIVED negotiations from trade_history table (like tradehis.php)
     * Returns array of negotiation_refused entries for all users
     */
    public function getArchivedNegotiations(): array {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    th.id,
                    th.user_id,
                    th.skin_id,
                    th.action,
                    th.skin_name,
                    th.skin_price,
                    th.skin_category,
                    th.negotiation_id,
                    th.created_at,
                    u.username
                FROM trade_history th
                JOIN users u ON th.user_id = u.id
                WHERE th.action = 'negotiation_refused'
                ORDER BY th.created_at DESC
            ");
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("AdminConversationController::getArchivedNegotiations error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get conversation statistics
     */
    public function getConversationStats(): array {
        try {
            // Count distinct sessions (Active + Refused) based on the same grouping as getAllConversations
            $countStmt = $this->db->query("
                SELECT COUNT(*) as total_rows FROM (
                    SELECT 1 
                    FROM trade_conversations 
                    GROUP BY skin_id, LEAST(sender_id, receiver_id), GREATEST(sender_id, receiver_id), negotiation_id
                ) as temp
            ");
            $totalConversations = $countStmt->fetchColumn();

            // Message stats
            $msgStmt = $this->db->query("
                SELECT 
                    SUM(CASE WHEN is_deleted = 0 THEN 1 ELSE 0 END) as active_messages,
                    SUM(CASE WHEN is_deleted = 1 THEN 1 ELSE 0 END) as archived_messages,
                    COUNT(*) as total_messages
                FROM trade_conversations
            ");
            $msgStats = $msgStmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'total_conversations' => $totalConversations ?: 0,
                'active_messages' => $msgStats['active_messages'] ?: 0,
                'archived_messages' => $msgStats['archived_messages'] ?: 0,
                'total_messages' => $msgStats['total_messages'] ?: 0
            ];
        } catch (PDOException $e) {
            error_log("AdminConversationController::getConversationStats error: " . $e->getMessage());
            return [
                'total_conversations' => 0,
                'active_messages' => 0,
                'archived_messages' => 0,
                'total_messages' => 0
            ];
        }
    }
    
    /**
     * Get ALL conversations (active and archived)
     * Returns a unified list with status, buyer, seller details
     */
    public function getAllConversations(): array {
        try {
            // Unified query for Active and Archived conversations
            // Group by Skin + Normalized Participants
            // Status is 'Active' if there are ANY non-deleted messages.
            // Status is 'Refused' if all messages are deleted.
            
            $stmt = $this->db->prepare("
                SELECT 
                    tc.skin_id,
                    LEAST(tc.sender_id, tc.receiver_id) as p1_id,
                    GREATEST(tc.sender_id, tc.receiver_id) as p2_id,
                    SUM(CASE WHEN tc.is_deleted = 0 THEN 1 ELSE 0 END) as active_count,
                    COUNT(*) as total_count,
                    MAX(tc.created_at) as last_message_at,
                    MAX(tc.negotiation_id) as latest_negotiation_id,
                    s.name as skin_name,
                    s.owner_id as owner_id,
                    s.price as skin_price,
                    s.category as skin_category,
                    u1.username as p1_username,
                    u2.username as p2_username,
                    u_owner.username as owner_username
                FROM trade_conversations tc
                LEFT JOIN skins s ON tc.skin_id = s.skin_id
                LEFT JOIN users u1 ON LEAST(tc.sender_id, tc.receiver_id) = u1.id
                LEFT JOIN users u2 ON GREATEST(tc.sender_id, tc.receiver_id) = u2.id
                LEFT JOIN users u_owner ON s.owner_id = u_owner.id
                GROUP BY tc.skin_id, LEAST(tc.sender_id, tc.receiver_id), GREATEST(tc.sender_id, tc.receiver_id), tc.negotiation_id
                ORDER BY last_message_at DESC
            ");
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $conversations = [];
            
            foreach ($rows as $row) {
                // Determine Buyer and Seller
                $sellerId = $row['owner_id'];
                $sellerName = $row['owner_username'] ?? 'Unknown';
                
                // Identify Buyer
                if ($row['p1_id'] == $sellerId) {
                    $buyerId = $row['p2_id'];
                    $buyerName = $row['p2_username'];
                } else {
                    $buyerId = $row['p1_id'];
                    $buyerName = $row['p1_username'];
                }
                
                // Determine Status based on negotiation_id grouping
                // If negotiation_id is NULL, it's the active conversation (is_deleted=0)
                // If negotiation_id is set, it's a refused/archived session (is_deleted=1 usually)
                
                $negotiationId = $row['latest_negotiation_id']; // This comes from MAX(tc.negotiation_id) which matches group if grouped by it
                
                // Note: SQL GROUP BY handles NULL as a distinct group. 
                // However, MAX(negotiation_id) on a group of NULLs is NULL.
                
                // To be precise, let's trust the grouping.
                // But wait, if we group by negotiation_id, we should select it directly in GROUP BY, not just MAX.
                // Let's rely on $row['latest_negotiation_id'] which will be the value for that group.
                
                $status = empty($negotiationId) ? 'Active' : 'Refused';

                if (!empty($negotiationId)) {
                    try {
                        $check = $this->db->prepare("SELECT COUNT(*) FROM trade_history WHERE negotiation_id = :neg AND action = 'trade'");
                        $check->execute([':neg' => $negotiationId]);
                        if ((int)$check->fetchColumn() > 0) {
                            $status = 'Accepted';
                        }
                    } catch (PDOException $e) {
                        // ignore, keep default status
                    }
                }
                
                $conversations[] = [
                    'type' => strtolower($status),
                    'status' => $status,
                    'skin_id' => $row['skin_id'],
                    'skin_name' => $row['skin_name'] ?? 'Unknown Skin', // Handle deleted skins
                    'skin_price' => $row['skin_price'],
                    'skin_category' => $row['skin_category'],
                    'last_activity' => $row['last_message_at'],
                    'buyer_id' => $buyerId,
                    'buyer_name' => $buyerName,
                    'seller_id' => $sellerId,
                    'seller_name' => $sellerName,
                    'sender_id' => $row['p1_id'],
                    'receiver_id' => $row['p2_id'],
                    'sender_username' => $row['p1_username'],
                    'receiver_username' => $row['p2_username'],
                    'message_count' => $row['total_count'],
                    'negotiation_id' => $row['latest_negotiation_id']
                ];
            }
            
            return $conversations;
        } catch (PDOException $e) {
            error_log("AdminConversationController::getAllConversations error: " . $e->getMessage());
            return [];
        }
    }

    private function getParticipantsFromNegotiation($negotiationId) {
        if (!$negotiationId) return null;
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    tc.sender_id, tc.receiver_id,
                    u1.username as sender_name,
                    u2.username as receiver_name
                FROM trade_conversations tc
                LEFT JOIN users u1 ON tc.sender_id = u1.id
                LEFT JOIN users u2 ON tc.receiver_id = u2.id
                WHERE tc.negotiation_id = ?
                LIMIT 1
            ");
            $stmt->execute([$negotiationId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Get full conversation between two users for a specific skin
     */
    public function getConversationMessages(int $skinId, int $userId1, int $userId2, bool $includeDeleted = true): array {
        try {
            $sql = "
                SELECT tc.*, u.username as sender_username
                FROM trade_conversations tc
                JOIN users u ON tc.sender_id = u.id
                WHERE tc.skin_id = ?
                AND (
                    (tc.sender_id = ? AND tc.receiver_id = ?)
                    OR (tc.sender_id = ? AND tc.receiver_id = ?)
                )
            ";
            
            if (!$includeDeleted) {
                $sql .= " AND tc.is_deleted = 0";
            }
            
            $sql .= " ORDER BY tc.created_at ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$skinId, $userId1, $userId2, $userId2, $userId1]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("AdminConversationController::getConversationMessages error: " . $e->getMessage());
            return [];
        }
    }
}
