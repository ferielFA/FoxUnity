<?php 
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Response.php';

class ResponseController {
    
    public function addResponse($response) {
        $sql = "INSERT INTO reponses (id_reclamation, id_admin, message, date_reponse, statut_reponse) 
                VALUES (:id_reclamation, :id_admin, :message, :date_reponse, :statut_reponse)";
        $db = Config::getConnexion();
        try {
            // Vérifier que la connexion est établie
            if (!$db) {
                error_log("❌ Erreur: Connexion à la base de données échouée");
                return false;
            }
            
            // Vérifier les données
            $idReclamation = $response->getIdReclamation();
            $message = $response->getMessage();
            $idAdmin = $response->getIdAdmin();
            $dateReponse = $response->getDateCreation();
            $statutReponse = $response->getStatutReponse() ?? 'sent';
            
            if (empty($idReclamation) || empty($message)) {
                error_log("❌ Erreur: Données manquantes - id_reclamation: $idReclamation, message: " . (empty($message) ? 'vide' : 'présent'));
                return false;
            }
            
            $query = $db->prepare($sql);
            if (!$query) {
                $errorInfo = $db->errorInfo();
                error_log("❌ Erreur lors de la préparation de la requête: " . implode(", ", $errorInfo));
                return false;
            }
            
            $params = [
                'id_reclamation' => $idReclamation,
                'id_admin' => $idAdmin ?? 1,
                'message' => $message,
                'date_reponse' => $dateReponse ?? date('Y-m-d H:i:s'),
                'statut_reponse' => $statutReponse
            ];
            
            error_log("📝 Tentative d'insertion réponse: " . print_r($params, true));
            
            $result = $query->execute($params);
            
            if ($result) {
                $insertId = $db->lastInsertId();
                error_log("✓ Réponse ajoutée avec succès, ID: $insertId");
                return $insertId;
            } else {
                $errorInfo = $query->errorInfo();
                error_log("❌ Erreur lors de l'insertion PDO: " . implode(", ", $errorInfo));
                error_log("❌ SQL: " . $sql);
                error_log("❌ Params: " . print_r($params, true));
                return false;
            }
        } catch (PDOException $e) {
            error_log('❌ Erreur addResponse PDO: ' . $e->getMessage());
            error_log('❌ Code erreur: ' . $e->getCode());
            error_log('❌ SQL: ' . $sql);
            return false;
        } catch (Exception $e) {
            error_log('❌ Erreur addResponse: ' . $e->getMessage());
            error_log('❌ Fichier: ' . $e->getFile() . ' ligne ' . $e->getLine());
            return false;
        }
    }

    public function getResponseById($id) {
        $sql = "SELECT * FROM reponses WHERE id_reponse = :id";
        $db = Config::getConnexion();
        try {
            $query = $db->prepare($sql);
            $query->execute(['id' => $id]);
            return $query->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('❌ Erreur getResponseById: ' . $e->getMessage());
            return false;
        }
    }

    public function getResponsesByReclamationId($id_reclamation) {
        $sql = "SELECT * FROM reponses WHERE id_reclamation = :id_reclamation ORDER BY date_reponse DESC";
        $db = Config::getConnexion();
        try {
            $query = $db->prepare($sql);
            $query->execute(['id_reclamation' => $id_reclamation]);
            return $query->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('❌ Erreur getResponsesByReclamationId: ' . $e->getMessage());
            return [];
        }
    }

    public function getAllResponses() {
        $sql = "SELECT * FROM reponses ORDER BY date_reponse DESC";
        $db = Config::getConnexion();
        try {
            $query = $db->query($sql);
            return $query->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('❌ Erreur getAllResponses: ' . $e->getMessage());
            return [];
        }
    }

    public function updateResponse($response) {
        try {
            $db = Config::getConnexion();
            $query = $db->prepare(
                'UPDATE reponses SET
                    message = :message,
                    id_admin = :id_admin,
                    statut_reponse = :statut_reponse
                WHERE id_reponse = :id_reponse'
            );
            
            $result = $query->execute([
                'message' => $response->getMessage(),
                'id_admin' => $response->getIdAdmin(),
                'statut_reponse' => $response->getStatutReponse() ?? 'sent',
                'id_reponse' => $response->getIdResponse()
            ]);
            
            return $result;
        } catch (PDOException $e) {
            error_log('❌ Erreur updateResponse: ' . $e->getMessage());
            return false;
        }
    }

    public function deleteResponse($id) {
        $sql = "DELETE FROM reponses WHERE id_reponse = :id_reponse";
        $db = Config::getConnexion();
        try {
            $query = $db->prepare($sql);
            $result = $query->execute(['id_reponse' => $id]);
            return $result;
        } catch (Exception $e) {
            error_log('❌ Erreur deleteResponse: ' . $e->getMessage());
            return false;
        }
    }
}
?>









