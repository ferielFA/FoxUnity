<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../model/Participation.php';
require_once __DIR__ . '/TicketController.php';

class ParticipationController {
    private $db;
    private $ticketController;

    public function __construct() {
        $this->db = Database::getConnection();
        $this->ticketController = new TicketController();
    }

    public function inscrire(Participation $participation): bool {
        try {
            error_log("ParticipationController::inscrire() START");
            error_log("Email: " . $participation->getEmailParticipant());
            error_log("User ID: " . ($participation->getUserId() ?? 'NULL'));
            error_log("Event ID: " . $participation->getIdEvenement());
            
            // Check if already registered
            $alreadyRegistered = $this->verifierInscription($participation->getEmailParticipant(), $participation->getIdEvenement());
            error_log("Already registered check: " . ($alreadyRegistered ? "YES" : "NO"));
            
            if ($alreadyRegistered) {
                error_log("User already registered - returning false");
                return false;
            }

            error_log("Preparing INSERT query...");
            $sql = "INSERT INTO participation (id_evenement, user_id, nom_participant, email_participant, date_participation) 
                    VALUES (:id_evenement, :user_id, :nom_participant, :email_participant, :date_participation)";
            
            $stmt = $this->db->prepare($sql);
            $params = [
                ':id_evenement' => $participation->getIdEvenement(),
                ':user_id' => $participation->getUserId(),
                ':nom_participant' => $participation->getNomParticipant(),
                ':email_participant' => $participation->getEmailParticipant(),
                ':date_participation' => $participation->getDateParticipation()->format('Y-m-d H:i:s')
            ];
            
            error_log("Executing INSERT with params: " . json_encode($params));
            $executeResult = $stmt->execute($params);
            error_log("Execute result: " . ($executeResult ? "TRUE" : "FALSE"));
            
            // Get the newly created participation ID
            $idParticipation = (int)$this->db->lastInsertId();
            error_log("Last insert ID: " . $idParticipation);
            
            if ($idParticipation > 0) {
                // Automatically generate ticket for this participation
                error_log("Generating ticket for participation ID: " . $idParticipation);
                $this->ticketController->generateTicket($idParticipation, $participation->getIdEvenement());
                error_log("Ticket generated successfully");
                return true;
            } else {
                error_log("ERROR: Last insert ID is 0 or negative");
                return false;
            }
        } catch (PDOException $e) {
            error_log("PDOException in inscrire: " . $e->getMessage());
            error_log("SQL Error Code: " . $e->getCode());
            return false;
        } catch (Exception $e) {
            error_log("Exception in inscrire: " . $e->getMessage());
            return false;
        }
    }

    public function verifierInscription(string $email, int $id_evenement): bool {
        try {
            error_log("ParticipationController::verifierInscription() START");
            error_log("Checking email: '$email' for event ID: $id_evenement");
            
            $sql = "SELECT COUNT(*) FROM participation 
                    WHERE email_participant = :email AND id_evenement = :id_evenement";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':email' => $email,
                ':id_evenement' => $id_evenement
            ]);
            
            $count = $stmt->fetchColumn();
            error_log("Found $count existing registrations");
            
            return $count > 0;
        } catch (PDOException $e) {
            error_log("PDOException in verifierInscription: " . $e->getMessage());
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
                    $row['user_id'] ?? null,
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

    public function lireParUtilisateur(int $userId): array {
        try {
            $sql = "SELECT p.*, e.titre, e.date_debut, e.lieu 
                    FROM participation p 
                    INNER JOIN evenement e ON p.id_evenement = e.id_evenement 
                    WHERE p.user_id = :user_id
                    ORDER BY p.date_participation DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':user_id' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lecture participations par utilisateur: " . $e->getMessage());
            return [];
        }
    }
}