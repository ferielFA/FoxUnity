<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Code Setup Guide - FoxUnity</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
            padding: 20px;
            min-height: 100vh;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
        }

        .logo {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #f5c242, #f39c12);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
        }

        .subtitle {
            color: #969696;
            font-size: 1.2rem;
        }

        .card {
            background: rgba(27, 27, 32, 0.8);
            border: 2px solid rgba(245, 194, 66, 0.3);
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 25px;
        }

        .card-title {
            font-size: 1.5rem;
            color: #f5c242;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-box {
            background: rgba(94, 196, 255, 0.1);
            border-left: 4px solid #5ec4ff;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .info-box strong {
            color: #5ec4ff;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
        }

        .warning-box {
            background: rgba(245, 158, 11, 0.1);
            border-left: 4px solid #f59e0b;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .warning-box strong {
            color: #f59e0b;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
        }

        .success-box {
            background: rgba(16, 185, 129, 0.1);
            border-left: 4px solid #10b981;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .success-box strong {
            color: #10b981;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
        }

        .steps {
            list-style: none;
            counter-reset: step-counter;
        }

        .steps li {
            counter-increment: step-counter;
            margin-bottom: 20px;
            padding-left: 50px;
            position: relative;
            line-height: 1.8;
        }

        .steps li::before {
            content: counter(step-counter);
            position: absolute;
            left: 0;
            top: 0;
            width: 35px;
            height: 35px;
            background: linear-gradient(135deg, #f5c242, #f39c12);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #000;
        }

        .code-block {
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(245, 194, 66, 0.3);
            border-radius: 8px;
            padding: 15px;
            font-family: 'Courier New', monospace;
            color: #f5c242;
            margin: 10px 0;
            overflow-x: auto;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 24px;
            background: linear-gradient(135deg, #f5c242, #f39c12);
            color: #000;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 700;
            transition: all 0.3s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(245, 194, 66, 0.4);
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .grid-item {
            background: rgba(0, 0, 0, 0.3);
            padding: 20px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .grid-item h3 {
            color: #f5c242;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }

            .card {
                padding: 20px;
            }

            .steps li {
                padding-left: 45px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo"><i class="fas fa-qrcode"></i> QR Code Setup Guide</div>
            <div class="subtitle">Make Your Tickets Scannable from Any Phone</div>
        </div>

        <?php
        require_once __DIR__ . '/../config/site_config.php';
        
        // Detect if accessed from phone
        $isMobile = preg_match("/(android|iphone|ipad|mobile)/i", $_SERVER['HTTP_USER_AGENT'] ?? '');
        ?>

        <div class="card">
            <div class="card-title"><i class="fas fa-info-circle"></i> Current Configuration</div>
            
            <div class="success-box">
                <strong><i class="fas fa-check-circle"></i> QR Code URL</strong>
                <div class="code-block"><?= VERIFY_TICKET_URL ?></div>
            </div>

            <div class="info-box">
                <strong><i class="fas fa-server"></i> Server IP Address</strong>
                <p>Your computer's local IP: <code style="color: #5ec4ff; font-weight: 700;"><?= SERVER_IP ?></code></p>
                <p style="margin-top: 10px; font-size: 0.9rem; color: #969696;">
                    This IP allows phones on the same WiFi network to access your tickets.
                </p>
            </div>
        </div>

        <div class="card">
            <div class="card-title"><i class="fas fa-mobile-alt"></i> How to Scan QR Codes from Your Phone</div>
            
            <ul class="steps">
                <li>
                    <strong>Connect to the Same WiFi Network</strong><br>
                    Make sure your phone and computer are connected to the same WiFi network.
                </li>
                <li>
                    <strong>Open Your Phone's Camera or QR Scanner</strong><br>
                    Most phones have built-in QR code scanning in the camera app. You can also download any QR scanner app.
                </li>
                <li>
                    <strong>Point at the QR Code</strong><br>
                    Hold your phone's camera over the QR code on the ticket.
                </li>
                <li>
                    <strong>Tap the Notification</strong><br>
                    A notification will appear with a link. Tap it to open the verification page.
                </li>
                <li>
                    <strong>View Ticket Details</strong><br>
                    The page will show if the ticket is valid, along with event and participant information.
                </li>
            </ul>
        </div>

        <div class="card">
            <div class="card-title"><i class="fas fa-tools"></i> Troubleshooting</div>
            
            <div class="warning-box">
                <strong><i class="fas fa-exclamation-triangle"></i> If QR Code Doesn't Work</strong>
                <ol style="margin-left: 20px; margin-top: 10px; line-height: 1.8;">
                    <li>Check that both devices are on the same WiFi</li>
                    <li>Make sure XAMPP Apache server is running</li>
                    <li>Try accessing this URL from your phone's browser:
                        <div class="code-block" style="margin-top: 10px;"><?= BASE_URL ?>/view/front/verify_ticket.php</div>
                    </li>
                    <li>If your IP changed, regenerate QR codes by running:
                        <div class="code-block" style="margin-top: 10px;">
                            php c:\xampp\htdocs\pw\projet_web\regenerate_qrcodes.php
                        </div>
                    </li>
                </ol>
            </div>

            <div class="grid">
                <div class="grid-item">
                    <h3><i class="fas fa-wifi"></i> WiFi Issue?</h3>
                    <p style="line-height: 1.6; color: #ccc;">
                        Run <code>ipconfig</code> in Command Prompt to find your current IP address and update it in <code>config/site_config.php</code>
                    </p>
                </div>
                <div class="grid-item">
                    <h3><i class="fas fa-server"></i> Server Not Running?</h3>
                    <p style="line-height: 1.6; color: #ccc;">
                        Open XAMPP Control Panel and make sure Apache is started (green indicator).
                    </p>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-title"><i class="fas fa-flask"></i> Test the System</div>
            
            <div style="text-align: center; padding: 20px;">
                <a href="<?= VERIFY_TICKET_URL ?>" class="btn" target="_blank">
                    <i class="fas fa-external-link-alt"></i> Open Verification Page
                </a>
                <p style="margin-top: 15px; color: #969696;">
                    This link should work from both your computer and your phone (if on same WiFi).
                </p>
            </div>

            <?php if ($isMobile): ?>
                <div class="success-box">
                    <strong><i class="fas fa-mobile-alt"></i> Mobile Device Detected!</strong>
                    <p>Great! You're already viewing this from a mobile device. The QR code system should work perfectly for you.</p>
                </div>
            <?php endif; ?>
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <a href="../back/eventsb.php" class="btn" style="background: rgba(94, 196, 255, 0.2); color: #5ec4ff; border: 2px solid #5ec4ff;">
                <i class="fas fa-arrow-left"></i> Back to Events Dashboard
            </a>
        </div>
    </div>
</body>
</html>
