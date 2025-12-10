<?php
/**
 * Helper functions for the model layer.
 */

if (!function_exists('generateArticleSummary')) {
    function generateArticleSummary($content, $title) {
        // Simple summary generation: take first 150 chars of content
        $cleanContent = strip_tags($content);
        if (strlen($cleanContent) > 150) {
            return substr($cleanContent, 0, 150) . '...';
        }
        return $cleanContent;
    }
}

if (!function_exists('findCategoryName')) {
    function findCategoryName($id, $categories) {
        foreach ($categories as $c) {
            if (($c['idCategorie'] ?? 0) == $id) {
                return $c['nom'];
            }
        }
        return null;
    }
}

if (!function_exists('getImagePath')) {
    function getImagePath($imagePath) {
        if (empty($imagePath)) return '../images/nopic.png';

        // Paths in DB usually look like "uploads/images/something.jpg"
        
        // 1. Check if it's an absolute URL
        if (strpos($imagePath, 'http') === 0) return $imagePath;
        
        // 2. Check if it's already a relative path structure we trust (starts with dots)
        if (strpos($imagePath, '../') === 0) return $imagePath;

        // 3. Logic for "uploads/..." paths
        // We assume this function is primarily called from "view/front/" context
        // so we need to return paths relative to "view/front/"
        
        // Define physical paths to check
        // __DIR__ is .../projet_web/model
        $root = dirname(__DIR__); // .../projet_web
        $frontUploads = $root . '/view/front/' . $imagePath;
        $backUploads  = $root . '/view/back/' . $imagePath;
        
        // Check Back (Admin) Uploads first (most likely for news)
        if (file_exists($backUploads)) {
            return '../back/' . $imagePath;
        }

        // Check Front Uploads
        if (file_exists($frontUploads)) {
            return $imagePath; // relative to view/front/ is just the path
        }
        
        // Check "view/images" (legacy path?)
        // e.g. path is "images/foo.jpg" -> physical "view/images/foo.jpg"
        // relative from front: "../images/foo.jpg"
        if (strpos($imagePath, 'images/') === 0) {
            $viewImages = $root . '/view/' . $imagePath;
            if (file_exists($viewImages)) {
                return '../' . $imagePath; 
            }
        }

        // Check if file exists relative to where it was likely uploaded if blindly stored
        // Fallback: if we can't find it, return it anyway? 
        // Or return nopic? 
        // Let's return nopic if we really can't find it, to be safe.
        // But if it's just a file in the same folder...
        
        if (file_exists($root . '/view/front/' . $imagePath)) return $imagePath;

        return '../images/nopic.png';
    }
}


?>
