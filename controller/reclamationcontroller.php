<?php 
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../model/Reclamation.php';

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

    /**
     * Ajoute une réclamation en s'appuyant sur la logique du modèle.
     * On délègue à Reclamation::save() pour rester aligné avec le schéma réel de la base.
     */
    public function addReclamation($reclamation) {
        if (!$reclamation instanceof Reclamation) {
            error_log('❌ addReclamation: objet invalide passé au contrôleur');
            return false;
        }

        return $reclamation->save();
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
        // Récupérer les réclamations avec, en plus, la note moyenne et le nombre d'évaluations
        $sql = "SELECT 
                    r.*,
                    AVG(s.rating) AS average_rating,
                    COUNT(s.id_satisfaction) AS rating_count
                FROM reclamations r
                LEFT JOIN satisfactions s ON s.id_reclamation = r.id_reclamation
                WHERE 1=1";
        $params = [];
        
        // Filtre par statut
        if ($statusFilter && $statusFilter !== 'all') {
            $sql .= " AND r.statut = :statut";
            $params['statut'] = $statusFilter;
        }
        
        // Filtre par date
        if ($dateFilter && $dateFilter !== 'all') {
            $today = date('Y-m-d');
            switch ($dateFilter) {
                case 'today':
                    $sql .= " AND DATE(r.date_creation) = :date_filter";
                    $params['date_filter'] = $today;
                    break;
                case 'week':
                    $sql .= " AND r.date_creation >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                    break;
                case 'month':
                    $sql .= " AND r.date_creation >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
                    break;
            }
        }
        
        // Filtre par catégorie
        if ($categorieFilter && $categorieFilter !== 'all') {
            $sql .= " AND r.categorie = :categorie";
            $params['categorie'] = $categorieFilter;
        }
        
        // Regrouper par réclamation pour que les agrégats AVG/COUNT fonctionnent,
        // puis trier comme avant (par statut puis par date)
        $sql .= " GROUP BY r.id_reclamation
                  ORDER BY 
                    CASE r.statut 
                        WHEN 'nouveau' THEN 1 
                        WHEN 'en_cours' THEN 2 
                        WHEN 'resolu' THEN 3 
                        ELSE 4 
                    END ASC, 
                    r.date_creation DESC";
        
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