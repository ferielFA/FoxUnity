<?php
/**
 * Script de debug pour vérifier l'affichage des réponses
 * Simule exactement ce que fait reclamback.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../controllers/ResponseController.php';
require_once __DIR__ . '/../../controllers/ReclamationController.php';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Debug Affichage Réponses</title>";
echo "<style>body{font-family:Arial;padding:20px;background:#1a1a1a;color:#fff;}";
echo ".success{color:#4caf50;}.error{color:#f44336;}.warning{color:#ff9800;}";
echo "pre{background:#2a2a2a;padding:15px;border-radius:5px;overflow-x:auto;}";
echo ".response-item{background:rgba(255,122,0,0.05);border-left:3px solid #ff7a00;padding:15px;margin:10px 0;border-radius:8px;}</style></head><body>";
echo "<h1>🔍 Debug Affichage Réponses</h1>";

try {
    $reclamationController = new ReclamationController();
    $responseController = new ResponseController();
    
    // Récupérer toutes les réclamations
    $allReclamations = $reclamationController->getAllReclamations(null, null);
    
    echo "<p class='success'>✅ " . count($allReclamations) . " réclamation(s) trouvée(s)</p>";
    
    // Simuler exactement ce que fait reclamback.php
    foreach ($allReclamations as &$reclamation) {
        $reclamationId = $reclamation['id_reclamation'];
        $reclamation['responses'] = $responseController->getResponsesByReclamationId($reclamationId);
        
        echo "<hr>";
        echo "<h2>📋 Réclamation ID: $reclamationId</h2>";
        echo "<p><strong>Email:</strong> " . htmlspecialchars($reclamation['email'] ?? 'N/A') . "</p>";
        echo "<p><strong>Sujet:</strong> " . htmlspecialchars($reclamation['sujet'] ?? 'N/A') . "</p>";
        echo "<p><strong>Statut:</strong> " . htmlspecialchars($reclamation['statut'] ?? 'N/A') . "</p>";
        
        // Vérifier les réponses
        $reclamationResponses = $reclamation['responses'] ?? [];
        
        echo "<h3>🔍 Vérification des réponses:</h3>";
        echo "<pre>";
        echo "Type de données: " . gettype($reclamationResponses) . "\n";
        echo "Est un tableau: " . (is_array($reclamationResponses) ? 'OUI' : 'NON') . "\n";
        echo "Nombre de réponses: " . count($reclamationResponses) . "\n";
        echo "Est vide: " . (empty($reclamationResponses) ? 'OUI' : 'NON') . "\n";
        if (!empty($reclamationResponses)) {
            echo "Première réponse:\n";
            print_r($reclamationResponses[0]);
        }
        echo "</pre>";
        
        // Test de la condition d'affichage
        $shouldDisplay = !empty($reclamationResponses) && is_array($reclamationResponses) && count($reclamationResponses) > 0;
        echo "<p><strong>Condition d'affichage:</strong> " . ($shouldDisplay ? '<span class="success">TRUE - Devrait s\'afficher</span>' : '<span class="error">FALSE - Ne s\'affichera pas</span>') . "</p>";
        
        // Afficher les réponses comme dans reclamback.php
        if ($shouldDisplay) {
            echo "<h3>✅ Affichage des réponses (comme dans reclamback.php):</h3>";
            echo "<div class='responses-section' style='margin-top: 20px; padding-top: 20px; border-top: 1px solid #444;'>";
            echo "<h6 style='color: #ff7a00; margin-bottom: 15px; font-size: 14px; font-weight: 600;'>";
            echo "<i class='fas fa-reply'></i> Réponses (" . count($reclamationResponses) . ")";
            echo "</h6>";
            
            foreach ($reclamationResponses as $response) {
                $responseId = $response['id_reponse'] ?? $response['id_response'] ?? $response['response_id'] ?? '';
                $responseMessage = $response['message'] ?? $response['response_text'] ?? $response['texte'] ?? 'Aucun message';
                $responseDate = $response['date_reponse'] ?? $response['response_date'] ?? $response['date_creation'] ?? 'now';
                
                echo "<div class='response-item'>";
                echo "<div style='display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;'>";
                echo "<div>";
                echo "<strong style='color: #ff7a00;'>Admin</strong>";
                echo "<span style='color: #aaa; font-size: 12px; margin-left: 10px;'>";
                try {
                    $dateObj = new DateTime($responseDate);
                    echo $dateObj->format('M j, Y H:i');
                } catch (Exception $e) {
                    echo date('M j, Y H:i');
                }
                echo "</span>";
                echo "</div>";
                echo "</div>";
                echo "<p style='color: #fff; margin: 0; line-height: 1.6;'>";
                echo nl2br(htmlspecialchars($responseMessage));
                echo "</p>";
                echo "</div>";
            }
            
            echo "</div>";
        } else {
            echo "<p class='warning'>⚠️ Aucune réponse à afficher pour cette réclamation</p>";
        }
    }
    unset($reclamation);
    
    echo "<hr>";
    echo "<p><a href='reclamback.php' style='color:#4caf50;'>← Retour aux Réclamations</a></p>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Erreur: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "</body></html>";
?>

