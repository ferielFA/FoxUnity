<?php
require_once __DIR__ . '/../config/config.php';

class Satisfaction {
    private $id_satisfaction;
    private $id_reclamation;
    private $email;
    private $rating; // 1-5 étoiles
    private $commentaire;
    private $date_evaluation;

    public function __construct($id_reclamation = null, $email = null, $rating = null, $commentaire = null) {
        $this->id_reclamation = $id_reclamation;
        $this->email = $email;
        $this->rating = $rating;
        $this->commentaire = $commentaire;
        $this->date_evaluation = date('Y-m-d H:i:s');
    }

    // Getters
    public function getIdSatisfaction() { return $this->id_satisfaction; }
    public function getIdReclamation() { return $this->id_reclamation; }
    public function getEmail() { return $this->email; }
    public function getRating() { return $this->rating; }
    public function getCommentaire() { return $this->commentaire; }
    public function getDateEvaluation() { return $this->date_evaluation; }

    // Setters
    public function setIdSatisfaction($id) { $this->id_satisfaction = $id; }
    public function setIdReclamation($id) { $this->id_reclamation = $id; }
    public function setEmail($email) { $this->email = $email; }
    public function setRating($rating) { $this->rating = $rating; }
    public function setCommentaire($commentaire) { $this->commentaire = $commentaire; }
    public function setDateEvaluation($date) { $this->date_evaluation = $date; }

    // Sauvegarder l'évaluation
    public function save() {
        $db = Config::getConnexion();
        try {
            if ($this->id_satisfaction) {
                // Mise à jour
                $query = $db->prepare(
                    'UPDATE satisfactions SET
                        id_reclamation = :id_reclamation,
                        email = :email,
                        rating = :rating,
                        commentaire = :commentaire
                    WHERE id_satisfaction = :id_satisfaction'
                );
                
                $result = $query->execute([
                    'id_reclamation' => $this->id_reclamation,
                    'email' => $this->email,
                    'rating' => $this->rating,
                    'commentaire' => $this->commentaire ?? null,
                    'id_satisfaction' => $this->id_satisfaction
                ]);
                
                return $result;
            } else {
                // Insertion
                $query = $db->prepare(
                    'INSERT INTO satisfactions (id_reclamation, email, rating, commentaire, date_evaluation) 
                     VALUES (:id_reclamation, :email, :rating, :commentaire, :date_evaluation)'
                );
                
                $result = $query->execute([
                    'id_reclamation' => $this->id_reclamation,
                    'email' => $this->email,
                    'rating' => $this->rating,
                    'commentaire' => $this->commentaire ?? null,
                    'date_evaluation' => $this->date_evaluation
                ]);
                
                if ($result) {
                    $this->id_satisfaction = $db->lastInsertId();
                    return $this->id_satisfaction;
                } else {
                    $errorInfo = $query->errorInfo();
                    $errorMsg = isset($errorInfo[2]) ? $errorInfo[2] : 'Erreur inconnue';
                    error_log('❌ Erreur Satisfaction::save() INSERT: ' . print_r($errorInfo, true));
                    error_log('❌ Données tentées: id_reclamation=' . $this->id_reclamation . ', email=' . substr($this->email, 0, 30) . ', rating=' . $this->rating);
                    // Retourner l'erreur pour un meilleur débogage
                    throw new Exception('Erreur INSERT: ' . $errorMsg);
                }
            }
        } catch (PDOException $e) {
            $errorMsg = $e->getMessage();
            error_log('❌ Erreur Satisfaction::save(): ' . $errorMsg);
            
            // Vérifier si c'est une erreur de table inexistante
            if (strpos($errorMsg, "doesn't exist") !== false || 
                strpos($errorMsg, "n'existe pas") !== false ||
                strpos($errorMsg, "Table") !== false && strpos($errorMsg, "unknown") !== false) {
                error_log('⚠️ La table satisfactions n\'existe pas. Exécutez le script SQL: .vscode/database/create_satisfactions_table.sql');
            }
            
            return false;
        }
    }

    // Trouver une évaluation par ID de réclamation (première trouvée - pour compatibilité)
    public static function findByReclamationId($id_reclamation) {
        $db = Config::getConnexion();
        try {
            $query = $db->prepare('SELECT * FROM satisfactions WHERE id_reclamation = :id_reclamation LIMIT 1');
            $query->execute(['id_reclamation' => $id_reclamation]);
            $data = $query->fetch(PDO::FETCH_ASSOC);
            
            if ($data) {
                $satisfaction = new Satisfaction();
                $satisfaction->id_satisfaction = $data['id_satisfaction'];
                $satisfaction->id_reclamation = $data['id_reclamation'];
                $satisfaction->email = $data['email'];
                $satisfaction->rating = $data['rating'];
                $satisfaction->commentaire = $data['commentaire'];
                $satisfaction->date_evaluation = $data['date_evaluation'];
                return $satisfaction;
            }
            return null;
        } catch (PDOException $e) {
            error_log('❌ Erreur Satisfaction::findByReclamationId(): ' . $e->getMessage());
            return null;
        }
    }
    
    // Trouver une évaluation par ID de réclamation ET email (pour vérifier si un utilisateur a déjà évalué)
    public static function findByReclamationIdAndEmail($id_reclamation, $email) {
        $db = Config::getConnexion();
        try {
            $query = $db->prepare('SELECT * FROM satisfactions WHERE id_reclamation = :id_reclamation AND email = :email LIMIT 1');
            $query->execute([
                'id_reclamation' => $id_reclamation,
                'email' => $email
            ]);
            $data = $query->fetch(PDO::FETCH_ASSOC);
            
            if ($data) {
                $satisfaction = new Satisfaction();
                $satisfaction->id_satisfaction = $data['id_satisfaction'];
                $satisfaction->id_reclamation = $data['id_reclamation'];
                $satisfaction->email = $data['email'];
                $satisfaction->rating = $data['rating'];
                $satisfaction->commentaire = $data['commentaire'];
                $satisfaction->date_evaluation = $data['date_evaluation'];
                return $satisfaction;
            }
            return null;
        } catch (PDOException $e) {
            error_log('❌ Erreur Satisfaction::findByReclamationIdAndEmail(): ' . $e->getMessage());
            return null;
        }
    }
    
    // Trouver toutes les évaluations d'une réclamation
    public static function findAllByReclamationId($id_reclamation) {
        $db = Config::getConnexion();
        try {
            $query = $db->prepare('SELECT * FROM satisfactions WHERE id_reclamation = :id_reclamation ORDER BY date_evaluation DESC');
            $query->execute(['id_reclamation' => $id_reclamation]);
            $results = $query->fetchAll(PDO::FETCH_ASSOC);
            
            $satisfactions = [];
            foreach ($results as $data) {
                $satisfaction = new Satisfaction();
                $satisfaction->id_satisfaction = $data['id_satisfaction'];
                $satisfaction->id_reclamation = $data['id_reclamation'];
                $satisfaction->email = $data['email'];
                $satisfaction->rating = $data['rating'];
                $satisfaction->commentaire = $data['commentaire'];
                $satisfaction->date_evaluation = $data['date_evaluation'];
                $satisfactions[] = $satisfaction;
            }
            return $satisfactions;
        } catch (PDOException $e) {
            error_log('❌ Erreur Satisfaction::findAllByReclamationId(): ' . $e->getMessage());
            return [];
        }
    }

    // Obtenir toutes les évaluations
    public static function findAll() {
        $db = Config::getConnexion();
        try {
            $query = $db->query('SELECT * FROM satisfactions ORDER BY date_evaluation DESC');
            $results = $query->fetchAll(PDO::FETCH_ASSOC);
            
            $satisfactions = [];
            foreach ($results as $data) {
                $satisfaction = new Satisfaction();
                $satisfaction->id_satisfaction = $data['id_satisfaction'];
                $satisfaction->id_reclamation = $data['id_reclamation'];
                $satisfaction->email = $data['email'];
                $satisfaction->rating = $data['rating'];
                $satisfaction->commentaire = $data['commentaire'];
                $satisfaction->date_evaluation = $data['date_evaluation'];
                $satisfactions[] = $satisfaction;
            }
            return $satisfactions;
        } catch (PDOException $e) {
            error_log('❌ Erreur Satisfaction::findAll(): ' . $e->getMessage());
            return [];
        }
    }

    // Obtenir les statistiques
    public static function getStats() {
        $db = Config::getConnexion();
        try {
            $stats = [];
            
            // Nombre total d'évaluations
            $query = $db->query('SELECT COUNT(*) as total FROM satisfactions');
            $stats['total'] = $query->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Note moyenne
            $query = $db->query('SELECT AVG(rating) as moyenne FROM satisfactions');
            $stats['moyenne'] = round($query->fetch(PDO::FETCH_ASSOC)['moyenne'], 2);
            
            // Répartition par note
            $query = $db->query('SELECT rating, COUNT(*) as count FROM satisfactions GROUP BY rating ORDER BY rating DESC');
            $stats['repartition'] = $query->fetchAll(PDO::FETCH_ASSOC);
            
            // Pourcentage de satisfaction (4-5 étoiles)
            $query = $db->query('SELECT COUNT(*) as satisfied FROM satisfactions WHERE rating >= 4');
            $satisfied = $query->fetch(PDO::FETCH_ASSOC)['satisfied'];
            $stats['pourcentage_satisfait'] = $stats['total'] > 0 ? round(($satisfied / $stats['total']) * 100, 2) : 0;
            
            return $stats;
        } catch (PDOException $e) {
            error_log('❌ Erreur Satisfaction::getStats(): ' . $e->getMessage());
            return [
                'total' => 0,
                'moyenne' => 0,
                'repartition' => [],
                'pourcentage_satisfait' => 0
            ];
        }
    }
}
?>

