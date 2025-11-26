<?php

/**
 * Class Comment
 * 
 * Représente un commentaire avec notation sur un événement
 * Gère les avis, notes (1-5 étoiles), likes/dislikes et signalements
 * 
 * @author FoxUnity Team
 * @version 1.0
 * @date 2025-11-26
 */
class Comment {
    
    // Propriétés privées (Encapsulation POO)
    private ?int $idComment;
    private int $idEvenement;
    private string $userName;
    private string $userEmail;
    private string $content;
    private int $rating;
    private int $likes;
    private int $dislikes;
    private bool $isReported;
    private ?string $reportReason;
    private DateTime $createdAt;
    private ?DateTime $updatedAt;

    /**
     * Constructeur
     * 
     * @param int|null $idComment ID du commentaire (null pour nouveau commentaire)
     * @param int $idEvenement ID de l'événement commenté
     * @param string $userName Nom de l'utilisateur (temporaire)
     * @param string $userEmail Email de l'utilisateur (temporaire)
     * @param string $content Contenu du commentaire
     * @param int $rating Note donnée (1-5)
     * @param int $likes Nombre de likes (défaut: 0)
     * @param int $dislikes Nombre de dislikes (défaut: 0)
     * @param bool $isReported Commentaire signalé ? (défaut: false)
     * @param string|null $reportReason Raison du signalement
     * @param DateTime|null $createdAt Date de création
     * @param DateTime|null $updatedAt Date de modification
     */
    public function __construct(
        ?int $idComment = null,
        int $idEvenement = 0,
        string $userName = '',
        string $userEmail = '',
        string $content = '',
        int $rating = 5,
        int $likes = 0,
        int $dislikes = 0,
        bool $isReported = false,
        ?string $reportReason = null,
        ?DateTime $createdAt = null,
        ?DateTime $updatedAt = null
    ) {
        $this->idComment = $idComment;
        $this->idEvenement = $idEvenement;
        $this->userName = $userName;
        $this->userEmail = $userEmail;
        $this->content = $content;
        $this->rating = $rating;
        $this->likes = $likes;
        $this->dislikes = $dislikes;
        $this->isReported = $isReported;
        $this->reportReason = $reportReason;
        $this->createdAt = $createdAt ?? new DateTime();
        $this->updatedAt = $updatedAt;
    }

    // ==========================================
    // GETTERS
    // ==========================================

    public function getIdComment(): ?int {
        return $this->idComment;
    }

    public function getIdEvenement(): int {
        return $this->idEvenement;
    }

    public function getUserName(): string {
        return $this->userName;
    }

    public function getUserEmail(): string {
        return $this->userEmail;
    }

    public function getContent(): string {
        return $this->content;
    }

    public function getRating(): int {
        return $this->rating;
    }

    public function getLikes(): int {
        return $this->likes;
    }

    public function getDislikes(): int {
        return $this->dislikes;
    }

    public function getIsReported(): bool {
        return $this->isReported;
    }

    public function getReportReason(): ?string {
        return $this->reportReason;
    }

    public function getCreatedAt(): DateTime {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTime {
        return $this->updatedAt;
    }

    // ==========================================
    // SETTERS
    // ==========================================

    public function setIdComment(?int $idComment): void {
        $this->idComment = $idComment;
    }

    public function setIdEvenement(int $idEvenement): void {
        $this->idEvenement = $idEvenement;
    }

    public function setUserName(string $userName): void {
        $this->userName = $userName;
    }

    public function setUserEmail(string $userEmail): void {
        $this->userEmail = $userEmail;
    }

    public function setContent(string $content): void {
        $this->content = $content;
    }

    public function setRating(int $rating): void {
        if ($rating < 1 || $rating > 5) {
            throw new InvalidArgumentException("La note doit être entre 1 et 5");
        }
        $this->rating = $rating;
    }

    public function setLikes(int $likes): void {
        $this->likes = max(0, $likes);
    }

    public function setDislikes(int $dislikes): void {
        $this->dislikes = max(0, $dislikes);
    }

    public function setIsReported(bool $isReported): void {
        $this->isReported = $isReported;
    }

    public function setReportReason(?string $reportReason): void {
        $this->reportReason = $reportReason;
    }

    public function setCreatedAt(DateTime $createdAt): void {
        $this->createdAt = $createdAt;
    }

    public function setUpdatedAt(?DateTime $updatedAt): void {
        $this->updatedAt = $updatedAt;
    }

    // ==========================================
    // MÉTHODES UTILITAIRES
    // ==========================================

    /**
     * Obtenir les initiales de l'utilisateur pour l'avatar
     * Exemple: "Alice Martin" → "AM"
     * 
     * @return string
     */
    public function getUserInitials(): string {
        $parts = explode(' ', trim($this->userName));
        if (count($parts) >= 2) {
            return strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1));
        }
        return strtoupper(substr($this->userName, 0, 2));
    }

    /**
     * Obtenir le temps écoulé depuis la création du commentaire
     * Exemple: "il y a 2 heures", "il y a 3 jours"
     * 
     * @return string
     */
    public function getTimeAgo(): string {
        $now = new DateTime();
        $diff = $now->diff($this->createdAt);

        if ($diff->y > 0) {
            return $diff->y . ' an' . ($diff->y > 1 ? 's' : '');
        }
        if ($diff->m > 0) {
            return $diff->m . ' mois';
        }
        if ($diff->d > 0) {
            return $diff->d . ' jour' . ($diff->d > 1 ? 's' : '');
        }
        if ($diff->h > 0) {
            return $diff->h . ' heure' . ($diff->h > 1 ? 's' : '');
        }
        if ($diff->i > 0) {
            return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '');
        }
        return 'À l\'instant';
    }

    /**
     * Obtenir les étoiles en format texte
     * Exemple: 4 → "★★★★☆"
     * 
     * @return string
     */
    public function getStarsDisplay(): string {
        $filled = str_repeat('★', $this->rating);
        $empty = str_repeat('☆', 5 - $this->rating);
        return $filled . $empty;
    }

    /**
     * Calculer le score net (likes - dislikes)
     * 
     * @return int
     */
    public function getNetScore(): int {
        return $this->likes - $this->dislikes;
    }

    /**
     * Vérifier si le commentaire est récent (moins de 24h)
     * 
     * @return bool
     */
    public function isRecent(): bool {
        $now = new DateTime();
        $diff = $now->diff($this->createdAt);
        return $diff->days < 1;
    }

    /**
     * Obtenir un extrait du contenu (pour les aperçus)
     * 
     * @param int $maxLength Longueur maximale
     * @return string
     */
    public function getExcerpt(int $maxLength = 150): string {
        if (strlen($this->content) <= $maxLength) {
            return $this->content;
        }
        return substr($this->content, 0, $maxLength) . '...';
    }

    /**
     * Vérifier si le contenu contient des mots inappropriés
     * (Simple vérification - peut être améliorée)
     * 
     * @return bool
     */
    public function containsInappropriateContent(): bool {
        $badWords = ['spam', 'arnaque', 'fake', 'scam'];
        $lowerContent = strtolower($this->content);
        
        foreach ($badWords as $word) {
            if (strpos($lowerContent, $word) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Incrémenter le nombre de likes
     */
    public function incrementLikes(): void {
        $this->likes++;
    }

    /**
     * Décrémenter le nombre de likes
     */
    public function decrementLikes(): void {
        $this->likes = max(0, $this->likes - 1);
    }

    /**
     * Incrémenter le nombre de dislikes
     */
    public function incrementDislikes(): void {
        $this->dislikes++;
    }

    /**
     * Décrémenter le nombre de dislikes
     */
    public function decrementDislikes(): void {
        $this->dislikes = max(0, $this->dislikes - 1);
    }

    /**
     * Signaler le commentaire
     * 
     * @param string $reason Raison du signalement
     */
    public function report(string $reason): void {
        $this->isReported = true;
        $this->reportReason = $reason;
    }

    /**
     * Annuler le signalement
     */
    public function unreport(): void {
        $this->isReported = false;
        $this->reportReason = null;
    }

    /**
     * Convertir l'objet en tableau associatif
     * 
     * @return array
     */
    public function toArray(): array {
        return [
            'id_comment' => $this->idComment,
            'id_evenement' => $this->idEvenement,
            'user_name' => $this->userName,
            'user_email' => $this->userEmail,
            'content' => $this->content,
            'rating' => $this->rating,
            'likes' => $this->likes,
            'dislikes' => $this->dislikes,
            'is_reported' => $this->isReported,
            'report_reason' => $this->reportReason,
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt ? $this->updatedAt->format('Y-m-d H:i:s') : null,
            'time_ago' => $this->getTimeAgo(),
            'stars_display' => $this->getStarsDisplay(),
            'net_score' => $this->getNetScore(),
            'user_initials' => $this->getUserInitials()
        ];
    }

    /**
     * Représentation en chaîne de caractères
     * 
     * @return string
     */
    public function __toString(): string {
        return sprintf(
            "[Comment #%d] %s (%d★) - %s - par %s",
            $this->idComment ?? 0,
            $this->getExcerpt(50),
            $this->rating,
            $this->getTimeAgo(),
            $this->userName
        );
    }
}
