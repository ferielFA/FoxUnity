<?php

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/Categorie.php';

class CategoryRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    private function hasColumn(string $col): bool
    {
        $db   = $this->pdo->query('SELECT DATABASE()')->fetchColumn();
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.columns
             WHERE table_schema = ? AND table_name = ? AND column_name = ?'
        );
        $stmt->execute([$db, 'categorie', $col]);
        return (bool) $stmt->fetchColumn();
    }

    public function getAll(): array
    {
        $cols = ['idCategorie', 'nom', 'description'];
        if ($this->hasColumn('slug')) {
            $cols[] = 'slug';
        }
        if ($this->hasColumn('position')) {
            $cols[] = 'position';
        }
        if ($this->hasColumn('active')) {
            $cols[] = 'active';
        }

        $sql  = 'SELECT ' . implode(', ', $cols) . ' FROM categorie ORDER BY ' .
            ($this->hasColumn('position') ? 'position ASC, nom ASC' : 'nom ASC');
        $stmt = $this->pdo->query($sql);
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

    public function findById(int $id): ?array
    {
        $cols = ['idCategorie', 'nom', 'description'];
        if ($this->hasColumn('slug')) {
            $cols[] = 'slug';
        }
        if ($this->hasColumn('position')) {
            $cols[] = 'position';
        }
        if ($this->hasColumn('active')) {
            $cols[] = 'active';
        }

        $sql  = 'SELECT ' . implode(', ', $cols) . ' FROM categorie WHERE idCategorie = ? LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
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

    public function add(array $data): array
    {
        $name = $data['nom'] ?? $data['name'] ?? '';
        if (!$name) {
            return [false, 'Name required'];
        }

        $slug = $data['slug'] ?? '';
        if (!$slug) {
            $slug = $this->slugify($name);
        }

        if ($this->hasColumn('slug')) {
            $check = $this->pdo->prepare('SELECT idCategorie FROM categorie WHERE slug = ? LIMIT 1');
            $check->execute([$slug]);
            if ($check->fetch()) {
                return [false, 'Slug already exists'];
            }
        } else {
            $check = $this->pdo->prepare('SELECT idCategorie FROM categorie WHERE nom = ? LIMIT 1');
            $check->execute([$name]);
            if ($check->fetch()) {
                return [false, 'Category name already exists'];
            }
        }

        $pos = 0;
        if ($this->hasColumn('position')) {
            $posRow = $this->pdo->query('SELECT COALESCE(MAX(position),0)+1 AS pos FROM categorie')->fetch();
            $pos    = $posRow['pos'] ?? 1;
        }

        $cols   = ['nom', 'description'];
        $params = [$name, $data['description'] ?? ''];

        if ($this->hasColumn('slug')) {
            $cols[]   = 'slug';
            $params[] = $slug;
        }
        if ($this->hasColumn('position')) {
            $cols[]   = 'position';
            $params[] = $pos;
        }
        if ($this->hasColumn('active')) {
            $cols[]   = 'active';
            $params[] = isset($data['active']) ? (int) $data['active'] : 1;
        }

        $sql  = 'INSERT INTO categorie (' . implode(', ', $cols) . ') VALUES (' .
            rtrim(str_repeat('?, ', count($cols)), ', ') . ')';
        $stmt = $this->pdo->prepare($sql);
        $ok   = $stmt->execute($params);

        if ($ok) {
            return [true, $this->pdo->lastInsertId()];
        }
        return [false, 'DB error'];
    }

    public function update(int $id, array $data): array
    {
        $name = $data['nom'] ?? $data['name'] ?? '';
        if (!$name) {
            return [false, 'Name required'];
        }

        $slug = $data['slug'] ?? $this->slugify($name);

        if ($this->hasColumn('slug')) {
            $check = $this->pdo->prepare(
                'SELECT idCategorie FROM categorie WHERE slug = ? AND idCategorie != ? LIMIT 1'
            );
            $check->execute([$slug, $id]);
            if ($check->fetch()) {
                return [false, 'Slug already exists for another category'];
            }
        } else {
            $check = $this->pdo->prepare(
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

        if ($this->hasColumn('slug')) {
            $sets[]   = 'slug = ?';
            $params[] = $slug;
        }
        if ($this->hasColumn('position')) {
            $sets[]   = 'position = ?';
            $params[] = (int) ($data['position'] ?? 0);
        }
        if ($this->hasColumn('active')) {
            $sets[]   = 'active = ?';
            $params[] = isset($data['active']) ? (int) $data['active'] : 1;
        }

        $sql      = 'UPDATE categorie SET ' . implode(', ', $sets) . ' WHERE idCategorie = ? LIMIT 1';
        $params[] = $id;

        $stmt = $this->pdo->prepare($sql);
        $ok   = $stmt->execute($params);

        return [$ok, $ok ? null : 'DB error'];
    }

    public function delete(int $id): array
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM article WHERE idCategorie = ?');
        $stmt->execute([$id]);
        $count = (int) $stmt->fetchColumn();
        if ($count > 0) {
            return [false, 'Category has ' . $count . ' article(s)'];
        }

        $del = $this->pdo->prepare('DELETE FROM categorie WHERE idCategorie = ? LIMIT 1');
        $ok  = $del->execute([$id]);
        return [$ok, $ok ? null : 'DB error'];
    }

    public function setStatus(int $id, int $active): bool
    {
        try {
            if (!$this->hasColumn('active')) {
                $this->pdo->exec(
                    'ALTER TABLE `categorie` ADD COLUMN `active` TINYINT(1) NOT NULL DEFAULT 1'
                );
            }
            $upd = $this->pdo->prepare('UPDATE categorie SET active = ? WHERE idCategorie = ? LIMIT 1');
            return $upd->execute([$active, $id]);
        } catch (Exception $e) {
            return false;
        }
    }

    public function reorder(int $id, int $newPosition): bool
    {
        if (!$this->hasColumn('position')) {
            return false;
        }

        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare('UPDATE categorie SET position = ? WHERE idCategorie = ?')
                ->execute([$newPosition, $id]);

            $rows = $this->pdo
                ->query('SELECT idCategorie FROM categorie ORDER BY position ASC, nom ASC')
                ->fetchAll();
            $pos  = 1;
            $upd  = $this->pdo->prepare('UPDATE categorie SET position = ? WHERE idCategorie = ?');
            foreach ($rows as $r) {
                $upd->execute([$pos++, $r['idCategorie']]);
            }

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    private function slugify(string $text): string
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
}

?>


