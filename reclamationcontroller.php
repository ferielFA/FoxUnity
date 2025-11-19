<?php 
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Reclamation.php';

class ReclamationController {
    public function updateReclamation($reclamation) {   
    try {
        $db = Config::getConnexion();
        $query = $db->prepare(
            'UPDATE reclamations SET
                full_name = :full_name,
                email = :email,
                subject = :subject,
                message = :message,
                statut = :statut
            WHERE id_reclamation = :id_reclamation'
        );
        
        $result = $query->execute([
            'full_name' => $reclamation->getFullName(),
            'email' => $reclamation->getEmail(),
            'subject' => $reclamation->getSubject(),
            'message' => $reclamation->getMessage(),
            'statut' => $reclamation->getStatut(),
            'id_reclamation' => $reclamation->getIdReclamation()
        ]);
        
        return $result;
    } catch (PDOException $e) {
        error_log(' Erreur updateReclamation: ' . $e->getMessage());
        return false;
    }
}
    public function addReclamation($reclamation) {
        $sql = "INSERT INTO reclamations (full_name, email, subject, message, date_creation, statut) 
                VALUES (:full_name, :email, :subject, :message, :date_creation, :statut)";
        $db = Config::getConnexion();
        try {
            $query = $db->prepare($sql);
            $result = $query->execute([
                'full_name' => $reclamation->getFullName(),
                'email' => $reclamation->getEmail(),
                'subject' => $reclamation->getSubject(),
                'message' => $reclamation->getMessage(),
                'date_creation' => $reclamation->getDateCreation(),
                'statut' => $reclamation->getStatut()
            ]);
            
            if ($result) {
                return $db->lastInsertId();
            } else {
                error_log(" Erreur lors de l'insertion PDO");
                return false;
            }
        } catch (Exception $e) {
            error_log(' Erreur addReclamation: ' . $e->getMessage());
            return false;
        }
    }

    public function deleteReclamation($reclamationId) {
        // Instead of deleting the record, mark it as archived so it remains stored
        $sql = "UPDATE reclamations SET statut = :statut WHERE id_reclamation = :id_reclamation";
        $db = Config::getConnexion();
        try {
            $query = $db->prepare($sql);
            $result = $query->execute(['statut' => 'archived', 'id_reclamation' => $reclamationId]);
            return $result;
        } catch (Exception $e) {
            error_log(' Erreur deleteReclamation (archive): ' . $e->getMessage());
            return false;
        }
    }

    public function getReclamationsByEmail($email) {
        $sql = "SELECT * FROM reclamations WHERE email = :email ORDER BY date_creation DESC";
        $db = Config::getConnexion();
        try {
            $query = $db->prepare($sql);
            $query->execute(['email' => $email]);
            return $query->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log(' Erreur getReclamationsByEmail: ' . $e->getMessage());
            return [];
        }
    }

    public function getReclamationById($id) {
        $sql = "SELECT * FROM reclamations WHERE id_reclamation = :id";
        $db = Config::getConnexion();
        try {
            $query = $db->prepare($sql);
            $query->execute(['id' => $id]);
            return $query->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('❌ Erreur getReclamationById: ' . $e->getMessage());
            return false;
        }
    }

    public function getAllReclamations() {
        $sql = "SELECT * FROM reclamations ORDER BY date_creation DESC";
        $db = Config::getConnexion();
        try {
            $query = $db->query($sql);
            return $query->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log(' Erreur getAllReclamations: ' . $e->getMessage());
            return [];
        }
    }
}
?>