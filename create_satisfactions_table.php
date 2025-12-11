<?php
/**
 * Script pour créer automatiquement la table satisfactions
 */

require_once __DIR__ . '/config/config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer Table Satisfactions - FoxUnity</title>
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
            padding: 10px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            display: block;
            margin: 10px 0;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Création de la table satisfactions</h1>
        
        <?php
        try {
            $db = Config::getConnexion();
            
            if (!$db) {
                echo '<div class="error">❌ Erreur: Impossible de se connecter à la base de données.</div>';
                exit;
            }
            
            echo '<div class="success">✅ Connexion à la base de données réussie.</div>';
            
            // Vérifier si la table existe déjà
            $checkTable = $db->query("SHOW TABLES LIKE 'satisfactions'");
            if ($checkTable->rowCount() > 0) {
                echo '<div class="info">ℹ️ La table satisfactions existe déjà.</div>';
                echo '<div class="info">💡 Si vous voulez la recréer, utilisez le script de correction ou supprimez-la manuellement.</div>';
                echo '<div style="margin-top: 20px;"><a href="check_satisfactions_table.php" class="btn">Vérifier la table</a></div>';
                exit;
            }
            
            // Lire le fichier SQL
            $sqlFile = __DIR__ . '/.vscode/database/create_satisfactions_table.sql';
            if (!file_exists($sqlFile)) {
                echo '<div class="error">❌ Le fichier SQL n\'existe pas: ' . htmlspecialchars($sqlFile) . '</div>';
                echo '<div class="info">💡 Création de la table avec le SQL intégré...</div>';
                
                // Créer la table directement
                $sql = "CREATE TABLE IF NOT EXISTS satisfactions (
                    id_satisfaction INT AUTO_INCREMENT PRIMARY KEY,
                    id_reclamation INT NOT NULL,
                    email VARCHAR(255) NOT NULL,
                    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
                    commentaire TEXT,
                    date_evaluation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (id_reclamation) REFERENCES reclamations(id_reclamation) ON DELETE CASCADE,
                    UNIQUE KEY unique_reclamation_email (id_reclamation, email),
                    INDEX idx_email (email),
                    INDEX idx_rating (rating),
                    INDEX idx_date (date_evaluation)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            } else {
                $sql = file_get_contents($sqlFile);
                // Extraire seulement la commande CREATE TABLE
                if (preg_match('/CREATE TABLE[^;]+;/is', $sql, $matches)) {
                    $sql = $matches[0];
                }
            }
            
            echo '<div class="info">📝 Exécution du script SQL...</div>';
            echo '<code>' . htmlspecialchars($sql) . '</code>';
            
            // Exécuter le SQL
            $result = $db->exec($sql);
            
            if ($result !== false) {
                echo '<div class="success">✅ Table satisfactions créée avec succès !</div>';
                
                // Vérifier la création
                $verify = $db->query("SHOW TABLES LIKE 'satisfactions'");
                if ($verify->rowCount() > 0) {
                    echo '<div class="success">✅ Vérification: La table existe maintenant.</div>';
                    
                    // Vérifier les contraintes
                    $indexes = $db->query("SHOW INDEX FROM satisfactions WHERE Key_name = 'unique_reclamation_email'");
                    if ($indexes->rowCount() > 0) {
                        echo '<div class="success">✅ La contrainte UNIQUE correcte (unique_reclamation_email) est active.</div>';
                        echo '<div class="success">🎉 La table est configurée pour permettre plusieurs évaluations par réclamation (une par email).</div>';
                    } else {
                        echo '<div class="error">⚠️ La contrainte unique_reclamation_email n\'a pas été créée. Exécutez fix_satisfactions_table.php</div>';
                    }
                }
            } else {
                $errorInfo = $db->errorInfo();
                echo '<div class="error">❌ Erreur lors de la création de la table.</div>';
                if (isset($errorInfo[2])) {
                    echo '<div class="error">Détails: ' . htmlspecialchars($errorInfo[2]) . '</div>';
                }
            }
            
        } catch (PDOException $e) {
            echo '<div class="error">❌ Erreur PDO: ' . htmlspecialchars($e->getMessage()) . '</div>';
            echo '<div class="info">💡 Vérifiez que la table reclamations existe et que les permissions sont correctes.</div>';
        } catch (Exception $e) {
            echo '<div class="error">❌ Erreur: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        ?>
        
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid rgba(255, 255, 255, 0.1);">
            <a href="check_satisfactions_table.php" class="btn">🔍 Vérifier la table</a>
            <a href="view/front/public_reclamations.php" class="btn">← Retour aux évaluations</a>
        </div>
    </div>
</body>
</html>




