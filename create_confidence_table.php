<?php
require_once 'config/config.php';

try {
    $db = Config::getConnexion();
    
    $sql = "CREATE TABLE IF NOT EXISTS user_confidence_scores (
        id_score INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) UNIQUE NOT NULL,
        nombre_avis INT DEFAULT 0,
        likes_recus INT DEFAULT 0,
        taux_transparence DECIMAL(5,2) DEFAULT 0,
        score_total DECIMAL(5,2) DEFAULT 0,
        date_mise_a_jour DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_email (email),
        INDEX idx_score (score_total)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $db->exec($sql);
    echo "Table 'user_confidence_scores' created successfully!\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>









