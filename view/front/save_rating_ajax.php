<?php
/**
 * AJAX endpoint for real-time rating submission
 * Saves ratings immediately when user clicks stars
 */

require_once __DIR__ . '/../../controller/CommentController.php';
require_once __DIR__ . '/../../model/Comment.php';

header('Content-Type: application/json');

// Check if it's an AJAX request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    // Fallback to regular POST
    $data = $_POST;
}

// Validate required fields
if (!isset($data['event_id']) || !isset($data['rating'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$eventId = (int)$data['event_id'];
$rating = (int)$data['rating'];
$userId = $data['user_id'] ?? null;
$userName = $data['user_name'] ?? 'Anonymous';
$userEmail = $data['user_email'] ?? '';
$content = $data['content'] ?? 'Quick rating';

// Validate rating
if ($rating < 1 || $rating > 5) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid rating value']);
    exit;
}

try {
    $commentController = new CommentController();
    
    // Check if user already rated this event
    $existingComment = $commentController->getUserCommentForEvent($eventId, $userEmail);
    
    if ($existingComment) {
        // Update existing rating
        $updated = $commentController->updateCommentRating($existingComment['id_comment'], $rating);
        
        if ($updated) {
            // Update event_statistics table
            updateEventStatistics($eventId);
            
            // Get updated stats
            $stats = $commentController->getEventRatingStats($eventId);
            
            echo json_encode([
                'success' => true,
                'message' => 'Rating updated successfully',
                'action' => 'updated',
                'rating' => $rating,
                'stats' => $stats
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to update rating']);
        }
    } else {
        // Create new comment with rating
        $comment = new Comment(
            null,
            $eventId,
            $userId,
            $userName,
            $userEmail,
            $content,
            $rating
        );
        
        if ($commentController->addComment($comment)) {
            // Update event_statistics table
            updateEventStatistics($eventId);
            
            // Get updated stats
            $stats = $commentController->getEventRatingStats($eventId);
            
            echo json_encode([
                'success' => true,
                'message' => 'Rating saved successfully',
                'action' => 'created',
                'rating' => $rating,
                'stats' => $stats
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to save rating']);
        }
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

/**
 * Update event_statistics table in real-time
 */
function updateEventStatistics($eventId) {
    require_once __DIR__ . '/../../config/database.php';
    
    try {
        $pdo = Database::getConnection();
        
        $sql = "INSERT INTO event_statistics 
            (id_evenement, event_title, event_location, event_status, event_start, event_end,
             total_participants, total_tickets, active_tickets, used_tickets, cancelled_tickets,
             total_comments, average_rating, five_stars, four_stars, three_stars, two_stars, one_star,
             total_likes, total_dislikes, reported_comments, creator_id)
        SELECT 
            e.id_evenement,
            e.titre,
            e.lieu,
            e.statut,
            e.date_debut,
            e.date_fin,
            
            COUNT(DISTINCT p.id_participation) as total_participants,
            
            COUNT(DISTINCT t.id_ticket) as total_tickets,
            SUM(CASE WHEN t.status = 'active' THEN 1 ELSE 0 END) as active_tickets,
            SUM(CASE WHEN t.status = 'used' THEN 1 ELSE 0 END) as used_tickets,
            SUM(CASE WHEN t.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_tickets,
            
            COUNT(DISTINCT c.id_comment) as total_comments,
            COALESCE(AVG(c.rating), 0) as average_rating,
            SUM(CASE WHEN c.rating = 5 THEN 1 ELSE 0 END) as five_stars,
            SUM(CASE WHEN c.rating = 4 THEN 1 ELSE 0 END) as four_stars,
            SUM(CASE WHEN c.rating = 3 THEN 1 ELSE 0 END) as three_stars,
            SUM(CASE WHEN c.rating = 2 THEN 1 ELSE 0 END) as two_stars,
            SUM(CASE WHEN c.rating = 1 THEN 1 ELSE 0 END) as one_star,
            
            COALESCE(SUM(c.likes), 0) as total_likes,
            COALESCE(SUM(c.dislikes), 0) as total_dislikes,
            SUM(CASE WHEN c.is_reported = 1 THEN 1 ELSE 0 END) as reported_comments,
            e.createur_id
            
        FROM evenement e
        LEFT JOIN participation p ON e.id_evenement = p.id_evenement
        LEFT JOIN tickets t ON e.id_evenement = t.id_evenement
        LEFT JOIN comment c ON e.id_evenement = c.id_evenement
        WHERE e.id_evenement = :event_id
        GROUP BY e.id_evenement
        ON DUPLICATE KEY UPDATE
            total_comments = VALUES(total_comments),
            average_rating = VALUES(average_rating),
            five_stars = VALUES(five_stars),
            four_stars = VALUES(four_stars),
            three_stars = VALUES(three_stars),
            two_stars = VALUES(two_stars),
            one_star = VALUES(one_star),
            total_likes = VALUES(total_likes),
            total_dislikes = VALUES(total_dislikes),
            reported_comments = VALUES(reported_comments)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':event_id' => $eventId]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Error updating event statistics: " . $e->getMessage());
        return false;
    }
}
