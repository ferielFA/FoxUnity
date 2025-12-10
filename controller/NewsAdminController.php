<?php
// Controller for admin news actions (class-based, MVC)
require_once __DIR__ . '/../model/ArticleRepository.php';
require_once __DIR__ . '/../model/CategoryRepository.php';
require_once __DIR__ . '/../model/CommentRepository.php';
require_once __DIR__ . '/../model/helpers.php';
require_once __DIR__ . '/../model/NotificationService.php';

class NewsAdminController
{
    private array $messages = [];
    private array $errors = [];
    private string $action;
    private string $id;
    private array $categories = [];
    private array $articles = [];
    private ?array $editing = null;

    private ArticleRepository $articleRepository;
    private CategoryRepository $categoryRepository;
    private CommentRepository $commentRepository;
    private NotificationService $notificationService;

    public function __construct()
    {
        global $pdo;
        $this->articleRepository  = new ArticleRepository($pdo);
        $this->categoryRepository = new CategoryRepository($pdo);
        $this->commentRepository  = new CommentRepository($pdo);
        $this->notificationService = new NotificationService($pdo);

        $this->action     = $_GET['action'] ?? '';
        $this->id         = $_GET['id'] ?? '';
        $this->categories = $this->categoryRepository->getAll();
        $this->articles   = $this->articleRepository->getAll();
    }

    public function handleRequest(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $act = $_POST['action'] ?? '';
            switch ($act) {
                case 'add':
                    $this->handleAdd();
                    break;
                case 'save':
                    $this->handleSave();
                    break;
                case 'delete':
                    $this->handleDelete();
                    break;
                case 'toggle_hot':
                    $this->handleToggleHot();
                    break;
                case 'update_comment':
                    $this->handleUpdateComment();
                    break;
            }
        }

        $this->resolveEditingItem();
    }

    private function handleAdd(): void
    {
        $postedId = preg_replace('/[^a-z0-9_-]/i', '', $_POST['id'] ?? '');
        if (empty($postedId)) {
            $this->errors[] = 'ID is required for new articles.';
        }
        if (empty($_POST['title'])) {
            $this->errors[] = 'Title is required.';
        }
        if (empty($_POST['content'])) {
            $this->errors[] = 'Content is required.';
        }

        if (!empty($this->errors)) {
            return;
        }

        [$ok, $item] = $this->articleRepository->add(array_merge($_POST, ['id' => $postedId]), $_FILES);
        if ($ok) {
            $this->messages[] = 'Article added successfully. View it on the news page.';
            // Trigger Notification if HOT
            if (!empty($item['hot'])) {
                $count = $this->notificationService->notifySubscribersForArticle($item);
                if ($count > 0) $this->messages[] = "Notification sent to $count subscribers.";
            }

            header('Refresh: 1.5; url=/projet_web/view/front/news.php');
        } else {
            $this->errors[] = 'Failed to save article — database error.';
        }
    }

    private function handleSave(): void
    {
        if ($this->id === '') {
            $this->errors[] = 'Missing article id for save.';
            return;
        }

        if (empty($_POST['title'])) {
            $this->errors[] = 'Title is required.';
        }
        if (empty($_POST['content'])) {
            $this->errors[] = 'Content is required.';
        }
        if (!empty($this->errors)) {
            return;
        }

        [$ok, $after] = $this->articleRepository->update($this->id, $_POST, $_FILES);
        if ($ok) {
            $this->messages[] = 'Article updated successfully.';
            
            // Trigger Notification if HOT and not sent yet (handled by service)
            if (!empty($after['hot'])) {
                // We need the full article record with ID to check flag
                $full = $this->articleRepository->findBySlug($this->id); 
                // findBySlug returns array, let's ensure we have notification status
                // If the update didn't return notification_sent, retrieve it.
                // Actually findBySlug might not have 'notifications_sent' in SELECT yet.
                // Ideally, Service queries DB or we add it to Repo. 
                // Let's rely on Service to query/check. Pass $full.
                 $count = $this->notificationService->notifySubscribersForArticle($full);
                 if ($count > 0) $this->messages[] = "Notification sent to $count subscribers.";
            }

        } else {
            $this->errors[] = 'Failed to save changes — database error.';
        }
    }

    private function handleDelete(): void
    {
        if ($this->id === '') {
            $this->errors[] = 'Missing article id for delete.';
            return;
        }

        if ($this->articleRepository->delete($this->id)) {
            $this->messages[] = 'Article deleted.';
        } else {
            $this->errors[] = 'Failed to delete article — database error.';
        }
    }

    private function handleToggleHot(): void
    {
        if ($this->id === '') {
            $this->errors[] = 'Missing article id for hot toggle.';
            return;
        }

        $res = $this->articleRepository->toggleHot($this->id);
        if ($res !== null) {
            $this->messages[] = 'Article ' . ($res ? 'marked as hot' : 'removed from hot news') . '.';
            if ($res) { // became hot
                 $full = $this->articleRepository->findBySlug($this->id);
                 $count = $this->notificationService->notifySubscribersForArticle($full);
                 if ($count > 0) $this->messages[] = "Notification sent to $count subscribers.";
            }
        } else {
            $this->errors[] = 'Failed to update hot status — database error.';
        }
    }



    private function handleUpdateComment(): void
    {
        $slug = $_POST['slug'] ?? '';
        $index = isset($_POST['index']) ? intval($_POST['index']) : -1;
        $name = trim($_POST['name'] ?? '');
        $text = trim($_POST['text'] ?? '');

        if (empty($slug)) {
            $this->errors[] = 'Missing article slug for comment update.';
            return;
        }

        // Get article by slug to find article ID
        $article = $this->articleRepository->findBySlug($slug);
        if (!$article) {
            $this->errors[] = 'Article not found.';
            return;
        }

        // Get all comments for this article and find the one at the specified index
        $comments = $this->commentRepository->findCommentsByArticleId($article['idArticle']);
        if (!isset($comments[$index])) {
            $this->errors[] = 'Comment not found.';
            return;
        }

        $comment = $comments[$index];
        $comment->setName($name);
        $comment->setText($text);

        $ok = $this->commentRepository->save($comment);

        if ($ok) {
            $this->messages[] = 'Comment updated.';
        } else {
            $this->errors[] = 'Failed to update comment.';
        }
    }

    private function resolveEditingItem(): void
    {
        $this->editing = null;
        if ($this->action === 'edit' && $this->id !== '') {
            foreach ($this->articles as $it) {
                if (($it['id'] ?? '') === $this->id) {
                    $this->editing = $it;
                    break;
                }
            }
            if (!$this->editing) {
                $this->editing = $this->articleRepository->findBySlug($this->id);
            }
            if ($this->editing) {
                $this->editing['history'] = $this->articleRepository->getHistoryByArticleId($this->editing['idArticle']);
            }
        }
    }

    // ---- Getters used by the views ----

    public function getMessages(): array
    {
        return $this->messages;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getCategories(): array
    {
        return $this->categories;
    }

    public function getArticles(): array
    {
        return $this->articles;
    }

    public function getEditing(): ?array
    {
        return $this->editing;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getId(): string
    {
        return $this->id;
    }
}

// Bootstrap for legacy usage: expose simple variables for any views
$__newsAdminController = new NewsAdminController();
$__newsAdminController->handleRequest();

$messages   = $__newsAdminController->getMessages();
$errors     = $__newsAdminController->getErrors();
$categories = $__newsAdminController->getCategories();
$data       = $__newsAdminController->getArticles();
$editing    = $__newsAdminController->getEditing();
$action     = $__newsAdminController->getAction();
$id         = $__newsAdminController->getId();

unset($__newsAdminController);

?>

?>



