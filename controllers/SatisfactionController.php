<?php
require_once __DIR__ . '/../models/Satisfaction.php';

class SatisfactionController {
    
    // Ajouter une évaluation (permet plusieurs évaluations par réclamation - une par email)
    public function addSatisfaction($id_reclamation, $email, $rating, $commentaire = null) {
        try {
            // Validation des paramètres
            if (empty($id_reclamation) || $id_reclamation <= 0) {
                error_log('❌ addSatisfaction: ID réclamation invalide: ' . $id_reclamation);
                return false;
            }
            
            if (empty($email)) {
                error_log('❌ addSatisfaction: Email vide');
                return false;
            }
            
            if ($rating < 1 || $rating > 5) {
                error_log('❌ addSatisfaction: Rating invalide: ' . $rating);
                return false;
            }
            
            // Vérifier si cet utilisateur (email) a déjà évalué cette réclamation
            $existing = Satisfaction::findByReclamationIdAndEmail($id_reclamation, $email);
            if ($existing) {
                // Mettre à jour l'évaluation existante de cet utilisateur
                $existing->setRating($rating);
                $existing->setCommentaire($commentaire);
                return $existing->save(); // save() lance une exception en cas d'erreur
            } else {
                // Créer une nouvelle évaluation (plusieurs utilisateurs peuvent évaluer la même réclamation)
                $satisfaction = new Satisfaction($id_reclamation, $email, $rating, $commentaire);
                return $satisfaction->save(); // save() lance une exception en cas d'erreur
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





