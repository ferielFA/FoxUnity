-- phpMyAdmin SQL Dump
-- Complete Integration Database for FoxUnity  
-- Merged: foxunity0 (all modules) + Events Module
-- Generated: December 10, 2025

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET NAMES utf8mb4 */;

-- Database: integration

DROP DATABASE IF EXISTS `integration`;
CREATE DATABASE `integration` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `integration`;

-- ========================================
-- USER MANAGEMENT MODULE
-- ========================================

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `google_id` varchar(255) DEFAULT NULL,
  `dob` date NOT NULL,
  `password` varchar(255) NOT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `role` varchar(50) DEFAULT 'user',
  `status` varchar(20) DEFAULT 'active',
  `image` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `google_id` (`google_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `email_verifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `email_verifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ========================================
-- SHOP MODULE
-- ========================================

CREATE TABLE `produit` (
  `produit_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) DEFAULT 0,
  `category` varchar(50) DEFAULT NULL,
  `brand` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`produit_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `purchase` (
  `purchase_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `produit_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `donationAmount` decimal(10,2) DEFAULT 0.00,
  `purchaseDate` datetime DEFAULT current_timestamp(),
  `status` varchar(50) DEFAULT 'pending',
  `transactionId` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`purchase_id`),
  UNIQUE KEY `transactionId` (`transactionId`),
  KEY `user_id` (`user_id`),
  KEY `produit_id` (`produit_id`),
  CONSTRAINT `purchase_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `purchase_ibfk_2` FOREIGN KEY (`produit_id`) REFERENCES `produit` (`produit_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ========================================
-- TRADE MODULE
-- ========================================

CREATE TABLE `skins` (
  `skin_id` int(11) NOT NULL AUTO_INCREMENT,
  `owner_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `image` varchar(255) DEFAULT NULL,
  `description` varchar(100) NOT NULL,
  `category` varchar(100) NOT NULL,
  `is_listed` tinyint(1) DEFAULT 1,
  `is_deleted` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`skin_id`),
  KEY `owner_id` (`owner_id`),
  CONSTRAINT `skins_ibfk_1` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `trade` (
  `trade_id` int(11) NOT NULL AUTO_INCREMENT,
  `buyer_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `skin_id` int(11) NOT NULL,
  `trade_date` datetime DEFAULT current_timestamp(),
  `trade_type` enum('buy','exchange') NOT NULL,
  PRIMARY KEY (`trade_id`),
  KEY `buyer_id` (`buyer_id`),
  KEY `seller_id` (`seller_id`),
  KEY `skin_id` (`skin_id`),
  CONSTRAINT `trade_ibfk_1` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `trade_ibfk_3` FOREIGN KEY (`skin_id`) REFERENCES `skins` (`skin_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `trade_conversations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `skin_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `negotiation_id` varchar(50) DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `skin_id` (`skin_id`),
  KEY `sender_id` (`sender_id`),
  KEY `receiver_id` (`receiver_id`),
  CONSTRAINT `trade_conversations_ibfk_1` FOREIGN KEY (`skin_id`) REFERENCES `skins` (`skin_id`) ON DELETE CASCADE,
  CONSTRAINT `trade_conversations_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `trade_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `skin_id` int(11) NOT NULL,
  `action` enum('created','updated','deleted','buy','bought','negotiation_refused') NOT NULL,
  `skin_name` varchar(255) NOT NULL,
  `skin_price` decimal(10,2) NOT NULL,
  `skin_category` varchar(50) NOT NULL,
  `negotiation_id` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `visible_in_trading` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `trade_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ========================================
-- NEWS MODULE
-- ========================================

CREATE TABLE `categorie` (
  `idCategorie` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`idCategorie`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `article` (
  `idArticle` int(11) NOT NULL AUTO_INCREMENT,
  `id_pub` int(11) NOT NULL,
  `titre` varchar(255) NOT NULL,
  `contenu` text NOT NULL,
  `datePublication` date DEFAULT curdate(),
  `idCategorie` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`idArticle`),
  KEY `id_pub` (`id_pub`),
  KEY `idCategorie` (`idCategorie`),
  CONSTRAINT `article_ibfk_1` FOREIGN KEY (`id_pub`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `article_ibfk_2` FOREIGN KEY (`idCategorie`) REFERENCES `categorie` (`idCategorie`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ========================================
-- SUPPORT MODULE
-- ========================================

CREATE TABLE `reclamation` (
  `id_reclamation` int(11) NOT NULL AUTO_INCREMENT,
  `id_utilisateur` int(11) NOT NULL,
  `sujet` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `date_creation` datetime DEFAULT current_timestamp(),
  `statut` varchar(20) DEFAULT 'pending',
  `priorite` varchar(10) DEFAULT 'normal',
  `piece_jointe` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id_reclamation`),
  KEY `id_utilisateur` (`id_utilisateur`),
  CONSTRAINT `reclamation_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `reponse` (
  `id_reponse` int(11) NOT NULL AUTO_INCREMENT,
  `id_reclamation` int(11) NOT NULL,
  `id_admin` int(11) NOT NULL,
  `message` text NOT NULL,
  `date_reponse` datetime DEFAULT current_timestamp(),
  `statut_reponse` varchar(20) DEFAULT 'sent',
  PRIMARY KEY (`id_reponse`),
  KEY `id_reclamation` (`id_reclamation`),
  KEY `id_admin` (`id_admin`),
  CONSTRAINT `reponse_ibfk_1` FOREIGN KEY (`id_reclamation`) REFERENCES `reclamation` (`id_reclamation`) ON DELETE CASCADE,
  CONSTRAINT `reponse_ibfk_2` FOREIGN KEY (`id_admin`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ========================================
-- EVENTS MODULE
-- ========================================

CREATE TABLE `evenement` (
  `id_evenement` int(11) NOT NULL AUTO_INCREMENT,
  `titre` varchar(200) NOT NULL,
  `description` text NOT NULL,
  `date_debut` datetime NOT NULL,
  `date_fin` datetime NOT NULL,
  `lieu` varchar(255) NOT NULL,
  `createur_id` int(11) DEFAULT NULL,
  `createur_email` varchar(150) NOT NULL,
  `statut` enum('upcoming','ongoing','completed','cancelled') DEFAULT 'upcoming',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id_evenement`),
  KEY `idx_statut` (`statut`),
  KEY `idx_date_debut` (`date_debut`),
  KEY `idx_createur_id` (`createur_id`),
  KEY `idx_createur_email` (`createur_email`),
  CONSTRAINT `fk_evenement_createur` FOREIGN KEY (`createur_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `participation` (
  `id_participation` int(11) NOT NULL AUTO_INCREMENT,
  `id_evenement` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `nom_participant` varchar(100) NOT NULL,
  `email_participant` varchar(150) NOT NULL,
  `date_participation` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id_participation`),
  UNIQUE KEY `unique_participation` (`id_evenement`,`email_participant`),
  KEY `idx_evenement` (`id_evenement`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_email_participant` (`email_participant`),
  CONSTRAINT `fk_participation_evenement` FOREIGN KEY (`id_evenement`) REFERENCES `evenement` (`id_evenement`) ON DELETE CASCADE,
  CONSTRAINT `fk_participation_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
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
  `user_id` int(11) DEFAULT NULL,
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
  KEY `idx_user_id` (`user_id`),
  KEY `idx_user_email` (`user_email`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_rating` (`rating`),
  KEY `idx_reported` (`is_reported`),
  CONSTRAINT `fk_comment_evenement` FOREIGN KEY (`id_evenement`) REFERENCES `evenement` (`id_evenement`) ON DELETE CASCADE,
  CONSTRAINT `fk_comment_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `comment_interaction` (
  `id_interaction` int(11) NOT NULL AUTO_INCREMENT,
  `id_comment` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `user_email` varchar(150) NOT NULL,
  `interaction_type` enum('like','dislike') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_interaction`),
  UNIQUE KEY `unique_user_comment` (`id_comment`,`user_email`),
  KEY `idx_comment` (`id_comment`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_user_email` (`user_email`),
  CONSTRAINT `fk_interaction_comment` FOREIGN KEY (`id_comment`) REFERENCES `comment` (`id_comment`) ON DELETE CASCADE,
  CONSTRAINT `fk_interaction_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- VIEWS
-- ========================================

CREATE OR REPLACE VIEW `event_rating_stats` AS 
SELECT 
  `comment`.`id_evenement` AS `id_evenement`,
  COUNT(*) AS `total_comments`,
  AVG(`comment`.`rating`) AS `average_rating`,
  SUM(CASE WHEN `comment`.`rating` = 5 THEN 1 ELSE 0 END) AS `five_stars`,
  SUM(CASE WHEN `comment`.`rating` = 4 THEN 1 ELSE 0 END) AS `four_stars`,
  SUM(CASE WHEN `comment`.`rating` = 3 THEN 1 ELSE 0 END) AS `three_stars`,
  SUM(CASE WHEN `comment`.`rating` = 2 THEN 1 ELSE 0 END) AS `two_stars`,
  SUM(CASE WHEN `comment`.`rating` = 1 THEN 1 ELSE 0 END) AS `one_star`
FROM `comment`
GROUP BY `comment`.`id_evenement`;

COMMIT;
