<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
// Include UserController for authentication
require_once __DIR__ . '/../../controller/UserController.php';

$isLoggedIn = UserController::isLoggedIn();
$currentUser = null;

if ($isLoggedIn) {
    $currentUser = UserController::getCurrentUser();
}

$userImage = null;
if ($currentUser && $currentUser->getImage()) {
    $userImage = '../../view/' . $currentUser->getImage();
}
// Include controllers
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../controller/reclamationcontroller.php';
require_once __DIR__ . '/../../controller/ResponseController.php';
require_once __DIR__ . '/../../controller/SatisfactionController.php';
require_once __DIR__ . '/../../model/Satisfaction.php';

$reclamationController = new ReclamationController();
$responseController = new ResponseController();
$satisfactionController = new SatisfactionController();

// Process adding a rating
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'rate_reclamation') {
    header('Content-Type: application/json');
    
    $id_reclamation = intval($_POST['id_reclamation'] ?? 0);
    $email = trim($_POST['email'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $rating = intval($_POST['rating'] ?? 0);
    $commentaire = trim($_POST['commentaire'] ?? '');
    
    // Validation
    // Email and name are now optional - if both are empty, use an anonymous identifier
    if (empty($email) && empty($name)) {
        // Generate an anonymous identifier based on IP and timestamp
        $anonymousId = 'anonymous_' . md5($_SERVER['REMOTE_ADDR'] . date('Y-m-d') . $id_reclamation);
        $email = $anonymousId;
    } elseif (empty($email) && !empty($name)) {
        // If only name is provided, use name as identifier
        $email = 'user_' . md5($name . $id_reclamation);
    } elseif (empty($email)) {
        // If email empty but we need an identifier
        $email = 'anonymous_' . md5($_SERVER['REMOTE_ADDR'] . date('Y-m-d') . $id_reclamation);
    }
    
    if ($rating < 1 || $rating > 5) {
        echo json_encode(['success' => false, 'message' => 'Invalid rating (must be between 1 and 5)']);
        exit;
    }
    
    if (empty($id_reclamation) || $id_reclamation <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid complaint ID']);
        exit;
    }
    
    // Store name in comment if provided
    $finalCommentaire = null;
    if (!empty($name) && empty($commentaire)) {
        $finalCommentaire = 'Rated by: ' . $name;
    } elseif (!empty($name) && !empty($commentaire)) {
        $finalCommentaire = 'Rated by: ' . $name . ' | ' . $commentaire;
    } elseif (!empty($commentaire)) {
        $finalCommentaire = $commentaire;
    }
    
    // If comment is empty, use null
    if (empty($finalCommentaire)) {
        $finalCommentaire = null;
    }
    
    try {
        // Check that complaint exists
        $reclamationCheck = $reclamationController->getReclamationById($id_reclamation);
        if (!$reclamationCheck) {
            echo json_encode(['success' => false, 'message' => 'Complaint not found.']);
            exit;
        }
        
        // Check that satisfactions table exists
        $db = Config::getConnexion();
        $tableCheck = $db->query("SHOW TABLES LIKE 'satisfactions'");
        if ($tableCheck->rowCount() == 0) {
            echo json_encode([
                'success' => false, 
                'message' => 'The satisfactions table does not exist. Please run: http://localhost/foxunity/create_satisfactions_table.php'
            ]);
            exit;
        }
        
        // Add rating with detailed debugging
        error_log("🔵 ========== START ADDING RATING ==========");
        error_log("🔵 Complaint ID: $id_reclamation");
        error_log("🔵 Email: " . substr($email, 0, 50));
        error_log("🔵 Rating: $rating");
        error_log("🔵 Comment: " . (empty($finalCommentaire) ? 'empty' : substr($finalCommentaire, 0, 50)));
        
        try {
            $result = $satisfactionController->addSatisfaction($id_reclamation, $email, $rating, $finalCommentaire);
            
            error_log("🔵 Result of addSatisfaction: " . var_export($result, true));
            error_log("🔵 Result type: " . gettype($result));
            error_log("🔵 Is empty: " . (empty($result) ? 'YES' : 'NO'));
            error_log("🔵 Is false: " . ($result === false ? 'YES' : 'NO'));
            error_log("🔵 Is > 0: " . (is_numeric($result) && $result > 0 ? 'YES' : 'NO'));
            
            if ($result && $result !== false && (is_numeric($result) ? $result > 0 : true)) {
                error_log("✅ Rating added successfully - Complaint ID: $id_reclamation, Rating: $rating, Email: " . substr($email, 0, 30));
                error_log("🔵 ========== END ADDING RATING (SUCCESS) ==========");
                echo json_encode(['success' => true, 'message' => 'Thank you for your rating!']);
            } else {
                error_log('❌ addSatisfaction returned an invalid result');
                error_log('❌ Returned value: ' . var_export($result, true));
                error_log('❌ Type: ' . gettype($result));
                
                // Check table structure
                try {
                    $columns = $db->query("SHOW COLUMNS FROM satisfactions");
                    $columnNames = [];
                    while ($col = $columns->fetch(PDO::FETCH_ASSOC)) {
                        $columnNames[] = $col['Field'];
                    }
                    error_log('📋 Columns in satisfactions table: ' . implode(', ', $columnNames));
                } catch (Exception $e) {
                    error_log('❌ Error checking columns: ' . $e->getMessage());
                }
                
                // Test a direct insert to see exact error
                try {
                    $testEmail = 'test_debug_' . time() . '@test.com';
                    $testQuery = $db->prepare("INSERT INTO satisfactions (id_reclamation, email, rating, commentaire, date_evaluation) VALUES (?, ?, ?, ?, NOW())");
                    $testResult = $testQuery->execute([$id_reclamation, $testEmail, $rating, $finalCommentaire]);
                    
                    if ($testResult) {
                        $testId = $db->lastInsertId();
                        error_log("✅ Direct insert test succeeded - ID: $testId");
                        // Delete test
                        $db->prepare("DELETE FROM satisfactions WHERE id_satisfaction = ?")->execute([$testId]);
                        error_log("❌ PROBLEM: Direct insert works but controller fails!");
                    } else {
                        $errorInfo = $testQuery->errorInfo();
                        error_log("❌ Error in direct insert test: " . print_r($errorInfo, true));
                    }
                } catch (PDOException $testE) {
                    error_log("❌ Exception in direct insert test: " . $testE->getMessage());
                    error_log("❌ Code: " . $testE->getCode());
                    error_log("❌ ErrorInfo: " . print_r($testE->errorInfo(), true));
                }
                
                error_log("🔵 ========== END ADDING RATING (FAILURE) ==========");
                echo json_encode([
                    'success' => false, 
                    'message' => 'Error during save. Check server logs (error.log) or run: http://localhost/foxunity/test_satisfaction_insert.php'
                ]);
            }
        } catch (Exception $controllerException) {
            error_log('❌ Exception in addSatisfaction: ' . $controllerException->getMessage());
            error_log('❌ Stack trace: ' . $controllerException->getTraceAsString());
            
            $errorMsg = $controllerException->getMessage();
            $userMessage = 'Error saving rating.';
            
            // Detect Foreign Key problem on email
            if (strpos($errorMsg, "foreign key constraint fails") !== false || 
                strpos($errorMsg, "1452") !== false ||
                strpos($errorMsg, "fk_satisfactions_user") !== false ||
                (strpos($errorMsg, "FOREIGN KEY") !== false && strpos($errorMsg, "email") !== false)) {
                $userMessage = '⚠️ Foreign Key constraint error detected. Click here to fix: <a href="http://localhost/foxunity/scripts/fix_satisfaction_foreign_key.php" target="_blank" style="color: #ff7a00; text-decoration: underline;">fix_satisfaction_foreign_key.php</a>';
            } elseif (strpos($errorMsg, "CONSTRAINT_ERROR") !== false) {
                $userMessage = 'UNIQUE constraint problem. Run: http://localhost/foxunity/fix_satisfactions_table.php';
            } elseif (strpos($errorMsg, "doesn't exist") !== false || strpos($errorMsg, "n'existe pas") !== false || strpos($errorMsg, "TABLE_NOT_FOUND") !== false) {
                $userMessage = 'The satisfactions table does not exist. Run: http://localhost/foxunity/create_satisfactions_table.php';
            } elseif (strpos($errorMsg, "Duplicate entry") !== false || strpos($errorMsg, "UNIQUE") !== false) {
                $userMessage = 'You have already rated this complaint with this email.';
            } elseif (strpos($errorMsg, "Integrity constraint violation") !== false) {
                $userMessage = 'Integrity constraint error. Run: http://localhost/foxunity/fix_satisfaction_foreign_key.php to fix.';
            }
            
            echo json_encode([
                'success' => false, 
                'message' => $userMessage
            ]);
        }
    } catch (PDOException $e) {
        $errorMsg = $e->getMessage();
        $errorCode = $e->getCode();
        error_log('❌ PDO Exception in addSatisfaction: ' . $errorMsg);
        error_log('❌ Code: ' . $errorCode);
        error_log('❌ ErrorInfo: ' . print_r($e->errorInfo(), true));
        
        $userMessage = 'Error saving rating.';
        
        // Detect Foreign Key problem (code 1452 or 23000)
        if ($errorCode == 1452 || $errorCode == 23000 || 
            strpos($errorMsg, "foreign key constraint fails") !== false ||
            strpos($errorMsg, "fk_satisfactions_user") !== false ||
            (strpos($errorMsg, "FOREIGN KEY") !== false && strpos($errorMsg, "email") !== false) ||
            strpos($errorMsg, "Integrity constraint violation") !== false) {
            $userMessage = '⚠️ Foreign Key constraint error detected. Run this script to fix: http://localhost/foxunity/scripts/fix_satisfaction_foreign_key.php';
        } elseif (strpos($errorMsg, "Duplicate entry") !== false || 
            strpos($errorMsg, "duplicata") !== false ||
            strpos($errorMsg, "UNIQUE") !== false ||
            strpos($errorMsg, "unique_reclamation") !== false) {
            $userMessage = 'UNIQUE constraint error. Please run: http://localhost/foxunity/fix_satisfactions_table.php to fix the database.';
        } elseif (strpos($errorMsg, "doesn't exist") !== false || 
                   strpos($errorMsg, "n'existe pas") !== false ||
                   strpos($errorMsg, "TABLE_NOT_FOUND") !== false) {
            $userMessage = 'The satisfactions table does not exist. Please run: http://localhost/foxunity/create_satisfactions_table.php';
        } elseif (strpos($errorMsg, "CONSTRAINT_ERROR") !== false) {
            $userMessage = 'UNIQUE constraint problem. Run: http://localhost/foxunity/fix_satisfactions_table.php';
        }
        
        echo json_encode([
            'success' => false, 
            'message' => $userMessage
        ]);
    } catch (Exception $e) {
        $errorMsg = $e->getMessage();
        error_log('❌ Exception in addSatisfaction: ' . $errorMsg);
        error_log('❌ Stack trace: ' . $e->getTraceAsString());
        
        $userMessage = 'Error saving rating.';
        
        // Detect Foreign Key problem
        if (strpos($errorMsg, "foreign key constraint fails") !== false || 
            strpos($errorMsg, "1452") !== false ||
            strpos($errorMsg, "fk_satisfactions_user") !== false ||
            strpos($errorMsg, "Integrity constraint violation") !== false) {
            $userMessage = '⚠️ Foreign Key constraint error. Run: http://localhost/foxunity/scripts/fix_satisfaction_foreign_key.php';
        } elseif (strpos($errorMsg, "CONSTRAINT_ERROR") !== false) {
            $userMessage = 'UNIQUE constraint problem detected. Please run: http://localhost/foxunity/fix_satisfactions_table.php';
        } elseif (strpos($errorMsg, "TABLE_NOT_FOUND") !== false) {
            $userMessage = 'The satisfactions table does not exist. Please run: http://localhost/foxunity/create_satisfactions_table.php';
        } elseif (strpos($errorMsg, "Duplicate entry") !== false || strpos($errorMsg, "UNIQUE") !== false) {
            $userMessage = 'You have already rated this complaint with this email.';
        }
        
        echo json_encode([
            'success' => false, 
            'message' => $userMessage
        ]);
    }
    exit;
}

// Retrieve ALL complaints (for public display - all users can view and react)
$allReclamations = $reclamationController->getAllReclamations(null, null, null);

// Retrieve responses and all ratings for each complaint
// and calculate global / by-category statistics for advanced display
$globalStats = [
    'total_evaluations' => 0,
    'total_rating_sum' => 0,
    'ratings_count' => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0],
    'satisfied_count' => 0, // rating >= 4
];

$categoryStats = []; // [category => ['total_evaluations' => ..., 'rating_sum' => ..., 'satisfied_count' => ...]]

foreach ($allReclamations as &$reclamation) {
    $reclamation['responses'] = $responseController->getResponsesByReclamationId($reclamation['id_reclamation']);
    $reclamation['satisfactions'] = $satisfactionController->getSatisfactionsByReclamationId($reclamation['id_reclamation']);

    $ratingCount = 0;
    $ratingSum = 0;
    $hasComment = false;

    if (!empty($reclamation['satisfactions'])) {
        foreach ($reclamation['satisfactions'] as $sat) {
            $rating = (int) $sat->getRating();
            $ratingSum += $rating;
            $ratingCount++;

            // Global stat
            $globalStats['total_evaluations']++;
            $globalStats['total_rating_sum'] += $rating;
            if (isset($globalStats['ratings_count'][$rating])) {
                $globalStats['ratings_count'][$rating]++;
            }
            if ($rating >= 4) {
                $globalStats['satisfied_count']++;
            }

            // Category stat
            $catKey = !empty($reclamation['categorie']) ? strtolower($reclamation['categorie']) : 'other';
            if (!isset($categoryStats[$catKey])) {
                $categoryStats[$catKey] = [
                    'label' => $reclamation['categorie'] ?? 'Other',
                    'total_evaluations' => 0,
                    'rating_sum' => 0,
                    'satisfied_count' => 0,
                ];
            }
            $categoryStats[$catKey]['total_evaluations']++;
            $categoryStats[$catKey]['rating_sum'] += $rating;
            if ($rating >= 4) {
                $categoryStats[$catKey]['satisfied_count']++;
            }

            // Detect if there is at least one "real" comment
            $comment = trim((string) $sat->getCommentaire());
            if ($comment !== '') {
                // If comment starts with "Rated by:", check if there is a real comment part after " | "
                if (strpos($comment, 'Rated by:') === 0) {
                    $parts = explode(' | ', $comment);
                    if (isset($parts[1]) && trim($parts[1]) !== '') {
                        $hasComment = true;
                    }
                } else {
                    $hasComment = true;
                }
            }
        }
    }

    if ($ratingCount > 0) {
        $reclamation['average_rating'] = round($ratingSum / $ratingCount, 1);
        $reclamation['rating_count'] = $ratingCount;
    } else {
        $reclamation['average_rating'] = 0;
        $reclamation['rating_count'] = 0;
    }

    $reclamation['has_comment'] = $hasComment;
}
unset($reclamation);

// Final calculations for global stats
$globalStats['average_rating'] = $globalStats['total_evaluations'] > 0
    ? round($globalStats['total_rating_sum'] / $globalStats['total_evaluations'], 1)
    : 0;

$globalStats['satisfied_percentage'] = $globalStats['total_evaluations'] > 0
    ? round(($globalStats['satisfied_count'] / $globalStats['total_evaluations']) * 100, 1)
    : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Public Complaints - FoxUnity</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Orbitron:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        :root {
            --primary-color: #ff7a00;
            --primary-dark: #ff4f00;
            --bg-dark: #0a0a0a;
            --bg-card: rgba(20, 20, 20, 0.95);
            --text-light: #ffffff;
            --text-gray: #aaaaaa;
            --border-color: rgba(255, 255, 255, 0.1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: var(--bg-dark);
            color: var(--text-light);
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            margin-top: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
            padding: 30px;
            background: linear-gradient(135deg, var(--bg-card) 0%, rgba(10, 10, 10, 0.95) 100%);
            border: 2px solid var(--border-color);
            border-radius: 15px;
        }
        
        .header h1 {
            font-family: 'Orbitron', sans-serif;
            font-size: 36px;
            margin-bottom: 10px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .header p {
            color: var(--text-gray);
            font-size: 16px;
        }

        /* Cart in header */
        .cart-icon {
            color: #ff7a00 !important;
            position: relative;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .cart-icon:hover {
            color: #ff9933 !important;
            transform: translateY(-2px);
        }
        
        .cart-icon i {
            color: #ff7a00;
            font-size: 18px;
        }
        
        .cart-count {
            background: linear-gradient(135deg, #ff7a00, #ff4f00);
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 11px;
            font-weight: 700;
            position: absolute;
            top: -8px;
            right: -8px;
            min-width: 18px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(255, 122, 0, 0.4);
        }

        /* Global statistics block */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stats-card {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 18px 20px;
        }

        .stats-card h4 {
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-gray);
            margin-bottom: 8px;
        }

        .stats-main-value {
            font-size: 26px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 4px;
        }

        .stats-subtext {
            font-size: 13px;
            color: var(--text-gray);
        }

        .rating-distribution {
            margin-top: 10px;
        }

        .rating-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 4px;
            font-size: 12px;
            color: var(--text-gray);
        }

        .rating-row i {
            color: #ffc107;
        }

        .progress-bar-container {
            flex: 1;
            background: rgba(255, 255, 255, 0.06);
            border-radius: 999px;
            overflow: hidden;
            height: 6px;
        }

        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #ffc107, #ff7a00);
            width: 0;
            transition: width 0.5s ease;
        }

        /* Filters and sorting */
        .filters-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
            justify-content: space-between;
            padding: 15px 20px;
            margin-bottom: 25px;
            background: rgba(255, 255, 255, 0.02);
            border-radius: 12px;
            border: 1px solid var(--border-color);
        }

        .filters-group {
            display: flex;
            flex-wrap: wrap;
            gap: 10px 15px;
            align-items: center;
        }

        .filters-bar label {
            font-size: 13px;
            color: var(--text-gray);
        }

        .filters-select,
        .filters-checkbox {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--border-color);
            color: var(--text-light);
            border-radius: 20px;
            padding: 6px 12px;
            font-size: 13px;
        }

        .filters-select {
            padding-right: 26px;
        }

        .filters-checkbox input {
            margin-right: 6px;
        }

        .filters-select:focus,
        .filters-checkbox input:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .reclamations-list {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }
        
        .reclamation-card {
            background: linear-gradient(135deg, var(--bg-card) 0%, rgba(10, 10, 10, 0.95) 100%);
            border: 2px solid var(--border-color);
            border-radius: 15px;
            padding: 30px;
            transition: all 0.3s ease;
        }
        
        .reclamation-card:hover {
            border-color: rgba(255, 122, 0, 0.3);
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(255, 122, 0, 0.2);
        }
        
        .reclamation-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .reclamation-title {
            font-size: 22px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        .reclamation-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            font-size: 14px;
            color: var(--text-gray);
        }
        
        .category-badge {
            background: rgba(255, 122, 0, 0.2);
            color: #ff7a00;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            border: 1px solid rgba(255, 122, 0, 0.3);
        }
        
        .reclamation-description {
            color: var(--text-light);
            line-height: 1.8;
            margin-bottom: 20px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 8px;
        }
        
        .responses-section {
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid var(--border-color);
        }
        
        .response-item {
            background: rgba(255, 122, 0, 0.05);
            border-left: 3px solid var(--primary-color);
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 8px;
        }
        
        .response-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .response-author {
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .response-date {
            color: var(--text-gray);
            font-size: 12px;
        }
        
        .response-text {
            color: var(--text-light);
            line-height: 1.6;
        }
        
        .rating-section {
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid var(--border-color);
        }
        
        .rating-form {
            background: rgba(255, 122, 0, 0.05);
            padding: 20px;
            border-radius: 10px;
            border: 1px solid rgba(255, 122, 0, 0.2);
        }
        
        .rating-form h4 {
            color: var(--primary-color);
            margin-bottom: 15px;
            font-size: 18px;
        }
        
        .star-rating {
            display: flex;
            gap: 5px;
            margin-bottom: 15px;
            direction: rtl;
        }
        
        .star-rating input[type="radio"] {
            display: none;
        }
        
        .star-rating label {
            font-size: 30px;
            color: #444;
            cursor: pointer;
            transition: color 0.2s;
            transform: translateY(0);
            transition: color 0.2s, transform 0.15s ease;
        }
        
        .star-rating label:hover,
        .star-rating label:hover ~ label,
        .star-rating input[type="radio"]:checked ~ label {
            color: #ffc107;
            transform: translateY(-2px) scale(1.05);
        }
        
        .star-rating input[type="radio"]:checked ~ label {
            color: #ffc107;
        }
        
        .rating-form textarea {
            width: 100%;
            padding: 12px;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-light);
            font-family: 'Poppins', sans-serif;
            resize: vertical;
            min-height: 100px;
            margin-bottom: 15px;
        }
        
        .rating-form textarea:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .rating-form input[type="email"],
        .rating-form input[type="text"] {
            width: 100%;
            padding: 12px;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-light);
            font-family: 'Poppins', sans-serif;
            margin-bottom: 15px;
        }
        
        .rating-form input[type="email"]:focus,
        .rating-form input[type="text"]:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .btn {
            padding: 12px 25px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 122, 0, 0.3);
        }
        
        .existing-rating {
            background: rgba(76, 175, 80, 0.1);
            border: 1px solid rgba(76, 175, 80, 0.3);
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
        }
        
        .existing-rating h5 {
            color: #4caf50;
            margin-bottom: 10px;
        }
        
        .rating-stars-display {
            color: #ffc107;
            font-size: 20px;
            margin-bottom: 10px;
        }
        
        .rating-comment {
            color: var(--text-light);
            line-height: 1.6;
            font-style: italic;
        }
        
        .no-reclamations {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-gray);
        }
        
        .no-reclamations i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background: rgba(76, 175, 80, 0.2);
            border: 1px solid rgba(76, 175, 80, 0.4);
            color: #4caf50;
        }
        
        .alert-error {
            background: rgba(220, 53, 69, 0.2);
            border: 1px solid rgba(220, 53, 69, 0.4);
            color: #dc3545;
        }
        
        @media (max-width: 768px) {
            .reclamation-header {
                flex-direction: column;
            }
            
            .header h1 {
                font-size: 28px;
            }
        }
        /* User Dropdown Menu Styles */
.user-dropdown {
    position: relative;
    display: inline-block;
}

.username-display {
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 10px;
    transition: all 0.3s ease;
    padding: 5px 10px;
    border-radius: 8px;
}

.username-display:hover {
    background: rgba(255, 122, 0, 0.1);
}

.username-display img {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #ff7a00;
}

.username-display span {
    color: #ff7a00;
    font-weight: 600;
    font-size: 16px;
}

.username-display i.fa-chevron-down {
    font-size: 12px;
    color: #ff7a00;
    transition: transform 0.3s ease;
}

.username-display i.fa-user-circle {
    font-size: 24px;
    color: #ff7a00;
}

.user-dropdown.active .username-display i.fa-chevron-down {
    transform: rotate(180deg);
}

.dropdown-menu {
    position: absolute;
    top: 100%;
    right: 0;
    margin-top: 10px;
    background: rgba(20, 20, 20, 0.98);
    border: 2px solid rgba(255, 122, 0, 0.3);
    border-radius: 12px;
    min-width: 200px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.3s ease;
    z-index: 1000;
    overflow: hidden;
}

.user-dropdown.active .dropdown-menu {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.dropdown-item {
    padding: 12px 15px;
    color: #fff;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 10px;
    transition: all 0.3s ease;
    border-left: 3px solid transparent;
}

.dropdown-item:hover {
    background: rgba(255, 122, 0, 0.1);
    border-left-color: #ff7a00;
}

.dropdown-item i {
    font-size: 16px;
    color: #ff7a00;
    width: 20px;
}

.dropdown-divider {
    height: 1px;
    background: rgba(255, 122, 0, 0.2);
    margin: 5px 0;
}

.dropdown-item.logout {
    color: #ff4444;
}

.dropdown-item.logout i {
    color: #ff4444;
}

.dropdown-item.logout:hover {
    background: rgba(255, 68, 68, 0.1);
    border-left-color: #ff4444;
}
    </style>
</head>
<body>
    <!-- HEADER -->
    <header class="site-header">
        <div class="logo-section">
            <img src="../images/Nine__1_-removebg-preview.png" alt="FoxUnity Logo" class="site-logo">
            <span class="site-name">FoxUnity</span>
        </div>
        
        <nav class="site-nav">
            <a href="index.php">Home</a>
            <a href="events.php">Events</a>
            <a href="shop.php">Shop</a>
            <a href="trading.php">Trading</a>
            <a href="news.php">News</a>
            <a href="reclamation.php">Support</a>
            <a href="contact_us.php">New Request</a>
            <a href="public_reclamations.php" class="active"><i class="fas fa-star"></i> Public Reviews</a>
            <a href="about.php">About Us</a>
        </nav>
        
        <div class="header-right">
    <div class="user-dropdown" id="userDropdown">
        <div class="username-display">
            <?php if ($isLoggedIn && $currentUser): ?>
                <?php if ($userImage): ?>
                    <img src="<?php echo htmlspecialchars($userImage); ?>" alt="Profile">
                <?php else: ?>
                    <i class="fas fa-user-circle"></i>
                <?php endif; ?>
                <span><?php echo htmlspecialchars($currentUser->getUsername()); ?></span>
            <?php else: ?>
                <i class="fas fa-user-circle"></i>
                <span>Guest</span>
            <?php endif; ?>
            <i class="fas fa-chevron-down"></i>
        </div>
        
        <div class="dropdown-menu">
            <?php if ($isLoggedIn && $currentUser): ?>
            <a href="profile.php" class="dropdown-item">
                <i class="fas fa-user"></i>
                <span>My Profile</span>
            </a>
            
            <a href="tradehis.php" class="dropdown-item">
                <i class="fas fa-history"></i>
                <span>Trade History</span>
            </a>
            
            <a href="events.php?view=history" class="dropdown-item">
                <i class="fas fa-ticket-alt"></i>
                <span>Event History</span>
            </a>
            
            <?php 
            $userRole = strtolower($currentUser->getRole());
            if ($userRole === 'admin' || $userRole === 'superadmin'): 
            ?>
            <a href="../back/dashboard.php" class="dropdown-item">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
            <?php endif; ?>
            
            <div class="dropdown-divider"></div>
            
            <a href="logout.php" class="dropdown-item logout">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
            <?php else: ?>
            <a href="login.php" class="dropdown-item">
                <i class="fas fa-sign-in-alt"></i>
                <span>Login/Register</span>
            </a>
            <?php endif; ?>
        </div>
    </div>
    
    <a href="panier.php" class="cart-icon">
        <i class="fas fa-shopping-cart"></i> Cart
        <span class="cart-count">0</span>
    </a>
</div>
    </header>

    <div class="container">
        <div class="header">
            <h1><i class="fas fa-comments"></i> Public Complaints</h1>
            <p>View all complaints and share your feedback with star ratings</p>
        </div>

        <?php if ($globalStats['total_evaluations'] > 0): ?>
            <div class="stats-grid">
                <div class="stats-card">
                    <h4>Overall Average Rating</h4>
                    <div class="stats-main-value">
                        <?php echo $globalStats['average_rating']; ?>/5
                    </div>
                    <div class="stats-subtext">
                        Based on <?php echo $globalStats['total_evaluations']; ?> review<?php echo $globalStats['total_evaluations'] > 1 ? 's' : ''; ?>
                    </div>
                </div>
                <div class="stats-card">
                    <h4>Satisfied Customers</h4>
                    <div class="stats-main-value">
                        <?php echo $globalStats['satisfied_percentage']; ?>%
                    </div>
                    <div class="stats-subtext">
                        Ratings of 4★ and 5★
                    </div>
                </div>
                <div class="stats-card">
                    <h4>Rating Distribution</h4>
                    <div class="rating-distribution">
                        <?php
                        $maxCount = max($globalStats['ratings_count']) ?: 1;
                        for ($r = 5; $r >= 1; $r--):
                            $count = $globalStats['ratings_count'][$r];
                            $percentageRow = $globalStats['total_evaluations'] > 0
                                ? round(($count / $globalStats['total_evaluations']) * 100)
                                : 0;
                            $width = $maxCount > 0 ? ($count / $maxCount) * 100 : 0;
                        ?>
                        <div class="rating-row" data-rating-row="<?php echo $r; ?>">
                            <span><?php echo $r; ?> <i class="fas fa-star"></i></span>
                            <div class="progress-bar-container">
                                <div class="progress-bar" style="width: <?php echo $width; ?>%;"></div>
                            </div>
                            <span><?php echo $percentageRow; ?>%</span>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>
                <?php if (!empty($categoryStats)): ?>
                    <div class="stats-card">
                        <h4>By Category</h4>
                        <div class="stats-subtext">
                            <?php foreach ($categoryStats as $catKey => $cat): 
                                $avgCat = $cat['total_evaluations'] > 0
                                    ? round($cat['rating_sum'] / $cat['total_evaluations'], 1)
                                    : 0;
                                $pctCat = $cat['total_evaluations'] > 0
                                    ? round(($cat['satisfied_count'] / $cat['total_evaluations']) * 100)
                                    : 0;
                            ?>
                                <div style="margin-bottom: 6px;">
                                    <strong><?php echo htmlspecialchars($cat['label']); ?></strong>:
                                    <?php echo $avgCat; ?>/5 • <?php echo $pctCat; ?>% satisfied
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['rated']) && $_GET['rated'] == '1'): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span>Thank you for your rating!</span>
            </div>
        <?php endif; ?>
        
        <?php if (empty($allReclamations)): ?>
            <div class="no-reclamations">
                <i class="fas fa-inbox"></i>
                <h2>No complaints at the moment</h2>
                <p>Complaints will appear here once they have been created.</p>
            </div>
        <?php else: ?>
            <div class="filters-bar">
                <div class="filters-group">
                    <label for="sort-by">Sort by:</label>
                    <select id="sort-by" class="filters-select">
                        <option value="recent">Most Recent</option>
                        <option value="best">Best Rating</option>
                        <option value="worst">Worst Rating</option>
                    </select>
                </div>
                <div class="filters-group">
                    <label class="filters-checkbox">
                        <input type="checkbox" id="filter-4plus">
                        4★ and above
                    </label>
                    <label class="filters-checkbox">
                        <input type="checkbox" id="filter-comment-only">
                        With comment
                    </label>
                    <label for="filter-category">Category:</label>
                    <select id="filter-category" class="filters-select">
                        <option value="all">All</option>
                        <?php foreach ($categoryStats as $catKey => $cat): ?>
                            <option value="<?php echo htmlspecialchars($catKey); ?>">
                                <?php echo htmlspecialchars($cat['label']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="reclamations-list">
                <?php foreach ($allReclamations as $reclamation): ?>
                    <?php
                        $catKey = !empty($reclamation['categorie']) ? strtolower($reclamation['categorie']) : 'other';
                    ?>
                    <div 
                        class="reclamation-card" 
                        id="reclamation-<?php echo $reclamation['id_reclamation']; ?>"
                        data-average-rating="<?php echo $reclamation['average_rating']; ?>"
                        data-rating-count="<?php echo $reclamation['rating_count']; ?>"
                        data-date="<?php echo htmlspecialchars($reclamation['date_creation']); ?>"
                        data-category="<?php echo htmlspecialchars($catKey); ?>"
                        data-has-comment="<?php echo !empty($reclamation['has_comment']) ? '1' : '0'; ?>"
                    >
                        <div class="reclamation-header">
                            <div>
                                <h3 class="reclamation-title"><?php echo htmlspecialchars($reclamation['sujet'] ?? ''); ?></h3>
                                <div class="reclamation-meta">
                                    <span><i class="far fa-calendar"></i> <?php echo date('m/d/Y h:i A', strtotime($reclamation['date_creation'])); ?></span>
                                    <?php if (!empty($reclamation['categorie']) && strtolower($reclamation['categorie']) !== 'other'): ?>
                                        <span class="category-badge">
                                            <i class="fas fa-tag"></i> <?php echo htmlspecialchars($reclamation['categorie']); ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php 
                                    $statut = $reclamation['statut'] ?? 'nouveau';
                                    $statutColors = [
                                        'nouveau' => ['bg' => 'rgba(255, 122, 0, 0.2)', 'color' => '#ff7a00', 'text' => 'New'],
                                        'en_cours' => ['bg' => 'rgba(255, 193, 7, 0.2)', 'color' => '#ffc107', 'text' => 'In Progress'],
                                        'resolu' => ['bg' => 'rgba(76, 175, 80, 0.2)', 'color' => '#4caf50', 'text' => 'Resolved']
                                    ];
                                    $statutInfo = $statutColors[$statut] ?? $statutColors['nouveau'];
                                    ?>
                                    <span class="category-badge" style="background: <?php echo $statutInfo['bg']; ?>; color: <?php echo $statutInfo['color']; ?>; border-color: <?php echo $statutInfo['color']; ?>;">
                                        <i class="fas fa-circle" style="font-size: 8px;"></i> <?php echo $statutInfo['text']; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="reclamation-description">
                            <?php echo nl2br(htmlspecialchars($reclamation['description'] ?? '')); ?>
                        </div>
                        
                        <?php if (!empty($reclamation['responses'])): ?>
                            <div class="responses-section">
                                <h4 style="color: var(--primary-color); margin-bottom: 15px;">
                                    <i class="fas fa-reply"></i> Team Response (<?php echo count($reclamation['responses']); ?>)
                                </h4>
                                <?php foreach ($reclamation['responses'] as $response): ?>
                                    <div class="response-item">
                                        <div class="response-header">
                                            <span class="response-author">
                                                <i class="fas fa-user-shield"></i> Admin
                                            </span>
                                            <span class="response-date">
                                                <?php echo date('m/d/Y h:i A', strtotime($response['date_reponse'] ?? $response['date_creation'] ?? 'now')); ?>
                                            </span>
                                        </div>
                                        <div class="response-text">
                                            <?php echo nl2br(htmlspecialchars($response['message'] ?? $response['response_text'] ?? '')); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="rating-section">
                            <!-- Display average and number of ratings -->
                            <?php if ($reclamation['rating_count'] > 0): ?>
                                <div style="margin-bottom: 20px; padding: 15px; background: rgba(255, 193, 7, 0.1); border-radius: 8px; border-left: 4px solid #ffc107;">
                                    <h5 style="color: #ffc107; margin-bottom: 10px;">
                                        <i class="fas fa-star"></i> Reviews (<?php echo $reclamation['rating_count']; ?>)
                                    </h5>
                                    <div class="rating-stars-display" style="font-size: 24px;">
                                        <?php 
                                        $avgRating = $reclamation['average_rating'];
                                        for ($i = 1; $i <= 5; $i++): 
                                        ?>
                                            <i class="fas fa-star" style="color: <?php echo $i <= round($avgRating) ? '#ffc107' : '#444'; ?>;"></i>
                                        <?php endfor; ?>
                                        <span style="margin-left: 10px; color: var(--text-light); font-size: 18px; font-weight: 700;">
                                            <?php echo $avgRating; ?>/5 (<?php echo $reclamation['rating_count']; ?> review<?php echo $reclamation['rating_count'] > 1 ? 's' : ''; ?>)
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- Display all reviews -->
                                <div style="margin-bottom: 20px;">
                                    <h5 style="color: var(--primary-color); margin-bottom: 15px;">
                                        <i class="fas fa-users"></i> All Reviews
                                    </h5>
                                    <?php foreach ($reclamation['satisfactions'] as $satisfaction): 
                                        // Determine display name
                                        $displayName = 'Anonymous User';
                                        $email = $satisfaction->getEmail();
                                        $commentaire = $satisfaction->getCommentaire();
                                        
                                        // If email starts with "anonymous_" or "user_", it's anonymous
                                        if (strpos($email, 'anonymous_') === 0 || strpos($email, 'user_') === 0) {
                                            // Try to extract name from comment
                                            if ($commentaire && strpos($commentaire, 'Rated by:') === 0) {
                                                $parts = explode(' | ', $commentaire);
                                                $displayName = str_replace('Rated by: ', '', $parts[0]);
                                            } else {
                                                $displayName = 'Anonymous User';
                                            }
                                        } else {
                                            // Real email - extract name from comment or use email
                                            if ($commentaire && strpos($commentaire, 'Rated by:') === 0) {
                                                $parts = explode(' | ', $commentaire);
                                                $displayName = str_replace('Rated by: ', '', $parts[0]);
                                            } else {
                                                // Use email but mask part of it
                                                $emailParts = explode('@', $email);
                                                $displayName = substr($emailParts[0], 0, 3) . '***@' . (isset($emailParts[1]) ? $emailParts[1] : '');
                                            }
                                        }
                                    ?>
                                        <div class="existing-rating" style="margin-bottom: 15px;">
                                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                                <div>
                                                    <strong style="color: var(--text-light);"><?php echo htmlspecialchars($displayName); ?></strong>
                                                    <span style="color: var(--text-gray); font-size: 12px; margin-left: 10px;">
                                                        <?php echo date('m/d/Y h:i A', strtotime($satisfaction->getDateEvaluation())); ?>
                                                    </span>
                                                </div>
                                                <div class="rating-stars-display" style="font-size: 16px;">
                                                    <?php 
                                                    $rating = $satisfaction->getRating();
                                                    for ($i = 1; $i <= 5; $i++): 
                                                    ?>
                                                        <i class="fas fa-star" style="color: <?php echo $i <= $rating ? '#ffc107' : '#444'; ?>;"></i>
                                                    <?php endfor; ?>
                                                    <span style="margin-left: 5px; color: var(--text-light);">(<?php echo $rating; ?>/5)</span>
                                                </div>
                                            </div>
                                            <?php 
                                            // Display comment only if it's not just the name
                                            $commentaireToShow = $satisfaction->getCommentaire();
                                            if ($commentaireToShow && strpos($commentaireToShow, 'Rated by:') === 0) {
                                                // If comment contains "Rated by:", extract the part after " | "
                                                $parts = explode(' | ', $commentaireToShow);
                                                if (isset($parts[1]) && !empty(trim($parts[1]))) {
                                                    $commentaireToShow = trim($parts[1]);
                                                } else {
                                                    $commentaireToShow = null; // No real comment, just the name
                                                }
                                            }
                                            if ($commentaireToShow): 
                                            ?>
                                                <div class="rating-comment">
                                                    "<?php echo htmlspecialchars($commentaireToShow); ?>"
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Rating form (always visible to allow everyone to rate) -->
                            <div class="rating-form" id="rating-form-<?php echo $reclamation['id_reclamation']; ?>">
                                <h4><i class="fas fa-star"></i> <?php echo $reclamation['rating_count'] > 0 ? 'Add Your Review' : 'Rate This Complaint'; ?></h4>
                                <form class="rating-form-inner" data-reclamation-id="<?php echo $reclamation['id_reclamation']; ?>">
                                    <div class="star-rating">
                                        <input type="radio" id="star5-<?php echo $reclamation['id_reclamation']; ?>" name="rating-<?php echo $reclamation['id_reclamation']; ?>" value="5">
                                        <label for="star5-<?php echo $reclamation['id_reclamation']; ?>"><i class="fas fa-star"></i></label>
                                        
                                        <input type="radio" id="star4-<?php echo $reclamation['id_reclamation']; ?>" name="rating-<?php echo $reclamation['id_reclamation']; ?>" value="4">
                                        <label for="star4-<?php echo $reclamation['id_reclamation']; ?>"><i class="fas fa-star"></i></label>
                                        
                                        <input type="radio" id="star3-<?php echo $reclamation['id_reclamation']; ?>" name="rating-<?php echo $reclamation['id_reclamation']; ?>" value="3">
                                        <label for="star3-<?php echo $reclamation['id_reclamation']; ?>"><i class="fas fa-star"></i></label>
                                        
                                        <input type="radio" id="star2-<?php echo $reclamation['id_reclamation']; ?>" name="rating-<?php echo $reclamation['id_reclamation']; ?>" value="2">
                                        <label for="star2-<?php echo $reclamation['id_reclamation']; ?>"><i class="fas fa-star"></i></label>
                                        
                                        <input type="radio" id="star1-<?php echo $reclamation['id_reclamation']; ?>" name="rating-<?php echo $reclamation['id_reclamation']; ?>" value="1">
                                        <label for="star1-<?php echo $reclamation['id_reclamation']; ?>"><i class="fas fa-star"></i></label>
                                    </div>
                                    
                                    <input type="text" name="name-<?php echo $reclamation['id_reclamation']; ?>" 
                                           placeholder="Your name (optional)">
                                    
                                    <input type="email" name="email-<?php echo $reclamation['id_reclamation']; ?>" 
                                           placeholder="Your email (optional)">
                                    
                                    <textarea name="commentaire-<?php echo $reclamation['id_reclamation']; ?>" 
                                              placeholder="Your comment (optional)"></textarea>
                                    
                                    <button type="submit" class="btn">
                                        <i class="fas fa-paper-plane"></i> Submit Review
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Handle rating forms
        document.querySelectorAll('.rating-form-inner').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const reclamationId = this.getAttribute('data-reclamation-id');
                const ratingInput = this.querySelector('input[type="radio"]:checked');
                const nameInput = this.querySelector('input[type="text"]');
                const emailInput = this.querySelector('input[type="email"]');
                const commentaireInput = this.querySelector('textarea');
                
                if (!ratingInput) {
                    alert('Please select a rating');
                    return;
                }
                
                // Email and name are now optional
                const formData = new FormData();
                formData.append('action', 'rate_reclamation');
                formData.append('id_reclamation', reclamationId);
                formData.append('name', nameInput ? nameInput.value.trim() : '');
                formData.append('email', emailInput ? emailInput.value.trim() : '');
                formData.append('rating', ratingInput.value);
                formData.append('commentaire', commentaireInput ? commentaireInput.value.trim() : '');
                
                // Disable button during submission
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
                
                fetch('public_reclamations.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('HTTP Error: ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Display success message
                        const formContainer = form.closest('.rating-form');
                        const successMsg = document.createElement('div');
                        successMsg.className = 'alert alert-success';
                        successMsg.style.marginTop = '15px';
                        successMsg.innerHTML = '<i class="fas fa-check-circle"></i> ' + (data.message || 'Thank you for your review!');
                        formContainer.insertBefore(successMsg, form);
                        
                        // Reset form
                        form.reset();
                        form.querySelectorAll('input[type="radio"]').forEach(radio => {
                            radio.checked = false;
                        });
                        form.querySelectorAll('label').forEach(label => {
                            label.style.color = '#444';
                        });
                        
                        // Reload page after 2 seconds to display new review
                        setTimeout(() => {
                            window.location.href = 'public_reclamations.php?rated=1#reclamation-' + reclamationId;
                        }, 2000);
                    } else {
                        console.error('Server error:', data);
                        
                        // Display error in form instead of alert
                        const formContainer = form.closest('.rating-form');
                        let errorMsg = formContainer.querySelector('.alert-error');
                        if (!errorMsg) {
                            errorMsg = document.createElement('div');
                            errorMsg.className = 'alert alert-error';
                            formContainer.insertBefore(errorMsg, form);
                        }
                        errorMsg.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + (data.message || 'Error submitting review.');
                        errorMsg.style.display = 'block';
                        
                        // Scroll to error
                        errorMsg.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                        
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalText;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while submitting. Please check your connection and try again.');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                });
            });
        });

        // Sorting and filtering complaints
        (function() {
            const list = document.querySelector('.reclamations-list');
            if (!list) return;

            const cards = Array.from(list.querySelectorAll('.reclamation-card'));
            const sortSelect = document.getElementById('sort-by');
            const filter4Plus = document.getElementById('filter-4plus');
            const filterCommentOnly = document.getElementById('filter-comment-only');
            const filterCategory = document.getElementById('filter-category');

            function applyFiltersAndSort() {
                const sortBy = sortSelect ? sortSelect.value : 'recent';
                const need4Plus = filter4Plus && filter4Plus.checked;
                const needComment = filterCommentOnly && filterCommentOnly.checked;
                const category = filterCategory ? filterCategory.value : 'all';

                // Filter
                cards.forEach(card => {
                    const avg = parseFloat(card.getAttribute('data-average-rating')) || 0;
                    const count = parseInt(card.getAttribute('data-rating-count') || '0', 10);
                    const hasComment = card.getAttribute('data-has-comment') === '1';
                    const catKey = card.getAttribute('data-category') || 'other';

                    let visible = true;

                    if (need4Plus && !(count > 0 && avg >= 4)) {
                        visible = false;
                    }
                    if (needComment && !hasComment) {
                        visible = false;
                    }
                    if (category !== 'all' && catKey !== category) {
                        visible = false;
                    }

                    card.style.display = visible ? '' : 'none';
                });

                // Sort only visible cards
                const visibleCards = cards.filter(c => c.style.display !== 'none');

                visibleCards.sort((a, b) => {
                    const dateA = new Date(a.getAttribute('data-date') || 0).getTime();
                    const dateB = new Date(b.getAttribute('data-date') || 0).getTime();
                    const avgA = parseFloat(a.getAttribute('data-average-rating')) || 0;
                    const avgB = parseFloat(b.getAttribute('data-average-rating')) || 0;

                    if (sortBy === 'best') {
                        // Best rating first, then most recent
                        if (avgB !== avgA) return avgB - avgA;
                        return dateB - dateA;
                    } else if (sortBy === 'worst') {
                        // Worst rating first, then most recent
                        if (avgA !== avgB) return avgA - avgB;
                        return dateB - dateA;
                    } else {
                        // Default: most recent
                        return dateB - dateA;
                    }
                });

                // Reorder in DOM
                visibleCards.forEach(card => list.appendChild(card));
            }

            if (sortSelect) sortSelect.addEventListener('change', applyFiltersAndSort);
            if (filter4Plus) filter4Plus.addEventListener('change', applyFiltersAndSort);
            if (filterCommentOnly) filterCommentOnly.addEventListener('change', applyFiltersAndSort);
            if (filterCategory) filterCategory.addEventListener('change', applyFiltersAndSort);

            // Initial application
            applyFiltersAndSort();
        })();
        
        // Dropdown Menu Toggle
document.addEventListener('DOMContentLoaded', function() {
    const userDropdown = document.getElementById('userDropdown');
    
    if (userDropdown) {
        const usernameDisplay = userDropdown.querySelector('.username-display');
        
        usernameDisplay.addEventListener('click', function(e) {
            e.stopPropagation();
            userDropdown.classList.toggle('active');
        });
        
        document.addEventListener('click', function(e) {
            if (!userDropdown.contains(e.target)) {
                userDropdown.classList.remove('active');
            }
        });
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                userDropdown.classList.remove('active');
            }
        });
    }
    
    // Existing cart code...
    const cart = JSON.parse(localStorage.getItem('cart')) || [];
    const cartCount = document.querySelector('.cart-count');
    if (cartCount) {
        cartCount.textContent = cart.length;
    }
});
    </script>

    <footer class="site-footer">
        <div class="footer-content">
            <div class="footer-section">
                <h4>FoxUnity</h4>
                <p>Gaming for Good - Every action makes a difference</p>
            </div>
            <div class="footer-section">
                <h4>Back to Top</h4>
                <a href="#" class="back-to-top-link" onclick="window.scrollTo({top: 0, behavior: 'smooth'}); return false;">
                    <i class="fas fa-arrow-up"></i> Scroll to Top
                </a>
            </div>
            <div class="footer-section">
                <h4>Support</h4>
                <a href="reclamation.php">Contact Support</a>
                <a href="#">FAQ</a>
                <a href="#">Privacy Policy</a>
            </div>
            <div class="footer-section">
                <h4>Follow Us</h4>
                <div class="social-links">
                    <a href="#"><i class="fab fa-discord"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
            <div class="footer-section">
                <h4>Dashboard</h4>
                <a href="../back/dashboard.php" class="dashboard-link">
                    <i class="fas fa-tachometer-alt"></i> My Dashboard
                </a>
                <a href="../back/reclamback.php" class="dashboard-link" style="margin-top: 10px; display: block;">
                    <i class="fas fa-headset"></i> Support Dashboard
                </a>
            </div>
        </div>
        <div class="footer-bottom">
            <p>© 2025 FoxUnity. All rights reserved. Made with <span>♥</span> by gamers for gamers</p>
        </div>
    </footer>
</body>
</html>