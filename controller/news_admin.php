<?php
// Controller for admin news actions
require_once __DIR__ . '/../model/article_model.php';
require_once __DIR__ . '/../model/helpers.php';

$messages = [];
$errors = [];
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? '';

$categories = getCategories();
$data = getAllArticles();

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'] ?? '';
    if ($act === 'add') {
        $postedId = preg_replace('/[^a-z0-9_-]/i', '', $_POST['id'] ?? '');
        if (empty($postedId)) $errors[] = 'ID is required for new articles.';
        if (empty($_POST['title'])) $errors[] = 'Title is required.';
        if (empty($_POST['content'])) $errors[] = 'Content is required.';
        if (empty($errors)) {
            list($ok, $item) = addArticle(array_merge($_POST, ['id'=>$postedId]), $_FILES);
            if ($ok) {
                $messages[] = 'Article added successfully. View it on the news page.';
                header('Refresh: 1.5; url=news.php');
            } else {
                $errors[] = 'Failed to save article — database error.';
            }
        }
    }
    if ($act === 'save' && $id !== '') {
        if (empty($_POST['title'])) $errors[] = 'Title is required.';
        if (empty($_POST['content'])) $errors[] = 'Content is required.';
        if (empty($errors)) {
            list($ok, $after) = updateArticle($id, $_POST, $_FILES);
            if ($ok) $messages[] = 'Article updated successfully.'; else $errors[] = 'Failed to save changes — database error.';
        }
    }
    if ($act === 'delete' && $id !== '') {
        if (deleteArticle($id)) $messages[] = 'Article deleted.'; else $errors[] = 'Failed to delete article — database error.';
    }
    if ($act === 'toggle_hot' && $id !== '') {
        $res = toggleHot($id);
        if ($res !== null) $messages[] = 'Article ' . ($res ? 'marked as hot' : 'removed from hot news') . '.'; else $errors[] = 'Failed to update hot status — database error.';
    }
}

$editing = null;
if ($action === 'edit' && $id !== '') {
    foreach ($data as $it) { if (($it['id'] ?? '') === $id) { $editing = $it; break; } }
    if (!$editing) { $editing = getArticleBySlug($id); }
}

// Provide history directory path for view
$historyDir = __DIR__ . '/../view/back/uploads/history';
@mkdir($historyDir, 0755, true);
