-- Script pour recréer la table satisfactions avec la bonne contrainte UNIQUE
-- ATTENTION: Ce script supprime toutes les données existantes !

-- Supprimer la table si elle existe (ATTENTION: supprime toutes les données)
DROP TABLE IF EXISTS satisfactions;

-- Recréer la table avec la bonne contrainte
-- Permet plusieurs évaluations par réclamation (une par email)
CREATE TABLE satisfactions (
    id_satisfaction INT AUTO_INCREMENT PRIMARY KEY,
    id_reclamation INT NOT NULL,
    email VARCHAR(255) NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    commentaire TEXT,
    date_evaluation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_reclamation) REFERENCES reclamations(id_reclamation) ON DELETE CASCADE,
    UNIQUE KEY unique_reclamation_email (id_reclamation, email),
    INDEX idx_email (email),
    INDEX idx_rating (rating),
    INDEX idx_date (date_evaluation)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;




