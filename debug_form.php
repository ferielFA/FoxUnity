<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/models/Reclamation.php';
require_once __DIR__ . '/controllers/reclamationcontroller.php';

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Debug Formulaire - FoxUnity</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
            background: #1e1e1e;
            color: #fff;
        }
        .section {
            background: rgba(255,255,255,0.05);
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            border-left: 4px solid #ff7a00;
        }
        .success { border-left-color: #4caf50; }
        .error { border-left-color: #f44336; }
        .warning { border-left-color: #ff9800; }
        h2 { color: #ff7a00; margin-top: 0; }
        pre {
            background: rgba(0,0,0,0.3);
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
        }
        .test-form {
            background: rgba(255,255,255,0.05);
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .test-form input, .test-form textarea, .test-form select {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid rgba(255,255,255,0.2);
            background: rgba(255,255,255,0.1);
            color: #fff;
            border-radius: 4px;
        }
        .test-form button {
            background: #ff7a00;
            color: #fff;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
        .test-form button:hover {
            background: #ff4f00;
        }
    </style>
</head>
<body>
    <h1>🔍 Debug du Formulaire de Contact</h1>

    <?php
    // Test 1: Connexion à la base de données
    echo '<div class="section">';
    echo '<h2>Test 1: Connexion à la base de données</h2>';
    try {
        $db = Config::getConnexion();
        if ($db) {
            echo '<p class="success">✓ Connexion réussie à la base de données foxunity0</p>';
        } else {
            echo '<p class="error">✗ Échec de la connexion</p>';
        }
    } catch (Exception $e) {
        echo '<p class="error">✗ Erreur: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
    echo '</div>';

    // Test 2: Structure de la table
    echo '<div class="section">';
    echo '<h2>Test 2: Structure de la table reclamations</h2>';
    try {
        $db = Config::getConnexion();
        $stmt = $db->query("DESCRIBE reclamations");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo '<pre>';
        print_r($columns);
        echo '</pre>';
    } catch (Exception $e) {
        echo '<p class="error">✗ Erreur: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
    echo '</div>';

    // Test 3: Test d'insertion
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_insert'])) {
        echo '<div class="section">';
        echo '<h2>Test 3: Test d\'insertion</h2>';
        
        try {
            $testEmail = $_POST['email'] ?? 'test@example.com';
            $testSujet = $_POST['subject'] ?? 'Test Sujet';
            $testDescription = $_POST['message'] ?? 'Test Description';
            
            echo '<p><strong>Données à insérer:</strong></p>';
            echo '<pre>';
            echo "Email: $testEmail\n";
            echo "Sujet: $testSujet\n";
            echo "Description: $testDescription\n";
            echo '</pre>';
            
            // Créer l'objet Reclamation
            $reclamation = new Reclamation(
                $testEmail,
                $testSujet,
                $testDescription,
                null,
                null
            );
            
            echo '<p><strong>Objet Reclamation créé:</strong></p>';
            echo '<pre>';
            echo "Email: " . $reclamation->getEmail() . "\n";
            echo "Sujet: " . $reclamation->getSujet() . "\n";
            echo "Description: " . $reclamation->getDescription() . "\n";
            echo "ID Utilisateur: " . ($reclamation->getIdUtilisateur() ?? 'NULL') . "\n";
            echo "Statut: " . ($reclamation->getStatut() ?? 'NULL') . "\n";
            echo "Date: " . $reclamation->getDateCreation() . "\n";
            echo '</pre>';
            
            // Tester l'insertion
            $controller = new ReclamationController();
            $result = $controller->addReclamation($reclamation);
            
            if ($result && $result > 0) {
                echo '<p class="success">✓ Insertion réussie! ID: ' . $result . '</p>';
                
                // Vérifier que l'enregistrement existe
                $db = Config::getConnexion();
                $check = $db->prepare("SELECT * FROM reclamations WHERE id_reclamation = ?");
                $check->execute([$result]);
                $record = $check->fetch(PDO::FETCH_ASSOC);
                
                if ($record) {
                    echo '<p class="success">✓ Enregistrement vérifié dans la base de données:</p>';
                    echo '<pre>';
                    print_r($record);
                    echo '</pre>';
                } else {
                    echo '<p class="warning">⚠ L\'ID a été retourné mais l\'enregistrement n\'a pas été trouvé</p>';
                }
            } else {
                echo '<p class="error">✗ Échec de l\'insertion. Résultat: ' . var_export($result, true) . '</p>';
                
                // Afficher les erreurs PDO
                $db = Config::getConnexion();
                $errorInfo = $db->errorInfo();
                if ($errorInfo[0] !== '00000') {
                    echo '<p class="error">Erreur PDO: ' . implode(', ', $errorInfo) . '</p>';
                }
            }
        } catch (PDOException $e) {
            echo '<p class="error">✗ Erreur PDO: ' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '<p class="error">Code: ' . $e->getCode() . '</p>';
        } catch (Exception $e) {
            echo '<p class="error">✗ Erreur: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
        echo '</div>';
    }

    // Formulaire de test
    echo '<div class="section test-form">';
    echo '<h2>Test d\'insertion manuel</h2>';
    echo '<form method="POST">';
    echo '<input type="hidden" name="test_insert" value="1">';
    echo '<label>Email:</label>';
    echo '<input type="email" name="email" value="test@example.com" required>';
    echo '<label>Sujet:</label>';
    echo '<input type="text" name="subject" value="Test Sujet" required>';
    echo '<label>Description:</label>';
    echo '<textarea name="message" required>Test Description</textarea>';
    echo '<button type="submit">Tester l\'insertion</button>';
    echo '</form>';
    echo '</div>';

    // Test 4: Vérifier les réclamations existantes
    echo '<div class="section">';
    echo '<h2>Test 4: Réclamations existantes</h2>';
    try {
        $db = Config::getConnexion();
        $stmt = $db->query("SELECT COUNT(*) as total FROM reclamations");
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        echo '<p>Nombre total de réclamations: <strong>' . $count . '</strong></p>';
        
        if ($count > 0) {
            $stmt = $db->query("SELECT * FROM reclamations ORDER BY date_creation DESC LIMIT 5");
            $recent = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo '<p>5 dernières réclamations:</p>';
            echo '<pre>';
            print_r($recent);
            echo '</pre>';
        }
    } catch (Exception $e) {
        echo '<p class="error">✗ Erreur: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
    echo '</div>';
    ?>

    <div class="section">
        <h2>🔗 Liens utiles</h2>
        <ul>
            <li><a href="view/front/contact_us.php" style="color: #ff7a00;">Formulaire de contact</a></li>
            <li><a href="view_reclamations.php" style="color: #ff7a00;">Voir toutes les réclamations</a></li>
            <li><a href="test_insert.php" style="color: #ff7a00;">Test d'insertion automatique</a></li>
        </ul>
    </div>
</body>
</html>









