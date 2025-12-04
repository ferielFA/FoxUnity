<?php
/**
 * Script pour corriger la contrainte UNIQUE de la table satisfactions
 * Permet plusieurs évaluations par réclamation (une par email)
 */

require_once __DIR__ . '/config/config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Satisfactions Table - FoxUnity</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background: #0a0a0a;
            color: #fff;
            padding: 40px;
            line-height: 1.6;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: rgba(20, 20, 20, 0.95);
            padding: 30px;
            border-radius: 10px;
            border: 2px solid #ff7a00;
        }
        h1 {
            color: #ff7a00;
            margin-bottom: 20px;
        }
        .success {
            background: rgba(76, 175, 80, 0.2);
            border: 1px solid #4caf50;
            color: #4caf50;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
        .error {
            background: rgba(220, 53, 69, 0.2);
            border: 1px solid #dc3545;
            color: #dc3545;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
        .info {
            background: rgba(33, 150, 243, 0.2);
            border: 1px solid #2196f3;
            color: #2196f3;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
        .btn {
            background: linear-gradient(135deg, #ff7a00, #ff4f00);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            margin-top: 20px;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 122, 0, 0.3);
        }
        code {
            background: rgba(0, 0, 0, 0.5);
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Correction de la table satisfactions</h1>
        
        <?php
        try {
            $db = Config::getConnexion();
            
            if (!$db) {
                echo '<div class="error">❌ Erreur: Impossible de se connecter à la base de données.</div>';
                exit;
            }
            
            // Vérifier si la table existe
            $checkTable = $db->query("SHOW TABLES LIKE 'satisfactions'");
            if ($checkTable->rowCount() == 0) {
                echo '<div class="error">❌ La table satisfactions n\'existe pas. Veuillez d\'abord créer la table.</div>';
                echo '<div class="info">💡 Exécutez le script: <code>.vscode/database/create_satisfactions_table.sql</code></div>';
                exit;
            }
            
            echo '<div class="info">✅ La table satisfactions existe.</div>';
            
            // Vérifier les contraintes existantes
            $constraints = $db->query("SHOW INDEX FROM satisfactions WHERE Key_name = 'unique_reclamation' OR Key_name = 'unique_reclamation_email'");
            $existingConstraints = $constraints->fetchAll(PDO::FETCH_ASSOC);
            
            $hasOldConstraint = false;
            $hasNewConstraint = false;
            
            foreach ($existingConstraints as $constraint) {
                if ($constraint['Key_name'] === 'unique_reclamation') {
                    $hasOldConstraint = true;
                }
                if ($constraint['Key_name'] === 'unique_reclamation_email') {
                    $hasNewConstraint = true;
                }
            }
            
            if ($hasNewConstraint) {
                echo '<div class="success">✅ La contrainte correcte (unique_reclamation_email) existe déjà. La table est déjà configurée correctement !</div>';
            } else {
                if ($hasOldConstraint) {
                    echo '<div class="info">⚠️ Ancienne contrainte détectée. Correction en cours...</div>';
                    
                    // Supprimer l'ancienne contrainte
                    try {
                        $db->exec("ALTER TABLE satisfactions DROP INDEX unique_reclamation");
                        echo '<div class="success">✅ Ancienne contrainte supprimée.</div>';
                    } catch (PDOException $e) {
                        // La contrainte pourrait ne pas exister ou avoir un nom différent
                        echo '<div class="info">ℹ️ Tentative de suppression de l\'ancienne contrainte: ' . $e->getMessage() . '</div>';
                    }
                }
                
                // Ajouter la nouvelle contrainte
                try {
                    $db->exec("ALTER TABLE satisfactions ADD UNIQUE KEY unique_reclamation_email (id_reclamation, email)");
                    echo '<div class="success">✅ Nouvelle contrainte ajoutée avec succès !</div>';
                    echo '<div class="success">🎉 La table est maintenant configurée pour permettre plusieurs évaluations par réclamation (une par email).</div>';
                } catch (PDOException $e) {
                    if (strpos($e->getMessage(), "Duplicate key name") !== false) {
                        echo '<div class="info">ℹ️ La contrainte existe déjà sous un autre nom. Vérification...</div>';
                    } else {
                        echo '<div class="error">❌ Erreur lors de l\'ajout de la contrainte: ' . htmlspecialchars($e->getMessage()) . '</div>';
                    }
                }
            }
            
            // Vérification finale
            $finalCheck = $db->query("SHOW INDEX FROM satisfactions WHERE Key_name = 'unique_reclamation_email'");
            if ($finalCheck->rowCount() > 0) {
                echo '<div class="success">✅ Vérification finale: La contrainte unique_reclamation_email est active.</div>';
            }
            
        } catch (PDOException $e) {
            echo '<div class="error">❌ Erreur PDO: ' . htmlspecialchars($e->getMessage()) . '</div>';
        } catch (Exception $e) {
            echo '<div class="error">❌ Erreur: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        ?>
        
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid rgba(255, 255, 255, 0.1);">
            <a href="view/front/public_reclamations.php" class="btn">← Retour aux évaluations publiques</a>
        </div>
    </div>
</body>
</html>




