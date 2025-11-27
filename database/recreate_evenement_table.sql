-- Recreate evenement table with all fields including createur_email
-- Run this in phpMyAdmin or MySQL client

USE foxunity_db;

-- Disable foreign key checks
SET FOREIGN_KEY_CHECKS = 0;

-- Drop corrupted table
DROP TABLE IF EXISTS evenement;

-- Recreate table with all fields
CREATE TABLE evenement (
    id_evenement INT AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    date_debut DATETIME NOT NULL,
    date_fin DATETIME NOT NULL,
    lieu VARCHAR(255) NOT NULL,
    createur_email VARCHAR(255) DEFAULT NULL,
    statut ENUM('upcoming', 'ongoing', 'completed', 'cancelled') DEFAULT 'upcoming',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_statut (statut),
    INDEX idx_date_debut (date_debut),
    INDEX idx_createur_email (createur_email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample events for testing
INSERT INTO evenement (titre, description, date_debut, date_fin, lieu, createur_email, statut) VALUES
('Fortnite Championship 2025', 'Join us for the biggest Fortnite tournament of the year with amazing prizes!', '2025-12-15 14:00:00', '2025-12-15 18:00:00', 'Paris Gaming Arena', 'admin@foxunity.com', 'upcoming'),
('Valorant Team Battle', 'Competitive 5v5 Valorant tournament for teams', '2025-12-20 16:00:00', '2025-12-20 20:00:00', 'Online', 'expert1@foxunity.com', 'upcoming'),
('Charity Gaming Marathon', 'Gaming for Good - 24h gaming marathon to support charity', '2026-01-10 10:00:00', '2026-01-11 10:00:00', 'FoxUnity HQ', 'admin@foxunity.com', 'upcoming');

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;
