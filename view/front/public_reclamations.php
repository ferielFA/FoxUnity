<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Inclure les contrôleurs
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../controllers/reclamationcontroller.php';
require_once __DIR__ . '/../../controllers/ResponseController.php';
require_once __DIR__ . '/../../controllers/SatisfactionController.php';
require_once __DIR__ . '/../../models/Satisfaction.php';

$reclamationController = new ReclamationController();
$responseController = new ResponseController();
$satisfactionController = new SatisfactionController();

// Traitement pour ajouter une évaluation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'rate_reclamation') {
    header('Content-Type: application/json');
    
    $id_reclamation = intval($_POST['id_reclamation'] ?? 0);
    $email = trim($_POST['email'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $rating = intval($_POST['rating'] ?? 0);
    $commentaire = trim($_POST['commentaire'] ?? '');
    
    // Validation
    // Email et nom sont maintenant optionnels - si les deux sont vides, utiliser un identifiant anonyme
    if (empty($email) && empty($name)) {
        // Générer un identifiant anonyme basé sur l'IP et le timestamp
        $anonymousId = 'anonymous_' . md5($_SERVER['REMOTE_ADDR'] . date('Y-m-d') . $id_reclamation);
        $email = $anonymousId;
    } elseif (empty($email) && !empty($name)) {
        // Si seulement le nom est fourni, utiliser le nom comme identifiant
        $email = 'user_' . md5($name . $id_reclamation);
    } elseif (empty($email)) {
        // Si email vide mais on a besoin d'un identifiant
        $email = 'anonymous_' . md5($_SERVER['REMOTE_ADDR'] . date('Y-m-d') . $id_reclamation);
    }
    
    if ($rating < 1 || $rating > 5) {
        echo json_encode(['success' => false, 'message' => 'Note invalide (doit être entre 1 et 5)']);
        exit;
    }
    
    if (empty($id_reclamation) || $id_reclamation <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID de réclamation invalide']);
        exit;
    }
    
    // Stocker le nom dans le commentaire si fourni
    $finalCommentaire = null;
    if (!empty($name) && empty($commentaire)) {
        $finalCommentaire = 'Évalué par: ' . $name;
    } elseif (!empty($name) && !empty($commentaire)) {
        $finalCommentaire = 'Évalué par: ' . $name . ' | ' . $commentaire;
    } elseif (!empty($commentaire)) {
        $finalCommentaire = $commentaire;
    }
    
    // Si le commentaire est vide, utiliser null
    if (empty($finalCommentaire)) {
        $finalCommentaire = null;
    }
    
    try {
        // Vérifier que la réclamation existe
        $reclamationCheck = $reclamationController->getReclamationById($id_reclamation);
        if (!$reclamationCheck) {
            echo json_encode(['success' => false, 'message' => 'Réclamation introuvable.']);
            exit;
        }
        
        // Vérifier que la table satisfactions existe
        $db = Config::getConnexion();
        $tableCheck = $db->query("SHOW TABLES LIKE 'satisfactions'");
        if ($tableCheck->rowCount() == 0) {
            echo json_encode([
                'success' => false, 
                'message' => 'La table satisfactions n\'existe pas. Veuillez exécuter: http://localhost/foxunity/create_satisfactions_table.php'
            ]);
            exit;
        }
        
        // Ajouter l'évaluation avec débogage détaillé
        error_log("🔵 ========== DÉBUT AJOUT ÉVALUATION ==========");
        error_log("🔵 ID Réclamation: $id_reclamation");
        error_log("🔵 Email: " . substr($email, 0, 50));
        error_log("🔵 Rating: $rating");
        error_log("🔵 Commentaire: " . (empty($finalCommentaire) ? 'vide' : substr($finalCommentaire, 0, 50)));
        
        try {
            $result = $satisfactionController->addSatisfaction($id_reclamation, $email, $rating, $finalCommentaire);
            
            error_log("🔵 Résultat de addSatisfaction: " . var_export($result, true));
            error_log("🔵 Type du résultat: " . gettype($result));
            error_log("🔵 Est vide: " . (empty($result) ? 'OUI' : 'NON'));
            error_log("🔵 Est false: " . ($result === false ? 'OUI' : 'NON'));
            error_log("🔵 Est > 0: " . (is_numeric($result) && $result > 0 ? 'OUI' : 'NON'));
            
            if ($result && $result !== false && (is_numeric($result) ? $result > 0 : true)) {
                error_log("✅ Évaluation ajoutée avec succès - ID réclamation: $id_reclamation, Rating: $rating, Email: " . substr($email, 0, 30));
                error_log("🔵 ========== FIN AJOUT ÉVALUATION (SUCCÈS) ==========");
                echo json_encode(['success' => true, 'message' => 'Merci pour votre évaluation !']);
            } else {
                error_log('❌ addSatisfaction a retourné un résultat invalide');
                error_log('❌ Valeur retournée: ' . var_export($result, true));
                error_log('❌ Type: ' . gettype($result));
                
                // Vérifier la structure de la table
                try {
                    $columns = $db->query("SHOW COLUMNS FROM satisfactions");
                    $columnNames = [];
                    while ($col = $columns->fetch(PDO::FETCH_ASSOC)) {
                        $columnNames[] = $col['Field'];
                    }
                    error_log('📋 Colonnes de la table satisfactions: ' . implode(', ', $columnNames));
                } catch (Exception $e) {
                    error_log('❌ Erreur lors de la vérification des colonnes: ' . $e->getMessage());
                }
                
                // Tester une insertion directe pour voir l'erreur exacte
                try {
                    $testEmail = 'test_debug_' . time() . '@test.com';
                    $testQuery = $db->prepare("INSERT INTO satisfactions (id_reclamation, email, rating, commentaire, date_evaluation) VALUES (?, ?, ?, ?, NOW())");
                    $testResult = $testQuery->execute([$id_reclamation, $testEmail, $rating, $finalCommentaire]);
                    
                    if ($testResult) {
                        $testId = $db->lastInsertId();
                        error_log("✅ Test d'insertion directe réussi - ID: $testId");
                        // Supprimer le test
                        $db->prepare("DELETE FROM satisfactions WHERE id_satisfaction = ?")->execute([$testId]);
                        error_log("❌ PROBLÈME: L'insertion directe fonctionne mais le contrôleur échoue!");
                    } else {
                        $errorInfo = $testQuery->errorInfo();
                        error_log("❌ Erreur lors du test d'insertion directe: " . print_r($errorInfo, true));
                    }
                } catch (PDOException $testE) {
                    error_log("❌ Exception lors du test d'insertion directe: " . $testE->getMessage());
                    error_log("❌ Code: " . $testE->getCode());
                    error_log("❌ ErrorInfo: " . print_r($testE->errorInfo(), true));
                }
                
                error_log("🔵 ========== FIN AJOUT ÉVALUATION (ÉCHEC) ==========");
                echo json_encode([
                    'success' => false, 
                    'message' => 'Erreur lors de l\'enregistrement. Vérifiez les logs du serveur (error.log) ou exécutez: http://localhost/foxunity/test_satisfaction_insert.php'
                ]);
            }
        } catch (Exception $controllerException) {
            error_log('❌ Exception dans addSatisfaction: ' . $controllerException->getMessage());
            error_log('❌ Stack trace: ' . $controllerException->getTraceAsString());
            
            $errorMsg = $controllerException->getMessage();
            $userMessage = 'Erreur lors de l\'enregistrement de l\'évaluation.';
            
            // Détecter le problème de Foreign Key sur email
            if (strpos($errorMsg, "foreign key constraint fails") !== false || 
                strpos($errorMsg, "1452") !== false ||
                strpos($errorMsg, "fk_satisfactions_user") !== false ||
                (strpos($errorMsg, "FOREIGN KEY") !== false && strpos($errorMsg, "email") !== false)) {
                $userMessage = '⚠️ Erreur de contrainte Foreign Key détectée. Cliquez ici pour corriger: <a href="http://localhost/foxunity/scripts/fix_satisfaction_foreign_key.php" target="_blank" style="color: #ff7a00; text-decoration: underline;">fix_satisfaction_foreign_key.php</a>';
            } elseif (strpos($errorMsg, "CONSTRAINT_ERROR") !== false) {
                $userMessage = 'Problème de contrainte UNIQUE. Exécutez: http://localhost/foxunity/fix_satisfactions_table.php';
            } elseif (strpos($errorMsg, "doesn't exist") !== false || strpos($errorMsg, "n'existe pas") !== false || strpos($errorMsg, "TABLE_NOT_FOUND") !== false) {
                $userMessage = 'La table satisfactions n\'existe pas. Exécutez: http://localhost/foxunity/create_satisfactions_table.php';
            } elseif (strpos($errorMsg, "Duplicate entry") !== false || strpos($errorMsg, "UNIQUE") !== false) {
                $userMessage = 'Vous avez déjà évalué cette réclamation avec cet email.';
            } elseif (strpos($errorMsg, "Integrity constraint violation") !== false) {
                $userMessage = 'Erreur de contrainte d\'intégrité. Exécutez: http://localhost/foxunity/fix_satisfaction_foreign_key.php pour corriger.';
            }
            
            echo json_encode([
                'success' => false, 
                'message' => $userMessage
            ]);
        }
    } catch (PDOException $e) {
        $errorMsg = $e->getMessage();
        $errorCode = $e->getCode();
        error_log('❌ Exception PDO dans addSatisfaction: ' . $errorMsg);
        error_log('❌ Code: ' . $errorCode);
        error_log('❌ ErrorInfo: ' . print_r($e->errorInfo(), true));
        
        $userMessage = 'Erreur lors de l\'enregistrement de l\'évaluation.';
        
        // Détecter le problème de Foreign Key (code 1452 ou 23000)
        if ($errorCode == 1452 || $errorCode == 23000 || 
            strpos($errorMsg, "foreign key constraint fails") !== false ||
            strpos($errorMsg, "fk_satisfactions_user") !== false ||
            (strpos($errorMsg, "FOREIGN KEY") !== false && strpos($errorMsg, "email") !== false) ||
            strpos($errorMsg, "Integrity constraint violation") !== false) {
            $userMessage = '⚠️ Erreur de contrainte Foreign Key détectée. Exécutez ce script pour corriger: http://localhost/foxunity/scripts/fix_satisfaction_foreign_key.php';
        } elseif (strpos($errorMsg, "Duplicate entry") !== false || 
            strpos($errorMsg, "duplicata") !== false ||
            strpos($errorMsg, "UNIQUE") !== false ||
            strpos($errorMsg, "unique_reclamation") !== false) {
            $userMessage = 'Erreur de contrainte UNIQUE. Veuillez exécuter: http://localhost/foxunity/fix_satisfactions_table.php pour corriger la base de données.';
        } elseif (strpos($errorMsg, "doesn't exist") !== false || 
                   strpos($errorMsg, "n'existe pas") !== false ||
                   strpos($errorMsg, "TABLE_NOT_FOUND") !== false) {
            $userMessage = 'La table satisfactions n\'existe pas. Veuillez exécuter: http://localhost/foxunity/create_satisfactions_table.php';
        } elseif (strpos($errorMsg, "CONSTRAINT_ERROR") !== false) {
            $userMessage = 'Problème de contrainte UNIQUE. Exécutez: http://localhost/foxunity/fix_satisfactions_table.php';
        }
        
        echo json_encode([
            'success' => false, 
            'message' => $userMessage
        ]);
    } catch (Exception $e) {
        $errorMsg = $e->getMessage();
        error_log('❌ Exception dans addSatisfaction: ' . $errorMsg);
        error_log('❌ Stack trace: ' . $e->getTraceAsString());
        
        $userMessage = 'Erreur lors de l\'enregistrement de l\'évaluation.';
        
        // Détecter le problème de Foreign Key
        if (strpos($errorMsg, "foreign key constraint fails") !== false || 
            strpos($errorMsg, "1452") !== false ||
            strpos($errorMsg, "fk_satisfactions_user") !== false ||
            strpos($errorMsg, "Integrity constraint violation") !== false) {
            $userMessage = '⚠️ Erreur de contrainte Foreign Key. Exécutez: http://localhost/foxunity/scripts/fix_satisfaction_foreign_key.php';
        } elseif (strpos($errorMsg, "CONSTRAINT_ERROR") !== false) {
            $userMessage = 'Problème de contrainte UNIQUE détecté. Veuillez exécuter: http://localhost/foxunity/fix_satisfactions_table.php';
        } elseif (strpos($errorMsg, "TABLE_NOT_FOUND") !== false) {
            $userMessage = 'La table satisfactions n\'existe pas. Veuillez exécuter: http://localhost/foxunity/create_satisfactions_table.php';
        } elseif (strpos($errorMsg, "Duplicate entry") !== false || strpos($errorMsg, "UNIQUE") !== false) {
            $userMessage = 'Vous avez déjà évalué cette réclamation avec cet email.';
        }
        
        echo json_encode([
            'success' => false, 
            'message' => $userMessage
        ]);
    }
    exit;
}

// Récupérer TOUTES les réclamations (pour affichage public - tous les utilisateurs peuvent voir et réagir)
$allReclamations = $reclamationController->getAllReclamations(null, null, null);

// Récupérer les réponses et toutes les évaluations pour chaque réclamation
// et calculer les statistiques globales / par catégorie pour l'affichage avancé
$globalStats = [
    'total_evaluations' => 0,
    'total_rating_sum' => 0,
    'ratings_count' => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0],
    'satisfied_count' => 0, // rating >= 4
];

$categoryStats = []; // [categorie => ['total_evaluations' => ..., 'rating_sum' => ..., 'satisfied_count' => ...]]

foreach ($allReclamations as &$reclamation) {
    $reclamation['responses'] = $responseController->getResponsesByReclamationId($reclamation['id_reclamation']);
    $reclamation['satisfactions'] = $satisfactionController->getSatisfactionsByReclamationId($reclamation['id_reclamation']);

    $ratingCount = 0;
    $ratingSum = 0;
    $hasComment = false;

    if (!empty($reclamation['satisfactions'])) {
        foreach ($reclamation['satisfactions'] as $sat) {
            $rating = (int) $sat->getRating();
            $ratingSum += $rating;
            $ratingCount++;

            // Stat globale
            $globalStats['total_evaluations']++;
            $globalStats['total_rating_sum'] += $rating;
            if (isset($globalStats['ratings_count'][$rating])) {
                $globalStats['ratings_count'][$rating]++;
            }
            if ($rating >= 4) {
                $globalStats['satisfied_count']++;
            }

            // Stat catégorie
            $catKey = !empty($reclamation['categorie']) ? strtolower($reclamation['categorie']) : 'autre';
            if (!isset($categoryStats[$catKey])) {
                $categoryStats[$catKey] = [
                    'label' => $reclamation['categorie'] ?? 'Autre',
                    'total_evaluations' => 0,
                    'rating_sum' => 0,
                    'satisfied_count' => 0,
                ];
            }
            $categoryStats[$catKey]['total_evaluations']++;
            $categoryStats[$catKey]['rating_sum'] += $rating;
            if ($rating >= 4) {
                $categoryStats[$catKey]['satisfied_count']++;
            }

            // Détecter s'il existe au moins un commentaire "réel"
            $comment = trim((string) $sat->getCommentaire());
            if ($comment !== '') {
                // Si le commentaire commence par "Évalué par: ", vérifier s'il y a une vraie partie commentaire après " | "
                if (strpos($comment, 'Évalué par:') === 0) {
                    $parts = explode(' | ', $comment);
                    if (isset($parts[1]) && trim($parts[1]) !== '') {
                        $hasComment = true;
                    }
                } else {
                    $hasComment = true;
                }
            }
        }
    }

    if ($ratingCount > 0) {
        $reclamation['average_rating'] = round($ratingSum / $ratingCount, 1);
        $reclamation['rating_count'] = $ratingCount;
    } else {
        $reclamation['average_rating'] = 0;
        $reclamation['rating_count'] = 0;
    }

    $reclamation['has_comment'] = $hasComment;
}
unset($reclamation);

// Calculs finaux pour les stats globales
$globalStats['average_rating'] = $globalStats['total_evaluations'] > 0
    ? round($globalStats['total_rating_sum'] / $globalStats['total_evaluations'], 1)
    : 0;

$globalStats['satisfied_percentage'] = $globalStats['total_evaluations'] > 0
    ? round(($globalStats['satisfied_count'] / $globalStats['total_evaluations']) * 100, 1)
    : 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réclamations Publiques - FoxUnity</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Orbitron:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        :root {
            --primary-color: #ff7a00;
            --primary-dark: #ff4f00;
            --bg-dark: #0a0a0a;
            --bg-card: rgba(20, 20, 20, 0.95);
            --text-light: #ffffff;
            --text-gray: #aaaaaa;
            --border-color: rgba(255, 255, 255, 0.1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: var(--bg-dark);
            color: var(--text-light);
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            margin-top: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
            padding: 30px;
            background: linear-gradient(135deg, var(--bg-card) 0%, rgba(10, 10, 10, 0.95) 100%);
            border: 2px solid var(--border-color);
            border-radius: 15px;
        }
        
        .header h1 {
            font-family: 'Orbitron', sans-serif;
            font-size: 36px;
            margin-bottom: 10px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .header p {
            color: var(--text-gray);
            font-size: 16px;
        }

        /* Cart dans le header */
        .cart-icon {
            color: #ff7a00 !important;
            position: relative;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .cart-icon:hover {
            color: #ff9933 !important;
            transform: translateY(-2px);
        }
        
        .cart-icon i {
            color: #ff7a00;
            font-size: 18px;
        }
        
        .cart-count {
            background: linear-gradient(135deg, #ff7a00, #ff4f00);
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 11px;
            font-weight: 700;
            position: absolute;
            top: -8px;
            right: -8px;
            min-width: 18px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(255, 122, 0, 0.4);
        }

        /* Bloc de statistiques globales */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stats-card {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 18px 20px;
        }

        .stats-card h4 {
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-gray);
            margin-bottom: 8px;
        }

        .stats-main-value {
            font-size: 26px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 4px;
        }

        .stats-subtext {
            font-size: 13px;
            color: var(--text-gray);
        }

        .rating-distribution {
            margin-top: 10px;
        }

        .rating-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 4px;
            font-size: 12px;
            color: var(--text-gray);
        }

        .rating-row i {
            color: #ffc107;
        }

        .progress-bar-container {
            flex: 1;
            background: rgba(255, 255, 255, 0.06);
            border-radius: 999px;
            overflow: hidden;
            height: 6px;
        }

        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #ffc107, #ff7a00);
            width: 0;
            transition: width 0.5s ease;
        }

        /* Filtres et tri */
        .filters-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
            justify-content: space-between;
            padding: 15px 20px;
            margin-bottom: 25px;
            background: rgba(255, 255, 255, 0.02);
            border-radius: 12px;
            border: 1px solid var(--border-color);
        }

        .filters-group {
            display: flex;
            flex-wrap: wrap;
            gap: 10px 15px;
            align-items: center;
        }

        .filters-bar label {
            font-size: 13px;
            color: var(--text-gray);
        }

        .filters-select,
        .filters-checkbox {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--border-color);
            color: var(--text-light);
            border-radius: 20px;
            padding: 6px 12px;
            font-size: 13px;
        }

        .filters-select {
            padding-right: 26px;
        }

        .filters-checkbox input {
            margin-right: 6px;
        }

        .filters-select:focus,
        .filters-checkbox input:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .reclamations-list {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }
        
        .reclamation-card {
            background: linear-gradient(135deg, var(--bg-card) 0%, rgba(10, 10, 10, 0.95) 100%);
            border: 2px solid var(--border-color);
            border-radius: 15px;
            padding: 30px;
            transition: all 0.3s ease;
        }
        
        .reclamation-card:hover {
            border-color: rgba(255, 122, 0, 0.3);
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(255, 122, 0, 0.2);
        }
        
        .reclamation-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .reclamation-title {
            font-size: 22px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        .reclamation-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            font-size: 14px;
            color: var(--text-gray);
        }
        
        .category-badge {
            background: rgba(255, 122, 0, 0.2);
            color: #ff7a00;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            border: 1px solid rgba(255, 122, 0, 0.3);
        }
        
        .reclamation-description {
            color: var(--text-light);
            line-height: 1.8;
            margin-bottom: 20px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 8px;
        }
        
        .responses-section {
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid var(--border-color);
        }
        
        .response-item {
            background: rgba(255, 122, 0, 0.05);
            border-left: 3px solid var(--primary-color);
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 8px;
        }
        
        .response-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .response-author {
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .response-date {
            color: var(--text-gray);
            font-size: 12px;
        }
        
        .response-text {
            color: var(--text-light);
            line-height: 1.6;
        }
        
        .rating-section {
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid var(--border-color);
        }
        
        .rating-form {
            background: rgba(255, 122, 0, 0.05);
            padding: 20px;
            border-radius: 10px;
            border: 1px solid rgba(255, 122, 0, 0.2);
        }
        
        .rating-form h4 {
            color: var(--primary-color);
            margin-bottom: 15px;
            font-size: 18px;
        }
        
        .star-rating {
            display: flex;
            gap: 5px;
            margin-bottom: 15px;
            direction: rtl;
        }
        
        .star-rating input[type="radio"] {
            display: none;
        }
        
        .star-rating label {
            font-size: 30px;
            color: #444;
            cursor: pointer;
            transition: color 0.2s;
            transform: translateY(0);
            transition: color 0.2s, transform 0.15s ease;
        }
        
        .star-rating label:hover,
        .star-rating label:hover ~ label,
        .star-rating input[type="radio"]:checked ~ label {
            color: #ffc107;
            transform: translateY(-2px) scale(1.05);
        }
        
        .star-rating input[type="radio"]:checked ~ label {
            color: #ffc107;
        }
        
        .rating-form textarea {
            width: 100%;
            padding: 12px;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-light);
            font-family: 'Poppins', sans-serif;
            resize: vertical;
            min-height: 100px;
            margin-bottom: 15px;
        }
        
        .rating-form textarea:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .rating-form input[type="email"],
        .rating-form input[type="text"] {
            width: 100%;
            padding: 12px;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-light);
            font-family: 'Poppins', sans-serif;
            margin-bottom: 15px;
        }
        
        .rating-form input[type="email"]:focus,
        .rating-form input[type="text"]:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .btn {
            padding: 12px 25px;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 122, 0, 0.3);
        }
        
        .existing-rating {
            background: rgba(76, 175, 80, 0.1);
            border: 1px solid rgba(76, 175, 80, 0.3);
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
        }
        
        .existing-rating h5 {
            color: #4caf50;
            margin-bottom: 10px;
        }
        
        .rating-stars-display {
            color: #ffc107;
            font-size: 20px;
            margin-bottom: 10px;
        }
        
        .rating-comment {
            color: var(--text-light);
            line-height: 1.6;
            font-style: italic;
        }
        
        .no-reclamations {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-gray);
        }
        
        .no-reclamations i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background: rgba(76, 175, 80, 0.2);
            border: 1px solid rgba(76, 175, 80, 0.4);
            color: #4caf50;
        }
        
        .alert-error {
            background: rgba(220, 53, 69, 0.2);
            border: 1px solid rgba(220, 53, 69, 0.4);
            color: #dc3545;
        }
        
        @media (max-width: 768px) {
            .reclamation-header {
                flex-direction: column;
            }
            
            .header h1 {
                font-size: 28px;
            }
        }
    </style>
</head>
<body>
    <!-- HEADER -->
    <header class="site-header">
        <div class="logo-section">
            <img src="../images/Nine__1_-removebg-preview.png" alt="FoxUnity Logo" class="site-logo">
            <span class="site-name">FoxUnity</span>
        </div>
        
        <nav class="site-nav">
            <a href="indexf.html">Home</a>
            <a href="events.html">Events</a>
            <a href="shop.html">Shop</a>
            <a href="trading.html">Trading</a>
            <a href="news.html">News</a>
            <a href="reclamation.php">Support</a>
            <a href="contact_us.php">New Request</a>
            <a href="public_reclamations.php" class="active"><i class="fas fa-star"></i> Public Evaluations</a>
            <a href="about.html">About Us</a>
        </nav>
        
        <div class="header-right">
            <a href="login.html" class="login-register-link">
                <i class="fas fa-user"></i> Login / Register
            </a>
            <a href="profile.html" class="profile-icon">
                <i class="fas fa-user-circle"></i>
            </a>
            <a href="panier.html" class="cart-icon">
                <i class="fas fa-shopping-cart"></i> Cart
                <span class="cart-count">0</span>
            </a>
        </div>
    </header>

    <div class="container">
        <div class="header">
            <h1><i class="fas fa-comments"></i> Réclamations Publiques</h1>
            <p>Consultez toutes les réclamations et partagez votre avis avec des étoiles</p>
        </div>

        <?php if ($globalStats['total_evaluations'] > 0): ?>
            <div class="stats-grid">
                <div class="stats-card">
                    <h4>Note moyenne globale</h4>
                    <div class="stats-main-value">
                        <?php echo $globalStats['average_rating']; ?>/5
                    </div>
                    <div class="stats-subtext">
                        Basé sur <?php echo $globalStats['total_evaluations']; ?> évaluation<?php echo $globalStats['total_evaluations'] > 1 ? 's' : ''; ?>
                    </div>
                </div>
                <div class="stats-card">
                    <h4>Clients satisfaits</h4>
                    <div class="stats-main-value">
                        <?php echo $globalStats['satisfied_percentage']; ?>%
                    </div>
                    <div class="stats-subtext">
                        Notes de 4★ et 5★
                    </div>
                </div>
                <div class="stats-card">
                    <h4>Répartition des notes</h4>
                    <div class="rating-distribution">
                        <?php
                        $maxCount = max($globalStats['ratings_count']) ?: 1;
                        for ($r = 5; $r >= 1; $r--):
                            $count = $globalStats['ratings_count'][$r];
                            $percentageRow = $globalStats['total_evaluations'] > 0
                                ? round(($count / $globalStats['total_evaluations']) * 100)
                                : 0;
                            $width = $maxCount > 0 ? ($count / $maxCount) * 100 : 0;
                        ?>
                        <div class="rating-row" data-rating-row="<?php echo $r; ?>">
                            <span><?php echo $r; ?> <i class="fas fa-star"></i></span>
                            <div class="progress-bar-container">
                                <div class="progress-bar" style="width: <?php echo $width; ?>%;"></div>
                            </div>
                            <span><?php echo $percentageRow; ?>%</span>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>
                <?php if (!empty($categoryStats)): ?>
                    <div class="stats-card">
                        <h4>Par catégorie</h4>
                        <div class="stats-subtext">
                            <?php foreach ($categoryStats as $catKey => $cat): 
                                $avgCat = $cat['total_evaluations'] > 0
                                    ? round($cat['rating_sum'] / $cat['total_evaluations'], 1)
                                    : 0;
                                $pctCat = $cat['total_evaluations'] > 0
                                    ? round(($cat['satisfied_count'] / $cat['total_evaluations']) * 100)
                                    : 0;
                            ?>
                                <div style="margin-bottom: 6px;">
                                    <strong><?php echo htmlspecialchars($cat['label']); ?></strong> :
                                    <?php echo $avgCat; ?>/5 • <?php echo $pctCat; ?>% satisfaits
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['rated']) && $_GET['rated'] == '1'): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span>Merci pour votre évaluation !</span>
            </div>
        <?php endif; ?>
        
        <?php if (empty($allReclamations)): ?>
            <div class="no-reclamations">
                <i class="fas fa-inbox"></i>
                <h2>Aucune réclamation pour le moment</h2>
                <p>Les réclamations apparaîtront ici une fois qu'elles auront été créées.</p>
            </div>
        <?php else: ?>
            <div class="filters-bar">
                <div class="filters-group">
                    <label for="sort-by">Trier par :</label>
                    <select id="sort-by" class="filters-select">
                        <option value="recent">Plus récentes</option>
                        <option value="best">Meilleure note</option>
                        <option value="worst">Pire note</option>
                    </select>
                </div>
                <div class="filters-group">
                    <label class="filters-checkbox">
                        <input type="checkbox" id="filter-4plus">
                        4★ et plus
                    </label>
                    <label class="filters-checkbox">
                        <input type="checkbox" id="filter-comment-only">
                        Avec commentaire
                    </label>
                    <label for="filter-category">Catégorie :</label>
                    <select id="filter-category" class="filters-select">
                        <option value="all">Toutes</option>
                        <?php foreach ($categoryStats as $catKey => $cat): ?>
                            <option value="<?php echo htmlspecialchars($catKey); ?>">
                                <?php echo htmlspecialchars($cat['label']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="reclamations-list">
                <?php foreach ($allReclamations as $reclamation): ?>
                    <?php
                        $catKey = !empty($reclamation['categorie']) ? strtolower($reclamation['categorie']) : 'autre';
                    ?>
                    <div 
                        class="reclamation-card" 
                        id="reclamation-<?php echo $reclamation['id_reclamation']; ?>"
                        data-average-rating="<?php echo $reclamation['average_rating']; ?>"
                        data-rating-count="<?php echo $reclamation['rating_count']; ?>"
                        data-date="<?php echo htmlspecialchars($reclamation['date_creation']); ?>"
                        data-category="<?php echo htmlspecialchars($catKey); ?>"
                        data-has-comment="<?php echo !empty($reclamation['has_comment']) ? '1' : '0'; ?>"
                    >
                        <div class="reclamation-header">
                            <div>
                                <h3 class="reclamation-title"><?php echo htmlspecialchars($reclamation['sujet'] ?? ''); ?></h3>
                                <div class="reclamation-meta">
                                    <span><i class="far fa-calendar"></i> <?php echo date('d/m/Y H:i', strtotime($reclamation['date_creation'])); ?></span>
                                    <?php if (!empty($reclamation['categorie']) && strtolower($reclamation['categorie']) !== 'other'): ?>
                                        <span class="category-badge">
                                            <i class="fas fa-tag"></i> <?php echo htmlspecialchars($reclamation['categorie']); ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php 
                                    $statut = $reclamation['statut'] ?? 'nouveau';
                                    $statutColors = [
                                        'nouveau' => ['bg' => 'rgba(255, 122, 0, 0.2)', 'color' => '#ff7a00', 'text' => 'Nouveau'],
                                        'en_cours' => ['bg' => 'rgba(255, 193, 7, 0.2)', 'color' => '#ffc107', 'text' => 'En cours'],
                                        'resolu' => ['bg' => 'rgba(76, 175, 80, 0.2)', 'color' => '#4caf50', 'text' => 'Résolu']
                                    ];
                                    $statutInfo = $statutColors[$statut] ?? $statutColors['nouveau'];
                                    ?>
                                    <span class="category-badge" style="background: <?php echo $statutInfo['bg']; ?>; color: <?php echo $statutInfo['color']; ?>; border-color: <?php echo $statutInfo['color']; ?>;">
                                        <i class="fas fa-circle" style="font-size: 8px;"></i> <?php echo $statutInfo['text']; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="reclamation-description">
                            <?php echo nl2br(htmlspecialchars($reclamation['description'] ?? '')); ?>
                        </div>
                        
                        <?php if (!empty($reclamation['responses'])): ?>
                            <div class="responses-section">
                                <h4 style="color: var(--primary-color); margin-bottom: 15px;">
                                    <i class="fas fa-reply"></i> Réponse de l'équipe (<?php echo count($reclamation['responses']); ?>)
                                </h4>
                                <?php foreach ($reclamation['responses'] as $response): ?>
                                    <div class="response-item">
                                        <div class="response-header">
                                            <span class="response-author">
                                                <i class="fas fa-user-shield"></i> Admin
                                            </span>
                                            <span class="response-date">
                                                <?php echo date('d/m/Y H:i', strtotime($response['date_reponse'] ?? $response['date_creation'] ?? 'now')); ?>
                                            </span>
                                        </div>
                                        <div class="response-text">
                                            <?php echo nl2br(htmlspecialchars($response['message'] ?? $response['response_text'] ?? '')); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="rating-section">
                            <!-- Afficher la moyenne et le nombre d'évaluations -->
                            <?php if ($reclamation['rating_count'] > 0): ?>
                                <div style="margin-bottom: 20px; padding: 15px; background: rgba(255, 193, 7, 0.1); border-radius: 8px; border-left: 4px solid #ffc107;">
                                    <h5 style="color: #ffc107; margin-bottom: 10px;">
                                        <i class="fas fa-star"></i> Évaluations (<?php echo $reclamation['rating_count']; ?>)
                                    </h5>
                                    <div class="rating-stars-display" style="font-size: 24px;">
                                        <?php 
                                        $avgRating = $reclamation['average_rating'];
                                        for ($i = 1; $i <= 5; $i++): 
                                        ?>
                                            <i class="fas fa-star" style="color: <?php echo $i <= round($avgRating) ? '#ffc107' : '#444'; ?>;"></i>
                                        <?php endfor; ?>
                                        <span style="margin-left: 10px; color: var(--text-light); font-size: 18px; font-weight: 700;">
                                            <?php echo $avgRating; ?>/5 (<?php echo $reclamation['rating_count']; ?> évaluation<?php echo $reclamation['rating_count'] > 1 ? 's' : ''; ?>)
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- Afficher toutes les évaluations -->
                                <div style="margin-bottom: 20px;">
                                    <h5 style="color: var(--primary-color); margin-bottom: 15px;">
                                        <i class="fas fa-users"></i> Toutes les évaluations
                                    </h5>
                                    <?php foreach ($reclamation['satisfactions'] as $satisfaction): 
                                        // Déterminer le nom à afficher
                                        $displayName = 'Utilisateur anonyme';
                                        $email = $satisfaction->getEmail();
                                        $commentaire = $satisfaction->getCommentaire();
                                        
                                        // Si l'email commence par "anonymous_" ou "user_", c'est anonyme
                                        if (strpos($email, 'anonymous_') === 0 || strpos($email, 'user_') === 0) {
                                            // Essayer d'extraire le nom du commentaire
                                            if ($commentaire && strpos($commentaire, 'Évalué par:') === 0) {
                                                $parts = explode(' | ', $commentaire);
                                                $displayName = str_replace('Évalué par: ', '', $parts[0]);
                                            } else {
                                                $displayName = 'Utilisateur anonyme';
                                            }
                                        } else {
                                            // Email réel - extraire le nom du commentaire ou utiliser l'email
                                            if ($commentaire && strpos($commentaire, 'Évalué par:') === 0) {
                                                $parts = explode(' | ', $commentaire);
                                                $displayName = str_replace('Évalué par: ', '', $parts[0]);
                                            } else {
                                                // Utiliser l'email mais masquer une partie
                                                $emailParts = explode('@', $email);
                                                $displayName = substr($emailParts[0], 0, 3) . '***@' . (isset($emailParts[1]) ? $emailParts[1] : '');
                                            }
                                        }
                                    ?>
                                        <div class="existing-rating" style="margin-bottom: 15px;">
                                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                                <div>
                                                    <strong style="color: var(--text-light);"><?php echo htmlspecialchars($displayName); ?></strong>
                                                    <span style="color: var(--text-gray); font-size: 12px; margin-left: 10px;">
                                                        <?php echo date('d/m/Y H:i', strtotime($satisfaction->getDateEvaluation())); ?>
                                                    </span>
                                                </div>
                                                <div class="rating-stars-display" style="font-size: 16px;">
                                                    <?php 
                                                    $rating = $satisfaction->getRating();
                                                    for ($i = 1; $i <= 5; $i++): 
                                                    ?>
                                                        <i class="fas fa-star" style="color: <?php echo $i <= $rating ? '#ffc107' : '#444'; ?>;"></i>
                                                    <?php endfor; ?>
                                                    <span style="margin-left: 5px; color: var(--text-light);">(<?php echo $rating; ?>/5)</span>
                                                </div>
                                            </div>
                                            <?php 
                                            // Afficher le commentaire seulement s'il n'est pas juste le nom
                                            $commentaireToShow = $satisfaction->getCommentaire();
                                            if ($commentaireToShow && strpos($commentaireToShow, 'Évalué par:') === 0) {
                                                // Si le commentaire contient "Évalué par:", extraire la partie après " | "
                                                $parts = explode(' | ', $commentaireToShow);
                                                if (isset($parts[1]) && !empty(trim($parts[1]))) {
                                                    $commentaireToShow = trim($parts[1]);
                                                } else {
                                                    $commentaireToShow = null; // Pas de commentaire réel, juste le nom
                                                }
                                            }
                                            if ($commentaireToShow): 
                                            ?>
                                                <div class="rating-comment">
                                                    "<?php echo htmlspecialchars($commentaireToShow); ?>"
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Formulaire d'évaluation (toujours visible pour permettre à tous d'évaluer) -->
                            <div class="rating-form" id="rating-form-<?php echo $reclamation['id_reclamation']; ?>">
                                <h4><i class="fas fa-star"></i> <?php echo $reclamation['rating_count'] > 0 ? 'Ajoutez votre évaluation' : 'Évaluez cette réclamation'; ?></h4>
                                <form class="rating-form-inner" data-reclamation-id="<?php echo $reclamation['id_reclamation']; ?>">
                                    <div class="star-rating">
                                        <input type="radio" id="star5-<?php echo $reclamation['id_reclamation']; ?>" name="rating-<?php echo $reclamation['id_reclamation']; ?>" value="5">
                                        <label for="star5-<?php echo $reclamation['id_reclamation']; ?>"><i class="fas fa-star"></i></label>
                                        
                                        <input type="radio" id="star4-<?php echo $reclamation['id_reclamation']; ?>" name="rating-<?php echo $reclamation['id_reclamation']; ?>" value="4">
                                        <label for="star4-<?php echo $reclamation['id_reclamation']; ?>"><i class="fas fa-star"></i></label>
                                        
                                        <input type="radio" id="star3-<?php echo $reclamation['id_reclamation']; ?>" name="rating-<?php echo $reclamation['id_reclamation']; ?>" value="3">
                                        <label for="star3-<?php echo $reclamation['id_reclamation']; ?>"><i class="fas fa-star"></i></label>
                                        
                                        <input type="radio" id="star2-<?php echo $reclamation['id_reclamation']; ?>" name="rating-<?php echo $reclamation['id_reclamation']; ?>" value="2">
                                        <label for="star2-<?php echo $reclamation['id_reclamation']; ?>"><i class="fas fa-star"></i></label>
                                        
                                        <input type="radio" id="star1-<?php echo $reclamation['id_reclamation']; ?>" name="rating-<?php echo $reclamation['id_reclamation']; ?>" value="1">
                                        <label for="star1-<?php echo $reclamation['id_reclamation']; ?>"><i class="fas fa-star"></i></label>
                                    </div>
                                    
                                    <input type="text" name="name-<?php echo $reclamation['id_reclamation']; ?>" 
                                           placeholder="Votre nom (optionnel)">
                                    
                                    <input type="email" name="email-<?php echo $reclamation['id_reclamation']; ?>" 
                                           placeholder="Votre email (optionnel)">
                                    
                                    <textarea name="commentaire-<?php echo $reclamation['id_reclamation']; ?>" 
                                              placeholder="Votre commentaire (optionnel)"></textarea>
                                    
                                    <button type="submit" class="btn">
                                        <i class="fas fa-paper-plane"></i> Envoyer l'évaluation
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Gestion des formulaires d'évaluation
        document.querySelectorAll('.rating-form-inner').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const reclamationId = this.getAttribute('data-reclamation-id');
                const ratingInput = this.querySelector('input[type="radio"]:checked');
                const nameInput = this.querySelector('input[type="text"]');
                const emailInput = this.querySelector('input[type="email"]');
                const commentaireInput = this.querySelector('textarea');
                
                if (!ratingInput) {
                    alert('Veuillez sélectionner une note');
                    return;
                }
                
                // Email et nom sont maintenant optionnels
                const formData = new FormData();
                formData.append('action', 'rate_reclamation');
                formData.append('id_reclamation', reclamationId);
                formData.append('name', nameInput ? nameInput.value.trim() : '');
                formData.append('email', emailInput ? emailInput.value.trim() : '');
                formData.append('rating', ratingInput.value);
                formData.append('commentaire', commentaireInput ? commentaireInput.value.trim() : '');
                
                // Désactiver le bouton pendant l'envoi
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Envoi...';
                
                fetch('public_reclamations.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Erreur HTTP: ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Afficher un message de succès
                        const formContainer = form.closest('.rating-form');
                        const successMsg = document.createElement('div');
                        successMsg.className = 'alert alert-success';
                        successMsg.style.marginTop = '15px';
                        successMsg.innerHTML = '<i class="fas fa-check-circle"></i> ' + (data.message || 'Merci pour votre évaluation !');
                        formContainer.insertBefore(successMsg, form);
                        
                        // Réinitialiser le formulaire
                        form.reset();
                        form.querySelectorAll('input[type="radio"]').forEach(radio => {
                            radio.checked = false;
                        });
                        form.querySelectorAll('label').forEach(label => {
                            label.style.color = '#444';
                        });
                        
                        // Recharger la page après 2 secondes pour afficher la nouvelle évaluation
                        setTimeout(() => {
                            window.location.href = 'public_reclamations.php?rated=1#reclamation-' + reclamationId;
                        }, 2000);
                    } else {
                        console.error('Erreur serveur:', data);
                        
                        // Afficher l'erreur dans le formulaire au lieu d'une alerte
                        const formContainer = form.closest('.rating-form');
                        let errorMsg = formContainer.querySelector('.alert-error');
                        if (!errorMsg) {
                            errorMsg = document.createElement('div');
                            errorMsg.className = 'alert alert-error';
                            formContainer.insertBefore(errorMsg, form);
                        }
                        errorMsg.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + (data.message || 'Erreur lors de l\'envoi de l\'évaluation.');
                        errorMsg.style.display = 'block';
                        
                        // Faire défiler jusqu'à l'erreur
                        errorMsg.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                        
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalText;
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Une erreur est survenue lors de l\'envoi. Veuillez vérifier votre connexion et réessayer.');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                });
            });
        });

        // Tri et filtres sur les réclamations
        (function() {
            const list = document.querySelector('.reclamations-list');
            if (!list) return;

            const cards = Array.from(list.querySelectorAll('.reclamation-card'));
            const sortSelect = document.getElementById('sort-by');
            const filter4Plus = document.getElementById('filter-4plus');
            const filterCommentOnly = document.getElementById('filter-comment-only');
            const filterCategory = document.getElementById('filter-category');

            function applyFiltersAndSort() {
                const sortBy = sortSelect ? sortSelect.value : 'recent';
                const need4Plus = filter4Plus && filter4Plus.checked;
                const needComment = filterCommentOnly && filterCommentOnly.checked;
                const category = filterCategory ? filterCategory.value : 'all';

                // Filtrer
                cards.forEach(card => {
                    const avg = parseFloat(card.getAttribute('data-average-rating')) || 0;
                    const count = parseInt(card.getAttribute('data-rating-count') || '0', 10);
                    const hasComment = card.getAttribute('data-has-comment') === '1';
                    const catKey = card.getAttribute('data-category') || 'autre';

                    let visible = true;

                    if (need4Plus && !(count > 0 && avg >= 4)) {
                        visible = false;
                    }
                    if (needComment && !hasComment) {
                        visible = false;
                    }
                    if (category !== 'all' && catKey !== category) {
                        visible = false;
                    }

                    card.style.display = visible ? '' : 'none';
                });

                // Trier uniquement les cartes visibles
                const visibleCards = cards.filter(c => c.style.display !== 'none');

                visibleCards.sort((a, b) => {
                    const dateA = new Date(a.getAttribute('data-date') || 0).getTime();
                    const dateB = new Date(b.getAttribute('data-date') || 0).getTime();
                    const avgA = parseFloat(a.getAttribute('data-average-rating')) || 0;
                    const avgB = parseFloat(b.getAttribute('data-average-rating')) || 0;

                    if (sortBy === 'best') {
                        // Meilleure note d'abord, puis plus récentes
                        if (avgB !== avgA) return avgB - avgA;
                        return dateB - dateA;
                    } else if (sortBy === 'worst') {
                        // Pire note d'abord, puis plus récentes
                        if (avgA !== avgB) return avgA - avgB;
                        return dateB - dateA;
                    } else {
                        // Par défaut : plus récentes
                        return dateB - dateA;
                    }
                });

                // Réordonner dans le DOM
                visibleCards.forEach(card => list.appendChild(card));
            }

            if (sortSelect) sortSelect.addEventListener('change', applyFiltersAndSort);
            if (filter4Plus) filter4Plus.addEventListener('change', applyFiltersAndSort);
            if (filterCommentOnly) filterCommentOnly.addEventListener('change', applyFiltersAndSort);
            if (filterCategory) filterCategory.addEventListener('change', applyFiltersAndSort);

            // Application initiale
            applyFiltersAndSort();
        })();
    </script>

    <footer class="site-footer">
        <div class="footer-content">
            <div class="footer-section">
                <h4>FoxUnity</h4>
                <p>Gaming for Good - Every action makes a difference</p>
            </div>
            <div class="footer-section">
                <h4>Back to Top</h4>
                <a href="#" class="back-to-top-link" onclick="window.scrollTo({top: 0, behavior: 'smooth'}); return false;">
                    <i class="fas fa-arrow-up"></i> Scroll to Top
                </a>
            </div>
            <div class="footer-section">
                <h4>Support</h4>
                <a href="reclamation.php">Contact Support</a>
                <a href="#">FAQ</a>
                <a href="#">Privacy Policy</a>
            </div>
            <div class="footer-section">
                <h4>Follow Us</h4>
                <div class="social-links">
                    <a href="#"><i class="fab fa-discord"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
            <div class="footer-section">
                <h4>Dashboard</h4>
                <a href="../back/dashboard.php" class="dashboard-link">
                    <i class="fas fa-tachometer-alt"></i> My Dashboard
                </a>
                <a href="../back/reclamback.php" class="dashboard-link" style="margin-top: 10px; display: block;">
                    <i class="fas fa-headset"></i> Dashboard Support
                </a>
            </div>
        </div>
        <div class="footer-bottom">
            <p>© 2025 FoxUnity. All rights reserved. Made with <span>♥</span> by gamers for gamers</p>
        </div>
    </footer>
</body>
</html>

