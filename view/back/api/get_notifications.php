<?php
// Use output buffering to catch any accidental whitespace or warnings
ob_start();

header('Content-Type: application/json');

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../controller/UserController.php';

// Force error reporting OFF after all requires (which might have turned it ON)
error_reporting(0);
ini_set('display_errors', 0);

// Check authentication
if (!UserController::isLoggedIn()) {
    if (ob_get_length()) ob_clean();
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$currentUser = UserController::getCurrentUser();
if (!$currentUser || !in_array(strtolower($currentUser->getRole()), ['admin', 'superadmin'])) {
    if (ob_get_length()) ob_clean();
    echo json_encode(['success' => false, 'error' => 'Forbidden']);
    exit;
}

try {
    $db = Config::getConnexion();
    $lastCheckParam = isset($_GET['last_check']) ? intval($_GET['last_check']) : 0;

    // If first load (0), fetch extensive history (e.g., last 24h), else fetch since last check
    $lastCheckDate = $lastCheckParam > 0
        ? date('Y-m-d H:i:s', $lastCheckParam / 1000)
        : date('Y-m-d H:i:s', strtotime('-24 hours'));

    $notifications = [];

    // 1. RECLAMATIONS (Support)
    try {
        $stmt = $db->prepare("SELECT id_reclamation as id, 'support' as type, sujet as title, description as text, date_creation as time 
                             FROM reclamations 
                             WHERE date_creation > :last_check 
                             AND statut IN ('nouveau', 'pending')
                             ORDER BY date_creation DESC");
        $stmt->execute([':last_check' => $lastCheckDate]);
        $reclamations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($reclamations as $rec) {
            $notifications[] = [
                'id' => $rec['id'],
                'type' => 'support',
                'title' => 'Support: ' . ($rec['title'] ?? 'Sans sujet'),
                'text' => mb_substr(($rec['text'] ?? ''), 0, 50) . '...',
                'time' => $rec['time'],
                'timestamp' => strtotime($rec['time'] ?? 'now')
            ];
        }
    } catch (Exception $e) {
        // Ignore if error in this specific query
    }

    // 2. USERS (New Registrations)
    try {
        $stmt = $db->prepare("SELECT id, 'user' as type, username as title, 'New user registered' as text, created_at as time 
                             FROM users 
                             WHERE created_at > :last_check 
                             ORDER BY created_at DESC");
        $stmt->execute([':last_check' => $lastCheckDate]);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($users as $user) {
            $notifications[] = [
                'id' => $user['id'],
                'type' => 'user',
                'title' => 'New User: ' . ($user['title'] ?? 'Unknown'),
                'text' => 'Role: Gamer',
                'time' => $user['time'],
                'timestamp' => strtotime($user['time'] ?? 'now')
            ];
        }
    } catch (Exception $e) {
        // Ignore if created_at missing
    }

    // 3. TRADES & SHOP (Trade History)
    try {
        $stmt = $db->prepare("SELECT id, action, skin_name, skin_price, created_at as time 
                             FROM trade_history 
                             WHERE created_at > :last_check 
                             AND action IN ('trade', 'bought', 'sold', 'created')
                             ORDER BY created_at DESC");
        $stmt->execute([':last_check' => $lastCheckDate]);
        $trades = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($trades as $trade) {
            $type = 'trade';
            $title = 'Trade Update';
            $text = '';

            switch ($trade['action']) {
                case 'trade':
                    $title = 'New Trade Completed';
                    $text = "Skin: {$trade['skin_name']} ({$trade['skin_price']} €)";
                    $type = 'trade';
                    break;
                case 'bought':
                case 'sold':
                    $title = 'Shop Sale';
                    $text = "Item sold: {$trade['skin_name']} for {$trade['skin_price']} €";
                    $type = 'shop';
                    break;
                case 'created':
                    $title = 'New Listing';
                    $text = "New item listed: {$trade['skin_name']}";
                    $type = 'shop';
                    break;
            }

            $notifications[] = [
                'id' => $trade['id'],
                'type' => $type,
                'title' => $title,
                'text' => $text,
                'time' => $trade['time'],
                'timestamp' => strtotime($trade['time'] ?? 'now')
            ];
        }
    } catch (Exception $e) {
        // Ignore if trade_history missing
    }

    // Sort by timestamp DESC
    usort($notifications, function ($a, $b) {
        return $b['timestamp'] - $a['timestamp'];
    });

    // Clear buffer before outputting JSON
    if (ob_get_length()) ob_clean();
    
    echo json_encode([
        'success' => true,
        'new_count' => count($notifications),
        'notifications' => array_values($notifications),
        'debug_last_check' => $lastCheckDate
    ]);

} catch (Exception $e) {
    if (ob_get_length()) ob_clean();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
ob_end_flush();
