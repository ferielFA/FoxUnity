<?php
// Simple admin CRUD for news stored in MySQL (article / categorie tables)
// Usage: open in browser via the dashboard link

require __DIR__ . '/db.php';

// messages to show to admin user
$messages = [];
$errors = [];

$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? '';

// use existing model functions for categories and articles
require_once __DIR__ . '/../../model/article_model.php';
require_once __DIR__ . '/../../model/category_model.php';

$categories = getCategories();

// load all articles via model (handles hot column and joins)
$data = getAllArticles();

function findCategoryName($id, $categories){
  foreach($categories as $c) if (($c['idCategorie'] ?? 0) == $id) return $c['nom'];
  return null;
}

// Helper to save uploads and manage history (delegated to model)
$uploadsDir = __DIR__ . '/uploads/images';
$historyDir = __DIR__ . '/uploads/history';
// comments stored as JSON per-article
$commentsDir = __DIR__ . '/uploads/comments';
@mkdir($uploadsDir, 0755, true);
@mkdir($historyDir, 0755, true);
@mkdir($commentsDir, 0755, true);

// POST actions: add/save/delete/toggle_hot
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'] ?? '';
    if ($act === 'add') {
      $postedId = preg_replace('/[^a-z0-9_-]/i', '', $_POST['id'] ?? '');
      if (empty($postedId)) {
        $errors[] = 'ID is required for new articles.';
      } elseif (!preg_match('/^[a-z0-9_-]+$/i', $postedId)) {
        $errors[] = 'ID may contain only letters, numbers, underscore and hyphen.';
      } else {
        // check duplicate slug
        $dupStmt = $pdo->prepare("SELECT slug FROM article WHERE slug = ? LIMIT 1");
        $dupStmt->execute([$postedId]);
        if ($dupStmt->fetch()) {
          $errors[] = 'An article with this ID already exists.';
        }
      }
      if (empty($_POST['title'])) $errors[] = 'Title is required.';
      if (empty($_POST['content'])) $errors[] = 'Content is required.';
      if (empty($errors)) {
        // handle image upload
        $imagePath = '';
        if (isset($_FILES['image_upload']['tmp_name']) && is_uploaded_file($_FILES['image_upload']['tmp_name'])) {
          $ext = strtolower(pathinfo($_FILES['image_upload']['name'], PATHINFO_EXTENSION));
          if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
            $filename = uniqid('img_') . '.' . $ext;
            $dest = $uploadsDir . '/' . $filename;
            if (move_uploaded_file($_FILES['image_upload']['tmp_name'], $dest)) {
              $imagePath = 'uploads/images/' . $filename;
            }
          }
        }
        // validate and resolve category
        $postedIdCategorie = intval($_POST['idCategorie'] ?? 0);
        $postedCategoryName = trim($_POST['category'] ?? '');
        $validIdCategorie = null;
        if ($postedIdCategorie > 0) {
          foreach ($categories as $c) {
            if (intval($c['idCategorie']) === $postedIdCategorie) { $validIdCategorie = $postedIdCategorie; break; }
          }
          if ($validIdCategorie === null) $validIdCategorie = 1;
        }
        if ($postedCategoryName) {
          $catStmt = $pdo->prepare("SELECT idCategorie FROM categorie WHERE nom = ? LIMIT 1");
          $catStmt->execute([$postedCategoryName]);
          $catRow = $catStmt->fetch();
          if ($catRow) $validIdCategorie = $catRow['idCategorie'];
        }
        // Build article data with hot status
        $item = [
          'slug' => $postedId,
          'id_pub' => 4, // default author/admin id; adjust if needed
          'title' => $_POST['title'],
          'content' => $_POST['content'],
          'excerpt' => $_POST['excerpt'] ?? '',
          'image' => $imagePath,
          'datePublication' => $_POST['datePublication'] ?? date('Y-m-d'),
          'idCategorie' => $validIdCategorie,
          'category' => $postedCategoryName,
          'hot' => isset($_POST['hot']) && $_POST['hot'] === '1' ? 1 : 0,
          'comments' => []
        ];

      // insert into DB
      $stmt = $pdo->prepare("INSERT INTO article
        (slug, id_pub, titre, contenu, excerpt, image, datePublication, idCategorie, hot)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
      $id_pub = 4; // default author/admin id; adjust if needed
      $isHot = isset($_POST['hot']) && $_POST['hot'] === '1' ? 1 : 0;
      $ok = $stmt->execute([
        $postedId,
        $id_pub,
        $item['title'],
        $item['content'],
        $item['excerpt'],
        $item['image'],
        $item['datePublication'],
        $validIdCategorie ?: 0,
        $isHot
      ]);

      if ($ok) {
        $item['idArticle'] = (int)$pdo->lastInsertId();
        saveArticleVersion($postedId, $item, 'created');
        $messages[] = 'Article added successfully. View it on the news page.';
        // After 1.5 seconds, redirect to news page to show new article
        header('Refresh: 1.5; url=news.php');
      } else {
        $errors[] = 'Failed to save article — database error.';
      }
    }
    }
    if ($act === 'save' && $id !== '') {
      // Save existing article
      $newTitle = $_POST['title'] ?? '';
      $newContent = $_POST['content'] ?? '';
      $newExcerpt = $_POST['excerpt'] ?? '';
      $newDate = $_POST['date'] ?? '';
      $newDatePub = $_POST['datePublication'] ?? '';
      $newIdCategorie = intval($_POST['idCategorie'] ?? 0);
      $newCategoryName = trim($_POST['category'] ?? '');
      if (empty($newTitle)) $errors[] = 'Title is required.';
      if (empty($newContent)) $errors[] = 'Content is required.';
      if (empty($errors)) {
        // handle image upload
        $imagePath = $_POST['image_existing'] ?? '';
        if (isset($_FILES['image_upload']['tmp_name']) && is_uploaded_file($_FILES['image_upload']['tmp_name'])) {
          $ext = strtolower(pathinfo($_FILES['image_upload']['name'], PATHINFO_EXTENSION));
          if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
            $filename = uniqid('img_') . '.' . $ext;
            $dest = $uploadsDir . '/' . $filename;
            if (move_uploaded_file($_FILES['image_upload']['tmp_name'], $dest)) {
              $imagePath = 'uploads/images/' . $filename;
            }
          }
        }
        // resolve category
        $validNewIdCategorie = null;
        if ($newIdCategorie > 0) {
          foreach ($categories as $c) {
            if (intval($c['idCategorie']) === $newIdCategorie) { $validNewIdCategorie = $newIdCategorie; break; }
          }
          if ($validNewIdCategorie === null) $validNewIdCategorie = 1;
        }
        if ($newCategoryName) {
          $catStmt = $pdo->prepare("SELECT idCategorie FROM categorie WHERE nom = ? LIMIT 1");
          $catStmt->execute([$newCategoryName]);
          $catRow = $catStmt->fetch();
          if ($catRow) $validNewIdCategorie = $catRow['idCategorie'];
        }

        $upd = $pdo->prepare("UPDATE article SET
          titre = ?,
          contenu = ?,
          excerpt = ?,
          image = ?,
          datePublication = ?,
          idCategorie = ?,
          hot = ?
        WHERE slug = ? LIMIT 1");
        $isHot = isset($_POST['hot']) && $_POST['hot'] === '1' ? 1 : 0;
        $ok = $upd->execute([
          $newTitle,
          $newContent,
          $newExcerpt,
          $imagePath,
          $newDatePub,
          $validNewIdCategorie ?: 0,
          $isHot,
          $id
        ]);

        if ($ok) {
          // Ensure we have the previous article data for history (avoid undefined $current)
          $curStmt = $pdo->prepare("SELECT
            slug AS id,
            titre AS title,
            datePublication AS datePublication,
            datePublication,
            idCategorie,
            excerpt,
            contenu AS content,
            image,
            hot
          FROM article WHERE slug = ? LIMIT 1");
          $curStmt->execute([$id]);
          $current = $curStmt->fetch() ?: [];
          $after = $current ?: [];
          $after['title'] = $newTitle;
          $after['date'] = $newDate;
          $after['datePublication'] = $newDatePub;
          $after['idCategorie'] = $newIdCategorie;
          $after['category'] = $newCategoryName;
          $after['excerpt'] = $newExcerpt;
          $after['content'] = $newContent;
          $after['image'] = $imagePath;
          $after['hot'] = $isHot;
          saveArticleVersion($id, $after, 'edited');
          $messages[] = 'Article updated successfully.';
        } else {
          $errors[] = 'Failed to save changes — database error.';
        }
      }
    }
    if ($act === 'delete' && $id !== '') {
      $delStmt = $pdo->prepare("DELETE FROM article WHERE slug = ? LIMIT 1");
      $ok = $delStmt->execute([$id]);
      if ($ok) {
        $messages[] = 'Article deleted.';
      } else {
        $errors[] = 'Failed to delete article — database error.';
      }
    }
    if ($act === 'toggle_hot' && $id !== '') {
        $toggle = $pdo->prepare("UPDATE article SET hot = NOT hot WHERE slug = ? LIMIT 1");
        if ($toggle->execute([$id])) {
          $check = $pdo->prepare("SELECT hot FROM article WHERE slug = ? LIMIT 1");
          $check->execute([$id]);
          $isHot = $check->fetchColumn();
          $messages[] = 'Article ' . ($isHot ? 'marked as hot' : 'removed from hot news') . '.';
        } else {
          $errors[] = 'Failed to update hot status — database error.';
        }
    }
    // Comment moderation actions: delete single comment or clear all comments for an article
    if ($act === 'delete_comment' && !empty($_POST['slug'])) {
      $slug = $_POST['slug'];
      $index = isset($_POST['index']) ? intval($_POST['index']) : -1;
      $file = $commentsDir . '/' . md5($slug) . '.json';
      if (file_exists($file)) {
        $list = json_decode(file_get_contents($file), true) ?: [];
        if ($index >= 0 && $index < count($list)) {
          array_splice($list, $index, 1);
          if (empty($list)) {
            @unlink($file);
          } else {
            file_put_contents($file, json_encode($list, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
          }
          $messages[] = 'Comment deleted.';
        } else {
          $errors[] = 'Invalid comment index.';
        }
      } else {
        $errors[] = 'No comments found for that article.';
      }
    }
    if ($act === 'clear_comments' && !empty($_POST['slug'])) {
      $slug = $_POST['slug'];
      $file = $commentsDir . '/' . md5($slug) . '.json';
      if (file_exists($file)) {
        @unlink($file);
        $messages[] = 'All comments cleared for that article.';
      } else {
        $errors[] = 'No comments to clear for that article.';
      }
    }
}

// For edit form
$editing = null;
if ($action === 'edit' && $id !== '') {
    foreach ($data as $it) { if (($it['id'] ?? '') === $id) { $editing = $it; break; } }
    // If not found in data, try direct DB query
    if (!$editing) {
        $editStmt = $pdo->prepare("SELECT
          idArticle,
          slug       AS id,
          titre      AS title,
          datePublication AS date,
          datePublication,
          image,
          idCategorie,
          excerpt,
          contenu    AS content,
          hot
        FROM article WHERE slug = ? LIMIT 1");
        $editStmt->execute([$id]);
        $editing = $editStmt->fetch();
    }
}

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>News Admin - Dashboard</title>
  <link rel="stylesheet" href="style.css">
</head>
<body class="dashboard-body">
  <div class="stars"></div>
  <div class="shooting-star"></div>
  <div class="shooting-star"></div>
  <div class="shooting-star"></div>

  <div class="sidebar">
    <img src="../images/Nine__1_-removebg-preview.png" alt="Nine Tailed Fox Logo" class="dashboard-logo">
    <h2>Dashboard</h2>
    <a href="#" class="">Overview</a>
    <a href="#">Users</a>
    <a href="#">Shop</a>
    <a href="#">Trade History</a>
    <a href="#">Events</a>
    <a href="news_admin.php" class="active">News</a>
    <a href="news_history.php" id="news-history-link">News History</a>
    <a href="categories.php" id="categories-link">Categories</a>
    <a href="#">Support</a>
    <a href="../front/indexf.html">← Return Homepage</a>
  </div>

  <div class="main">
    <div class="topbar">
      <h1>News Administration</h1>
      <div class="user">
        <img src="../images/meriem.png" alt="User Avatar">
        <span>FoxLeader</span>
      </div>
    </div>

    <div class="content">
      <div class="card" style="width:100%; grid-column: 1 / -1;">
      
        <div style="margin-bottom:16px;border-bottom:1px solid #333;padding-bottom:12px">
          <button id="tab-manage" class="tab-btn active" style="margin-right:8px">Manage News</button>
          <button id="tab-history" class="tab-btn">News History</button>
          <button id="tab-comments" class="tab-btn" style="margin-left:8px">Comments</button>
        </div>

        <div id="tab-manage-content">
          <div style="display:flex;justify-content:space-between;align-items:center">
            <h2 style="margin:0">Manage News</h2>
            <div>
              <a class="btn" href="news_admin.php?action=new">+ Add Article</a>
              <a class="btn" href="news.php" target="_blank">View Public News</a>
            </div>
          </div>

              <?php if (!empty($messages) || !empty($errors)): ?>
                <div style="margin-top:10px">
                  <?php foreach ($messages as $m): ?><div style="color:#b6ffb3;padding:8px;border-radius:6px;background:#0b2b10;margin-bottom:6px"><?php echo htmlspecialchars($m); ?></div><?php endforeach; ?>
                  <?php foreach ($errors as $e): ?><div style="color:#ffd6d6;padding:8px;border-radius:6px;background:#2b0b0b;margin-bottom:6px"><?php echo htmlspecialchars($e); ?></div><?php endforeach; ?>
                </div>
              <?php endif; ?>

          <?php if ($action === 'new' || $editing !== null): ?>
            <?php $it = $editing ?? ['id'=>'','title'=>'','date'=>date('d-m-Y'),'datePublication'=>date('Y-m-d'),'image'=>'','idCategorie'=>0,'category'=>'','excerpt'=>'','content'=>'','hot'=>0]; ?>
            <section style="margin-top:16px">
              <h3><?php echo $editing ? 'Edit' : 'New'; ?> Article</h3>
              <form id="article-form" class="admin-form" method="post" action="news_admin.php<?php echo $editing ? '?id='.urlencode($editing['id']) : '' ;?>" enctype="multipart/form-data">
                <input type="hidden" name="action" value="<?php echo $editing ? 'save' : 'add'; ?>">
                <?php if (!$editing): ?><label for="fld-id">ID (alphanumeric):</label><input id="fld-id" name="id" value="<?php echo htmlspecialchars($it['id'] ?? ''); ?>" class="small"><?php endif; ?>
                <label for="fld-datePublication">Publication Date</label><input id="fld-datePublication" name="datePublication" type="date" value="<?php echo htmlspecialchars($it['datePublication'] ?? date('Y-m-d')); ?>" class="small">
                <label for="fld-title">Title</label><input id="fld-title" name="title" value="<?php echo htmlspecialchars($it['title'] ?? ''); ?>">
                <label for="fld-date">Displayed Date</label><input id="fld-date" name="date" type="date" value="<?php echo htmlspecialchars($it['date'] ?? date('d-m-Y')); ?>" class="small">
                
                <label for="fld-image-upload">Upload Image</label>
                <input id="fld-image-upload" type="file" name="image_upload" accept="image/*" style="padding:8px;margin:8px 0;background:#0b0b0b;border:1px solid #333;color:#fff;border-radius:6px;width:100%">
                <input type="hidden" id="fld-image-existing" name="image_existing" value="<?php echo htmlspecialchars($it['image'] ?? ''); ?>">
                <?php if (!empty($it['image'])): ?>
                  <div style="margin:12px 0;border-radius:8px;overflow:hidden;background:#0b0b0b;border:1px solid #333">
                    <img id="img-preview" src="<?php echo htmlspecialchars($it['image']); ?>" style="max-width:100%;max-height:200px;display:block" alt="Preview">
                  </div>
                <?php else: ?>
                  <div id="img-preview-container" style="display:none;margin:12px 0;border-radius:8px;overflow:hidden;background:#0b0b0b;border:1px solid #333">
                    <img id="img-preview" style="max-width:100%;max-height:200px;display:block" alt="Preview">
                  </div>
                <?php endif; ?>
                
                <label for="fld-category">Category</label>
                <select id="fld-idCategorie" name="idCategorie">
                  <option value="0">-- Select category --</option>
                  <?php foreach ($categories as $c): ?>
                    <option value="<?php echo intval($c['idCategorie']); ?>" <?php if (!empty($it['idCategorie']) && intval($it['idCategorie'])===intval($c['idCategorie'])) echo 'selected'; ?>><?php echo htmlspecialchars($c['nom']); ?></option>
                  <?php endforeach; ?>
                </select>
                <small style="display:block;color:#aaa;margin-top:6px">Or enter a custom category name below (will be saved to article only):</small>
                <input id="fld-category" name="category" value="<?php echo htmlspecialchars($it['category'] ?? ''); ?>">
                
                <div style="margin:16px 0">
                  <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                    <input type="checkbox" id="fld-hot" name="hot" value="1" <?php echo ($it['hot'] ?? 0) ? 'checked' : ''; ?> style="margin:0">
                    <span>🔥 Mark as Hot News (will appear at top of news page)</span>
                  </label>
                </div>
                <label for="fld-excerpt">Excerpt / Summary</label><textarea id="fld-excerpt" name="excerpt" rows="3"><?php echo htmlspecialchars($it['excerpt'] ?? ''); ?></textarea>
                <label for="fld-content">Full Content</label><textarea id="fld-content" name="content" rows="8"><?php echo htmlspecialchars($it['content'] ?? ''); ?></textarea>
                <p>
                  <button id="btn-save" class="btn" type="submit">Save</button>
                  <a class="btn" href="news_admin.php">Cancel</a>
                  <button id="btn-restore" class="btn" type="button" style="margin-left:8px;">Restore Draft</button>
                  <button id="btn-clear-draft" class="btn" type="button" style="margin-left:6px;background:#c33;color:#fff;border-color:#c33;">Clear Draft</button>
                </p>
              </form>
            </section>
          <?php else: ?>

            <section style="margin-top:16px">
              <h3>Existing Articles (<?php echo count($data); ?>)</h3>
              <table class="admin-table" id="articles-table">
                <thead><tr><th>#</th><th>Slug</th><th>Title</th><th>Date</th><th>Category</th><th>Hot</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($data as $row): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($row['idArticle'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['id'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['title'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($row['date'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars((findCategoryName($row['idCategorie'] ?? 0, $categories) ?? '') . (empty($row['idCategorie']) ? '' : ' (ID:'. ($row['idCategorie']) .')')); ?></td>
                    <td><?php echo $row['hot'] ? '🔥 Yes' : 'No'; ?></td>
                    <td class="admin-actions">
                      <a href="news_article.php?id=<?php echo urlencode($row['id']); ?>" target="_blank">View</a> |
                      <a href="news_admin.php?action=edit&id=<?php echo urlencode($row['id']); ?>">Edit</a> |
                      <a href="news_admin.php?action=delete&id=<?php echo urlencode($row['id']); ?>" onclick="return confirm('Delete this article?');">Delete</a>
                      <?php if ($row['hot']): ?>
                        | <a href="news_admin.php" onclick="toggleHot('<?php echo urlencode($row['id']); ?>'); return false;" style="color:#ff7a00">🔥 Hot</a>
                      <?php else: ?>
                        | <a href="news_admin.php" onclick="toggleHot('<?php echo urlencode($row['id']); ?>'); return false;" style="color:#888">🔥 Make Hot</a>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </section>

            <?php if ($action === 'delete' && $id !== ''): ?>
              <section style="margin-top:12px">
                <h3>Confirm Delete</h3>
                <p>Are you sure you want to delete <strong><?php echo htmlspecialchars($id); ?></strong>?</p>
                <form class="admin-form" method="post" action="news_admin.php?id=<?php echo urlencode($id); ?>">
                  <input type="hidden" name="action" value="delete">
                  <button class="btn" type="submit">Yes, delete</button>
                  <a class="btn" href="news_admin.php">Cancel</a>
                </form>
              </section>
            <?php endif; ?>

          <?php endif; ?>

        </div>

        <!-- HISTORY TAB -->
        <div id="tab-history-content" style="display:none">
          <h2 style="margin:0 0 16px">Article Edit History</h2>
          <div id="history-list" style="max-height:600px;overflow-y:auto">
            <?php 
              $historyFiles = glob($historyDir . '/*.json') ?: [];
              if (empty($historyFiles)): ?>
              <p style="color:#bbb">No history available yet.</p>
            <?php else:
              foreach ($historyFiles as $hf) {
                $versions = json_decode(file_get_contents($hf), true) ?: [];
                if (empty($versions)) continue;
                $articleId = $versions[count($versions)-1]['article']['id'] ?? 'unknown';
                echo '<div style="background:#111;padding:12px;border-radius:8px;margin-bottom:12px;border-left:3px solid #ff7a00">';
                echo '<strong>' . htmlspecialchars($articleId) . '</strong> (' . count($versions) . ' version' . (count($versions)!==1?'s':'') . ')<br>';
                echo '<table style="width:100%;margin-top:8px;font-size:0.85rem;border-collapse:collapse">';
                echo '<thead><tr style="border-bottom:1px solid #333"><th style="text-align:left;padding:6px">Timestamp</th><th style="text-align:left;padding:6px">Action</th></tr></thead><tbody>';
                foreach ($versions as $idx => $v) {
                  $ts = $v['timestamp'] ?? '';
                  $act = $v['action'] ?? '';
                  $art = $v['article'] ?? [];
                  echo '<tr style="border-bottom:1px solid #222"><td style="padding:6px">' . htmlspecialchars($ts) . '</td><td style="padding:6px">' . htmlspecialchars($act) . '</td>';
                  if ($act !== 'deleted') {
                    echo '<td style="padding:6px"><form method="post" action="news_admin.php?action=restore" style="display:inline"><input type="hidden" name="restore_data" value="' . htmlspecialchars(json_encode($art)) . '"><button type="submit" class="btn" style="padding:4px 8px;font-size:0.8rem">Restore</button></form></td>';
                  }
                  echo '</tr>';
                }
                echo '</tbody></table>';
                echo '</div>';
              }
            endif;
            ?>
          </div>
        </div>
        
        <!-- COMMENTS TAB -->
        <div id="tab-comments-content" style="display:none">
          <h2 style="margin:0 0 16px">Comments Moderation</h2>
          <div style="max-height:600px;overflow:auto">
            <?php
            // List articles that have comment files
            $foundAny = false;
            foreach ($data as $row) {
              $slug = $row['id'] ?? '';
              if (!$slug) continue;
              $cf = $commentsDir . '/' . md5($slug) . '.json';
              if (!file_exists($cf)) continue;
              $foundAny = true;
              $list = json_decode(file_get_contents($cf), true) ?: [];
              echo '<div style="background:#111;padding:12px;border-radius:8px;margin-bottom:12px;border-left:3px solid #ff7a00">';
              echo '<strong>' . htmlspecialchars($slug) . '</strong> — ' . count($list) . ' comment' . (count($list)!==1?'s':'') . '<br>';
              echo '<div style="margin-top:8px">';
              foreach ($list as $i => $c) {
                echo '<div style="background:#0b0b0b;padding:10px;border-radius:6px;margin-bottom:8px">';
                echo '<div style="font-weight:700;color:#fff">' . htmlspecialchars($c['name'] ?? 'Anonymous') . ' <span style="font-weight:400;color:#999;margin-left:8px">' . htmlspecialchars($c['date'] ?? '') . '</span></div>';
                echo '<div style="color:#ddd;margin-top:6px">' . nl2br(htmlspecialchars($c['text'] ?? '')) . '</div>';
                // delete single comment form
                echo '<form method="post" action="news_admin.php" style="margin-top:8px">';
                echo '<input type="hidden" name="action" value="delete_comment">';
                echo '<input type="hidden" name="slug" value="' . htmlspecialchars($slug) . '">';
                echo '<input type="hidden" name="index" value="' . intval($i) . '">';
                echo '<button class="btn" type="submit" style="background:#c33;color:#fff;border-color:#c33">Delete</button>';
                echo '</form>';
                echo '</div>';
              }
              // clear all comments for article
              echo '<form method="post" action="news_admin.php" style="margin-top:6px">';
              echo '<input type="hidden" name="action" value="clear_comments">';
              echo '<input type="hidden" name="slug" value="' . htmlspecialchars($slug) . '">';
              echo '<button class="btn" type="submit" style="background:#444;color:#fff">Clear all comments for this article</button>';
              echo '</form>';
              echo '</div>';
            }
            if (!$foundAny) echo '<p style="color:#bbb">No comments available.</p>';
            ?>
          </div>
        </div>
      </div>
    </div>

    <footer class="site-footer">
      © 2025 <span>Nine Tailed Fox</span>. All Rights Reserved.
    </footer>
  </div>

  <div class="transition-screen"></div>
  <script>
    // Ensure the transition overlay is hidden after page load
    window.addEventListener('load', function(){
      try{
        var t = document.querySelector('.transition-screen');
        if(t) t.classList.add('hidden');
      }catch(e){}
    });
  </script>
  <div class="toast-container" id="toast-container"></div>
  
  <style>
    .tab-btn {
      background: transparent;
      border: 1px solid #333;
      color: #bbb;
      padding: 10px 16px;
      border-radius: 6px;
      cursor: pointer;
      transition: all 0.2s ease;
      font-weight: 600;
    }
    .tab-btn.active {
      background: #ff7a00;
      color: #000;
      border-color: #ff7a00;
    }
    .tab-btn:hover {
      border-color: #ff7a00;
    }
  </style>

  <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
  <script>
    // Tab switching - ensure it works properly
    document.addEventListener('DOMContentLoaded', function(){
      console.log('DOM loaded, setting up tabs');
      
      var tabManageBtn = document.getElementById('tab-manage');
      var tabHistoryBtn = document.getElementById('tab-history');
      var tabCommentsBtn = document.getElementById('tab-comments');
      var tabManageContent = document.getElementById('tab-manage-content');
      var tabHistoryContent = document.getElementById('tab-history-content');
      var tabCommentsContent = document.getElementById('tab-comments-content');
      
      console.log('Elements found:', {
        tabManageBtn: !!tabManageBtn,
        tabHistoryBtn: !!tabHistoryBtn,
        tabManageContent: !!tabManageContent,
        tabHistoryContent: !!tabHistoryContent
      });
      
      if (tabManageBtn && tabHistoryBtn && tabManageContent && tabHistoryContent && tabCommentsBtn && tabCommentsContent) {
        tabManageBtn.addEventListener('click', function(e){
          e.preventDefault();
          console.log('Manage tab clicked');
          tabManageBtn.classList.add('active');
          tabHistoryBtn.classList.remove('active');
          tabCommentsBtn.classList.remove('active');
          tabManageContent.style.display = 'block';
          tabHistoryContent.style.display = 'none';
          tabCommentsContent.style.display = 'none';
        });
        
        tabHistoryBtn.addEventListener('click', function(e){
          e.preventDefault();
          console.log('History tab clicked');
          tabHistoryBtn.classList.add('active');
          tabManageBtn.classList.remove('active');
          tabCommentsBtn.classList.remove('active');
          tabHistoryContent.style.display = 'block';
          tabManageContent.style.display = 'none';
          tabCommentsContent.style.display = 'none';
        });
        
        tabCommentsBtn.addEventListener('click', function(e){
          e.preventDefault();
          tabCommentsBtn.classList.add('active');
          tabManageBtn.classList.remove('active');
          tabHistoryBtn.classList.remove('active');
          tabCommentsContent.style.display = 'block';
          tabManageContent.style.display = 'none';
          tabHistoryContent.style.display = 'none';
        });
      }
    });

    // Image file preview
    var fileInput = document.getElementById('fld-image-upload');
    if (fileInput) {
      fileInput.addEventListener('change', function(e){
        var file = e.target.files[0];
        if (file) {
          var reader = new FileReader();
          reader.onload = function(ev){
            var existingImg = document.getElementById('img-preview');
            if (!existingImg) {
              var container = document.getElementById('img-preview-container');
              if (!container) {
                container = document.createElement('div');
                container.id = 'img-preview-container';
                container.style.cssText = 'margin:12px 0;border-radius:8px;overflow:hidden;background:#0b0b0b;border:1px solid #333';
                fileInput.parentNode.appendChild(container);
              }
              existingImg = document.createElement('img');
              existingImg.id = 'img-preview';
              existingImg.style.cssText = 'max-width:100%;max-height:200px;display:block';
              container.appendChild(existingImg);
            }
            if (existingImg.parentNode && existingImg.parentNode.id === 'img-preview-container') {
              existingImg.parentNode.style.display = 'block';
            } else if (!existingImg.parentNode || existingImg.parentNode.tagName === 'BODY') {
              var container = document.getElementById('img-preview-container');
              if (!container) {
                container = document.createElement('div');
                container.id = 'img-preview-container';
                container.style.cssText = 'margin:12px 0;border-radius:8px;overflow:hidden;background:#0b0b0b;border:1px solid #333';
                fileInput.parentNode.appendChild(container);
              }
              container.appendChild(existingImg);
              container.style.display = 'block';
            }
            existingImg.src = ev.target.result;
          };
          reader.readAsDataURL(file);
        }
      });
    }

    // Client-side: TinyMCE editor, autosave/restore drafts, toast handling, and validation
    (function(){
      function toast(message, type){
        var container = document.getElementById('toast-container');
        if(!container) return;
        var el = document.createElement('div'); el.className = 'toast ' + (type||''); el.textContent = message;
        container.appendChild(el);
        setTimeout(function(){ el.style.opacity = '1'; }, 10);
        setTimeout(function(){ el.style.opacity = '0'; setTimeout(function(){ try{ container.removeChild(el); }catch(e){} },400); }, 4500);
      }

      var serverMessages = <?php echo json_encode($messages); ?> || [];
      var serverErrors = <?php echo json_encode($errors); ?> || [];
      var currentId = <?php echo json_encode($editing['id'] ?? 'new'); ?>;

      document.addEventListener('DOMContentLoaded', function(){
        serverMessages.forEach(function(m){ if(m) toast(m,''); });
        serverErrors.forEach(function(e){ if(e) toast(e,'error'); });
      });

      // Autosave
      var autosaveInterval = 5000; // ms
      var autosaveTimer = null;
      function scheduleAutosave(){ if(autosaveTimer) clearTimeout(autosaveTimer); autosaveTimer = setTimeout(doAutosave, autosaveInterval); }
      function doAutosave(){ try{
        var key = 'news_draft_' + currentId;
        var payload = {
          title: (document.getElementById('fld-title')||{}).value || '',
          image: (document.getElementById('fld-image-existing')||{}).value || '',
          excerpt: (document.getElementById('fld-excerpt')||{}).value || '',
          content: (document.getElementById('fld-content')||{}).value || '',
          timestamp: Date.now()
        };
        localStorage.setItem(key, JSON.stringify(payload));
      }catch(e){}

      // Schedule autosave on input
      ['fld-title','fld-excerpt','fld-content','fld-date','fld-datePublication'].forEach(function(id){
        var el = document.getElementById(id);
        if (el) el.addEventListener('input', scheduleAutosave);
      });

      // Restore / Clear
      var btnRestore = document.getElementById('btn-restore');
      var btnClear = document.getElementById('btn-clear-draft');
      if(btnRestore){ btnRestore.addEventListener('click', function(){ try{
        var key = 'news_draft_' + currentId; var raw = localStorage.getItem(key); if(!raw){ toast('No draft found','error'); return; }
        var d = JSON.parse(raw);
        if(document.getElementById('fld-title')) document.getElementById('fld-title').value = d.title || '';
        if(document.getElementById('fld-image-existing')) document.getElementById('fld-image-existing').value = d.image || '';
        if(document.getElementById('fld-excerpt')) document.getElementById('fld-excerpt').value = d.excerpt || '';
        if(document.getElementById('fld-content')) document.getElementById('fld-content').value = d.content || '';
        toast('Draft restored'); }catch(e){ toast('Failed to restore draft','error'); } }); }
      if(btnClear){ btnClear.addEventListener('click', function(){ try{ localStorage.removeItem('news_draft_' + currentId); toast('Draft cleared'); }catch(e){ toast('Failed to clear draft','error'); } }); }

      // Clear draft if server reports success
      document.addEventListener('DOMContentLoaded', function(){
        if(serverMessages && serverMessages.length){
          var keys = ['added successfully','updated successfully','deleted'];
          var clear = serverMessages.some(function(m){ m = (m||'').toLowerCase(); return keys.some(function(k){ return m.indexOf(k)!==-1; }); });
          if(clear){ try{ localStorage.removeItem('news_draft_' + currentId); }catch(e){} }
        }
      });

      // Validation on submit
      var form = document.getElementById('article-form');
      if(form){ form.addEventListener('submit', function(e){
        var isEdit = <?php echo $editing ? 'true' : 'false'; ?>;
        var idField = document.getElementById('fld-id');
        var title = document.getElementById('fld-title');
        var errors = [];
        if(!isEdit){ if(!idField || !idField.value.trim()) errors.push('ID is required for new articles.'); else if(!/^[a-z0-9_-]+$/i.test(idField.value.trim())) errors.push('ID may contain only letters, numbers, underscore and hyphen.'); }
        if(!title || !title.value.trim()) errors.push('Title is required.');
        if(errors.length){ e.preventDefault(); errors.forEach(function(m){ toast(m,'error'); }); return false; }
      }); }

      // Toggle hot status
      window.toggleHot = function(articleId) {
        var formData = new FormData();
        formData.append('action', 'toggle_hot');
        formData.append('id', articleId);
        
        fetch('news_admin.php', {
          method: 'POST',
          body: formData
        })
        .then(response => response.text())
        .then(html => {
          // Reload page to show updated status
          window.location.reload();
        })
        .catch(error => {
          toast('Failed to update hot status', 'error');
        });
      };
    })();
  </script>
</body>
</html>
