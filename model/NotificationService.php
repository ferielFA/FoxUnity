<?php
require_once __DIR__ . '/SubscriberRepository.php';

class NotificationService {
    private $pdo;
    private $subRepo;
    private $logFile;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->subRepo = new SubscriberRepository($pdo);
        $this->logFile = __DIR__ . '/../view/back/data/email_logs.txt';
        @mkdir(dirname($this->logFile), 0755, true);
    }

    public function notifySubscribersForArticle(array $article) {
        // If already notified, skip
        if (!empty($article['notifications_sent'])) {
            return 0;
        }

        $catId = $article['idCategorie'];
        $emails = $this->subRepo->findInterestedEmails($catId);

        if (empty($emails)) {
            $this->markAsSent($article['idArticle']);
            return 0;
        }

        $count = 0;
        foreach ($emails as $email) {
            $this->simulateEmail($email, $article);
            $count++;
        }

        $this->markAsSent($article['idArticle']);
        return $count;
    }

    private function simulateEmail($to, $article) {
        $date = date('Y-m-d H:i:s');
        $subject = "🔥 Hot News: " . $article['title'];
        
        // Prepare HTML Content
        // Link to localhost is tricky, we try best guess or relative.
        $link = "http://" . $_SERVER['HTTP_HOST'] . "/projet_web/view/front/news_article.php?id=" . $article['slug'];
        
        $message = "
        <html>
        <head>
          <title>$subject</title>
        </head>
        <body style='background:#111;color:#ddd;font-family:sans-serif;padding:20px;'>
          <h2 style='color:#ff9900;'>New Hot Article Released!</h2>
          <h1 style='color:#fff;'>{$article['title']}</h1>
          <p>{$article['excerpt']}</p>
          <p><a href='$link' style='background:#ff9900;color:#000;padding:10px 20px;text-decoration:none;border-radius:5px;font-weight:bold;'>Read Now</a></p>
          <hr style='border:1px solid #333;'>
          <p style='font-size:0.8rem;color:#888;'>You are receiving this because you subscribed to FoxUnity News.</p>
        </body>
        </html>
        ";

        // Headers
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: FoxUnity <no-reply@foxunity.com>" . "\r\n";

        // Attempt Send
        $status = "FAILED";
        if (mail($to, $subject, $message, $headers)) {
            $status = "SENT";
        } else {
             // Fallback/Warning for localhost without SMTP
             $status = "FAILED (Check PHP mail setup)";
        }

        // Always log for admin visibility
        $logEntry = "[$date] $status TO: $to | SUB: $subject\n";
        file_put_contents($this->logFile, $logEntry, FILE_APPEND);
    }

    private function markAsSent($articleId) {
        $stmt = $this->pdo->prepare("UPDATE article SET notifications_sent = 1 WHERE idArticle = ?");
        $stmt->execute([$articleId]);
    }

    public function getLogs() {
        if (file_exists($this->logFile)) {
            return file_get_contents($this->logFile);
        }
        return "No logs yet.";
    }
}
?>
