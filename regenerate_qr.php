<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/site_config.php';
require_once __DIR__ . '/libs/phpqrcode/qrlib.php';

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Régénération des QR Codes</title>
    <style>
        body { font-family: Arial; background: #1a1a1e; color: white; padding: 20px; }
        .success { color: #2ed573; }
        .error { color: #ff6b6b; }
        .info { color: #f5c242; }
        .result { background: rgba(255,255,255,0.05); padding: 15px; margin: 10px 0; border-radius: 8px; }
    </style>
</head>
<body>
<h1>🔄 Régénération des QR Codes</h1>
<p class='info'>Nouvelle IP: <strong>" . SERVER_IP . "</strong></p>
<p class='info'>URL de vérification: <strong>" . VERIFY_TICKET_URL . "</strong></p>
<hr>
";

try {
    $db = Database::getConnection();
    
    // Get all tickets
    $stmt = $db->query("SELECT id_ticket, id_participation, id_evenement, token, qr_code_path FROM tickets");
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Total de tickets trouvés: <strong>" . count($tickets) . "</strong></p>";
    
    $qrCodeDir = __DIR__ . '/view/front/qrcodes/';
    
    foreach ($tickets as $ticket) {
        echo "<div class='result'>";
        echo "<h3>Ticket #" . $ticket['id_ticket'] . " - " . htmlspecialchars($ticket['token']) . "</h3>";
        
        // Generate new QR code
        $filename = 'ticket_' . $ticket['id_participation'] . '_' . $ticket['id_evenement'] . '_' . time() . '.png';
        $filepath = $qrCodeDir . $filename;
        $newQrPath = 'qrcodes/' . $filename;
        
        // QR code content with new IP
        $qrContent = VERIFY_TICKET_URL . '?token=' . urlencode($ticket['token']);
        
        try {
            // Generate QR code
            QRcode::png($qrContent, $filepath, QR_ECLEVEL_L, 4, 2);
            
            // Delete old QR code if exists
            if (!empty($ticket['qr_code_path'])) {
                $oldPath = __DIR__ . '/view/front/' . $ticket['qr_code_path'];
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                    echo "<p>✓ Ancien QR supprimé: " . htmlspecialchars($ticket['qr_code_path']) . "</p>";
                }
            }
            
            // Update database
            $updateStmt = $db->prepare("UPDATE tickets SET qr_code_path = :qr_path WHERE id_ticket = :id");
            $updateStmt->execute([
                ':qr_path' => $newQrPath,
                ':id' => $ticket['id_ticket']
            ]);
            
            echo "<p class='success'>✅ Nouveau QR généré: " . htmlspecialchars($newQrPath) . "</p>";
            echo "<p class='info'>URL dans le QR: " . htmlspecialchars($qrContent) . "</p>";
            echo "<img src='view/front/" . htmlspecialchars($newQrPath) . "' style='max-width:150px; background:white; padding:10px; border-radius:8px;'>";
            
        } catch (Exception $e) {
            echo "<p class='error'>❌ Erreur: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        
        echo "</div>";
    }
    
    echo "<hr>";
    echo "<h2 class='success'>✅ Régénération terminée!</h2>";
    echo "<p><a href='mobile_test.php' style='color:#f5c242;'>→ Tester la connexion mobile</a></p>";
    echo "<p><a href='view/front/my_tickets.php' style='color:#f5c242;'>→ Voir mes tickets</a></p>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Erreur: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</body></html>";
?>
