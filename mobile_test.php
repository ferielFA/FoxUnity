<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Connexion Mobile</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #1a1a1e;
            color: white;
            padding: 20px;
            text-align: center;
        }
        .success {
            background: linear-gradient(135deg, #2ed573, #1abc9c);
            padding: 30px;
            border-radius: 15px;
            margin: 20px auto;
            max-width: 400px;
        }
        h1 { font-size: 2rem; margin-bottom: 20px; }
        p { font-size: 1.2rem; line-height: 1.6; }
        .info { 
            background: rgba(245, 194, 66, 0.2);
            padding: 20px;
            border-radius: 10px;
            margin: 20px auto;
            max-width: 500px;
        }
        code {
            background: rgba(0,0,0,0.3);
            padding: 5px 10px;
            border-radius: 5px;
            display: inline-block;
            margin: 5px;
        }
    </style>
</head>
<body>
    <div class="success">
        <h1>✅ Connexion Réussie!</h1>
        <p>Votre téléphone peut accéder au serveur!</p>
    </div>
    
    <div class="info">
        <h2>Informations de connexion:</h2>
        <p><strong>IP du serveur:</strong><br><code><?php echo $_SERVER['SERVER_ADDR'] ?? 'N/A'; ?></code></p>
        <p><strong>Votre IP:</strong><br><code><?php echo $_SERVER['REMOTE_ADDR']; ?></code></p>
        <p><strong>URL configurée:</strong><br><code><?php require_once 'config/site_config.php'; echo SERVER_IP; ?></code></p>
        <p><strong>Port:</strong> <code><?php echo $_SERVER['SERVER_PORT']; ?></code></p>
        <p><strong>Heure du serveur:</strong><br><code><?php echo date('Y-m-d H:i:s'); ?></code></p>
    </div>
    
    <div class="info">
        <h2>Test des URLs:</h2>
        <p><a href="<?php echo BASE_URL; ?>/view/front/verify_ticket.php?token=TKT-TEST123" 
             style="color: #f5c242; text-decoration: none; font-weight: bold;">
             🔗 Tester verify_ticket.php
        </a></p>
        <p><a href="<?php echo BASE_URL; ?>/view/front/events.php" 
             style="color: #f5c242; text-decoration: none; font-weight: bold;">
             🔗 Tester events.php
        </a></p>
    </div>
    
    <div class="info">
        <h2>Instructions:</h2>
        <p>Sur votre téléphone, assurez-vous que:</p>
        <ol style="text-align: left; max-width: 400px; margin: 0 auto;">
            <li>Vous êtes connecté au même réseau WiFi</li>
            <li>Le pare-feu Windows autorise les connexions entrantes</li>
            <li>Vous utilisez l'URL: <code>http://192.168.100.83/pw/projet_web/mobile_test.php</code></li>
        </ol>
    </div>
</body>
</html>
