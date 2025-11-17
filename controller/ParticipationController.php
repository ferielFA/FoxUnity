<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../model/Participation.php';

class ParticipationController {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function inscrire(Participation $participation): bool {
        try {
            // Check if already registered
            if ($this->verifierInscription($participation->getEmailParticipant(), $participation->getIdEvenement())) {
                return false;
            }

            $sql = "INSERT INTO participation (id_evenement, nom_participant, email_participant, date_participation) 
                    VALUES (:id_evenement, :nom_participant, :email_participant, :date_participation)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id_evenement' => $participation->getIdEvenement(),
                ':nom_participant' => $participation->getNomParticipant(),
                ':email_participant' => $participation->getEmailParticipant(),
                ':date_participation' => $participation->getDateParticipation()->format('Y-m-d H:i:s')
            ]);
            
            return true;
        } catch (PDOException $e) {
            error_log("Erreur inscription: " . $e->getMessage());
            return false;
        }
    }

    public function verifierInscription(string $email, int $id_evenement): bool {
        try {
            $sql = "SELECT COUNT(*) FROM participation 
                    WHERE email_participant = :email AND id_evenement = :id_evenement";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':email' => $email,
                ':id_evenement' => $id_evenement
            ]);
            
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Erreur vérification: " . $e->getMessage());
            return false;
        }
    }

    public function lireParEvenement(int $id_evenement): array {
        try {
            $sql = "SELECT * FROM participation WHERE id_evenement = :id_evenement 
                    ORDER BY date_participation DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id_evenement' => $id_evenement]);
            
            $results = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $results[] = new Participation(
                    $row['id_participation'],
                    $row['id_evenement'],
                    $row['nom_participant'],
                    $row['email_participant'],
                    new DateTime($row['date_participation'])
                );
            }
            
            return $results;
        } catch (PDOException $e) {
            error_log("Erreur lecture participations: " . $e->getMessage());
            return [];
        }
    }

    public function desinscrire(string $email, int $id_evenement): bool {
        try {
            $sql = "DELETE FROM participation 
                    WHERE email_participant = :email AND id_evenement = :id_evenement";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':email' => $email,
                ':id_evenement' => $id_evenement
            ]);
            return true;
        } catch (PDOException $e) {
            error_log("Erreur désinscription: " . $e->getMessage());
            return false;
        }
    }

    public function lireTous(): array {
        try {
            $sql = "SELECT p.*, e.titre 
                    FROM participation p 
                    INNER JOIN evenement e ON p.id_evenement = e.id_evenement 
                    ORDER BY p.date_participation DESC";
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lecture participations: " . $e->getMessage());
            return [];
        }
    }
}
