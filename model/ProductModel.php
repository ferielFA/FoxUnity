<?php
/**
 * Product Model
 * Handles all database operations for products
 */

class ProductModel {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDB();
    }
    
    /**
     * Get all products from database
     * @return array Array of all products
     */
    public function getAllProducts() {
        try {
            // Try ordering by id first
            try {
                $stmt = $this->pdo->query("SELECT * FROM produit ORDER BY produit_id DESC");
            } catch (PDOException $e) {
                // If id doesn't exist, try created_at or no ordering
                try {
                    $stmt = $this->pdo->query("SELECT * FROM produit ORDER BY created_at DESC");
                } catch (PDOException $e2) {
                    $stmt = $this->pdo->query("SELECT * FROM produit");
                }
            }
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error fetching products: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get a single product by ID
     * @param int $id Product ID
     * @return array|null Product data or null if not found
     */
    public function getProductById($id) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM produit WHERE produit_id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Error fetching product: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Create a new product
     * @param string $name Product name
     * @param string $description Product description
     * @param float $price Product price
     * @param string $image Image URL/path
     * @return bool True on success, false on failure
     */
    public function createProduct($name, $description, $price, $image) {
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO produit (name, description, price, image) VALUES (?, ?, ?, ?)"
            );
            return $stmt->execute([$name, $description, $price, $image]);
        } catch (PDOException $e) {
            error_log("Error creating product: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update an existing product
     * @param int $id Product ID
     * @param string $name Product name
     * @param string $description Product description
     * @param float $price Product price
     * @param string $image Image URL/path
     * @return bool True on success, false on failure
     */
    public function updateProduct($id, $name, $description, $price, $image) {
        try {
            $stmt = $this->pdo->prepare(
                "UPDATE produit SET name = ?, description = ?, price = ?, image = ? WHERE produit_id = ?"
            );
            return $stmt->execute([$name, $description, $price, $image, $id]);
        } catch (PDOException $e) {
            error_log("Error updating product: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete a product
     * @param int $id Product ID
     * @return bool True on success, false on failure
     */
    public function deleteProduct($id) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM produit WHERE produit_id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Error deleting product: " . $e->getMessage());
            return false;
        }
    }
}
?>
