<?php
// Controller for admin news actions (class-based, MVC)
require_once __DIR__ . '/../model/Article.php';
require_once __DIR__ . '/../model/Categorie.php';
require_once __DIR__ . '/../model/Comment.php';

class NewsAdminController
{
    private array $messages = [];
    private array $errors = [];
    private string $action;
    private string $id;
    private array $categories = [];
    private array $articles = [];
    private ?array $editing = null;

    public function __construct()
    {
        global $pdo;

        $this->action     = $_GET['action'] ?? '';
        $this->id         = $_GET['id'] ?? '';
        $this->categories = Categorie::getAll();
        $this->articles   = Article::getAll();
        foreach ($this->articles as &$art) {
            $art['sentiment_stats'] = Comment::getSentimentStats($art['idArticle']);
        }
        unset($art);
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

        [$ok, $item] = Article::add(array_merge($_POST, ['id' => $postedId]), $_FILES);
        if ($ok) {
            $this->messages[] = 'Article added successfully. View it on the news page.';
            $count = Categorie::notifySubscribersForArticle($item);
            if ($count > 0) $this->messages[] = "Notification sent to $count subscribers.";

            header('Refresh: 1.5; url=/projet_web/view/front/news.php');
        } else {
            $this->errors[] = 'Failed to save article — database error.';
        }
    }

    private function handleSave(): void
    {
        if ($this->id === '') {
            $this->id = $_POST['id'] ?? '';
            if ($this->id === '') {
                $this->errors[] = 'Missing article id for save.';
                return;
            }
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

        [$ok, $after] = Article::update($this->id, $_POST, $_FILES);
        if ($ok) {
            $this->messages[] = 'Article updated successfully.';
            
            // Trigger Notification if HOT and not sent yet (handled by service)
            if (!empty($after['hot'])) {
                // We need the full article record with ID to check flag
                $full = Article::findBySlug($this->id); 
                // findBySlug returns array, let's ensure we have notification status
                // If the update didn't return notification_sent, retrieve it.
                // Ideally, Service queries DB or we add it to Repo. 
                // Let's rely on Service to query/check. Pass $full.
                 $count = Categorie::notifySubscribersForArticle($full);
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

        if (Article::delete($this->id)) {
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

        $res = Article::toggleHot($this->id);
        if ($res !== null) {
            $this->messages[] = 'Article ' . ($res ? 'marked as hot' : 'removed from hot news') . '.';
            if ($res) { // became hot
                 $full = Article::findBySlug($this->id);
                 $count = Categorie::notifySubscribersForArticle($full);
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
        $article = Article::findBySlug($slug);
        if (!$article) {
            $this->errors[] = 'Article not found.';
            return;
        }

        // Get all comments for this article and find the one at the specified index
        $comments = Comment::findByArticleId($article['idArticle']);
        if (!isset($comments[$index])) {
            $this->errors[] = 'Comment not found.';
            return;
        }

        $comment = $comments[$index];
        $comment->setName($name);
        $comment->setText($text);

        $ok = Comment::save($comment);

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
                $this->editing = Article::findBySlug($this->id);
            }
            if ($this->editing) {
                $this->editing['history'] = Article::getHistoryByArticleId($this->editing['idArticle']);
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



