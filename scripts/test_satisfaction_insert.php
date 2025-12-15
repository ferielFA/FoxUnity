<?php
/**
 * Script de test pour vérifier l'insertion dans la table satisfactions
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../controllers/SatisfactionController.php';
require_once __DIR__ . '/../models/Satisfaction.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Insertion Satisfaction - FoxUnity</title>
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
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Test d'insertion dans la table satisfactions</h1>
        
        <?php
        try {
            $db = Config::getConnexion();
            
            if (!$db) {
                echo '<div class="error">❌ Erreur: Impossible de se connecter à la base de données.</div>';
                exit;
            }
            
            echo '<div class="success">✅ Connexion à la base de données réussie.</div>';
            
            // Vérifier que la table existe
            $tableCheck = $db->query("SHOW TABLES LIKE 'satisfactions'");
            if ($tableCheck->rowCount() == 0) {
                echo '<div class="error">❌ La table satisfactions n\'existe pas.</div>';
                echo '<div class="info">💡 Exécutez: <a href="create_satisfactions_table.php" style="color: #ff7a00;">create_satisfactions_table.php</a></div>';
                exit;
            }
            
            echo '<div class="success">✅ La table satisfactions existe.</div>';
            
            // Récupérer une réclamation existante pour le test
            $reclamationCheck = $db->query("SELECT id_reclamation FROM reclamations LIMIT 1");
            $reclamationData = $reclamationCheck->fetch(PDO::FETCH_ASSOC);
            
            if (!$reclamationData) {
                echo '<div class="error">❌ Aucune réclamation trouvée dans la base de données.</div>';
                echo '<div class="info">💡 Créez d\'abord une réclamation pour pouvoir tester.</div>';
                exit;
            }
            
            $testReclamationId = $reclamationData['id_reclamation'];
            echo '<div class="info">ℹ️ Utilisation de la réclamation ID: ' . $testReclamationId . ' pour le test</div>';
            
            // Test 1: Insertion directe en SQL
            echo '<h2 style="color: #ff7a00; margin-top: 30px;">Test 1: Insertion directe en SQL</h2>';
            try {
                $testEmail = 'test_' . time() . '@test.com';
                $testQuery = $db->prepare("INSERT INTO satisfactions (id_reclamation, email, rating, commentaire, date_evaluation) VALUES (?, ?, ?, ?, NOW())");
                $testResult = $testQuery->execute([$testReclamationId, $testEmail, 5, 'Test d\'insertion directe']);
                
                if ($testResult) {
                    $insertId = $db->lastInsertId();
                    echo '<div class="success">✅ Insertion directe réussie. ID: ' . $insertId . '</div>';
                    
                    // Supprimer l'enregistrement de test
                    $db->prepare("DELETE FROM satisfactions WHERE id_satisfaction = ?")->execute([$insertId]);
                    echo '<div class="info">ℹ️ L\'enregistrement de test a été supprimé.</div>';
                } else {
                    $errorInfo = $testQuery->errorInfo();
                    echo '<div class="error">❌ Erreur lors de l\'insertion directe:</div>';
                    echo '<code>' . print_r($errorInfo, true) . '</code>';
                }
            } catch (PDOException $e) {
                echo '<div class="error">❌ Exception PDO lors de l\'insertion directe:</div>';
                echo '<code>' . htmlspecialchars($e->getMessage()) . '</code>';
                echo '<code>Code: ' . $e->getCode() . '</code>';
                echo '<code>ErrorInfo: ' . print_r($e->errorInfo(), true) . '</code>';
            }
            
            // Test 2: Utilisation du modèle Satisfaction
            echo '<h2 style="color: #ff7a00; margin-top: 30px;">Test 2: Utilisation du modèle Satisfaction</h2>';
            try {
                $testEmail2 = 'test_model_' . time() . '@test.com';
                $satisfaction = new Satisfaction($testReclamationId, $testEmail2, 4, 'Test via modèle');
                $result = $satisfaction->save();
                
                if ($result && $result !== false) {
                    echo '<div class="success">✅ Insertion via modèle réussie. ID: ' . $result . '</div>';
                    
                    // Supprimer l'enregistrement de test
                    $db->prepare("DELETE FROM satisfactions WHERE id_satisfaction = ?")->execute([$result]);
                    echo '<div class="info">ℹ️ L\'enregistrement de test a été supprimé.</div>';
                } else {
                    echo '<div class="error">❌ Le modèle a retourné false ou 0.</div>';
                }
            } catch (Exception $e) {
                echo '<div class="error">❌ Exception lors de l\'insertion via modèle:</div>';
                echo '<code>' . htmlspecialchars($e->getMessage()) . '</code>';
                echo '<code>Fichier: ' . $e->getFile() . ' ligne ' . $e->getLine() . '</code>';
            }
            
            // Test 3: Utilisation du contrôleur SatisfactionController
            echo '<h2 style="color: #ff7a00; margin-top: 30px;">Test 3: Utilisation du contrôleur SatisfactionController</h2>';
            try {
                $testEmail3 = 'test_controller_' . time() . '@test.com';
                $satisfactionController = new SatisfactionController();
                $result = $satisfactionController->addSatisfaction($testReclamationId, $testEmail3, 3, 'Test via contrôleur');
                
                if ($result && $result !== false) {
                    echo '<div class="success">✅ Insertion via contrôleur réussie. Résultat: ' . var_export($result, true) . '</div>';
                    
                    // Trouver et supprimer l'enregistrement de test
                    $testRecord = Satisfaction::findByReclamationIdAndEmail($testReclamationId, $testEmail3);
                    if ($testRecord) {
                        $db->prepare("DELETE FROM satisfactions WHERE id_satisfaction = ?")->execute([$testRecord->getIdSatisfaction()]);
                        echo '<div class="info">ℹ️ L\'enregistrement de test a été supprimé.</div>';
                    }
                } else {
                    echo '<div class="error">❌ Le contrôleur a retourné false.</div>';
                }
            } catch (Exception $e) {
                echo '<div class="error">❌ Exception lors de l\'insertion via contrôleur:</div>';
                echo '<code>' . htmlspecialchars($e->getMessage()) . '</code>';
                echo '<code>Fichier: ' . $e->getFile() . ' ligne ' . $e->getLine() . '</code>';
                echo '<code>Stack trace:</code>';
                echo '<code>' . htmlspecialchars($e->getTraceAsString()) . '</code>';
            }
            
            // Afficher la structure de la table
            echo '<h2 style="color: #ff7a00; margin-top: 30px;">Structure de la table</h2>';
            $columns = $db->query("DESCRIBE satisfactions");
            echo '<code>';
            while ($col = $columns->fetch(PDO::FETCH_ASSOC)) {
                echo htmlspecialchars(print_r($col, true));
            }
            echo '</code>';
            
        } catch (Exception $e) {
            echo '<div class="error">❌ Erreur générale: ' . htmlspecialchars($e->getMessage()) . '</div>';
            echo '<code>' . htmlspecialchars($e->getTraceAsString()) . '</code>';
        }
        ?>
        
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid rgba(255, 255, 255, 0.1);">
            <a href="check_satisfaction_table.php" style="color: #ff7a00; text-decoration: none; margin-right: 20px;">🔍 Diagnostic</a>
            <a href="create_satisfactions_table.php" style="color: #ff7a00; text-decoration: none; margin-right: 20px;">🔧 Créer la table</a>
            <a href="view/front/public_reclamations.php" style="color: #ff7a00; text-decoration: none;">← Retour</a>
        </div>
    </div>
</body>
</html>


