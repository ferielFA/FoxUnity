<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Test PHP</h1>";

// Test 1: PHP fonctionne
echo "PHP fonctionne!<br>";

// Test 2: Connexion base de données
try {
    require_once __DIR__ . '/config.php';
    $pdo = Config::getConnexion();
    echo "Connexion BD foxunity0 réussie!<br>";
    
    // Vérifier si les tables existent
    $tables = ['reclamations', 'reponses'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "Table '$table' existe<br>";
        } else {
            echo "Table '$table' N'EXISTE PAS<br>";
        }
    }
} catch (Exception $e) {
    echo "Erreur BD: " . $e->getMessage() . "<br>";
}

// Test 3: Fichiers existent
$files = [
    'config/config.php',
    'models/Reclamation.php', 
    'controllers/reclamationcontroller.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "$file existe<br>";
    } else {
        echo "$file MANQUANT<br>";
    }
}
?>