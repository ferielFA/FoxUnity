<?php
require_once __DIR__ . '/../model/db.php';
require_once __DIR__ . '/../model/SubscriberRepository.php';

class NewsletterController {
    private $subRepo;

    public function __construct() {
        global $pdo;
        $this->subRepo = new SubscriberRepository($pdo);
    }

    public function handleRequest() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'subscribe') {
            $email = $_POST['email'] ?? '';
            $cats = $_POST['categories'] ?? []; // Array of IDs

            if (empty($email)) {
                $this->redirectWithMsg('Email is required.', 'error');
            }
            if (empty($cats)) {
                $this->redirectWithMsg('Please select at least one category.', 'error');
            }

            [$ok, $msg] = $this->subRepo->add($email, $cats);
            $type = $ok ? 'success' : 'error';
            $this->redirectWithMsg($msg, $type);
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
