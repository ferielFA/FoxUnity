<?php
/**
 * Script de diagnostic pour vérifier la configuration de la base de données
 * Accédez à ce fichier via: elhttp://localhost/foxunity/view/back/check_database.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../config/config.php';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Diagnostic Base de Données</title>";
echo "<style>body{font-family:Arial;padding:20px;background:#1a1a1a;color:#fff;}";
echo ".success{color:#4caf50;}.error{color:#f44336;}.warning{color:#ff9800;}";
echo "pre{background:#2a2a2a;padding:15px;border-radius:5px;overflow-x:auto;}</style></head><body>";
echo "<h1>🔍 Diagnostic Base de Données</h1>";

try {
    $db = Config::getConnexion();
    echo "<p class='success'>✅ Connexion à la base de données réussie</p>";
    
    // Vérifier si la table reclamations existe
    $checkTable = $db->query("SHOW TABLES LIKE 'reclamations'");
    if ($checkTable->rowCount() > 0) {
        echo "<p class='success'>✅ La table 'reclamations' existe</p>";
        
        // Vérifier si la colonne categorie existe
        $checkColumn = $db->query("SHOW COLUMNS FROM reclamations LIKE 'categorie'");
        if ($checkColumn->rowCount() > 0) {
            echo "<p class='success'>✅ La colonne 'categorie' existe dans la table 'reclamations'</p>";
            
            // Afficher les statistiques par catégorie
            $statsQuery = $db->query("SELECT categorie, COUNT(*) as count FROM reclamations GROUP BY categorie ORDER BY count DESC");
            $stats = $statsQuery->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($stats)) {
                echo "<h2>📊 Statistiques par catégorie:</h2><pre>";
                foreach ($stats as $stat) {
                    echo htmlspecialchars($stat['categorie']) . ": " . $stat['count'] . "\n";
                }
                echo "</pre>";
            } else {
                echo "<p class='warning'>⚠️ Aucune réclamation trouvée dans la base de données</p>";
            }
        } else {
            echo "<p class='error'>❌ La colonne 'categorie' n'existe PAS dans la table 'reclamations'</p>";
            echo "<h2>🔧 Solution:</h2>";
            echo "<p>Exécutez le script SQL suivant dans phpMyAdmin ou votre client MySQL:</p>";
            echo "<pre>";
            echo "-- Ajouter la colonne catégorie à la table reclamations\n";
            echo "ALTER TABLE reclamations \n";
            echo "ADD COLUMN categorie VARCHAR(50) DEFAULT 'Other' AFTER description;\n\n";
            echo "-- Créer un index pour améliorer les performances\n";
            echo "CREATE INDEX idx_categorie ON reclamations(categorie);\n\n";
            echo "-- Mettre à jour les réclamations existantes\n";
            echo "UPDATE reclamations SET categorie = 'Other' WHERE categorie IS NULL;\n";
            echo "</pre>";
            echo "<p><strong>Ou exécutez le fichier:</strong> <code>.vscode/database/add_categorie_to_reclamations.sql</code></p>";
        }
        
        // Afficher la structure de la table
        $structure = $db->query("DESCRIBE reclamations");
        $columns = $structure->fetchAll(PDO::FETCH_ASSOC);
        echo "<h2>📋 Structure de la table 'reclamations':</h2><pre>";
        foreach ($columns as $col) {
            echo htmlspecialchars($col['Field']) . " | " . htmlspecialchars($col['Type']) . " | " . htmlspecialchars($col['Null']) . " | " . htmlspecialchars($col['Key']) . "\n";
        }
        echo "</pre>";
        
    } else {
        echo "<p class='error'>❌ La table 'reclamations' n'existe pas</p>";
    }
    
    // Vérifier la table satisfactions
    $checkSatisfactions = $db->query("SHOW TABLES LIKE 'satisfactions'");
    if ($checkSatisfactions->rowCount() > 0) {
        echo "<p class='success'>✅ La table 'satisfactions' existe</p>";
    } else {
        echo "<p class='warning'>⚠️ La table 'satisfactions' n'existe pas (optionnel pour CSAT)</p>";
    }
    
} catch (PDOException $e) {
    echo "<p class='error'>❌ Erreur PDO: " . htmlspecialchars($e->getMessage()) . "</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Erreur: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</body></html>";
?>

