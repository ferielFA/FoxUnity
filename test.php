<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Test PHP</h1>";

// Test 1: PHP fonctionne
echo "PHP fonctionne!<br>";

// Test 2: Connexion base de données
try {
    $pdo = new PDO('mysql:host=localhost;dbname=foxunity', 'root', '');
    echo "Connexion BD réussie!<br>";
} catch (Exception $e) {
    echo "Erreur BD: " . $e->getMessage() . "<br>";
}

// Test 3: Fichiers existent
$files = [
    'config/config.php',
    'models/Reclamation.php', 
    'controllers/ReclamationController.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "$file existe<br>";
    } else {
        echo "$file MANQUANT<br>";
    }
}
?>