<?php
/**
 * Script SIMPLE pour supprimer la contrainte Foreign Key sur email
 * Version simplifiée qui supprime directement la contrainte
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Fix Foreign Key - Simple</title>
    <style>
        body { font-family: Arial; background: #0a0a0a; color: #fff; padding: 40px; }
        .container { max-width: 800px; margin: 0 auto; background: rgba(20,20,20,0.95); padding: 30px; border-radius: 10px; border: 2px solid #ff7a00; }
        h1 { color: #ff7a00; }
        .success { background: rgba(76,175,80,0.2); border: 1px solid #4caf50; color: #4caf50; padding: 15px; margin: 15px 0; border-radius: 8px; }
        .error { background: rgba(220,53,69,0.2); border: 1px solid #dc3545; color: #dc3545; padding: 15px; margin: 15px 0; border-radius: 8px; }
        .info { background: rgba(33,150,243,0.2); border: 1px solid #2196f3; color: #2196f3; padding: 15px; margin: 15px 0; border-radius: 8px; }
        code { background: rgba(0,0,0,0.5); padding: 10px; display: block; margin: 10px 0; border-radius: 4px; white-space: pre-wrap; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Suppression de la contrainte Foreign Key (Version Simple)</h1>
        
        <?php
        try {
            $db = Config::getConnexion();
            
            if (!$db) {
                echo '<div class="error">❌ Erreur de connexion à la base de données.</div>';
                exit;
            }
            
            echo '<div class="success">✅ Connexion réussie.</div>';
            
            // Liste des noms possibles de la contrainte
            $possibleNames = [
                'fk_satisfactions_user',
                'fk_satisfactions_users',
                'satisfactions_ibfk_1',
                'satisfactions_ibfk_2',
                'satisfactions_ibfk_3'
            ];
            
            // Méthode 1: Trouver le nom exact via SHOW CREATE TABLE
            echo '<h2 style="color: #ff7a00;">Méthode 1: Recherche du nom de la contrainte</h2>';
            
            $createTable = $db->query("SHOW CREATE TABLE satisfactions");
            $tableInfo = $createTable->fetch(PDO::FETCH_ASSOC);
            
            if (isset($tableInfo['Create Table'])) {
                $createStatement = $tableInfo['Create Table'];
                echo '<div class="info">📋 Structure de la table:</div>';
                echo '<code>' . htmlspecialchars($createStatement) . '</code>';
                
                // Extraire les noms de contraintes Foreign Key
                preg_match_all('/CONSTRAINT\s+`?([^`\s]+)`?\s+FOREIGN KEY.*email.*users/is', $createStatement, $matches);
                
                if (!empty($matches[1])) {
                    $constraintName = $matches[1][0];
                    echo '<div class="info">🔍 Contrainte trouvée: <strong>' . htmlspecialchars($constraintName) . '</strong></div>';
                    
                    // Supprimer la contrainte
                    try {
                        $sql = "ALTER TABLE satisfactions DROP FOREIGN KEY `" . $constraintName . "`";
                        echo '<div class="info">📝 Exécution: <code>' . htmlspecialchars($sql) . '</code></div>';
                        
                        $db->exec($sql);
                        echo '<div class="success">✅ Contrainte supprimée avec succès !</div>';
                    } catch (PDOException $e) {
                        echo '<div class="error">❌ Erreur: ' . htmlspecialchars($e->getMessage()) . '</div>';
                    }
                } else {
                    echo '<div class="info">ℹ️ Aucune contrainte Foreign Key sur email trouvée dans CREATE TABLE.</div>';
                    echo '<div class="info">💡 Tentative avec les noms probables...</div>';
                    
                    // Méthode 2: Essayer avec les noms probables
                    $success = false;
                    foreach ($possibleNames as $name) {
                        try {
                            $sql = "ALTER TABLE satisfactions DROP FOREIGN KEY `" . $name . "`";
                            $db->exec($sql);
                            echo '<div class="success">✅ Contrainte supprimée avec le nom: <strong>' . htmlspecialchars($name) . '</strong></div>';
                            $success = true;
                            break;
                        } catch (PDOException $e) {
                            // Continuer avec le nom suivant
                            continue;
                        }
                    }
                    
                    if (!$success) {
                        echo '<div class="error">❌ Impossible de supprimer avec les noms probables.</div>';
                        echo '<div class="info">💡 Recherche dans INFORMATION_SCHEMA...</div>';
                        
                        // Méthode 3: Recherche dans INFORMATION_SCHEMA
                        $constraints = $db->query("
                            SELECT CONSTRAINT_NAME
                            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                            WHERE TABLE_SCHEMA = DATABASE()
                            AND TABLE_NAME = 'satisfactions'
                            AND COLUMN_NAME = 'email'
                            AND REFERENCED_TABLE_NAME = 'users'
                        ");
                        
                        if ($constraint = $constraints->fetch(PDO::FETCH_ASSOC)) {
                            $constraintName = $constraint['CONSTRAINT_NAME'];
                            echo '<div class="info">🔍 Contrainte trouvée: <strong>' . htmlspecialchars($constraintName) . '</strong></div>';
                            
                            try {
                                $sql = "ALTER TABLE satisfactions DROP FOREIGN KEY `" . $constraintName . "`";
                                $db->exec($sql);
                                echo '<div class="success">✅ Contrainte supprimée avec succès !</div>';
                                $success = true;
                            } catch (PDOException $e) {
                                echo '<div class="error">❌ Erreur: ' . htmlspecialchars($e->getMessage()) . '</div>';
                            }
                        }
                    }
                }
            }
            
            // Vérification finale
            echo '<h2 style="color: #ff7a00; margin-top: 30px;">Vérification finale</h2>';
            
            $verify = $db->query("
                SELECT CONSTRAINT_NAME
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'satisfactions'
                AND COLUMN_NAME = 'email'
                AND REFERENCED_TABLE_NAME = 'users'
            ");
            
            if ($verify->rowCount() == 0) {
                echo '<div class="success">✅ Aucune contrainte Foreign Key sur email trouvée. Le problème est résolu !</div>';
                echo '<div class="success">🎉 Vous pouvez maintenant ajouter des évaluations avec n\'importe quel email.</div>';
            } else {
                echo '<div class="error">❌ La contrainte existe toujours. Essayez de la supprimer manuellement via phpMyAdmin.</div>';
            }
            
        } catch (Exception $e) {
            echo '<div class="error">❌ Erreur: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        ?>
        
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.1);">
            <a href="test_satisfaction_simple.php" style="color: #ff7a00; text-decoration: none; margin-right: 20px;">🧪 Tester</a>
            <a href="view/front/public_reclamations.php" style="color: #ff7a00; text-decoration: none;">← Retour</a>
        </div>
    </div>
</body>
</html>


