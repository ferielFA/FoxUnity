<?php
// Test simple de la base de données
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Test de connexion à la base de données...<br>";

try {
    require_once __DIR__ . '/config/database.php';
    
    $db = Database::getInstance();
    echo "✅ Connexion réussie!<br>";
    
    // Test simple
    $query = $db->query("SELECT COUNT(*) as count FROM users");
    $result = $query->fetch(PDO::FETCH_ASSOC);
    echo "✅ Nombre d'utilisateurs: " . $result['count'] . "<br>";
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "<br>";
    echo "Stack trace: " . $e->getTraceAsString();
}
