<?php

require_once __DIR__ . '/Comment.php';

class CommentRepository
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function findCommentsByArticleId($articleId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM comments WHERE article_id = :articleId AND is_deleted = 0 ORDER BY created_at DESC"
        );
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
                new DateTime($row['created_at'])
            );
        }

        return $comments;
    }

    public function save(Comment $comment): bool
    {
        if ($comment->getIdComment() === null) {
            return $this->insert($comment);
        }
        
        return $this->update($comment);
    }

    private function insert(Comment $comment): bool
    {
        $sql = "INSERT INTO comments
                (article_id, name, email, text, created_at)
                VALUES (:articleId, :name, :email, :text, NOW())";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            'articleId' => $comment->getArticleId(),
            'name' => $comment->getName(),
            'email' => $comment->getEmail(),
            'text' => $comment->getText()
        ]);
    }

    private function update(Comment $comment): bool
    {
        $sql = "UPDATE comments SET
                article_id = :articleId,
                name = :name,
                email = :email,
                text = :text,
                is_deleted = :is_deleted
                WHERE idComment = :id";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            'id' => $comment->getIdComment(),
            'articleId' => $comment->getArticleId(),
            'name' => $comment->getName(),
            'email' => $comment->getEmail(),
            'text' => $comment->getText(),
            'is_deleted' => (int)$comment->isDeleted()
        ]);
    }

    public function delete($commentId): bool
    {
        $sql = "UPDATE comments SET is_deleted = 1 WHERE idComment = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['id' => $commentId]);
    }

    public function clearArticleComments($articleId): bool
    {
        $sql = "UPDATE comments SET is_deleted = 1 WHERE article_id = :articleId";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['articleId' => $articleId]);
    }
}
