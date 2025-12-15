<?php
/**
 * Script de test simple pour l'ajout d'évaluation
 * Utilise les mêmes paramètres que le formulaire
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../controllers/SatisfactionController.php';
require_once __DIR__ . '/../models/Satisfaction.php';

// Simuler les données du formulaire
$id_reclamation = isset($_GET['id_reclamation']) ? intval($_GET['id_reclamation']) : 0;
$email = isset($_GET['email']) ? $_GET['email'] : 'test_' . time() . '@test.com';
$rating = isset($_GET['rating']) ? intval($_GET['rating']) : 5;
$commentaire = isset($_GET['commentaire']) ? $_GET['commentaire'] : 'Test depuis script simple';

if (empty($id_reclamation)) {
    // Récupérer une réclamation existante
    $db = Config::getConnexion();
    $reclamationCheck = $db->query("SELECT id_reclamation FROM reclamations LIMIT 1");
    $reclamationData = $reclamationCheck->fetch(PDO::FETCH_ASSOC);
    if ($reclamationData) {
        $id_reclamation = $reclamationData['id_reclamation'];
    }
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Test Simple Satisfaction</title>
    <style>
        body { font-family: Arial; background: #0a0a0a; color: #fff; padding: 20px; }
        .success { background: rgba(76, 175, 80, 0.2); border: 1px solid #4caf50; padding: 15px; margin: 10px 0; border-radius: 8px; }
        .error { background: rgba(220, 53, 69, 0.2); border: 1px solid #dc3545; padding: 15px; margin: 10px 0; border-radius: 8px; }
        .info { background: rgba(33, 150, 243, 0.2); border: 1px solid #2196f3; padding: 15px; margin: 10px 0; border-radius: 8px; }
        code { background: rgba(0,0,0,0.5); padding: 10px; display: block; margin: 10px 0; border-radius: 4px; white-space: pre-wrap; }
    </style>
</head>
<body>
    <h1>🧪 Test Simple d'Ajout d'Évaluation</h1>
    
    <div class="info">
        <strong>Paramètres utilisés:</strong><br>
        ID Réclamation: <?php echo $id_reclamation; ?><br>
        Email: <?php echo htmlspecialchars($email); ?><br>
        Rating: <?php echo $rating; ?><br>
        Commentaire: <?php echo htmlspecialchars($commentaire); ?>
    </div>
    
    <?php
    if (empty($id_reclamation)) {
        echo '<div class="error">❌ Aucune réclamation trouvée. Créez d\'abord une réclamation.</div>';
        exit;
    }
    
    try {
        $satisfactionController = new SatisfactionController();
        
        echo '<div class="info">🔄 Tentative d\'ajout d\'évaluation...</div>';
        
        $result = $satisfactionController->addSatisfaction($id_reclamation, $email, $rating, $commentaire);
        
        echo '<div class="info">📊 Résultat reçu: ' . var_export($result, true) . '</div>';
        echo '<div class="info">📊 Type: ' . gettype($result) . '</div>';
        
        if ($result && $result !== false && (is_numeric($result) ? $result > 0 : true)) {
            echo '<div class="success">✅ SUCCÈS ! Évaluation ajoutée avec ID: ' . $result . '</div>';
            
            // Vérifier que l'évaluation existe bien
            $db = Config::getConnexion();
            $check = $db->prepare("SELECT * FROM satisfactions WHERE id_satisfaction = ?");
            $check->execute([$result]);
            $data = $check->fetch(PDO::FETCH_ASSOC);
            
            if ($data) {
                echo '<div class="success">✅ Vérification: L\'évaluation existe bien en base de données</div>';
                echo '<code>' . print_r($data, true) . '</code>';
            } else {
                echo '<div class="error">❌ PROBLÈME: L\'ID retourné n\'existe pas en base de données !</div>';
            }
        } else {
            echo '<div class="error">❌ ÉCHEC ! Le résultat est invalide.</div>';
            echo '<code>Résultat: ' . var_export($result, true) . '</code>';
        }
        
    } catch (Exception $e) {
        echo '<div class="error">❌ EXCEPTION: ' . htmlspecialchars($e->getMessage()) . '</div>';
        echo '<code>Fichier: ' . $e->getFile() . ' ligne ' . $e->getLine() . '</code>';
        echo '<code>Stack trace:</code>';
        echo '<code>' . htmlspecialchars($e->getTraceAsString()) . '</code>';
    }
    ?>
    
    <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.1);">
        <p><strong>Pour tester avec vos propres paramètres:</strong></p>
        <p>
            <code>
                test_satisfaction_simple.php?id_reclamation=XXX&email=votre@email.com&rating=5&commentaire=Votre commentaire
            </code>
        </p>
        <p>
            <a href="check_satisfaction_table.php" style="color: #ff7a00;">🔍 Diagnostic de la table</a> | 
            <a href="test_satisfaction_insert.php" style="color: #ff7a00;">🧪 Test complet</a> |
            <a href="view/front/public_reclamations.php" style="color: #ff7a00;">← Retour</a>
        </p>
    </div>
</body>
</html>


