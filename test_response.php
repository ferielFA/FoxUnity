<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/models/Response.php';
require_once __DIR__ . '/controllers/ResponseController.php';

echo "<h2>Test de la table reponses</h2>";

try {
    $db = Config::getConnexion();
    echo "<p style='color: green;'>✓ Connexion à la base de données réussie</p>";
    
    // Afficher la structure de la table
    echo "<h3>Structure de la table 'reponses':</h3>";
    $stmt = $db->query("DESCRIBE reponses");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td><strong>" . htmlspecialchars($col['Field']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($col['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test d'insertion directe
    echo "<h3>Test d'insertion directe:</h3>";
    
    // Récupérer une réclamation existante pour tester
    $stmt = $db->query("SELECT id_reclamation FROM reclamations LIMIT 1");
    $reclamation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($reclamation) {
        $testData = [
            'id_reclamation' => $reclamation['id_reclamation'],
            'response_text' => 'Test réponse',
            'admin_name' => 'Admin Test',
            'date_creation' => date('Y-m-d H:i:s')
        ];
        
        $testSql = "INSERT INTO reponses (id_reclamation, response_text, admin_name, date_creation) 
                    VALUES (:id_reclamation, :response_text, :admin_name, :date_creation)";
        $testQuery = $db->prepare($testSql);
        $testResult = $testQuery->execute($testData);
        
        if ($testResult) {
            $testId = $db->lastInsertId();
            echo "<p style='color: green;'>✓ Insertion directe réussie! ID: $testId</p>";
            
            // Supprimer le test
            $db->prepare("DELETE FROM reponses WHERE id_response = ?")->execute([$testId]);
            echo "<p style='color: blue;'>✓ Enregistrement de test supprimé</p>";
        } else {
            $errorInfo = $testQuery->errorInfo();
            echo "<p style='color: red;'>✗ Erreur d'insertion directe: " . implode(", ", $errorInfo) . "</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠ Aucune réclamation trouvée pour tester</p>";
    }
    
    // Test avec le modèle et le contrôleur
    if ($reclamation) {
        echo "<h3>Test avec le modèle Response et le contrôleur:</h3>";
        $response = new Response(
            $reclamation['id_reclamation'],
            'Test réponse via modèle',
            'Admin Test'
        );
        
        echo "<p>Données du modèle:</p>";
        echo "<ul>";
        echo "<li>ID Reclamation: " . htmlspecialchars($response->getIdReclamation()) . "</li>";
        echo "<li>Response Text: " . htmlspecialchars($response->getResponseText()) . "</li>";
        echo "<li>Admin Name: " . htmlspecialchars($response->getAdminName()) . "</li>";
        echo "<li>Date: " . htmlspecialchars($response->getDateCreation()) . "</li>";
        echo "</ul>";
        
        $controller = new ResponseController();
        $result = $controller->addResponse($response);
        
        if ($result && $result > 0) {
            echo "<p style='color: green;'>✓ Insertion via contrôleur réussie! ID: $result</p>";
            
            // Vérifier que l'enregistrement existe
            $check = $db->prepare("SELECT * FROM reponses WHERE id_response = ?");
            $check->execute([$result]);
            $record = $check->fetch(PDO::FETCH_ASSOC);
            
            if ($record) {
                echo "<p style='color: green;'>✓ Enregistrement trouvé dans la base de données:</p>";
                echo "<pre>" . print_r($record, true) . "</pre>";
                
                // Supprimer le test
                $db->prepare("DELETE FROM reponses WHERE id_response = ?")->execute([$result]);
                echo "<p style='color: blue;'>✓ Enregistrement de test supprimé</p>";
            }
        } else {
            echo "<p style='color: red;'>✗ Erreur d'insertion via contrôleur. Résultat: " . var_export($result, true) . "</p>";
        }
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ Erreur PDO: " . $e->getMessage() . "</p>";
    echo "<p style='color: red;'>Code: " . $e->getCode() . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Erreur: " . $e->getMessage() . "</p>";
}
?>









