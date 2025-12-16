<?php
/**
 * Cart Model
 * Handles all database operations for shopping cart
 */

class CartModel {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDB();
    }
    
    /**
     * Get or create cart for session
     */
    public function getCart($sessionId) {
        $stmt = $this->pdo->prepare("SELECT * FROM panier WHERE session_id = ?");
        $stmt->execute([$sessionId]);
        $cart = $stmt->fetch();
        
        if (!$cart) {
            // Create new cart
            $stmt = $this->pdo->prepare("INSERT INTO panier (session_id) VALUES (?)");
            $stmt->execute([$sessionId]);
            $cartId = $this->pdo->lastInsertId();
            
            $stmt = $this->pdo->prepare("SELECT * FROM panier WHERE id = ?");
            $stmt->execute([$cartId]);
            $cart = $stmt->fetch();
        }
        
        return $cart;
    }
    
    /**
     * Get all items in cart
     */
    public function getCartItems($cartId) {
        $stmt = $this->pdo->prepare("
            SELECT ci.*, p.name, p.description 
            FROM panier_items ci
            JOIN produit p ON ci.product_id = p.produit_id
            WHERE ci.panier_id = ?
        ");
        $stmt->execute([$cartId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Add item to cart
     */
    public function addItem($cartId, $productId, $quantity = 1) {
        // Get product price
        $stmt = $this->pdo->prepare("SELECT price FROM produit WHERE produit_id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();
        
        if (!$product) {
            return false;
        }
        
        // Check if item already exists
        $stmt = $this->pdo->prepare("
            SELECT * FROM panier_items 
            WHERE panier_id = ? AND product_id = ?
        ");
        $stmt->execute([$cartId, $productId]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Update quantity
            $newQty = $existing['quantity'] + $quantity;
            $stmt = $this->pdo->prepare("
                UPDATE panier_items 
                SET quantity = ? 
                WHERE id = ?
            ");
            return $stmt->execute([$newQty, $existing['id']]);
        } else {
            // Insert new item
            $stmt = $this->pdo->prepare("
                INSERT INTO panier_items (panier_id, product_id, quantity, price)
                VALUES (?, ?, ?, ?)
            ");
            return $stmt->execute([$cartId, $productId, $quantity, $product['price']]);
        }
    }
    
    /**
     * Update item quantity
     */
    public function updateQuantity($itemId, $quantity) {
        if ($quantity <= 0) {
            return $this->removeItem($itemId);
        }
        
        $stmt = $this->pdo->prepare("UPDATE panier_items SET quantity = ? WHERE id = ?");
        return $stmt->execute([$quantity, $itemId]);
    }
    
    /**
     * Remove item from cart
     */
    public function removeItem($itemId) {
        $stmt = $this->pdo->prepare("DELETE FROM panier_items WHERE id = ?");
        return $stmt->execute([$itemId]);
    }
    
    /**
     * Clear cart
     */
    public function clearCart($cartId) {
        $stmt = $this->pdo->prepare("DELETE FROM panier_items WHERE panier_id = ?");
        return $stmt->execute([$cartId]);
    }
    
    /**
     * Get cart total
     */
    public function getCartTotal($cartId) {
        $stmt = $this->pdo->prepare("
            SELECT SUM(quantity * price) as total
            FROM panier_items
            WHERE panier_id = ?
        ");
        $stmt->execute([$cartId]);
        $result = $stmt->fetch();
        return $result['total'] ?? 0;
    }
    
    /**
     * Get cart item count
     */
    public function getCartCount($cartId) {
        $stmt = $this->pdo->prepare("
            SELECT SUM(quantity) as count
            FROM panier_items
            WHERE panier_id = ?
        ");
        $stmt->execute([$cartId]);
        $result = $stmt->fetch();
        return $result['count'] ?? 0;
    }
}
