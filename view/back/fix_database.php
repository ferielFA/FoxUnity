<?php
/**
 * Script pour ajouter automatiquement la colonne categorie à la table reclamations
 * Accédez à ce fichier via: http://localhost/foxunity/view/back/fix_database.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../config/config.php';

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Fix Base de Données</title>";
echo "<style>body{font-family:Arial;padding:20px;background:#1a1a1a;color:#fff;}";
echo ".success{color:#4caf50;}.error{color:#f44336;}.warning{color:#ff9800;}";
echo "pre{background:#2a2a2a;padding:15px;border-radius:5px;overflow-x:auto;}";
echo "button{background:#4caf50;color:#fff;border:none;padding:10px 20px;border-radius:5px;cursor:pointer;font-size:16px;margin:10px 0;}";
echo "button:hover{background:#45a049;}</style></head><body>";
echo "<h1>🔧 Correction Base de Données</h1>";

try {
    $db = Config::getConnexion();
    echo "<p class='success'>✅ Connexion à la base de données réussie</p>";
    
    // Vérifier si la colonne existe déjà
    $checkColumn = $db->query("SHOW COLUMNS FROM reclamations LIKE 'categorie'");
    if ($checkColumn->rowCount() > 0) {
        echo "<p class='success'>✅ La colonne 'categorie' existe déjà. Aucune action nécessaire.</p>";
    } else {
        echo "<p class='warning'>⚠️ La colonne 'categorie' n'existe pas. Ajout en cours...</p>";
        
        // Ajouter la colonne
        try {
            $db->exec("ALTER TABLE reclamations ADD COLUMN categorie VARCHAR(50) DEFAULT 'Other' AFTER description");
            echo "<p class='success'>✅ Colonne 'categorie' ajoutée avec succès</p>";
        } catch (PDOException $e) {
            // Si AFTER description échoue, essayer sans AFTER
            try {
                $db->exec("ALTER TABLE reclamations ADD COLUMN categorie VARCHAR(50) DEFAULT 'Other'");
                echo "<p class='success'>✅ Colonne 'categorie' ajoutée avec succès (sans position spécifique)</p>";
            } catch (PDOException $e2) {
                echo "<p class='error'>❌ Erreur lors de l'ajout de la colonne: " . htmlspecialchars($e2->getMessage()) . "</p>";
                throw $e2;
            }
        }
        
        // Créer l'index
        try {
            $db->exec("CREATE INDEX idx_categorie ON reclamations(categorie)");
            echo "<p class='success'>✅ Index 'idx_categorie' créé avec succès</p>";
        } catch (PDOException $e) {
            // L'index existe peut-être déjà
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                echo "<p class='warning'>⚠️ L'index existe déjà</p>";
            } else {
                echo "<p class='warning'>⚠️ Erreur lors de la création de l'index: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        }
        
        // Mettre à jour les réclamations existantes
        try {
            $updateResult = $db->exec("UPDATE reclamations SET categorie = 'Other' WHERE categorie IS NULL");
            echo "<p class='success'>✅ " . $updateResult . " réclamation(s) mise(s) à jour avec la catégorie par défaut 'Other'</p>";
        } catch (PDOException $e) {
            echo "<p class='warning'>⚠️ Erreur lors de la mise à jour: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
    
    // Vérifier les statistiques
    echo "<h2>📊 Vérification des statistiques:</h2>";
    try {
        $statsQuery = $db->query("SELECT categorie, COUNT(*) as count FROM reclamations GROUP BY categorie ORDER BY count DESC");
        $stats = $statsQuery->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($stats)) {
            echo "<pre>";
            foreach ($stats as $stat) {
                echo htmlspecialchars($stat['categorie']) . ": " . $stat['count'] . " réclamation(s)\n";
            }
            echo "</pre>";
        } else {
            echo "<p class='warning'>⚠️ Aucune réclamation trouvée dans la base de données</p>";
        }
    } catch (PDOException $e) {
        echo "<p class='error'>❌ Erreur lors de la récupération des statistiques: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
    echo "<hr>";
    echo "<p><strong>✅ Correction terminée !</strong></p>";
    echo "<p><a href='dashboard.php' style='color:#4caf50;'>← Retour au Dashboard</a></p>";
    
} catch (PDOException $e) {
    echo "<p class='error'>❌ Erreur PDO: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<h2>🔧 Solution manuelle:</h2>";
    echo "<p>Exécutez le script SQL suivant dans phpMyAdmin:</p>";
    echo "<pre>";
    echo "ALTER TABLE reclamations ADD COLUMN categorie VARCHAR(50) DEFAULT 'Other';\n";
    echo "CREATE INDEX idx_categorie ON reclamations(categorie);\n";
    echo "UPDATE reclamations SET categorie = 'Other' WHERE categorie IS NULL;\n";
    echo "</pre>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Erreur: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</body></html>";
?>







