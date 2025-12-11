<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/config.php';

try {
    $db = Config::getConnexion();
    echo "<h2>Structure détaillée de la table reclamations</h2>";
    
    $stmt = $db->query("DESCRIBE reclamations");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    foreach ($columns as $col) {
        $nullAllowed = $col['Null'] === 'YES' ? 'OUI (peut être NULL)' : 'NON (NOT NULL)';
        $default = $col['Default'] ?? 'NULL';
        
        echo "<tr>";
        echo "<td><strong>" . htmlspecialchars($col['Field']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td style='color: " . ($col['Null'] === 'YES' ? 'green' : 'red') . ";'>" . $nullAllowed . "</td>";
        echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($default) . "</td>";
        echo "<td>" . htmlspecialchars($col['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>Recommandations pour l'insertion:</h3>";
    echo "<ul>";
    foreach ($columns as $col) {
        if ($col['Null'] === 'NO' && $col['Field'] !== 'id_reclamation') {
            $default = $col['Default'] ?? 'aucune';
            echo "<li><strong>" . htmlspecialchars($col['Field']) . "</strong>: OBLIGATOIRE";
            if ($default !== 'NULL' && $default !== 'aucune') {
                echo " (défaut: " . htmlspecialchars($default) . ")";
            }
            echo "</li>";
        }
    }
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Erreur: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>









