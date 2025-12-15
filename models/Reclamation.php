<?php
require_once __DIR__ . '/../config/config.php';

class Reclamation {
    private $id_reclamation;
    private $id_utilisateur;
    private $email;
    private $sujet;
    private $description;
    private $date_creation;
    private $statut;
    private $piece_jointe;
    private $categorie;

    public function __construct($email = null, $sujet = null, $description = null, $id_utilisateur = null, $statut = 'nouveau', $categorie = 'Other') {
        $this->id_utilisateur = $id_utilisateur;
        $this->email = $email;
        $this->sujet = $sujet;
        $this->description = $description;
        $this->date_creation = date('Y-m-d H:i:s');
        $this->statut = $statut; // Par défaut 'nouveau' pour les nouvelles réclamations
        $this->categorie = $categorie; // Par défaut 'Other'
    }

    // Getters
    public function getIdReclamation() { return $this->id_reclamation; }
    public function getIdUtilisateur() { return $this->id_utilisateur; }
    public function getEmail() { return $this->email; }
    public function getSujet() { return $this->sujet; }
    public function getDescription() { return $this->description; }
    public function getDateCreation() { return $this->date_creation; }
    public function getStatut() { return $this->statut; }
    public function getPieceJointe() { return $this->piece_jointe; }
    public function getCategorie() { return $this->categorie; }
    
    // Getters pour compatibilité (aliases)
    public function getSubject() { return $this->sujet; }
    public function getMessage() { return $this->description; }
    public function getFullName() { return null; } // N'existe plus dans la table

    // Setters
    public function setIdReclamation($id) { $this->id_reclamation = $id; }
    public function setIdUtilisateur($id) { $this->id_utilisateur = $id; }
    public function setEmail($email) { $this->email = $email; }
    public function setSujet($sujet) { $this->sujet = $sujet; }
    public function setDescription($description) { $this->description = $description; }
    public function setStatut($statut) { $this->statut = $statut; }
    public function setPieceJointe($piece) { $this->piece_jointe = $piece; }
    public function setCategorie($categorie) { $this->categorie = $categorie; }
    
    // Setters pour compatibilité (aliases)
    public function setSubject($subject) { $this->sujet = $subject; }
    public function setMessage($message) { $this->description = $message; }

    // Méthodes CRUD pour la connexion à la base de données
    public function save() {
        $db = Config::getConnexion();
        try {
            if ($this->id_reclamation) {
                // Mise à jour
                $query = $db->prepare(
                    'UPDATE reclamations SET
                        id_utilisateur = :id_utilisateur,
                        email = :email,
                        sujet = :sujet,
                        description = :description,
                        statut = :statut
                    WHERE id_reclamation = :id_reclamation'
                );
                
                $result = $query->execute([
                    'id_utilisateur' => $this->id_utilisateur,
                    'email' => $this->email,
                    'sujet' => $this->sujet,
                    'description' => $this->description,
                    'statut' => $this->statut,
                    'id_reclamation' => $this->id_reclamation
                ]);
                
                return $result;
            } else {
                // Insertion
                $query = $db->prepare(
                    'INSERT INTO reclamations (id_utilisateur, email, sujet, description, date_creation, statut, categorie) 
                     VALUES (:id_utilisateur, :email, :sujet, :description, :date_creation, :statut, :categorie)'
                );
                
                $result = $query->execute([
                    'id_utilisateur' => $this->id_utilisateur,
                    'email' => $this->email,
                    'sujet' => $this->sujet,
                    'description' => $this->description,
                    'date_creation' => $this->date_creation,
                    'statut' => $this->statut,
                    'categorie' => $this->categorie ?? 'Other'
                ]);
                
                if ($result) {
                    $this->id_reclamation = $db->lastInsertId();
                    return $this->id_reclamation;
                }
                return false;
            }
        } catch (PDOException $e) {
            error_log('❌ Erreur Reclamation::save(): ' . $e->getMessage());
            return false;
        }
    }

    public function delete() {
        if (!$this->id_reclamation) {
            return false;
        }
        
        $db = Config::getConnexion();
        try {
            $query = $db->prepare('DELETE FROM reclamations WHERE id_reclamation = :id_reclamation');
            $result = $query->execute(['id_reclamation' => $this->id_reclamation]);
            return $result;
        } catch (PDOException $e) {
            error_log('❌ Erreur Reclamation::delete(): ' . $e->getMessage());
            return false;
        }
    }

    public static function findById($id) {
        $db = Config::getConnexion();
        try {
            $query = $db->prepare('SELECT * FROM reclamations WHERE id_reclamation = :id');
            $query->execute(['id' => $id]);
            $data = $query->fetch(PDO::FETCH_ASSOC);
            
            if ($data) {
                $reclamation = new Reclamation();
                $reclamation->id_reclamation = $data['id_reclamation'];
                $reclamation->id_utilisateur = $data['id_utilisateur'] ?? null;
                $reclamation->email = $data['email'];
                $reclamation->sujet = $data['sujet'];
                $reclamation->description = $data['description'];
                $reclamation->date_creation = $data['date_creation'];
                $reclamation->statut = $data['statut'];
                $reclamation->piece_jointe = $data['piece_jointe'] ?? null;
                $reclamation->categorie = $data['categorie'] ?? 'Other';
                return $reclamation;
            }
            return null;
        } catch (PDOException $e) {
            error_log('❌ Erreur Reclamation::findById(): ' . $e->getMessage());
            return null;
        }
    }

    public static function findAll() {
        $db = Config::getConnexion();
        try {
            $query = $db->query('SELECT * FROM reclamations ORDER BY date_creation DESC');
            $results = $query->fetchAll(PDO::FETCH_ASSOC);
            
            $reclamations = [];
            foreach ($results as $data) {
                $reclamation = new Reclamation();
                $reclamation->id_reclamation = $data['id_reclamation'];
                $reclamation->id_utilisateur = $data['id_utilisateur'] ?? null;
                $reclamation->email = $data['email'];
                $reclamation->sujet = $data['sujet'];
                $reclamation->description = $data['description'];
                $reclamation->date_creation = $data['date_creation'];
                $reclamation->statut = $data['statut'];
                $reclamation->piece_jointe = $data['piece_jointe'] ?? null;
                $reclamation->categorie = $data['categorie'] ?? 'Other';
                $reclamations[] = $reclamation;
            }
            return $reclamations;
        } catch (PDOException $e) {
            error_log('❌ Erreur Reclamation::findAll(): ' . $e->getMessage());
            return [];
        }
    }

    public static function findByEmail($email) {
        $db = Config::getConnexion();
        try {
            $query = $db->prepare('SELECT * FROM reclamations WHERE email = :email ORDER BY date_creation DESC');
            $query->execute(['email' => $email]);
            $results = $query->fetchAll(PDO::FETCH_ASSOC);
            
            $reclamations = [];
            foreach ($results as $data) {
                $reclamation = new Reclamation();
                $reclamation->id_reclamation = $data['id_reclamation'];
                $reclamation->id_utilisateur = $data['id_utilisateur'] ?? null;
                $reclamation->email = $data['email'];
                $reclamation->sujet = $data['sujet'];
                $reclamation->description = $data['description'];
                $reclamation->date_creation = $data['date_creation'];
                $reclamation->statut = $data['statut'];
                $reclamation->piece_jointe = $data['piece_jointe'] ?? null;
                $reclamation->categorie = $data['categorie'] ?? 'Other';
                $reclamations[] = $reclamation;
            }
            return $reclamations;
        } catch (PDOException $e) {
            error_log('❌ Erreur Reclamation::findByEmail(): ' . $e->getMessage());
            return [];
        }
    }
}
?>