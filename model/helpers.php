<?php
// Helpers shared by views/controllers

function getImagePath($imagePath) {
    if (empty($imagePath)) return '../images/nopic.png';
    if (strpos($imagePath, 'uploads/') === 0) {
        $fullPath = __DIR__ . '/../view/back/' . $imagePath;
        if (file_exists($fullPath)) return '../back/' . $imagePath;
    }
    if (strpos($imagePath, '../') === 0) {
        $fullPath = __DIR__ . '/../view/back/' . $imagePath;
        if (file_exists($fullPath)) return '../back/' . $imagePath;
    }
    if (strpos($imagePath, 'images/') === 0 || strpos($imagePath, '../images/') === 0) {
        return $imagePath;
    }
    return '../images/nopic.png';
}

function findCategoryName($id, $categories){
  foreach($categories as $c) if (($c['idCategorie'] ?? 0) == $id) return $c['nom'];
  return null;
}

// Comment management functions (to be moved to proper model/repository later)
function deleteEmbeddedCommentBySlug($slug, $index) {
    $commentsDir = __DIR__ . '/../view/back/uploads/comments';
    $commentFile = $commentsDir . '/' . $slug . '.json';
    
    if (!file_exists($commentFile)) {
        return false;
    }
    
    $comments = json_decode(file_get_contents($commentFile), true);
    if (!is_array($comments) || !isset($comments[$index])) {
        return false;
    }
    
    unset($comments[$index]);
    $comments = array_values($comments); // Re-index array
    
    return file_put_contents($commentFile, json_encode($comments)) !== false;
}

function getEmbeddedCommentsBySlug($slug) {
    $commentsDir = __DIR__ . '/../view/back/uploads/comments';
    $commentFile = $commentsDir . '/' . $slug . '.json';
    
    if (!file_exists($commentFile)) {
        return [];
    }
    
    $comments = json_decode(file_get_contents($commentFile), true);
    return is_array($comments) ? $comments : [];
}

function clearEmbeddedCommentsBySlug($slug) {
    $commentsDir = __DIR__ . '/../view/back/uploads/comments';
    $commentFile = $commentsDir . '/' . $slug . '.json';
    
    if (file_exists($commentFile)) {
        return unlink($commentFile);
    }
    
    return true; // Nothing to clear is considered success
}

function addEmbeddedCommentBySlug($slug, $name, $email, $text) {
    $commentsDir = __DIR__ . '/../view/back/uploads/comments';
    @mkdir($commentsDir, 0755, true);
    $commentFile = $commentsDir . '/' . $slug . '.json';

    $comments = [];
    if (file_exists($commentFile)) {
        $comments = json_decode(file_get_contents($commentFile), true);
        if (!is_array($comments)) {
            $comments = [];
        }
    }

    $newComment = [
        'name' => $name,
        'email' => $email,
        'text' => $text,
        'date' => date('Y-m-d H:i:s')
    ];

    $comments[] = $newComment;

    return file_put_contents($commentFile, json_encode($comments)) !== false;
}

function updateEmbeddedCommentBySlug($slug, $index, $name, $text) {
    $commentsDir = __DIR__ . '/../view/back/uploads/comments';
    $commentFile = $commentsDir . '/' . $slug . '.json';

    if (!file_exists($commentFile)) {
        return false;
    }

    $comments = json_decode(file_get_contents($commentFile), true);
    if (!is_array($comments) || !isset($comments[$index])) {
        return false;
    }

    $comments[$index]['name'] = $name;
    $comments[$index]['text'] = $text;
    $comments[$index]['edited'] = date('Y-m-d H:i:s');

    return file_put_contents($commentFile, json_encode($comments)) !== false;
}

function generateArticleSummary($content, $title) {
    // Simple summary generation: take first 200 characters of content
    $summary = substr(strip_tags($content), 0, 200);
    if (strlen($content) > 200) {
        $summary .= '...';
    }
    return $summary;
}
