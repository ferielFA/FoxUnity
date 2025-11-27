<?php
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../model/Ticket.php';

/**
 * Generate a beautiful ticket image with QR code and event details
 */
function generateTicketImage($ticketId) {
    try {
        // Get ticket data
        $pdo = Database::getConnection();
        $sql = "SELECT t.*, p.nom_participant, p.email_participant, e.titre, e.date_debut, e.date_fin, e.lieu
                FROM tickets t
                INNER JOIN participation p ON t.id_participation = p.id_participation
                INNER JOIN evenement e ON t.id_evenement = e.id_evenement
                WHERE t.id_ticket = :id_ticket";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id_ticket' => $ticketId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$data) {
            throw new Exception("Ticket not found");
        }
        
        // Dimensions du ticket
        $width = 800;
        $height = 400;
        
        // Créer l'image
        $ticket = imagecreatetruecolor($width, $height);
        
        // Couleurs
        $bgColor = imagecolorallocate($ticket, 22, 22, 26); // #16161a
        $primaryColor = imagecolorallocate($ticket, 245, 194, 66); // #f5c242 (gold)
        $secondaryColor = imagecolorallocate($ticket, 243, 156, 18); // #f39c12
        $whiteColor = imagecolorallocate($ticket, 255, 255, 255);
        $grayColor = imagecolorallocate($ticket, 150, 150, 150); // #969696
        $darkColor = imagecolorallocate($ticket, 27, 27, 32); // #1b1b20
        
        // Fond dégradé
        for ($i = 0; $i < $height; $i++) {
            $ratio = $i / $height;
            $r = 22 + (27 - 22) * $ratio;
            $g = 22 + (27 - 22) * $ratio;
            $b = 26 + (32 - 26) * $ratio;
            $gradientColor = imagecolorallocate($ticket, $r, $g, $b);
            imageline($ticket, 0, $i, $width, $i, $gradientColor);
        }
        
        // Bordure dorée
        imagerectangle($ticket, 10, 10, $width - 10, $height - 10, $primaryColor);
        imagerectangle($ticket, 11, 11, $width - 11, $height - 11, $primaryColor);
        imagerectangle($ticket, 12, 12, $width - 12, $height - 12, $secondaryColor);
        
        // Bande décorative en haut
        imagefilledrectangle($ticket, 12, 12, $width - 12, 80, $darkColor);
        
        // Ligne de séparation dorée
        imagefilledrectangle($ticket, 12, 78, $width - 12, 82, $primaryColor);
        
        // Charger la police (utiliser une police système)
        $fontPath = __DIR__ . '/../assets/fonts/';
        
        // Si pas de police custom, utiliser imagestring
        $useCustomFont = false;
        $fontFile = '';
        
        // Essayer de charger une police TrueType
        if (file_exists($fontPath . 'Arial.ttf')) {
            $fontFile = $fontPath . 'Arial.ttf';
            $useCustomFont = true;
        } elseif (file_exists('C:/Windows/Fonts/arial.ttf')) {
            $fontFile = 'C:/Windows/Fonts/arial.ttf';
            $useCustomFont = true;
        }
        
        // HEADER - Titre "EVENT TICKET"
        if ($useCustomFont) {
            imagettftext($ticket, 28, 0, 30, 55, $primaryColor, $fontFile, "EVENT TICKET");
        } else {
            imagestring($ticket, 5, 30, 30, "EVENT TICKET", $primaryColor);
        }
        
        // Ticket Number (top right)
        $ticketNumber = "#" . str_pad($data['id_ticket'], 6, '0', STR_PAD_LEFT);
        if ($useCustomFont) {
            imagettftext($ticket, 16, 0, $width - 180, 55, $grayColor, $fontFile, $ticketNumber);
        } else {
            imagestring($ticket, 4, $width - 150, 30, $ticketNumber, $grayColor);
        }
        
        // QR CODE (Left side)
        $qrCodePath = __DIR__ . '/../view/front/' . $data['qr_code_path'];
        if (file_exists($qrCodePath)) {
            $qrCode = imagecreatefrompng($qrCodePath);
            $qrSize = 200;
            imagecopyresampled($ticket, $qrCode, 40, 120, 0, 0, $qrSize, $qrSize, imagesx($qrCode), imagesy($qrCode));
            imagedestroy($qrCode);
            
            // QR Code label
            if ($useCustomFont) {
                imagettftext($ticket, 10, 0, 80, 335, $grayColor, $fontFile, "SCAN AT ENTRANCE");
            } else {
                imagestring($ticket, 2, 70, 330, "SCAN AT ENTRANCE", $grayColor);
            }
        }
        
        // EVENT DETAILS (Right side)
        $startX = 280;
        $startY = 110;
        $lineHeight = 35;
        
        // Event Title
        if ($useCustomFont) {
            imagettftext($ticket, 20, 0, $startX, $startY, $whiteColor, $fontFile, strtoupper(substr($data['titre'], 0, 30)));
            
            // Location
            imagettftext($ticket, 12, 0, $startX, $startY + $lineHeight, $grayColor, $fontFile, "LOCATION:");
            imagettftext($ticket, 14, 0, $startX + 100, $startY + $lineHeight, $whiteColor, $fontFile, substr($data['lieu'], 0, 25));
            
            // Date
            $dateDebut = new DateTime($data['date_debut']);
            $dateFin = new DateTime($data['date_fin']);
            imagettftext($ticket, 12, 0, $startX, $startY + $lineHeight * 2, $grayColor, $fontFile, "DATE:");
            imagettftext($ticket, 14, 0, $startX + 100, $startY + $lineHeight * 2, $whiteColor, $fontFile, $dateDebut->format('d/m/Y H:i'));
            
            // Participant
            imagettftext($ticket, 12, 0, $startX, $startY + $lineHeight * 3, $grayColor, $fontFile, "PARTICIPANT:");
            imagettftext($ticket, 14, 0, $startX + 130, $startY + $lineHeight * 3, $primaryColor, $fontFile, strtoupper(substr($data['nom_participant'], 0, 20)));
            
            // Status
            $status = strtoupper($data['status']);
            $statusColor = $data['status'] === 'active' ? imagecolorallocate($ticket, 46, 213, 115) : $grayColor;
            imagettftext($ticket, 12, 0, $startX, $startY + $lineHeight * 4, $grayColor, $fontFile, "STATUS:");
            imagettftext($ticket, 14, 0, $startX + 100, $startY + $lineHeight * 4, $statusColor, $fontFile, $status);
        } else {
            // Fallback to built-in fonts
            imagestring($ticket, 5, $startX, $startY, strtoupper(substr($data['titre'], 0, 30)), $whiteColor);
            
            imagestring($ticket, 3, $startX, $startY + 30, "LOCATION:", $grayColor);
            imagestring($ticket, 4, $startX + 90, $startY + 30, substr($data['lieu'], 0, 25), $whiteColor);
            
            $dateDebut = new DateTime($data['date_debut']);
            imagestring($ticket, 3, $startX, $startY + 60, "DATE:", $grayColor);
            imagestring($ticket, 4, $startX + 90, $startY + 60, $dateDebut->format('d/m/Y H:i'), $whiteColor);
            
            imagestring($ticket, 3, $startX, $startY + 90, "PARTICIPANT:", $grayColor);
            imagestring($ticket, 4, $startX + 120, $startY + 90, strtoupper(substr($data['nom_participant'], 0, 20)), $primaryColor);
            
            $status = strtoupper($data['status']);
            $statusColor = $data['status'] === 'active' ? imagecolorallocate($ticket, 46, 213, 115) : $grayColor;
            imagestring($ticket, 3, $startX, $startY + 120, "STATUS:", $grayColor);
            imagestring($ticket, 4, $startX + 90, $startY + 120, $status, $statusColor);
        }
        
        // Footer - Nine Tailed Fox branding
        if ($useCustomFont) {
            imagettftext($ticket, 10, 0, $width / 2 - 100, $height - 20, $grayColor, $fontFile, "NINE TAILED FOX - 2025");
        } else {
            imagestring($ticket, 2, $width / 2 - 80, $height - 25, "NINE TAILED FOX - 2025", $grayColor);
        }
        
        // Output image
        header('Content-Type: image/png');
        header('Content-Disposition: attachment; filename="ticket_' . $data['id_ticket'] . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $data['nom_participant']) . '.png"');
        imagepng($ticket);
        imagedestroy($ticket);
        
    } catch (Exception $e) {
        // Create error image
        $errorImg = imagecreatetruecolor(400, 200);
        $bgColor = imagecolorallocate($errorImg, 220, 53, 69);
        $textColor = imagecolorallocate($errorImg, 255, 255, 255);
        imagefill($errorImg, 0, 0, $bgColor);
        imagestring($errorImg, 4, 50, 90, "Error: " . $e->getMessage(), $textColor);
        header('Content-Type: image/png');
        imagepng($errorImg);
        imagedestroy($errorImg);
    }
}

// Check if ticket ID is provided
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    generateTicketImage((int)$_GET['id']);
} else {
    header('HTTP/1.1 400 Bad Request');
    echo "Invalid ticket ID";
}
