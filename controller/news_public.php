<?php
// Controller for public news listing
require_once __DIR__ . '/../model/article_model.php';
require_once __DIR__ . '/../model/helpers.php';

$categories = getCategories();
$articles = getAllArticles();
$hotNews = array_filter($articles, function($a) { return ($a['hot'] ?? 0) == 1; });
$articles = array_filter($articles, function($a) { return ($a['hot'] ?? 0) == 0 || $a['hot'] === null; });
