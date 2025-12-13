<?php
/**
 * Trade History Controller (moved from model)
 * Provides trade history operations (previously a model)
 */

require_once __DIR__ . '/../model/config.php';

class TradeHistoryController {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
        $this->upgradeSchema();
    }

    private function upgradeSchema(): void {
        try {
            // Check ENUM values
            $stmt = $this->db->query("SHOW COLUMNS FROM trade_history LIKE 'action'");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                // Ensure 'trade' and 'negotiation_refused' exist in ENUM
                $type = $row['Type'] ?? '';
                $needsAlter = (strpos($type, "'trade'") === false) || (strpos($type, "'negotiation_refused'") === false);
                if ($needsAlter) {
                    $this->db->exec("ALTER TABLE trade_history MODIFY COLUMN action ENUM('created', 'updated', 'deleted', 'buy', 'bought', 'negotiation_refused', 'trade') NOT NULL");
                }
            }

            // Check for negotiation_id column
            $colCheck = $this->db->query("SHOW COLUMNS FROM trade_history LIKE 'negotiation_id'");
            if ($colCheck->rowCount() == 0) {
                $this->db->exec("ALTER TABLE trade_history ADD COLUMN negotiation_id VARCHAR(50) DEFAULT NULL AFTER skin_category");
                $this->db->exec("ALTER TABLE trade_history ADD INDEX idx_negotiation_id (negotiation_id)");
            }
        } catch (PDOException $e) {
            error_log("TradeHistoryController::upgradeSchema error: " . $e->getMessage());
        }
    }

    private function ensureTradeHistoryTable(): void {
        try {
            $tableCheck = $this->db->query("SHOW TABLES LIKE 'trade_history'");
            if ($tableCheck->rowCount() == 0) {
                $createTableSQL = "
                    CREATE TABLE trade_history (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        user_id INT NOT NULL,
                        skin_id INT NOT NULL,
                        action ENUM('created', 'updated', 'deleted', 'buy', 'bought', 'negotiation_refused', 'trade') NOT NULL,
                        skin_name VARCHAR(255) NOT NULL,
                        skin_price DECIMAL(10,2) NOT NULL,
                        skin_category VARCHAR(50) NOT NULL,
                        negotiation_id VARCHAR(50) DEFAULT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        visible_in_trading TINYINT(1) DEFAULT 1,
                        INDEX idx_user_id (user_id),
                        INDEX idx_created_at (created_at),
                        INDEX idx_negotiation_id (negotiation_id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
                ";
                $this->db->exec($createTableSQL);
            }
        } catch (PDOException $e) {
            error_log("TradeHistoryController::ensureTradeHistoryTable error: " . $e->getMessage());
        }
    }

    public function logTradeHistory(int $userId, int $skinId, string $action, string $skinName, float $skinPrice, string $skinCategory, ?string $negotiationId = null): bool {
        $this->ensureTradeHistoryTable();
        $success = true;

        try {
            $stmt = $this->db->prepare(
                "INSERT INTO trade_history (user_id, skin_id, action, skin_name, skin_price, skin_category, negotiation_id, created_at)
                VALUES (:user_id, :skin_id, :action, :skin_name, :skin_price, :skin_category, :negotiation_id, NOW())"
            );
            $stmt->execute([
                ':user_id' => $userId,
                ':skin_id' => $skinId,
                ':action' => $action,
                ':skin_name' => $skinName,
                ':skin_price' => $skinPrice,
                ':skin_category' => $skinCategory,
                ':negotiation_id' => $negotiationId
            ]);
        } catch (PDOException $e) {
            error_log("TradeHistoryController::logTradeHistory - trade_history error: " . $e->getMessage());
            $success = false;
        }

        return $success;
    }

    public function getTradeHistoryByUsername(string $username): array {
        try {
            $this->ensureTradeHistoryTable();
            $stmt = $this->db->prepare(
                "SELECT th.*, u.username
                FROM trade_history th
                JOIN users u ON th.user_id = u.id
                WHERE u.username = :username
                ORDER BY th.created_at DESC"
            );
            $stmt->execute([':username' => $username]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("TradeHistoryController::getTradeHistoryByUsername error: " . $e->getMessage());
            return [];
        }
    }

    public function clearTradeHistory(int $userId, string $type = 'all'): bool {
        try {
            $this->ensureTradeHistoryTable();
            
            $sql = "DELETE FROM trade_history WHERE user_id = :user_id";
            
            if ($type === 'negotiations') {
                $sql .= " AND action IN ('negotiation_refused', 'trade')";
            } elseif ($type === 'standard') {
                $sql .= " AND action != 'negotiation_refused'";
            }
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':user_id' => $userId]);
        } catch (PDOException $e) {
            error_log("TradeHistoryController::clearTradeHistory error: " . $e->getMessage());
            return false;
        }
    }

    public function getAllTradeHistory(): array {
        try {
            $this->ensureTradeHistoryTable();
            $stmt = $this->db->prepare(
                "SELECT th.*, u.username, u.email 
                FROM trade_history th 
                JOIN users u ON th.user_id = u.id 
                ORDER BY th.created_at DESC"
            );
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("TradeHistoryController::getAllTradeHistory error: " . $e->getMessage());
            return [];
        }
    }

    private function buildFilterQuery(array $filters): array {
        $where = [];
        $params = [];

        if (!empty($filters['search'])) {
            $where[] = "(th.skin_name LIKE :search OR u.username LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['action']) && $filters['action'] !== 'all') {
            if ($filters['action'] === 'finished') {
                $where[] = "th.action IN ('finished', 'bought', 'buy', 'trade')";
            } else {
                $where[] = "th.action = :action";
                $params[':action'] = $filters['action'];
            }
        }

        if (!empty($filters['game']) && $filters['game'] !== 'all') {
            $where[] = "th.skin_category = :game";
            $params[':game'] = $filters['game'];
        }

        $sql = "";
        if (!empty($where)) {
            $sql = " WHERE " . implode(" AND ", $where);
        }

        return ['sql' => $sql, 'params' => $params];
    }

    public function countAllTradeHistory(array $filters = []): int {
        try {
            $this->ensureTradeHistoryTable();
            
            $queryParts = $this->buildFilterQuery($filters);
            $sql = "SELECT COUNT(th.id) 
                    FROM trade_history th 
                    JOIN users u ON th.user_id = u.id" . $queryParts['sql'];
            
            $stmt = $this->db->prepare($sql);
            foreach ($queryParts['params'] as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("TradeHistoryController::countAllTradeHistory error: " . $e->getMessage());
            return 0;
        }
    }

    public function getPaginatedTradeHistory(int $limit, int $offset, array $filters = []): array {
        try {
            $this->ensureTradeHistoryTable();
            
            $queryParts = $this->buildFilterQuery($filters);
            $sql = "SELECT th.*, u.username, u.email 
                    FROM trade_history th 
                    JOIN users u ON th.user_id = u.id" . 
                    $queryParts['sql'] . 
                    " ORDER BY th.created_at DESC 
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($sql);
            
            // Bind filter params
            foreach ($queryParts['params'] as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            // Bind pagination params
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("TradeHistoryController::getPaginatedTradeHistory error: " . $e->getMessage());
            return [];
        }
    }

    public function getStatistics(): array {
        try {
            $this->ensureTradeHistoryTable();
            $stmt = $this->db->prepare(
                "SELECT 
                    COUNT(*) as total_trades,
                    (SELECT COUNT(DISTINCT owner_id) FROM skins WHERE is_listed = 1) as total_users,
                    (SELECT COUNT(*) FROM skins WHERE is_listed = 1) as total_skins,
                    COALESCE(
                        SUM(CASE WHEN action = 'finished' THEN skin_price ELSE 0 END) +
                        SUM(CASE WHEN action = 'bought' THEN skin_price * 0.20 ELSE 0 END),
                        0
                    ) as total_value
                FROM trade_history"
            );
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
            error_log("TradeHistoryController::getStatistics error: " . $e->getMessage());
            return [
                'total_trades' => 0,
                'total_users' => 0,
                'total_skins' => 0,
                'total_value' => 0.0
            ];
        }
    }
}
