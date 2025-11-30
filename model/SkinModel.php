<?php

require_once __DIR__ . '/config.php';

class SkinModel {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * 
     * 
     * @return array
     */
    public function getAllSkins(): array {
        try {
            $stmt = $this->db->prepare("
                SELECT s.*, u.username
                FROM skins s
                LEFT JOIN users u ON s.owner_id = u.id
                ORDER BY s.created_at DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("SkinModel::getAllSkins error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * 
     * 
     * @param string $username
     * @return array
     */
    public function getSkinsByUsername(string $username): array {
        try {
            $stmt = $this->db->prepare("
                SELECT s.*, u.username
                FROM skins s
                JOIN users u ON s.owner_id = u.id
                WHERE u.username = :username
                ORDER BY s.created_at DESC
            ");
            $stmt->execute([':username' => $username]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("SkinModel::getSkinsByUsername error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     *
     * 
     * @param int $skinId
     * @return array|null
     */
    public function getSkinById(int $skinId): ?array {
        try {
            $stmt = $this->db->prepare("
                SELECT s.*, u.username as seller_username, u.id as seller_id
                FROM skins s
                JOIN users u ON s.owner_id = u.id
                WHERE s.skin_id = :skin_id
            ");
            $stmt->execute([':skin_id' => $skinId]);
            $skin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $skin ?: null;
        } catch (PDOException $e) {
            error_log("SkinModel::getSkinById error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * 
     * 
     * @param int $skinId
     * @param string $username
     * @return array|null
     */
    public function getSkinByOwner(int $skinId, string $username): ?array {
        try {
            $stmt = $this->db->prepare("
                SELECT s.*
                FROM skins s
                JOIN users u ON s.owner_id = u.id
                WHERE s.skin_id = :skin_id AND u.username = :username
            ");
            $stmt->execute([
                ':skin_id' => $skinId,
                ':username' => $username
            ]);
            $skin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $skin ?: null;
        } catch (PDOException $e) {
            error_log("SkinModel::getSkinByOwner error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * 
     * 
     * @param int $ownerId
     * @param string $name
     * @param float $price
     * @param string|null $imagePath
     * @param string $description
     * @param string $category
     * @return int|null Returns the new skin ID or null on failure
     */
    public function createSkin(int $ownerId, string $name, float $price, ?string $imagePath, string $description, string $category): ?int {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO skins (owner_id, name, price, image, description, category, created_at)
                VALUES (:owner, :name, :price, :image, :description, :category, NOW())
            ");
            
            $stmt->execute([
                ':owner' => $ownerId,
                ':name'  => $name,
                ':price' => $price,
                ':image' => $imagePath,
                ':description' => $description,
                ':category' => $category
            ]);
            
            return (int)$this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("SkinModel::createSkin error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * 
     * 
     * @param int $skinId
     * @param string $name
     * @param float $price
     * @param string $description
     * @param string $category
     * @return bool
     */
    public function updateSkin(int $skinId, string $name, float $price, string $description, string $category): bool {
        try {
            $stmt = $this->db->prepare("
                UPDATE skins
                SET name = :name, price = :price, description = :description, category = :category
                WHERE skin_id = :skin_id
            ");
            
            return $stmt->execute([
                ':name' => $name,
                ':price' => $price,
                ':description' => $description,
                ':category' => $category,
                ':skin_id' => $skinId
            ]);
        } catch (PDOException $e) {
            error_log("SkinModel::updateSkin error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete a skin
     * 
     * @param int $skinId
     * @return bool
     */
    public function deleteSkin(int $skinId): bool {
        try {
            $stmt = $this->db->prepare("DELETE FROM skins WHERE skin_id = :skin_id");
            return $stmt->execute([':skin_id' => $skinId]);
        } catch (PDOException $e) {
            error_log("SkinModel::deleteSkin error: " . $e->getMessage());
            return false;
        }
    }
}

