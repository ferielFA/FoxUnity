<?php
require_once __DIR__ . '/../../controller/EmailConfig.php';

$mail = EmailConfig::getMailer();

if (!$mail) {
    die("❌ Impossible d'initialiser PHPMailer");
}

try {
    $mail->addAddress('dhrifmeriem1231230@gmail.com');
    $mail->Subject = "Test Email FoxUnity";
    $mail->isHTML(true);
    $mail->Body = "<h1>Test réussi !</h1><p>L'email fonctionne correctement.</p>";
    
    $mail->send();
    echo "✅ Email envoyé avec succès !";
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage();
}
?>