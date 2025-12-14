<?php

/**
 * Modèle de domaine pour un abonné à la newsletter.
 */
class Subscriber
{
    private ?int $id;
    private string $email;
    private string $categories;
    private DateTime $createdAt;

    public function __construct(
        ?int $id = null,
        string $email = '',
        string $categories = '',
        ?DateTime $createdAt = null
    ) {
        $this->id = $id;
        $this->email = $email;
        $this->categories = $categories;
        $this->createdAt = $createdAt ?? new DateTime();
    }

    // Getters
    public function getId(): ?int { return $this->id; }
    public function getEmail(): string { return $this->email; }
    public function getCategories(): string { return $this->categories; }
    public function getCreatedAt(): DateTime { return $this->createdAt; }

    // Setters
    public function setId(?int $id): void { $this->id = $id; }
    public function setEmail(string $email): void { $this->email = $email; }
    public function setCategories(string $categories): void { $this->categories = $categories; }
    public function setCreatedAt(DateTime $createdAt): void { $this->createdAt = $createdAt; }

    // ========== Static Database Methods ==========

    private static function getPdo(): PDO
    {
        require_once __DIR__ . '/db.php';
        global $pdo;
        return $pdo;
    }

    private static function hasColumn(string $col): bool
    {
        $pdo = self::getPdo();
        $db   = $pdo->query('SELECT DATABASE()')->fetchColumn();
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.columns
             WHERE table_schema = ? AND table_name = ? AND column_name = ?'
        );
        $stmt->execute([$db, 'subscribers', $col]);
        return (bool) $stmt->fetchColumn();
    }

    private static function ensureCategoriesColumn(): void
    {
        $pdo = self::getPdo();
        if (!self::hasColumn('categories')) {
            try {
                $pdo->exec('ALTER TABLE `subscribers` ADD COLUMN `categories` TEXT NOT NULL');
            } catch (Exception $e) {
            }
        }
    }

    public static function add(string $email, array $categoryIds): array
    {
        $pdo = self::getPdo();
        // Detect schema and adapt behavior
        $hasCategoryId = self::hasColumn('category_id');
        $hasCategories = self::hasColumn('categories');
        if ($hasCategories && !$hasCategoryId) {
            self::ensureCategoriesColumn();
        }
        
        // Validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [false, 'Invalid email format'];
        }

        if ($hasCategoryId) {
            // Schema: one row per (email, category_id)
            $categoryIds = array_values(array_filter(array_map('intval', $categoryIds), function($id){ return $id > 0; }));
            if (empty($categoryIds)) {
                return [false, 'Please select at least one category'];
            }
            // Upsert desired set, remove others to sync preferences
            foreach ($categoryIds as $cid) {
                try {
                    // Validate category exists
                    $cat = Categorie::findById($cid);
                    if (!$cat) {
                        continue;
                    }
                    // Insert if not exists
                    $check = $pdo->prepare("SELECT id FROM subscribers WHERE email = ? AND category_id = ?");
                    $check->execute([$email, $cid]);
                    if (!$check->fetch()) {
                        $ins = $pdo->prepare("INSERT INTO subscribers (email, category_id) VALUES (?, ?)");
                        $ins->execute([$email, $cid]);
                    }
                } catch (PDOException $e) {
                    // Ignore duplicates or FK issues gracefully
                }
            }
            // Remove categories not in desired list
            try {
                $del = $pdo->prepare(
                    "DELETE FROM subscribers WHERE email = ? AND category_id NOT IN (" . implode(',', array_fill(0, count($categoryIds), '?')) . ")"
                );
                $del->execute(array_merge([$email], $categoryIds));
            } catch (PDOException $e) {
                // If NOT IN fails when list empty, ignore
            }
            return [true, 'Preferences updated successfully'];
        } else {
            // Schema: one row per email, categories stored as comma-separated
            // Check if exists
            $stmt = $pdo->prepare("SELECT id FROM subscribers WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                // Update existing
                $catString = implode(',', $categoryIds);
                $upd = $pdo->prepare("UPDATE subscribers SET categories = ? WHERE email = ?");
                if ($upd->execute([$catString, $email])) {
                    return [true, 'Preferences updated successfully'];
                }
            } else {
                // Insert new
                $catString = implode(',', $categoryIds);
                $ins = $pdo->prepare("INSERT INTO subscribers (email, categories) VALUES (?, ?)");
                if ($ins->execute([$email, $catString])) {
                    return [true, 'Subscribed successfully'];
                }
            }
            return [false, 'Database error'];
        }
    }

    public static function getAll(): array
    {
        $pdo = self::getPdo();
        $hasCategoryId = self::hasColumn('category_id');
        $hasCategories = self::hasColumn('categories');
        if ($hasCategoryId && !$hasCategories) {
            // Aggregate categories per email
            $sql = 'SELECT id, email, category_id, created_at FROM subscribers ORDER BY created_at DESC';
            $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
            $agg = [];
            foreach ($rows as $r) {
                $email = $r['email'];
                if (!isset($agg[$email])) {
                    $agg[$email] = [
                        'id' => $r['id'],
                        'email' => $email,
                        'created_at' => $r['created_at'],
                        'categories' => (string)($r['category_id'] ?? '')
                    ];
                } else {
                    $agg[$email]['categories'] = trim($agg[$email]['categories'] . ',' . (string)($r['category_id'] ?? ''), ',');
                }
            }
            return array_values($agg);
        } else {
            $cols = ['id', 'email', 'created_at'];
            if ($hasCategories) {
                $cols[] = 'categories';
            }
            $sql = 'SELECT ' . implode(', ', $cols) . ' FROM subscribers ORDER BY created_at DESC';
            $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
            if (!in_array('categories', $cols, true)) {
                foreach ($rows as &$r) {
                    $r['categories'] = '';
                }
            }
            return $rows;
        }
    }

    public static function findInterestedEmails(int $categoryId): array
    {
        $pdo = self::getPdo();
        $hasCategoryId = self::hasColumn('category_id');
        $hasCategories = self::hasColumn('categories');
        if ($hasCategoryId) {
            $stmt = $pdo->prepare("SELECT email FROM subscribers WHERE category_id = ?");
            $stmt->execute([$categoryId]);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } elseif ($hasCategories) {
            $stmt = $pdo->prepare("SELECT email FROM subscribers WHERE FIND_IN_SET(?, categories)");
            $stmt->execute([$categoryId]);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        }
        return [];
    }

    public static function getByEmail(string $email): ?array
    {
        $pdo = self::getPdo();
        $hasCategoryId = self::hasColumn('category_id');
        $hasCategories = self::hasColumn('categories');
        if ($hasCategoryId && !$hasCategories) {
            $stmt = $pdo->prepare('SELECT id, email, category_id, created_at FROM subscribers WHERE email = ?');
            $stmt->execute([$email]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!$rows) return null;
            $cats = [];
            foreach ($rows as $r) {
                if (isset($r['category_id'])) {
                    $cats[] = (string)$r['category_id'];
                }
            }
            return [
                'id' => $rows[0]['id'],
                'email' => $email,
                'categories' => implode(',', $cats),
                'created_at' => $rows[0]['created_at']
            ];
        } else {
            self::ensureCategoriesColumn();
            $stmt = $pdo->prepare('SELECT id, email, categories, created_at FROM subscribers WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                return null;
            }
            if (!isset($row['categories'])) {
                $row['categories'] = '';
            }
            return $row;
        }
    }
}

?>
