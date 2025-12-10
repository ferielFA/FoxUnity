<?php

/**
 * Modèle de domaine pour un article.
 *
 * Entité pure (aucun accès BD / fichiers).
 */
class Article
{
    private ?int $idArticle;
    private string $slug;
    private int $id_pub;
    private string $titre;
    private string $description;
    private string $contenu;
    private string $excerpt;
    private string $image;
    private DateTime $datePublication;
    private int $idCategorie;
    private bool $hot;

    public function __construct(
        ?int $idArticle = null,
        string $slug = '',
        int $id_pub = 0,
        string $titre = '',
        string $description = '',
        string $contenu = '',
        string $excerpt = '',
        string $image = '',
        ?DateTime $datePublication = null,
        int $idCategorie = 0,
        bool $hot = false
    ) {
        $this->idArticle       = $idArticle;
        $this->slug            = $slug;
        $this->id_pub          = $id_pub;
        $this->titre           = $titre;
        $this->description     = $description;
        $this->contenu         = $contenu;
        $this->excerpt         = $excerpt;
        $this->image           = $image;
        $this->datePublication = $datePublication ?? new DateTime();
        $this->idCategorie     = $idCategorie;
        $this->hot             = $hot;
    }

    // Getters
    public function getIdArticle(): ?int { return $this->idArticle; }
    public function getSlug(): string { return $this->slug; }
    public function getIdPub(): int { return $this->id_pub; }
    public function getTitre(): string { return $this->titre; }
    public function getDescription(): string { return $this->description; }
    public function getContenu(): string { return $this->contenu; }
    public function getExcerpt(): string { return $this->excerpt; }
    public function getImage(): string { return $this->image; }
    public function getDatePublication(): DateTime { return $this->datePublication; }
    public function getIdCategorie(): int { return $this->idCategorie; }
    public function isHot(): bool { return $this->hot; }

    // Setters
    public function setIdArticle(?int $idArticle): void { $this->idArticle = $idArticle; }
    public function setSlug(string $slug): void { $this->slug = $slug; }
    public function setIdPub(int $id_pub): void { $this->id_pub = $id_pub; }
    public function setTitre(string $titre): void { $this->titre = $titre; }
    public function setDescription(string $description): void { $this->description = $description; }
    public function setContenu(string $contenu): void { $this->contenu = $contenu; }
    public function setExcerpt(string $excerpt): void { $this->excerpt = $excerpt; }
    public function setImage(string $image): void { $this->image = $image; }
    public function setDatePublication(DateTime $datePublication): void { $this->datePublication = $datePublication; }
    public function setIdCategorie(int $idCategorie): void { $this->idCategorie = $idCategorie; }
    public function setHot(bool $hot): void { $this->hot = $hot; }

    // Méthodes métier
    public function toggleHot(): void
    {
        $this->hot = !$this->hot;
    }

    public function getResumeTitre(int $max = 60): string
    {
        if (mb_strlen($this->titre) <= $max) {
            return $this->titre;
        }
        return mb_substr($this->titre, 0, $max - 3) . '...';
    }
}

?>



