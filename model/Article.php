<?php

/**
 * Modèle de domaine pour un article.
 *
 * Entité pure (aucun accès BD / fichiers).
 */
class Article
{
    private ?int $idArticle;
    private string $slug;
    private int $id_pub;
    private string $titre;
    private string $description;
    private string $contenu;
    private string $excerpt;
    private string $image;
    private DateTime $datePublication;
    private int $idCategorie;
    private bool $hot;

    public function __construct(
        ?int $idArticle = null,
        string $slug = '',
        int $id_pub = 0,
        string $titre = '',
        string $description = '',
        string $contenu = '',
        string $excerpt = '',
        string $image = '',
        ?DateTime $datePublication = null,
        int $idCategorie = 0,
        bool $hot = false
    ) {
        $this->idArticle       = $idArticle;
        $this->slug            = $slug;
        $this->id_pub          = $id_pub;
        $this->titre           = $titre;
        $this->description     = $description;
        $this->contenu         = $contenu;
        $this->excerpt         = $excerpt;
        $this->image           = $image;
        $this->datePublication = $datePublication ?? new DateTime();
        $this->idCategorie     = $idCategorie;
        $this->hot             = $hot;
    }

    // Getters
    public function getIdArticle(): ?int { return $this->idArticle; }
    public function getSlug(): string { return $this->slug; }
    public function getIdPub(): int { return $this->id_pub; }
    public function getTitre(): string { return $this->titre; }
    public function getDescription(): string { return $this->description; }
    public function getContenu(): string { return $this->contenu; }
    public function getExcerpt(): string { return $this->excerpt; }
    public function getImage(): string { return $this->image; }
    public function getDatePublication(): DateTime { return $this->datePublication; }
    public function getIdCategorie(): int { return $this->idCategorie; }
    public function isHot(): bool { return $this->hot; }

    // Setters
    public function setIdArticle(?int $idArticle): void { $this->idArticle = $idArticle; }
    public function setSlug(string $slug): void { $this->slug = $slug; }
    public function setIdPub(int $id_pub): void { $this->id_pub = $id_pub; }
    public function setTitre(string $titre): void { $this->titre = $titre; }
    public function setDescription(string $description): void { $this->description = $description; }
    public function setContenu(string $contenu): void { $this->contenu = $contenu; }
    public function setExcerpt(string $excerpt): void { $this->excerpt = $excerpt; }
    public function setImage(string $image): void { $this->image = $image; }
    public function setDatePublication(DateTime $datePublication): void { $this->datePublication = $datePublication; }
    public function setIdCategorie(int $idCategorie): void { $this->idCategorie = $idCategorie; }
    public function setHot(bool $hot): void { $this->hot = $hot; }

    // Méthodes métier
    public function toggleHotStatus(): void
    {
        $this->hot = !$this->hot;
    }

    public function getResumeTitre(int $max = 60): string
    {
        if (mb_strlen($this->titre) <= $max) {
            return $this->titre;
        }
        return mb_substr($this->titre, 0, $max - 3) . '...';
    }

    public function getTempsLecture(int $wpm = 200): int
    {
        $wc = str_word_count(strip_tags($this->contenu));
        return max(1, (int) ceil($wc / $wpm));
    }

    public function getMotsCles(int $limit = 8): array
    {
        return extractKeywordsAI($this->contenu, $limit);
    }

    public function getResumeAutomatique(): string
    {
        if (!empty($this->excerpt)) {
            return $this->excerpt;
        }
        return generateAdvancedSummaryAI($this->contenu, $this->titre);
    }

    public function getExtraitsImportants(int $limit = 5): array
    {
        $text = trim(strip_tags($this->contenu));
        if ($text === '') return [];
        $text = preg_replace('/\s+/', ' ', $text);
        $sentences = preg_split('/(?<=[.!?])\s+/', $text);
        $keywords = $this->getMotsCles(8);
        $highlights = [];
        foreach ($sentences as $s) {
            $sClean = trim($s);
            if ($sClean === '') continue;
            $match = false;
            foreach ($keywords as $k) { if ($k && stripos($sClean, $k) !== false) { $match = true; break; } }
            if ($match && !in_array($sClean, $highlights, true)) { $highlights[] = $sClean; }
            if (count($highlights) >= $limit) break;
        }
        if (empty($highlights)) {
            for ($i = 0; $i < min(3, count($sentences)); $i++) { $highlights[] = trim($sentences[$i]); }
        }
        return $highlights;
    }

    // ========== Static Database Methods ==========

    private static function getPdo(): PDO
    {
        require_once __DIR__ . '/db.php';
        global $pdo;
        return $pdo;
    }

    public static function getAll(): array
    {
        $pdo = self::getPdo();
        $stmt = $pdo->query(
            "SELECT
                a.idArticle,
                a.slug        AS id,
                a.titre       AS title,
                a.datePublication AS date,
                a.datePublication,
                a.idCategorie,
                c.nom         AS category,
                a.excerpt,
                a.summary,
                a.contenu     AS content,
                a.image,
                a.hot
             FROM article a
             LEFT JOIN categorie c ON c.idCategorie = a.idCategorie
             ORDER BY a.hot DESC, a.datePublication DESC, a.idArticle DESC"
        );
        return $stmt->fetchAll();
    }

    public static function findBySlug(string $slug): ?array
    {
        $pdo = self::getPdo();
        $stmt = $pdo->prepare(
            "SELECT
                a.idArticle,
                a.slug       AS id,
                a.titre      AS title,
                a.displayDate AS date,
                a.datePublication,
                a.image,
                a.idCategorie,
                a.summary,
                a.notifications_sent,
                c.nom         AS category_name,
                c.description AS category_description,
                a.excerpt,
                a.contenu    AS content,
                a.hot
              FROM article a
              LEFT JOIN categorie c ON c.idCategorie = a.idCategorie
              WHERE a.slug = ? LIMIT 1"
        );
        $stmt->execute([$slug]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        if (!isset($row['category_name']) || $row['category_name'] === null) {
            $row['category_name'] = '';
        }
        if (!isset($row['category_description']) || $row['category_description'] === null) {
            $row['category_description'] = '';
        }
        return $row;
    }

    public static function countByCategory(): array
    {
        $pdo = self::getPdo();
        $stmt = $pdo->query('SELECT idCategorie, COUNT(*) as cnt FROM article GROUP BY idCategorie');
        $rows = $stmt->fetchAll();
        $out  = [];
        foreach ($rows as $r) {
            $out[$r['idCategorie']] = (int) $r['cnt'];
        }
        return $out;
    }

    public static function add(array $data, array $files): array
    {
        $pdo = self::getPdo();
        
        $uploadsDir = __DIR__ . '/../view/back/uploads/images';
        @mkdir($uploadsDir, 0755, true);
        $imagePath = '';

        if (isset($files['image_upload']['tmp_name']) && is_uploaded_file($files['image_upload']['tmp_name'])) {
            $ext = strtolower(pathinfo($files['image_upload']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'], true)) {
                $filename = uniqid('img_') . '.' . $ext;
                $dest     = $uploadsDir . '/' . $filename;
                if (move_uploaded_file($files['image_upload']['tmp_name'], $dest)) {
                    $imagePath = 'uploads/images/' . $filename;
                }
            }
        }

        $stmt = $pdo->prepare(
            "INSERT INTO article
                (slug, id_pub, titre, contenu, excerpt, summary, image, datePublication, idCategorie, hot)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        $id_pub          = 4;
        $isHot           = isset($data['hot']) && $data['hot'] == '1' ? 1 : 0;
        $validIdCategorie = (int) ($data['idCategorie'] ?? 0);

        $summary = generateAdvancedSummaryAI($data['content'] ?? '', $data['title'] ?? '');
        $ok = $stmt->execute([
            $data['id'],
            $id_pub,
            $data['title'],
            $data['content'],
            $data['excerpt'] ?? '',
            $summary,
            $imagePath,
            $data['datePublication'] ?? date('Y-m-d'),
            $validIdCategorie > 0 ? $validIdCategorie : null,
            $isHot,
        ]);

        if ($ok) {
            $item              = $data;
            $item['image']     = $imagePath;
            $item['idArticle'] = (int) $pdo->lastInsertId();
            return [true, $item];
        }

        return [false, null];
    }

    public static function update(string $slug, array $data, array $files): array
    {
        $pdo = self::getPdo();
        
        // Get current data for history
        $current = self::findBySlug($slug);
        if (!$current) {
            return [false, null];
        }

        $uploadsDir = __DIR__ . '/../view/back/uploads/images';
        @mkdir($uploadsDir, 0755, true);
        $imagePath = $data['image_existing'] ?? '';

        if (isset($files['image_upload']['tmp_name']) && is_uploaded_file($files['image_upload']['tmp_name'])) {
            $ext = strtolower(pathinfo($files['image_upload']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'], true)) {
                $filename = uniqid('img_') . '.' . $ext;
                $dest     = $uploadsDir . '/' . $filename;
                if (move_uploaded_file($files['image_upload']['tmp_name'], $dest)) {
                    $imagePath = 'uploads/images/' . $filename;
                }
            }
        }

        $validIdCategorie = (int) ($data['idCategorie'] ?? 0);
        $isHot            = isset($data['hot']) && $data['hot'] == '1' ? 1 : 0;

        // Generate AI summary if content changed
        $summary = isset($data['summary']) && !empty($data['summary']) ? $data['summary'] : generateArticleSummary($data['content'], $data['title']);

        // Save to history before update
        self::saveHistory($current, 4); // edited_by = 4 (admin)

        $upd = $pdo->prepare(
            "UPDATE article SET
                titre = ?,
                contenu = ?,
                excerpt = ?,
                summary = ?,
                image = ?,
                displayDate = ?,
                datePublication = ?,
                idCategorie = ?,
                hot = ?
              WHERE slug = ? LIMIT 1"
        );

        $ok = $upd->execute([
            $data['title'],
            $data['content'],
            $data['excerpt'] ?? '',
            $summary,
            $imagePath,
            $data['date'] ?? '',
            $data['datePublication'] ?? '',
            $validIdCategorie > 0 ? $validIdCategorie : null,
            $isHot,
            $slug,
        ]);

        if ($ok) {
            $after         = $data;
            $after['image'] = $imagePath;
            return [true, $after];
        }

        return [false, null];
    }

    public static function delete(string $slug): bool
    {
        $pdo = self::getPdo();
        $del = $pdo->prepare("DELETE FROM article WHERE slug = ? LIMIT 1");
        return $del->execute([$slug]);
    }

    public static function toggleHot(string $slug): ?int
    {
        $pdo = self::getPdo();
        $toggle = $pdo->prepare("UPDATE article SET hot = NOT hot WHERE slug = ? LIMIT 1");
        if ($toggle->execute([$slug])) {
            $check = $pdo->prepare("SELECT hot FROM article WHERE slug = ? LIMIT 1");
            $check->execute([$slug]);
            return (int) $check->fetchColumn();
        }
        return null;
    }

    private static function saveHistory(array $article, int $editedBy): void
    {
        $pdo = self::getPdo();
        $stmt = $pdo->prepare(
            "INSERT INTO article_history
                (idArticle, slug, titre, contenu, excerpt, summary, image, datePublication, idCategorie, hot, edited_by)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $article['idArticle'],
            $article['id'],
            $article['title'],
            $article['content'],
            $article['excerpt'] ?? '',
            $article['summary'] ?? '',
            $article['image'] ?? '',
            $article['datePublication'] ?? $article['date'],
            $article['idCategorie'] ?? 0,
            $article['hot'] ?? 0,
            $editedBy
        ]);
    }

    public static function getHistoryByArticleId(int $articleId): array
    {
        $pdo = self::getPdo();
        $stmt = $pdo->prepare(
            "SELECT h.*, u.username AS edited_by_name
             FROM article_history h
             LEFT JOIN users u ON u.id = h.edited_by
             WHERE h.idArticle = ?
             ORDER BY h.edited_at DESC"
        );
        $stmt->execute([$articleId]);
        return $stmt->fetchAll();
    }
}

// ---- Global helper functions migrated from helpers.php ----
if (!function_exists('generateArticleSummary')) {
    function generateArticleSummary($content, $title) {
        $cleanContent = strip_tags($content);
        if (strlen($cleanContent) > 150) {
            return substr($cleanContent, 0, 150) . '...';
        }
        return $cleanContent;
    }
}

if (!function_exists('generateAdvancedSummaryAI')) {
    function generateAdvancedSummaryAI($content, $title) {
        $text = trim(strip_tags((string)$content));
        if ($text === '') return '';
        $text = preg_replace('/\s+/', ' ', $text);
        $sentences = preg_split('/(?<=[.!?])\s+/', $text);
        $summary = '';
        $limit = 3;
        $count = 0;
        foreach ($sentences as $s) {
            $s = trim($s);
            if ($s === '') continue;
            $summary .= ($summary === '' ? '' : ' ') . $s;
            $count++;
            if ($count >= $limit || strlen($summary) >= 300) break;
        }
        if (strlen($summary) < 140) {
            $summary = generateArticleSummary($content, $title);
        }
        return $summary;
    }
}

if (!function_exists('extractKeywordsAI')) {
    function extractKeywordsAI($content, $limit = 8) {
        $text = mb_strtolower(strip_tags((string)$content));
        $text = preg_replace('/[^a-zàâäçéèêëîïôöùûü0-9\s-]/u', ' ', $text);
        $words = preg_split('/\s+/', $text);
        $stopEn = ['the','and','or','a','an','to','of','in','on','for','with','by','is','are','was','were','be','as','at','it','this','that','from','but','not','have','has','had','you','we','they','our','your','their','i'];
        $stopFr = ['le','la','les','un','une','des','et','ou','de','du','dans','sur','pour','avec','par','est','sont','était','étaient','être','comme','à','il','elle','nous','vous','ils','elles','ce','cet','cette','cela','ça','qui','que','quoi','dont','où','ne','pas','plus','moins'];
        $stop = array_merge($stopEn, $stopFr);
        $freq = [];
        foreach ($words as $w) {
            $w = trim($w);
            if ($w === '' || is_numeric($w)) continue;
            if (in_array($w, $stop, true)) continue;
            if (mb_strlen($w) < 3) continue;
            $freq[$w] = ($freq[$w] ?? 0) + 1;
        }
        arsort($freq);
        $keywords = array_slice(array_keys($freq), 0, $limit);
        return $keywords;
    }
}

if (!function_exists('findCategoryName')) {
    function findCategoryName($id, $categories) {
        foreach ($categories as $c) {
            if (($c['idCategorie'] ?? 0) == $id) {
                return $c['nom'];
            }
        }
        return null;
    }
}

if (!function_exists('getImagePath')) {
    function getImagePath($imagePath) {
        if (empty($imagePath)) return '../images/nopic.png';
        if (strpos($imagePath, 'http') === 0) return $imagePath;
        if (strpos($imagePath, '../') === 0) return $imagePath;
        $root = dirname(__DIR__);
        $frontUploads = $root . '/view/front/' . $imagePath;
        $backUploads  = $root . '/view/back/' . $imagePath;
        if (file_exists($backUploads)) {
            return '../back/' . $imagePath;
        }
        if (file_exists($frontUploads)) {
            return $imagePath;
        }
        if (strpos($imagePath, 'images/') === 0) {
            $viewImages = $root . '/view/' . $imagePath;
            if (file_exists($viewImages)) {
                return '../' . $imagePath;
            }
        }
        if (file_exists($root . '/view/front/' . $imagePath)) return $imagePath;
        return '../images/nopic.png';
    }
}


?>



