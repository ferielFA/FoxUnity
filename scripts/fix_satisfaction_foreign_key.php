<?php
/**
 * Script pour supprimer la contrainte de clé étrangère sur email dans satisfactions
 * Les évaluations publiques ne nécessitent pas que l'email existe dans users
 */

require_once __DIR__ . '/../config/config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Corriger Contrainte Foreign Key - FoxUnity</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background: #0a0a0a;
            color: #fff;
            padding: 40px;
            line-height: 1.6;
        }
        .container {
            max-width: 900px;
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
        .warning {
            background: rgba(255, 193, 7, 0.2);
            border: 1px solid #ffc107;
            color: #ffc107;
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
        code {
            background: rgba(0, 0, 0, 0.5);
            padding: 10px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            display: block;
            margin: 10px 0;
            overflow-x: auto;
            white-space: pre-wrap;
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
            margin-right: 10px;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 122, 0, 0.3);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Correction de la contrainte Foreign Key</h1>
        
        <?php
        try {
            $db = Config::getConnexion();
            
            if (!$db) {
                echo '<div class="error">❌ Erreur: Impossible de se connecter à la base de données.</div>';
                exit;
            }
            
            echo '<div class="success">✅ Connexion à la base de données réussie.</div>';
            
            // Vérifier si la table existe
            $tableCheck = $db->query("SHOW TABLES LIKE 'satisfactions'");
            if ($tableCheck->rowCount() == 0) {
                echo '<div class="error">❌ La table satisfactions n\'existe pas.</div>';
                echo '<div class="info">💡 Exécutez d\'abord: <a href="create_satisfactions_table.php" style="color: #ff7a00;">create_satisfactions_table.php</a></div>';
                exit;
            }
            
            echo '<div class="success">✅ La table satisfactions existe.</div>';
            
            // Vérifier les contraintes de clé étrangère existantes
            echo '<h2 style="color: #ff7a00; margin-top: 30px;">Contraintes Foreign Key actuelles</h2>';
            
            $constraints = $db->query("
                SELECT 
                    CONSTRAINT_NAME,
                    COLUMN_NAME,
                    REFERENCED_TABLE_NAME,
                    REFERENCED_COLUMN_NAME
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'satisfactions'
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ");
            
            $fkFound = false;
            $emailFkFound = false;
            
            while ($constraint = $constraints->fetch(PDO::FETCH_ASSOC)) {
                $fkFound = true;
                echo '<div class="info">';
                echo 'Contrainte: <strong>' . htmlspecialchars($constraint['CONSTRAINT_NAME']) . '</strong><br>';
                echo 'Colonne: ' . htmlspecialchars($constraint['COLUMN_NAME']) . '<br>';
                echo 'Table référencée: ' . htmlspecialchars($constraint['REFERENCED_TABLE_NAME']) . '<br>';
                echo 'Colonne référencée: ' . htmlspecialchars($constraint['REFERENCED_COLUMN_NAME']) . '<br>';
                echo '</div>';
                
                if ($constraint['COLUMN_NAME'] === 'email' && $constraint['REFERENCED_TABLE_NAME'] === 'users') {
                    $emailFkFound = true;
                }
            }
            
            if (!$fkFound) {
                echo '<div class="success">✅ Aucune contrainte Foreign Key trouvée. La table est déjà correcte.</div>';
            } else {
                if ($emailFkFound) {
                    echo '<div class="warning">⚠️ Contrainte Foreign Key trouvée sur la colonne email. Cette contrainte empêche les évaluations publiques.</div>';
                    
                    // Supprimer la contrainte
                    echo '<h2 style="color: #ff7a00; margin-top: 30px;">Suppression de la contrainte</h2>';
                    
                    try {
                        // Méthode 1: Trouver le nom exact de la contrainte via INFORMATION_SCHEMA
                        $constraintName = null;
                        $constraints2 = $db->query("
                            SELECT CONSTRAINT_NAME
                            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                            WHERE TABLE_SCHEMA = DATABASE()
                            AND TABLE_NAME = 'satisfactions'
                            AND COLUMN_NAME = 'email'
                            AND REFERENCED_TABLE_NAME = 'users'
                        ");
                        
                        if ($constraint = $constraints2->fetch(PDO::FETCH_ASSOC)) {
                            $constraintName = $constraint['CONSTRAINT_NAME'];
                        } else {
                            // Méthode 2: Chercher via REFERENTIAL_CONSTRAINTS
                            $constraints3 = $db->query("
                                SELECT CONSTRAINT_NAME
                                FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS
                                WHERE CONSTRAINT_SCHEMA = DATABASE()
                                AND TABLE_NAME = 'satisfactions'
                            ");
                            
                            while ($const = $constraints3->fetch(PDO::FETCH_ASSOC)) {
                                // Vérifier si cette contrainte concerne la colonne email
                                $checkCol = $db->query("
                                    SELECT COLUMN_NAME
                                    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                                    WHERE TABLE_SCHEMA = DATABASE()
                                    AND TABLE_NAME = 'satisfactions'
                                    AND CONSTRAINT_NAME = '" . $const['CONSTRAINT_NAME'] . "'
                                    AND COLUMN_NAME = 'email'
                                ");
                                
                                if ($checkCol->rowCount() > 0) {
                                    $constraintName = $const['CONSTRAINT_NAME'];
                                    break;
                                }
                            }
                        }
                        
                        if ($constraintName) {
                            echo '<div class="info">🔍 Nom de la contrainte trouvé: <strong>' . htmlspecialchars($constraintName) . '</strong></div>';
                            
                            // Supprimer la contrainte
                            $sql = "ALTER TABLE satisfactions DROP FOREIGN KEY `" . $constraintName . "`";
                            echo '<div class="info">📝 Exécution de: <code>' . htmlspecialchars($sql) . '</code></div>';
                            
                            $db->exec($sql);
                            
                            echo '<div class="success">✅ Contrainte Foreign Key supprimée avec succès !</div>';
                            
                            // Vérifier qu'elle a bien été supprimée
                            $verify = $db->query("
                                SELECT CONSTRAINT_NAME
                                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                                WHERE TABLE_SCHEMA = DATABASE()
                                AND TABLE_NAME = 'satisfactions'
                                AND COLUMN_NAME = 'email'
                                AND REFERENCED_TABLE_NAME = 'users'
                            ");
                            
                            if ($verify->rowCount() == 0) {
                                echo '<div class="success">✅ Vérification: La contrainte a bien été supprimée.</div>';
                                echo '<div class="success">🎉 Vous pouvez maintenant ajouter des évaluations avec n\'importe quel email !</div>';
                                
                                // Test d'insertion pour confirmer
                                try {
                                    $testEmail = 'test_' . time() . '@test.com';
                                    $testQuery = $db->prepare("INSERT INTO satisfactions (id_reclamation, email, rating, commentaire, date_evaluation) VALUES (999999, ?, 5, 'Test', NOW())");
                                    $testResult = $testQuery->execute([$testEmail]);
                                    
                                    if ($testResult) {
                                        $testId = $db->lastInsertId();
                                        echo '<div class="success">✅ Test d\'insertion réussi ! ID: ' . $testId . '</div>';
                                        // Supprimer le test
                                        $db->prepare("DELETE FROM satisfactions WHERE id_satisfaction = ?")->execute([$testId]);
                                        echo '<div class="info">ℹ️ L\'enregistrement de test a été supprimé.</div>';
                                    }
                                } catch (PDOException $testE) {
                                    if ($testE->getCode() == 1452) {
                                        echo '<div class="error">❌ Le test d\'insertion échoue toujours. La contrainte pourrait être sur une autre colonne.</div>';
                                    } else {
                                        echo '<div class="info">ℹ️ Erreur de test (normal si id_reclamation 999999 n\'existe pas): ' . htmlspecialchars($testE->getMessage()) . '</div>';
                                    }
                                }
                            } else {
                                echo '<div class="error">❌ La contrainte existe toujours. Vérifiez manuellement.</div>';
                            }
                        } else {
                            echo '<div class="warning">⚠️ Impossible de trouver automatiquement le nom de la contrainte.</div>';
                            echo '<div class="info">💡 Tentative de suppression avec le nom probable: fk_satisfactions_user</div>';
                            
                            // Essayer avec le nom probable
                            try {
                                $db->exec("ALTER TABLE satisfactions DROP FOREIGN KEY `fk_satisfactions_user`");
                                echo '<div class="success">✅ Contrainte supprimée avec le nom fk_satisfactions_user !</div>';
                            } catch (PDOException $e2) {
                                echo '<div class="error">❌ Impossible de supprimer avec ce nom. Erreur: ' . htmlspecialchars($e2->getMessage()) . '</div>';
                                echo '<div class="info">💡 Vous devrez peut-être supprimer la contrainte manuellement via phpMyAdmin.</div>';
                                echo '<div class="info">📋 Commande SQL à exécuter dans phpMyAdmin:</div>';
                                echo '<code>SHOW CREATE TABLE satisfactions;</code>';
                                echo '<div class="info">Puis utilisez le nom exact de la contrainte dans:</div>';
                                echo '<code>ALTER TABLE satisfactions DROP FOREIGN KEY nom_de_la_contrainte;</code>';
                            }
                        }
                    } catch (PDOException $e) {
                        echo '<div class="error">❌ Erreur lors de la suppression de la contrainte:</div>';
                        echo '<code>' . htmlspecialchars($e->getMessage()) . '</code>';
                        echo '<code>Code: ' . $e->getCode() . '</code>';
                    }
                } else {
                    echo '<div class="info">ℹ️ La contrainte Foreign Key existe mais ne concerne pas la colonne email. Aucune action nécessaire.</div>';
                }
            }
            
        } catch (PDOException $e) {
            echo '<div class="error">❌ Erreur PDO: ' . htmlspecialchars($e->getMessage()) . '</div>';
        } catch (Exception $e) {
            echo '<div class="error">❌ Erreur: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        ?>
        
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid rgba(255, 255, 255, 0.1);">
            <a href="test_satisfaction_simple.php" class="btn">🧪 Tester à nouveau</a>
            <a href="check_satisfaction_table.php" class="btn">🔍 Diagnostic</a>
            <a href="view/front/public_reclamations.php" class="btn">← Retour</a>
        </div>
    </div>
</body>
</html>

