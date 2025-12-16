<?php

/**
 * Modèle de domaine pour un commentaire.
 */
class Comment
{
    private ?int $idComment;
    private ?int $articleId;
    private string $name;
    private string $email;
    private string $text;
    private bool $deleted;
    private float $toxicityScore;
    private string $sentimentLabel;
    private ?int $rating;
    private ?int $parentId;    // NEW: For threading
    private ?string $userImage; // NEW: Fetched via JOIN
    private DateTime $createdAt;

    public function __construct(
        ?int $idComment = null,
        ?int $articleId = null,
        string $name = '',
        string $email = '',
        string $text = '',
        bool $deleted = false,
        float $toxicityScore = 0.0,
        string $sentimentLabel = 'neutral',
        ?int $rating = null,
        ?int $parentId = null,      // NEW
        ?string $userImage = null,  // NEW
        ?DateTime $createdAt = null
    ) {
        $this->idComment = $idComment;
        $this->articleId = $articleId;
        $this->name      = $name;
        $this->email     = $email;
        $this->text      = $text;
        $this->deleted   = $deleted;
        $this->toxicityScore = $toxicityScore;
        $this->sentimentLabel = $sentimentLabel;
        $this->rating         = $rating;
        $this->parentId       = $parentId;
        $this->userImage      = $userImage;
        $this->createdAt = $createdAt ?? new DateTime();
    }

    // Getters
    public function getIdComment(): ?int { return $this->idComment; }
    public function getArticleId(): ?int { return $this->articleId; }
    public function getName(): string { return $this->name; }
    public function getEmail(): string { return $this->email; }
    public function getText(): string { return $this->text; }
    public function isDeleted(): bool { return $this->deleted; }
    public function getToxicityScore(): float { return $this->toxicityScore; }
    public function getSentimentLabel(): string { return $this->sentimentLabel; }
    public function getRating(): ?int { return $this->rating; }
    public function getParentId(): ?int { return $this->parentId; }
    public function getUserImage(): ?string { return $this->userImage; }
    public function getCreatedAt(): DateTime { return $this->createdAt; }

    private array $replies = []; // NEW: For threading

    public function setReplies(array $replies): void { $this->replies = $replies; }
    public function addReply(Comment $reply): void { $this->replies[] = $reply; }
    public function getReplies(): array { return $this->replies; }

    // Setters
    public function setIdComment(?int $idComment): void { $this->idComment = $idComment; }
    public function setArticleId(?int $articleId): void { $this->articleId = $articleId; }
    public function setName(string $name): void { $this->name = $name; }
    public function setEmail(string $email): void { $this->email = $email; }
    public function setText(string $text): void { $this->text = $text; }
    public function setDeleted(bool $deleted): void { $this->deleted = $deleted; }
    public function setToxicityScore(float $score): void { $this->toxicityScore = $score; }
    public function setSentimentLabel(string $label): void { $this->sentimentLabel = $label; }
    public function setRating(?int $rating): void { $this->rating = $rating; }
    public function setParentId(?int $parentId): void { $this->parentId = $parentId; }
    public function setUserImage(?string $image): void { $this->userImage = $image; }
    public function setCreatedAt(DateTime $createdAt): void { $this->createdAt = $createdAt; }

    // Méthodes métier
    public function softDelete(): void
    {
        $this->deleted = true;
    }

    // ========== Static Database Methods ==========

    private static function getPdo(): PDO
    {
        require_once __DIR__ . '/db.php';
        global $pdo;
        return $pdo;
    }

    public static function findByArticleId(int $articleId): array
    {
        $pdo = self::getPdo();
        // JOIN users to get the profile image efficiently
        $sql = "SELECT c.*, u.image as user_image 
                FROM comments c 
                LEFT JOIN users u ON c.email = u.email 
                WHERE c.article_id = :articleId AND c.is_deleted = 0 
                ORDER BY c.created_at ASC"; // Order ASC to build threads chronologically if needed, or DESC
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['articleId' => $articleId]);

        $comments = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $comments[] = new Comment(
                $row['idComment'],
                $row['article_id'],
                $row['name'],
                $row['email'],
                $row['text'],
                (bool)$row['is_deleted'],
                (float)($row['toxicity_score'] ?? 0),
                $row['sentiment_label'] ?? 'neutral',
                isset($row['rating']) ? (int)$row['rating'] : null,
                isset($row['parent_id']) ? (int)$row['parent_id'] : null,
                $row['user_image'] ?? null,
                new DateTime($row['created_at'])
            );
        }

        return $comments;
    }

    public static function save(Comment $comment): bool
    {
        if ($comment->getIdComment() === null) {
            return self::insert($comment);
        }
        
        return self::update($comment);
    }

    private static function insert(Comment $comment): bool
    {
        $pdo = self::getPdo();
        $sql = "INSERT INTO comments
                (article_id, name, email, text, toxicity_score, sentiment_label, rating, parent_id, created_at)
                VALUES (:articleId, :name, :email, :text, :tox, :sent, :rating, :parentId, NOW())";

        $stmt = $pdo->prepare($sql);

        return $stmt->execute([
            'articleId' => $comment->getArticleId(),
            'name' => $comment->getName(),
            'email' => $comment->getEmail(),
            'text' => $comment->getText(),
            'tox' => $comment->getToxicityScore(),
            'sent' => $comment->getSentimentLabel(),
            'rating' => $comment->getRating(),
            'parentId' => $comment->getParentId()
        ]);
    }

    private static function update(Comment $comment): bool
    {
        $pdo = self::getPdo();
        $sql = "UPDATE comments SET
                article_id = :articleId,
                name = :name,
                email = :email,
                text = :text,
                toxicity_score = :tox,
                sentiment_label = :sent,
                rating = :rating,
                parent_id = :parentId,
                is_deleted = :is_deleted
                WHERE idComment = :id";

        $stmt = $pdo->prepare($sql);

        return $stmt->execute([
            'id' => $comment->getIdComment(),
            'articleId' => $comment->getArticleId(),
            'name' => $comment->getName(),
            'email' => $comment->getEmail(),
            'text' => $comment->getText(),
            'tox' => $comment->getToxicityScore(),
            'sent' => $comment->getSentimentLabel(),
            'rating' => $comment->getRating(),
            'parentId' => $comment->getParentId(),
            'is_deleted' => (int)$comment->isDeleted()
        ]);
    }

    public static function deleteById(int $commentId): bool
    {
        $pdo = self::getPdo();
        $sql = "UPDATE comments SET is_deleted = 1 WHERE idComment = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute(['id' => $commentId]);
    }

    public static function clearArticleComments(int $articleId): bool
    {
        $pdo = self::getPdo();
        $sql = "UPDATE comments SET is_deleted = 1 WHERE article_id = :articleId";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute(['articleId' => $articleId]);
    }

    public static function getSentimentStats(int $articleId): array
    {
        $pdo = self::getPdo();
        $stmt = $pdo->prepare("SELECT sentiment_label, COUNT(*) as count FROM comments WHERE article_id = :aid AND is_deleted = 0 GROUP BY sentiment_label");
        $stmt->execute(['aid' => $articleId]);
        $stats = ['positive' => 0, 'negative' => 0, 'neutral' => 0];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $label = strtolower($row['sentiment_label'] ?? 'neutral');
            if (isset($stats[$label])) {
                $stats[$label] = (int)$row['count'];
            }
        }
        return $stats;
    }


    // --- Logic migrated from SentimentService ---
    
    // Simple dictionaries for heuristic sentiment analysis
    private static $positiveWords = ['good', 'great', 'awesome', 'amazing', 'love', 'excellent', 'happy', 'best', 'fantastic', 'nice', 'cool', 'perfect', 'beautiful', 'thank', 'thanks'];
    private static $negativeWords = ['bad', 'terrible', 'awful', 'hate', 'worst', 'stupid', 'boring', 'horrible', 'useless', 'trash', 'shit', 'suck', 'disappointing', 'poor'];
    private static $toxicWords    = ['idiot', 'moron', 'dumb', 'stupid', 'shut up', 'ugly', 'fat', 'kill', 'die', 'racist', 'nazi', 'bitch', 'fuck', 'shit', 'asshole'];

    public static function censor(string $text): string {
        $badWords = array_merge(self::$negativeWords, self::$toxicWords, ['shit', 'fuck', 'bitch', 'ass']); 
        foreach ($badWords as $word) {
            $pattern = '/\b' . preg_quote($word, '/') . '\b/i';
            $text = preg_replace($pattern, '****', $text);
        }
        return $text;
    }

    public static function analyzeSentiment(string $text): array {
        $textLower = strtolower($text);
        $score = 0;
        $toxicity = 0;

        // Sentiment score
        foreach (self::$positiveWords as $word) {
            if (strpos($textLower, $word) !== false) $score++;
        }
        foreach (self::$negativeWords as $word) {
            if (strpos($textLower, $word) !== false) $score--;
        }

        // Toxicity score
        $toxCount = 0;
        foreach (self::$toxicWords as $word) {
            if (strpos($textLower, $word) !== false) {
                $toxicity += 20; 
                $toxCount++;
            }
        }
        
        // Normalize toxicity
        if ($toxicity > 100) $toxicity = 100;
        if ($score < -5) $score = -5;
        if ($score > 5) $score = 5;

        // Determine label
        $label = 'Neutral';
        if ($score > 0) $label = 'Positive';
        if ($score < 0) $label = 'Negative';

        return [
            'score' => $score,
            'label' => $label,
            'toxicity' => $toxicity
        ];
    }
}

?>



