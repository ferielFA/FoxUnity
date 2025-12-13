<?php
require_once __DIR__ . '/../model/db.php';
require_once __DIR__ . '/../model/Subscriber.php';
require_once __DIR__ . '/../model/Categorie.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class NewsletterController {
    private $notifService;

    public function __construct() {
        global $pdo;
    }

    public function handleRequest() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            if ($action === 'subscribe') {
                $email = $_POST['email'] ?? '';
                $cats = $_POST['categories'] ?? [];

                if (empty($email)) {
                    $this->redirectWithMsg('Email is required.', 'error');
                }
                if (empty($cats)) {
                    $this->redirectWithMsg('Please select at least one category.', 'error');
                }

                [$ok, $msg] = Subscriber::add($email, $cats);

                if ($ok) {
                    $_SESSION['newsletter_email'] = $email;
                    Categorie::sendWelcomeEmail($email, $cats);
                }

                $type = $ok ? 'success' : 'error';
                $this->redirectWithMsg($msg, $type);
            } elseif ($action === 'unsubscribe') {
                $email = $_POST['email'] ?? ($_SESSION['newsletter_email'] ?? '');
                $catId = isset($_POST['category_id']) ? (int) $_POST['category_id'] : 0;
                if (empty($email)) {
                    $this->redirectWithMsg('Missing email context.', 'error');
                }
                if ($catId <= 0) {
                    $this->redirectWithMsg('Invalid category.', 'error');
                }
                $row = Subscriber::getByEmail($email);
                if (!$row) {
                    $this->redirectWithMsg('Subscription not found.', 'error');
                }
                $ids = array_filter(explode(',', $row['categories'] ?? ''), 'strlen');
                $ids = array_map('intval', $ids);
                $next = array_values(array_filter($ids, function($id) use ($catId){ return $id !== $catId; }));
                [$ok, $msg] = Subscriber::add($email, $next);
                $this->redirectWithMsg($ok ? 'Category removed.' : 'Failed to update preferences.', $ok ? 'success' : 'error');
            } elseif ($action === 'unsubscribe_all') {
                $email = $_POST['email'] ?? ($_SESSION['newsletter_email'] ?? '');
                if (empty($email)) {
                    $this->redirectWithMsg('Missing email context.', 'error');
                }
                [$ok, $msg] = Subscriber::add($email, []);
                $this->redirectWithMsg($ok ? 'All categories cleared.' : 'Failed to clear preferences.', $ok ? 'success' : 'error');
            }
        }
    }

    private function redirectWithMsg($msg, $type) {
        $prefix = $type === 'success' ? 'msg' : 'err';
        header("Location: /projet_web/view/front/news.php?$prefix=" . urlencode($msg) . "#newsletter");
        exit;
    }
}

// Instantiate and handle
$newsletterCtrl = new NewsletterController();
$newsletterCtrl->handleRequest();
?>
