<?php
// Controller for single article pages
require_once __DIR__ . '/../model/article_model.php';
require_once __DIR__ . '/../model/helpers.php';

$slug = $_GET['id'] ?? '';
if (empty($slug)) {
    header('HTTP/1.0 404 Not Found');
    echo '<h1>Article not found</h1>';
    exit;
}

$a = getArticleBySlug($slug);
if (!$a) {
    header('HTTP/1.0 404 Not Found');
    echo '<h1>Article not found</h1>';
    exit;
}

$categories = getCategories();
