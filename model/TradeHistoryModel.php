<?php
/**
 * Trade History Model
 * Handles trade history database operations
 */

require_once __DIR__ . '/config.php';

class TradeHistoryModel {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
        $this->upgradeSchema();
    }

    /**
     * Upgrade schema to support new features
     */
    private function upgradeSchema(): void {
        try {
            // Check if 'bought' is in the ENUM for trade_history
            $stmt = $this->db->query("SHOW COLUMNS FROM trade_history LIKE 'action'");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && strpos($row['Type'], "'bought'") === false) {
                $this->db->exec("ALTER TABLE trade_history MODIFY COLUMN action ENUM('created', 'updated', 'deleted', 'buy', 'bought') NOT NULL");
            }

            // Check if 'bought' is in the ENUM for trade_history_trading_view
            $stmt = $this->db->query("SHOW COLUMNS FROM trade_history_trading_view LIKE 'action'");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && strpos($row['Type'], "'bought'") === false) {
                $this->db->exec("ALTER TABLE trade_history_trading_view MODIFY COLUMN action ENUM('created', 'updated', 'deleted', 'buy', 'bought') NOT NULL");
            }
        } catch (PDOException $e) {
            error_log("TradeHistoryModel::upgradeSchema error: " . $e->getMessage());
        }
    }
    
    /**
     * Ensure trade_history table exists
     */
    private function ensureTradeHistoryTable(): void {
        try {
            $tableCheck = $this->db->query("SHOW TABLES LIKE 'trade_history'");
            if ($tableCheck->rowCount() == 0) {
                $createTableSQL = "
                    CREATE TABLE trade_history (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT NOT NULL,
                        skin_id INT NOT NULL,
                        action ENUM('created', 'updated', 'deleted', 'buy', 'bought') NOT NULL,
                        skin_name VARCHAR(255) NOT NULL,
                        skin_price DECIMAL(10,2) NOT NULL,
                        skin_category VARCHAR(50) NOT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        INDEX idx_user_id (user_id),
                        INDEX idx_created_at (created_at)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
                ";
                $this->db->exec($createTableSQL);
            }
        } catch (PDOException $e) {
            error_log("TradeHistoryModel::ensureTradeHistoryTable error: " . $e->getMessage());
        }
    }
    
    /**
     * Ensure trade_history_trading_view table exists
     */
    private function ensureTradingViewTable(): void {
        try {
            $tableCheck = $this->db->query("SHOW TABLES LIKE 'trade_history_trading_view'");
            if ($tableCheck->rowCount() == 0) {
                $createTableSQL = "
                    CREATE TABLE trade_history_trading_view (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT NOT NULL,
                        skin_id INT NOT NULL,
                        action ENUM('created', 'updated', 'deleted', 'buy', 'bought') NOT NULL,
                        skin_name VARCHAR(255) NOT NULL,
                        skin_price DECIMAL(10,2) NOT NULL,
                        skin_category VARCHAR(50) NOT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        INDEX idx_user_id (user_id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
                ";
                $this->db->exec($createTableSQL);
            }
        } catch (PDOException $e) {
            error_log("TradeHistoryModel::ensureTradingViewTable error: " . $e->getMessage());
        }
    }
    
    /**
     * Log trade history
     * 
     * @param int $userId
     * @param int $skinId
     * @param string $action
     * @param string $skinName
     * @param float $skinPrice
     * @param string $skinCategory
     * @return bool
     */
    public function logTradeHistory(int $userId, int $skinId, string $action, string $skinName, float $skinPrice, string $skinCategory): bool {
        $this->ensureTradeHistoryTable();
        $this->ensureTradingViewTable();
        
        $success = true;

        // Insert into trade_history table
        try {
            $stmt = $this->db->prepare("
                INSERT INTO trade_history (user_id, skin_id, action, skin_name, skin_price, skin_category, created_at)
                VALUES (:user_id, :skin_id, :action, :skin_name, :skin_price, :skin_category, NOW())
            ");
            
            $stmt->execute([
                ':user_id' => $userId,
                ':skin_id' => $skinId,
                ':action' => $action,
                ':skin_name' => $skinName,
                ':skin_price' => $skinPrice,
                ':skin_category' => $skinCategory
            ]);
        } catch (PDOException $e) {
            error_log("TradeHistoryModel::logTradeHistory - trade_history error: " . $e->getMessage());
            $success = false;
        }

        // Insert into trade_history_trading_view table
        try {
            $tradingStmt = $this->db->prepare("
                INSERT INTO trade_history_trading_view (user_id, skin_id, action, skin_name, skin_price, skin_category, created_at)
                VALUES (:user_id, :skin_id, :action, :skin_name, :skin_price, :skin_category, NOW())
            ");
            
            $tradingStmt->execute([
                ':user_id' => $userId,
                ':skin_id' => $skinId,
                ':action' => $action,
                ':skin_name' => $skinName,
                ':skin_price' => $skinPrice,
                ':skin_category' => $skinCategory
            ]);
        } catch (PDOException $e) {
            error_log("TradeHistoryModel::logTradeHistory - trade_history_trading_view error: " . $e->getMessage());
        }
        
        return $success;
    }
    
    /**
     * @param string $username
     * @return array
     */
    public function getTradeHistoryByUsername(string $username): array {
        try {
            $this->ensureTradingViewTable();
            
            $stmt = $this->db->prepare("
                SELECT th.*, u.username
                FROM trade_history_trading_view th
                JOIN users u ON th.user_id = u.id
                WHERE u.username = :username
                ORDER BY th.created_at DESC
            ");
            $stmt->execute([':username' => $username]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("TradeHistoryModel::getTradeHistoryByUsername error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Clear trade history for a user
     * 
     * @param int $userId
     * @return bool
     */
    public function clearTradeHistory(int $userId): bool {
        try {
            $this->ensureTradingViewTable();
            
            $stmt = $this->db->prepare("DELETE FROM trade_history_trading_view WHERE user_id = :user_id");
            return $stmt->execute([':user_id' => $userId]);
        } catch (PDOException $e) {
            error_log("TradeHistoryModel::clearTradeHistory error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all trade history with user information (for admin)
     * 
     * @return array
     */
    public function getAllTradeHistory(): array {
        try {
            $this->ensureTradeHistoryTable();
            
            $stmt = $this->db->prepare("
                SELECT th.*, u.username, u.email 
                FROM trade_history th 
                JOIN users u ON th.user_id = u.id 
                ORDER BY th.created_at DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("TradeHistoryModel::getAllTradeHistory error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get statistics for admin dashboard
     * 
     * @return array
     */
    public function getStatistics(): array {
        try {
            $this->ensureTradeHistoryTable();
            
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_trades,
                    (SELECT COUNT(DISTINCT owner_id) FROM skins WHERE is_listed = 1) as total_users,
                    (SELECT COUNT(*) FROM skins WHERE is_listed = 1) as total_skins,
                    COALESCE(
                        SUM(CASE WHEN action = 'finished' THEN skin_price ELSE 0 END) +
                        SUM(CASE WHEN action = 'bought' THEN skin_price * 0.20 ELSE 0 END),
                        0
                    ) as total_value
                FROM trade_history
            ");
            $stmt->execute();
            $statsData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($statsData) {
                return [
                    'total_trades' => (int)$statsData['total_trades'],
                    'total_users' => (int)$statsData['total_users'],
                    'total_skins' => (int)$statsData['total_skins'],
                    'total_value' => (float)$statsData['total_value']
                ];
            }
            
            return [
                'total_trades' => 0,
                'total_users' => 0,
                'total_skins' => 0,
                'total_value' => 0.0
            ];
        } catch (PDOException $e) {
            error_log("TradeHistoryModel::getStatistics error: " . $e->getMessage());
            return [
                'total_trades' => 0,
                'total_users' => 0,
                'total_skins' => 0,
                'total_value' => 0.0
            ];
        }
    }
}

