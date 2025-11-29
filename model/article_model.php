<?php
// Article data access functions
require_once __DIR__ . '/db.php';

function ensureHotColumn() {
    global $pdo;
    try {
        $pdo->query('SELECT hot FROM article LIMIT 1');
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Unknown column') !== false) {
            $pdo->exec("ALTER TABLE article ADD COLUMN hot TINYINT(1) NOT NULL DEFAULT 0");
        } else {
            throw $e;
        }
    }
}

function getCategories() {
    global $pdo;
    $stmt = $pdo->query("SELECT idCategorie, nom, description FROM categorie ORDER BY nom");
    return $stmt->fetchAll();
}

function getAllArticles() {
    global $pdo;
    ensureHotColumn();
    $stmt = $pdo->query("SELECT
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
    ORDER BY a.hot DESC, a.datePublication DESC, a.idArticle DESC");
    return $stmt->fetchAll();
}

function getArticleBySlug($slug) {
    global $pdo;
    // Return article with its category name and description (if assigned)
    $stmt = $pdo->prepare("SELECT
      a.idArticle,
      a.slug       AS id,
      a.titre      AS title,
      a.datePublication AS date,
      a.datePublication,
      a.image,
      a.idCategorie,
      c.nom        AS category_name,
      c.description AS category_description,
      a.excerpt,
      a.contenu    AS content,
      a.hot
    FROM article a
    LEFT JOIN categorie c ON c.idCategorie = a.idCategorie
    WHERE a.slug = ? LIMIT 1");
    $stmt->execute([$slug]);
    $row = $stmt->fetch();
    // normalize category fields if null
    if ($row) {
      if (!isset($row['category_name']) || $row['category_name'] === null) $row['category_name'] = '';
      if (!isset($row['category_description']) || $row['category_description'] === null) $row['category_description'] = '';
    }
    return $row;
}

  function countArticlesByCategory() {
    global $pdo;
    $stmt = $pdo->query('SELECT idCategorie, COUNT(*) as cnt FROM article GROUP BY idCategorie');
    $rows = $stmt->fetchAll();
    $out = [];
    foreach($rows as $r) $out[$r['idCategorie']] = (int)$r['cnt'];
    return $out;
  }

function saveArticleVersion($slug, $article, $action = 'edit') {
  $historyDir = __DIR__ . '/../view/back/uploads/history';
  @mkdir($historyDir, 0755, true);
  $file = $historyDir . '/' . md5($slug) . '.json';
  $versions = file_exists($file) ? json_decode(file_get_contents($file), true) ?: [] : [];
  $versions[] = [
    'timestamp' => date('Y-m-d H:i:s'),
    'action' => $action,
    'article' => $article
  ];
  file_put_contents($file, json_encode($versions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function addArticle($data, $files) {
  global $pdo;
  $uploadsDir = __DIR__ . '/../view/back/uploads/images';
  @mkdir($uploadsDir, 0755, true);
  $imagePath = '';
  if (isset($files['image_upload']['tmp_name']) && is_uploaded_file($files['image_upload']['tmp_name'])) {
    $ext = strtolower(pathinfo($files['image_upload']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg','jpeg','png','gif'])) {
      $filename = uniqid('img_') . '.' . $ext;
      $dest = $uploadsDir . '/' . $filename;
      if (move_uploaded_file($files['image_upload']['tmp_name'], $dest)) {
        $imagePath = 'uploads/images/' . $filename;
      }
    }
  }
  $stmt = $pdo->prepare("INSERT INTO article
    (slug, id_pub, titre, contenu, excerpt, image, datePublication, idCategorie, hot)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
  $id_pub = 4;
  $isHot = isset($data['hot']) && $data['hot'] == '1' ? 1 : 0;
  $validIdCategorie = intval($data['idCategorie'] ?? 0);
  $ok = $stmt->execute([
    $data['id'],
    $id_pub,
    $data['title'],
    $data['content'],
    $data['excerpt'] ?? '',
    $imagePath,
    $data['datePublication'] ?? date('Y-m-d'),
    $validIdCategorie ?: 0,
    $isHot
  ]);
  if ($ok) {
    $item = $data;
    $item['image'] = $imagePath;
    $item['idArticle'] = (int)$pdo->lastInsertId();
    saveArticleVersion($data['id'], $item, 'created');
    return [true, $item];
  }
  return [false, null];
}

function updateArticle($slug, $data, $files) {
  global $pdo;
  $uploadsDir = __DIR__ . '/../view/back/uploads/images';
  @mkdir($uploadsDir, 0755, true);
  $imagePath = $data['image_existing'] ?? '';
  if (isset($files['image_upload']['tmp_name']) && is_uploaded_file($files['image_upload']['tmp_name'])) {
    $ext = strtolower(pathinfo($files['image_upload']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg','jpeg','png','gif'])) {
      $filename = uniqid('img_') . '.' . $ext;
      $dest = $uploadsDir . '/' . $filename;
      if (move_uploaded_file($files['image_upload']['tmp_name'], $dest)) {
        $imagePath = 'uploads/images/' . $filename;
      }
    }
  }
  $validIdCategorie = intval($data['idCategorie'] ?? 0);
  $isHot = isset($data['hot']) && $data['hot'] == '1' ? 1 : 0;
  $upd = $pdo->prepare("UPDATE article SET
    titre = ?,
    contenu = ?,
    excerpt = ?,
    image = ?,
    datePublication = ?,
    idCategorie = ?,
    hot = ?
  WHERE slug = ? LIMIT 1");
  $ok = $upd->execute([
    $data['title'],
    $data['content'],
    $data['excerpt'] ?? '',
    $imagePath,
    $data['datePublication'] ?? '',
    $validIdCategorie ?: 0,
    $isHot,
    $slug
  ]);
  if ($ok) {
    $after = $data;
    $after['image'] = $imagePath;
    saveArticleVersion($slug, $after, 'edited');
    return [true, $after];
  }
  return [false, null];
}

function deleteArticle($slug) {
  global $pdo;
  $del = $pdo->prepare("DELETE FROM article WHERE slug = ? LIMIT 1");
  return $del->execute([$slug]);
}

function toggleHot($slug) {
  global $pdo;
  $toggle = $pdo->prepare("UPDATE article SET hot = NOT hot WHERE slug = ? LIMIT 1");
  if ($toggle->execute([$slug])) {
    $check = $pdo->prepare("SELECT hot FROM article WHERE slug = ? LIMIT 1");
    $check->execute([$slug]);
    return $check->fetchColumn();
  }
  return null;
}
