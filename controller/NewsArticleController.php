<?php
// Controller for single article pages (class-based)
require_once __DIR__ . '/../model/db.php';
require_once __DIR__ . '/../model/ArticleRepository.php';
require_once __DIR__ . '/../model/CategoryRepository.php';
require_once __DIR__ . '/../model/helpers.php';

require_once __DIR__ . '/../model/CommentRepository.php';
require_once __DIR__ . '/../model/Comment.php';

/**
 * Class NewsArticleController
 *
 * Loads a single article by slug and its related category data.
 * Does not render HTML; it only prepares data for the view layer.
 */
class NewsArticleController
{
    private ?array $article = null;
    private array $categories = [];
    private string $slug = '';
    private bool $notFound = false;
    private array $comments = [];
    private array $errors = [];

    private ArticleRepository $articleRepository;
    private CategoryRepository $categoryRepository;
    private CommentRepository $commentRepository;

    public function __construct()
    {
        global $pdo;
        $this->articleRepository  = new ArticleRepository($pdo);
        $this->categoryRepository = new CategoryRepository($pdo);
        $this->commentRepository  = new CommentRepository($pdo);

        $this->slug = $_GET['id'] ?? '';
        $this->load();
        $this->handlePost();
    }

    private function load(): void
    {
        if ($this->slug === '') {
            $this->notFound = true;
            return;
        }

        $a = $this->articleRepository->findBySlug($this->slug);
        if (!$a) {
            $this->notFound = true;
            return;
        }

        $this->article    = $a;
        $this->categories = $this->categoryRepository->getAll();
        
        // Load comments
        $loadedComments = $this->commentRepository->findCommentsByArticleId($a['idArticle']);
        
        // Convert to array for view compatibility (legacy structure)
        $this->comments = [];
        foreach ($loadedComments as $c) {
            $this->comments[] = [
                'name' => $c->getName(),
                'email' => $c->getEmail(),
                'text' => $c->getText(),
                'date' => $c->getCreatedAt()->format('Y-m-d H:i:s')
            ];
        }
    }

    private function handlePost(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_submit']) && $this->article) {
            $name = trim((string)($_POST['name'] ?? ''));
            $text = trim((string)($_POST['comment'] ?? ''));

            if ($name === '') $this->errors[] = 'Name is required.';
            if ($text === '') $this->errors[] = 'Comment is required.';

            // Mutes handling
            $mutesFile = __DIR__ . '/../view/front/uploads/comments/mutes.json';
            // ensure dir exists
            @mkdir(dirname($mutesFile), 0755, true);
            $mutes = [];
            if (file_exists($mutesFile)) $mutes = json_decode(file_get_contents($mutesFile), true) ?: [];
            
            $lower = strtolower($name);
            if ($lower && isset($mutes[$lower]) && intval($mutes[$lower]) > time()) {
                $this->errors[] = 'You are muted until ' . date('Y-m-d H:i:s', intval($mutes[$lower])) . '. You cannot post comments.';
            }

            if (empty($this->errors)) {
                $newComment = new Comment(null, $this->article['idArticle'], $name, '', $text);
                $this->commentRepository->save($newComment);
                
                // Redirect to avoid repost
                header('Location: news_article.php?id=' . urlencode($this->slug) . '#comments');
                exit;
            }
        }
    }

    public function isNotFound(): bool
    {
        return $this->notFound;
    }

    public function getArticle(): ?array
    {
        return $this->article;
    }

    public function getCategories(): array
    {
        return $this->categories;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }
    
    public function getComments(): array
    {
        return $this->comments;
    }
    
    public function getErrors(): array
    {
        return $this->errors;
    }
}

// Bootstrap for legacy usage: expose variables if this controller is included directly
$__newsArticleController = new NewsArticleController();
if ($__newsArticleController->isNotFound()) {
    header('HTTP/1.0 404 Not Found');
    echo '<h1>Article not found</h1>';
    exit;
}

$a          = $__newsArticleController->getArticle();
$categories = $__newsArticleController->getCategories();
$slug       = $__newsArticleController->getSlug();
$comments   = $__newsArticleController->getComments();
$errors     = $__newsArticleController->getErrors();
unset($__newsArticleController);

?>



