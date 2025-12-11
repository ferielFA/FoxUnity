<?php
require_once __DIR__ . '/../config/config.php';

class UserConfidenceScore {
    private $id_score;
    private $email;
    private $nombre_avis;
    private $likes_recus;
    private $taux_transparence;
    private $score_total;
    private $date_mise_a_jour;

    public function __construct($email = null, $nombre_avis = 0, $likes_recus = 0, $taux_transparence = 0) {
        $this->email = $email;
        $this->nombre_avis = $nombre_avis;
        $this->likes_recus = $likes_recus;
        $this->taux_transparence = $taux_transparence;
        $this->score_total = $this->calculerScore();
        $this->date_mise_a_jour = date('Y-m-d H:i:s');
    }

    private function calculerScore() {
        // Calcul du score : nombre d'avis (40%) + likes reçus (30%) + taux de transparence (30%)
        $score = ($this->nombre_avis * 0.4) + ($this->likes_recus * 0.3) + ($this->taux_transparence * 0.3);
        return round($score, 2);
    }

    // Getters
    public function getIdScore() { return $this->id_score; }
    public function getEmail() { return $this->email; }
    public function getNombreAvis() { return $this->nombre_avis; }
    public function getLikesRecus() { return $this->likes_recus; }
    public function getTauxTransparence() { return $this->taux_transparence; }
    public function getScoreTotal() { return $this->score_total; }
    public function getDateMiseAJour() { return $this->date_mise_a_jour; }

    // Setters
    public function setIdScore($id) { $this->id_score = $id; }
    public function setEmail($email) { $this->email = $email; }
    public function setNombreAvis($nombre) { 
        $this->nombre_avis = $nombre;
        $this->score_total = $this->calculerScore();
    }
    public function setLikesRecus($likes) { 
        $this->likes_recus = $likes;
        $this->score_total = $this->calculerScore();
    }
    public function setTauxTransparence($taux) { 
        $this->taux_transparence = $taux;
        $this->score_total = $this->calculerScore();
    }

    public function save() {
        $db = Config::getConnexion();
        
        if ($this->id_score) {
            // Mise à jour
            $query = $db->prepare(
                'UPDATE user_confidence_scores 
                 SET email = :email, nombre_avis = :nombre_avis, likes_recus = :likes_recus, 
                     taux_transparence = :taux_transparence, score_total = :score_total, 
                     date_mise_a_jour = :date_mise_a_jour 
                 WHERE id_score = :id_score'
            );
            return $query->execute([
                'email' => $this->email,
                'nombre_avis' => $this->nombre_avis,
                'likes_recus' => $this->likes_recus,
                'taux_transparence' => $this->taux_transparence,
                'score_total' => $this->score_total,
                'date_mise_a_jour' => $this->date_mise_a_jour,
                'id_score' => $this->id_score
            ]);
        } else {
            // Insertion
            $query = $db->prepare(
                'INSERT INTO user_confidence_scores (email, nombre_avis, likes_recus, taux_transparence, score_total, date_mise_a_jour) 
                 VALUES (:email, :nombre_avis, :likes_recus, :taux_transparence, :score_total, :date_mise_a_jour)'
            );
            $result = $query->execute([
                'email' => $this->email,
                'nombre_avis' => $this->nombre_avis,
                'likes_recus' => $this->likes_recus,
                'taux_transparence' => $this->taux_transparence,
                'score_total' => $this->score_total,
                'date_mise_a_jour' => $this->date_mise_a_jour
            ]);
            
            if ($result) {
                $this->id_score = $db->lastInsertId();
            }
            return $result;
        }
    }

    public static function findByEmail($email) {
        $db = Config::getConnexion();
        $query = $db->prepare('SELECT * FROM user_confidence_scores WHERE email = :email');
        $query->execute(['email' => $email]);
        $data = $query->fetch(PDO::FETCH_ASSOC);
        
        if ($data) {
            $score = new UserConfidenceScore(
                $data['email'],
                $data['nombre_avis'],
                $data['likes_recus'],
                $data['taux_transparence']
            );
            $score->setIdScore($data['id_score']);
            return $score;
        }
        return null;
    }

    public static function getAll() {
        $db = Config::getConnexion();
        $query = $db->query('SELECT * FROM user_confidence_scores ORDER BY score_total DESC');
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>


