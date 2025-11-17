<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../model/Evenement.php';

class EvenementController {
    private $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function creer(Evenement $evenement): bool {
        try {
            $sql = "INSERT INTO evenement (titre, description, date_debut, date_fin, lieu, statut) 
                    VALUES (:titre, :description, :date_debut, :date_fin, :lieu, :statut)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':titre' => $evenement->getTitre(),
                ':description' => $evenement->getDescription(),
                ':date_debut' => $evenement->getDateDebut()->format('Y-m-d H:i:s'),
                ':date_fin' => $evenement->getDateFin()->format('Y-m-d H:i:s'),
                ':lieu' => $evenement->getLieu(),
                ':statut' => $evenement->getStatut()
            ]);
            
            return true;
        } catch (PDOException $e) {
            error_log("Erreur création événement: " . $e->getMessage());
            return false;
        }
    }

    public function lireTous(): array {
        try {
            $sql = "SELECT e.*, COUNT(p.id_participation) as nb_participants 
                    FROM evenement e 
                    LEFT JOIN participation p ON e.id_evenement = p.id_evenement 
                    GROUP BY e.id_evenement 
                    ORDER BY e.date_debut ASC";
            
            $stmt = $this->db->query($sql);
            $results = [];
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $evenement = new Evenement(
                    $row['id_evenement'],
                    $row['titre'],
                    $row['description'],
                    new DateTime($row['date_debut']),
                    new DateTime($row['date_fin']),
                    $row['lieu'],
                    $row['statut']
                );
                
                $results[] = [
                    'evenement' => $evenement,
                    'nb_participants' => (int)$row['nb_participants']
                ];
            }
            
            return $results;
        } catch (PDOException $e) {
            error_log("Erreur lecture événements: " . $e->getMessage());
            return [];
        }
    }

    public function lireParId(int $id): ?Evenement {
        try {
            $sql = "SELECT * FROM evenement WHERE id_evenement = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                return new Evenement(
                    $row['id_evenement'],
                    $row['titre'],
                    $row['description'],
                    new DateTime($row['date_debut']),
                    new DateTime($row['date_fin']),
                    $row['lieu'],
                    $row['statut']
                );
            }
            
            return null;
        } catch (PDOException $e) {
            error_log("Erreur lecture événement: " . $e->getMessage());
            return null;
        }
    }

    public function modifier(Evenement $evenement): bool {
        try {
            $sql = "UPDATE evenement 
                    SET titre = :titre, description = :description, date_debut = :date_debut, 
                        date_fin = :date_fin, lieu = :lieu, statut = :statut 
                    WHERE id_evenement = :id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id' => $evenement->getIdEvenement(),
                ':titre' => $evenement->getTitre(),
                ':description' => $evenement->getDescription(),
                ':date_debut' => $evenement->getDateDebut()->format('Y-m-d H:i:s'),
                ':date_fin' => $evenement->getDateFin()->format('Y-m-d H:i:s'),
                ':lieu' => $evenement->getLieu(),
                ':statut' => $evenement->getStatut()
            ]);
            
            return true;
        } catch (PDOException $e) {
            error_log("Erreur modification événement: " . $e->getMessage());
            return false;
        }
    }

    public function supprimer(int $id): bool {
        try {
            $sql = "DELETE FROM evenement WHERE id_evenement = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $id]);
            return true;
        } catch (PDOException $e) {
            error_log("Erreur suppression événement: " . $e->getMessage());
            return false;
        }
    }
}
