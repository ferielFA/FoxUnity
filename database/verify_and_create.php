<?php
// Script de vérification et création des tables
require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getConnection();
    
    echo "=== VÉRIFICATION DE LA BASE DE DONNÉES ===\n\n";
    
    // Vérifier les tables existantes
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Tables existantes (" . count($tables) . ") :\n";
    foreach ($tables as $table) {
        echo "  - $table\n";
    }
    
    echo "\n=== CRÉATION DES TABLES MANQUANTES ===\n\n";
    
    // Lire et exécuter le script SQL
    $sqlFile = __DIR__ . '/create_foxunity_db_complete.sql';
    if (!file_exists($sqlFile)) {
        die("Erreur: Fichier SQL introuvable: $sqlFile\n");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Supprimer les commandes DROP DATABASE et CREATE DATABASE
    $sql = preg_replace('/DROP DATABASE IF EXISTS.*?;/i', '', $sql);
    $sql = preg_replace('/CREATE DATABASE.*?;/i', '', $sql);
    $sql = preg_replace('/USE `foxunity_db`;/i', '', $sql);
    
    // Séparer les commandes
    $sql = str_replace('DELIMITER $$', '', $sql);
    $sql = str_replace('DELIMITER ;', '', $sql);
    
    // Exécuter le script
    try {
        $db->exec($sql);
        echo "✅ Script SQL exécuté avec succès!\n\n";
    } catch (PDOException $e) {
        echo "⚠️ Erreur lors de l'exécution: " . $e->getMessage() . "\n\n";
    }
    
    // Vérifier à nouveau
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "=== TABLES APRÈS CRÉATION ===\n";
    echo "Nombre total: " . count($tables) . "\n\n";
    foreach ($tables as $table) {
        // Compter les lignes
        $count = $db->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
        echo "  ✓ $table ($count lignes)\n";
    }
    
    echo "\n✅ Base de données foxunity_db prête!\n";
    
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
}
?>
