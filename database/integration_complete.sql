-- phpMyAdmin SQL Dump
-- Complete Integration Database for FoxUnity
-- Merged from Events and Integration branches
-- Generated: December 10, 2025
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `integration`
--

DROP DATABASE IF EXISTS `integration`;
CREATE DATABASE `integration` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `integration`;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `google_id` varchar(255) DEFAULT NULL,
  `dob` date NOT NULL,
  `password` varchar(255) NOT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `role` varchar(50) DEFAULT 'user',
  `status` varchar(20) DEFAULT 'active',
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `evenement`
--

CREATE TABLE `evenement` (
  `id_evenement` int(11) NOT NULL,
  `titre` varchar(200) NOT NULL,
  `description` text NOT NULL,
  `date_debut` datetime NOT NULL,
  `date_fin` datetime NOT NULL,
  `lieu` varchar(255) NOT NULL,
  `createur_email` varchar(150) NOT NULL,
  `statut` enum('upcoming','ongoing','completed','cancelled') DEFAULT 'upcoming',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `evenement`
--

INSERT INTO `evenement` (`id_evenement`, `titre`, `description`, `date_debut`, `date_fin`, `lieu`, `createur_email`, `statut`, `created_at`, `updated_at`) VALUES
(1, 'hjjjjjjjoofffffffff', 'Describe your event', '2025-12-05 23:35:00', '2025-12-06 23:35:00', 'rtgrtgh', 'ferielayari@gmail.com', 'upcoming', '2025-12-03 22:35:40', '2025-12-03 22:35:40'),
(2, 'hjjjjjjjoofffffffff', 'Describe your event', '2025-12-05 23:35:00', '2025-12-06 23:35:00', 'rtgrtgh', 'ferielayari@gmail.com', 'upcoming', '2025-12-03 22:35:40', '2025-12-03 22:35:40'),
(3, 'kkkkkkkkkkkkkkkkkkkkk', 'Describe your event', '2025-12-04 23:39:00', '2025-12-05 23:40:00', 'rtgrtgh', 'feriel@gmail.com', 'upcoming', '2025-12-03 22:40:09', '2025-12-03 22:40:09'),
(4, 'hhhhhhhhhhhhhh', 'Describe your event', '2025-12-19 00:00:00', '2025-12-20 00:00:00', 'rtgrtgh', 'feriel@gmail.com', 'upcoming', '2025-12-03 23:00:28', '2025-12-03 23:00:28'),
(5, 'ggggggggggggg', 'Describe your event', '2026-01-09 08:46:00', '2026-01-11 08:46:00', 'bardo', 'nour@gmail.com', 'upcoming', '2025-12-04 07:46:56', '2025-12-04 07:46:56'),
(6, 'hjjjjjjjoofffffffff', 'Describe your event', '2025-12-11 10:38:00', '2025-12-12 10:38:00', 'rtgr', 'ferielayari@gmail.com', 'upcoming', '2025-12-04 09:38:23', '2025-12-04 09:38:23'),
(7, 'event1', 'Describe your event', '2025-12-05 10:39:00', '2025-12-06 10:39:00', 'bardo', 'ferielayari19@gmail.com', 'upcoming', '2025-12-04 09:39:57', '2025-12-04 09:39:57'),
(8, 'fifi20', 'Describe4444444444 your event', '2026-01-02 08:37:00', '2026-01-11 08:37:00', 'zahrouni manba3 l asatir wiouuu', 'ferielayari19@gmail.com', 'upcoming', '2025-12-09 07:37:41', '2025-12-09 07:37:41');

-- --------------------------------------------------------

--
-- Table structure for table `participation`
--

CREATE TABLE `participation` (
  `id_participation` int(11) NOT NULL,
  `id_evenement` int(11) NOT NULL,
  `nom_participant` varchar(100) NOT NULL,
  `email_participant` varchar(150) NOT NULL,
  `date_participation` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `participation`
--

INSERT INTO `participation` (`id_participation`, `id_evenement`, `nom_participant`, `email_participant`, `date_participation`) VALUES
(1, 1, 'feryyyy', 'feriel@gmail.com', '2025-12-03 23:35:56'),
(2, 3, 'feryyyy', 'feriel@gmail.com', '2025-12-03 23:40:25'),
(3, 3, 'hedi', 'hedi@gmail.com', '2025-12-03 23:44:17'),
(4, 4, 'hedi', 'feriel19@gmail.com', '2025-12-04 00:00:50'),
(5, 5, 'balha', 'hedi12@gmail.com', '2025-12-04 08:47:27'),
(6, 7, 'feriell', 'feriel1@gmail.com', '2025-12-04 10:41:11'),
(7, 7, 'jj', 'feriel19@gmail.com', '2025-12-09 08:36:57'),
(8, 8, 'hedi', 'feriel@gmail.com', '2025-12-09 08:38:05');

-- --------------------------------------------------------

--
-- Table structure for table `tickets`
--

CREATE TABLE `tickets` (
  `id_ticket` int(11) NOT NULL,
  `id_participation` int(11) NOT NULL,
  `id_evenement` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `qr_code_path` varchar(500) DEFAULT NULL,
  `status` enum('active','used','cancelled') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tickets`
--

INSERT INTO `tickets` (`id_ticket`, `id_participation`, `id_evenement`, `token`, `qr_code_path`, `status`, `created_at`, `updated_at`) VALUES
(1, 3, 3, 'TKT-9F8262F43F', 'qrcodes/ticket_3_3_1764801857.png', 'active', '2025-12-03 22:44:17', '2025-12-03 22:44:17'),
(2, 2, 3, 'TKT-6F0CD158F1', 'qrcodes/ticket_2_3_1764801922.png', 'active', '2025-12-03 22:45:22', '2025-12-03 22:45:22'),
(3, 1, 1, 'TKT-0581660914', 'qrcodes/ticket_1_1_1764801922.png', 'active', '2025-12-03 22:45:22', '2025-12-03 22:45:22'),
(4, 4, 4, 'TKT-FB9F71C3B8', 'qrcodes/ticket_4_4_1764802850.png', 'active', '2025-12-03 23:00:50', '2025-12-03 23:00:50'),
(5, 5, 5, 'TKT-9DEA598E64', 'qrcodes/ticket_5_5_1764834447.png', 'active', '2025-12-04 07:47:27', '2025-12-04 07:47:27'),
(6, 6, 7, 'TKT-A4B799F67E', 'qrcodes/ticket_6_7_1764841271.png', 'active', '2025-12-04 09:41:11', '2025-12-04 09:41:11'),
(7, 7, 7, 'TKT-AD5284789B', 'qrcodes/ticket_7_7_1765265817.png', 'active', '2025-12-09 07:36:57', '2025-12-09 07:36:57'),
(8, 8, 8, 'TKT-76C970709F', 'qrcodes/ticket_8_8_1765265885.png', 'active', '2025-12-09 07:38:05', '2025-12-09 07:38:05');

-- --------------------------------------------------------

--
-- Table structure for table `comment`
--

CREATE TABLE `comment` (
  `id_comment` int(11) NOT NULL,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `comment`
--

INSERT INTO `comment` (`id_comment`, `id_evenement`, `user_name`, `user_email`, `content`, `rating`, `likes`, `dislikes`, `is_reported`, `report_reason`, `created_at`, `updated_at`) VALUES
(1, 3, 'feriel', 'feriel@gmail.com', 'Share your experience about this event...', 3, 0, 0, 0, NULL, '2025-12-03 22:44:56', '2025-12-03 22:44:56'),
(2, 3, 'feriel', 'feriel@gmail.com', 'Share your experience about this event...', 3, 0, 0, 0, NULL, '2025-12-03 22:46:01', '2025-12-03 22:46:01'),
(3, 7, 'feriel', 'feriel19@gmail.com', 'evventttttt', 4, 0, 0, 0, NULL, '2025-12-04 09:43:24', '2025-12-04 09:43:24');

-- --------------------------------------------------------

--
-- Table structure for table `comment_interaction`
--

CREATE TABLE `comment_interaction` (
  `id_interaction` int(11) NOT NULL,
  `id_comment` int(11) NOT NULL,
  `user_email` varchar(150) NOT NULL,
  `interaction_type` enum('like','dislike') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure for view `event_rating_stats`
--

CREATE TABLE `event_rating_stats` (
`id_evenement` int(11)
,`total_comments` bigint(21)
,`average_rating` decimal(7,4)
,`five_stars` decimal(22,0)
,`four_stars` decimal(22,0)
,`three_stars` decimal(22,0)
,`two_stars` decimal(22,0)
,`one_star` decimal(22,0)
);

-- --------------------------------------------------------

--
-- Indexes for dumped tables
--

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `google_id` (`google_id`),
  ADD KEY `idx_user_email` (`email`),
  ADD KEY `idx_user_username` (`username`);

--
-- Indexes for table `evenement`
--
ALTER TABLE `evenement`
  ADD PRIMARY KEY (`id_evenement`),
  ADD KEY `idx_statut` (`statut`),
  ADD KEY `idx_date_debut` (`date_debut`),
  ADD KEY `idx_createur_email` (`createur_email`);

--
-- Indexes for table `participation`
--
ALTER TABLE `participation`
  ADD PRIMARY KEY (`id_participation`),
  ADD UNIQUE KEY `unique_participation` (`id_evenement`,`email_participant`),
  ADD KEY `idx_evenement` (`id_evenement`),
  ADD KEY `idx_email_participant` (`email_participant`);

--
-- Indexes for table `tickets`
--
ALTER TABLE `tickets`
  ADD PRIMARY KEY (`id_ticket`),
  ADD UNIQUE KEY `token` (`token`),
  ADD UNIQUE KEY `unique_ticket_per_participant` (`id_participation`,`id_evenement`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_participation` (`id_participation`),
  ADD KEY `idx_evenement` (`id_evenement`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `comment`
--
ALTER TABLE `comment`
  ADD PRIMARY KEY (`id_comment`),
  ADD KEY `idx_evenement` (`id_evenement`),
  ADD KEY `idx_user_email` (`user_email`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_rating` (`rating`),
  ADD KEY `idx_reported` (`is_reported`);

--
-- Indexes for table `comment_interaction`
--
ALTER TABLE `comment_interaction`
  ADD PRIMARY KEY (`id_interaction`),
  ADD UNIQUE KEY `unique_user_comment` (`id_comment`,`user_email`),
  ADD KEY `idx_comment` (`id_comment`),
  ADD KEY `idx_user_email` (`user_email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `evenement`
--
ALTER TABLE `evenement`
  MODIFY `id_evenement` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `participation`
--
ALTER TABLE `participation`
  MODIFY `id_participation` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `tickets`
--
ALTER TABLE `tickets`
  MODIFY `id_ticket` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `comment`
--
ALTER TABLE `comment`
  MODIFY `id_comment` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `comment_interaction`
--
ALTER TABLE `comment_interaction`
  MODIFY `id_interaction` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `evenement`
--
-- No foreign key to users table as createur_email is just a reference

--
-- Constraints for table `participation`
--
ALTER TABLE `participation`
  ADD CONSTRAINT `fk_participation_evenement` FOREIGN KEY (`id_evenement`) REFERENCES `evenement` (`id_evenement`) ON DELETE CASCADE;
-- No foreign key to users table as email_participant is just a reference

--
-- Constraints for table `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `fk_ticket_evenement` FOREIGN KEY (`id_evenement`) REFERENCES `evenement` (`id_evenement`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ticket_participation` FOREIGN KEY (`id_participation`) REFERENCES `participation` (`id_participation`) ON DELETE CASCADE;

--
-- Constraints for table `comment`
--
ALTER TABLE `comment`
  ADD CONSTRAINT `fk_comment_evenement` FOREIGN KEY (`id_evenement`) REFERENCES `evenement` (`id_evenement`) ON DELETE CASCADE;
-- No foreign key to users table as user_email is just a reference

--
-- Constraints for table `comment_interaction`
--
ALTER TABLE `comment_interaction`
  ADD CONSTRAINT `fk_interaction_comment` FOREIGN KEY (`id_comment`) REFERENCES `comment` (`id_comment`) ON DELETE CASCADE;
-- No foreign key to users table as user_email is just a reference

--
-- Structure for view `event_rating_stats`
--
DROP TABLE IF EXISTS `event_rating_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `event_rating_stats`  AS SELECT `comment`.`id_evenement` AS `id_evenement`, count(0) AS `total_comments`, avg(`comment`.`rating`) AS `average_rating`, sum(case when `comment`.`rating` = 5 then 1 else 0 end) AS `five_stars`, sum(case when `comment`.`rating` = 4 then 1 else 0 end) AS `four_stars`, sum(case when `comment`.`rating` = 3 then 1 else 0 end) AS `three_stars`, sum(case when `comment`.`rating` = 2 then 1 else 0 end) AS `two_stars`, sum(case when `comment`.`rating` = 1 then 1 else 0 end) AS `one_star` FROM `comment` GROUP BY `comment`.`id_evenement` ;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
