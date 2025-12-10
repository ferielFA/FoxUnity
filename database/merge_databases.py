import os

# This script merges the foxunity0 database with events database into integration

events_tables = """
-- Events Module Tables
CREATE TABLE `evenement` (
  `id_evenement` int(11) NOT NULL AUTO_INCREMENT,
  `titre` varchar(200) NOT NULL,
  `description` text NOT NULL,
  `date_debut` datetime NOT NULL,
  `date_fin` datetime NOT NULL,
  `lieu` varchar(255) NOT NULL,
  `createur_email` varchar(150) NOT NULL,
  `statut` enum('upcoming','ongoing','completed','cancelled') DEFAULT 'upcoming',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_evenement`),
  KEY `idx_statut` (`statut`),
  KEY `idx_date_debut` (`date_debut`),
  KEY `idx_createur_email` (`createur_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `participation` (
  `id_participation` int(11) NOT NULL AUTO_INCREMENT,
  `id_evenement` int(11) NOT NULL,
  `nom_participant` varchar(100) NOT NULL,
  `email_participant` varchar(150) NOT NULL,
  `date_participation` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_participation`),
  UNIQUE KEY `unique_participation` (`id_evenement`,`email_participant`),
  KEY `idx_evenement` (`id_evenement`),
  KEY `idx_email_participant` (`email_participant`),
  CONSTRAINT `fk_participation_evenement` FOREIGN KEY (`id_evenement`) REFERENCES `evenement` (`id_evenement`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `tickets` (
  `id_ticket` int(11) NOT NULL AUTO_INCREMENT,
  `id_participation` int(11) NOT NULL,
  `id_evenement` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `qr_code_path` varchar(500) DEFAULT NULL,
  `status` enum('active','used','cancelled') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_ticket`),
  UNIQUE KEY `token` (`token`),
  UNIQUE KEY `unique_ticket_per_participant` (`id_participation`,`id_evenement`),
  KEY `idx_token` (`token`),
  KEY `idx_participation` (`id_participation`),
  KEY `idx_evenement` (`id_evenement`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_ticket_evenement` FOREIGN KEY (`id_evenement`) REFERENCES `evenement` (`id_evenement`) ON DELETE CASCADE,
  CONSTRAINT `fk_ticket_participation` FOREIGN KEY (`id_participation`) REFERENCES `participation` (`id_participation`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `comment` (
  `id_comment` int(11) NOT NULL AUTO_INCREMENT,
  `id_evenement` int(11) NOT NULL,
  `user_name` varchar(100) NOT NULL,
  `user_email` varchar(150) NOT NULL,
  `content` text NOT NULL,
  `rating` tinyint(1) NOT NULL,
  `likes` int(11) DEFAULT 0,
  `dislikes` int(11) DEFAULT 0,
  `is_reported` tinyint(1) DEFAULT 0,
  `report_reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_comment`),
  KEY `idx_evenement` (`id_evenement`),
  KEY `idx_user_email` (`user_email`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_rating` (`rating`),
  KEY `idx_reported` (`is_reported`),
  CONSTRAINT `fk_comment_evenement` FOREIGN KEY (`id_evenement`) REFERENCES `evenement` (`id_evenement`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `comment_interaction` (
  `id_interaction` int(11) NOT NULL AUTO_INCREMENT,
  `id_comment` int(11) NOT NULL,
  `user_email` varchar(150) NOT NULL,
  `interaction_type` enum('like','dislike') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_interaction`),
  UNIQUE KEY `unique_user_comment` (`id_comment`,`user_email`),
  KEY `idx_comment` (`id_comment`),
  KEY `idx_user_email` (`user_email`),
  CONSTRAINT `fk_interaction_comment` FOREIGN KEY (`id_comment`) REFERENCES `comment` (`id_comment`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
"""

print("Database merge schema created")
print("Run this SQL file in MySQL to create the integrated database")
