<?php

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/Article.php';

/**
 * Repository responsable de l'accès aux données pour les articles.
 * Utilise PDO et retourne pour l'instant des tableaux associatifs,
 * afin de rester compatible avec les vues existantes.
 */
class ArticleRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getAll(): array
    {
        $stmt = $this->pdo->query(
            "SELECT
                a.idArticle,
                a.slug        AS id,
                a.titre       AS title,
                a.datePublication AS date,
                a.datePublication,
                a.idCategorie,
                c.nom         AS category,
                a.excerpt,
                a.contenu     AS content,
                a.image,
                a.hot
             FROM article a
             LEFT JOIN categorie c ON c.idCategorie = a.idCategorie
             ORDER BY a.hot DESC, a.datePublication DESC, a.idArticle DESC"
        );
        return $stmt->fetchAll();
    }

    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT
                a.idArticle,
                a.slug       AS id,
                a.titre      AS title,
                a.displayDate AS date,
                a.datePublication,
                a.image,
                a.idCategorie,
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

    public function countByCategory(): array
    {
        $stmt = $this->pdo->query('SELECT idCategorie, COUNT(*) as cnt FROM article GROUP BY idCategorie');
        $rows = $stmt->fetchAll();
        $out  = [];
        foreach ($rows as $r) {
            $out[$r['idCategorie']] = (int) $r['cnt'];
        }
        return $out;
    }

    public function add(array $data, array $files): array
    {
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

        $stmt = $this->pdo->prepare(
            "INSERT INTO article
                (slug, id_pub, titre, contenu, excerpt, image, datePublication, idCategorie, hot)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        $id_pub          = 4;
        $isHot           = isset($data['hot']) && $data['hot'] == '1' ? 1 : 0;
        $validIdCategorie = (int) ($data['idCategorie'] ?? 0);

        $ok = $stmt->execute([
            $data['id'],
            $id_pub,
            $data['title'],
            $data['content'],
            $data['excerpt'] ?? '',
            $imagePath,
            $data['datePublication'] ?? date('Y-m-d'),
            $validIdCategorie ?: 0,
            $isHot,
        ]);

        if ($ok) {
            $item              = $data;
            $item['image']     = $imagePath;
            $item['idArticle'] = (int) $this->pdo->lastInsertId();
            return [true, $item];
        }

        return [false, null];
    }

    public function update(string $slug, array $data, array $files): array
    {
        // Get current data for history
        $current = $this->findBySlug($slug);
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
        $this->saveHistory($current, 4); // edited_by = 4 (admin)

        $upd = $this->pdo->prepare(
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
            $validIdCategorie ?: 0,
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

    public function delete(string $slug): bool
    {
        $del = $this->pdo->prepare("DELETE FROM article WHERE slug = ? LIMIT 1");
        return $del->execute([$slug]);
    }

    public function toggleHot(string $slug): ?int
    {
        $toggle = $this->pdo->prepare("UPDATE article SET hot = NOT hot WHERE slug = ? LIMIT 1");
        if ($toggle->execute([$slug])) {
            $check = $this->pdo->prepare("SELECT hot FROM article WHERE slug = ? LIMIT 1");
            $check->execute([$slug]);
            return (int) $check->fetchColumn();
        }
        return null;
    }

    private function saveHistory(array $article, int $editedBy): void
    {
        $stmt = $this->pdo->prepare(
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

    public function getHistoryByArticleId(int $articleId): array
    {
        $stmt = $this->pdo->prepare(
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

?>


