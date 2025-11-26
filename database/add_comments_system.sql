-- ============================================
-- FoxUnity - Système de Commentaires & Évaluations
-- ============================================
-- Date: 2025-11-26
-- Description: Tables pour gérer les commentaires, notes et interactions
-- Note: Utilise des champs temporaires (user_name, user_email) 
--       en attendant l'intégration avec la table user
-- ============================================

USE foxunity_db;

-- Table des commentaires avec notation
CREATE TABLE IF NOT EXISTS comment (
    id_comment INT PRIMARY KEY AUTO_INCREMENT,
    id_evenement INT NOT NULL,
    
    -- Champs temporaires pour les utilisateurs (avant intégration table user)
    user_name VARCHAR(100) NOT NULL,
    user_email VARCHAR(150) NOT NULL,
    
    -- Contenu du commentaire
    content TEXT NOT NULL,
    
    -- Note donnée (1 à 5 étoiles)
    rating TINYINT(1) NOT NULL CHECK(rating BETWEEN 1 AND 5),
    
    -- Interactions sur le commentaire
    likes INT DEFAULT 0,
    dislikes INT DEFAULT 0,
    
    -- Modération
    is_reported BOOLEAN DEFAULT FALSE,
    report_reason VARCHAR(255) DEFAULT NULL,
    
    -- Dates
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Clé étrangère vers événement
    FOREIGN KEY (id_evenement) REFERENCES evenement(id_evenement) ON DELETE CASCADE,
    
    -- Index pour améliorer les performances
    INDEX idx_evenement (id_evenement),
    INDEX idx_created_at (created_at),
    INDEX idx_rating (rating),
    INDEX idx_reported (is_reported)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table pour stocker les interactions utilisateurs (likes/dislikes)
-- Évite qu'un utilisateur like plusieurs fois le même commentaire
CREATE TABLE IF NOT EXISTS comment_interaction (
    id_interaction INT PRIMARY KEY AUTO_INCREMENT,
    id_comment INT NOT NULL,
    
    -- Identification temporaire de l'utilisateur
    user_email VARCHAR(150) NOT NULL,
    
    -- Type d'interaction: 'like' ou 'dislike'
    interaction_type ENUM('like', 'dislike') NOT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (id_comment) REFERENCES comment(id_comment) ON DELETE CASCADE,
    
    -- Un utilisateur ne peut avoir qu'une seule interaction par commentaire
    UNIQUE KEY unique_user_comment (id_comment, user_email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Vue pour calculer automatiquement les statistiques de notation par événement
CREATE OR REPLACE VIEW event_rating_stats AS
SELECT 
    id_evenement,
    COUNT(*) as total_comments,
    AVG(rating) as average_rating,
    SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_stars,
    SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_stars,
    SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_stars,
    SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_stars,
    SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
FROM comment
GROUP BY id_evenement;

-- ============================================
-- Migration future (quand table user sera disponible)
-- ============================================
-- ALTER TABLE comment ADD COLUMN id_user INT NULL AFTER id_evenement;
-- ALTER TABLE comment ADD FOREIGN KEY (id_user) REFERENCES user(id_user) ON DELETE CASCADE;
-- ALTER TABLE comment_interaction ADD COLUMN id_user INT NULL AFTER id_comment;
-- ALTER TABLE comment_interaction ADD FOREIGN KEY (id_user) REFERENCES user(id_user) ON DELETE CASCADE;
-- ALTER TABLE comment_interaction DROP INDEX unique_user_comment;
-- ALTER TABLE comment_interaction ADD UNIQUE KEY unique_user_comment (id_comment, id_user);
-- ============================================

-- Insertion de données de test
INSERT INTO comment (id_evenement, user_name, user_email, content, rating) VALUES
(1, 'Alice Martin', 'alice.martin@example.com', 'Événement incroyable ! Très bien organisé, ambiance au top et beaucoup de monde. Je recommande vivement !', 5),
(1, 'Bob Dupont', 'bob.dupont@example.com', 'Bonne organisation mais un peu trop de monde à mon goût. Sinon très sympa.', 4),
(1, 'Charlie Bernard', 'charlie.bernard@example.com', 'Pas mal mais j\'attendais mieux niveau animations. Le lieu était top par contre.', 3),
(1, 'Diana Laurent', 'diana.laurent@example.com', 'Superbe expérience ! L\'équipe était vraiment accueillante et professionnelle.', 5),
(1, 'Ethan Moreau', 'ethan.moreau@example.com', 'Correct mais rien d\'exceptionnel. Le prix était un peu élevé pour ce qui était proposé.', 3);

-- Quelques interactions de test
INSERT INTO comment_interaction (id_comment, user_email, interaction_type) VALUES
(1, 'test1@example.com', 'like'),
(1, 'test2@example.com', 'like'),
(1, 'test3@example.com', 'like'),
(2, 'test1@example.com', 'like'),
(3, 'test2@example.com', 'dislike');

-- Mettre à jour les compteurs
UPDATE comment SET likes = (SELECT COUNT(*) FROM comment_interaction WHERE comment_interaction.id_comment = comment.id_comment AND interaction_type = 'like');
UPDATE comment SET dislikes = (SELECT COUNT(*) FROM comment_interaction WHERE comment_interaction.id_comment = comment.id_comment AND interaction_type = 'dislike');

-- ============================================
-- Fin du script
-- ============================================
