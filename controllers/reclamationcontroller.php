<?php 
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Reclamation.php';

class ReclamationController {
    public function updateReclamation($reclamation) {   
    try {
        $db = Config::getConnexion();
        $query = $db->prepare(
            'UPDATE reclamations SET
                id_utilisateur = :id_utilisateur,
                email = :email,
                sujet = :sujet,
                description = :description,
                statut = :statut,
                categorie = :categorie
            WHERE id_reclamation = :id_reclamation'
        );
        
        $result = $query->execute([
            'id_utilisateur' => $reclamation->getIdUtilisateur(),
            'email' => $reclamation->getEmail(),
            'sujet' => $reclamation->getSujet(),
            'description' => $reclamation->getDescription(),
            'statut' => $reclamation->getStatut(),
            'categorie' => $reclamation->getCategorie() ?? 'Other',
            'id_reclamation' => $reclamation->getIdReclamation()
        ]);
        
        return $result;
    } catch (PDOException $e) {
        error_log('❌ Erreur updateReclamation: ' . $e->getMessage());
        return false;
    }
}
    public function addReclamation($reclamation) {
        // Construire la requête SQL dynamiquement selon les valeurs NULL
        $fields = [];
        $values = [];
        $params = [];
        
        // Colonnes obligatoires
        $fields[] = 'email';
        $params['email'] = $reclamation->getEmail();
        
        $fields[] = 'sujet';
        $params['sujet'] = $reclamation->getSujet();
        
        $fields[] = 'description';
        $params['description'] = $reclamation->getDescription();
        
        // Colonnes optionnelles
        $idUtilisateur = $reclamation->getIdUtilisateur();
        // id_utilisateur : si NULL, utiliser 0 (pour les utilisateurs non connectés)
        // car la colonne est probablement NOT NULL
        $fields[] = 'id_utilisateur';
        $params['id_utilisateur'] = $idUtilisateur !== null ? $idUtilisateur : 0;
        
        $dateCreation = $reclamation->getDateCreation();
        if ($dateCreation !== null) {
            $fields[] = 'date_creation';
            $params['date_creation'] = $dateCreation;
        }
        
        $statut = $reclamation->getStatut();
        // Toujours inclure le statut, utiliser 'nouveau' par défaut si null
        $fields[] = 'statut';
        $params['statut'] = $statut !== null ? $statut : 'nouveau';
        
        // Ajouter la pièce jointe si elle existe
        $pieceJointe = $reclamation->getPieceJointe();
        if ($pieceJointe !== null && $pieceJointe !== '') {
            $fields[] = 'piece_jointe';
            $params['piece_jointe'] = $pieceJointe;
        }
        
        $sql = "INSERT INTO reclamations (" . implode(', ', $fields) . ") 
                VALUES (:" . implode(', :', $fields) . ")";
        
        $db = Config::getConnexion();
        try {
            // Vérifier que la connexion est établie
            if (!$db) {
                error_log("❌ Erreur: Connexion à la base de données échouée");
                return false;
            }
            
            // Log pour débogage
            error_log("SQL: " . $sql);
            error_log("Params: " . print_r($params, true));
            
            $query = $db->prepare($sql);
            if (!$query) {
                $errorInfo = $db->errorInfo();
                error_log("❌ Erreur lors de la préparation de la requête: " . implode(", ", $errorInfo));
                return false;
            }
            
            $result = $query->execute($params);
            
            if ($result) {
                $insertId = $db->lastInsertId();
                error_log("✓ Insertion réussie, ID: " . $insertId);
                return $insertId;
            } else {
                $errorInfo = $query->errorInfo();
                error_log("❌ Erreur lors de l'insertion PDO: " . implode(", ", $errorInfo));
                error_log("❌ SQL: " . $sql);
                error_log("❌ Params: " . print_r($params, true));
                return false;
            }
        } catch (PDOException $e) {
            error_log('❌ Erreur addReclamation PDO: ' . $e->getMessage());
            error_log('❌ Code erreur: ' . $e->getCode());
            error_log('❌ SQL: ' . $sql);
            error_log('❌ Params: ' . print_r($params, true));
            return false;
        } catch (Exception $e) {
            error_log('❌ Erreur addReclamation: ' . $e->getMessage());
            return false;
        }
    }

    public function deleteReclamation($reclamationId) {
        $sql = "DELETE FROM reclamations WHERE id_reclamation = :id_reclamation";
        $db = Config::getConnexion();
        try {
            $query = $db->prepare($sql);
            $result = $query->execute(['id_reclamation' => $reclamationId]);
            return $result;
        } catch (Exception $e) {
            error_log('❌ Erreur deleteReclamation: ' . $e->getMessage());
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
            error_log('❌ Erreur getReclamationsByEmail: ' . $e->getMessage());
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

    public function getAllReclamations($statusFilter = null, $dateFilter = null, $categorieFilter = null) {
        $sql = "SELECT * FROM reclamations WHERE 1=1";
        $params = [];
        
        // Filtre par statut
        if ($statusFilter && $statusFilter !== 'all') {
            $sql .= " AND statut = :statut";
            $params['statut'] = $statusFilter;
        }
        
        // Filtre par date
        if ($dateFilter && $dateFilter !== 'all') {
            $today = date('Y-m-d');
            switch ($dateFilter) {
                case 'today':
                    $sql .= " AND DATE(date_creation) = :date_filter";
                    $params['date_filter'] = $today;
                    break;
                case 'week':
                    $sql .= " AND date_creation >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                    break;
                case 'month':
                    $sql .= " AND date_creation >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
                    break;
            }
        }
        
        // Filtre par catégorie
        if ($categorieFilter && $categorieFilter !== 'all') {
            $sql .= " AND categorie = :categorie";
            $params['categorie'] = $categorieFilter;
        }
        
        // Tri automatique : d'abord par statut (nouveau, en_cours, resolu), puis par date décroissante
        $sql .= " ORDER BY 
            CASE statut 
                WHEN 'nouveau' THEN 1 
                WHEN 'en_cours' THEN 2 
                WHEN 'resolu' THEN 3 
                ELSE 4 
            END ASC, 
            date_creation DESC";
        
        $db = Config::getConnexion();
        try {
            $query = $db->prepare($sql);
            $query->execute($params);
            return $query->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('❌ Erreur getAllReclamations: ' . $e->getMessage());
            return [];
        }
    }

    // Obtenir les statistiques par catégorie
    public function getStatsByCategory() {
        $db = Config::getConnexion();
        try {
            // Vérifier d'abord si la colonne categorie existe
            $checkColumn = $db->query("SHOW COLUMNS FROM reclamations LIKE 'categorie'");
            if ($checkColumn->rowCount() == 0) {
                error_log('⚠️ La colonne categorie n\'existe pas. Exécutez le script SQL: .vscode/database/add_categorie_to_reclamations.sql');
                return [];
            }
            
            $query = $db->query('SELECT categorie, COUNT(*) as count FROM reclamations WHERE categorie IS NOT NULL GROUP BY categorie ORDER BY count DESC');
            $results = $query->fetchAll(PDO::FETCH_ASSOC);
            
            $stats = [];
            foreach ($results as $row) {
                $category = $row['categorie'] ?? 'Other';
                $stats[$category] = (int)$row['count'];
            }
            
            return $stats;
        } catch (PDOException $e) {
            error_log('❌ Erreur getStatsByCategory PDO: ' . $e->getMessage());
            // Si c'est une erreur de colonne inexistante, retourner un tableau vide
            if (strpos($e->getMessage(), "Unknown column") !== false || strpos($e->getMessage(), "doesn't exist") !== false) {
                error_log('⚠️ La colonne categorie n\'existe pas. Exécutez le script SQL: .vscode/database/add_categorie_to_reclamations.sql');
            }
            return [];
        } catch (Exception $e) {
            error_log('❌ Erreur getStatsByCategory: ' . $e->getMessage());
            return [];
        }
    }
}
?>