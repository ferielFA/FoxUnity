<?php
/**
 * Script de diagnostic pour vérifier la table satisfactions
 */

require_once __DIR__ . '/../config/config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnostic Table Satisfactions - FoxUnity</title>
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
        code {
            background: rgba(0, 0, 0, 0.5);
            padding: 10px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            display: block;
            margin: 10px 0;
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        table th, table td {
            padding: 10px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            text-align: left;
        }
        table th {
            background: rgba(255, 122, 0, 0.2);
            color: #ff7a00;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Diagnostic de la table satisfactions</h1>
        
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
                echo '<div class="info">💡 Solution: <a href="create_satisfactions_table.php" class="btn">Créer la table</a></div>';
                exit;
            }
            
            echo '<div class="success">✅ La table satisfactions existe.</div>';
            
            // Vérifier la structure de la table
            echo '<h2 style="color: #ff7a00; margin-top: 30px;">Structure de la table</h2>';
            $columns = $db->query("SHOW COLUMNS FROM satisfactions");
            echo '<table>';
            echo '<tr><th>Colonne</th><th>Type</th><th>Null</th><th>Clé</th><th>Défaut</th><th>Extra</th></tr>';
            $requiredColumns = ['id_satisfaction', 'id_reclamation', 'email', 'rating', 'commentaire', 'date_evaluation'];
            $foundColumns = [];
            
            while ($col = $columns->fetch(PDO::FETCH_ASSOC)) {
                $foundColumns[] = $col['Field'];
                echo '<tr>';
                echo '<td>' . htmlspecialchars($col['Field']) . '</td>';
                echo '<td>' . htmlspecialchars($col['Type']) . '</td>';
                echo '<td>' . htmlspecialchars($col['Null']) . '</td>';
                echo '<td>' . htmlspecialchars($col['Key']) . '</td>';
                echo '<td>' . htmlspecialchars($col['Default'] ?? 'NULL') . '</td>';
                echo '<td>' . htmlspecialchars($col['Extra']) . '</td>';
                echo '</tr>';
            }
            echo '</table>';
            
            // Vérifier les colonnes requises
            $missingColumns = array_diff($requiredColumns, $foundColumns);
            if (!empty($missingColumns)) {
                echo '<div class="error">❌ Colonnes manquantes: ' . implode(', ', $missingColumns) . '</div>';
            } else {
                echo '<div class="success">✅ Toutes les colonnes requises sont présentes.</div>';
            }
            
            // Vérifier les index et contraintes
            echo '<h2 style="color: #ff7a00; margin-top: 30px;">Index et contraintes</h2>';
            $indexes = $db->query("SHOW INDEX FROM satisfactions");
            $indexList = [];
            while ($idx = $indexes->fetch(PDO::FETCH_ASSOC)) {
                $indexList[$idx['Key_name']][] = $idx['Column_name'];
            }
            
            echo '<table>';
            echo '<tr><th>Nom de l\'index</th><th>Colonnes</th><th>Type</th></tr>';
            foreach ($indexList as $indexName => $columns) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($indexName) . '</td>';
                echo '<td>' . htmlspecialchars(implode(', ', $columns)) . '</td>';
                echo '<td>' . ($indexName == 'PRIMARY' ? 'PRIMARY KEY' : ($indexName == 'unique_reclamation_email' ? 'UNIQUE' : 'INDEX')) . '</td>';
                echo '</tr>';
            }
            echo '</table>';
            
            // Vérifier la contrainte UNIQUE
            if (isset($indexList['unique_reclamation_email'])) {
                echo '<div class="success">✅ La contrainte UNIQUE (id_reclamation, email) est présente.</div>';
            } else {
                echo '<div class="warning">⚠️ La contrainte UNIQUE (id_reclamation, email) est absente.</div>';
                echo '<div class="info">💡 Solution: <a href="fix_satisfactions_table.php" class="btn">Corriger la contrainte</a></div>';
            }
            
            // Test d'insertion
            echo '<h2 style="color: #ff7a00; margin-top: 30px;">Test d\'insertion</h2>';
            try {
                $testEmail = 'test_' . time() . '@test.com';
                $testQuery = $db->prepare("INSERT INTO satisfactions (id_reclamation, email, rating, commentaire, date_evaluation) VALUES (999999, ?, 5, 'Test', NOW())");
                $testResult = $testQuery->execute([$testEmail]);
                
                if ($testResult) {
                    echo '<div class="success">✅ Test d\'insertion réussi.</div>';
                    // Supprimer l'enregistrement de test
                    $db->prepare("DELETE FROM satisfactions WHERE email = ?")->execute([$testEmail]);
                    echo '<div class="info">ℹ️ L\'enregistrement de test a été supprimé.</div>';
                } else {
                    echo '<div class="error">❌ Test d\'insertion échoué.</div>';
                }
            } catch (PDOException $e) {
                echo '<div class="error">❌ Erreur lors du test d\'insertion: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
            
            // Compter les évaluations existantes
            $count = $db->query("SELECT COUNT(*) as total FROM satisfactions")->fetch(PDO::FETCH_ASSOC);
            echo '<div class="info">ℹ️ Nombre total d\'évaluations dans la table: ' . $count['total'] . '</div>';
            
        } catch (PDOException $e) {
            echo '<div class="error">❌ Erreur PDO: ' . htmlspecialchars($e->getMessage()) . '</div>';
        } catch (Exception $e) {
            echo '<div class="error">❌ Erreur: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        ?>
        
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid rgba(255, 255, 255, 0.1);">
            <a href="create_satisfactions_table.php" class="btn">🔧 Créer/Recréer la table</a>
            <a href="fix_satisfactions_table.php" class="btn">🔨 Corriger la contrainte</a>
            <a href="view/front/public_reclamations.php" class="btn">← Retour aux évaluations</a>
        </div>
    </div>
</body>
</html>


