<?php
// Comment model: supports embedded JSON comments in `article.comments`
// and a normalized `comments` table. Falls back to file-backed comments
// when DB columns are not available.
require_once __DIR__ . '/db.php';

function articleHasCommentsColumn() {
  global $pdo;
  try {
    $stmt = $pdo->query("SHOW COLUMNS FROM article LIKE 'comments'");
    $row = $stmt->fetch();
    return (bool)$row;
  } catch (PDOException $e) {
    return false;
  }
}

function getArticleIdBySlug($slug) {
  global $pdo;
  $s = $pdo->prepare('SELECT idArticle FROM article WHERE slug = ? LIMIT 1');
  $s->execute([$slug]);
  $r = $s->fetch();
  return $r ? intval($r['idArticle']) : null;
}

function getEmbeddedCommentsBySlug($slug) {
  global $pdo;
  if (articleHasCommentsColumn()) {
    $s = $pdo->prepare('SELECT comments FROM article WHERE slug = ? LIMIT 1');
    $s->execute([$slug]);
    $r = $s->fetch();
    $json = $r && isset($r['comments']) ? $r['comments'] : null;
    $arr = $json ? json_decode($json, true) : [];
    return is_array($arr) ? $arr : [];
  }
  // fallback to file-backed comments (same path used elsewhere)
  $commentsDir = __DIR__ . '/../view/front/uploads/comments';
  $file = $commentsDir . '/' . md5($slug) . '.json';
  if (!file_exists($file)) return [];
  $list = json_decode(file_get_contents($file), true) ?: [];
  return $list;
}

function saveEmbeddedCommentsBySlug($slug, $comments) {
  global $pdo;
  $json = json_encode(array_values($comments), JSON_UNESCAPED_UNICODE);
  if (articleHasCommentsColumn()) {
    try {
      $cnt = count($comments);
      $upd = $pdo->prepare('UPDATE article SET comments = ?, comments_count = ? WHERE slug = ? LIMIT 1');
      return $upd->execute([$json, $cnt, $slug]);
    } catch (PDOException $e) {
      return false;
    }
  }
  // fallback file write
  $commentsDir = __DIR__ . '/../view/front/uploads/comments';
  @mkdir($commentsDir, 0755, true);
  $file = $commentsDir . '/' . md5($slug) . '.json';
  file_put_contents($file . '.tmp', json_encode(array_values($comments), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
  rename($file . '.tmp', $file);
  return true;
}

function addEmbeddedCommentBySlug($slug, $name, $email, $text) {
  $list = getEmbeddedCommentsBySlug($slug);
  $entry = [
    'name' => $name,
    'email' => $email,
    'text' => $text,
    'date' => date('Y-m-d H:i:s')
  ];
  array_unshift($list, $entry);
  return saveEmbeddedCommentsBySlug($slug, $list);
}

function deleteEmbeddedCommentBySlug($slug, $index) {
  $list = getEmbeddedCommentsBySlug($slug);
  if (!isset($list[$index])) return false;
  array_splice($list, $index, 1);
  // if empty and DB-backed, set NULL; file-backed will remove file
  if (empty($list)) {
    if (articleHasCommentsColumn()) {
      global $pdo;
      $upd = $pdo->prepare('UPDATE article SET comments = NULL, comments_count = 0 WHERE slug = ? LIMIT 1');
      return $upd->execute([$slug]);
    } else {
      $commentsDir = __DIR__ . '/../view/front/uploads/comments';
      $file = $commentsDir . '/' . md5($slug) . '.json';
      if (file_exists($file)) { unlink($file); }
      return true;
    }
  }
  return saveEmbeddedCommentsBySlug($slug, $list);
}

function updateEmbeddedCommentBySlug($slug, $index, $name, $text) {
  $list = getEmbeddedCommentsBySlug($slug);
  if (!isset($list[$index])) return false;
  $list[$index]['name'] = $name;
  $list[$index]['text'] = $text;
  $list[$index]['date'] = date('Y-m-d H:i:s');
  return saveEmbeddedCommentsBySlug($slug, $list);
}

function clearEmbeddedCommentsBySlug($slug) {
  if (articleHasCommentsColumn()) {
    global $pdo;
    $upd = $pdo->prepare('UPDATE article SET comments = NULL, comments_count = 0 WHERE slug = ? LIMIT 1');
    return $upd->execute([$slug]);
  }
  $commentsDir = __DIR__ . '/../view/front/uploads/comments';
  $file = $commentsDir . '/' . md5($slug) . '.json';
  if (file_exists($file)) { unlink($file); }
  return true;
}

// Normalized comments table helpers (if present)
function commentsTableExists() {
  global $pdo;
  try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'comments'");
    $row = $stmt->fetch();
    return (bool)$row;
  } catch (PDOException $e) {
    return false;
  }
}

function getNormalizedCommentsByArticleId($articleId) {
  global $pdo;
  if (!commentsTableExists()) return [];
  $s = $pdo->prepare('SELECT idComment, article_id, name, email, text, is_deleted, created_at FROM comments WHERE article_id = ? AND is_deleted = 0 ORDER BY created_at DESC');
  $s->execute([$articleId]);
  return $s->fetchAll();
}

function addNormalizedComment($articleId, $name, $email, $text) {
  global $pdo;
  if (!commentsTableExists()) return false;
  $ins = $pdo->prepare('INSERT INTO comments (article_id, name, email, text, is_deleted, created_at) VALUES (?, ?, ?, ?, 0, NOW())');
  $ok = $ins->execute([$articleId, $name, $email, $text]);
  if ($ok) {
    // increment article.comments_count if column exists
    if (articleHasCommentsColumn()) {
      $upd = $pdo->prepare('UPDATE article SET comments_count = comments_count + 1 WHERE idArticle = ? LIMIT 1');
      $upd->execute([$articleId]);
    }
    return true;
  }
  return false;
}

function deleteNormalizedComment($idComment) {
  global $pdo;
  if (!commentsTableExists()) return false;
  $upd = $pdo->prepare('UPDATE comments SET is_deleted = 1 WHERE idComment = ? LIMIT 1');
  return $upd->execute([$idComment]);
}

?>
