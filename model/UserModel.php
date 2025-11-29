<?php
/**
 * User Model
 * Handles user-related database operations
 */

require_once __DIR__ . '/config.php';

class UserModel {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Get user ID by username
     * 
     * @param string $username
     * @return int|null
     */
    public function getUserIdByUsername(string $username): ?int {
        try {
            $stmt = $this->db->prepare("SELECT id FROM users WHERE username = :username");
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $user ? (int)$user['id'] : null;
        } catch (PDOException $e) {
            error_log("UserModel::getUserIdByUsername error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get user by username
     * 
     * @param string $username
     * @return array|null
     */
    public function getUserByUsername(string $username): ?array {
        try {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE username = :username");
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $user ?: null;
        } catch (PDOException $e) {
            error_log("UserModel::getUserByUsername error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get user by ID
     * 
     * @param int $userId
     * @return array|null
     */
    public function getUserById(int $userId): ?array {
        try {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :user_id");
            $stmt->execute([':user_id' => $userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $user ?: null;
        } catch (PDOException $e) {
            error_log("UserModel::getUserById error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get current logged-in user from session
     * 
     * @return array|null
     */
    public function getCurrentUserFromSession(): ?array {
        if (!isLoggedIn()) {
            return null;
        }
        
        $userId = getCurrentUserId();
        if ($userId === null) {
            return null;
        }
        
        return $this->getUserById($userId);
    }
    
    /**
     * Get username by user ID
     * 
     * @param int $userId
     * @return string|null
     */
    public function getUsernameById(int $userId): ?string {
        try {
            $stmt = $this->db->prepare("SELECT username FROM users WHERE id = :user_id");
            $stmt->execute([':user_id' => $userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $user ? (string)$user['username'] : null;
        } catch (PDOException $e) {
            error_log("UserModel::getUsernameById error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Verify user exists and is active
     * 
     * @param string $username
     * @return bool
     */
    public function userExistsAndActive(string $username): bool {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE username = :username AND status = 'active'");
            $stmt->execute([':username' => $username]);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("UserModel::userExistsAndActive error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Ensure user is linked to database (verify session user exists)
     * 
     * @return bool
     */
    public function verifySessionUserLinked(): bool {
        if (!isLoggedIn()) {
            return false;
        }
        
        $userId = getCurrentUserId();
        $username = getCurrentUsername();
        
        if ($userId === null || $username === null) {
            return false;
        }
        
        // Verify user exists in database
        $user = $this->getUserById($userId);
        if (!$user) {
            return false;
        }
        
        // Verify username matches
        if ($user['username'] !== $username) {
            return false;
        }
        
        // Verify user is active
        if ($user['status'] !== 'active') {
            return false;
        }
        
        return true;
    }
}

