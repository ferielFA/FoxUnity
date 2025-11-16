<?php
// Simple admin CRUD for news stored in data/news.json
// Usage: open in browser via the dashboard link

// Basic helpers
function load_data() {
    $file = __DIR__ . '/data/news.json';
    if (!file_exists($file)) return [];
    $json = file_get_contents($file);
    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}

function save_data($data) {
    $file = __DIR__ . '/data/news.json';
    $tmp = $file . '.tmp';
    $bak = $file . '.' . time() . '.bak';
    // create backup
    if (file_exists($file)) { copy($file, $bak); }
    file_put_contents($tmp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    rename($tmp, $file);
}

$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? '';
$data = load_data();

// POST actions: add/save/delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'] ?? '';
    if ($act === 'add') {
        $item = [
            'id' => preg_replace('/[^a-z0-9_-]/i', '', $_POST['id'] ?? uniqid('a')),
            'title' => $_POST['title'] ?? '',
            'date' => $_POST['date'] ?? date('F j, Y'),
            'image' => $_POST['image'] ?? '',
            'category' => $_POST['category'] ?? '',
            'excerpt' => $_POST['excerpt'] ?? '',
            'content' => $_POST['content'] ?? '',
            'hot' => isset($_POST['hot']) ? true : false,
            'comments' => []
        ];
        array_unshift($data, $item);
        save_data($data);
        header('Location: news_admin.php'); exit;
    }
    if ($act === 'save' && $id !== '') {
        foreach ($data as &$it) {
            if (($it['id'] ?? '') === $id) {
                $it['title'] = $_POST['title'] ?? $it['title'];
                $it['date'] = $_POST['date'] ?? $it['date'];
                $it['image'] = $_POST['image'] ?? $it['image'];
                $it['category'] = $_POST['category'] ?? $it['category'];
                $it['excerpt'] = $_POST['excerpt'] ?? $it['excerpt'];
                $it['content'] = $_POST['content'] ?? $it['content'];
                $it['hot'] = isset($_POST['hot']) ? true : false;
            }
        }
        unset($it);
        save_data($data);
        header('Location: news_admin.php'); exit;
    }
    if ($act === 'delete' && $id !== '') {
        $new = [];
        foreach ($data as $it) { if (($it['id'] ?? '') !== $id) $new[] = $it; }
        save_data($new);
        header('Location: news_admin.php'); exit;
    }
}

// For edit form
$editing = null;
if ($action === 'edit' && $id !== '') {
    foreach ($data as $it) { if (($it['id'] ?? '') === $id) { $editing = $it; break; } }
}

?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>News Admin</title>
  <style>
    body{font-family:Arial;background:#0f0f10;color:#eee;padding:20px}
    .wrap{max-width:1000px;margin:0 auto}
    table{width:100%;border-collapse:collapse;margin-bottom:18px}
    th,td{padding:8px;text-align:left;border-bottom:1px solid #222}
    a.btn{display:inline-block;padding:6px 10px;background:#1976d2;color:#fff;border-radius:4px;text-decoration:none}
    form input,form textarea{width:100%;padding:8px;margin:6px 0;background:#0b0b0b;border:1px solid #333;color:#fff}
    .small{width:160px}
  </style>
</head>
<body>
  <div class="wrap">
    <h1>News Admin</h1>
    <p><a class="btn" href="dashboard.html">← Dashboard</a> <a class="btn" href="news_admin.php?action=new">+ Add Article</a> <a class="btn" href="news.php">View Site News</a></p>

    <?php if ($action === 'new' || $editing !== null): ?>
      <?php $it = $editing ?? ['id'=>'','title'=>'','date'=>date('F j, Y'),'image'=>'','category'=>'','excerpt'=>'','content'=>'','hot'=>false]; ?>
      <h2><?php echo $editing ? 'Edit' : 'New'; ?> Article</h2>
      <form method="post" action="news_admin.php<?php echo $editing ? '?id='.urlencode($editing['id']) : '' ;?>">
        <input type="hidden" name="action" value="<?php echo $editing ? 'save' : 'add'; ?>">
        <?php if (!$editing): ?><label>ID (alphanumeric):</label><input name="id" value="<?php echo $it['id']; ?>" class="small"><?php endif; ?>
        <label>Title</label><input name="title" value="<?php echo htmlspecialchars($it['title']); ?>">
        <label>Date</label><input name="date" value="<?php echo htmlspecialchars($it['date']); ?>" class="small">
        <label>Image (relative path)</label><input name="image" value="<?php echo htmlspecialchars($it['image']); ?>">
        <label>Category</label><input name="category" value="<?php echo htmlspecialchars($it['category']); ?>">
        <label>Excerpt</label><textarea name="excerpt" rows="3"><?php echo htmlspecialchars($it['excerpt']); ?></textarea>
        <label>Content</label><textarea name="content" rows="8"><?php echo htmlspecialchars($it['content']); ?></textarea>
        <label><input type="checkbox" name="hot" <?php echo !empty($it['hot']) ? 'checked' : ''; ?>> Hot</label>
        <p><button type="submit">Save</button> <a href="news_admin.php">Cancel</a></p>
      </form>
    <?php else: ?>

      <h2>Existing Articles (<?php echo count($data); ?>)</h2>
      <table>
        <thead><tr><th>ID</th><th>Title</th><th>Date</th><th>Category</th><th>Hot</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($data as $row): ?>
          <tr>
            <td><?php echo htmlspecialchars($row['id'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($row['title'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($row['date'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($row['category'] ?? ''); ?></td>
            <td><?php echo !empty($row['hot']) ? 'Yes' : 'No'; ?></td>
            <td>
              <a href="news_article.php?id=<?php echo urlencode($row['id']); ?>" target="_blank">View</a> |
              <a href="news_admin.php?action=edit&id=<?php echo urlencode($row['id']); ?>">Edit</a> |
              <a href="news_admin.php?action=delete&id=<?php echo urlencode($row['id']); ?>" onclick="return confirm('Delete this article?');">Delete</a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>

      <?php if ($action === 'delete' && $id !== ''): ?>
        <h3>Confirm Delete</h3>
        <p>Are you sure you want to delete <strong><?php echo htmlspecialchars($id); ?></strong>?</p>
        <form method="post" action="news_admin.php?id=<?php echo urlencode($id); ?>">
          <input type="hidden" name="action" value="delete">
          <button type="submit">Yes, delete</button>
          <a href="news_admin.php">Cancel</a>
        </form>
      <?php endif; ?>

    <?php endif; ?>

    <hr>
    <p style="color:#888;font-size:0.9em">Note: This admin saves to <code>view/back/data/news.json</code>. Backups created automatically before each save.</p>
  </div>
</body>
</html>
