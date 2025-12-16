<?php
require_once __DIR__ . '/model/Comment.php';

$pdo = new PDO('mysql:host=127.0.0.1;dbname=foxunity0;charset=utf8mb4', 'root', '');

// Find article with most comments
$stmt = $pdo->query("SELECT article_id, COUNT(*) as c FROM comments GROUP BY article_id ORDER BY c DESC LIMIT 1");
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    echo "No comments in database at all.\n";
    exit;
}

$articleId = $row['article_id'];
echo "Testing comments for Article ID: $articleId (Count: {$row['c']})\n";

$comments = Comment::findByArticleId($articleId);

foreach ($comments as $c) {
    echo "Comment ID: " . $c->getIdComment() . "\n";
    echo "Author: " . $c->getName() . "\n";
    echo "Email: " . $c->getEmail() . "\n";
    echo "Avatar (DB): " . ($c->getUserImage() ?? 'NULL') . "\n";
    echo "Parent ID: " . ($c->getParentId() ?? 'NULL') . "\n";
    echo "---------------------------\n";
}
