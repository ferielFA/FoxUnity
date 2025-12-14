<?php

/**
 * Modèle de domaine pour une catégorie d'article.
 */
class Categorie
{
    private ?int $idCategorie;
    private string $nom;
    private string $description;
    private string $slug;
    private int $position;
    private bool $active;

    public function __construct(
        ?int $idCategorie = null,
        string $nom = '',
        string $description = '',
        string $slug = '',
        int $position = 0,
        bool $active = true
    ) {
        $this->idCategorie = $idCategorie;
        $this->nom         = $nom;
        $this->description = $description;
        $this->slug        = $slug;
        $this->position    = $position;
        $this->active      = $active;
    }

    // Getters
    public function getIdCategorie(): ?int { return $this->idCategorie; }
    public function getNom(): string { return $this->nom; }
    public function getDescription(): string { return $this->description; }
    public function getSlug(): string { return $this->slug; }
    public function getPosition(): int { return $this->position; }
    public function isActive(): bool { return $this->active; }

    // Setters
    public function setIdCategorie(?int $idCategorie): void { $this->idCategorie = $idCategorie; }
    public function setNom(string $nom): void { $this->nom = $nom; }
    public function setDescription(string $description): void { $this->description = $description; }
    public function setSlug(string $slug): void { $this->slug = $slug; }
    public function setPosition(int $position): void { $this->position = $position; }
    public function setActive(bool $active): void { $this->active = $active; }

    // Méthodes métier
    public function activer(): void
    {
        $this->active = true;
    }

    public function desactiver(): void
    {
        $this->active = false;
    }

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
        $stmt->execute([$db, 'categorie', $col]);
        return (bool) $stmt->fetchColumn();
    }

    public static function getAll(): array
    {
        $pdo = self::getPdo();
        $cols = ['idCategorie', 'nom', 'description'];
        if (self::hasColumn('slug')) {
            $cols[] = 'slug';
        }
        if (self::hasColumn('position')) {
            $cols[] = 'position';
        }
        if (self::hasColumn('active')) {
            $cols[] = 'active';
        }

        $sql  = 'SELECT ' . implode(', ', $cols) . ' FROM categorie ORDER BY ' .
            (self::hasColumn('position') ? 'position ASC, nom ASC' : 'nom ASC');
        $stmt = $pdo->query($sql);
        $rows = $stmt->fetchAll();

        foreach ($rows as &$r) {
            if (!isset($r['slug'])) {
                $r['slug'] = '';
            }
            if (!isset($r['position'])) {
                $r['position'] = 0;
            }
            if (!isset($r['active'])) {
                $r['active'] = 1;
            }
        }

        return $rows;
    }

    public static function findById(int $id): ?array
    {
        $pdo = self::getPdo();
        $cols = ['idCategorie', 'nom', 'description'];
        if (self::hasColumn('slug')) {
            $cols[] = 'slug';
        }
        if (self::hasColumn('position')) {
            $cols[] = 'position';
        }
        if (self::hasColumn('active')) {
            $cols[] = 'active';
        }

        $sql  = 'SELECT ' . implode(', ', $cols) . ' FROM categorie WHERE idCategorie = ? LIMIT 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        $r = $stmt->fetch();
        if (!$r) {
            return null;
        }
        if (!isset($r['slug'])) {
            $r['slug'] = '';
        }
        if (!isset($r['position'])) {
            $r['position'] = 0;
        }
        if (!isset($r['active'])) {
            $r['active'] = 1;
        }

        return $r;
    }

    public static function add(array $data): array
    {
        $pdo = self::getPdo();
        $name = $data['nom'] ?? $data['name'] ?? '';
        if (!$name) {
            return [false, 'Name required'];
        }

        $slug = $data['slug'] ?? '';
        if (!$slug) {
            $slug = self::slugify($name);
        }

        if (self::hasColumn('slug')) {
            $check = $pdo->prepare('SELECT idCategorie FROM categorie WHERE slug = ? LIMIT 1');
            $check->execute([$slug]);
            if ($check->fetch()) {
                return [false, 'Slug already exists'];
            }
        } else {
            $check = $pdo->prepare('SELECT idCategorie FROM categorie WHERE nom = ? LIMIT 1');
            $check->execute([$name]);
            if ($check->fetch()) {
                return [false, 'Category name already exists'];
            }
        }

        $pos = 0;
        if (self::hasColumn('position')) {
            $posRow = $pdo->query('SELECT COALESCE(MAX(position),0)+1 AS pos FROM categorie')->fetch();
            $pos    = $posRow['pos'] ?? 1;
        }

        $cols   = ['nom', 'description'];
        $params = [$name, $data['description'] ?? ''];

        if (self::hasColumn('slug')) {
            $cols[]   = 'slug';
            $params[] = $slug;
        }
        if (self::hasColumn('position')) {
            $cols[]   = 'position';
            $params[] = $pos;
        }
        if (self::hasColumn('active')) {
            $cols[]   = 'active';
            $params[] = isset($data['active']) ? (int) $data['active'] : 1;
        }

        $sql  = 'INSERT INTO categorie (' . implode(', ', $cols) . ') VALUES (' .
            rtrim(str_repeat('?, ', count($cols)), ', ') . ')';
        $stmt = $pdo->prepare($sql);
        $ok   = $stmt->execute($params);

        if ($ok) {
            return [true, $pdo->lastInsertId()];
        }
        return [false, 'DB error'];
    }

    public static function update(int $id, array $data): array
    {
        $pdo = self::getPdo();
        $name = $data['nom'] ?? $data['name'] ?? '';
        if (!$name) {
            return [false, 'Name required'];
        }

        $slug = $data['slug'] ?? self::slugify($name);

        if (self::hasColumn('slug')) {
            $check = $pdo->prepare(
                'SELECT idCategorie FROM categorie WHERE slug = ? AND idCategorie != ? LIMIT 1'
            );
            $check->execute([$slug, $id]);
            if ($check->fetch()) {
                return [false, 'Slug already exists for another category'];
            }
        } else {
            $check = $pdo->prepare(
                'SELECT idCategorie FROM categorie WHERE nom = ? AND idCategorie != ? LIMIT 1'
            );
            $check->execute([$name, $id]);
            if ($check->fetch()) {
                return [false, 'Category name already exists for another category'];
            }
        }

        $sets   = [];
        $params = [];

        $sets[]   = 'nom = ?';
        $params[] = $name;

        $sets[]   = 'description = ?';
        $params[] = $data['description'] ?? '';

        if (self::hasColumn('slug')) {
            $sets[]   = 'slug = ?';
            $params[] = $slug;
        }
        if (self::hasColumn('position')) {
            $sets[]   = 'position = ?';
            $params[] = (int) ($data['position'] ?? 0);
        }
        if (self::hasColumn('active')) {
            $sets[]   = 'active = ?';
            $params[] = isset($data['active']) ? (int) $data['active'] : 1;
        }

        $sql      = 'UPDATE categorie SET ' . implode(', ', $sets) . ' WHERE idCategorie = ? LIMIT 1';
        $params[] = $id;

        $stmt = $pdo->prepare($sql);
        $ok   = $stmt->execute($params);

        return [$ok, $ok ? null : 'DB error'];
    }

    public static function delete(int $id): array
    {
        $pdo = self::getPdo();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM article WHERE idCategorie = ?');
        $stmt->execute([$id]);
        $count = (int) $stmt->fetchColumn();
        if ($count > 0) {
            return [false, 'Category has ' . $count . ' article(s)'];
        }

        $del = $pdo->prepare('DELETE FROM categorie WHERE idCategorie = ? LIMIT 1');
        $ok  = $del->execute([$id]);
        return [$ok, $ok ? null : 'DB error'];
    }

    public static function setStatus(int $id, int $active): bool
    {
        $pdo = self::getPdo();
        try {
            if (!self::hasColumn('active')) {
                $pdo->exec(
                    'ALTER TABLE `categorie` ADD COLUMN `active` TINYINT(1) NOT NULL DEFAULT 1'
                );
            }
            $upd = $pdo->prepare('UPDATE categorie SET active = ? WHERE idCategorie = ? LIMIT 1');
            return $upd->execute([$active, $id]);
        } catch (Exception $e) {
            return false;
        }
    }

    public static function reorder(int $id, int $newPosition): bool
    {
        $pdo = self::getPdo();
        if (!self::hasColumn('position')) {
            return false;
        }

        $pdo->beginTransaction();
        try {
            $pdo->prepare('UPDATE categorie SET position = ? WHERE idCategorie = ?')
                ->execute([$newPosition, $id]);

            $rows = $pdo
                ->query('SELECT idCategorie FROM categorie ORDER BY position ASC, nom ASC')
                ->fetchAll();
            $pos  = 1;
            $upd  = $pdo->prepare('UPDATE categorie SET position = ? WHERE idCategorie = ?');
            foreach ($rows as $r) {
                $upd->execute([$pos++, $r['idCategorie']]);
            }

            $pdo->commit();
            return true;
        } catch (Exception $e) {
            $pdo->rollBack();
            return false;
        }
    }

    public static function slugify(string $text): string
    {
        $text = preg_replace('~[^\pL0-9]+~u', '-', $text);
        $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text);
        $text = preg_replace('~[^-a-zA-Z0-9]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        $text = strtolower($text);
        if (empty($text)) {
            return 'cat-' . time();
        }
        return $text;
    }

    // --- Logic migrated from NotificationService ---

    public static function notifySubscribersForArticle(array $article): int {
        require_once __DIR__ . '/Subscriber.php';
        
        if (!empty($article['notifications_sent'])) {
            return 0;
        }

        $catId = $article['idCategorie'];
        if (empty($catId)) {
            self::markAsSent($article['idArticle']);
            return 0;
        }

        $emails = Subscriber::findInterestedEmails($catId);

        if (empty($emails)) {
            self::markAsSent($article['idArticle']);
            return 0;
        }

        $count = 0;
        foreach ($emails as $email) {
            if (self::sendArticleNotification($email, $article)) {
                $count++;
            }
        }

        self::markAsSent($article['idArticle']);
        return $count;
    }

    public static function sendWelcomeEmail($email, $categoryIds = []): bool {
        $config = self::getEmailConfig();
        $subject = $config['templates']['welcome']['subject'] ?? "Welcome to FoxUnity News!";
        
        $categoryNames = [];
        if (!empty($categoryIds)) {
            foreach ($categoryIds as $catId) {
                $cat = self::findById((int)$catId);
                if ($cat) {
                    $categoryNames[] = $cat['nom'];
                }
            }
        }
        
        $categoriesList = '';
        if (!empty($categoryNames)) {
            $categoriesList = '<div style="background:rgba(255,153,0,0.1);border-left:3px solid #ff9900;padding:15px;margin:20px 0;">
                <h3 style="color:#ff9900;margin:0 0 10px 0;font-size:1.1rem;">Your Subscribed Categories:</h3>
                <ul style="margin:0;padding-left:20px;color:#ddd;">';
            foreach ($categoryNames as $name) {
                $categoriesList .= '<li style="margin:5px 0;">' . htmlspecialchars($name) . '</li>';
            }
            $categoriesList .= '</ul></div>';
        }
        
        $styles = $config['styles'];
        $message = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>$subject</title>
        </head>
        <body style='background:{$styles['background']};color:{$styles['text_color']};font-family:\"Segoe UI\",Tahoma,Geneva,Verdana,sans-serif;margin:0;padding:0;'>
            <div style='max-width:600px;margin:40px auto;background:linear-gradient(135deg, rgba(30,30,30,0.95), rgba(20,20,20,0.98));border:1px solid rgba(255,153,0,0.2);border-radius:12px;overflow:hidden;'>
                <div style='background:linear-gradient(135deg, #ff7a00, #ff5500);padding:30px;text-align:center;'>
                    <h1 style='color:#fff;margin:0;font-size:2rem;text-shadow:0 2px 4px rgba(0,0,0,0.3);'>🎮 FoxUnity</h1>
                    <p style='color:rgba(255,255,255,0.9);margin:10px 0 0 0;font-size:0.9rem;'>Gaming for Good</p>
                </div>
                <div style='padding:40px 30px;'>
                    <h2 style='color:{$styles['accent_color']};margin:0 0 20px 0;font-size:1.5rem;'>✓ Subscription Successful!</h2>
                    <p style='line-height:1.8;margin:0 0 20px 0;font-size:1rem;'>
                        Thank you for subscribing to <strong style='color:{$styles['accent_color']}'>FoxUnity News</strong>! 
                    </p>
                    $categoriesList
                    <div style='text-align:center;margin:30px 0 20px 0;'>
                        <a href='http://{$_SERVER['HTTP_HOST']}/projet_web/view/front/news.php' 
                           style='display:inline-block;background:{$styles['button_bg']};color:{$styles['button_text']};
                                  padding:14px 35px;text-decoration:none;border-radius:6px;font-weight:bold;
                                  font-size:1rem;box-shadow:0 4px 12px rgba(255,153,0,0.3);'>
                            Browse Latest News
                        </a>
                    </div>
                </div>
            </div>
        </body>
        </html>";
        
        return self::sendEmail($email, $subject, $message, 'Welcome');
    }

    private static function sendArticleNotification($to, $article): bool {
        $config = self::getEmailConfig();
        $subjectPrefix = $config['templates']['article_notification']['subject_prefix'] ?? '🔥 Hot News: ';
        $subject = $subjectPrefix . $article['title'];
        
        $categoryName = 'Gaming';
        if (!empty($article['idCategorie'])) {
            $cat = self::findById($article['idCategorie']);
            if ($cat) $categoryName = $cat['nom'];
        }
        
        $link = "http://" . $_SERVER['HTTP_HOST'] . "/projet_web/view/front/news_article.php?id=" . urlencode($article['id'] ?? ($article['slug'] ?? ''));
        $styles = $config['styles'];
        
        $message = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>$subject</title>
        </head>
        <body style='background:{$styles['background']};color:{$styles['text_color']};font-family:\"Segoe UI\",Tahoma,Geneva,Verdana,sans-serif;margin:0;padding:0;'>
            <div style='max-width:600px;margin:40px auto;background:linear-gradient(135deg, rgba(30,30,30,0.95), rgba(20,20,20,0.98));border:1px solid rgba(255,153,0,0.2);border-radius:12px;overflow:hidden;'>
                <div style='background:linear-gradient(135deg, #ff7a00, #ff5500);padding:30px;text-align:center;'>
                    <h1 style='color:#fff;margin:0;font-size:2rem;text-shadow:0 2px 4px rgba(0,0,0,0.3);'>🎮 FoxUnity</h1>
                    <p style='color:rgba(255,255,255,0.9);margin:10px 0 0 0;font-size:0.9rem;'>Gaming for Good</p>
                </div>
                <div style='padding:40px 30px;'>
                    <h2 style='color:#fff;margin:0 0 20px 0;font-size:1.8rem;line-height:1.3;'>
                        " . htmlspecialchars($article['title']) . "
                    </h2>
                    <p style='line-height:1.8;margin:0 0 25px 0;font-size:1rem;color:#bbb;'>
                        " . htmlspecialchars($article['excerpt'] ?? substr(strip_tags($article['contenu'] ?? ''), 0, 200)) . "...
                    </p>
                    <div style='text-align:center;margin:35px 0 25px 0;'>
                        <a href='$link' style='display:inline-block;background:{$styles['button_bg']};color:{$styles['button_text']};padding:16px 40px;text-decoration:none;border-radius:6px;font-weight:bold;font-size:1.1rem;'>
                            📖 Read Full Article
                        </a>
                    </div>
                </div>
            </div>
        </body>
        </html>";
        
        return self::sendEmail($to, $subject, $message, 'Article Notification');
    }

    private static function sendEmail($to, $subject, $htmlMessage, $type = 'Email'): bool {
        $config = self::getEmailConfig();
        $date = date('Y-m-d H:i:s');
        $logFile = __DIR__ . '/../view/back/data/email_logs.txt';
        @mkdir(dirname($logFile), 0755, true);

        $status = "FAILED";
        $errorMsg = "";

        try {
            if ($config['method'] === 'phpmailer') {
                require_once __DIR__ . '/../lib/PHPMailer/PHPMailer.php';
                require_once __DIR__ . '/../lib/PHPMailer/SMTP.php';
                require_once __DIR__ . '/../lib/PHPMailer/Exception.php';

                $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                
                // Server settings
                $mail->isSMTP();
                $mail->Host       = $config['smtp']['host'];
                $mail->SMTPAuth   = true;
                $mail->Username   = $config['gmail']['username'];
                $mail->Password   = $config['gmail']['password'];
                $mail->SMTPSecure = $config['smtp']['encryption'];
                $mail->Port       = $config['smtp']['port'];
                $mail->CharSet    = 'UTF-8';
                
                // Recipients
                $mail->setFrom($config['gmail']['from_email'], $config['gmail']['from_name']);
                $mail->addAddress($to);
                $mail->addReplyTo($config['gmail']['from_email'], $config['gmail']['from_name']);
                
                // Content
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body    = $htmlMessage;
                $mail->AltBody = strip_tags($htmlMessage);
                
                $mail->send();
                $status = "SENT";
            } else {
                // Fallback to PHP mail() function
                $from = $config['from'];
                $headers = "MIME-Version: 1.0" . "\r\n";
                $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
                $headers .= "From: {$from['name']} <{$from['address']}>" . "\r\n";
                $headers .= "Reply-To: {$from['address']}" . "\r\n";
                $headers .= "X-Mailer: PHP/" . phpversion();

                if (mail($to, $subject, $htmlMessage, $headers)) {
                    $status = "SENT";
                } else {
                    $errorMsg = error_get_last()['message'] ?? 'Unknown error';
                    $status = "FAILED (mail() error: $errorMsg)";
                }
            }
        } catch (Exception $e) {
            $errorMsg = $e->getMessage();
            $status = "FAILED (Exception: $errorMsg)";
        }
        
        $logEntry = "[$date] $status TO: $to | SUB: $subject | TYPE: $type\n";
        file_put_contents($logFile, $logEntry, FILE_APPEND);
        
        return $status === "SENT";
    }

    private static function markAsSent($articleId): void {
        $pdo = self::getPdo();
        try {
            $stmt = $pdo->prepare("UPDATE article SET notifications_sent = 1 WHERE idArticle = ?");
            $stmt->execute([$articleId]);
        } catch (Exception $e) { }
    }

    private static function getEmailConfig(): array {
        $configFile = __DIR__ . '/../config/email_config.php';
        if (file_exists($configFile)) {
            return require $configFile;
        }
        return [
            'from' => ['address' => 'noreply@foxunity.com', 'name' => 'FoxUnity News'],
            'styles' => [
                'background' => '#0a0a0a',
                'text_color' => '#dddddd',
                'accent_color' => '#ff9900',
                'button_bg' => '#ff9900',
                'button_text' => '#000000',
            ]
        ];
    }
}

?>



