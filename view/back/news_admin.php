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
require_once __DIR__ . '/../../model/comment_model.php';

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
// mutes file (store muted commenter names -> expiry timestamp)
$mutesFile = $commentsDir . '/mutes.json';
if (!file_exists($mutesFile)) file_put_contents($mutesFile, json_encode(new stdClass()));

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
        // After 1.5 seconds, redirect to public news page (moved to front)
        header('Refresh: 1.5; url=../front/news.php');
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
      $ok = deleteEmbeddedCommentBySlug($slug, $index);
      if ($ok) {
        $messages[] = 'Comment deleted.';
      } else {
        $errors[] = 'Failed to delete comment (invalid index or storage error).';
      }
    }
    if ($act === 'clear_comments' && !empty($_POST['slug'])) {
      $slug = $_POST['slug'];
      $ok = clearEmbeddedCommentsBySlug($slug);
      if ($ok) {
        $messages[] = 'All comments cleared for that article.';
      } else {
        $errors[] = 'Failed to clear comments for that article.';
      }
    }

    // Mute / unmute commenter (global by name)
    if ($act === 'mute_user' && !empty($_POST['slug'])) {
      $slug = $_POST['slug'];
      $name = trim($_POST['name'] ?? '');
      $minutes = max(0, intval($_POST['minutes'] ?? 0));
      if ($name === '' || $minutes <= 0) {
        $errors[] = 'Name and positive minutes are required to mute.';
      } else {
        $mutes = json_decode(file_get_contents($mutesFile), true) ?: [];
        $mutes[strtolower($name)] = time() + ($minutes * 60);
        file_put_contents($mutesFile, json_encode($mutes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $messages[] = 'User ' . htmlspecialchars($name) . ' muted for ' . intval($minutes) . ' minutes.';
      }
    }
    if ($act === 'unmute_user' && !empty($_POST['slug'])) {
      $slug = $_POST['slug'];
      $name = trim($_POST['name'] ?? '');
      if ($name === '') {
        $errors[] = 'Name is required to unmute.';
      } else {
        $mutes = json_decode(file_get_contents($mutesFile), true) ?: [];
        $key = strtolower($name);
        if (isset($mutes[$key])) {
          unset($mutes[$key]);
          file_put_contents($mutesFile, json_encode($mutes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
          $messages[] = 'User ' . htmlspecialchars($name) . ' unmuted.';
        } else {
          $errors[] = 'User not muted.';
        }
      }
    }

    // Update/edit a single comment
    if ($act === 'update_comment' && !empty($_POST['slug'])) {
      $slug = $_POST['slug'];
      $index = isset($_POST['index']) ? intval($_POST['index']) : -1;
      $name = trim($_POST['name'] ?? '');
      $text = trim($_POST['text'] ?? '');
      $ok = updateEmbeddedCommentBySlug($slug, $index, $name, $text);
      if ($ok) {
        $messages[] = 'Comment updated.';
      } else {
        $errors[] = 'Failed to update comment (invalid index or storage error).';
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
    <a href="dashboard.php" class="">Overview</a>
    <a href="#">Users</a>
    <a href="#">Shop</a>
    <a href="#">Trade History</a>
    <a href="#">Events</a>
    <a href="news_admin.php" class="active">News</a>
    <a href="news_history.php" id="news-history-link">News History</a>
    <a href="categories.php" id="categories-link">Categories</a>
    <a href="#">Support</a>
    <a href="../front/indexf.php">← Return Homepage</a>
  </div>

  <div class="main">
    <div class="topbar">
      <h1>News Administration</h1>
      <div class="user">
        <img src="../images/rayen.png" alt="User Avatar">
        <span>FoxAdmin</span>
      </div>
    </div>

    <div class="content">
      <div class="card" style="width:100%; grid-column: 1 / -1;">
      
        <div style="margin-bottom:16px;border-bottom:1px solid #333;padding-bottom:12px">
          <span style="font-weight:700;color:#fff;font-size:1.05rem">Manage News</span>
        </div>

        <div id="tab-manage-content">
          <div style="display:flex;justify-content:space-between;align-items:center">
            <h2 style="margin:0">Manage News</h2>
              <div>
              <a class="btn" href="news_admin.php?action=new">+ Add Article</a>
              <a class="btn" href="../front/news.php" target="_blank">View Public News</a>
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
                      <a href="../front/news_article.php?id=<?php echo urlencode($row['id']); ?>" target="_blank">View</a> |
                      <a href="news_admin.php?action=edit&id=<?php echo urlencode($row['id']); ?>">Edit</a> |
                      <a href="news_admin.php?action=delete&id=<?php echo urlencode($row['id']); ?>" onclick="return confirm('Delete this article?');">Delete</a>
                      | <a href="news_admin.php?comments=<?php echo urlencode($row['id']); ?>">Comments</a>
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

            <?php
            // If comments for a specific article were requested, render them here
            $showCommentsFor = $_GET['comments'] ?? '';
            if ($showCommentsFor) {
              $slug = $showCommentsFor;
                echo '<div style="margin-top:18px;background:#0b0b0b;padding:12px;border-radius:8px;border-left:3px solid #ff7a00">';
                echo '<h3 style="margin-top:0;color:#ff7a00">Comments for: ' . htmlspecialchars($slug) . '</h3>';
                $list = getEmbeddedCommentsBySlug($slug);
                if (empty($list)) {
                  echo '<p style="color:#bbb">No comments for this article.</p>';
                } else {
                  foreach ($list as $i => $c) {
                    $name = htmlspecialchars($c['name'] ?? '');
                    $date = htmlspecialchars($c['date'] ?? '');
                    $text = htmlspecialchars($c['text'] ?? '');
                    // check mute status for this commenter
                    $mutes = json_decode(file_get_contents($mutesFile), true) ?: [];
                    $isMuted = false; $mutedUntil = 0;
                    $lower = strtolower($c['name'] ?? '');
                    if ($lower && isset($mutes[$lower]) && intval($mutes[$lower]) > time()) { $isMuted = true; $mutedUntil = intval($mutes[$lower]); }

                    echo '<form method="post" action="news_admin.php" style="background:#111;padding:10px;border-radius:6px;margin-bottom:10px">';
                    echo '<input type="hidden" name="action" value="update_comment">';
                    echo '<input type="hidden" name="slug" value="' . htmlspecialchars($slug) . '">';
                    echo '<input type="hidden" name="index" value="' . intval($i) . '">';
                    echo '<div style="display:flex;gap:8px;align-items:flex-start">';
                    echo '<div style="flex:1">';
                    echo '<label style="color:#ccc;display:block;margin-bottom:6px">Name</label>';
                    echo '<input name="name" value="' . $name . '" style="width:100%;padding:8px;margin-bottom:8px;background:#0b0b0b;border:1px solid #333;color:#fff;border-radius:6px">';
                    echo '<label style="color:#ccc;display:block;margin-bottom:6px">Comment</label>';
                    echo '<textarea name="text" rows="4" style="width:100%;padding:8px;background:#0b0b0b;border:1px solid #333;color:#fff;border-radius:6px">' . $text . '</textarea>';
                    echo '<div style="margin-top:8px">';
                    echo '<button class="btn" type="submit">Save</button> ';
                    echo '</div>';
                    echo '</div>';
                    echo '<div style="width:140px;text-align:right;">';
                    echo '<div style="color:#999;font-size:0.85rem;margin-bottom:12px">' . $date . '</div>';
                    if ($isMuted) {
                      echo '<div style="color:#ffb67a;font-size:0.85rem;margin-bottom:8px">Muted until: ' . date('Y-m-d H:i:s', $mutedUntil) . '</div>';
                      echo '<form method="post" action="news_admin.php" style="margin:0">';
                      echo '<input type="hidden" name="action" value="unmute_user">';
                      echo '<input type="hidden" name="slug" value="' . htmlspecialchars($slug) . '">';
                      echo '<input type="hidden" name="name" value="' . $name . '">';
                      echo '<button class="btn" type="submit" style="background:#666;color:#fff;border-color:#666;margin-top:6px">Unmute</button>';
                      echo '</form>';
                    } else {
                      echo '<div style="margin-bottom:8px">';
                      echo '<form method="post" action="news_admin.php" style="display:flex;gap:6px;justify-content:flex-end;align-items:center;margin:0">';
                      echo '<input type="hidden" name="action" value="mute_user">';
                      echo '<input type="hidden" name="slug" value="' . htmlspecialchars($slug) . '">';
                      echo '<input type="hidden" name="name" value="' . $name . '">';
                      echo '<input type="number" name="minutes" value="60" min="1" style="width:72px;padding:6px;border-radius:6px;border:1px solid #333;background:#0b0b0b;color:#fff">';
                      echo '<button class="btn" type="submit" style="background:#444;color:#fff;border-color:#444">Mute (min)</button>';
                      echo '</form>';
                      echo '</div>';
                    }
                    echo '<form method="post" action="news_admin.php" onsubmit="return confirm(\'Delete this comment?\')">';
                    echo '<input type="hidden" name="action" value="delete_comment">';
                    echo '<input type="hidden" name="slug" value="' . htmlspecialchars($slug) . '">';
                    echo '<input type="hidden" name="index" value="' . intval($i) . '">';
                    echo '<button class="btn" type="submit" style="background:#c33;color:#fff;border-color:#c33;margin-top:6px">Delete</button>';
                    echo '</form>';
                    echo '</div>';
                    echo '</div>';
                    echo '</form>';
                  }
                  echo '<form method="post" action="news_admin.php">';
                  echo '<input type="hidden" name="action" value="clear_comments">';
                  echo '<input type="hidden" name="slug" value="' . htmlspecialchars($slug) . '">';
                  echo '<button class="btn" type="submit" style="background:#444;color:#fff">Clear all comments for this article</button>';
                  echo ' <a class="btn" href="news_admin.php">Back</a>';
                  echo '</form>';
                }
                echo '</div>';
            }
            ?>

          <?php endif; ?>
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
