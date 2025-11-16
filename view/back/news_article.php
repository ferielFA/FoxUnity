<?php
// news_article.php
// Loads articles from data/news.json and shows detail + comments (session-backed)
session_start();

$dataFile = __DIR__ . '/data/news.json';
$json = file_exists($dataFile) ? file_get_contents($dataFile) : '[]';
$data = json_decode($json, true) ?: [];

$articles = [];
foreach ($data as $item) {
    if (isset($item['id'])) {
        $articles[$item['id']] = $item;
    }
}

$id = isset($_GET['id']) ? preg_replace('/[^a-z0-9_-]/i', '', $_GET['id']) : '';

if (!$id || !isset($articles[$id])) {
    http_response_code(404);
    echo "<!doctype html><html><head><meta charset=\"utf-8\"><title>Article Not Found</title></head><body><h1>Article not found</h1><p><a href=\"news.php\">Back to News</a></p></body></html>";
    exit;
}

$article = $articles[$id];

// Handle comment POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $text = trim($_POST['comment'] ?? '');
    if ($name !== '' && $text !== '') {
        $entry = ['name' => htmlspecialchars($name), 'date' => date('M j, Y H:i'), 'text' => htmlspecialchars($text)];
        if (!isset($_SESSION['article_comments'])) { $_SESSION['article_comments'] = []; }
        if (!isset($_SESSION['article_comments'][$id])) { $_SESSION['article_comments'][$id] = []; }
        array_unshift($_SESSION['article_comments'][$id], $entry);
    }
    header('Location: news_article.php?id=' . urlencode($id) . '#comments');
    exit;
}

// Prepare comments: initial (from JSON) + session
$initial_comments = $article['comments'] ?? [];
$session_comments = $_SESSION['article_comments'][$id] ?? [];
$comments = array_merge($initial_comments, $session_comments);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?php echo htmlspecialchars($article['title']); ?></title>
  <style>
    body{font-family:Arial,Helvetica,sans-serif;background:#0f0f10;color:#eee;margin:0;padding:20px}
    .container{max-width:820px;margin:0 auto}
    .article-image{width:100%;max-height:360px;object-fit:cover;border-radius:6px}
    .meta{color:#bbb;font-size:0.9em;margin:6px 0}
    .content{margin-top:14px;line-height:1.6;color:#ddd}
    .comments-section{margin-top:28px}
    .comment{background:#111;padding:10px;border-radius:6px;margin-bottom:10px}
    .comment-meta{font-weight:bold;color:#fff}
    .comment-date{font-weight:normal;color:#999;font-size:0.85em;margin-left:8px}
    .comment-text{margin-top:6px;color:#ddd}
    .comment-form input,.comment-form textarea{width:100%;padding:8px;margin:6px 0;border-radius:4px;border:1px solid #333;background:#0b0b0b;color:#fff}
    .comment-form button{background:#1b6; color:#000;border:0;padding:8px 12px;border-radius:4px;cursor:pointer}
    .back-link{display:inline-block;margin-top:18px;color:#9cf;text-decoration:none}
  </style>
</head>
<body>
  <div class="container">
    <h1><?php echo htmlspecialchars($article['title']); ?></h1>
    <div class="meta"><?php echo htmlspecialchars($article['date'] ?? ''); ?> • <?php echo htmlspecialchars($article['category'] ?? ''); ?></div>
    <?php if (!empty($article['image'])): ?>
      <img class="article-image" src="<?php echo htmlspecialchars($article['image']); ?>" alt="<?php echo htmlspecialchars($article['title']); ?>">
    <?php endif; ?>

    <div class="content"><?php echo nl2br(htmlspecialchars($article['content'] ?? '')); ?></div>

    <div id="comments" class="comments-section">
      <h3>Comments (<?php echo count($comments); ?>)</h3>
      <?php if (count($comments) === 0): ?>
        <p style="color:#bbb;margin:8px 0">Be the first to comment on this article.</p>
      <?php endif; ?>
      <?php foreach ($comments as $c): ?>
        <div class="comment">
          <div class="comment-meta"><?php echo htmlspecialchars($c['name']); ?> <span class="comment-date"><?php echo htmlspecialchars($c['date']); ?></span></div>
          <div class="comment-text"><?php echo nl2br(htmlspecialchars($c['text'])); ?></div>
        </div>
      <?php endforeach; ?>

      <form class="comment-form" method="post" action="news_article.php?id=<?php echo urlencode($id); ?>#comments">
        <input type="text" name="name" placeholder="Your name" required>
        <textarea name="comment" rows="4" placeholder="Your comment" required></textarea>
        <button type="submit">Post Comment</button>
      </form>
    </div>

    <a class="back-link" href="news.php">← Back to News</a>
  </div>
</body>
</html>
