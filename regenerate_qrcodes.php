<?php
/**
 * Regenerate QR codes for existing tickets with verification URL
 * Run this script once to update old QR codes
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/site_config.php';
require_once __DIR__ . '/libs/phpqrcode/qrlib.php';

try {
    $pdo = Database::getConnection();
    
    // Get all tickets
    $sql = "SELECT id_ticket, token, id_participation, id_evenement FROM tickets";
    $stmt = $pdo->query($sql);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($tickets) . " tickets to update...\n";
    echo "Using URL: " . VERIFY_TICKET_URL . "\n\n";
    
    $qrCodeDir = __DIR__ . '/view/front/qrcodes/';
    if (!file_exists($qrCodeDir)) {
        mkdir($qrCodeDir, 0777, true);
    }
    
    $updated = 0;
    
    foreach ($tickets as $ticket) {
        // Generate new QR code with URL
        $filename = 'ticket_' . $ticket['id_participation'] . '_' . $ticket['id_evenement'] . '_' . time() . '.png';
        $filepath = $qrCodeDir . $filename;
        
        $qrContent = VERIFY_TICKET_URL . '?token=' . urlencode($ticket['token']);
        
        // Generate QR code
        QRcode::png($qrContent, $filepath, QR_ECLEVEL_L, 4, 2);
        
        // Update database
        $updateSql = "UPDATE tickets SET qr_code_path = :qr_path WHERE id_ticket = :id_ticket";
        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->execute([
            ':qr_path' => 'qrcodes/' . $filename,
            ':id_ticket' => $ticket['id_ticket']
        ]);
        
        $updated++;
        echo "✓ Updated ticket #" . $ticket['id_ticket'] . " (Token: " . $ticket['token'] . ")\n";
        echo "  QR URL: " . $qrContent . "\n";
        
        // Small delay to ensure unique timestamps
        usleep(100000); // 0.1 second
    }
    
    echo "\n✅ Successfully regenerated $updated QR codes!\n";
    echo "All QR codes now link to: " . VERIFY_TICKET_URL . "\n";
    echo "\n📱 To scan from your phone:\n";
    echo "1. Make sure your phone is on the same WiFi network\n";
    echo "2. Scan the QR code with any QR scanner app\n";
    echo "3. Your phone will open: " . VERIFY_TICKET_URL . "?token=XXX\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
