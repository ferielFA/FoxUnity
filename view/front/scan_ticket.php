<?php
require_once __DIR__ . '/../../controller/TicketController.php';

$ticketController = new TicketController();
$ticketData = null;
$error = null;

// Handle scanned QR code
if (isset($_GET['token'])) {
    $scannedData = $_GET['token'];
    
    // Extract token from QR code format: "TICKET:TKT-XXX|PARTICIPATION:X|EVENT:X"
    $token = $scannedData;
    if (strpos($scannedData, 'TICKET:') !== false) {
        // Parse the QR code format
        preg_match('/TICKET:([^|]+)/', $scannedData, $matches);
        if (isset($matches[1])) {
            $token = $matches[1];
        }
    }
    
    $ticket = $ticketController->getTicketByToken($token);
    
    if ($ticket) {
        // Get full ticket details
        $ticketData = $ticketController->getTicketById($ticket->getIdTicket());
        
        if ($ticketData) {
            // Determine real-time status based on event end date
            $currentDateTime = new DateTime();
            $eventEndDateTime = new DateTime($ticketData['event_end']);
            
            // If event has ended and ticket is still 'active', mark it as 'used'
            if ($currentDateTime > $eventEndDateTime && $ticketData['status'] === 'active') {
                $ticketData['status'] = 'used';
                $ticketData['is_event_ended'] = true;
            } else {
                $ticketData['is_event_ended'] = ($currentDateTime > $eventEndDateTime);
            }
        }
    } else {
        $error = "Invalid or expired ticket";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scan Ticket - FoxUnity</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&family=Poppins:wght@300;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://unpkg.com/html5-qrcode"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #16161a 0%, #1b1b20 100%);
            color: #fff;
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }
        
        /* Animated background */
        .stars {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            background-image: 
                radial-gradient(2px 2px at 20px 30px, #eee, rgba(0,0,0,0)),
                radial-gradient(2px 2px at 60px 70px, #fff, rgba(0,0,0,0)),
                radial-gradient(1px 1px at 50px 50px, #ddd, rgba(0,0,0,0)),
                radial-gradient(1px 1px at 130px 80px, #fff, rgba(0,0,0,0)),
                radial-gradient(2px 2px at 90px 10px, #eee, rgba(0,0,0,0));
            background-repeat: repeat;
            background-size: 200px 200px;
            opacity: 0.3;
            animation: twinkle 3s ease-in-out infinite;
        }
        
        @keyframes twinkle {
            0%, 100% { opacity: 0.3; }
            50% { opacity: 0.5; }
        }
        
        .navbar {
            background: rgba(22, 22, 26, 0.95);
            backdrop-filter: blur(10px);
            padding: 15px 0;
            border-bottom: 2px solid rgba(245, 194, 66, 0.3);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .navbar .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo img {
            height: 50px;
        }
        
        .nav-links {
            list-style: none;
            display: flex;
            gap: 30px;
        }
        
        .nav-links a {
            color: #cfd3d8;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            padding: 8px 16px;
            border-radius: 6px;
        }
        
        .nav-links a:hover,
        .nav-links a.active {
            color: #f5c242;
            background: rgba(245, 194, 66, 0.1);
        }
        
        .scanner-container {
            max-width: 700px;
            margin: 60px auto;
            padding: 40px;
            position: relative;
            z-index: 10;
        }
        
        .scanner-card {
            background: linear-gradient(135deg, rgba(22, 22, 26, 0.98), rgba(27, 27, 32, 0.98));
            border: 2px solid rgba(245, 194, 66, 0.3);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        }
        
        .scanner-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .scanner-header h1 {
            font-family: 'Orbitron', sans-serif;
            color: #f5c242;
            font-size: 2rem;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }
        
        .scanner-header h1 i {
            font-size: 2.5rem;
        }
        
        #qr-reader {
            border-radius: 12px;
            overflow: hidden;
            margin: 20px 0;
            border: 2px solid rgba(245, 194, 66, 0.2);
        }
        
        .btn-scanner {
            background: linear-gradient(135deg, #f5c242, #f39c12);
            color: #000;
            border: none;
            padding: 15px 40px;
            border-radius: 10px;
            font-weight: bold;
            font-size: 1rem;
            cursor: pointer;
            width: 100%;
            margin: 20px 0;
            font-family: 'Orbitron', sans-serif;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-scanner:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(245, 194, 66, 0.5);
        }
        
        .manual-entry {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .manual-entry p {
            text-align: center;
            color: #969696;
            margin-bottom: 20px;
        }
        
        .manual-entry input {
            width: 100%;
            padding: 15px;
            border-radius: 10px;
            border: 2px solid rgba(245, 194, 66, 0.3);
            background: rgba(255, 255, 255, 0.05);
            color: #fff;
            font-size: 1rem;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }
        
        .manual-entry input:focus {
            outline: none;
            border-color: #f5c242;
            background: rgba(245, 194, 66, 0.1);
        }
        
        .ticket-result {
            margin-top: 30px;
            padding: 30px;
            border-radius: 16px;
            animation: slideIn 0.5s ease;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .ticket-valid {
            background: linear-gradient(135deg, rgba(46, 213, 115, 0.15), rgba(46, 213, 115, 0.05));
            border: 2px solid #2ed573;
        }
        
        .ticket-invalid {
            background: linear-gradient(135deg, rgba(255, 107, 107, 0.15), rgba(255, 107, 107, 0.05));
            border: 2px solid #ff6b6b;
        }
        
        .result-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .result-icon {
            font-size: 5rem;
            margin-bottom: 20px;
        }
        
        .result-header h2 {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.8rem;
            margin-bottom: 10px;
        }
        
        .ticket-details {
            display: grid;
            gap: 15px;
        }
        
        .detail-box {
            background: rgba(0, 0, 0, 0.2);
            padding: 20px;
            border-radius: 12px;
            border-left: 4px solid #f5c242;
        }
        
        .detail-box label {
            color: #969696;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            display: block;
            margin-bottom: 8px;
        }
        
        .detail-box .value {
            color: #fff;
            font-size: 1.1rem;
            font-weight: 600;
        }
        
        .detail-box .value.large {
            font-size: 1.3rem;
            color: #f5c242;
        }
        
        .status-badge {
            display: inline-block;
            padding: 10px 25px;
            border-radius: 25px;
            font-weight: bold;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .status-active {
            background: #2ed573;
            color: #000;
        }
        
        .status-used {
            background: #969696;
            color: #fff;
        }
        
        .status-cancelled {
            background: #ff6b6b;
            color: #fff;
        }
        
        .btn-back-scan {
            display: inline-block;
            text-decoration: none;
            padding: 12px 30px;
            background: rgba(245, 194, 66, 0.1);
            border: 2px solid #f5c242;
            color: #f5c242;
            border-radius: 10px;
            font-weight: 600;
            text-align: center;
            transition: all 0.3s ease;
            margin-top: 20px;
        }
        
        .btn-back-scan:hover {
            background: rgba(245, 194, 66, 0.2);
            transform: translateX(-5px);
        }
        
        @media (max-width: 768px) {
            .scanner-container {
                padding: 20px;
                margin: 30px auto;
            }
            
            .scanner-card {
                padding: 25px;
            }
            
            .scanner-header h1 {
                font-size: 1.5rem;
            }
            
            .nav-links {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="stars"></div>
    
    <nav class="navbar">
        <div class="container">
            <div class="logo">
                <img src="../images/Nine__1_-removebg-preview.png" alt="FoxUnity Logo">
            </div>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="events.php">Events</a></li>
                <li><a href="my_tickets.php">My Tickets</a></li>
                <li><a href="scan_ticket.php" class="active">Scan Ticket</a></li>
            </ul>
        </div>
    </nav>

    <div class="scanner-container">
        <div class="scanner-card">
            <?php if (!isset($_GET['token'])): ?>
                <div class="scanner-header">
                    <h1><i class="fas fa-qrcode"></i> Scan Ticket QR Code</h1>
                    <p style="color: #969696; margin-top: 10px;">Verify event tickets instantly</p>
                </div>
                
                <div id="qr-reader" style="display: none;"></div>
                <button id="startScanBtn" class="btn-scanner">
                    <i class="fas fa-camera"></i> Start Camera Scanner
                </button>
                
                <div class="manual-entry">
                    <p>Or enter ticket token manually:</p>
                    <form method="GET">
                        <input type="text" name="token" placeholder="Enter ticket token (e.g., TKT-XXXX)" required>
                        <button type="submit" class="btn-scanner">
                            <i class="fas fa-search"></i> Verify Ticket
                        </button>
                    </form>
                </div>
            <?php endif; ?>

        <?php if ($error): ?>
            <div class="ticket-result ticket-invalid">
                <div class="result-header">
                    <div class="result-icon" style="color: #ff6b6b;">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <h2 style="color: #ff6b6b;">Invalid Ticket</h2>
                    <p style="color: #cfd3d8; margin-top: 10px;"><?= htmlspecialchars($error) ?></p>
                </div>
                <div style="text-align: center;">
                    <a href="scan_ticket.php" class="btn-scanner">
                        <i class="fas fa-redo"></i> Scan Another Ticket
                    </a>
                </div>
            </div>
        <?php elseif ($ticketData): ?>
            <div class="ticket-result ticket-valid">
                <div class="result-header">
                    <div class="result-icon" style="color: #2ed573;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h2 style="color: #2ed573;">Valid Ticket</h2>
                </div>
                
                <div class="ticket-details">
                    <div class="detail-box">
                        <label>Ticket Number</label>
                        <div class="value large">#<?= str_pad($ticketData['id_ticket'], 6, '0', STR_PAD_LEFT) ?></div>
                    </div>
                    
                    <div class="detail-box">
                        <label>Event</label>
                        <div class="value"><?= htmlspecialchars($ticketData['event_title']) ?></div>
                        <div style="margin-top: 8px;">
                            <?php if ($ticketData['is_event_ended']): ?>
                                <span style="display: inline-block; padding: 4px 12px; background: rgba(255, 71, 87, 0.2); color: #ff4757; border-radius: 20px; font-size: 0.85rem; font-weight: 600;">
                                    Event Ended
                                </span>
                            <?php else: ?>
                                <span style="display: inline-block; padding: 4px 12px; background: rgba(46, 213, 115, 0.2); color: #2ed573; border-radius: 20px; font-size: 0.85rem; font-weight: 600;">
                                    Event Active
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="detail-box">
                        <label>Participant</label>
                        <div class="value"><?= htmlspecialchars($ticketData['participant_name'] ?? 'N/A') ?></div>
                        <div style="color: #969696; font-size: 0.9rem; margin-top: 5px;">
                            <?= htmlspecialchars($ticketData['participant_email'] ?? 'N/A') ?>
                        </div>
                    </div>
                    
                    <div class="detail-box">
                        <label>Event Date & Time</label>
                        <div class="value"><?= date('F d, Y - H:i', strtotime($ticketData['event_start'])) ?></div>
                        <div style="color: #969696; font-size: 0.85rem; margin-top: 5px;">
                            Ends: <?= date('F d, Y - H:i', strtotime($ticketData['event_end'])) ?>
                        </div>
                    </div>
                    
                    <div class="detail-box">
                        <label>Location</label>
                        <div class="value"><?= htmlspecialchars($ticketData['event_location']) ?></div>
                    </div>
                    
                    <div class="detail-box" style="text-align: center; border-left: none;">
                        <label>Status</label>
                        <div style="margin-top: 10px;">
                            <span class="status-badge status-<?= $ticketData['status'] ?>">
                                <?= strtoupper($ticketData['status']) ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <div style="text-align: center;">
                    <a href="scan_ticket.php" class="btn-scanner" style="margin-top: 30px;">
                        <i class="fas fa-qrcode"></i> Scan Another Ticket
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        let html5QrCode;
        let isScanning = false;
        
        document.getElementById('startScanBtn').addEventListener('click', function() {
            const qrReader = document.getElementById('qr-reader');
            const btn = this;
            
            if (!isScanning) {
                qrReader.style.display = 'block';
                btn.innerHTML = '<i class="fas fa-stop"></i> Stop Scanner';
                
                html5QrCode = new Html5Qrcode("qr-reader");
                
                // Try to start with any available camera
                html5QrCode.start(
                    { facingMode: "user" }, // Use front camera (works for both PC and phone)
                    {
                        fps: 10,
                        qrbox: { width: 250, height: 250 },
                        aspectRatio: 1.0
                    },
                    (decodedText, decodedResult) => {
                        // QR code successfully scanned
                        console.log("Scanned:", decodedText);
                        html5QrCode.stop().then(() => {
                            window.location.href = 'scan_ticket.php?token=' + encodeURIComponent(decodedText);
                        }).catch(err => {
                            console.error("Error stopping camera:", err);
                            window.location.href = 'scan_ticket.php?token=' + encodeURIComponent(decodedText);
                        });
                    },
                    (errorMessage) => {
                        // Scanning errors (can be ignored - these happen frequently during scanning)
                    }
                ).catch((err) => {
                    console.error("Camera error:", err);
                    // If user facingMode fails, try environment mode
                    html5QrCode.start(
                        { facingMode: "environment" },
                        {
                            fps: 10,
                            qrbox: { width: 250, height: 250 }
                        },
                        (decodedText, decodedResult) => {
                            console.log("Scanned:", decodedText);
                            html5QrCode.stop().then(() => {
                                window.location.href = 'scan_ticket.php?token=' + encodeURIComponent(decodedText);
                            }).catch(err => {
                                console.error("Error stopping camera:", err);
                                window.location.href = 'scan_ticket.php?token=' + encodeURIComponent(decodedText);
                            });
                        },
                        (errorMessage) => {}
                    ).catch((err2) => {
                        console.error("Both camera modes failed:", err2);
                        alert('Unable to access camera. Please:\n1. Allow camera permissions in your browser\n2. Make sure no other app is using the camera\n3. Try refreshing the page');
                        qrReader.style.display = 'none';
                        btn.innerHTML = '<i class="fas fa-camera"></i> Start Camera Scanner';
                        isScanning = false;
                    });
                });
                
                isScanning = true;
            } else {
                if (html5QrCode && html5QrCode.isScanning) {
                    html5QrCode.stop().then(() => {
                        qrReader.style.display = 'none';
                        btn.innerHTML = '<i class="fas fa-camera"></i> Start Camera Scanner';
                        isScanning = false;
                    }).catch(err => {
                        console.error("Error stopping scanner:", err);
                        qrReader.style.display = 'none';
                        btn.innerHTML = '<i class="fas fa-camera"></i> Start Camera Scanner';
                        isScanning = false;
                    });
                }
            }
        });
    </script>
</body>
</html>
