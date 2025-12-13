<?php
// Controller for news article page (class-based, fully MVC)
require_once __DIR__ . '/../model/Article.php';
require_once __DIR__ . '/../model/Comment.php';
require_once __DIR__ . '/../model/Categorie.php'; // For category processing if needed


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

    public function __construct()
    {
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

        $a = Article::findBySlug($this->slug);
        if (!$a) {
            $this->notFound = true;
            return;
        }

        $this->article    = $a;
        $this->categories = Categorie::getAll();
        
        // Load comments
        // Calculate Community Verdict
        $positiveCount = 0;
        $negativeCount = 0;
        $loadedComments = Comment::findByArticleId($a['idArticle']);
        $this->comments = [];
        foreach ($loadedComments as $c) {
            if ($c->getSentimentLabel() === 'positive') $positiveCount++;
            if ($c->getSentimentLabel() === 'negative') $negativeCount++;
            
            $this->comments[] = [
                'name' => $c->getName(),
                'email' => $c->getEmail(),
                'text' => $c->getText(), // Already censored in DB
                'sentiment' => $c->getSentimentLabel(),
                'rating' => $c->getRating(),
                'date' => $c->getCreatedAt()->format('Y-m-d H:i:s')
            ];
        }
        
        $total = $positiveCount + $negativeCount;
        $verdict = 'Neutral';
        if ($total > 0) {
            if ($positiveCount > $negativeCount * 1.5) $verdict = 'Mostly Positive';
            elseif ($negativeCount > $positiveCount * 1.5) $verdict = 'Mostly Negative';
            else $verdict = 'Mixed';
        }
        $this->article['verdict'] = $verdict;
    }

    private function handlePost(): void
    {

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_submit']) && $this->article) {
            $name = trim((string)($_POST['name'] ?? ''));
            $text = trim((string)($_POST['comment'] ?? ''));
            $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : null;
            if ($rating !== null && ($rating < 1 || $rating > 5)) $rating = null;

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
                
                // AI Analysis
                $analysis = Comment::analyzeSentiment($text);
                
                // 2. Reject if too toxic
                if ($analysis['toxicity'] > 70) {
                    $this->errors[] = 'Your comment was rejected because it violates our community guidelines (High Toxicity).';
					$rating = null; // Reset rating if rejected
                } else {
                    $censoredText = Comment::censor($text);
                    
                    $newComment = new Comment(null, $this->article['idArticle'], $name, '', $censoredText, false, $analysis['toxicity'], $analysis['label'], $rating);
                    Comment::save($newComment);
                    
                    // Redirect to avoid repost
                    header('Location: news_article.php?id=' . urlencode($this->slug) . '#comments');
                    exit;
                }
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



