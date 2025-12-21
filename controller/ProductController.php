<?php
require_once __DIR__ . '/../model/config.php';
require_once __DIR__ . '/../model/Product.php';

class ProductController
{

    public function getAllProducts()
    {
        return $this->getFilteredProducts([], 'created_at', 'DESC');
    }

    public function getFilteredProducts($filters = [], $sortBy = 'created_at', $sortOrder = 'DESC', $limit = null, $offset = 0)
    {
        try {
            $pdo = getDB();
            $sql = "SELECT * FROM produit WHERE 1=1";
            $params = [];

            // ========== DEBUG LOGS ==========
            error_log("=== CONTROLLER DEBUG START ===");
            error_log("Raw filters: " . json_encode($filters));
            
            // Search Filter - VERSION ULTRA FLEXIBLE
$searchValue = isset($filters['search']) ? trim($filters['search']) : '';
error_log("Search value extracted: '$searchValue'");

if (!empty($searchValue)) {
    $searchTerm = strtolower($searchValue);
    error_log("Search term lowercase: '$searchTerm'");
    
    // Diviser la recherche en mots individuels
    $searchWords = explode(' ', $searchTerm);
    $searchWords = array_filter($searchWords); // Enlever les espaces vides
    
    if (!empty($searchWords)) {
        $searchConditions = [];
        
        foreach ($searchWords as $index => $word) {
            $paramName = ":search_word_$index";
            $searchConditions[] = "(LOWER(name) LIKE $paramName OR LOWER(COALESCE(description, '')) LIKE $paramName)";
            $params[$paramName] = '%' . $word . '%';
        }
        
        $sql .= " AND (" . implode(' OR ', $searchConditions) . ")";
        
        error_log("Search words: " . implode(', ', $searchWords));
        error_log("SQL after search: $sql");
    }
}

            // Category Filter - Exact match
            if (!empty($filters['category']) && $filters['category'] !== 'all') {
                $sql .= " AND category = :category";
                $params[':category'] = $filters['category'];
                error_log("Category filter: " . $filters['category']);
            }

            // Price Range (optional, for future)
            if (!empty($filters['min_price'])) {
                $sql .= " AND price >= :min_price";
                $params[':min_price'] = $filters['min_price'];
            }
            if (!empty($filters['max_price'])) {
                $sql .= " AND price <= :max_price";
                $params[':max_price'] = $filters['max_price'];
            }

            // Sorting - IMPROVED
            $allowedSorts = ['price', 'name', 'created_at', 'stock', 'category'];
            if (!in_array($sortBy, $allowedSorts)) {
                $sortBy = 'created_at';
            }
            
            $sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';

            // Special handling for category sorting
            if ($sortBy === 'category') {
                // Sort by category first (ASC), then by name within each category
                $sql .= " ORDER BY category ASC, name ASC";
            } else {
                $sql .= " ORDER BY $sortBy $sortOrder";
            }

            // Pagination
            if ($limit !== null) {
                $sql .= " LIMIT :limit OFFSET :offset";
            }

            error_log("FINAL SQL: $sql");
            error_log("FINAL PARAMS: " . json_encode($params));

            $stmt = $pdo->prepare($sql);

            // Bind parameters
            foreach ($params as $key => $val) {
                error_log("Binding $key = $val");
                $stmt->bindValue($key, $val, PDO::PARAM_STR);
            }
            
            if ($limit !== null) {
                $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
                $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
            }

            $stmt->execute();

            $products = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $products[] = new Product(
                    $row['produit_id'],
                    $row['publisher_id'],
                    $row['name'],
                    $row['description'],
                    $row['price'],
                    $row['stock'],
                    $row['category'],
                    $row['brand'],
                    $row['image'],
                    $row['created_at'],
                    $row['updated_at']
                );
            }
            
            error_log("Products found: " . count($products));
            error_log("=== CONTROLLER DEBUG END ===");
            
            return $products;
            
        } catch (PDOException $e) {
            error_log("❌ PDO ERROR in getFilteredProducts: " . $e->getMessage());
            error_log("SQL was: " . ($sql ?? 'NOT SET'));
            return [];
        }
    }

    public function countProducts($filters = [])
{
    try {
        $pdo = getDB();
        $sql = "SELECT COUNT(*) FROM produit WHERE 1=1";
        $params = [];

        // Search Filter - VERSION ULTRA FLEXIBLE
        $searchValue = isset($filters['search']) ? trim($filters['search']) : '';
        
        if (!empty($searchValue)) {
            $searchTerm = strtolower($searchValue);
            $searchWords = explode(' ', $searchTerm);
            $searchWords = array_filter($searchWords);
            
            if (!empty($searchWords)) {
                $searchConditions = [];
                
                foreach ($searchWords as $index => $word) {
                    $paramName = ":search_word_$index";
                    $searchConditions[] = "(LOWER(name) LIKE $paramName OR LOWER(COALESCE(description, '')) LIKE $paramName)";
                    $params[$paramName] = '%' . $word . '%';
                }
                
                $sql .= " AND (" . implode(' OR ', $searchConditions) . ")";
            }
        }
        
        if (!empty($filters['category']) && $filters['category'] !== 'all') {
            $sql .= " AND category = :category";
            $params[':category'] = $filters['category'];
        }

        error_log("COUNT SQL: $sql");
        error_log("COUNT PARAMS: " . json_encode($params));

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $count = $stmt->fetchColumn();
        
        error_log("COUNT RESULT: $count");
        
        return $count;
    } catch (PDOException $e) {
        error_log("❌ COUNT ERROR: " . $e->getMessage());
        return 0;
    }
}
    public function getProductById($id)
    {
        try {
            $pdo = getDB();
            $sql = "SELECT * FROM produit WHERE produit_id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':id' => $id]);

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                return new Product(
                    $row['produit_id'],
                    $row['publisher_id'],
                    $row['name'],
                    $row['description'],
                    $row['price'],
                    $row['stock'],
                    $row['category'],
                    $row['brand'],
                    $row['image'],
                    $row['created_at'],
                    $row['updated_at']
                );
            }
            return null;
        } catch (PDOException $e) {
            error_log("Error fetching product by id: " . $e->getMessage());
            return null;
        }
    }

    // Admin Methods
    public function deleteProduct($id)
    {
        try {
            $pdo = getDB();
            $sql = "DELETE FROM produit WHERE produit_id = :id";
            $stmt = $pdo->prepare($sql);
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log("Error deleting product: " . $e->getMessage());
            return false;
        }
    }

    public function updateProduct($id, $data)
    {
        try {
            $pdo = getDB();
            $fields = [];
            $params = [':id' => $id];

            if (isset($data['name'])) {
                $fields[] = 'name = :name';
                $params[':name'] = $data['name'];
            }
            if (isset($data['price'])) {
                $fields[] = 'price = :price';
                $params[':price'] = $data['price'];
            }
            if (isset($data['stock'])) {
                $fields[] = 'stock = :stock';
                $params[':stock'] = $data['stock'];
            }
            if (isset($data['category'])) {
                $fields[] = 'category = :category';
                $params[':category'] = $data['category'];
            }
            if (isset($data['image'])) {
                $fields[] = 'image = :image';
                $params[':image'] = $data['image'];
            }

            if (empty($fields))
                return false;

            $sql = "UPDATE produit SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE produit_id = :id";
            $stmt = $pdo->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Error updating product: " . $e->getMessage());
            return false;
        }
    }

    public function getPurchaseHistory()
    {
        try {
            $pdo = getDB();
            $sql = "SELECT p.purchase_id, p.transactionId, p.purchaseDate, p.amount, u.username, pr.name as product_name 
                    FROM purchase p 
                    JOIN users u ON p.user_id = u.id 
                    JOIN produit pr ON p.produit_id = pr.produit_id 
                    ORDER BY p.purchaseDate DESC";
            $stmt = $pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching purchase history: " . $e->getMessage());
            return [];
        }
    }

    public function addProduct($data)
    {
        try {
            $pdo = getDB();
            $sql = "INSERT INTO produit (name, description, price, stock, category, image, publisher_id) 
                    VALUES (:name, :description, :price, :stock, :category, :image, :publisher_id)";
            $stmt = $pdo->prepare($sql);

            return $stmt->execute([
                ':name' => $data['name'],
                ':description' => $data['description'] ?? '',
                ':price' => $data['price'],
                ':stock' => $data['stock'],
                ':category' => $data['category'],
                ':image' => $data['image'] ?? null,
                ':publisher_id' => $data['publisher_id'] ?? 1
            ]);
        } catch (PDOException $e) {
            error_log("Error adding product: " . $e->getMessage());
            return false;
        }
    }

    public function buyProduct($userId, $productId)
    {
        try {
            $pdo = getDB();

            // 1. Get Product and Check Stock
            $stmt = $pdo->prepare("SELECT price, stock, name FROM produit WHERE produit_id = :id");
            $stmt->execute([':id' => $productId]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$product) {
                return ['success' => false, 'error' => "Product ID $productId not found"];
            }

            if ($product['stock'] <= 0) {
                return ['success' => false, 'error' => "{$product['name']} is out of stock"];
            }

            // 2. Transaction
            $pdo->beginTransaction();

            // Deduct Stock
            $upd = $pdo->prepare("UPDATE produit SET stock = stock - 1 WHERE produit_id = :id");
            $upd->execute([':id' => $productId]);

            // Record Purchase
            $transactionId = uniqid('txn_prod_');
            $ins = $pdo->prepare("INSERT INTO purchase (transactionId, purchaseDate, amount, user_id, produit_id) VALUES (:tid, NOW(), :amount, :uid, :pid)");
            $ins->execute([
                ':tid' => $transactionId,
                ':amount' => $product['price'],
                ':uid' => $userId,
                ':pid' => $productId
            ]);

            $pdo->commit();
            return ['success' => true, 'name' => $product['name']];

        } catch (Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("BuyProduct Error: " . $e->getMessage());
            return ['success' => false, 'error' => "Database error during purchase"];
        }
    }

    /**
     * Get category statistics for dashboard/analytics
     */
    public function getCategoryStats()
    {
        try {
            $pdo = getDB();
            $sql = "SELECT category, 
                           COUNT(*) as product_count, 
                           SUM(stock) as total_stock,
                           AVG(price) as avg_price
                    FROM produit 
                    GROUP BY category 
                    ORDER BY product_count DESC";
            $stmt = $pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching category stats: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get low stock products (stock <= threshold)
     */
    public function getLowStockProducts($threshold = 5)
    {
        try {
            $pdo = getDB();
            $sql = "SELECT * FROM produit WHERE stock <= :threshold ORDER BY stock ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':threshold' => $threshold]);

            $products = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $products[] = new Product(
                    $row['produit_id'],
                    $row['publisher_id'],
                    $row['name'],
                    $row['description'],
                    $row['price'],
                    $row['stock'],
                    $row['category'],
                    $row['brand'],
                    $row['image'],
                    $row['created_at'],
                    $row['updated_at']
                );
            }
            return $products;
        } catch (PDOException $e) {
            error_log("Error fetching low stock products: " . $e->getMessage());
            return [];
        }
    }
}
?>