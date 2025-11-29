<?php
// Category model: CRUD and helpers
require_once __DIR__ . '/db.php';

// Helper: check if a column exists in `categorie` table
function _cat_has_column($col) {
  global $pdo;
  $db = $pdo->query('SELECT DATABASE()')->fetchColumn();
  $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = ? AND table_name = ? AND column_name = ?');
  $stmt->execute([$db, 'categorie', $col]);
  return (bool)$stmt->fetchColumn();
}

function getAllCategories() {
  global $pdo;
  // Select only available columns
  $cols = ['idCategorie','nom','description'];
  if (_cat_has_column('slug')) $cols[] = 'slug';
  if (_cat_has_column('position')) $cols[] = 'position';
  if (_cat_has_column('active')) $cols[] = 'active';
  $sql = 'SELECT ' . implode(', ', $cols) . ' FROM categorie ORDER BY ' . (_cat_has_column('position') ? 'position ASC, nom ASC' : 'nom ASC');
  $stmt = $pdo->query($sql);
  $rows = $stmt->fetchAll();
  // Normalize missing keys
  foreach($rows as &$r){ if (!isset($r['slug'])) $r['slug'] = ''; if (!isset($r['position'])) $r['position'] = 0; if (!isset($r['active'])) $r['active'] = 1; }
  return $rows;
}

function getCategoryById($id) {
  global $pdo;
  $cols = ['idCategorie','nom','description'];
  if (_cat_has_column('slug')) $cols[] = 'slug';
  if (_cat_has_column('position')) $cols[] = 'position';
  if (_cat_has_column('active')) $cols[] = 'active';
  $sql = 'SELECT ' . implode(', ', $cols) . ' FROM categorie WHERE idCategorie = ? LIMIT 1';
  $stmt = $pdo->prepare($sql);
  $stmt->execute([(int)$id]);
  $r = $stmt->fetch();
  if ($r) { if (!isset($r['slug'])) $r['slug']=''; if (!isset($r['position'])) $r['position']=0; if (!isset($r['active'])) $r['active']=1; }
  return $r;
}

function addCategory($data) {
  global $pdo;
  $name = $data['nom'] ?? $data['name'] ?? '';
  if (!$name) return [false, 'Name required'];
  $slug = $data['slug'] ?? '';
  if (!$slug) $slug = slugify($name);

  // If slug column exists, check uniqueness on slug; otherwise check name uniqueness
  if (_cat_has_column('slug')) {
    $check = $pdo->prepare('SELECT idCategorie FROM categorie WHERE slug = ? LIMIT 1');
    $check->execute([$slug]);
    if ($check->fetch()) return [false, 'Slug already exists'];
  } else {
    $check = $pdo->prepare('SELECT idCategorie FROM categorie WHERE nom = ? LIMIT 1');
    $check->execute([$name]);
    if ($check->fetch()) return [false, 'Category name already exists'];
  }

  // Determine position if available
  $pos = 0;
  if (_cat_has_column('position')) {
    $posRow = $pdo->query('SELECT COALESCE(MAX(position),0)+1 AS pos FROM categorie')->fetch();
    $pos = $posRow['pos'] ?? 1;
  }

  // Build INSERT dynamically depending on available columns
  $cols = ['nom','description'];
  $params = [$name, $data['description'] ?? ''];
  if (_cat_has_column('slug')) { $cols[] = 'slug'; $params[] = $slug; }
  if (_cat_has_column('position')) { $cols[] = 'position'; $params[] = $pos; }
  if (_cat_has_column('active')) { $cols[] = 'active'; $params[] = isset($data['active']) ? (int)$data['active'] : 1; }

  $sql = 'INSERT INTO categorie (' . implode(', ', $cols) . ') VALUES (' . rtrim(str_repeat('?, ', count($cols)), ', ') . ')';
  $stmt = $pdo->prepare($sql);
  $ok = $stmt->execute($params);
  if ($ok) return [true, $pdo->lastInsertId()];
  return [false, 'DB error'];
}

function updateCategory($id, $data) {
  global $pdo;
  $name = $data['nom'] ?? $data['name'] ?? '';
  if (!$name) return [false, 'Name required'];
  $slug = $data['slug'] ?? slugify($name);

  if (_cat_has_column('slug')) {
    $check = $pdo->prepare('SELECT idCategorie FROM categorie WHERE slug = ? AND idCategorie != ? LIMIT 1');
    $check->execute([$slug, (int)$id]);
    if ($check->fetch()) return [false, 'Slug already exists for another category'];
  } else {
    $check = $pdo->prepare('SELECT idCategorie FROM categorie WHERE nom = ? AND idCategorie != ? LIMIT 1');
    $check->execute([$name, (int)$id]);
    if ($check->fetch()) return [false, 'Category name already exists for another category'];
  }

  $sets = [];
  $params = [];
  $sets[] = 'nom = ?'; $params[] = $name;
  $sets[] = 'description = ?'; $params[] = $data['description'] ?? '';
  if (_cat_has_column('slug')) { $sets[] = 'slug = ?'; $params[] = $slug; }
  if (_cat_has_column('position')) { $sets[] = 'position = ?'; $params[] = intval($data['position'] ?? 0); }
  if (_cat_has_column('active')) { $sets[] = 'active = ?'; $params[] = isset($data['active']) ? (int)$data['active'] : 1; }

  $sql = 'UPDATE categorie SET ' . implode(', ', $sets) . ' WHERE idCategorie = ? LIMIT 1';
  $params[] = (int)$id;
  $stmt = $pdo->prepare($sql);
  $ok = $stmt->execute($params);
  return [$ok, $ok ? null : 'DB error'];
}

function deleteCategory($id) {
  global $pdo;
  // ensure no articles assigned
  $stmt = $pdo->prepare('SELECT COUNT(*) FROM article WHERE idCategorie = ?');
  $stmt->execute([(int)$id]);
  $count = (int)$stmt->fetchColumn();
  if ($count > 0) return [false, 'Category has '.$count.' article(s)'];

  $del = $pdo->prepare('DELETE FROM categorie WHERE idCategorie = ? LIMIT 1');
  $ok = $del->execute([(int)$id]);
  return [$ok, $ok ? null : 'DB error'];
}

function setCategoryStatus($id, $active) {
  global $pdo;
  // Ensure `active` column exists; if not, try to add it (safe migration)
  try {
    if (!_cat_has_column('active')) {
      // Add the column with default 1 so existing rows become active
      $pdo->exec('ALTER TABLE `categorie` ADD COLUMN `active` TINYINT(1) NOT NULL DEFAULT 1');
      // small pause not needed; information_schema will reflect change for subsequent calls
    }
    $upd = $pdo->prepare('UPDATE categorie SET active = ? WHERE idCategorie = ? LIMIT 1');
    return $upd->execute([ (int)$active, (int)$id ]);
  } catch (Exception $e) {
    // Log or surface errors elsewhere; for now return false so controller can report it
    return false;
  }
}

function reorderCategory($id, $newPosition) {
  global $pdo;
  if (!_cat_has_column('position')) return false;
  // naive reposition: set position to new value and re-normalize
  $pdo->beginTransaction();
  try {
    $pdo->prepare('UPDATE categorie SET position = ? WHERE idCategorie = ?')->execute([(int)$newPosition, (int)$id]);
    // normalize positions
    $rows = $pdo->query('SELECT idCategorie FROM categorie ORDER BY position ASC, nom ASC')->fetchAll();
    $pos = 1;
    $upd = $pdo->prepare('UPDATE categorie SET position = ? WHERE idCategorie = ?');
    foreach($rows as $r){ $upd->execute([$pos++, $r['idCategorie']]); }
    $pdo->commit();
    return true;
  } catch (Exception $e) {
    $pdo->rollBack();
    return false;
  }
}

function slugify($text) {
  $text = preg_replace('~[^\pL0-9]+~u', '-', $text);
  $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text);
  $text = preg_replace('~[^-a-zA-Z0-9]+~', '', $text);
  $text = trim($text, '-');
  $text = preg_replace('~-+~', '-', $text);
  $text = strtolower($text);
  if (empty($text)) return 'cat-'.time();
  return $text;
}
