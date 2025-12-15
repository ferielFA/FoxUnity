<?php
require_once __DIR__ . '/../config/config.php';

class Response {
    private $id_reponse;
    private $id_reclamation;
    private $message;
    private $date_reponse;
    private $id_admin;
    private $statut_reponse;

    public function __construct($id_reclamation = null, $message = null, $admin_name = 'Admin', $date_reponse = null) {
        $this->id_reclamation = $id_reclamation;
        $this->message = $message;
        $this->id_admin = 1; // Valeur par défaut pour l'admin (peut être ajustée)
        $this->date_reponse = $date_reponse ? $date_reponse : date('Y-m-d H:i:s');
        $this->statut_reponse = 'sent'; // Valeur par défaut
    }

    // Getters
    public function getIdResponse() { return $this->id_reponse; }
    public function getIdReclamation() { return $this->id_reclamation; }
    public function getMessage() { return $this->message; }
    public function getDateCreation() { return $this->date_reponse; }
    public function getIdAdmin() { return $this->id_admin; }
    public function getStatutReponse() { return $this->statut_reponse; }
    
    // Getters pour compatibilité (aliases)
    public function getResponseText() { return $this->message; }
    public function getAdminName() { return 'Admin'; } // Pour compatibilité, retourne toujours 'Admin'

    // Setters
    public function setIdResponse($id) { $this->id_reponse = $id; }
    public function setIdReclamation($id) { $this->id_reclamation = $id; }
    public function setMessage($text) { $this->message = $text; }
    public function setIdAdmin($id) { $this->id_admin = $id; }
    public function setStatutReponse($statut) { $this->statut_reponse = $statut; }
    
    // Setters pour compatibilité (aliases)
    public function setResponseText($text) { $this->message = $text; }
    public function setAdminName($name) { $this->id_admin = 1; } // Pour compatibilité

    // Méthodes CRUD pour la connexion à la base de données
    public function save() {
        $db = Config::getConnexion();
        try {
            if ($this->id_reponse) {
                // Mise à jour
                $query = $db->prepare(
                    'UPDATE reponses SET
                        message = :message,
                        id_admin = :id_admin,
                        statut_reponse = :statut_reponse
                    WHERE id_reponse = :id_reponse'
                );
                
                $result = $query->execute([
                    'message' => $this->message,
                    'id_admin' => $this->id_admin,
                    'statut_reponse' => $this->statut_reponse,
                    'id_reponse' => $this->id_reponse
                ]);
                
                return $result;
            } else {
                // Insertion
                $query = $db->prepare(
                    'INSERT INTO reponses (id_reclamation, id_admin, message, date_reponse, statut_reponse) 
                     VALUES (:id_reclamation, :id_admin, :message, :date_reponse, :statut_reponse)'
                );
                
                $result = $query->execute([
                    'id_reclamation' => $this->id_reclamation,
                    'id_admin' => $this->id_admin,
                    'message' => $this->message,
                    'date_reponse' => $this->date_reponse,
                    'statut_reponse' => $this->statut_reponse
                ]);
                
                if ($result) {
                    $this->id_reponse = $db->lastInsertId();
                    return $this->id_reponse;
                }
                return false;
            }
        } catch (PDOException $e) {
            error_log('❌ Erreur Response::save(): ' . $e->getMessage());
            return false;
        }
    }

    public function delete() {
        if (!$this->id_reponse) {
            return false;
        }
        
        $db = Config::getConnexion();
        try {
            $query = $db->prepare('DELETE FROM reponses WHERE id_reponse = :id_reponse');
            $result = $query->execute(['id_reponse' => $this->id_reponse]);
            return $result;
        } catch (PDOException $e) {
            error_log('❌ Erreur Response::delete(): ' . $e->getMessage());
            return false;
        }
    }

    public static function findById($id) {
        $db = Config::getConnexion();
        try {
            $query = $db->prepare('SELECT * FROM reponses WHERE id_reponse = :id');
            $query->execute(['id' => $id]);
            $data = $query->fetch(PDO::FETCH_ASSOC);
            
            if ($data) {
                $response = new Response();
                $response->id_reponse = $data['id_reponse'];
                $response->id_reclamation = $data['id_reclamation'];
                $response->message = $data['message'];
                $response->date_reponse = $data['date_reponse'];
                $response->id_admin = $data['id_admin'];
                $response->statut_reponse = $data['statut_reponse'] ?? 'sent';
                return $response;
            }
            return null;
        } catch (PDOException $e) {
            error_log('❌ Erreur Response::findById(): ' . $e->getMessage());
            return null;
        }
    }

    public static function findAll() {
        $db = Config::getConnexion();
        try {
            $query = $db->query('SELECT * FROM reponses ORDER BY date_reponse DESC');
            $results = $query->fetchAll(PDO::FETCH_ASSOC);
            
            $responses = [];
            foreach ($results as $data) {
                $response = new Response();
                $response->id_reponse = $data['id_reponse'];
                $response->id_reclamation = $data['id_reclamation'];
                $response->message = $data['message'];
                $response->date_reponse = $data['date_reponse'];
                $response->id_admin = $data['id_admin'];
                $response->statut_reponse = $data['statut_reponse'] ?? 'sent';
                $responses[] = $response;
            }
            return $responses;
        } catch (PDOException $e) {
            error_log('❌ Erreur Response::findAll(): ' . $e->getMessage());
            return [];
        }
    }

    public static function findByReclamationId($id_reclamation) {
        $db = Config::getConnexion();
        try {
            $query = $db->prepare('SELECT * FROM reponses WHERE id_reclamation = :id_reclamation ORDER BY date_reponse DESC');
            $query->execute(['id_reclamation' => $id_reclamation]);
            $results = $query->fetchAll(PDO::FETCH_ASSOC);
            
            $responses = [];
            foreach ($results as $data) {
                $response = new Response();
                $response->id_reponse = $data['id_reponse'];
                $response->id_reclamation = $data['id_reclamation'];
                $response->message = $data['message'];
                $response->date_reponse = $data['date_reponse'];
                $response->id_admin = $data['id_admin'];
                $response->statut_reponse = $data['statut_reponse'] ?? 'sent';
                $responses[] = $response;
            }
            return $responses;
        } catch (PDOException $e) {
            error_log('❌ Erreur Response::findByReclamationId(): ' . $e->getMessage());
            return [];
        }
    }
}
?>

