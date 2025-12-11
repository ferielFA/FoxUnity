<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../model/Comment.php';

/**
 * Class CommentController
 * 
 * Gère toutes les opérations CRUD et interactions sur les commentaires
 * Comprend: création, lecture, likes/dislikes, signalements, statistiques
 * 
 * @author FoxUnity Team
 * @version 1.0
 * @date 2025-11-26
 */
class CommentController {
    
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    // ==========================================
    // CRÉATION & LECTURE
    // ==========================================

    /**
     * Ajouter un nouveau commentaire avec notation
     * 
     * @param Comment $comment Objet commentaire à ajouter
     * @return bool Succès de l'opération
     */
    public function addComment(Comment $comment): bool {
        try {
            // Validation
            if (!$this->validateComment($comment)) {
                return false;
            }

            $sql = "INSERT INTO comment (id_evenement, user_id, user_name, user_email, content, rating) 
                    VALUES (:id_evenement, :user_id, :user_name, :user_email, :content, :rating)";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                ':id_evenement' => $comment->getIdEvenement(),
                ':user_id' => $comment->getUserId(),
                ':user_name' => $comment->getUserName(),
                ':user_email' => $comment->getUserEmail(),
                ':content' => $comment->getContent(),
                ':rating' => $comment->getRating()
            ]);

            if ($result) {
                $comment->setIdComment((int)$this->db->lastInsertId());
            }

            return $result;
        } catch (PDOException $e) {
            error_log("Erreur ajout commentaire: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupérer tous les commentaires d'un événement
     * 
     * @param int $eventId ID de l'événement
     * @param string $order Ordre de tri (newest, oldest, highest_rated, most_liked)
     * @return array Tableau d'objets Comment
     */
    public function getEventComments(int $eventId, string $order = 'newest'): array {
        try {
            // Déterminer l'ordre SQL
            $orderSQL = match($order) {
                'oldest' => 'created_at ASC',
                'highest_rated' => 'rating DESC, created_at DESC',
                'most_liked' => 'likes DESC, created_at DESC',
                default => 'created_at DESC' // newest
            };

            $sql = "SELECT * FROM comment 
                    WHERE id_evenement = :id_evenement 
                    ORDER BY $orderSQL";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id_evenement' => $eventId]);
            
            $comments = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $comments[] = $this->mapRowToComment($row);
            }
            
            return $comments;
        } catch (PDOException $e) {
            error_log("Erreur lecture commentaires: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer un commentaire par son ID
     * 
     * @param int $commentId ID du commentaire
     * @return Comment|null
     */
    public function getCommentById(int $commentId): ?Comment {
        try {
            $sql = "SELECT * FROM comment WHERE id_comment = :id_comment";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id_comment' => $commentId]);
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? $this->mapRowToComment($row) : null;
        } catch (PDOException $e) {
            error_log("Erreur lecture commentaire: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Compter les commentaires d'un événement
     * 
     * @param int $eventId ID de l'événement
     * @return int Nombre de commentaires
     */
    public function countEventComments(int $eventId): int {
        try {
            $sql = "SELECT COUNT(*) FROM comment WHERE id_evenement = :id_evenement";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id_evenement' => $eventId]);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Erreur comptage commentaires: " . $e->getMessage());
            return 0;
        }
    }

    // ==========================================
    // STATISTIQUES & NOTATIONS
    // ==========================================

    /**
     * Obtenir les statistiques de notation d'un événement
     * 
     * @param int $eventId ID de l'événement
     * @return array ['average' => float, 'total' => int, 'distribution' => array]
     */
    public function getEventRatingStats(int $eventId): array {
        try {
            $sql = "SELECT * FROM event_rating_stats WHERE id_evenement = :id_evenement";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id_evenement' => $eventId]);
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$row) {
                return [
                    'average' => 0.0,
                    'total' => 0,
                    'distribution' => [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0]
                ];
            }

            return [
                'average' => round((float)$row['average_rating'], 1),
                'total' => (int)$row['total_comments'],
                'distribution' => [
                    5 => (int)$row['five_stars'],
                    4 => (int)$row['four_stars'],
                    3 => (int)$row['three_stars'],
                    2 => (int)$row['two_stars'],
                    1 => (int)$row['one_star']
                ]
            ];
        } catch (PDOException $e) {
            error_log("Erreur statistiques notation: " . $e->getMessage());
            return [
                'average' => 0.0,
                'total' => 0,
                'distribution' => [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0]
            ];
        }
    }

    /**
     * Obtenir la note moyenne d'un événement (format simple)
     * 
     * @param int $eventId ID de l'événement
     * @return float Note moyenne (0.0 si aucune note)
     */
    public function getAverageRating(int $eventId): float {
        $stats = $this->getEventRatingStats($eventId);
        return $stats['average'];
    }

    // ==========================================
    // INTERACTIONS (LIKES / DISLIKES)
    // ==========================================

    /**
     * Liker un commentaire
     * 
     * @param int $commentId ID du commentaire
     * @param string $userEmail Email de l'utilisateur (temporaire)
     * @param int|null $userId ID de l'utilisateur (si connecté)
     * @return bool Succès de l'opération
     */
    public function likeComment(int $commentId, string $userEmail, ?int $userId = null): bool {
        try {
            // Vérifier si l'utilisateur a déjà interagi
            $existing = $this->getUserInteraction($commentId, $userEmail, $userId);

            if ($existing === 'like') {
                // Déjà liké → retirer le like
                return $this->removeInteraction($commentId, $userEmail, $userId);
            } elseif ($existing === 'dislike') {
                // Changer dislike en like
                return $this->updateInteraction($commentId, $userEmail, 'like', $userId);
            } else {
                // Nouveau like
                return $this->addInteraction($commentId, $userEmail, 'like', $userId);
            }
        } catch (PDOException $e) {
            error_log("Erreur like commentaire: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Disliker un commentaire
     * 
     * @param int $commentId ID du commentaire
     * @param string $userEmail Email de l'utilisateur (temporaire)
     * @param int|null $userId ID de l'utilisateur (si connecté)
     * @return bool Succès de l'opération
     */
    public function dislikeComment(int $commentId, string $userEmail, ?int $userId = null): bool {
        try {
            $existing = $this->getUserInteraction($commentId, $userEmail, $userId);

            if ($existing === 'dislike') {
                // Déjà disliké → retirer le dislike
                return $this->removeInteraction($commentId, $userEmail, $userId);
            } elseif ($existing === 'like') {
                // Changer like en dislike
                return $this->updateInteraction($commentId, $userEmail, 'dislike', $userId);
            } else {
                // Nouveau dislike
                return $this->addInteraction($commentId, $userEmail, 'dislike', $userId);
            }
        } catch (PDOException $e) {
            error_log("Erreur dislike commentaire: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtenir l'interaction actuelle d'un utilisateur sur un commentaire
     * 
     * @param int $commentId ID du commentaire
     * @param string $userEmail Email de l'utilisateur
     * @param int|null $userId ID de l'utilisateur (si connecté)
     * @return string|null 'like', 'dislike' ou null
     */
    public function getUserInteraction(int $commentId, string $userEmail, ?int $userId = null): ?string {
        try {
            if ($userId !== null) {
                $sql = "SELECT interaction_type FROM comment_interaction 
                        WHERE id_comment = :id_comment AND user_id = :user_id";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    ':id_comment' => $commentId,
                    ':user_id' => $userId
                ]);
            } else {
                $sql = "SELECT interaction_type FROM comment_interaction 
                        WHERE id_comment = :id_comment AND user_email = :user_email";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    ':id_comment' => $commentId,
                    ':user_email' => $userEmail
                ]);
            }
            
            $result = $stmt->fetchColumn();
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("Erreur lecture interaction: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Ajouter une interaction
     */
    private function addInteraction(int $commentId, string $userEmail, string $type, ?int $userId = null): bool {
        try {
            $this->db->beginTransaction();

            // Ajouter l'interaction
            $sql = "INSERT INTO comment_interaction (id_comment, user_id, user_email, interaction_type) 
                    VALUES (:id_comment, :user_id, :user_email, :type)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id_comment' => $commentId,
                ':user_id' => $userId,
                ':user_email' => $userEmail,
                ':type' => $type
            ]);

            // Mettre à jour le compteur
            $this->updateCommentCounters($commentId);

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur ajout interaction: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Modifier une interaction existante
     */
    private function updateInteraction(int $commentId, string $userEmail, string $type, ?int $userId = null): bool {
        try {
            $this->db->beginTransaction();

            if ($userId !== null) {
                $sql = "UPDATE comment_interaction 
                        SET interaction_type = :type 
                        WHERE id_comment = :id_comment AND user_id = :user_id";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    ':type' => $type,
                    ':id_comment' => $commentId,
                    ':user_id' => $userId
                ]);
            } else {
                $sql = "UPDATE comment_interaction 
                        SET interaction_type = :type 
                        WHERE id_comment = :id_comment AND user_email = :user_email";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    ':type' => $type,
                    ':id_comment' => $commentId,
                    ':user_email' => $userEmail
                ]);
            }

            $this->updateCommentCounters($commentId);

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur mise à jour interaction: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprimer une interaction
     */
    private function removeInteraction(int $commentId, string $userEmail, ?int $userId = null): bool {
        try {
            $this->db->beginTransaction();

            if ($userId !== null) {
                $sql = "DELETE FROM comment_interaction 
                        WHERE id_comment = :id_comment AND user_id = :user_id";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    ':id_comment' => $commentId,
                    ':user_id' => $userId
                ]);
            } else {
                $sql = "DELETE FROM comment_interaction 
                        WHERE id_comment = :id_comment AND user_email = :user_email";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    ':id_comment' => $commentId,
                    ':user_email' => $userEmail
                ]);
            }

            $this->updateCommentCounters($commentId);

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Erreur suppression interaction: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mettre à jour les compteurs likes/dislikes d'un commentaire
     */
    private function updateCommentCounters(int $commentId): void {
        $sql = "UPDATE comment 
                SET likes = (SELECT COUNT(*) FROM comment_interaction WHERE id_comment = :id1 AND interaction_type = 'like'),
                    dislikes = (SELECT COUNT(*) FROM comment_interaction WHERE id_comment = :id2 AND interaction_type = 'dislike')
                WHERE id_comment = :id3";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id1' => $commentId,
            ':id2' => $commentId,
            ':id3' => $commentId
        ]);
    }

    // ==========================================
    // SIGNALEMENT & MODÉRATION
    // ==========================================

    /**
     * Signaler un commentaire
     * 
     * @param int $commentId ID du commentaire
     * @param string $reason Raison du signalement
     * @return bool Succès de l'opération
     */
    public function reportComment(int $commentId, string $reason): bool {
        try {
            $sql = "UPDATE comment 
                    SET is_reported = TRUE, report_reason = :reason 
                    WHERE id_comment = :id_comment";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':reason' => $reason,
                ':id_comment' => $commentId
            ]);
        } catch (PDOException $e) {
            error_log("Erreur signalement commentaire: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupérer tous les commentaires signalés
     * 
     * @return array Tableau d'objets Comment
     */
    public function getReportedComments(): array {
        try {
            $sql = "SELECT * FROM comment WHERE is_reported = TRUE ORDER BY created_at DESC";
            $stmt = $this->db->query($sql);
            
            $comments = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $comments[] = $this->mapRowToComment($row);
            }
            
            return $comments;
        } catch (PDOException $e) {
            error_log("Erreur lecture commentaires signalés: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Annuler le signalement d'un commentaire (après modération)
     * 
     * @param int $commentId ID du commentaire
     * @return bool Succès de l'opération
     */
    public function unreportComment(int $commentId): bool {
        try {
            $sql = "UPDATE comment 
                    SET is_reported = FALSE, report_reason = NULL 
                    WHERE id_comment = :id_comment";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':id_comment' => $commentId]);
        } catch (PDOException $e) {
            error_log("Erreur annulation signalement: " . $e->getMessage());
            return false;
        }
    }

    // ==========================================
    // SUPPRESSION
    // ==========================================

    /**
     * Supprimer un commentaire
     * 
     * @param int $commentId ID du commentaire
     * @return bool Succès de l'opération
     */
    public function deleteComment(int $commentId): bool {
        try {
            $sql = "DELETE FROM comment WHERE id_comment = :id_comment";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([':id_comment' => $commentId]);
        } catch (PDOException $e) {
            error_log("Erreur suppression commentaire: " . $e->getMessage());
            return false;
        }
    }

    // ==========================================
    // VALIDATION & UTILITAIRES
    // ==========================================

    /**
     * Valider un commentaire avant insertion
     * 
     * @param Comment $comment Commentaire à valider
     * @return bool Validité du commentaire
     */
    private function validateComment(Comment $comment): bool {
        // Vérifier l'événement existe
        if ($comment->getIdEvenement() <= 0) {
            error_log("Validation échouée: ID événement invalide");
            return false;
        }

        // Vérifier nom utilisateur
        if (empty(trim($comment->getUserName())) || strlen($comment->getUserName()) < 2) {
            error_log("Validation échouée: Nom utilisateur invalide");
            return false;
        }

        // Vérifier email
        if (!filter_var($comment->getUserEmail(), FILTER_VALIDATE_EMAIL)) {
            error_log("Validation échouée: Email invalide");
            return false;
        }

        // Vérifier contenu
        if (empty(trim($comment->getContent())) || strlen($comment->getContent()) < 5) {
            error_log("Validation échouée: Contenu trop court");
            return false;
        }

        // Vérifier note
        if ($comment->getRating() < 1 || $comment->getRating() > 5) {
            error_log("Validation échouée: Note hors limites");
            return false;
        }

        return true;
    }

    /**
     * Mapper une ligne de base de données vers un objet Comment
     * 
     * @param array $row Ligne de résultat SQL
     * @return Comment
     */
    private function mapRowToComment(array $row): Comment {
        return new Comment(
            (int)$row['id_comment'],
            (int)$row['id_evenement'],
            isset($row['user_id']) ? (int)$row['user_id'] : null,
            $row['user_name'],
            $row['user_email'],
            $row['content'],
            (int)$row['rating'],
            (int)$row['likes'],
            (int)$row['dislikes'],
            (bool)$row['is_reported'],
            $row['report_reason'],
            new DateTime($row['created_at']),
            $row['updated_at'] ? new DateTime($row['updated_at']) : null
        );
    }

    /**
     * Vérifier si un utilisateur a déjà commenté un événement
     * 
     * @param int $eventId ID de l'événement
     * @param string $userEmail Email de l'utilisateur
     * @return bool
     */
    public function hasUserCommented(int $eventId, string $userEmail): bool {
        try {
            $sql = "SELECT COUNT(*) FROM comment 
                    WHERE id_evenement = :id_evenement AND user_email = :user_email";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id_evenement' => $eventId,
                ':user_email' => $userEmail
            ]);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Erreur vérification commentaire existant: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user's comment for a specific event
     * 
     * @param int $eventId
     * @param string $userEmail
     * @return array|null
     */
    public function getUserCommentForEvent(int $eventId, string $userEmail): ?array {
        try {
            $sql = "SELECT * FROM comment 
                    WHERE id_evenement = :id_evenement AND user_email = :user_email
                    LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id_evenement' => $eventId,
                ':user_email' => $userEmail
            ]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (PDOException $e) {
            error_log("Error getting user comment: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Update comment rating in real-time
     * 
     * @param int $commentId
     * @param int $rating
     * @return bool
     */
    public function updateCommentRating(int $commentId, int $rating): bool {
        try {
            // Validate rating
            if ($rating < 1 || $rating > 5) {
                return false;
            }

            $sql = "UPDATE comment SET rating = :rating, updated_at = CURRENT_TIMESTAMP 
                    WHERE id_comment = :id_comment";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                ':rating' => $rating,
                ':id_comment' => $commentId
            ]);
        } catch (PDOException $e) {
            error_log("Error updating rating: " . $e->getMessage());
            return false;
        }
    }
}
