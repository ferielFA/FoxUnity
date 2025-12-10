<?php

/**
 * Modèle de domaine pour une catégorie d'article.
 */
class Categorie
{
    private ?int $idCategorie;
    private string $nom;
    private string $description;
    private string $slug;
    private int $position;
    private bool $active;

    public function __construct(
        ?int $idCategorie = null,
        string $nom = '',
        string $description = '',
        string $slug = '',
        int $position = 0,
        bool $active = true
    ) {
        $this->idCategorie = $idCategorie;
        $this->nom         = $nom;
        $this->description = $description;
        $this->slug        = $slug;
        $this->position    = $position;
        $this->active      = $active;
    }

    // Getters
    public function getIdCategorie(): ?int { return $this->idCategorie; }
    public function getNom(): string { return $this->nom; }
    public function getDescription(): string { return $this->description; }
    public function getSlug(): string { return $this->slug; }
    public function getPosition(): int { return $this->position; }
    public function isActive(): bool { return $this->active; }

    // Setters
    public function setIdCategorie(?int $idCategorie): void { $this->idCategorie = $idCategorie; }
    public function setNom(string $nom): void { $this->nom = $nom; }
    public function setDescription(string $description): void { $this->description = $description; }
    public function setSlug(string $slug): void { $this->slug = $slug; }
    public function setPosition(int $position): void { $this->position = $position; }
    public function setActive(bool $active): void { $this->active = $active; }

    // Méthodes métier
    public function activer(): void
    {
        $this->active = true;
    }

    public function desactiver(): void
    {
        $this->active = false;
    }
}

?>



