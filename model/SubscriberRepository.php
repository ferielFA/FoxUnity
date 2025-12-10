<?php
require_once __DIR__ . '/db.php';

class SubscriberRepository {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function add($email, array $categoryIds) {
        // Validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [false, 'Invalid email format'];
        }

        // Check if exists
        $stmt = $this->pdo->prepare("SELECT id FROM subscribers WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            // Update existing
            $catString = implode(',', $categoryIds);
            $upd = $this->pdo->prepare("UPDATE subscribers SET categories = ? WHERE email = ?");
            if ($upd->execute([$catString, $email])) {
                return [true, 'Preferences updated successfully'];
            }
        } else {
            // Insert new
            $catString = implode(',', $categoryIds);
            $ins = $this->pdo->prepare("INSERT INTO subscribers (email, categories) VALUES (?, ?)");
            if ($ins->execute([$email, $catString])) {
                return [true, 'Subscribed successfully'];
            }
        }
        return [false, 'Database error'];
    }

    public function getAll() {
        return $this->pdo->query("SELECT * FROM subscribers ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findInterestedEmails($categoryId) {
        // Simple text search for ID in CSV string. 
        // Note: For "1", like "%1%" matches "10", "11". Improved regex or FIND_IN_SET is better but for simple comma list:
        // FIND_IN_SET is best for comma separated.
        $stmt = $this->pdo->prepare("SELECT email FROM subscribers WHERE FIND_IN_SET(?, categories)");
        $stmt->execute([$categoryId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
?>
