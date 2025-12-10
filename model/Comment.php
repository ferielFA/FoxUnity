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
    private DateTime $createdAt;

    public function __construct(
        ?int $idComment = null,
        ?int $articleId = null,
        string $name = '',
        string $email = '',
        string $text = '',
        bool $deleted = false,
        ?DateTime $createdAt = null
    ) {
        $this->idComment = $idComment;
        $this->articleId = $articleId;
        $this->name      = $name;
        $this->email     = $email;
        $this->text      = $text;
        $this->deleted   = $deleted;
        $this->createdAt = $createdAt ?? new DateTime();
    }

    // Getters
    public function getIdComment(): ?int { return $this->idComment; }
    public function getArticleId(): ?int { return $this->articleId; }
    public function getName(): string { return $this->name; }
    public function getEmail(): string { return $this->email; }
    public function getText(): string { return $this->text; }
    public function isDeleted(): bool { return $this->deleted; }
    public function getCreatedAt(): DateTime { return $this->createdAt; }

    // Setters
    public function setIdComment(?int $idComment): void { $this->idComment = $idComment; }
    public function setArticleId(?int $articleId): void { $this->articleId = $articleId; }
    public function setName(string $name): void { $this->name = $name; }
    public function setEmail(string $email): void { $this->email = $email; }
    public function setText(string $text): void { $this->text = $text; }
    public function setDeleted(bool $deleted): void { $this->deleted = $deleted; }
    public function setCreatedAt(DateTime $createdAt): void { $this->createdAt = $createdAt; }

    // Méthodes métier
    public function softDelete(): void
    {
        $this->deleted = true;
    }
}

?>



