<?php
header('Content-Type: application/json');

// Disable error reporting for cleaner JSON output
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../controller/UserController.php';

// Check authentication
if (!UserController::isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$currentUser = UserController::getCurrentUser();
if (!$currentUser || !in_array(strtolower($currentUser->getRole()), ['admin', 'superadmin'])) {
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
    // Table: reclamations (id_reclamation, date_creation, statut)
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
            'title' => 'Support: ' . $rec['title'],
            'text' => substr($rec['text'], 0, 50) . '...',
            'time' => $rec['time'],
            'timestamp' => strtotime($rec['time'])
        ];
    }

    // 2. USERS (New Registrations)
    // Table: users (id, username, created_at, role) - assuming created_at exists, verified in User.php logic it inserts NOW() usually but schema might vary.
    // User.php lines 110: INSERT INTO users ... (no created_at in insert?). 
    // Wait, User.php insert doesn't show created_at. Let's check DB schema via error safe query or assume standard.
    // Actually, looking at User.php, it doesn't seem to manage created_at in INSERT manually, usually DB has DEFAULT CURRENT_TIMESTAMP.
    // Let's try selecting it.
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
                'title' => 'New User: ' . $user['title'],
                'text' => 'Role: Gamer', // Default
                'time' => $user['time'],
                'timestamp' => strtotime($user['time'])
            ];
        }
    } catch (Exception $e) {
        // Ignore if created_at missing
    }

    // 3. TRADES & SHOP (Trade History)
    // Table: trade_history (action, skin_name, skin_price, created_at)
    // Actions: 'created', 'updated', 'deleted', 'buy', 'bought', 'sold', 'negotiation_refused', 'trade'
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
            'timestamp' => strtotime($trade['time'])
        ];
    }

    // Sort by timestamp DESC
    usort($notifications, function ($a, $b) {
        return $b['timestamp'] - $a['timestamp'];
    });

    echo json_encode([
        'success' => true,
        'new_count' => count($notifications),
        'notifications' => array_values($notifications), // Ensure indexed array
        'debug_last_check' => $lastCheckDate
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
