<?php
require_once __DIR__ . '/../model/Satisfaction.php';

class SatisfactionController {
    
    // Ajouter une évaluation (permet plusieurs évaluations par réclamation - une par email)
    public function addSatisfaction($id_reclamation, $email, $rating, $commentaire = null) {
        try {
            // Validation des paramètres
            if (empty($id_reclamation) || $id_reclamation <= 0) {
                error_log('❌ addSatisfaction: ID réclamation invalide: ' . $id_reclamation);
                throw new Exception('ID réclamation invalide: ' . $id_reclamation);
            }
            
            if (empty($email)) {
                error_log('❌ addSatisfaction: Email vide');
                throw new Exception('Email vide ou invalide');
            }
            
            if ($rating < 1 || $rating > 5) {
                error_log('❌ addSatisfaction: Rating invalide: ' . $rating);
                throw new Exception('Rating invalide: doit être entre 1 et 5, reçu: ' . $rating);
            }
            
            error_log("🔵 addSatisfaction - Validation OK - ID: $id_reclamation, Email: " . substr($email, 0, 30) . ", Rating: $rating");
            
            // Vérifier si cet utilisateur (email) a déjà évalué cette réclamation
            error_log("🔵 Recherche d'une évaluation existante pour ID réclamation: $id_reclamation, Email: " . substr($email, 0, 30));
            $existing = Satisfaction::findByReclamationIdAndEmail($id_reclamation, $email);
            
            if ($existing) {
                error_log("🔵 Évaluation existante trouvée - ID: " . $existing->getIdSatisfaction());
                // Mettre à jour l'évaluation existante de cet utilisateur
                $existing->setRating($rating);
                $existing->setCommentaire($commentaire);
                error_log("🔵 Tentative de mise à jour de l'évaluation existante...");
                $updateResult = $existing->save(); // save() lance une exception en cas d'erreur
                
                error_log("🔵 Résultat de la mise à jour: " . var_export($updateResult, true));
                
                // Pour une mise à jour, save() retourne l'ID
                if ($updateResult && $updateResult > 0) {
                    error_log("✅ Mise à jour réussie - ID: $updateResult");
                    return $updateResult;
                } else {
                    error_log("❌ Échec de la mise à jour - Résultat: " . var_export($updateResult, true));
                    throw new Exception('Erreur lors de la mise à jour de l\'évaluation existante. Résultat: ' . var_export($updateResult, true));
                }
            } else {
                error_log("🔵 Aucune évaluation existante trouvée - Création d'une nouvelle évaluation...");
                // Créer une nouvelle évaluation (plusieurs utilisateurs peuvent évaluer la même réclamation)
                $satisfaction = new Satisfaction($id_reclamation, $email, $rating, $commentaire);
                error_log("🔵 Objet Satisfaction créé - Tentative d'insertion...");
                $insertResult = $satisfaction->save(); // save() lance une exception en cas d'erreur
                
                error_log("🔵 Résultat de l'insertion: " . var_export($insertResult, true));
                
                // Pour une insertion, save() retourne l'ID ou lance une exception
                if ($insertResult && $insertResult > 0) {
                    error_log("✅ Insertion réussie - ID: $insertResult");
                    return $insertResult;
                } else {
                    error_log("❌ Échec de l'insertion - Résultat: " . var_export($insertResult, true));
                    throw new Exception('Erreur lors de l\'insertion de la nouvelle évaluation. Résultat: ' . var_export($insertResult, true));
                }
            }
        } catch (PDOException $e) {
            $errorMsg = $e->getMessage();
            error_log('❌ Erreur PDO addSatisfaction: ' . $errorMsg);
            error_log('❌ Code erreur: ' . $e->getCode());
            
            // Vérifier si c'est une erreur de contrainte UNIQUE
            if (strpos($errorMsg, "Duplicate entry") !== false || 
                strpos($errorMsg, "duplicata") !== false ||
                strpos($errorMsg, "UNIQUE") !== false ||
                strpos($errorMsg, "unique_reclamation") !== false) {
                error_log('⚠️ Erreur de contrainte UNIQUE détectée. Exécutez: fix_satisfactions_table.php');
                // Retourner un message spécial pour cette erreur
                throw new Exception('CONSTRAINT_ERROR: La contrainte UNIQUE bloque l\'insertion. Exécutez fix_satisfactions_table.php');
            }
            
            throw $e; // Re-lancer l'exception pour qu'elle soit capturée plus haut
        } catch (Exception $e) {
            error_log('❌ Erreur addSatisfaction: ' . $e->getMessage());
            error_log('❌ Stack trace: ' . $e->getTraceAsString());
            throw $e; // Re-lancer pour capturer le message exact
        }
    }
    
    // Obtenir toutes les évaluations d'une réclamation
    public function getSatisfactionsByReclamationId($id_reclamation) {
        return Satisfaction::findAllByReclamationId($id_reclamation);
    }
    
    // Obtenir l'évaluation d'une réclamation
    public function getSatisfactionByReclamationId($id_reclamation) {
        return Satisfaction::findByReclamationId($id_reclamation);
    }
    
    // Obtenir toutes les évaluations
    public function getAllSatisfactions() {
        return Satisfaction::findAll();
    }
    
    // Obtenir les statistiques
    public function getStats() {
        return Satisfaction::getStats();
    }
    
    // Vérifier si une réclamation a été évaluée
    public function hasSatisfaction($id_reclamation) {
        $satisfaction = Satisfaction::findByReclamationId($id_reclamation);
        return $satisfaction !== null;
    }
}
?>





