<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/EmailSender.php';
require_once __DIR__ . '/../../controllers/reclamationcontroller.php';

$reclamationController = new ReclamationController();

// Fonction pour envoyer un email de test
function sendTestEmail($userEmail, $reclamationSubject, $responseMessage) {
    try {
        $to = $userEmail;
        $subject = "Réponse à votre réclamation : " . htmlspecialchars($reclamationSubject);
        
        $message = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    max-width: 600px;
                    margin: 0 auto;
                    padding: 20px;
                }
                .header {
                    background: linear-gradient(135deg, #ff7a00 0%, #ff9500 100%);
                    color: white;
                    padding: 30px;
                    text-align: center;
                    border-radius: 10px 10px 0 0;
                }
                .content {
                    background: #f9f9f9;
                    padding: 30px;
                    border: 1px solid #ddd;
                    border-top: none;
                }
                .response-box {
                    background: white;
                    padding: 20px;
                    border-left: 4px solid #ff7a00;
                    margin: 20px 0;
                    border-radius: 5px;
                }
                .footer {
                    background: #333;
                    color: white;
                    padding: 20px;
                    text-align: center;
                    border-radius: 0 0 10px 10px;
                    font-size: 12px;
                }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>Nouvelle réponse à votre réclamation</h1>
            </div>
            <div class='content'>
                <p>Bonjour,</p>
                <p>Nous avons le plaisir de vous informer qu'une réponse a été apportée à votre réclamation :</p>
                <p><strong>Sujet :</strong> " . htmlspecialchars($reclamationSubject) . "</p>
                
                <div class='response-box'>
                    <h3 style='color: #ff7a00; margin-top: 0;'>Réponse de l'équipe support :</h3>
                    <p>" . nl2br(htmlspecialchars($responseMessage)) . "</p>
                </div>
                
                <p>Vous pouvez consulter votre réclamation et cette réponse en vous connectant à votre compte.</p>
                
                <p>Si vous avez d'autres questions, n'hésitez pas à nous contacter.</p>
                
                <p>Cordialement,<br>
                <strong>L'équipe Support</strong></p>
            </div>
            <div class='footer'>
                <p>© 2025 Nine Tailed Fox. Tous droits réservés.</p>
                <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre directement.</p>
            </div>
        </body>
        </html>
        ";
        
        // Utiliser EmailSender pour envoyer l'email
        $emailSender = new EmailSender();
        
        // Vérifier si la configuration SMTP est remplie
        $configFile = __DIR__ . '/../../config/email_config.php';
        $config = [];
        if (file_exists($configFile)) {
            $config = require $configFile;
        }
        
        if (empty($config['smtp_username']) || empty($config['smtp_password']) || 
            $config['smtp_username'] === 'votre-email@gmail.com' || 
            $config['smtp_password'] === 'votre-mot-de-passe-app') {
            // Configuration non remplie
            error_log("⚠️ Configuration SMTP non remplie dans email_config.php");
            return ['success' => false, 'error' => 'Configuration SMTP non remplie'];
        }
        
        // Activer l'affichage des erreurs pour le diagnostic
        $oldErrorReporting = error_reporting(E_ALL);
        $oldDisplayErrors = ini_get('display_errors');
        ini_set('display_errors', 0); // Ne pas afficher les erreurs à l'écran, on les capture
        
        $mailSent = $emailSender->sendEmail($to, $subject, $message);
        
        // Restaurer les paramètres
        error_reporting($oldErrorReporting);
        ini_set('display_errors', $oldDisplayErrors);
        
        if ($mailSent) {
            return ['success' => true];
        } else {
            // Capturer la dernière erreur
            $lastError = error_get_last();
            $errorMsg = 'Erreur inconnue lors de l\'envoi';
            if ($lastError && $lastError['type'] === E_ERROR) {
                $errorMsg = $lastError['message'];
            }
            return ['success' => false, 'error' => $errorMsg];
        }
    } catch (Exception $e) {
        error_log("❌ Erreur: " . $e->getMessage());
        return false;
    }
}

$testResult = '';
$testEmail = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_email'])) {
    $testEmail = $_POST['test_email'] ?? '';
    $testSubject = $_POST['test_subject'] ?? 'Test de réclamation';
    $testMessage = $_POST['test_message'] ?? 'Ceci est un message de test pour vérifier que l\'envoi d\'email fonctionne correctement.';
    
        if (!empty($testEmail) && filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
            // Vérifier la configuration avant d'envoyer
            $configFile = __DIR__ . '/../../config/email_config.php';
            $config = [];
            if (file_exists($configFile)) {
                $config = require $configFile;
            }
            
            if (empty($config['smtp_username']) || empty($config['smtp_password']) || 
                $config['smtp_username'] === 'votre-email@gmail.com' || 
                $config['smtp_password'] === 'votre-mot-de-passe-app') {
                $testResult = '<div style="background: #ff9800; color: white; padding: 15px; border-radius: 5px; margin: 20px 0;">
                    ⚠️ <strong>Configuration SMTP requise !</strong><br><br>
                    Pour envoyer des emails, vous devez configurer vos identifiants Gmail dans le fichier :<br>
                    <code style="background: rgba(0,0,0,0.3); padding: 5px; border-radius: 3px;">config/email_config.php</code><br><br>
                    <strong>Étapes :</strong><br>
                    1. Allez sur <a href="https://myaccount.google.com/" target="_blank" style="color: #fff; text-decoration: underline;">myaccount.google.com</a><br>
                    2. Sécurité → Mots de passe des applications → Créer<br>
                    3. Copiez le mot de passe (16 caractères)<br>
                    4. Modifiez <code style="background: rgba(0,0,0,0.3); padding: 2px 5px; border-radius: 3px;">config/email_config.php</code> avec votre email et le mot de passe d\'application
                </div>';
            } else {
                $result = sendTestEmail($testEmail, $testSubject, $testMessage);
                
                if (is_array($result) && isset($result['success'])) {
                    if ($result['success']) {
                        $testResult = '<div style="background: #4caf50; color: white; padding: 15px; border-radius: 5px; margin: 20px 0;">
                            ✅ Email envoyé avec succès à : ' . htmlspecialchars($testEmail) . '<br>
                            Vérifiez votre boîte de réception (et les spams).
                        </div>';
                    } else {
                        $errorDetail = isset($result['error']) ? '<br><br><strong>Détails de l\'erreur :</strong><br><code style="background: rgba(0,0,0,0.3); padding: 5px; border-radius: 3px;">' . htmlspecialchars($result['error']) . '</code>' : '';
                        $testResult = '<div style="background: #f44336; color: white; padding: 15px; border-radius: 5px; margin: 20px 0;">
                            ❌ Erreur lors de l\'envoi de l\'email.' . $errorDetail . '<br><br>
                            <strong>Vérifiez :</strong><br>
                            1. Que vos identifiants SMTP sont corrects dans <code>config/email_config.php</code><br>
                            2. Que vous utilisez un <strong>mot de passe d\'application</strong> (pas votre mot de passe Gmail normal)<br>
                            3. Le format du mot de passe : il doit faire exactement 16 caractères (sans espaces ou avec espaces selon Google)<br>
                            4. Que la validation en 2 étapes est activée sur votre compte Google<br>
                            5. Les logs d\'erreur PHP (fichier error_log ou console PHP) pour plus de détails<br><br>
                            <strong>💡 Astuce :</strong> Le mot de passe d\'application Gmail fait 16 caractères. Si Google l\'affiche avec des espaces (ex: "abcd efgh ijkl mnop"), vous pouvez soit garder les espaces, soit les enlever. Essayez les deux formats.
                        </div>';
                    }
                } else {
                    // Compatibilité avec l'ancien format (booléen)
                    if ($result) {
                        $testResult = '<div style="background: #4caf50; color: white; padding: 15px; border-radius: 5px; margin: 20px 0;">
                            ✅ Email envoyé avec succès à : ' . htmlspecialchars($testEmail) . '<br>
                            Vérifiez votre boîte de réception (et les spams).
                        </div>';
                    } else {
                        $testResult = '<div style="background: #f44336; color: white; padding: 15px; border-radius: 5px; margin: 20px 0;">
                            ❌ Erreur lors de l\'envoi de l\'email.<br><br>
                            <strong>Vérifiez :</strong><br>
                            1. Que vos identifiants SMTP sont corrects dans <code>config/email_config.php</code><br>
                            2. Que vous utilisez un <strong>mot de passe d\'application</strong> (pas votre mot de passe Gmail normal)<br>
                            3. Le format du mot de passe : il doit faire exactement 16 caractères (sans espaces ou avec espaces selon Google)<br>
                            4. Que la validation en 2 étapes est activée sur votre compte Google<br>
                            5. Les logs d\'erreur PHP (fichier error_log ou console PHP) pour plus de détails<br><br>
                            <strong>💡 Astuce :</strong> Le mot de passe d\'application Gmail fait 16 caractères. Si Google l\'affiche avec des espaces (ex: "abcd efgh ijkl mnop"), vous pouvez soit garder les espaces, soit les enlever. Essayez les deux formats.
                        </div>';
                    }
                }
            }
        } else {
            $testResult = '<div style="background: #ff9800; color: white; padding: 15px; border-radius: 5px; margin: 20px 0;">
                ⚠️ Adresse email invalide.
            </div>';
        }
}

// Récupérer les dernières réclamations pour voir les emails
$allReclamations = $reclamationController->getAllReclamations(null, null, null);
$recentReclamations = array_slice($allReclamations, 0, 10);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Email - Support</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            color: #fff;
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: rgba(20, 20, 20, 0.9);
            border-radius: 15px;
            padding: 30px;
            border: 2px solid #ff7a00;
        }
        h1 {
            color: #ff7a00;
            margin-bottom: 30px;
            text-align: center;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #ff7a00;
            font-weight: 600;
        }
        input[type="email"],
        input[type="text"],
        textarea {
            width: 100%;
            padding: 12px;
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid #ff7a00;
            border-radius: 8px;
            color: #fff;
            font-size: 14px;
        }
        input[type="email"]:focus,
        input[type="text"]:focus,
        textarea:focus {
            outline: none;
            border-color: #ff9500;
            background: rgba(255, 255, 255, 0.15);
        }
        textarea {
            min-height: 150px;
            resize: vertical;
        }
        button {
            background: linear-gradient(135deg, #ff7a00, #ff9500);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(255, 122, 0, 0.4);
        }
        .recent-reclamations {
            margin-top: 40px;
            padding-top: 30px;
            border-top: 2px solid #ff7a00;
        }
        .reclamation-item {
            background: rgba(255, 255, 255, 0.05);
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 8px;
            border-left: 4px solid #ff7a00;
        }
        .reclamation-item strong {
            color: #ff7a00;
        }
        .info-box {
            background: rgba(33, 150, 243, 0.2);
            border: 1px solid #2196F3;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📧 Test d'Envoi d'Email</h1>
        
        <div class="info-box">
            <strong>ℹ️ Information :</strong><br>
            Cette page permet de tester l'envoi d'email. L'email sera envoyé à l'adresse que vous spécifiez ci-dessous.
            <br><br>
            <strong>Note :</strong> Pour que l'envoi fonctionne, PHP doit être configuré pour envoyer des emails (serveur SMTP ou sendmail).
        </div>
        
        <?php echo $testResult; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="test_email">📬 Adresse Email de Test :</label>
                <input type="email" id="test_email" name="test_email" 
                       value="<?php echo htmlspecialchars($testEmail); ?>" 
                       placeholder="votre-email@example.com" required>
            </div>
            
            <div class="form-group">
                <label for="test_subject">📝 Sujet de Test :</label>
                <input type="text" id="test_subject" name="test_subject" 
                       value="Test de réclamation" 
                       placeholder="Sujet de la réclamation">
            </div>
            
            <div class="form-group">
                <label for="test_message">💬 Message de Test :</label>
                <textarea id="test_message" name="test_message" 
                          placeholder="Ceci est un message de test pour vérifier que l'envoi d'email fonctionne correctement.">Ceci est un message de test pour vérifier que l'envoi d'email fonctionne correctement.</textarea>
            </div>
            
            <button type="submit" name="test_email_submit">🚀 Envoyer l'Email de Test</button>
        </form>
        
        <div class="recent-reclamations">
            <h2 style="color: #ff7a00; margin-bottom: 20px;">📋 Emails des Récentes Réclamations</h2>
            <p style="margin-bottom: 15px; color: #aaa;">Vous pouvez utiliser ces emails pour tester :</p>
            
            <?php if (empty($recentReclamations)): ?>
                <p style="color: #aaa;">Aucune réclamation trouvée.</p>
            <?php else: ?>
                <?php foreach ($recentReclamations as $reclamation): ?>
                    <div class="reclamation-item">
                        <strong>Email :</strong> <?php echo htmlspecialchars($reclamation['email'] ?? 'N/A'); ?><br>
                        <strong>Sujet :</strong> <?php echo htmlspecialchars($reclamation['sujet'] ?? 'N/A'); ?><br>
                        <strong>Date :</strong> <?php echo htmlspecialchars($reclamation['date_creation'] ?? 'N/A'); ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #444; text-align: center;">
            <a href="reclamback.php" style="color: #ff7a00; text-decoration: none;">← Retour au Dashboard</a>
        </div>
    </div>
</body>
</html>

