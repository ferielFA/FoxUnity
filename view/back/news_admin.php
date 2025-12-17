<?php
// News admin page - uses NewsAdminController for MVC pattern
require_once __DIR__ . '/../../model/db.php';
require_once __DIR__ . '/../../controller/NewsAdminController.php';
require_once __DIR__ . '/../../model/Comment.php';
require_once __DIR__ . '/../../model/Subscriber.php';
require_once __DIR__ . '/../../controller/UserController.php';

// Check if user is logged in and is Admin
if (!UserController::isLoggedIn()) {
  header('Location: ../front/login.php');
  exit();
}

$currentUser = UserController::getCurrentUser();
$userRole = strtolower($currentUser ? $currentUser->getRole() : '');
if (!$currentUser || ($userRole !== 'admin' && $userRole !== 'superadmin')) {
  header('Location: ../front/index.php');
  exit();
}

// Get user image
$userImage = null;
if ($currentUser->getImage()) {
  $userImage = '../../view/' . $currentUser->getImage();
}

// Create directories for uploads if they don't exist
$uploadsDir = __DIR__ . '/uploads/images';
$historyDir = __DIR__ . '/uploads/history';
$commentsDir = __DIR__ . '/uploads/comments';
@mkdir($uploadsDir, 0755, true);
@mkdir($historyDir, 0755, true);
@mkdir($commentsDir, 0755, true);

// Comment moderation is now handled through the database
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>News Admin - Dashboard</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    /* Admin Dropdown Styles (Matches dashboard.php) */
    .admin-dropdown {
      position: relative;
      display: inline-block;
    }

    .admin-user {
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 10px;
      transition: all 0.3s ease;
      padding: 5px 10px;
      border-radius: 8px;
    }

    .admin-user:hover {
      background: rgba(255, 122, 0, 0.1);
    }

    .admin-user img {
      width: 35px;
      height: 35px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid #ff7a00;
    }

    .admin-user i.fa-user-circle {
      font-size: 35px;
      color: #ff7a00;
    }

    .admin-user span {
      color: #fff;
      font-weight: 600;
      font-size: 16px;
    }

    .admin-user i.fa-chevron-down {
      font-size: 12px;
      color: #ff7a00;
      transition: transform 0.3s ease;
    }

    .admin-dropdown.active .admin-user i.fa-chevron-down {
      transform: rotate(180deg);
    }

    .admin-dropdown-menu {
      position: absolute;
      top: 100%;
      right: 0;
      margin-top: 10px;
      background: rgba(20, 20, 20, 0.98);
      border: 2px solid rgba(255, 122, 0, 0.3);
      border-radius: 12px;
      min-width: 200px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
      opacity: 0;
      visibility: hidden;
      transform: translateY(-10px);
      transition: all 0.3s ease;
      z-index: 1000;
      overflow: hidden;
    }

    .admin-dropdown.active .admin-dropdown-menu {
      opacity: 1;
      visibility: visible;
      transform: translateY(0);
    }

    .dropdown-item {
      padding: 12px 15px;
      color: #fff;
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 10px;
      transition: all 0.3s ease;
      border-left: 3px solid transparent;
    }

    .dropdown-item:hover {
      background: rgba(255, 122, 0, 0.1);
      border-left-color: #ff7a00;
    }

    .dropdown-item i {
      font-size: 16px;
      color: #ff7a00;
      width: 20px;
    }

    .dropdown-divider {
      height: 1px;
      background: rgba(255, 122, 0, 0.2);
      margin: 5px 0;
    }

    .dropdown-item.logout {
      color: #ff4444;
    }

    .dropdown-item.logout i {
      color: #ff4444;
    }

    .dropdown-item.logout:hover {
      background: rgba(255, 68, 68, 0.1);
      border-left-color: #ff4444;
    }
  </style>
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
    <a href="users.php">Users</a>
    <a href="shopb.php">Shop</a>
    <a href="tradingb.php">Trade History</a>
    <a href="eventsb.php">Events</a>
    <a href="news_admin.php" class="active">News</a>
    <a href="news_history.php" id="news-history-link">News History</a>
    <a href="categories.php" id="categories-link">Categories</a>
    <a href="newsletter_admin.php" id="newsletter-link">Newsletter</a>
    <a href="evaluations_publiques.php">Évaluations Publiques</a>
    <a href="../front/index.php">← Return Homepage</a>
  </div>

  <div class="main" style="padding:0 1vw 30px 2vw;max-width:1800px;margin:0 auto;">
    <div class="topbar" style="margin-top:16px;">
      <h1>News Administration</h1>
      <div class="admin-dropdown" id="adminDropdown">
        <div class="user admin-user">
          <?php if ($userImage): ?>
            <img src="<?php echo htmlspecialchars($userImage); ?>" alt="Admin Avatar">
          <?php else: ?>
            <i class="fas fa-user-circle"></i>
          <?php endif; ?>
          <span><?php echo htmlspecialchars($currentUser->getUsername()); ?></span>
          <i class="fas fa-chevron-down"></i>
        </div>

        <div class="admin-dropdown-menu">
          <a href="admin-profile.php" class="dropdown-item">
            <i class="fas fa-user"></i>
            <span>My Profile</span>
          </a>

          <div class="dropdown-divider"></div>

          <a href="../front/logout.php" class="dropdown-item logout">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
          </a>
        </div>
      </div>
    </div>

    <div class="content">
      <div class="card modern-card"
        style="width:100%;grid-column: 1 / -1;box-shadow: 0 6px 32px 2px rgba(0,0,0,0.18);background: #181818;border-radius: 20px;border: none;padding: 30px 24px 24px 24px;min-height:unset;position:relative;margin-top:28px;max-width:100%;">
        <style>
          .modern-card {
            background: linear-gradient(135deg, #181818 60%, #232323 100%);
            border-radius: 20px;
            box-shadow: 0 6px 32px 2px rgba(0, 0, 0, 0.34);
            padding: 36px 32px 32px 32px;
            min-height: 650px;
            border: none;
          }

          .modern-section-title {
            font-weight: 800;
            color: #ff7a00;
            font-size: 1.3rem;
            background: linear-gradient(90deg, #2b2b2b 80%, #232323);
            padding: 12px 24px;
            border-radius: 12px;
            margin-bottom: 24px;
            letter-spacing: .5px;
            box-shadow: 0 1px 8px 0 rgba(255, 122, 0, 0.03);
            display: inline-block;
          }

          .admin-table.modern-table {
            background: #191919;
            border-radius: 14px;
            box-shadow: 0 1px 8px 0 rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 100%;
            border: none;
            margin: 16px 0;
          }

          .admin-table.modern-table thead tr {
            background: #232323;
            color: #ff7a00;
            font-weight: bold;
            font-size: 1.07rem;
            border-bottom: 3px solid #333;
            letter-spacing: 0.5px;
          }

          .admin-table.modern-table th,
          .admin-table.modern-table td {
            padding: 16px 12px;
            text-align: left;
            border: none;
            font-size: 1.06rem;
          }

          .admin-table.modern-table tbody tr:nth-child(even) {
            background: #202020;
          }

          .admin-table.modern-table tbody tr:nth-child(odd) {
            background: #181818;
          }

          .admin-table.modern-table tbody tr:hover {
            background: #22231e;
            transition: background 0.2s;
          }

          .category-badge {
            background: rgba(255, 255, 255, 0.08);
            color: #aaa;
            font-weight: 600;
            padding: 5px 14px;
            border-radius: 30px;
            font-size: 0.9em;
            border: 1px solid rgba(255, 255, 255, 0.1);
            display: inline-block;
          }

          .hot-badge {
            background: #ff7a00;
            color: #fff;
            padding: 4px 13px;
            border-radius: 40px;
            font-size: 0.99em;
            font-weight: 700;
            margin-left: 5px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            border: none;
            box-shadow: 0 2px 10px 0 rgba(255, 122, 0, 0.07);
            vertical-align: middle;
          }

          .form-floating-label-group label {
            color: #bbb;
            display: block;
            margin-bottom: 6px;
            font-size: 1.04em;
            font-weight: 700;
          }

          .form-floating-label-group input,
          .form-floating-label-group select,
          .form-floating-label-group textarea {
            background: #191919;
            border: 2px solid #2b2b2b;
            color: #fff;
            border-radius: 10px;
            padding: 13px 14px;
            margin-bottom: 19px;
            width: 100%;
            font-size: 1.07em;
            box-shadow: 0 1px 7px 0 rgba(0, 0, 0, 0.06);
            transition: border 0.2s;
          }

          .form-floating-label-group input:focus,
          .form-floating-label-group select:focus,
          .form-floating-label-group textarea:focus {
            border: 2px solid #ff7a00;
            outline: none;
            background: #232323;
            color: #fff;
          }

          .admin-form button.btn,
          a.btn {
            background: linear-gradient(90deg, #ff7a00 30%, #ffb380 100%);
            color: #1a0d00;
            font-weight: bold;
            font-size: 1.08em;
            padding: 12px 32px;
            border-radius: 22px;
            border: 2px solid #ff7a00;
            outline: none;
            box-shadow: 0 2px 10px 0 rgba(255, 122, 0, 0.07);
            transition: background 0.18s, color 0.18s, border 0.18s;
            margin: 0 3px 0 0;
            cursor: pointer;
            text-decoration: none;
            letter-spacing: .02em;
            display: inline-block;
            line-height: 1.1;
          }

          .admin-form button.btn:hover,
          a.btn:hover {
            background: #1a0d00;
            color: #fff;
            border-color: #ff7a00;
            box-shadow: 0 4px 16px 0 rgba(255, 122, 0, 0.14);
          }

          .admin-form button.btn[disabled],
          a.btn[disabled] {
            opacity: 0.65;
            cursor: not-allowed;
            filter: grayscale(45%);
          }

          .admin-actions a {
            font-weight: 600;
            color: #ff7a00;
            margin-right: 10px;
            transition: color 0.2s;
          }

          .admin-actions a:hover {
            color: #ff4500;
            text-decoration: underline;
          }

          .empty-state {
            text-align: center;
            color: #bbb;
            padding: 42px 0;
            font-size: 1.19em;
            opacity: 0.8;
          }

          .img-preview-modern {
            border-radius: 17px;
            box-shadow: 0 2px 15px 0 rgba(255, 122, 0, 0.14);
            object-fit: cover;
            background: #1a0d00;
            border: 1.8px solid #2b2b2b;
            margin-bottom: 12px;
            max-width: 99%;
            aspect-ratio: 3/1.3;
            display: block;
          }
        </style>

        <div
          style="margin-bottom:12px; border-bottom:1.5px solid #242323; padding-bottom:10px; display: flex; align-items: center; gap: 25px; min-height:unset;">
          <span class="modern-section-title" style="font-size:1.22em;"><i class="fas fa-newspaper"
              style="margin-right:8px; color:#ff7a00"></i>Manage News</span>
          <div style="margin-left:auto;display:flex;gap:14px;">
            <a class="btn" style="min-width:165px;font-size:1em;letter-spacing:.1em;box-shadow:none;"
              href="news_admin.php?action=new"><i class="fas fa-plus-circle" style="margin-right:7px"></i>ADD
              ARTICLE</a>
          </div>
        </div>

        <div id="tab-manage-content">
          <div style="display:flex;justify-content:space-between;align-items:center">
            <h2 style="margin:0">Manage News</h2>
          </div>

          <?php if (!empty($messages) || !empty($errors)): ?>
            <div style="margin-top:10px">
              <?php foreach ($messages as $m): ?>
                <div style="color:#b6ffb3;padding:8px;border-radius:6px;background:#0b2b10;margin-bottom:6px">
                  <?php echo htmlspecialchars($m); ?></div><?php endforeach; ?>
              <?php foreach ($errors as $e): ?>
                <div style="color:#ffd6d6;padding:8px;border-radius:6px;background:#2b0b0b;margin-bottom:6px">
                  <?php echo htmlspecialchars($e); ?></div><?php endforeach; ?>
            </div>
          <?php endif; ?>

          <?php if ($action === 'new' || $editing !== null): ?>
            <?php $it = $editing ?? ['id' => '', 'title' => '', 'date' => date('Y-m-d'), 'datePublication' => date('Y-m-d'), 'image' => '', 'idCategorie' => 0, 'category' => '', 'excerpt' => '', 'content' => '', 'hot' => 0]; ?>
            <section style="margin-top:16px">
              <h3><?php echo $editing ? 'Edit' : 'New'; ?> Article</h3>
              <form id="article-form" class="admin-form form-floating-label-group" method="post"
                action="news_admin.php<?php echo $editing ? '?id=' . urlencode($editing['id']) : ''; ?>"
                enctype="multipart/form-data"
                style="background: #181818; border-radius: 16px; box-shadow: 0 2px 36px 0 rgba(255,122,0,0.07); padding: 32px 24px 18px 24px; margin-bottom:30px; border:1.5px solid #242323">
                <input type="hidden" name="action" value="<?php echo $editing ? 'save' : 'add'; ?>">
                <?php if ($editing): ?><input type="hidden" name="id"
                    value="<?php echo htmlspecialchars($editing['id'] ?? ''); ?>"><?php endif; ?>
                <?php if (!$editing): ?><label for="fld-id">ID (alphanumeric):</label><input id="fld-id" name="id"
                    value="<?php echo htmlspecialchars($it['id'] ?? ''); ?>" class="small"><?php endif; ?>
                <label for="fld-datePublication">Publication Date</label><input id="fld-datePublication"
                  name="datePublication" type="date"
                  value="<?php echo htmlspecialchars($it['datePublication'] ?? date('Y-m-d')); ?>" class="small">
                <label for="fld-title">Title</label><input id="fld-title" name="title"
                  value="<?php echo htmlspecialchars($it['title'] ?? ''); ?>">
                <label for="fld-date">Displayed Date</label><input id="fld-date" name="date" type="date"
                  value="<?php echo htmlspecialchars($it['date'] ?? date('d-m-Y')); ?>" class="small">

                <label for="fld-image-upload">Upload Image</label>
                <input id="fld-image-upload" type="file" name="image_upload" accept="image/*"
                  style="padding:8px;margin:8px 0;background:#0b0b0b;border:1px solid #333;color:#fff;border-radius:6px;width:100%">
                <input type="hidden" id="fld-image-existing" name="image_existing"
                  value="<?php echo htmlspecialchars($it['image'] ?? ''); ?>">
                <?php if (!empty($it['image'])): ?>
                  <div style="margin:12px 0;border-radius:8px;overflow:hidden;background:#0b0b0b;border:1px solid #333">
                    <img id="img-preview" src="<?php echo htmlspecialchars($it['image']); ?>" class="img-preview-modern"
                      alt="Preview">
                  </div>
                <?php else: ?>
                  <div id="img-preview-container"
                    style="display:none;margin:12px 0;border-radius:8px;overflow:hidden;background:#0b0b0b;border:1px solid #333">
                    <img id="img-preview" class="img-preview-modern" alt="Preview">
                  </div>
                <?php endif; ?>

                <label for="fld-category">Category</label>
                <div style="display:flex;gap:10px;align-items:stretch;flex-wrap:wrap;">
                  <select id="fld-idCategorie" name="idCategorie"
                    style="flex:1 1 240px;min-width:220px;max-width:320px;width:100%;">
                    <option value="0">-- Select category --</option>
                    <?php foreach ($categories as $c): ?>
                      <option value="<?php echo intval($c['idCategorie']); ?>" <?php if (!empty($it['idCategorie']) && intval($it['idCategorie']) === intval($c['idCategorie']))
                           echo 'selected'; ?>>
                        <?php echo htmlspecialchars($c['nom']); ?></option>
                    <?php endforeach; ?>
                  </select>
                  <input id="fld-category" name="category" value="<?php echo htmlspecialchars($it['category'] ?? ''); ?>"
                    placeholder="Custom category (optional)" style="flex:2 1 420px;min-width:240px;width:100%;">
                </div>
                <small style="display:block;color:#aaa;margin-top:6px">You can select or add a custom category
                  name.</small>

                <div style="margin:16px 0">
                  <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
                    <input type="checkbox" id="fld-hot" name="hot" value="1" <?php echo ($it['hot'] ?? 0) ? 'checked' : ''; ?> style="margin:0">
                    <span>🔥 Mark as Hot News (will appear at top of news page)</span>
                  </label>
                </div>
                <label for="fld-excerpt">Excerpt</label><textarea id="fld-excerpt" name="excerpt"
                  rows="3"><?php echo htmlspecialchars($it['excerpt'] ?? ''); ?></textarea>

                <label for="fld-content">Full Content</label><textarea id="fld-content" name="content"
                  rows="8"><?php echo htmlspecialchars($it['content'] ?? ''); ?></textarea>

                <?php if ($editing): ?>
                  <div
                    style="margin-top:24px;background:#0b0b0b;padding:12px;border-radius:8px;border-left:3px solid #ff7a00">
                    <h3 style="margin-top:0;color:#ff7a00">Comments for: <?php echo htmlspecialchars($editing['id']); ?>
                    </h3>
                    <?php
                    $slug = $editing['id'];
                    $comments = Comment::findByArticleId($editing['idArticle']);

                    if (empty($comments)) {
                      echo '<p style="color:#bbb">No comments for this article.</p>';
                    } else {
                      foreach ($comments as $i => $comment) {
                        $name = htmlspecialchars($comment->getName());
                        $date = htmlspecialchars($comment->getCreatedAt()->format('Y-m-d H:i:s'));
                        $text = htmlspecialchars($comment->getText());

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
                        echo '</div>';
                        echo '</div>';
                        echo '</form>';
                      }
                      // Comment deletion functionality removed - only editing allowed
                    }
                    ?>

                    <h3>Edit History</h3>
                    <?php
                    $history = $editing['history'] ?? [];
                    if (empty($history)) {
                      echo '<p style="color:#bbb">No edit history for this article.</p>';
                    } else {
                      foreach ($history as $h) {
                        echo '<div style="background:#111;padding:10px;border-radius:6px;margin-bottom:10px">';
                        echo '<div style="color:#999;font-size:0.85rem;margin-bottom:8px">Edited by ' . htmlspecialchars($h['edited_by_name'] ?? 'Unknown') . ' on ' . htmlspecialchars($h['edited_at']) . '</div>';
                        echo '<strong>' . htmlspecialchars($h['titre']) . '</strong><br>';
                        echo '<small style="color:#bbb">Excerpt: ' . htmlspecialchars(substr($h['excerpt'] ?? '', 0, 100)) . '...</small>';
                        echo '</div>';
                      }
                    }
                    ?>
                  </div>
                <?php endif; ?>

                <p>
                  <button id="btn-save" class="btn" type="submit"><i class="fas fa-save"></i> Save</button>
                  <a class="btn" href="news_admin.php"><i class="fas fa-times-circle"></i> Cancel</a>
                  <button id="btn-restore" class="btn" type="button" style="margin-left:8px;"><i
                      class="fas fa-history"></i> Restore Draft</button>
                  <button id="btn-clear-draft" class="btn" type="button"
                    style="margin-left:6px;background:#c33;color:#fff;border-color:#c33;"><i class="fas fa-trash-alt"></i>
                    Clear Draft</button>
                </p>
              </form>
            </section>
          <?php else: ?>

            <?php
            $perPage = 8;
            $currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
            $totalArticles = count($data);
            $totalPages = max(1, (int) ceil($totalArticles / $perPage));
            if ($currentPage > $totalPages) {
              $currentPage = $totalPages;
            }
            $offset = ($currentPage - 1) * $perPage;
            $pageData = array_slice($data, $offset, $perPage);
            ?>
            <section style="margin-top:16px">
              <h3>Existing Articles (<?php echo $totalArticles; ?>)</h3>
              <table class="admin-table modern-table" id="articles-table">
                <thead>
                  <tr>
                    <th>#</th>
                    <th>Slug</th>
                    <th>Title</th>
                    <th>Date</th>
                    <th>Category</th>
                    <th>Hot</th>
                    <th>Sentiment</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($pageData as $row): ?>
                    <tr>
                      <td><?php echo htmlspecialchars($row['idArticle'] ?? ''); ?></td>
                      <td><?php echo htmlspecialchars($row['id'] ?? ''); ?></td>
                      <td><?php echo htmlspecialchars($row['title'] ?? ''); ?></td>
                      <td><?php echo htmlspecialchars($row['date'] ?? ''); ?></td>
                      <td><span
                          class="category-badge"><?php echo htmlspecialchars(findCategoryName($row['idCategorie'] ?? 0, $categories) ?? ''); ?></span>
                      </td>
                      <td>
                        <?php echo $row['hot'] ? '<span class="hot-badge"><i class="fas fa-fire"></i> Hot</span>' : '<span style="color:#888">No</span>'; ?>
                      </td>
                      <td>
                        <?php
                        $stats = $row['sentiment_stats'] ?? ['positive' => 0, 'negative' => 0];
                        $pos = $stats['positive'];
                        $neg = $stats['negative'];
                        if ($pos + $neg == 0)
                          echo '-';
                        else
                          echo "<span style='color:#28a745'>+$pos</span> / <span style='color:#dc3545'>-$neg</span>";
                        ?>
                      </td>
                      <td class="admin-actions">
                        <a href="news_admin.php?action=edit&id=<?php echo urlencode($row['id']); ?>">Edit</a> |
                        <a href="news_admin.php?action=delete&id=<?php echo urlencode($row['id']); ?>"
                          onclick="return confirm('Delete this article?');">Delete</a>
                        <?php if ($row['hot']): ?>
                          | <a href="news_admin.php"
                            onclick="toggleHot('<?php echo urlencode($row['id']); ?>'); return false;"
                            style="color:#ff7a00">🔥 Hot</a>
                        <?php else: ?>
                          | <a href="news_admin.php"
                            onclick="toggleHot('<?php echo urlencode($row['id']); ?>'); return false;" style="color:#888">🔥
                            Make Hot</a>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
              <?php if ($totalPages > 1): ?>
                <div style="margin-top:12px; display:flex; justify-content:flex-end; gap:6px; flex-wrap:wrap;">
                  <?php
                  $buildLink = function ($p) {
                    $base = 'news_admin.php';
                    $params = $_GET;
                    $params['page'] = $p;
                    return $base . '?' . http_build_query($params);
                  };
                  ?>
                  <?php if ($currentPage > 1): ?>
                    <a class="btn" style="padding:9px 14px; min-width:70px;"
                      href="<?php echo htmlspecialchars($buildLink($currentPage - 1)); ?>">&laquo; Prev</a>
                  <?php endif; ?>
                  <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <a class="btn"
                      style="padding:9px 12px; min-width:42px; <?php echo $p === $currentPage ? 'background:#ff7a00;color:#1a0d00;border-color:#ff7a00;' : '' ?>"
                      href="<?php echo htmlspecialchars($buildLink($p)); ?>"><?php echo $p; ?></a>
                  <?php endfor; ?>
                  <?php if ($currentPage < $totalPages): ?>
                    <a class="btn" style="padding:9px 14px; min-width:70px;"
                      href="<?php echo htmlspecialchars($buildLink($currentPage + 1)); ?>">Next &raquo;</a>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
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
      </div>
    </div>

    <footer class="site-footer">
      © 2025 <span>Nine Tailed Fox</span>. All Rights Reserved.
    </footer>
  </div>

  <div class="transition-screen"></div>
  <script>
    // Ensure the transition overlay is hidden after page load
    window.addEventListener('load', function () {
      try {
        var t = document.querySelector('.transition-screen');
        if (t) t.classList.add('hidden');
      } catch (e) { }
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

  <!-- TinyMCE removed - using plain textarea for content -->
  <script>
    // Tab switching - ensure it works properly
    document.addEventListener('DOMContentLoaded', function () {
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
        tabManageBtn.addEventListener('click', function (e) {
          e.preventDefault();
          console.log('Manage tab clicked');
          tabManageBtn.classList.add('active');
          tabHistoryBtn.classList.remove('active');
          tabCommentsBtn.classList.remove('active');
          tabManageContent.style.display = 'block';
          tabHistoryContent.style.display = 'none';
          tabCommentsContent.style.display = 'none';
        });

        tabHistoryBtn.addEventListener('click', function (e) {
          e.preventDefault();
          console.log('History tab clicked');
          tabHistoryBtn.classList.add('active');
          tabManageBtn.classList.remove('active');
          tabCommentsBtn.classList.remove('active');
          tabHistoryContent.style.display = 'block';
          tabManageContent.style.display = 'none';
          tabCommentsContent.style.display = 'none';
        });

        tabCommentsBtn.addEventListener('click', function (e) {
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
      fileInput.addEventListener('change', function (e) {
        var file = e.target.files[0];
        if (file) {
          var reader = new FileReader();
          reader.onload = function (ev) {
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
    (function () {
      function toast(message, type) {
        var container = document.getElementById('toast-container');
        if (!container) return;
        var el = document.createElement('div'); el.className = 'toast ' + (type || ''); el.textContent = message;
        container.appendChild(el);
        setTimeout(function () { el.style.opacity = '1'; }, 10);
        setTimeout(function () { el.style.opacity = '0'; setTimeout(function () { try { container.removeChild(el); } catch (e) { } }, 400); }, 4500);
      }

      var serverMessages = <?php echo json_encode($messages); ?> || [];
      var serverErrors = <?php echo json_encode($errors); ?> || [];
      var currentId = <?php echo json_encode($editing['id'] ?? 'new'); ?>;

      document.addEventListener('DOMContentLoaded', function () {
        console.log('DOMContentLoaded fired, currentId:', currentId);
        console.log('Server messages:', serverMessages);
        console.log('Server errors:', serverErrors);
        serverMessages.forEach(function (m) { if (m) toast(m, ''); });
        serverErrors.forEach(function (e) { if (e) toast(e, 'error'); });

        // Attach button event listeners after DOM is ready
        var btnRestore = document.getElementById('btn-restore');
        var btnClear = document.getElementById('btn-clear-draft');
        console.log('Buttons found after DOM ready - Restore:', !!btnRestore, 'Clear:', !!btnClear);

        if (btnRestore) {
          console.log('Attaching restore button listener');
          btnRestore.addEventListener('click', function () {
            console.log('Restore button clicked');
            try {
              var key = 'news_draft_' + currentId;
              console.log('Looking for draft with key:', key);
              var raw = localStorage.getItem(key);
              console.log('Raw draft data:', raw);
              if (!raw) {
                console.log('No draft found');
                toast('No draft found', 'error');
                return;
              }
              var d = JSON.parse(raw);
              console.log('Parsed draft data:', d);
              if (document.getElementById('fld-title')) document.getElementById('fld-title').value = d.title || '';
              if (document.getElementById('fld-date')) document.getElementById('fld-date').value = d.date || '';
              if (document.getElementById('fld-datePublication')) document.getElementById('fld-datePublication').value = d.datePublication || '';
              if (document.getElementById('fld-image-existing')) document.getElementById('fld-image-existing').value = d.image || '';
              if (document.getElementById('fld-idCategorie')) document.getElementById('fld-idCategorie').value = d.idCategorie || '';
              if (document.getElementById('fld-category')) document.getElementById('fld-category').value = d.category || '';
              if (document.getElementById('fld-hot')) document.getElementById('fld-hot').checked = (d.hot === '1');
              if (document.getElementById('fld-excerpt')) document.getElementById('fld-excerpt').value = d.excerpt || '';
              if (document.getElementById('fld-content')) document.getElementById('fld-content').value = d.content || '';
              toast('Draft restored');
            } catch (e) {
              console.error('Restore failed:', e);
              toast('Failed to restore draft', 'error');
            }
          });
        } else {
          console.error('Restore button not found!');
        }

        if (btnClear) {
          console.log('Attaching clear button listener');
          btnClear.addEventListener('click', function () {
            console.log('Clear button clicked');
            try {
              var key = 'news_draft_' + currentId;
              console.log('Clearing draft with key:', key);
              localStorage.removeItem(key);
              toast('Draft cleared');
            } catch (e) {
              console.error('Clear failed:', e);
              toast('Failed to clear draft', 'error');
            }
          });
        } else {
          console.error('Clear button not found!');
        }
      });

      // Autosave
      var autosaveInterval = 5000; // ms
      var autosaveTimer = null;
      function scheduleAutosave() { if (autosaveTimer) clearTimeout(autosaveTimer); autosaveTimer = setTimeout(doAutosave, autosaveInterval); }
      function doAutosave() {
        try {
          var key = 'news_draft_' + currentId;
          var payload = {
            title: (document.getElementById('fld-title') || {}).value || '',
            date: (document.getElementById('fld-date') || {}).value || '',
            datePublication: (document.getElementById('fld-datePublication') || {}).value || '',
            image: (document.getElementById('fld-image-existing') || {}).value || '',
            idCategorie: (document.getElementById('fld-idCategorie') || {}).value || '',
            category: (document.getElementById('fld-category') || {}).value || '',
            hot: (document.getElementById('fld-hot') || {}).checked ? '1' : '0',
            excerpt: (document.getElementById('fld-excerpt') || {}).value || '',

            content: (document.getElementById('fld-content') || {}).value || '',
            timestamp: Date.now()
          };
          localStorage.setItem(key, JSON.stringify(payload));
          console.log('Draft saved:', key, payload);
        } catch (e) { console.error('Autosave failed:', e); }

        // Schedule autosave on input/change
        ['fld-title', 'fld-date', 'fld-datePublication', 'fld-excerpt', 'fld-content', 'fld-category'].forEach(function (id) {
          var el = document.getElementById(id);
          if (el) {
            el.addEventListener('input', function () { console.log('Input event on', id); scheduleAutosave(); });
          } else {
            console.warn('Element not found:', id);
          }
        });
        // For select and checkbox
        ['fld-idCategorie'].forEach(function (id) {
          var el = document.getElementById(id);
          if (el) {
            el.addEventListener('change', function () { console.log('Change event on', id); scheduleAutosave(); });
          } else {
            console.warn('Element not found:', id);
          }
        });
        ['fld-hot'].forEach(function (id) {
          var el = document.getElementById(id);
          if (el) {
            el.addEventListener('change', function () { console.log('Change event on', id); scheduleAutosave(); });
          } else {
            console.warn('Element not found:', id);
          }
        });

        // Restore / Clear buttons are attached in DOMContentLoaded below

        // Clear draft if server reports success
        document.addEventListener('DOMContentLoaded', function () {
          console.log('Clearing draft on success check - serverMessages:', serverMessages);
          if (serverMessages && serverMessages.length) {
            var keys = ['added successfully', 'updated successfully', 'deleted'];
            var clear = serverMessages.some(function (m) { m = (m || '').toLowerCase(); return keys.some(function (k) { return m.indexOf(k) !== -1; }); });
            console.log('Should clear draft:', clear);
            if (clear) { try { localStorage.removeItem('news_draft_' + currentId); console.log('Draft cleared after success'); } catch (e) { } }
          }
        });

        // Validation on submit
        var form = document.getElementById('article-form');
        console.log('Form found:', !!form);
        if (form) {
          console.log('Attaching form submit listener');
          form.addEventListener('submit', function (e) {
            console.log('Form submit triggered');
            var isEdit = <?php echo $editing ? 'true' : 'false'; ?>;
            console.log('isEdit:', isEdit);
            var idField = document.getElementById('fld-id');
            var title = document.getElementById('fld-title');
            console.log('idField:', !!idField, 'title:', !!title);
            var errors = [];
            if (!isEdit) {
              if (!idField || !idField.value.trim()) {
                errors.push('ID is required for new articles.');
              } else if (!/^[a-z0-9_-]+$/i.test(idField.value.trim())) {
                errors.push('ID may contain only letters, numbers, underscore and hyphen.');
              }
            }
            if (!title || !title.value.trim()) {
              errors.push('Title is required.');
            }
            console.log('Validation errors:', errors);
            if (errors.length) {
              e.preventDefault();
              errors.forEach(function (m) { toast(m, 'error'); });
              return false;
            }

            console.log('Form validation passed, allowing submit');
          });
        } else {
          console.error('Form not found!');
        }

        // Toggle hot status
        window.toggleHot = function (articleId) {
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

      };

      // Dropdown Menu Toggle
      document.addEventListener('DOMContentLoaded', function () {
        const adminDropdown = document.getElementById('adminDropdown');

        if (adminDropdown) {
          const adminUser = adminDropdown.querySelector('.admin-user');

          // Toggle dropdown on click
          if (adminUser) {
            adminUser.addEventListener('click', function (e) {
              e.stopPropagation();
              adminDropdown.classList.toggle('active');
            });
          }

          // Close dropdown when clicking outside
          document.addEventListener('click', function (e) {
            if (!adminDropdown.contains(e.target)) {
              adminDropdown.classList.remove('active');
            }
          });

          // Close dropdown when pressing Escape
          document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
              adminDropdown.classList.remove('active');
            }
          });
        }
      });

    })();
  </script>
</body>

</html>