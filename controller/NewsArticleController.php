<?php
// Controller for single article pages (class-based)
require_once __DIR__ . '/../model/ArticleRepository.php';
require_once __DIR__ . '/../model/CategoryRepository.php';
require_once __DIR__ . '/../model/helpers.php';

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

    private ArticleRepository $articleRepository;
    private CategoryRepository $categoryRepository;

    public function __construct()
    {
        global $pdo;
        $this->articleRepository  = new ArticleRepository($pdo);
        $this->categoryRepository = new CategoryRepository($pdo);

        $this->slug = $_GET['id'] ?? '';
        $this->load();
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
unset($__newsArticleController);

?>



