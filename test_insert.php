<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/models/Reclamation.php';
require_once __DIR__ . '/controllers/reclamationcontroller.php';

echo "<h2>Test d'insertion dans la table reclamations</h2>";

try {
    $db = Config::getConnexion();
    echo "<p style='color: green;'>✓ Connexion à la base de données réussie</p>";
    
    // Afficher la structure de la table
    echo "<h3>Structure de la table 'reclamations':</h3>";
    $stmt = $db->query("DESCRIBE reclamations");
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
    $testData = [
        'id_utilisateur' => null,
        'email' => 'test@example.com',
        'sujet' => 'Test Subject',
        'description' => 'Test message',
        'date_creation' => date('Y-m-d H:i:s'),
        'statut' => 'pending'
    ];
    
    $testSql = "INSERT INTO reclamations (id_utilisateur, email, sujet, description, date_creation, statut) 
                VALUES (:id_utilisateur, :email, :sujet, :description, :date_creation, :statut)";
    $testQuery = $db->prepare($testSql);
    $testResult = $testQuery->execute($testData);
    
    if ($testResult) {
        $testId = $db->lastInsertId();
        echo "<p style='color: green;'>✓ Insertion directe réussie! ID: $testId</p>";
        
        // Supprimer le test
        $db->prepare("DELETE FROM reclamations WHERE id_reclamation = ?")->execute([$testId]);
        echo "<p style='color: blue;'>✓ Enregistrement de test supprimé</p>";
    } else {
        $errorInfo = $testQuery->errorInfo();
        echo "<p style='color: red;'>✗ Erreur d'insertion directe: " . implode(", ", $errorInfo) . "</p>";
    }
    
    // Test avec le modèle et le contrôleur
    echo "<h3>Test avec le modèle Reclamation et le contrôleur:</h3>";
    $reclamation = new Reclamation(
        'testmodel@example.com',
        'Test Subject Model',
        'Test message from model',
        null, // id_utilisateur
        null  // statut (utilisera le default 'pending')
    );
    
    echo "<p>Données du modèle:</p>";
    echo "<ul>";
    echo "<li>ID Utilisateur: " . ($reclamation->getIdUtilisateur() ?? 'NULL') . "</li>";
    echo "<li>Email: " . htmlspecialchars($reclamation->getEmail()) . "</li>";
    echo "<li>Sujet: " . htmlspecialchars($reclamation->getSujet()) . "</li>";
    echo "<li>Description: " . htmlspecialchars($reclamation->getDescription()) . "</li>";
    echo "<li>Date: " . htmlspecialchars($reclamation->getDateCreation()) . "</li>";
    echo "<li>Statut: " . ($reclamation->getStatut() ?? 'NULL (utilisera default)') . "</li>";
    echo "</ul>";
    
    $controller = new ReclamationController();
    $result = $controller->addReclamation($reclamation);
    
    if ($result && $result > 0) {
        echo "<p style='color: green;'>✓ Insertion via contrôleur réussie! ID: $result</p>";
        
        // Vérifier que l'enregistrement existe
        $check = $db->prepare("SELECT * FROM reclamations WHERE id_reclamation = ?");
        $check->execute([$result]);
        $record = $check->fetch(PDO::FETCH_ASSOC);
        
        if ($record) {
            echo "<p style='color: green;'>✓ Enregistrement trouvé dans la base de données:</p>";
            echo "<pre>" . print_r($record, true) . "</pre>";
            
            // Supprimer le test
            $db->prepare("DELETE FROM reclamations WHERE id_reclamation = ?")->execute([$result]);
            echo "<p style='color: blue;'>✓ Enregistrement de test supprimé</p>";
        } else {
            echo "<p style='color: orange;'>⚠ L'ID a été retourné mais l'enregistrement n'a pas été trouvé</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Erreur d'insertion via contrôleur. Résultat: " . var_export($result, true) . "</p>";
        
        // Afficher les dernières erreurs
        $errorInfo = $db->errorInfo();
        if ($errorInfo[0] !== '00000') {
            echo "<p style='color: red;'>Détails de l'erreur PDO: " . implode(", ", $errorInfo) . "</p>";
        }
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ Erreur PDO: " . $e->getMessage() . "</p>";
    echo "<p style='color: red;'>Code: " . $e->getCode() . "</p>";
    echo "<p style='color: red;'>Fichier: " . $e->getFile() . " ligne " . $e->getLine() . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Erreur: " . $e->getMessage() . "</p>";
    echo "<p style='color: red;'>Fichier: " . $e->getFile() . " ligne " . $e->getLine() . "</p>";
}
?>

