<?php

class Participation {
    private ?int $id_participation;
    private int $id_evenement;
    private ?int $user_id;
    private string $nom_participant;
    private string $email_participant;
    private DateTime $date_participation;

    public function __construct(
        ?int $id_participation = null,
        int $id_evenement = 0,
        ?int $user_id = null,
        string $nom_participant = '',
        string $email_participant = '',
        ?DateTime $date_participation = null
    ) {
        $this->id_participation = $id_participation;
        $this->id_evenement = $id_evenement;
        $this->user_id = $user_id;
        $this->nom_participant = $nom_participant;
        $this->email_participant = $email_participant;
        $this->date_participation = $date_participation ?? new DateTime();
    }

    // Getters
    public function getIdParticipation(): ?int { return $this->id_participation; }
    public function getIdEvenement(): int { return $this->id_evenement; }
    public function getUserId(): ?int { return $this->user_id; }
    public function getNomParticipant(): string { return $this->nom_participant; }
    public function getEmailParticipant(): string { return $this->email_participant; }
    public function getDateParticipation(): DateTime { return $this->date_participation; }

    // Setters
    public function setIdParticipation(?int $id): void { $this->id_participation = $id; }
    public function setIdEvenement(int $id): void { $this->id_evenement = $id; }
    public function setUserId(?int $id): void { $this->user_id = $id; }
    public function setNomParticipant(string $nom): void { $this->nom_participant = $nom; }
    public function setEmailParticipant(string $email): void { $this->email_participant = $email; }
    public function setDateParticipation(DateTime $date): void { $this->date_participation = $date; }

    // Méthodes métier
    public function inscrire(): bool {
        // Logique métier : vérifier si l'inscription est possible
        return true;
    }

    public function desinscrire(): bool {
        // Logique métier : vérifier si la désinscription est possible
        $dateEvenement = new DateTime(); // À récupérer via le contrôleur
        $now = new DateTime();
        
        // Exemple : on peut se désinscrire jusqu'à 24h avant
        $diff = $now->diff($dateEvenement);
        return $diff->days >= 1;
    }

    public function verifierInscription(string $email, int $id_evenement): bool {
        // Cette méthode sera implémentée via le contrôleur
        return false;
    }

    public function obtenirDetails(): array {
        return [
            'id' => $this->id_participation,
            'evenement' => $this->id_evenement,
            'gamer' => $this->id_gamer,
            'date' => $this->date_participation->format('Y-m-d H:i:s')
        ];
    }
}
?>