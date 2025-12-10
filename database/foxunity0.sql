-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 17, 2025 at 09:28 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;

-- Constraints for table `comments`
-- (link comments to article)
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `article` (`idArticle`) ON DELETE CASCADE;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `foxunity0`
--

-- --------------------------------------------------------

--
-- Table structure for table `article`
--

CREATE TABLE `article` (
  `idArticle` int(11) NOT NULL,
  `slug` varchar(150) NOT NULL,
  `id_pub` int(11) NOT NULL,
  `titre` varchar(255) NOT NULL,
  `contenu` text NOT NULL,
  `excerpt` text DEFAULT NULL,
  `summary` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `displayDate` varchar(50) DEFAULT NULL,
  `datePublication` date DEFAULT curdate(),
  `idCategorie` int(11) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `hot` tinyint(1) NOT NULL DEFAULT 0,
  `comments` longtext DEFAULT NULL,
  `comments_count` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- --------------------------------------------------------
--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `idComment` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `text` text NOT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------


--
-- Table structure for table `categorie`
--

CREATE TABLE `categorie` (
  `idCategorie` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categorie`
--

INSERT INTO `categorie` (`idCategorie`, `nom`, `description`, `created_at`) VALUES
(1, 'Gaming News', 'Latest news in the gaming industry', '2025-11-16 18:12:41'),
(2, 'eSports', 'Competitive gaming updates', '2025-11-16 18:12:41'),
(3, 'Reviews', 'Game reviews and ratings', '2025-11-16 18:12:41');

-- --------------------------------------------------------

--
-- Table structure for table `evenement`
--

CREATE TABLE `evenement` (
  `id_evenement` int(11) NOT NULL,
  `titre` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `date_debut` datetime NOT NULL,
  `date_fin` datetime NOT NULL,
  `lieu` varchar(255) DEFAULT NULL,
  `createur_id` int(11) NOT NULL,
  `statut` varchar(50) DEFAULT 'upcoming',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `participation`
--

CREATE TABLE `participation` (
  `id_participation` int(11) NOT NULL,
  `id_evenement` int(11) NOT NULL,
  `id_gamer` int(11) NOT NULL,
  `date_participation` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `produit`
--

CREATE TABLE `produit` (
  `produit_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) DEFAULT 0,
  `category` varchar(50) DEFAULT NULL,
  `brand` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `produit`
--

INSERT INTO `produit` (`produit_id`, `name`, `description`, `price`, `stock`, `category`, `brand`, `created_at`, `updated_at`) VALUES
(1, 'Gaming Mouse', 'High-precision gaming mouse', 49.99, 100, 'Peripherals', 'Razer', '2025-11-16 18:12:41', '2025-11-16 18:12:41'),
(2, 'Mechanical Keyboard', 'RGB mechanical keyboard', 129.99, 50, 'Peripherals', 'Corsair', '2025-11-16 18:12:41', '2025-11-16 18:12:41'),
(3, 'Gaming Headset', 'Surround sound headset', 79.99, 75, 'Audio', 'HyperX', '2025-11-16 18:12:41', '2025-11-16 18:12:41');

-- --------------------------------------------------------

--
-- Table structure for table `purchase`
--

CREATE TABLE `purchase` (
  `purchase_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `produit_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `donationAmount` decimal(10,2) DEFAULT 0.00,
  `purchaseDate` datetime DEFAULT current_timestamp(),
  `status` varchar(50) DEFAULT 'pending',
  `transactionId` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reclamation`
--

CREATE TABLE `reclamation` (
  `id_reclamation` int(11) NOT NULL,
  `id_utilisateur` int(11) NOT NULL,
  `sujet` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `date_creation` datetime DEFAULT current_timestamp(),
  `statut` varchar(20) DEFAULT 'pending',
  `priorite` varchar(10) DEFAULT 'normal',
  `piece_jointe` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reponse`
--

CREATE TABLE `reponse` (
  `id_reponse` int(11) NOT NULL,
  `id_reclamation` int(11) NOT NULL,
  `id_admin` int(11) NOT NULL,
  `message` text NOT NULL,
  `date_reponse` datetime DEFAULT current_timestamp(),
  `statut_reponse` varchar(20) DEFAULT 'sent'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `skins`
--

CREATE TABLE `skins` (
  `skin_id` int(11) NOT NULL,
  `owner_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `trade`
--

CREATE TABLE `trade` (
  `trade_id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `seller_id` int(11) NOT NULL,
  `skin_id` int(11) NOT NULL,
  `trade_date` datetime DEFAULT current_timestamp(),
  `trade_type` enum('buy','sell','exchange') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `article_history`
--

CREATE TABLE `article_history` (
  `id_history` int(11) NOT NULL,
  `idArticle` int(11) NOT NULL,
  `slug` varchar(150) NOT NULL,
  `titre` varchar(255) NOT NULL,
  `contenu` text NOT NULL,
  `excerpt` text DEFAULT NULL,
  `summary` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `datePublication` date DEFAULT NULL,
  `idCategorie` int(11) DEFAULT NULL,
  `hot` tinyint(1) NOT NULL DEFAULT 0,
  `edited_by` int(11) NOT NULL,
  `edited_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `dob` date NOT NULL,
  `password` varchar(255) NOT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `role` varchar(50) DEFAULT 'user',
  `status` varchar(20) DEFAULT 'active',
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `dob`, `password`, `gender`, `role`, `status`, `image`) VALUES
(4, 'MissTagada', 'dhrifmeriem1231230@gmail.com', '2005-12-10', '$2y$10$sKVc5L7xU9BC0MJNIGqVUuJDckzrsh4ZPGUfKSLyUWtW8.TwQspUK', 'Female', 'Admin', 'active', 'uploads/profiles/profile_691a176eacc73.jpg'),
(5, 'Lou', 'lou@gmail.com', '2005-10-15', '$2y$10$tD4qViTauGoJXlaSVlG72egrt.RX74tUCMi.U8tauQZtECPT8H6Ra', 'Male', 'Gamer', 'active', NULL),
(6, 'SkrrtTn', 'yassinebenmustapha05@gmail.com', '2005-09-17', '$2y$10$TEb29MhesF/CSkw.n/D8u.kmcOD.kGnJqvs/FTXj1rB6fr1LwOI4C', 'Male', 'Admin', 'active', NULL),
(7, 'Fifi', 'ferielayari19@gmail.com', '2005-07-27', '$2y$10$Bwl8JszmGDaIZudfmvlUTOnlOw/REOqL7pTqsVv2WxyeDdm49/ary', 'Female', 'Gamer', 'active', NULL);

--
-- Dumping data for table `article`
--

INSERT INTO `article` (`idArticle`, `slug`, `id_pub`, `titre`, `contenu`, `excerpt`, `summary`, `image`, `displayDate`, `datePublication`, `idCategorie`, `category`, `hot`, `comments`, `comments_count`, `created_at`, `updated_at`) VALUES
(1, 'cs2-major-update', 4, 'Counter-Strike 2 Major Update Released', 'Valve has released a major update for Counter-Strike 2, introducing new maps, weapons, and gameplay improvements. The update includes the new Dust 2 map rework and several balance changes to make the game more competitive.\n\n## New Features\n- Reworked Dust 2 map\n- New weapon skins\n- Improved matchmaking system\n\nThe community is excited about these changes.', 'Valve releases major CS2 update with new maps and balance changes.', 'Valve has released a major update for Counter-Strike 2 featuring a reworked Dust 2 map, new weapon skins, and improved matchmaking to enhance competitive gameplay.', 'uploads/images/img_69272abf42567.jpg', '2025-11-17', '2025-11-17', 1, 'Gaming News', 1, NULL, 0, '2025-11-17 10:00:00', '2025-11-17 10:00:00'),
(2, 'dota-2-tournament', 6, 'Dota 2 International Tournament Results', 'The Dota 2 International tournament has concluded with Team Spirit winning the championship. The tournament featured 18 teams competing for a $40 million prize pool.\n\n## Final Standings\n1. Team Spirit\n2. PSG.LGD\n3. Tundra Esports\n\nCongratulations to all participants!', 'Team Spirit wins Dota 2 International with record-breaking prize pool.', 'Team Spirit emerged victorious in the Dota 2 International tournament, securing the championship with a massive $40 million prize pool distributed among 18 competing teams.', 'uploads/images/img_69272c0000426.jpg', '2025-11-16', '2025-11-16', 2, 'eSports', 0, NULL, 0, '2025-11-16 15:30:00', '2025-11-16 15:30:00'),
(3, 'intel-arc-gaming', 5, 'Intel Arc GPUs Now Optimized for Gaming', 'Intel has announced optimizations for their Arc GPUs, making them more suitable for gaming. The new drivers improve performance in popular titles like Cyberpunk 2077 and Forza Horizon 5.\n\n## Performance Improvements\n- 20% better frame rates\n- Reduced latency\n- Better ray tracing support\n\nGamers can expect a smoother experience with these updates.', 'Intel Arc GPUs receive major gaming optimizations and driver updates.', 'Intel has optimized their Arc GPUs for gaming with new drivers that deliver 20% better frame rates, reduced latency, and improved ray tracing support for titles like Cyberpunk 2077.', 'uploads/images/img_69272c3585e78.jpg', '2025-11-15', '2025-11-15', 3, 'Reviews', 0, NULL, 0, '2025-11-15 12:00:00', '2025-11-15 12:00:00');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `article`
--
ALTER TABLE `article`
  ADD PRIMARY KEY (`idArticle`),
  ADD UNIQUE KEY `uniq_article_slug` (`slug`),
  ADD KEY `idx_article_pub` (`id_pub`),
  ADD KEY `idx_article_categorie` (`idCategorie`),
  ADD KEY `idx_article_datePublication` (`datePublication`),
  ADD KEY `idx_article_hot` (`hot`);

--
-- Indexes for table `categorie`
--
ALTER TABLE `categorie`
  ADD PRIMARY KEY (`idCategorie`),
  ADD UNIQUE KEY `uniq_categorie_nom` (`nom`);

--
-- Indexes for table `evenement`
--
ALTER TABLE `evenement`
  ADD PRIMARY KEY (`id_evenement`),
  ADD KEY `idx_evenement_createur` (`createur_id`),
  ADD KEY `idx_evenement_date` (`date_debut`);

--
-- Indexes for table `participation`
--
ALTER TABLE `participation`
  ADD PRIMARY KEY (`id_participation`),
  ADD UNIQUE KEY `unique_participation` (`id_evenement`,`id_gamer`),
  ADD KEY `id_gamer` (`id_gamer`);

--
-- Indexes for table `produit`
--
ALTER TABLE `produit`
  ADD PRIMARY KEY (`produit_id`);

--
-- Indexes for table `purchase`
--
ALTER TABLE `purchase`
  ADD PRIMARY KEY (`purchase_id`),
  ADD UNIQUE KEY `transactionId` (`transactionId`),
  ADD KEY `produit_id` (`produit_id`),
  ADD KEY `idx_purchase_user` (`user_id`),
  ADD KEY `idx_purchase_date` (`purchaseDate`);

--
-- Indexes for table `reclamation`
--
ALTER TABLE `reclamation`
  ADD PRIMARY KEY (`id_reclamation`),
  ADD KEY `idx_reclamation_user` (`id_utilisateur`);

--
-- Indexes for table `reponse`
--
ALTER TABLE `reponse`
  ADD PRIMARY KEY (`id_reponse`),
  ADD KEY `id_admin` (`id_admin`),
  ADD KEY `idx_reponse_reclamation` (`id_reclamation`);

--
-- Indexes for table `skins`
--
ALTER TABLE `skins`
  ADD PRIMARY KEY (`skin_id`),
  ADD KEY `idx_skins_owner` (`owner_id`);

--
-- Indexes for table `trade`
--
ALTER TABLE `trade`
  ADD PRIMARY KEY (`trade_id`),
  ADD KEY `skin_id` (`skin_id`),
  ADD KEY `idx_trade_buyer` (`buyer_id`),
  ADD KEY `idx_trade_seller` (`seller_id`);

--
-- Indexes for table `article_history`
--
ALTER TABLE `article_history`
  ADD PRIMARY KEY (`id_history`),
  ADD KEY `idx_history_article` (`idArticle`),
  ADD KEY `idx_history_edited_by` (`edited_by`),
  ADD KEY `idx_history_edited_at` (`edited_at`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_user_email` (`email`),
  ADD KEY `idx_user_username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `article`
--
ALTER TABLE `article`
  MODIFY `idArticle` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `article_history`
--
ALTER TABLE `article_history`
  MODIFY `id_history` int(11) NOT NULL AUTO_INCREMENT;

-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `idComment` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categorie`
--
ALTER TABLE `categorie`
  MODIFY `idCategorie` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `evenement`
--
ALTER TABLE `evenement`
  MODIFY `id_evenement` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `participation`
--
ALTER TABLE `participation`
  MODIFY `id_participation` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `produit`
--
ALTER TABLE `produit`
  MODIFY `produit_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `purchase`
--
ALTER TABLE `purchase`
  MODIFY `purchase_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reclamation`
--
ALTER TABLE `reclamation`
  MODIFY `id_reclamation` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reponse`
--
ALTER TABLE `reponse`
  MODIFY `id_reponse` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `skins`
--
ALTER TABLE `skins`
  MODIFY `skin_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `trade`
--
ALTER TABLE `trade`
  MODIFY `trade_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `article`
--
ALTER TABLE `article`
  ADD CONSTRAINT `article_ibfk_1` FOREIGN KEY (`id_pub`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `article_ibfk_2` FOREIGN KEY (`idCategorie`) REFERENCES `categorie` (`idCategorie`) ON DELETE SET NULL;

--
-- Constraints for table `article_history`
--
ALTER TABLE `article_history`
  ADD CONSTRAINT `article_history_ibfk_1` FOREIGN KEY (`idArticle`) REFERENCES `article` (`idArticle`) ON DELETE CASCADE,
  ADD CONSTRAINT `article_history_ibfk_2` FOREIGN KEY (`edited_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `evenement`
--
ALTER TABLE `evenement`
  ADD CONSTRAINT `evenement_ibfk_1` FOREIGN KEY (`createur_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `participation`
--
ALTER TABLE `participation`
  ADD CONSTRAINT `participation_ibfk_1` FOREIGN KEY (`id_evenement`) REFERENCES `evenement` (`id_evenement`) ON DELETE CASCADE,
  ADD CONSTRAINT `participation_ibfk_2` FOREIGN KEY (`id_gamer`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `purchase`
--
ALTER TABLE `purchase`
  ADD CONSTRAINT `purchase_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_ibfk_2` FOREIGN KEY (`produit_id`) REFERENCES `produit` (`produit_id`) ON DELETE CASCADE;

--
-- Constraints for table `reclamation`
--
ALTER TABLE `reclamation`
  ADD CONSTRAINT `reclamation_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reponse`
--
ALTER TABLE `reponse`
  ADD CONSTRAINT `reponse_ibfk_1` FOREIGN KEY (`id_reclamation`) REFERENCES `reclamation` (`id_reclamation`) ON DELETE CASCADE,
  ADD CONSTRAINT `reponse_ibfk_2` FOREIGN KEY (`id_admin`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `skins`
--
ALTER TABLE `skins`
  ADD CONSTRAINT `skins_ibfk_1` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `trade`
--
ALTER TABLE `trade`
  ADD CONSTRAINT `trade_ibfk_1` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `trade_ibfk_2` FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `trade_ibfk_3` FOREIGN KEY (`skin_id`) REFERENCES `skins` (`skin_id`) ON DELETE CASCADE;

CREATE OR REPLACE VIEW `vw_articles_with_categories` AS
SELECT 
  a.`idArticle`,
  a.`slug`,
  a.`id_pub`,
  a.`titre` AS `title`,
  a.`contenu` AS `content`,
  a.`excerpt`,
  a.`image`,
  a.`displayDate` AS `date`,
  a.`datePublication`,
  a.`idCategorie`,
  COALESCE(c.`nom`, a.`category`) AS `categorie_nom`,
  c.`description` AS `categorie_description`,
  a.`hot`,
  a.`created_at`, a.`updated_at`
FROM `article` a
LEFT JOIN `categorie` c ON c.`idCategorie` = a.`idCategorie`;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
