<?php
/**
 * Email Configuration for FoxUnity
 * Using Gmail SMTP with PHPMailer
 */

class EmailConfig {
    // ✅ REMPLACE PAR TON EMAIL GMAIL
    const SMTP_HOST = 'smtp.gmail.com';
    const SMTP_PORT = 587;
    const SMTP_USERNAME = 'dhrifmeriem1231230@gmail.com';  // ← CHANGE ICI
    const SMTP_PASSWORD = 'azqh ijgd ujzk mynx';   // ← CHANGE ICI (App Password)
    const SMTP_FROM_EMAIL = 'dhrifmeriem1231230@gmail.com'; // ← CHANGE ICI
    const SMTP_FROM_NAME = 'foxunity';
    
    // Site URL
    const SITE_URL = 'http://localhost/pw/projet_web';
    
    /**
     * Get PHPMailer instance configured with Gmail SMTP
     */
    public static function getMailer() {
        require_once __DIR__ . '/../vendor/autoload.php';
        
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = self::SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = self::SMTP_USERNAME;
            $mail->Password = self::SMTP_PASSWORD;
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = self::SMTP_PORT;
            $mail->CharSet = 'UTF-8';
            
            // From
            $mail->setFrom(self::SMTP_FROM_EMAIL, self::SMTP_FROM_NAME);
            
            return $mail;
        } catch (Exception $e) {
            error_log("Email configuration error: " . $e->getMessage());
            return null;
        }
    }
}
?>