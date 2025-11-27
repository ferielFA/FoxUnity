<?php

class Evenement {
    private ?int $id_evenement;
    private string $titre;
    private string $description;
    private DateTime $date_debut;
    private DateTime $date_fin;
    private string $lieu;
    private ?string $createur_email;
    private string $statut;

    public function __construct(
        ?int $id_evenement = null,
        string $titre = '',
        string $description = '',
        ?DateTime $date_debut = null,
        ?DateTime $date_fin = null,
        string $lieu = '',
        ?string $createur_email = null,
        string $statut = 'upcoming'
    ) {
        $this->id_evenement = $id_evenement;
        $this->titre = $titre;
        $this->description = $description;
        $this->date_debut = $date_debut ?? new DateTime();
        $this->date_fin = $date_fin ?? new DateTime();
        $this->lieu = $lieu;
        $this->createur_email = $createur_email;
        $this->statut = $statut;
    }

    // Getters
    public function getIdEvenement(): ?int { return $this->id_evenement; }
    public function getTitre(): string { return $this->titre; }
    public function getDescription(): string { return $this->description; }
    public function getDateDebut(): DateTime { return $this->date_debut; }
    public function getDateFin(): DateTime { return $this->date_fin; }
    public function getLieu(): string { return $this->lieu; }
    public function getCreateurEmail(): ?string { return $this->createur_email; }
    public function getStatut(): string { return $this->statut; }

    // Setters
    public function setIdEvenement(?int $id): void { $this->id_evenement = $id; }
    public function setTitre(string $titre): void { $this->titre = $titre; }
    public function setDescription(string $description): void { $this->description = $description; }
    public function setDateDebut(DateTime $date): void { $this->date_debut = $date; }
    public function setDateFin(DateTime $date): void { $this->date_fin = $date; }
    public function setLieu(string $lieu): void { $this->lieu = $lieu; }
    public function setCreateurEmail(?string $email): void { $this->createur_email = $email; }
    public function setStatut(string $statut): void { $this->statut = $statut; }

    // Méthodes métier
    public function calculerTempsRestant(): string {
        $now = new DateTime();
        $diff = $now->diff($this->date_debut);
        
        if ($this->date_debut < $now) {
            return "Événement commencé";
        }
        
        return $diff->format('%d jours, %h heures, %i minutes');
    }

    public function changerStatut(string $nouveauStatut): bool {
        $statutsValides = ['upcoming', 'ongoing', 'completed', 'cancelled'];
        if (in_array($nouveauStatut, $statutsValides)) {
            $this->statut = $nouveauStatut;
            return true;
        }
        return false;
    }

    public function obtenirParticipants(): int {
        // Cette méthode sera implémentée via le contrôleur
        return 0;
    }
}
?>
