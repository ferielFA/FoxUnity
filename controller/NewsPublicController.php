<?php
// Controller for public news listing (class-based, MVC-friendly)
require_once __DIR__ . '/../model/db.php';
require_once __DIR__ . '/../model/Article.php';
require_once __DIR__ . '/../model/Categorie.php';

/**
 * Class NewsPublicController
 *
 * Provides data for the public news listing page:
 *  - all categories
 *  - all non-hot articles
 *  - hot articles highlighted separately
 */
class NewsPublicController
{
    private array $categories = [];
    private array $articles = [];
    private array $hotNews = [];

    public function __construct()
    {
        $this->loadData();
    }

    private function loadData(): void
    {
        $allCategories = Categorie::getAll();
        $allArticles   = Article::getAll();

        $hot    = [];
        $normal = [];
        foreach ($allArticles as $a) {
            if (($a['hot'] ?? 0) == 1) {
                $hot[] = $a;
            } else {
                $normal[] = $a;
            }
        }

        $this->categories = $allCategories;
        $this->articles   = $normal;
        $this->hotNews    = $hot;
    }

    public function getCategories(): array
    {
        return $this->categories;
    }

    public function getArticles(): array
    {
        return $this->articles;
    }

    public function getHotNews(): array
    {
        return $this->hotNews;
    }
}

// Bootstrap for legacy includes: expose variables expected by older views
$__newsPublicController = new NewsPublicController();
$categories = $__newsPublicController->getCategories();
$articles   = $__newsPublicController->getArticles();
$hotNews    = $__newsPublicController->getHotNews();
unset($__newsPublicController);

?>



