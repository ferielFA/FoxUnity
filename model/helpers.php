<?php
// Helpers shared by views/controllers

function getImagePath($imagePath) {
    if (empty($imagePath)) return '../images/nopic.png';
    if (strpos($imagePath, 'uploads/') === 0) {
        $fullPath = __DIR__ . '/../view/back/' . $imagePath;
        if (file_exists($fullPath)) return $imagePath;
    }
    if (strpos($imagePath, '../') === 0) {
        $fullPath = __DIR__ . '/../view/back/' . $imagePath;
        if (file_exists($fullPath)) return $imagePath;
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
