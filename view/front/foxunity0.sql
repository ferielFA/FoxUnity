-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 26, 2025 at 04:22 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
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
  `id_pub` int(11) NOT NULL,
  `titre` varchar(255) NOT NULL,
  `contenu` text NOT NULL,
  `datePublication` date DEFAULT curdate(),
  `idCategorie` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
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
  `image` varchar(255) DEFAULT NULL,
  `description` varchar(100) NOT NULL,
  `category` varchar(100) NOT NULL,
  `is_listed` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `skins`
--

INSERT INTO `skins` (`skin_id`, `owner_id`, `name`, `price`, `created_at`, `image`, `description`, `category`, `is_listed`) VALUES
(4, 7, 'dfdsfsd', 69.00, '2025-11-17 22:34:50', NULL, 'sdf', 'sdfds', 1),
(29, 9, 'rtre', 7457.00, '2025-11-19 21:18:26', 'images/skins/skin_691e342208329.png', 'dfdzes', 'custom', 0),
(30, 9, 'erz', 454.00, '2025-11-21 23:44:28', 'images/skins/skin_6920f95c6f491.png', 'erze', 'custom', 0),
(31, 9, 'erfze', 4545.00, '2025-11-22 00:14:17', 'images/skins/skin_69210059a1a5b.png', 'erz', 'cs2', 0),
(32, 11, 'ddfsdf', 5785.00, '2025-11-26 15:13:31', 'images/skins/skin_6927191b87692.png', 'redrt', 'custom', 0),
(33, 9, 'sqdqs', 568.00, '2025-11-26 15:16:56', 'images/skins/skin_692719e8a6bf3.png', 'ezdzsd', 'custom', 0);

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
  `trade_type` enum('buy','exchange') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `trade_conversations`
--

CREATE TABLE `trade_conversations` (
  `id` int(11) NOT NULL,
  `skin_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `trade_conversations`
--

INSERT INTO `trade_conversations` (`id`, `skin_id`, `sender_id`, `receiver_id`, `message`, `created_at`) VALUES
(1, 4, 6, 7, 'sqdsq', '2025-11-17 22:38:55'),
(2, 4, 6, 7, 'sqdqs', '2025-11-17 22:39:13'),
(3, 4, 6, 7, 'ssss', '2025-11-17 22:39:15'),
(4, 4, 6, 7, 'dsqdq', '2025-11-17 22:47:24'),
(5, 4, 6, 7, 'dddd', '2025-11-17 22:47:44'),
(6, 4, 6, 7, 'sqdqs', '2025-11-17 23:00:18'),
(8, 4, 6, 7, 'dsdsqds', '2025-11-18 20:49:02'),
(9, 4, 6, 7, 'sdqd', '2025-11-19 14:56:01'),
(10, 4, 6, 7, 'zeaze', '2025-11-19 14:56:14'),
(11, 29, 8, 6, 'erzr', '2025-11-21 23:44:33'),
(12, 4, 9, 7, '\"\'rzerez', '2025-11-22 01:23:18'),
(13, 30, 9, 8, 'sdq', '2025-11-22 01:24:40'),
(14, 29, 9, 6, 'sqdqsdqd', '2025-11-22 01:25:20'),
(15, 29, 9, 6, 'zseaze', '2025-11-22 01:25:21'),
(16, 29, 9, 6, 'zeazeaze', '2025-11-22 01:25:23'),
(17, 30, 9, 8, 'sq', '2025-11-22 01:26:50'),
(18, 30, 9, 8, 'qsqs', '2025-11-22 01:27:52'),
(19, 29, 9, 6, 'qsqsq', '2025-11-22 01:27:58'),
(20, 4, 9, 7, 'sd', '2025-11-26 13:56:09');

-- --------------------------------------------------------

--
-- Table structure for table `trade_history`
--

CREATE TABLE `trade_history` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `skin_id` int(11) NOT NULL,
  `action` enum('created','updated','deleted','buy','bought') NOT NULL,
  `skin_name` varchar(255) NOT NULL,
  `skin_price` decimal(10,2) NOT NULL,
  `skin_category` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `visible_in_trading` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `trade_history`
--

INSERT INTO `trade_history` (`id`, `user_id`, `skin_id`, `action`, `skin_name`, `skin_price`, `skin_category`, `created_at`, `visible_in_trading`) VALUES
(6, 7, 4, 'created', 'zaeaze', 7878.00, 'dsqd', '2025-11-18 15:21:07', 1),
(13, 6, 21, 'deleted', 'qsdqs', 7574.00, 'custom', '2025-11-18 16:52:42', 1),
(14, 6, 20, 'deleted', 'rezr', 4564.00, 'cs2', '2025-11-18 17:01:20', 1),
(15, 6, 22, 'created', 'dszefz', 775.00, 'valorant', '2025-11-18 17:34:14', 1),
(16, 6, 22, 'deleted', 'dszefz', 775.00, 'valorant', '2025-11-18 17:34:35', 1),
(17, 6, 23, 'created', 'zaea', 4545.00, 'custom', '2025-11-18 17:39:13', 1),
(18, 6, 23, 'deleted', 'zaea', 4545.00, 'custom', '2025-11-18 17:39:21', 1),
(19, 6, 24, 'created', 'SkrrtTn', 78.00, 'custom', '2025-11-18 19:27:56', 1),
(20, 6, 25, 'created', 'dfsdf', 868.00, 'custom', '2025-11-18 19:28:10', 1),
(21, 6, 24, 'deleted', 'SkrrtTn', 78.00, 'custom', '2025-11-18 19:28:14', 1),
(22, 6, 25, 'updated', 'dfsdf', 8680.00, 'custom', '2025-11-18 19:28:18', 1),
(23, 6, 25, 'updated', 'dfsdf', 868.00, 'custom', '2025-11-18 20:48:27', 1),
(24, 6, 25, 'deleted', 'dfsdf', 868.00, 'custom', '2025-11-18 20:48:40', 1),
(25, 6, 26, 'created', 'sqdqsd', 5647.00, 'fortnite', '2025-11-18 20:48:53', 1),
(26, 6, 27, 'created', 'ba9', 69.00, 'fortnite', '2025-11-19 09:09:49', 1),
(27, 6, 28, 'deleted', 'dqsd', 2545.00, 'custom', '2025-11-19 11:21:13', 1),
(28, 6, 26, 'deleted', 'sqdqsd', 5647.00, 'fortnite', '2025-11-19 21:18:10', 1),
(29, 6, 27, 'deleted', 'ba9', 69.00, 'fortnite', '2025-11-19 21:18:14', 1),
(30, 6, 29, 'created', 'rtre', 7457.00, 'custom', '2025-11-19 21:18:26', 1),
(31, 8, 30, 'created', 'erz', 454.00, 'custom', '2025-11-21 23:44:28', 1),
(32, 9, 31, 'created', 'erfze', 4545.00, 'cs2', '2025-11-22 00:14:17', 1),
(36, 9, 30, 'bought', 'erz', 454.00, 'custom', '2025-11-26 15:08:56', 1),
(37, 9, 29, 'bought', 'rtre', 7457.00, 'custom', '2025-11-26 15:12:57', 1),
(38, 9, 31, 'bought', 'erfze', 4545.00, 'cs2', '2025-11-26 15:13:12', 1),
(39, 9, 32, 'created', 'ddfsdf', 5785.00, 'custom', '2025-11-26 15:13:31', 1),
(40, 11, 33, 'created', 'sqdqs', 568.00, 'custom', '2025-11-26 15:16:56', 1),
(41, 11, 32, 'bought', 'ddfsdf', 5785.00, 'custom', '2025-11-26 15:17:03', 1),
(42, 9, 33, 'bought', 'sqdqs', 568.00, 'custom', '2025-11-26 15:19:53', 1);

-- --------------------------------------------------------

--
-- Table structure for table `trade_history_trading_view`
--

CREATE TABLE `trade_history_trading_view` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `skin_id` int(11) NOT NULL,
  `action` enum('created','updated','deleted','buy','bought') NOT NULL,
  `skin_name` varchar(255) NOT NULL,
  `skin_price` decimal(10,2) NOT NULL,
  `skin_category` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `trade_history_trading_view`
--

INSERT INTO `trade_history_trading_view` (`id`, `user_id`, `skin_id`, `action`, `skin_name`, `skin_price`, `skin_category`, `created_at`) VALUES
(19, 6, 29, 'created', 'rtre', 7457.00, 'custom', '2025-11-19 21:18:26'),
(20, 8, 30, 'created', 'erz', 454.00, 'custom', '2025-11-21 23:44:28'),
(21, 9, 31, 'created', 'erfze', 4545.00, 'cs2', '2025-11-22 00:14:17'),
(25, 9, 30, 'bought', 'erz', 454.00, 'custom', '2025-11-26 15:08:56'),
(26, 9, 29, 'bought', 'rtre', 7457.00, 'custom', '2025-11-26 15:12:57'),
(27, 9, 31, 'bought', 'erfze', 4545.00, 'cs2', '2025-11-26 15:13:12'),
(28, 9, 32, 'created', 'ddfsdf', 5785.00, 'custom', '2025-11-26 15:13:31'),
(29, 11, 33, 'created', 'sqdqs', 568.00, 'custom', '2025-11-26 15:16:56'),
(30, 11, 32, 'bought', 'ddfsdf', 5785.00, 'custom', '2025-11-26 15:17:03'),
(31, 9, 33, 'bought', 'sqdqs', 568.00, 'custom', '2025-11-26 15:19:53');

-- --------------------------------------------------------

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
(7, 'Fifi', 'ferielayari19@gmail.com', '2005-07-27', '$2y$10$Bwl8JszmGDaIZudfmvlUTOnlOw/REOqL7pTqsVv2WxyeDdm49/ary', 'Female', 'Gamer', 'active', NULL),
(8, 'kayokin', 'killerbeeftw1@gmail.com', '2025-11-27', '$2y$10$teF4Kc1GV9Zk1/Jk955bQOd8L2aJCFyMd4LSey0ttsYGXpi6PiBjy', 'Male', 'Gamer', 'active', 'uploads/profiles/profile_6920f9098caad.PNG'),
(9, 'bo9', 'vgsdqsdqsbfvvbvcb@gmail.com', '2025-11-03', '$2y$10$LNzHa2MQEd3wZuVfg41/TeNKkX0fD8XgqF5z0AZq/1pPW3zNrGUeO', 'Male', 'Admin', 'active', 'uploads/profiles/profile_6922f892ccb94.png'),
(10, 'ezra', 'ezra@gmail.com', '2025-11-05', '$2y$10$JH4Hqkn5A.U8gCPWvWzrTepgGrh3CRKIHAA7hj9xC0n80qLs5v4Qq', 'Male', 'Gamer', 'banned', NULL),
(11, 'zzea', 'refgtreter@nasba.com', '2025-10-30', '$2y$10$r6mGCzNZ2QOsCBlDtVmpy.XErIUonCx.XB1giR8VoVbixMQ9EAKky', 'Male', 'Gamer', 'active', 'uploads/profiles/profile_6927156ce1af7.png');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `article`
--
ALTER TABLE `article`
  ADD PRIMARY KEY (`idArticle`),
  ADD KEY `idx_article_pub` (`id_pub`),
  ADD KEY `idx_article_categorie` (`idCategorie`);

--
-- Indexes for table `categorie`
--
ALTER TABLE `categorie`
  ADD PRIMARY KEY (`idCategorie`);

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
-- Indexes for table `trade_conversations`
--
ALTER TABLE `trade_conversations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_conversation_skin` (`skin_id`),
  ADD KEY `idx_conversation_sender` (`sender_id`),
  ADD KEY `idx_conversation_receiver` (`receiver_id`),
  ADD KEY `idx_conversation_created` (`created_at`);

--
-- Indexes for table `trade_history`
--
ALTER TABLE `trade_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `trade_history_trading_view`
--
ALTER TABLE `trade_history_trading_view`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`);

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
  MODIFY `skin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `trade`
--
ALTER TABLE `trade`
  MODIFY `trade_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `trade_conversations`
--
ALTER TABLE `trade_conversations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `trade_history`
--
ALTER TABLE `trade_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `trade_history_trading_view`
--
ALTER TABLE `trade_history_trading_view`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `article`
--
ALTER TABLE `article`
  ADD CONSTRAINT `article_ibfk_1` FOREIGN KEY (`id_pub`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `article_ibfk_2` FOREIGN KEY (`idCategorie`) REFERENCES `categorie` (`idCategorie`) ON DELETE CASCADE;

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

--
-- Constraints for table `trade_conversations`
--
ALTER TABLE `trade_conversations`
  ADD CONSTRAINT `trade_conversations_ibfk_1` FOREIGN KEY (`skin_id`) REFERENCES `skins` (`skin_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `trade_conversations_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `trade_conversations_ibfk_3` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
