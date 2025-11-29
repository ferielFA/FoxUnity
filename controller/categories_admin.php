<?php
// Controller for categories management (admin)
require_once __DIR__ . '/../model/category_model.php';
require_once __DIR__ . '/../model/article_model.php';

$messages = [];
$errors = [];

// Handle POST actions: add, edit, delete, toggle, reorder, bulk
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $act = $_POST['action'] ?? '';
  if ($act === 'add') {
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    if ($name === '') $errors[] = 'Category name is required.';
    if (empty($errors)) {
      list($ok, $result) = addCategory(['nom'=>$name, 'slug'=>$slug, 'description'=>$_POST['description'] ?? '', 'active'=>isset($_POST['active'])?1:0]);
      if ($ok) $messages[] = 'Category added.'; else $errors[] = $result;
    }
  }
  if ($act === 'edit' && !empty($_POST['id'])) {
    $id = (int)$_POST['id'];
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    if ($name === '') $errors[] = 'Category name is required.';
    if (empty($errors)) {
      list($ok, $msg) = updateCategory($id, ['nom'=>$name,'slug'=>$slug,'description'=>$_POST['description'] ?? '','position'=>$_POST['position'] ?? 0,'active'=>isset($_POST['active'])?1:0]);
      if ($ok) $messages[] = 'Category updated.'; else $errors[] = $msg;
    }
  }
  if ($act === 'delete' && !empty($_POST['id'])) {
    $id = (int)$_POST['id'];
    list($ok, $msg) = deleteCategory($id);
    if ($ok) $messages[] = 'Category deleted.'; else $errors[] = $msg;
  }
  if ($act === 'toggle' && !empty($_POST['id'])) {
    $id = (int)$_POST['id'];
    // Use the posted value (0 or 1) instead of isset() which is always true
    $new = isset($_POST['active']) ? (int)$_POST['active'] : 0;
    if (setCategoryStatus($id, $new)) {
      $messages[] = 'Category status updated.';
    } else {
      // If DB doesn't have the `active` column, surface a clearer error
      if (function_exists('_cat_has_column') && !_cat_has_column('active')) {
        $errors[] = 'Cannot update status: `active` column is missing in the database.';
      } else {
        $errors[] = 'Failed to update status.';
      }
    }
  }
  if ($act === 'reorder' && !empty($_POST['id'])) {
    $id = (int)$_POST['id'];
    $pos = intval($_POST['position'] ?? 0);
    if (reorderCategory($id, $pos)) $messages[] = 'Category order updated.'; else $errors[] = 'Failed to reorder.';
  }
}

$categories = getAllCategories();
$counts = countArticlesByCategory();
