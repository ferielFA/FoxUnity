<?php
/**
 * Script pour vérifier l'état de la table satisfactions
 */

require_once __DIR__ . '/config/config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification Table Satisfactions - FoxUnity</title>
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
        h2 {
            color: #ff7a00;
            margin-top: 30px;
            margin-bottom: 15px;
            font-size: 20px;
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            background: rgba(0, 0, 0, 0.3);
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        th {
            background: rgba(255, 122, 0, 0.2);
            color: #ff7a00;
            font-weight: 600;
        }
        code {
            background: rgba(0, 0, 0, 0.5);
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            display: block;
            padding: 10px;
            margin: 10px 0;
            overflow-x: auto;
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
        .btn-danger {
            background: linear-gradient(135deg, #dc3545, #c82333);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Vérification de la table satisfactions</h1>
        
        <?php
        try {
            $db = Config::getConnexion();
            
            if (!$db) {
                echo '<div class="error">❌ Erreur: Impossible de se connecter à la base de données.</div>';
                exit;
            }
            
            echo '<div class="success">✅ Connexion à la base de données réussie.</div>';
            
            // Vérifier si la table existe
            $checkTable = $db->query("SHOW TABLES LIKE 'satisfactions'");
            if ($checkTable->rowCount() == 0) {
                echo '<div class="error">❌ La table satisfactions n\'existe pas.</div>';
                echo '<div class="info">💡 Cliquez sur le bouton ci-dessous pour créer la table automatiquement.</div>';
                echo '<div style="margin-top: 20px;"><a href="create_satisfactions_table.php" class="btn">🔧 Créer la table maintenant</a></div>';
                echo '<div style="margin-top: 10px; color: #aaa; font-size: 14px;">Ou exécutez manuellement le script SQL: <code>.vscode/database/create_satisfactions_table.sql</code></div>';
                exit;
            }
            
            echo '<div class="success">✅ La table satisfactions existe.</div>';
            
            // Afficher la structure de la table
            echo '<h2>Structure de la table</h2>';
            $columns = $db->query("DESCRIBE satisfactions");
            echo '<table>';
            echo '<tr><th>Colonne</th><th>Type</th><th>Null</th><th>Clé</th><th>Défaut</th><th>Extra</th></tr>';
            while ($col = $columns->fetch(PDO::FETCH_ASSOC)) {
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
            
            // Vérifier les index et contraintes
            echo '<h2>Index et contraintes</h2>';
            $indexes = $db->query("SHOW INDEX FROM satisfactions");
            $indexData = $indexes->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($indexData)) {
                echo '<div class="warning">⚠️ Aucun index trouvé.</div>';
            } else {
                echo '<table>';
                echo '<tr><th>Nom</th><th>Colonne</th><th>Unique</th><th>Type</th></tr>';
                $processedIndexes = [];
                foreach ($indexData as $idx) {
                    $keyName = $idx['Key_name'];
                    if (!isset($processedIndexes[$keyName])) {
                        $processedIndexes[$keyName] = [
                            'name' => $keyName,
                            'columns' => [],
                            'unique' => $idx['Non_unique'] == 0 ? 'Oui' : 'Non',
                            'type' => $idx['Index_type']
                        ];
                    }
                    $processedIndexes[$keyName]['columns'][] = $idx['Column_name'];
                }
                
                foreach ($processedIndexes as $idx) {
                    echo '<tr>';
                    echo '<td><strong>' . htmlspecialchars($idx['name']) . '</strong></td>';
                    echo '<td>' . htmlspecialchars(implode(', ', $idx['columns'])) . '</td>';
                    echo '<td>' . $idx['unique'] . '</td>';
                    echo '<td>' . htmlspecialchars($idx['type']) . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
                
                // Vérifier la contrainte problématique
                $hasOldConstraint = false;
                $hasNewConstraint = false;
                
                foreach ($processedIndexes as $idx) {
                    if ($idx['name'] === 'unique_reclamation' && $idx['unique'] === 'Oui') {
                        $hasOldConstraint = true;
                        echo '<div class="error">❌ PROBLÈME DÉTECTÉ: L\'ancienne contrainte UNIQUE (unique_reclamation) existe encore !</div>';
                        echo '<div class="warning">⚠️ Cette contrainte empêche plusieurs évaluations par réclamation.</div>';
                    }
                    if ($idx['name'] === 'unique_reclamation_email' && $idx['unique'] === 'Oui') {
                        $hasNewConstraint = true;
                        echo '<div class="success">✅ La bonne contrainte UNIQUE (unique_reclamation_email) existe.</div>';
                    }
                }
                
                if (!$hasNewConstraint && !$hasOldConstraint) {
                    echo '<div class="warning">⚠️ Aucune contrainte UNIQUE trouvée sur (id_reclamation, email).</div>';
                }
            }
            
            // Statistiques
            echo '<h2>Statistiques</h2>';
            $count = $db->query("SELECT COUNT(*) as total FROM satisfactions")->fetch(PDO::FETCH_ASSOC);
            echo '<div class="info">📊 Nombre total d\'évaluations: <strong>' . $count['total'] . '</strong></div>';
            
            if ($count['total'] > 0) {
                $stats = $db->query("SELECT COUNT(DISTINCT id_reclamation) as reclamations, COUNT(DISTINCT email) as emails FROM satisfactions")->fetch(PDO::FETCH_ASSOC);
                echo '<div class="info">📊 Réclamations évaluées: <strong>' . $stats['reclamations'] . '</strong></div>';
                echo '<div class="info">📊 Utilisateurs ayant évalué: <strong>' . $stats['emails'] . '</strong></div>';
            }
            
        } catch (PDOException $e) {
            echo '<div class="error">❌ Erreur PDO: ' . htmlspecialchars($e->getMessage()) . '</div>';
        } catch (Exception $e) {
            echo '<div class="error">❌ Erreur: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        ?>
        
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid rgba(255, 255, 255, 0.1);">
            <a href="fix_satisfactions_table.php" class="btn">🔧 Corriger la table</a>
            <a href="view/front/public_reclamations.php" class="btn">← Retour aux évaluations</a>
        </div>
    </div>
</body>
</html>

