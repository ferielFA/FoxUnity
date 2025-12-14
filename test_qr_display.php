<!DOCTYPE html>
<html>
<head>
    <title>Test QR Display</title>
    <style>
        body { background: #1a1a1e; color: white; padding: 40px; font-family: Arial; }
        img { border: 2px solid #f5c242; padding: 10px; background: white; margin: 20px; }
        .test-section { margin: 30px 0; padding: 20px; background: rgba(255,255,255,0.05); border-radius: 10px; }
    </style>
</head>
<body>
    <h1>Test QR Code Display</h1>
    
    <div class="test-section">
        <h2>Test 1: Direct path from current location</h2>
        <p>Path: view/front/qrcodes/ticket_8_8_1765637521.png</p>
        <img src="view/front/qrcodes/ticket_8_8_1765637521.png" alt="QR Test 1">
    </div>
    
    <div class="test-section">
        <h2>Test 2: From my_tickets.php location (view/front/)</h2>
        <p>Path: qrcodes/ticket_8_8_1765637521.png</p>
        <img src="qrcodes/ticket_8_8_1765637521.png" alt="QR Test 2">
    </div>
    
    <div class="test-section">
        <h2>Test 3: Absolute path</h2>
        <p>Path: /pw/projet_web/view/front/qrcodes/ticket_8_8_1765637521.png</p>
        <img src="/pw/projet_web/view/front/qrcodes/ticket_8_8_1765637521.png" alt="QR Test 3">
    </div>
    
    <div class="test-section">
        <h2>Test 4: List all QR codes</h2>
        <?php
        $qrDir = __DIR__ . '/view/front/qrcodes/';
        if (is_dir($qrDir)) {
            $files = scandir($qrDir);
            echo "<ul>";
            foreach ($files as $file) {
                if ($file != '.' && $file != '..' && pathinfo($file, PATHINFO_EXTENSION) == 'png') {
                    echo "<li>" . htmlspecialchars($file);
                    echo "<br><img src='view/front/qrcodes/" . $file . "' style='max-width:150px;'>";
                    echo "</li>";
                }
            }
            echo "</ul>";
        } else {
            echo "<p style='color:red;'>Directory not found: $qrDir</p>";
        }
        ?>
    </div>
</body>
</html>
