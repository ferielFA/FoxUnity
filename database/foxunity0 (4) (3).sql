-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 17, 2025 at 11:56 PM
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
  `slug` varchar(255) DEFAULT NULL,
  `id_pub` int(11) NOT NULL,
  `titre` varchar(255) NOT NULL,
  `contenu` text NOT NULL,
  `datePublication` date DEFAULT curdate(),
  `idCategorie` int(11) DEFAULT NULL,
  `excerpt` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `image` varchar(255) DEFAULT NULL,
  `displayDate` varchar(50) DEFAULT NULL,
  `hot` tinyint(1) NOT NULL DEFAULT 0,
  `comments` longtext DEFAULT NULL,
  `comments_count` int(11) NOT NULL DEFAULT 0,
  `summary` text DEFAULT NULL,
  `notifications_sent` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `article`
--

INSERT INTO `article` (`idArticle`, `slug`, `id_pub`, `titre`, `contenu`, `datePublication`, `idCategorie`, `excerpt`, `created_at`, `updated_at`, `image`, `displayDate`, `hot`, `comments`, `comments_count`, `summary`, `notifications_sent`) VALUES
(5, '2', 4, 'Apex Legends Esl', 'The competitive Apex Legends scene lit up once again as ESL hosted its latest high-energy event, bringing together elite teams and thousands of viewers eager to watch the best players in action.\r\nHeld over several days, the tournament featured stacked lobbies, tight end-zones, and a level of coordination that showcased how far Apex esports has evolved.\r\n\r\nFrom the opening matches, fan-favorite teams wasted no time setting the pace. Early rounds were dominated by aggressive edge-control strategies, but later games revealed a shift toward disciplined positioning and calculated engagements — a testament to how teams have adapted to the current meta.\r\n\r\nOne of the standout storylines of the event was the consistency of top squads that maintained strong placements through smart rotations and discipline in the final circles. While kill-heavy teams brought sparks of excitement, it was ultimately the squads that balanced aggression with survival that thrived the most throughout the tournament.\r\n\r\nThe grand finals delivered peak Apex intensity. Teams battled through chaotic late-game skirmishes, third-party pressure, and unpredictable zone pulls. The final match secured the victory for the champions, who demonstrated unwavering composure, clean communication, and exceptional mechanical skill.\r\n\r\nBeyond the gameplay, the event highlighted ESL’s continued efforts in producing high-quality competitive experiences, featuring improved broadcast production, expert analysis, and community engagement. The success of this tournament further reinforces Apex Legends’ strong presence within the global esports ecosystem.\r\n\r\nAs Apex fans look ahead, the momentum from this ESL event sets the stage for even more thrilling competitions in the months to come.', '2025-11-26', 2, 'Apex Legends saw another explosive showdown as the latest ESL-hosted event brought together top squads from across the globe. With intense mechanical play, smart rotations, and late-game clutch moments, the tournament delivered one of the most exciting Apex performances of the year.', '2025-11-26 16:28:47', '2025-11-26 16:28:47', 'uploads/images/img_69272abf42567.jpg', NULL, 0, NULL, 0, NULL, 0),
(6, '3', 4, 'GTA 6', 'The gaming world erupted today as a new GTA 6 gameplay leak appeared across social platforms, giving fans yet another glimpse into Rockstar’s highly anticipated title. Although the studio has maintained complete silence, the leaked footage displayed impressive weather transitions, smoother character animations, and what seems to be a reworked police response system.\r\n\r\nFans quickly dissected every frame, pointing out new environmental details and mechanics hinting at a much more interactive open world. As usual, Rockstar has not confirmed the legitimacy of the leak, but community excitement continues to skyrocket. With the official release window approaching, players eagerly await the next official update — hoping the real reveal will surpass the leaks.', '2025-11-26', 1, 'A fresh GTA 6 gameplay leak has surfaced online, showcasing new weather effects, improved animations, and a potential look at the game’s open-world systems. Fans are more excited than ever as Rockstar remains silent.', '2025-11-26 16:34:08', '2025-11-26 16:34:08', 'uploads/images/img_69272c0000426.jpg', NULL, 0, NULL, 0, NULL, 0),
(7, '4', 4, 'Fortnite Chapter 6 Rumors Point to Massive Map Overhaul', 'Fortnite’s next chapter may be its biggest yet, according to new leaks surfacing from data miners and inside sources. Chapter 6 is rumored to feature a fully redesigned island, incorporating multi-biome zones and reactive environmental features. Potential traversal updates include wall-running and improved mobility options.\r\n\r\nEpic Games is also reportedly preparing several major crossover events with global entertainment brands — something the community now expects each season. As always, these details remain unconfirmed, but hype surrounding the next chapter continues to grow as players look forward to a fresh competitive meta and new creative mode opportunities.', '2025-11-26', 1, 'New Fortnite Chapter 6 leaks suggest a complete redesign of the current island, new traversal mechanics, and major collaborations coming to the game.', '2025-11-26 16:35:01', '2025-11-26 16:35:01', 'uploads/images/img_69272c3585e78.jpg', NULL, 0, NULL, 0, NULL, 0),
(8, '5', 4, 'Call of Duty 2026: First Look at Multiplayer Leaks', 'Leaks for Call of Duty 2025 have begun circulating, providing early insights into what could be a major shift for the franchise. Sources claim the new title will focus heavily on classic boots-on-the-ground gameplay, featuring simplified perks, a slower movement meta, and highly tactical combat.\r\n\r\nSeveral remastered maps from beloved older titles are expected to return, alongside a handful of new arenas built for competitive play. While Activision has yet to comment on the leaks, long-time fans are enthusiastic about the possibility of a more traditional Call of Duty experience.', '2025-11-26', 1, 'The first leaks for Call of Duty 2025 have emerged, hinting at a return-to-roots multiplayer design and several remastered fan-favorite maps.', '2025-11-26 16:35:48', '2025-12-10 17:17:03', 'uploads/images/img_69272c64842b9.jpg', NULL, 0, NULL, 0, NULL, 0),
(9, '6', 4, 'Valorant Mobile Beta Expands to New Regions', 'Riot Games has officially announced new regions joining the Valorant Mobile beta, marking a major milestone in the game’s development. Early testers have praised the smooth controls, impactful sound design, and faithful adaptation of PC abilities.\r\n\r\nWith new regions being invited, Riot is gathering broader feedback to refine balance, optimize performance, and ensure the competitive experience remains intact across devices. As excitement builds, fans worldwide continue waiting for news of a global launch date, which now seems closer than ever.', '2025-11-26', 1, 'Riot Games is expanding the Valorant Mobile beta into additional regions, giving more players access to the upcoming mobile tactical shooter.', '2025-11-26 16:38:11', '2025-12-10 16:59:21', 'uploads/images/img_69272cf3da86a.jpg', '2025-11-26', 0, NULL, 0, 'Riot Games has officially announced new regions joining the Valorant Mobile beta, marking a major milestone in the game’s development. Early testers...', 0),
(10, '8', 4, 'Cyberpunk 2078 Devs Confirm New Project in the Works!', 'CD Projekt Red has revealed that a new project is underway, described as a large-scale futuristic RPG building on the success of Cyberpunk 2077’s revival. While details are minimal, developers confirmed the new title will feature deeper narrative systems, improved AI, and expanded world-building.\r\n\r\nFans are already speculating whether the new project will connect to Night City or explore a completely new region of the Cyberpunk universe. With early pre-production moving forward, players can expect more concrete updates over the next year.', '2025-11-26', 1, 'CD Projekt Red has officially confirmed development on a brand-new project set within a futuristic universe, sparking excitement among Cyberpunk fans.', '2025-11-26 16:39:00', '2025-12-10 17:15:34', 'uploads/images/img_69272d24780bb.jpg', '2025-11-26', 0, '[{\"name\":\"antony benton\",\"email\":\"\",\"text\":\"sa\",\"date\":\"2025-12-03 19:59:34\"},{\"name\":\"antony benton\",\"email\":\"\",\"text\":\"aam\",\"date\":\"2025-12-03 20:03:47\"},{\"name\":\"antony benton\",\"email\":\"\",\"text\":\"Best Update Ever!\",\"date\":\"2025-12-02 20:28:13\"}]', 3, 'CD Projekt Red has revealed that a new project is underway, described as a large-scale futuristic RPG building on the success of Cyberpunk 2077’s re...', 0),
(11, '9', 4, 'EA Announces New Star Wars Game With Open-Galaxy Exploration', 'EA has unveiled its next major Star Wars project, promising an ambitious open-galaxy experience unlike any previous title in the franchise. Players will explore multiple planets, engage in dynamic ship combat, and build their own character from the ground up.\r\n\r\nDevelopers emphasized freedom of exploration, branching storylines, and a mixture of first-person and third-person gameplay. While no release date has been shared, early concept footage has already impressed fans. This new title aims to deepen the Star Wars gaming universe with more player choice than ever before.', '2025-11-24', 1, 'EA has announced a brand-new Star Wars adventure game featuring open-galaxy exploration, large-scale ship combat, and a fully customizable protagonist.', '2025-11-26 16:40:04', '2025-11-26 16:40:04', 'uploads/images/img_69272d64599e3.jpg', NULL, 0, NULL, 0, NULL, 0),
(13, '11', 4, 'Assassin’s Creed: Shadows Review – Stunning World, Mixed Execution', 'Assassin’s Creed: Shadows delivers breathtaking world-building and memorable characters, but inconsistent pacing and repetitive missions keep it from reaching masterpiece status.', '2025-11-26', 3, 'Assassin’s Creed: Shadows delivers breathtaking world-building and memorable characters, but inconsistent pacing and repetitive missions keep it from reaching masterpiece status.', '2025-11-26 16:44:51', '2025-11-29 12:51:06', 'uploads/images/img_69272e83552b6.jpg', NULL, 1, NULL, 0, NULL, 0),
(14, '12', 4, 'Nintendo Teases Major Announcement for Early 2026', 'Nintendo has confirmed a surprise broadcast event for early 2026, teasing what many believe could be the reveal of a brand-new console or major first-party title.aa', '2025-11-26', 1, 'Nintendo has confirmed a surprise broadcast event for early 2026, teasing what many believe could be the reveal of a brand-new console or major first-party titlaa', '2025-11-26 16:46:13', '2025-12-04 20:09:15', 'uploads/images/img_69272ed50a9a8.jpg', NULL, 1, NULL, 0, 'Nintendo has confirmed a surprise broadcast event for early 2026, teasing what many believe could be the reveal of a brand-new console or major first-party title.aa', 0),
(19, '51', 4, 'Epic Games Store\'s December 4 Freebies Are Completely Unlike Each Other', 'While waiting for the TGA-themed giveaway, Epic Games Store users are able to claim The Jackbox Party Pack 4 and The Darkside Detective. The former is the first Jackbox title to be offered for free on the platform since the original game in the long-running series received a 100% discount in January 2019, making it one of the earliest EGS freebies, offered just a month after the storefront\'s launch. The Jackbox Party Pack 4 includes five whimsical party games, with most of them designed for three or more players. The only exception is Fibbage 3, a wacky trivia bluffing game that can also be played in duos, with contestants trying to trick their peers into believing a made-up fact while simultaneously trying to separate other altered claims from true but utterly bizarre facts.\r\n\r\nConversely, The Darkside Detective is a fully single-player experience, one that continues Epic\'s streak of weekly point-and-click adventure freebies for the second week. Developed by Spooky Doorway, the 2017 title combines elements of pop culture, science fiction, and horror into a distinctive experience that may appeal to fans of everything from buddy comedies to David Lynch\'s Twin Peaks. Beneath its pixel art exterior is a collection of satirical mini-cases featuring a jaded detective looking into paranormal occurrences. The Darkside Detective holds a \"Strong\" rating on OpenCritic, with an average score of 76 and more than two in three reviewers recommending it', '2025-12-05', 1, 'Epic Games Strore Took an unexpected turn', '2025-12-05 22:11:40', '2025-12-05 22:11:40', 'uploads/images/img_6933589c722fd.png', NULL, 1, NULL, 0, NULL, 0);

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
-- Dumping data for table `article_history`
--

INSERT INTO `article_history` (`id_history`, `idArticle`, `slug`, `titre`, `contenu`, `excerpt`, `summary`, `image`, `datePublication`, `idCategorie`, `hot`, `edited_by`, `edited_at`) VALUES
(1, 14, '12', 'Nintendo Teases Major Announcement for Early 2026', 'Nintendo has confirmed a surprise broadcast event for early 2026, teasing what many believe could be the reveal of a brand-new console or major first-party title.aa', 'Nintendo has confirmed a surprise broadcast event for early 2026, teasing what many believe could be the reveal of a brand-new console or major first-party title.aa', '', 'uploads/images/img_69272ed50a9a8.jpg', '2025-11-26', 1, 1, 4, '2025-12-04 20:08:31'),
(2, 14, '12', 'Nintendo Teases Major Announcement for Early 2026', 'Nintendo has confirmed a surprise broadcast event for early 2026, teasing what many believe could be the reveal of a brand-new console or major first-party title.aa', 'Nintendo has confirmed a surprise broadcast event for early 2026, teasing what many believe could be the reveal of a brand-new console or major first-party title.aa', '', 'uploads/images/img_69272ed50a9a8.jpg', '2025-11-26', 1, 1, 4, '2025-12-04 20:09:15'),
(3, 10, '8', 'Cyberpunk 2077 Devs Confirm New Project in the Works', 'CD Projekt Red has revealed that a new project is underway, described as a large-scale futuristic RPG building on the success of Cyberpunk 2077’s revival. While details are minimal, developers confirmed the new title will feature deeper narrative systems, improved AI, and expanded world-building.\r\n\r\nFans are already speculating whether the new project will connect to Night City or explore a completely new region of the Cyberpunk universe. With early pre-production moving forward, players can expect more concrete updates over the next year.', 'CD Projekt Red has officially confirmed development on a brand-new project set within a futuristic universe, sparking excitement among Cyberpunk fans.', '', 'uploads/images/img_69272d24780bb.jpg', '2025-11-26', 1, 0, 4, '2025-12-10 16:34:48'),
(4, 9, '6', 'Valorant Mobile Beta Expands to New Regions', 'Riot Games has officially announced new regions joining the Valorant Mobile beta, marking a major milestone in the game’s development. Early testers have praised the smooth controls, impactful sound design, and faithful adaptation of PC abilities.\r\n\r\nWith new regions being invited, Riot is gathering broader feedback to refine balance, optimize performance, and ensure the competitive experience remains intact across devices. As excitement builds, fans worldwide continue waiting for news of a global launch date, which now seems closer than ever.', 'Riot Games is expanding the Valorant Mobile beta into additional regions, giving more players access to the upcoming mobile tactical shooter.', '', 'uploads/images/img_69272cf3da86a.jpg', '2025-11-26', 1, 0, 4, '2025-12-10 16:59:21');

-- --------------------------------------------------------

--
-- Table structure for table `categorie`
--

CREATE TABLE `categorie` (
  `idCategorie` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categorie`
--

INSERT INTO `categorie` (`idCategorie`, `nom`, `description`, `created_at`, `active`, `created_by`) VALUES
(1, 'Gaming News', 'Latest news in the gaming industry', '2025-11-16 18:12:41', 1, NULL),
(2, 'eSports', 'Competitive gaming updates', '2025-11-16 18:12:41', 1, NULL),
(3, 'Reviews', 'Game reviews and ratings', '2025-11-16 18:12:41', 1, NULL),
(5, 'Events', 'Upcoming events', '2025-11-26 18:58:12', 1, NULL),
(7, 'aab', 'aa', '2025-12-02 19:25:55', 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `charity_votes`
--

CREATE TABLE `charity_votes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `charity_key` varchar(64) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `charity_votes`
--

INSERT INTO `charity_votes` (`id`, `user_id`, `charity_key`, `created_at`) VALUES
(1, 4, 'unicef', '2025-12-17 01:27:30');

-- --------------------------------------------------------

--
-- Table structure for table `chatbot_conversations`
--

CREATE TABLE `chatbot_conversations` (
  `id` int(11) NOT NULL,
  `users_id` int(11) DEFAULT NULL,
  `users_message` text NOT NULL,
  `bot_response` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `comment`
--

CREATE TABLE `comment` (
  `id_comment` int(11) NOT NULL,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `toxicity_score` float DEFAULT 0,
  `sentiment_label` varchar(20) DEFAULT 'neutral',
  `rating` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`idComment`, `article_id`, `name`, `email`, `text`, `is_deleted`, `created_at`, `toxicity_score`, `sentiment_label`, `rating`, `user_id`, `parent_id`) VALUES
(2, 5, 'Test User', 'test@example.com', 'This is a test comment', 1, '2025-12-05 23:49:04', 0, 'neutral', NULL, NULL, NULL),
(3, 5, 'Test User', 'test@example.com', 'This is a test comment', 1, '2025-12-05 23:49:19', 0, 'neutral', NULL, NULL, NULL),
(4, 19, 'aaaaa', '', 'aaaaaaaa', 1, '2025-12-05 23:49:40', 0, 'neutral', NULL, NULL, NULL),
(5, 19, 'aaaaaa', '', 'dâsdsa', 1, '2025-12-05 23:57:35', 0, 'neutral', NULL, NULL, NULL),
(6, 8, 'aaaaa', '', '1515', 1, '2025-12-10 17:58:55', 0, 'neutral', NULL, NULL, NULL),
(7, 9, 'aaaa', '', 'sdadad', 1, '2025-12-10 23:38:04', 0, 'neutral', NULL, NULL, NULL),
(8, 9, 'aaaaaasss', '', 'i love it really !', 0, '2025-12-13 14:59:12', 0, 'positive', NULL, NULL, NULL),
(9, 9, 'ray', '', 'it\'s really bad game and it\'s shit', 0, '2025-12-13 14:59:46', 0, 'negative', NULL, NULL, NULL),
(10, 9, 'antony benton', '', 'this is ****', 0, '2025-12-13 15:07:23', 0, 'neutral', 4, NULL, NULL),
(11, 8, 'antony benton', '', 'this is **** game', 0, '2025-12-13 15:37:09', 0, 'neutral', 5, NULL, NULL),
(12, 8, 'Adolt Bitler', '', 'this very good game i love it !', 0, '2025-12-13 15:37:31', 0, 'positive', NULL, NULL, NULL),
(13, 8, 'nigga', '', 'nigga wtf is this', 1, '2025-12-13 15:37:49', 0, 'neutral', 2, NULL, NULL),
(14, 11, 'aa', '', 'aaaa', 0, '2025-12-13 16:18:26', 0, 'Neutral', NULL, NULL, NULL),
(15, 10, 'Miss', '', 'mala game', 1, '2025-12-15 11:51:29', 0, 'Neutral', NULL, NULL, NULL),
(18, 10, 'MissTagada', 'dhrifmeriem1231230@gmail.com', 'very good !', 0, '2025-12-16 15:55:14', 0, 'Positive', 5, NULL, NULL),
(19, 9, 'MissTagada', '', '+1', 1, '2025-12-16 16:05:09', 0, 'Neutral', NULL, NULL, 8),
(20, 10, 'MissTagada', '', 'aa', 1, '2025-12-16 16:33:44', 0, 'Neutral', NULL, NULL, 15),
(21, 10, 'MissTagada', '', 'y', 1, '2025-12-16 16:36:58', 0, 'Neutral', NULL, NULL, 20),
(22, 10, 'MissTagada', 'dhrifmeriem1231230@gmail.com', 'aa', 1, '2025-12-16 16:38:37', 0, 'Neutral', NULL, NULL, 21),
(23, 10, 'MissTagada', 'dhrifmeriem1231230@gmail.com', 'aa', 1, '2025-12-16 16:44:51', 0, 'Neutral', NULL, NULL, 15),
(24, 10, 'zzea', 'refgtreter@nasba.com', 'indeed!', 0, '2025-12-17 23:38:27', 0, 'Neutral', NULL, NULL, 18),
(25, 8, 'zzea', 'refgtreter@nasba.com', '****', 0, '2025-12-17 23:39:06', 20, 'Negative', NULL, NULL, NULL),
(26, 5, 'zzea', 'refgtreter@nasba.com', 'very cool', 0, '2025-12-17 23:39:33', 0, 'Positive', 5, NULL, NULL),
(27, 7, 'Rayen Kaabar', 'dhrifmeriem1231230@gmail.com', 'very cool !', 0, '2025-12-17 23:49:18', 0, 'Positive', 5, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `comment_interaction`
--

CREATE TABLE `comment_interaction` (
  `id_interaction` int(11) NOT NULL,
  `id_comment` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `user_email` varchar(150) NOT NULL,
  `interaction_type` enum('like','dislike') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `coupons`
--

CREATE TABLE `coupons` (
  `coupon_id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `discount_type` enum('percentage','fixed') DEFAULT 'percentage',
  `discount_value` decimal(10,2) NOT NULL,
  `min_purchase` decimal(10,2) DEFAULT 0.00,
  `max_discount` decimal(10,2) DEFAULT NULL,
  `usage_limit` int(11) DEFAULT NULL,
  `used_count` int(11) DEFAULT 0,
  `expires_at` datetime NOT NULL,
  `publisher_id` int(11) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `coupons`
--

INSERT INTO `coupons` (`coupon_id`, `code`, `discount_type`, `discount_value`, `min_purchase`, `max_discount`, `usage_limit`, `used_count`, `expires_at`, `publisher_id`, `is_active`, `created_at`, `updated_at`) VALUES
(9, 'WELCOME10', 'percentage', 10.00, 0.00, 50.00, NULL, 0, '2026-01-16 20:55:59', 4, 1, '2025-12-17 19:55:59', '2025-12-17 19:55:59'),
(10, 'SAVE20', 'percentage', 20.00, 50.00, 100.00, 100, 0, '2026-02-15 20:55:59', 4, 1, '2025-12-17 19:55:59', '2025-12-17 19:55:59'),
(11, 'FLASH5', 'fixed', 5.00, 10.00, NULL, 50, 0, '2025-12-24 20:55:59', 4, 1, '2025-12-17 19:55:59', '2025-12-17 19:55:59'),
(12, 'VIP30', 'percentage', 30.00, 100.00, 150.00, 20, 0, '2026-03-17 20:55:59', 4, 1, '2025-12-17 19:55:59', '2025-12-17 19:55:59');

-- --------------------------------------------------------

--
-- Table structure for table `coupon_usage`
--

CREATE TABLE `coupon_usage` (
  `usage_id` int(11) NOT NULL,
  `coupon_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `used_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `order_amount` decimal(10,2) NOT NULL,
  `discount_applied` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_verifications`
--

CREATE TABLE `email_verifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `email_verifications`
--

INSERT INTO `email_verifications` (`id`, `user_id`, `token`, `expires_at`, `created_at`) VALUES
(1, 12, '1fd5637ac14ccc2dde21326467d93149fce83d3ff786480c71b69d32b2e8efe9', '2025-12-02 16:48:20', '2025-12-02 15:45:20'),
(2, 13, '50e18756a2d27c8b36e94a34daf6e4c6907369f9ca7b5fa0a717279fc80bb3f7', '2025-12-02 16:49:35', '2025-12-02 15:46:35'),
(5, 14, 'f81e2675152270e525afc556a12c88f2e3c35ceaa6d77410e1af0bb28bcce40c', '2025-12-02 18:50:59', '2025-12-02 17:47:59'),
(6, 15, '73b4a40f57694fd35f64b678fcb38e364b0d7e0befd97c3de33a090672c9354a', '2025-12-10 12:38:38', '2025-12-10 11:35:38');

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
  `createur_id` int(11) DEFAULT NULL,
  `createur_email` varchar(150) NOT NULL,
  `statut` enum('upcoming','ongoing','completed','cancelled') DEFAULT 'upcoming',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `event_statistics`
--

CREATE TABLE `event_statistics` (
  `id_statistic` int(11) NOT NULL,
  `id_evenement` int(11) NOT NULL,
  `creator_id` int(11) DEFAULT NULL,
  `event_title` varchar(200) DEFAULT NULL,
  `event_location` varchar(255) DEFAULT NULL,
  `event_status` enum('upcoming','ongoing','completed','cancelled') DEFAULT NULL,
  `event_start` datetime DEFAULT NULL,
  `event_end` datetime DEFAULT NULL,
  `total_participants` int(11) DEFAULT 0,
  `total_tickets` int(11) DEFAULT 0,
  `active_tickets` int(11) DEFAULT 0,
  `used_tickets` int(11) DEFAULT 0,
  `cancelled_tickets` int(11) DEFAULT 0,
  `total_comments` int(11) DEFAULT 0,
  `average_rating` decimal(3,2) DEFAULT 0.00,
  `five_stars` int(11) DEFAULT 0,
  `four_stars` int(11) DEFAULT 0,
  `three_stars` int(11) DEFAULT 0,
  `two_stars` int(11) DEFAULT 0,
  `one_star` int(11) DEFAULT 0,
  `total_likes` int(11) DEFAULT 0,
  `total_dislikes` int(11) DEFAULT 0,
  `reported_comments` int(11) DEFAULT 0,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `participation`
--

CREATE TABLE `participation` (
  `id_participation` int(11) NOT NULL,
  `id_evenement` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `nom_participant` varchar(100) NOT NULL,
  `email_participant` varchar(150) NOT NULL,
  `date_participation` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `user_id`, `token`, `expires_at`, `created_at`) VALUES
(32, 6, '09291a999da264e21041824e117b5d423466b01483b8edb2a9c1a4f307bc1d2f', '2025-12-05 21:34:03', '2025-12-05 20:31:03');

-- --------------------------------------------------------

--
-- Table structure for table `produit`
--

CREATE TABLE `produit` (
  `produit_id` int(11) NOT NULL,
  `publisher_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) DEFAULT 0,
  `category` varchar(50) DEFAULT NULL,
  `brand` varchar(50) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `produit`
--

INSERT INTO `produit` (`produit_id`, `publisher_id`, `name`, `description`, `price`, `stock`, `category`, `brand`, `image`, `created_at`, `updated_at`) VALUES
(2, NULL, 'Razer Gaming Mouse', 'RGB mechanical keyboard', 129.50, 46, 'Accessory', 'Corsair', 'front/uploads/1765914278_image_2025-12-16_204416781.png', '2025-11-16 18:12:41', '2025-12-17 18:32:37'),
(3, NULL, 'Logitech Gaming Headset', 'Surround sound headset', 300.00, 76, 'Accessory', 'HyperX', 'front/uploads/1765914569_logitech.jpg', '2025-11-16 18:12:41', '2025-12-17 12:56:45'),
(4, 4, 'NVIDIA GeForce RTX 5090 Graphics Card', 'Neww outt', 2000.00, 67, 'Hardware', NULL, 'front/uploads/1765916258_image_2025-12-16_211703451.png', '2025-12-16 20:17:38', '2025-12-17 12:55:59'),
(5, 4, 'Benqxl2411p', 'esports gaming set', 500.00, 50, 'Accessory', NULL, 'front/uploads/1765916456_ecran.avif', '2025-12-16 20:20:56', '2025-12-16 20:20:56'),
(6, 4, 'MSI Gaming Mousepad', 'cool', 50.00, 47, 'Accessory', NULL, 'front/uploads/1765916526_image_2025-12-16_212151424.png', '2025-12-16 20:22:06', '2025-12-17 18:32:01'),
(7, 4, 'REDRAGON PAGOS Gaming PC Case', 'Boîtier Gamer REDRAGON PAGOS 2 GC-580 M-ATX - Matériau: Acier, plastique, verre trempé, maille - Compatibilité carte mère: MicroATX - Ports: 1x USB 3.0, / 2x USB 2.0, 1x Audio HD - Refroidissement exceptionnel grâce à ses 3 ventilateurs ARGB - Couleur Blanc - Garantie 1 an', 700.00, 19, 'Peripherals', NULL, 'front/uploads/1765916705_boiter.png', '2025-12-16 20:25:05', '2025-12-17 18:14:28'),
(8, 4, 'MSI MPG B550 Motherboard', 'Carte mère MSI MPG B550 GAMING PLUS - Socket AMD AM4 pour processeur AMD Ryzen 3ème génération - 4 Slots mémoire DDR4 4400+ MHz (OC) Dual-Channel - 1 x M.2 PCIe 4.0 x4 / SATA 6 Gbit/s + 1 x M.2 PCIe 3.0 x4 avec un dissipateur Frozr - 1 port PCI-Express 4.0 16x + 1 PCIe 3.0 16x (4x) avec prise en charge Multi-GPU AMD CrossFireX - Ports USB 3.1 dont un type C - Mystic Light - Dimensions 305 x 244 mm - Garantie 1 an', 400.00, 9, 'Merchandise', NULL, 'front/uploads/1765916826_image_2025-12-16_212653646.png', '2025-12-16 20:27:06', '2025-12-17 13:51:12'),
(9, 4, 'MSI FORGE GK100 Gaming Keyboard', 'Clavier Gaming MSI FORGE GK100 - Interface: USB 2.0 - Rétroéclairage RGB - Type de commutateur: Membrane - Vie de Coupe de Clé: 10 millions - 108 touches - 19 clés ANTI-FANTÔME - LONGUEUR DU CÂBLE: 1.5 métre - DIMENSIONS: 454 x 186 x 40 mm - POIDS: 756 g - Couleur Noir - Garantie 1 an', 100.00, 79, 'Peripherals', NULL, 'front/uploads/1765918256_clavier 2.png', '2025-12-16 20:50:56', '2025-12-17 18:07:43'),
(10, 4, 'AMD Ryzen 7 5700 Processor', 'Processeur AMD Ryzen 7 5700 - Fréquence de base 3.8 GHz - Fréquence Boost maximale Jusqu`à 4.6 GHz - mémoire cache (L2 4 Mo + L3 16 Mo) - 8 Cœurs CPU - 16 Threads - Support du processeur: AMD AM4 - Plateforme: AMD Zen 3 - TDP : 65 W - Contrôleur mémoire DDR4 - Fréquence : 2000 MHz - Garantie 1 an', 500.00, 12, 'Hardware', NULL, 'front/uploads/1765922282_ryzen.png', '2025-12-16 21:58:02', '2025-12-17 12:55:59'),
(11, 4, 'Gigabyte AMD Radeon RX 7700 Graphics Card', 'Carte graphique GIGABYTE Radeon RX 7700 XT GAMING OC - Carte Bus : PCI-E 4.0 - Horloge centrale : Boost Clock : jusqu\'à 2599 MHz (carte de référence : 2544 MHz) / Game Clock : jusqu\'à 2276 MHz (carte de référence : 2171 MHz) - Bus mémoire : 192 bits - MÉMOIRE : 12 Go - Type de mémoire : GDDR6 - Horloge mémoire : 18 Gbit/s - Résolution numérique maximale : 7680 x 4320 - Processeurs de flux : 3456 - Vues multiples : 4 - Formulaire PCB : ATX - DirectX : 12 Ultime - OpenGL 4.6 - Bloc d\'alimentation recommandé : 700 W - Connecteurs d\'alimentation : 8 broches x 2 - Sortir : DisplayPort 2.1 x 2, HDMI 2.1 x 2 - RGB Mode - BIOS double - Système de refroidissement WINDFORCE - Plaque arrière de protection en métal - Dimensions : 302 x 130 x 56 mm - Garantie 1 an', 1000.00, 69, 'Hardware', NULL, 'front/uploads/1765922442_image_2025-12-16_230008354.png', '2025-12-16 22:00:42', '2025-12-17 13:51:12'),
(12, 4, 'Redragon PEARL 23.6\" Full HD Curved Gaming Monitor', 'écran Gaming Curved Redragon PEARL - Taille de l\'ecran: 23.6\" FULL HD LED - Résolution: (1920 x 1080 pixels) - Luminosité: 250cd/m2 - Contrast: 3000:1 - Taux de rafraîchissement 180 Hz - FreeSync - Temps de réponse : 1 ms - 5 Modes (Standard, Film, Jeux FPS, RTS, Eye Saver ) - Extreme OD functionality - HDMI - Sorties audio Casque Jack 3.5mm - Garantie 1 an ', 319.00, 38, 'Accessory', NULL, 'front/uploads/1765922807_image_2025-12-16_230611533.png', '2025-12-16 22:06:47', '2025-12-17 20:40:54');

-- --------------------------------------------------------

--
-- Table structure for table `purchase`
--

CREATE TABLE `purchase` (
  `purchase_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `produit_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `purchaseDate` datetime DEFAULT current_timestamp(),
  `status` varchar(50) DEFAULT 'pending',
  `transactionId` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `purchase`
--

INSERT INTO `purchase` (`purchase_id`, `user_id`, `produit_id`, `amount`, `purchaseDate`, `status`, `transactionId`) VALUES
(1, 4, 2, 129.50, '2025-12-16 21:03:17', 'pending', 'txn_prod_6941bb050221d'),
(2, 4, 3, 79.99, '2025-12-16 21:03:17', 'pending', 'txn_prod_6941bb0507648'),
(3, 4, 2, 129.50, '2025-12-16 21:04:03', 'pending', 'txn_prod_6941bb330076f'),
(4, 4, 2, 129.50, '2025-12-16 21:13:58', 'pending', 'txn_prod_6941bd86d547e'),
(5, 4, 11, 1000.00, '2025-12-17 14:51:12', 'pending', 'txn_prod_6942b55088cdf'),
(6, 4, 8, 400.00, '2025-12-17 14:51:12', 'pending', 'txn_prod_6942b5508cfd8'),
(7, 4, 12, 319.00, '2025-12-17 18:58:50', 'pending', 'txn_prod_6942ef5ab76a2'),
(8, 4, 9, 100.00, '2025-12-17 19:07:43', 'pending', 'txn_prod_6942f16f5402d'),
(9, 4, 7, 700.00, '2025-12-17 19:14:28', 'pending', 'txn_prod_6942f30432271'),
(10, 4, 6, 50.00, '2025-12-17 19:21:57', 'pending', 'txn_prod_6942f4c551e33'),
(11, 4, 6, 50.00, '2025-12-17 19:30:59', 'pending', 'txn_prod_6942f6e358a38'),
(12, 4, 6, 50.00, '2025-12-17 19:32:01', 'pending', 'txn_prod_6942f7219a2c5'),
(13, 4, 2, 129.50, '2025-12-17 19:32:37', 'pending', 'txn_prod_6942f74580d81'),
(14, 4, 12, 319.00, '2025-12-17 21:40:54', 'pending', 'txn_prod_6943155642cb6');

-- --------------------------------------------------------

--
-- Table structure for table `reclamations`
--

CREATE TABLE `reclamations` (
  `id_reclamation` int(11) NOT NULL,
  `id_utilisateur` int(11) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `sujet` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `date_creation` datetime NOT NULL DEFAULT current_timestamp(),
  `statut` varchar(50) DEFAULT 'nouveau',
  `piece_jointe` varchar(255) DEFAULT NULL,
  `categorie` varchar(100) DEFAULT 'Other'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reclamations`
--

INSERT INTO `reclamations` (`id_reclamation`, `id_utilisateur`, `email`, `sujet`, `description`, `date_creation`, `statut`, `piece_jointe`, `categorie`) VALUES
(8, NULL, 'mayssa@gmail.com', 'Trading Issues', 'hhhhhhhhhhhh', '2025-12-15 13:29:31', 'en_cours', NULL, 'Trading Issues'),
(9, NULL, 'mayssa@gmail.com', 'Shop & Orders', 'bbbbbbbbbbbbb5454', '2025-12-15 13:29:40', 'resolu', NULL, 'Other'),
(10, NULL, 'toukebri@gmail.com', 'Charity & Donations', 'bbbbjjkolojhgffdd', '2025-12-15 13:44:59', 'en_cours', NULL, 'Charity & Donations'),
(11, NULL, 'toukebri@gmail.com', 'Shop & Orders', 'ccccccccccccccccc', '2025-12-15 13:48:02', 'en_cours', NULL, 'Shop & Orders'),
(12, NULL, 'dhrifmeriem1231230@gmail.com', 'Other', 'please help', '2025-12-17 16:33:36', 'resolu', NULL, 'Other'),
(13, NULL, 'misstagada1231230@gmail.com', 'Events & Tournaments', 'my product has been stolen', '2025-12-17 19:00:43', 'nouveau', NULL, 'Events & Tournaments'),
(14, NULL, 'claudemimi065@gmail.com', 'Charity & Donations', 'i want to give to charity where ?', '2025-12-17 19:33:50', 'en_cours', NULL, 'Charity & Donations');

-- --------------------------------------------------------

--
-- Table structure for table `reponses`
--

CREATE TABLE `reponses` (
  `id_reponse` int(11) NOT NULL,
  `id_reclamation` int(11) NOT NULL,
  `id_admin` int(11) NOT NULL,
  `message` text NOT NULL,
  `date_reponse` datetime NOT NULL DEFAULT current_timestamp(),
  `statut_reponse` varchar(50) DEFAULT 'sent'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reponses`
--

INSERT INTO `reponses` (`id_reponse`, `id_reclamation`, `id_admin`, `message`, `date_reponse`, `statut_reponse`) VALUES
(1, 9, 4, 'yyyyyyyyyyyyyyyy', '2025-12-15 13:30:00', 'sent'),
(8, 12, 6, 'yeah tell us please', '2025-12-17 17:38:24', 'sent'),
(9, 12, 6, 'oui je vous ecoute', '2025-12-17 17:44:59', 'sent'),
(10, 12, 6, 'yeahh we are hereee', '2025-12-17 17:50:03', 'sent'),
(11, 12, 6, 'yeah we are here', '2025-12-17 19:18:13', 'sent');

-- --------------------------------------------------------

--
-- Table structure for table `satisfactions`
--

CREATE TABLE `satisfactions` (
  `id_satisfaction` int(11) NOT NULL,
  `id_reclamation` int(11) NOT NULL,
  `id_user` int(11) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `rating` int(11) NOT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `commentaire` text DEFAULT NULL,
  `date_evaluation` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `satisfactions`
--

INSERT INTO `satisfactions` (`id_satisfaction`, `id_reclamation`, `id_user`, `email`, `rating`, `commentaire`, `date_evaluation`) VALUES
(34, 9, NULL, 'anonymous_cadd229d80bae264960c92a99d5d7810', 2, NULL, '2025-12-15 13:34:29'),
(35, 8, NULL, 'user_04d449c3fbba40291dc64abf6c5d574d', 5, 'Évalué par: mayssa | good', '2025-12-15 13:35:08'),
(36, 8, NULL, 'anonymous_b750ca25d7601b119afaf5f005e114b1', 3, NULL, '2025-12-15 13:35:27'),
(37, 10, NULL, 'anonymous_e0f7b7fb7093ba0ef799d0f2f3aaae65', 3, NULL, '2025-12-15 13:45:58'),
(38, 11, NULL, 'anonymous_3f7046205e5db6e02bf761e0e6532edf', 1, NULL, '2025-12-15 13:48:11'),
(39, 14, NULL, 'anonymous_e861706540385c5311f617319135edb9', 5, 'woahh mala trade', '2025-12-17 21:29:39');

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
  `is_listed` tinyint(1) DEFAULT 1,
  `is_deleted` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `skins`
--

INSERT INTO `skins` (`skin_id`, `owner_id`, `name`, `price`, `created_at`, `image`, `description`, `category`, `is_listed`, `is_deleted`) VALUES
(4, 9, 'dfdsfsd', 69.00, '2025-11-17 22:34:50', NULL, 'sdf', 'sdfds', 0, 0),
(29, 9, 'rtre', 7457.00, '2025-11-19 21:18:26', 'images/skins/skin_691e342208329.png', 'dfdzes', 'custom', 0, 0),
(30, 9, 'erz', 454.00, '2025-11-21 23:44:28', 'images/skins/skin_6920f95c6f491.png', 'erze', 'custom', 0, 0),
(31, 9, 'erfze', 4545.00, '2025-11-22 00:14:17', 'images/skins/skin_69210059a1a5b.png', 'erz', 'cs2', 0, 0),
(32, 11, 'ddfsdf', 5785.00, '2025-11-26 15:13:31', 'images/skins/skin_6927191b87692.png', 'redrt', 'custom', 0, 0),
(33, 9, 'sqdqs', 568.00, '2025-11-26 15:16:56', 'images/skins/skin_692719e8a6bf3.png', 'ezdzsd', 'custom', 0, 0),
(34, 9, 'zae', 45.00, '2025-11-26 15:25:38', 'images/skins/skin_69271bf26f637.png', 'rzfezr', 'custom', 0, 0),
(35, 9, 'zaeaze', 4144.00, '2025-11-26 15:25:44', 'images/skins/skin_69271bf89b2a7.png', 'zsdezaedza', 'custom', 0, 0),
(36, 9, 'zaeazea', 5455.00, '2025-11-26 15:25:50', 'images/skins/skin_69271bfe63562.png', '\"azaz', 'custom', 0, 0),
(37, 9, 'edsrzaer', 5454.00, '2025-11-26 17:22:49', 'images/skins/skin_6927376917ba5.png', 'dfsd', 'custom', 0, 0),
(38, 9, 'zerzerze', 45475.00, '2025-11-26 17:22:59', 'images/skins/skin_692737737ee9b.png', 'dssqd', 'custom', 0, 0),
(39, 9, 'sqdqsd', 1454.00, '2025-11-26 17:23:06', 'images/skins/skin_6927377a383da.png', 'erdzeza', 'custom', 0, 0),
(41, 9, 'zaazeaz', 585.00, '2025-11-26 19:59:52', 'images/skins/skin_69275c387b506.png', 'zeaze', 'custom', 0, 0),
(42, 9, 'ezrzer7785', 787.00, '2025-11-26 20:00:00', 'images/skins/skin_69275c4059e95.png', 'erezr', 'custom', 0, 0),
(43, 9, 'zeazea', 45874.00, '2025-11-26 20:00:08', 'images/skins/skin_69275c4886126.png', 'ezrzre', 'custom', 0, 0),
(44, 9, 'zeza', 47787.00, '2025-11-26 20:27:38', 'images/skins/skin_692762ba13be3.png', 'zaeaze', 'cs2', 0, 0),
(45, 9, 'zeaze', 45452.00, '2025-11-26 20:27:49', 'images/skins/skin_692762c56dced.png', 'sdqsd', 'custom', 0, 0),
(46, 9, 'srzerez', 4568.00, '2025-11-26 20:44:30', 'images/skins/skin_692766ae82661.png', 'erzer', 'custom', 0, 0),
(47, 9, 'dssqdqsd', 45855.00, '2025-11-26 20:44:36', 'images/skins/skin_692766b4e253e.png', 'frzerze', 'custom', 0, 0),
(48, 9, 'zerezrzer', 57868.00, '2025-11-26 20:44:49', 'images/skins/skin_692766c110dc6.png', 'ezrzaza', 'custom', 0, 0),
(51, 9, 'oui', 4566.00, '2025-11-27 08:31:39', 'images/skins/skin_69280c6bdf3f4.png', 'zeazezae', 'fortnite', 0, 0),
(52, 9, 'bom', 45878.00, '2025-11-27 08:31:51', 'images/skins/skin_69280c771e919.png', 'zeazea', 'cs2', 0, 0),
(73, 17, 'reaver phanthom', 50.00, '2025-12-13 15:05:28', 'images/skins/skin_693d80b834594.jpg', 'best skin', 'valorant', 1, 0),
(74, 17, 'dragon lore', 1200.00, '2025-12-13 15:05:59', 'images/skins/skin_693d80d723698.jpg', 'the dragon lore', 'cs2', 1, 0),
(75, 11, 'test', 500.00, '2025-12-13 15:42:14', 'images/skins/skin_693d8956879b7.png', 'hiiiii', 'custom', 0, 0),
(76, 11, 'Miss', 500.00, '2025-12-13 15:51:18', 'images/skins/skin_693d8b76c3240.png', 'hiii', 'custom', 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `subscribers`
--

CREATE TABLE `subscribers` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `category_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `categories` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subscribers`
--

INSERT INTO `subscribers` (`id`, `email`, `user_id`, `category_id`, `created_at`, `categories`) VALUES
(19, 'rayenkabar780@gmail.com', NULL, 2, '2025-12-16 15:17:44', ''),
(20, 'rayenkabar780@gmail.com', NULL, 5, '2025-12-16 15:17:44', ''),
(21, 'rayenkabar780@gmail.com', NULL, 1, '2025-12-16 15:17:44', ''),
(22, 'dhrifmeriem1231230@gmail.com', 4, 1, '2025-12-17 22:40:33', ''),
(23, 'dhrifmeriem1231230@gmail.com', 4, 3, '2025-12-17 22:40:33', '');

-- --------------------------------------------------------

--
-- Table structure for table `tickets`
--

CREATE TABLE `tickets` (
  `id_ticket` int(11) NOT NULL,
  `id_participation` int(11) NOT NULL,
  `id_evenement` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `token` varchar(255) NOT NULL,
  `qr_code_path` varchar(500) DEFAULT NULL,
  `status` enum('active','used','cancelled') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

--
-- Dumping data for table `trade`
--

INSERT INTO `trade` (`trade_id`, `buyer_id`, `seller_id`, `skin_id`, `trade_date`, `trade_type`) VALUES
(1, 9, 11, 34, '2025-11-26 16:30:47', 'buy'),
(2, 9, 11, 38, '2025-11-26 18:48:25', 'buy'),
(3, 9, 11, 39, '2025-11-26 18:49:30', 'buy'),
(4, 9, 11, 37, '2025-11-26 20:50:27', 'buy'),
(5, 9, 11, 42, '2025-11-26 21:00:38', 'buy'),
(6, 9, 11, 41, '2025-11-26 21:05:04', 'buy'),
(7, 9, 11, 43, '2025-11-26 21:14:28', 'buy'),
(8, 9, 11, 44, '2025-11-26 21:28:08', 'buy'),
(9, 9, 11, 45, '2025-11-26 21:36:50', 'buy'),
(10, 9, 7, 4, '2025-11-26 21:38:52', 'buy'),
(11, 9, 11, 47, '2025-11-26 21:45:07', 'buy'),
(12, 9, 11, 46, '2025-11-26 21:45:36', 'buy'),
(13, 9, 11, 48, '2025-11-26 21:50:38', 'buy'),
(14, 9, 11, 51, '2025-11-27 10:06:24', 'buy'),
(15, 9, 11, 52, '2025-11-27 10:10:47', 'buy'),
(18, 11, 4, 75, '2025-12-13 16:44:10', 'buy'),
(19, 11, 4, 76, '2025-12-13 16:54:55', 'buy');

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
  `image_path` varchar(255) DEFAULT NULL,
  `negotiation_id` varchar(50) DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `trade_conversations`
--

INSERT INTO `trade_conversations` (`id`, `skin_id`, `sender_id`, `receiver_id`, `message`, `image_path`, `negotiation_id`, `is_deleted`, `updated_at`, `created_at`) VALUES
(117, 74, 17, 18, 'slm', NULL, 'neg_693d83736dd58', 1, NULL, '2025-12-13 15:17:03'),
(118, 75, 11, 4, 'hii', NULL, 'neg_ok_693d89ca3fbe2', 1, NULL, '2025-12-13 15:43:35'),
(119, 76, 11, 4, 'slmmmmm', NULL, 'neg_ok_693d8c4f66355', 1, NULL, '2025-12-13 15:53:55'),
(120, 76, 4, 11, 'slmm', NULL, 'neg_ok_693d8c4f66355', 1, NULL, '2025-12-13 15:54:53'),
(121, 74, 4, 17, 'slm', NULL, NULL, 0, NULL, '2025-12-16 19:45:53');

-- --------------------------------------------------------

--
-- Table structure for table `trade_history`
--

CREATE TABLE `trade_history` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `skin_id` int(11) NOT NULL,
  `action` enum('created','updated','deleted','buy','bought','sold','negotiation_refused','trade') NOT NULL,
  `skin_name` varchar(255) NOT NULL,
  `skin_price` decimal(10,2) NOT NULL,
  `skin_category` varchar(50) NOT NULL,
  `negotiation_id` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `visible_in_trading` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `trade_history`
--

INSERT INTO `trade_history` (`id`, `user_id`, `skin_id`, `action`, `skin_name`, `skin_price`, `skin_category`, `negotiation_id`, `created_at`, `visible_in_trading`) VALUES
(6, 7, 4, 'created', 'zaeaze', 7878.00, 'dsqd', NULL, '2025-11-18 15:21:07', 1),
(31, 8, 30, 'created', 'erz', 454.00, 'custom', NULL, '2025-11-21 23:44:28', 1),
(40, 11, 33, 'created', 'sqdqs', 568.00, 'custom', NULL, '2025-11-26 15:16:56', 1),
(41, 11, 32, 'bought', 'ddfsdf', 5785.00, 'custom', NULL, '2025-11-26 15:17:03', 1),
(43, 11, 34, 'created', 'zae', 45.00, 'custom', NULL, '2025-11-26 15:25:38', 1),
(44, 11, 35, 'created', 'zaeaze', 4144.00, 'custom', NULL, '2025-11-26 15:25:44', 1),
(45, 11, 36, 'created', 'zaeazea', 5455.00, 'custom', NULL, '2025-11-26 15:25:50', 1),
(49, 11, 37, 'created', 'edsrzaer', 5454.00, 'custom', NULL, '2025-11-26 17:22:49', 1),
(50, 11, 38, 'created', 'zerzerze', 45475.00, 'custom', NULL, '2025-11-26 17:22:59', 1),
(51, 11, 39, 'created', 'sqdqsd', 1454.00, 'custom', NULL, '2025-11-26 17:23:06', 1),
(57, 11, 41, 'created', 'zaazeaz', 585.00, 'custom', NULL, '2025-11-26 19:59:52', 1),
(58, 11, 42, 'created', 'ezrzer7785', 787.00, 'custom', NULL, '2025-11-26 20:00:00', 1),
(59, 11, 43, 'created', 'zeazea', 45874.00, 'custom', NULL, '2025-11-26 20:00:08', 1),
(63, 11, 44, 'created', 'zeza', 47787.00, 'cs2', NULL, '2025-11-26 20:27:38', 1),
(64, 11, 45, 'created', 'zeaze', 45452.00, 'custom', NULL, '2025-11-26 20:27:49', 1),
(68, 11, 46, 'created', 'srzerez', 4568.00, 'custom', NULL, '2025-11-26 20:44:30', 1),
(69, 11, 47, 'created', 'dssqdqsd', 45855.00, 'custom', NULL, '2025-11-26 20:44:36', 1),
(70, 11, 48, 'created', 'zerezrzer', 57868.00, 'custom', NULL, '2025-11-26 20:44:49', 1),
(76, 11, 49, 'created', 'blue dragon', 4564.00, 'cs2', NULL, '2025-11-27 08:31:09', 1),
(77, 11, 50, 'created', 'red dragon', 5454.00, 'custom', NULL, '2025-11-27 08:31:26', 1),
(78, 11, 51, 'created', 'oui', 4566.00, 'fortnite', NULL, '2025-11-27 08:31:39', 1),
(79, 11, 52, 'created', 'bom', 45878.00, 'cs2', NULL, '2025-11-27 08:31:51', 1),
(145, 6, 64, 'created', 'zeaze', 4754.00, 'custom', NULL, '2025-12-10 12:21:11', 1),
(148, 6, 65, 'created', 'erzear', 44554.00, 'custom', NULL, '2025-12-10 12:22:59', 1),
(149, 6, 65, 'deleted', 'erzear', 44554.00, 'custom', NULL, '2025-12-10 12:24:36', 1),
(150, 6, 64, 'deleted', 'zeaze', 4754.00, 'custom', NULL, '2025-12-10 12:24:45', 1),
(151, 6, 66, 'created', 'zeaze', 455.00, 'custom', NULL, '2025-12-10 12:25:02', 1),
(154, 6, 66, 'deleted', 'zeaze', 455.00, 'custom', NULL, '2025-12-10 12:25:56', 1),
(155, 6, 67, 'created', 'zeaz', 5454.00, 'valorant', NULL, '2025-12-10 12:29:46', 1),
(158, 6, 67, 'deleted', 'zeaz', 5454.00, 'valorant', NULL, '2025-12-10 12:30:25', 1),
(159, 6, 68, 'created', 'fddffdfd', 555.00, 'custom', NULL, '2025-12-10 12:31:00', 1),
(162, 9, 69, 'created', 'rezrzer', 87878.00, 'valorant', NULL, '2025-12-10 12:35:32', 1),
(169, 9, 69, 'deleted', 'rezrzer', 87878.00, 'valorant', NULL, '2025-12-10 16:19:24', 1),
(170, 6, 68, 'updated', 'fddffdfd', 554.00, 'custom', NULL, '2025-12-10 18:01:13', 1),
(171, 6, 68, 'deleted', 'fddffdfd', 554.00, 'custom', NULL, '2025-12-10 18:01:17', 1),
(172, 6, 70, 'created', 'zza', 4754.00, 'custom', NULL, '2025-12-10 18:08:01', 1),
(173, 6, 70, 'deleted', 'zza', 4754.00, 'custom', NULL, '2025-12-10 18:08:09', 1),
(174, 6, 71, 'created', 'jig', 8.00, 'valorant', NULL, '2025-12-10 18:15:23', 1),
(203, 6, 71, 'updated', 'blue dragon', 8.00, 'valorant', NULL, '2025-12-11 08:22:05', 1),
(204, 6, 72, 'created', 'red dragpn', 67.00, 'fortnite', NULL, '2025-12-11 08:24:17', 1),
(205, 17, 73, 'created', 'reaver phanthom', 50.00, 'valorant', NULL, '2025-12-13 15:05:28', 1),
(206, 17, 74, 'created', 'dragon lore', 1200.00, 'cs2', NULL, '2025-12-13 15:05:59', 1),
(207, 17, 74, 'negotiation_refused', 'dragon lore', 1200.00, 'cs2', 'neg_693d83736dd58', '2025-12-13 15:17:07', 1),
(209, 4, 75, 'created', 'test', 500.00, 'custom', NULL, '2025-12-13 15:42:14', 1),
(210, 11, 75, 'trade', 'test', 500.00, 'custom', 'neg_ok_693d89ca3fbe2', '2025-12-13 15:44:10', 1),
(212, 4, 76, 'created', 'Miss', 500.00, 'custom', NULL, '2025-12-13 15:51:18', 1),
(213, 11, 76, 'trade', 'Miss', 500.00, 'custom', 'neg_ok_693d8c4f66355', '2025-12-13 15:54:55', 1),
(215, 4, 77, 'created', 'sovereign', 40.00, 'valorant', NULL, '2025-12-17 12:15:15', 1),
(218, 4, 78, 'created', 'test', 500.00, 'apex', NULL, '2025-12-17 18:09:08', 1),
(220, 4, 78, 'sold', 'test', 500.00, 'apex', 'neg_ok_6942f200ae6ad', '2025-12-17 18:10:08', 1);

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

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `google_id`, `dob`, `password`, `gender`, `role`, `status`, `image`) VALUES
(4, 'Yassine Ben Mustapha', 'dhrifmeriem1231230@gmail.com', NULL, '2005-12-10', '$2y$10$sKVc5L7xU9BC0MJNIGqVUuJDckzrsh4ZPGUfKSLyUWtW8.TwQspUK', 'Female', 'Superadmin', 'active', 'uploads/profiles/profile_6943341cbbbbd.jpg'),
(5, 'Lou', 'lou@gmail.com', NULL, '2005-10-15', '$2y$10$tD4qViTauGoJXlaSVlG72egrt.RX74tUCMi.U8tauQZtECPT8H6Ra', 'Male', 'Gamer', 'active', NULL),
(6, 'SkrrtTn', 'yassinebenmustapha05@gmail.com', '104603800901647082984', '2005-09-17', '$2y$10$TEb29MhesF/CSkw.n/D8u.kmcOD.kGnJqvs/FTXj1rB6fr1LwOI4C', 'Male', 'Admin', 'active', NULL),
(7, 'Fifi', 'ferielayari19@gmail.com', NULL, '2005-07-27', '$2y$10$Bwl8JszmGDaIZudfmvlUTOnlOw/REOqL7pTqsVv2WxyeDdm49/ary', 'Female', 'Gamer', 'active', NULL),
(8, 'kayokin', 'killerbeeftw1@gmail.com', NULL, '2025-11-27', '$2y$10$teF4Kc1GV9Zk1/Jk955bQOd8L2aJCFyMd4LSey0ttsYGXpi6PiBjy', 'Male', 'Gamer', 'active', 'uploads/profiles/profile_6920f9098caad.PNG'),
(9, 'bo9', 'vgsdqsdqsbfvvbvcb@gmail.com', NULL, '2025-11-03', '$2y$10$LNzHa2MQEd3wZuVfg41/TeNKkX0fD8XgqF5z0AZq/1pPW3zNrGUeO', 'Male', 'Admin', 'active', 'uploads/profiles/profile_69280bf20f01f.png'),
(10, 'ezra', 'ezra@gmail.com', NULL, '2025-11-05', '$2y$10$JH4Hqkn5A.U8gCPWvWzrTepgGrh3CRKIHAA7hj9xC0n80qLs5v4Qq', 'Male', 'Gamer', 'active', NULL),
(11, 'zzea', 'refgtreter@nasba.com', NULL, '2025-10-30', '$2y$10$r6mGCzNZ2QOsCBlDtVmpy.XErIUonCx.XB1giR8VoVbixMQ9EAKky', 'Male', 'Gamer', 'active', 'uploads/profiles/profile_6927156ce1af7.png'),
(12, 'ezrzer', 'sybau@gmail.com', NULL, '2025-08-13', '$2y$10$p36DPa13KaOPvkS833sxN.htMrfEeXH5i3QA335.Q5orw9kyrIFu.', 'Female', 'Gamer', 'pending', NULL),
(13, 'jih', 'vgbfvvbddddvcb@gmail.com', NULL, '2025-12-01', '12345678@', 'Male', 'Gamer', 'pending', NULL),
(14, 'sdqd', 'zedqszaea@gmail.com', NULL, '2025-12-01', '$2y$10$5NUn74tyUpppcBLr9pJ.deUgPPZ4jAlG5b/V21ecNpifY0NONARrC', 'Male', 'Gamer', 'pending', NULL),
(15, 'zeaze', 'vgsdqsdqsezbfvvbvcb@gmail.com', NULL, '2025-12-04', '$2y$10$LMJTsyy9YxQFou8CiK8uTuDfuaJrvt73I3LEa5BuPFAOZ3XpZSzp2', 'Male', 'Gamer', 'pending', NULL),
(16, 'bombaklat', 'bombaklat404@gmail.com', '102069229130088650245', '0000-00-00', '$2y$10$Vz.4HLYMj00tyvBBAdIBUOiDmjI7cIH60USjIr7gJnkTmvsNVYJvK', NULL, 'Gamer', 'active', NULL),
(17, 'amalhl', 'hlamal224@gmail.com', '109635839546018261696', '2001-10-15', '$2y$10$Cz/VZUYb9kQMuQiTejJyru824qjgHuV6sxQ2J8iJx53hVWPaKneqq', NULL, 'Gamer', 'active', 'uploads/profiles/profile_693d80e6e0aac.jpg'),
(24, 'misstagada1', 'misstagada1231230@gmail.com', '109610781764475096678', '0000-00-00', '$2y$10$dtWmI2PdzcTWY5J3Ft.8oeGkL40KSIPf6RpzoS83x6EHpTW25Nf7u', NULL, 'Gamer', 'active', NULL),
(25, 'Mayssa', 'claudemimi065@gmail.com', NULL, '2002-01-12', '$2y$10$xsFHW.9FFBVYxtUL8I1DpeilDGNNymRAYoTaccrgq1ek9bAz6zhJC', 'Female', 'Gamer', 'active', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_confidence_scores`
--

CREATE TABLE `user_confidence_scores` (
  `id_score` int(11) NOT NULL,
  `email` varchar(150) NOT NULL,
  `nombre_avis` int(11) DEFAULT 0,
  `likes_recus` int(11) DEFAULT 0,
  `taux_transparence` decimal(5,2) DEFAULT 0.00,
  `score_total` decimal(10,2) DEFAULT 0.00,
  `date_mise_a_jour` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- Indexes for table `article_history`
--
ALTER TABLE `article_history`
  ADD PRIMARY KEY (`id_history`),
  ADD KEY `idx_history_article` (`idArticle`),
  ADD KEY `idx_history_edited_by` (`edited_by`),
  ADD KEY `idx_history_edited_at` (`edited_at`),
  ADD KEY `fk_article_history_categorie` (`idCategorie`);

--
-- Indexes for table `categorie`
--
ALTER TABLE `categorie`
  ADD PRIMARY KEY (`idCategorie`),
  ADD KEY `fk_categorie_created_by` (`created_by`);

--
-- Indexes for table `charity_votes`
--
ALTER TABLE `charity_votes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_user` (`user_id`);

--
-- Indexes for table `chatbot_conversations`
--
ALTER TABLE `chatbot_conversations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`users_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `comment`
--
ALTER TABLE `comment`
  ADD PRIMARY KEY (`id_comment`),
  ADD KEY `idx_evenement` (`id_evenement`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_user_email` (`user_email`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_rating` (`rating`),
  ADD KEY `idx_reported` (`is_reported`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`idComment`),
  ADD KEY `comments_ibfk_1` (`article_id`),
  ADD KEY `fk_comments_users` (`user_id`);

--
-- Indexes for table `comment_interaction`
--
ALTER TABLE `comment_interaction`
  ADD PRIMARY KEY (`id_interaction`),
  ADD UNIQUE KEY `unique_user_comment` (`id_comment`,`user_email`),
  ADD KEY `idx_comment` (`id_comment`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_user_email` (`user_email`);

--
-- Indexes for table `coupons`
--
ALTER TABLE `coupons`
  ADD PRIMARY KEY (`coupon_id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `publisher_id` (`publisher_id`),
  ADD KEY `idx_code` (`code`),
  ADD KEY `idx_expires` (`expires_at`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `coupon_usage`
--
ALTER TABLE `coupon_usage`
  ADD PRIMARY KEY (`usage_id`),
  ADD UNIQUE KEY `unique_user_coupon` (`coupon_id`,`user_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_coupon` (`coupon_id`);

--
-- Indexes for table `email_verifications`
--
ALTER TABLE `email_verifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `evenement`
--
ALTER TABLE `evenement`
  ADD PRIMARY KEY (`id_evenement`),
  ADD KEY `idx_statut` (`statut`),
  ADD KEY `idx_date_debut` (`date_debut`),
  ADD KEY `idx_createur_id` (`createur_id`),
  ADD KEY `idx_createur_email` (`createur_email`);

--
-- Indexes for table `event_statistics`
--
ALTER TABLE `event_statistics`
  ADD PRIMARY KEY (`id_statistic`),
  ADD KEY `idx_evenement` (`id_evenement`),
  ADD KEY `idx_status` (`event_status`),
  ADD KEY `idx_rating` (`average_rating`),
  ADD KEY `idx_creator_id` (`creator_id`);

--
-- Indexes for table `participation`
--
ALTER TABLE `participation`
  ADD PRIMARY KEY (`id_participation`),
  ADD UNIQUE KEY `unique_participation` (`id_evenement`,`email_participant`),
  ADD KEY `idx_evenement` (`id_evenement`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_email_participant` (`email_participant`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `produit`
--
ALTER TABLE `produit`
  ADD PRIMARY KEY (`produit_id`),
  ADD KEY `idx_produit_publisher` (`publisher_id`);

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
-- Indexes for table `reclamations`
--
ALTER TABLE `reclamations`
  ADD PRIMARY KEY (`id_reclamation`),
  ADD KEY `idx_id_utilisateur` (`id_utilisateur`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_statut` (`statut`),
  ADD KEY `idx_date_creation` (`date_creation`),
  ADD KEY `idx_categorie` (`categorie`),
  ADD KEY `idx_email_reclamation` (`email`);

--
-- Indexes for table `reponses`
--
ALTER TABLE `reponses`
  ADD PRIMARY KEY (`id_reponse`),
  ADD KEY `idx_id_reclamation` (`id_reclamation`),
  ADD KEY `idx_id_admin` (`id_admin`),
  ADD KEY `idx_date_reponse` (`date_reponse`);

--
-- Indexes for table `satisfactions`
--
ALTER TABLE `satisfactions`
  ADD PRIMARY KEY (`id_satisfaction`),
  ADD UNIQUE KEY `unique_reclamation_email` (`id_reclamation`,`email`),
  ADD KEY `idx_id_reclamation` (`id_reclamation`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_rating` (`rating`),
  ADD KEY `idx_date_evaluation` (`date_evaluation`),
  ADD KEY `fk_satisfactions_user` (`id_user`),
  ADD KEY `idx_email_satisfaction` (`email`);

--
-- Indexes for table `skins`
--
ALTER TABLE `skins`
  ADD PRIMARY KEY (`skin_id`),
  ADD KEY `idx_skins_owner` (`owner_id`);

--
-- Indexes for table `subscribers`
--
ALTER TABLE `subscribers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email_category` (`email`,`category_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `fk_subscribers_userid` (`user_id`);

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
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_user_id` (`user_id`);

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
  ADD KEY `idx_conversation_created` (`created_at`),
  ADD KEY `idx_negotiation_id` (`negotiation_id`);

--
-- Indexes for table `trade_history`
--
ALTER TABLE `trade_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_visible_created` (`visible_in_trading`,`created_at`),
  ADD KEY `idx_user_visible` (`user_id`,`visible_in_trading`),
  ADD KEY `idx_negotiation_id` (`negotiation_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `google_id` (`google_id`),
  ADD KEY `idx_user_email` (`email`),
  ADD KEY `idx_user_username` (`username`),
  ADD KEY `idx_google_id` (`google_id`);

--
-- Indexes for table `user_confidence_scores`
--
ALTER TABLE `user_confidence_scores`
  ADD PRIMARY KEY (`id_score`),
  ADD UNIQUE KEY `unique_email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_score_total` (`score_total`),
  ADD KEY `idx_date_mise_a_jour` (`date_mise_a_jour`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `article`
--
ALTER TABLE `article`
  MODIFY `idArticle` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT for table `article_history`
--
ALTER TABLE `article_history`
  MODIFY `id_history` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `categorie`
--
ALTER TABLE `categorie`
  MODIFY `idCategorie` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `charity_votes`
--
ALTER TABLE `charity_votes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `chatbot_conversations`
--
ALTER TABLE `chatbot_conversations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `comment`
--
ALTER TABLE `comment`
  MODIFY `id_comment` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `idComment` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `comment_interaction`
--
ALTER TABLE `comment_interaction`
  MODIFY `id_interaction` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `coupons`
--
ALTER TABLE `coupons`
  MODIFY `coupon_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `coupon_usage`
--
ALTER TABLE `coupon_usage`
  MODIFY `usage_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `email_verifications`
--
ALTER TABLE `email_verifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `evenement`
--
ALTER TABLE `evenement`
  MODIFY `id_evenement` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `event_statistics`
--
ALTER TABLE `event_statistics`
  MODIFY `id_statistic` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `participation`
--
ALTER TABLE `participation`
  MODIFY `id_participation` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `produit`
--
ALTER TABLE `produit`
  MODIFY `produit_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `purchase`
--
ALTER TABLE `purchase`
  MODIFY `purchase_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `reclamations`
--
ALTER TABLE `reclamations`
  MODIFY `id_reclamation` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `reponses`
--
ALTER TABLE `reponses`
  MODIFY `id_reponse` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `satisfactions`
--
ALTER TABLE `satisfactions`
  MODIFY `id_satisfaction` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `skins`
--
ALTER TABLE `skins`
  MODIFY `skin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

--
-- AUTO_INCREMENT for table `subscribers`
--
ALTER TABLE `subscribers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `tickets`
--
ALTER TABLE `tickets`
  MODIFY `id_ticket` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `trade`
--
ALTER TABLE `trade`
  MODIFY `trade_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `trade_conversations`
--
ALTER TABLE `trade_conversations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=124;

--
-- AUTO_INCREMENT for table `trade_history`
--
ALTER TABLE `trade_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=221;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `user_confidence_scores`
--
ALTER TABLE `user_confidence_scores`
  MODIFY `id_score` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `article`
--
ALTER TABLE `article`
  ADD CONSTRAINT `fk_article_categorie` FOREIGN KEY (`idCategorie`) REFERENCES `categorie` (`idCategorie`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_article_publisher` FOREIGN KEY (`id_pub`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `article_history`
--
ALTER TABLE `article_history`
  ADD CONSTRAINT `article_history_ibfk_2` FOREIGN KEY (`edited_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_article_history_article` FOREIGN KEY (`idArticle`) REFERENCES `article` (`idArticle`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_article_history_categorie` FOREIGN KEY (`idCategorie`) REFERENCES `categorie` (`idCategorie`) ON DELETE SET NULL;

--
-- Constraints for table `categorie`
--
ALTER TABLE `categorie`
  ADD CONSTRAINT `fk_categorie_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_categorie_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `chatbot_conversations`
--
ALTER TABLE `chatbot_conversations`
  ADD CONSTRAINT `chatbot_conversations_ibfk_1` FOREIGN KEY (`users_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `comment`
--
ALTER TABLE `comment`
  ADD CONSTRAINT `fk_comment_evenement` FOREIGN KEY (`id_evenement`) REFERENCES `evenement` (`id_evenement`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_comment_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `article` (`idArticle`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_comments_article` FOREIGN KEY (`article_id`) REFERENCES `article` (`idArticle`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_comments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_comments_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `comment_interaction`
--
ALTER TABLE `comment_interaction`
  ADD CONSTRAINT `fk_comment_interaction_comment` FOREIGN KEY (`id_comment`) REFERENCES `comment` (`id_comment`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_comment_interaction_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_interaction_comment` FOREIGN KEY (`id_comment`) REFERENCES `comment` (`id_comment`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_interaction_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `coupons`
--
ALTER TABLE `coupons`
  ADD CONSTRAINT `coupons_ibfk_1` FOREIGN KEY (`publisher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `coupon_usage`
--
ALTER TABLE `coupon_usage`
  ADD CONSTRAINT `coupon_usage_ibfk_1` FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`coupon_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `coupon_usage_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `email_verifications`
--
ALTER TABLE `email_verifications`
  ADD CONSTRAINT `email_verifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `evenement`
--
ALTER TABLE `evenement`
  ADD CONSTRAINT `fk_evenement_createur` FOREIGN KEY (`createur_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `event_statistics`
--
ALTER TABLE `event_statistics`
  ADD CONSTRAINT `fk_event_statistics_createur` FOREIGN KEY (`creator_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_event_statistics_evenement` FOREIGN KEY (`id_evenement`) REFERENCES `evenement` (`id_evenement`) ON DELETE CASCADE;

--
-- Constraints for table `participation`
--
ALTER TABLE `participation`
  ADD CONSTRAINT `fk_participation_evenement` FOREIGN KEY (`id_evenement`) REFERENCES `evenement` (`id_evenement`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_participation_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `produit`
--
ALTER TABLE `produit`
  ADD CONSTRAINT `fk_produit_publisher` FOREIGN KEY (`publisher_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `purchase`
--
ALTER TABLE `purchase`
  ADD CONSTRAINT `purchase_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_ibfk_2` FOREIGN KEY (`produit_id`) REFERENCES `produit` (`produit_id`) ON DELETE CASCADE;

--
-- Constraints for table `reclamations`
--
ALTER TABLE `reclamations`
  ADD CONSTRAINT `fk_reclamations_user` FOREIGN KEY (`id_utilisateur`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `reponses`
--
ALTER TABLE `reponses`
  ADD CONSTRAINT `fk_reponses_admin` FOREIGN KEY (`id_admin`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_reponses_reclamation` FOREIGN KEY (`id_reclamation`) REFERENCES `reclamations` (`id_reclamation`) ON DELETE CASCADE;

--
-- Constraints for table `satisfactions`
--
ALTER TABLE `satisfactions`
  ADD CONSTRAINT `fk_satisfactions_reclamation` FOREIGN KEY (`id_reclamation`) REFERENCES `reclamations` (`id_reclamation`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_satisfactions_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `skins`
--
ALTER TABLE `skins`
  ADD CONSTRAINT `skins_ibfk_1` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subscribers`
--
ALTER TABLE `subscribers`
  ADD CONSTRAINT `fk_subscribers_categorie` FOREIGN KEY (`category_id`) REFERENCES `categorie` (`idCategorie`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_subscribers_userid` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `tickets`
--
ALTER TABLE `tickets`
  ADD CONSTRAINT `fk_tickets_evenement` FOREIGN KEY (`id_evenement`) REFERENCES `evenement` (`id_evenement`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_tickets_participation` FOREIGN KEY (`id_participation`) REFERENCES `participation` (`id_participation`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_tickets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `trade`
--
ALTER TABLE `trade`
  ADD CONSTRAINT `trade_ibfk_1` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `trade_ibfk_3` FOREIGN KEY (`skin_id`) REFERENCES `skins` (`skin_id`) ON DELETE CASCADE;

--
-- Constraints for table `trade_conversations`
--
ALTER TABLE `trade_conversations`
  ADD CONSTRAINT `trade_conversations_ibfk_1` FOREIGN KEY (`skin_id`) REFERENCES `skins` (`skin_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `trade_conversations_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `trade_history`
--
ALTER TABLE `trade_history`
  ADD CONSTRAINT `trade_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_confidence_scores`
--
ALTER TABLE `user_confidence_scores`
  ADD CONSTRAINT `fk_user_confidence_scores_user` FOREIGN KEY (`email`) REFERENCES `users` (`email`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_user_confidence_user` FOREIGN KEY (`email`) REFERENCES `users` (`email`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
