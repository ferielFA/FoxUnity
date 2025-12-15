<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config.php';

echo "<h2>Vérification de la structure des tables</h2>";

try {
    $db = Config::getConnexion();
    echo "<p style='color: green;'>✓ Connexion à la base de données réussie</p>";
    
    // Vérifier la structure de la table reclamations
    echo "<h3>Structure de la table 'reclamations':</h3>";
    $stmt = $db->query("DESCRIBE reclamations");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($col['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Vérifier la structure de la table reponses
    echo "<h3>Structure de la table 'reponses':</h3>";
    $stmt = $db->query("DESCRIBE reponses");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($col['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test d'insertion
    echo "<h3>Test d'insertion dans 'reclamations':</h3>";
    try {
        $testSql = "INSERT INTO reclamations (full_name, email, subject, message, date_creation, statut) 
                    VALUES (:full_name, :email, :subject, :message, :date_creation, :statut)";
        $testQuery = $db->prepare($testSql);
        $testResult = $testQuery->execute([
            'full_name' => 'Test User',
            'email' => 'test@example.com',
            'subject' => 'Test Subject',
            'message' => 'Test message',
            'date_creation' => date('Y-m-d H:i:s'),
            'statut' => 'nouveau'
        ]);
        
        if ($testResult) {
            $testId = $db->lastInsertId();
            echo "<p style='color: green;'>✓ Insertion réussie! ID: $testId</p>";
            
            // Supprimer le test
            $db->prepare("DELETE FROM reclamations WHERE id_reclamation = ?")->execute([$testId]);
            echo "<p style='color: blue;'>✓ Enregistrement de test supprimé</p>";
        } else {
            $errorInfo = $testQuery->errorInfo();
            echo "<p style='color: red;'>✗ Erreur d'insertion: " . implode(", ", $errorInfo) . "</p>";
        }
    } catch (PDOException $e) {
        echo "<p style='color: red;'>✗ Erreur PDO: " . $e->getMessage() . "</p>";
        echo "<p style='color: red;'>Code: " . $e->getCode() . "</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ Erreur de connexion: " . $e->getMessage() . "</p>";
}
?>

