-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Dec 05, 2025 at 10:27 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

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
(5, 14, 'f81e2675152270e525afc556a12c88f2e3c35ceaa6d77410e1af0bb28bcce40c', '2025-12-02 18:50:59', '2025-12-02 17:47:59');

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
(4, 9, 'dfdsfsd', 69.00, '2025-11-17 22:34:50', NULL, 'sdf', 'sdfds', 0),
(29, 9, 'rtre', 7457.00, '2025-11-19 21:18:26', 'images/skins/skin_691e342208329.png', 'dfdzes', 'custom', 0),
(30, 9, 'erz', 454.00, '2025-11-21 23:44:28', 'images/skins/skin_6920f95c6f491.png', 'erze', 'custom', 0),
(31, 9, 'erfze', 4545.00, '2025-11-22 00:14:17', 'images/skins/skin_69210059a1a5b.png', 'erz', 'cs2', 0),
(32, 11, 'ddfsdf', 5785.00, '2025-11-26 15:13:31', 'images/skins/skin_6927191b87692.png', 'redrt', 'custom', 0),
(33, 9, 'sqdqs', 568.00, '2025-11-26 15:16:56', 'images/skins/skin_692719e8a6bf3.png', 'ezdzsd', 'custom', 0),
(34, 9, 'zae', 45.00, '2025-11-26 15:25:38', 'images/skins/skin_69271bf26f637.png', 'rzfezr', 'custom', 0),
(35, 9, 'zaeaze', 4144.00, '2025-11-26 15:25:44', 'images/skins/skin_69271bf89b2a7.png', 'zsdezaedza', 'custom', 0),
(36, 9, 'zaeazea', 5455.00, '2025-11-26 15:25:50', 'images/skins/skin_69271bfe63562.png', '\"azaz', 'custom', 0),
(37, 9, 'edsrzaer', 5454.00, '2025-11-26 17:22:49', 'images/skins/skin_6927376917ba5.png', 'dfsd', 'custom', 0),
(38, 9, 'zerzerze', 45475.00, '2025-11-26 17:22:59', 'images/skins/skin_692737737ee9b.png', 'dssqd', 'custom', 0),
(39, 9, 'sqdqsd', 1454.00, '2025-11-26 17:23:06', 'images/skins/skin_6927377a383da.png', 'erdzeza', 'custom', 0),
(41, 9, 'zaazeaz', 585.00, '2025-11-26 19:59:52', 'images/skins/skin_69275c387b506.png', 'zeaze', 'custom', 0),
(42, 9, 'ezrzer7785', 787.00, '2025-11-26 20:00:00', 'images/skins/skin_69275c4059e95.png', 'erezr', 'custom', 0),
(43, 9, 'zeazea', 45874.00, '2025-11-26 20:00:08', 'images/skins/skin_69275c4886126.png', 'ezrzre', 'custom', 0),
(44, 9, 'zeza', 47787.00, '2025-11-26 20:27:38', 'images/skins/skin_692762ba13be3.png', 'zaeaze', 'cs2', 0),
(45, 9, 'zeaze', 45452.00, '2025-11-26 20:27:49', 'images/skins/skin_692762c56dced.png', 'sdqsd', 'custom', 0),
(46, 9, 'srzerez', 4568.00, '2025-11-26 20:44:30', 'images/skins/skin_692766ae82661.png', 'erzer', 'custom', 0),
(47, 9, 'dssqdqsd', 45855.00, '2025-11-26 20:44:36', 'images/skins/skin_692766b4e253e.png', 'frzerze', 'custom', 0),
(48, 9, 'zerezrzer', 57868.00, '2025-11-26 20:44:49', 'images/skins/skin_692766c110dc6.png', 'ezrzaza', 'custom', 0),
(49, 9, 'blue dragon', 4564.00, '2025-11-27 08:31:09', 'images/skins/skin_69280c4db3290.png', 'zezae', 'cs2', 0),
(50, 9, 'red dragon', 5454.00, '2025-11-27 08:31:26', 'images/skins/skin_69280c5e0fd34.png', 'terrt', 'custom', 0),
(51, 9, 'oui', 4566.00, '2025-11-27 08:31:39', 'images/skins/skin_69280c6bdf3f4.png', 'zeazezae', 'fortnite', 0),
(52, 9, 'bom', 45878.00, '2025-11-27 08:31:51', 'images/skins/skin_69280c771e919.png', 'zeazea', 'cs2', 0),
(55, 9, 'hbibi', 211000.00, '2025-12-02 14:41:22', 'images/skins/skin_692efa924e035.png', 'rzaeeaz', 'fortnite', 1),
(62, 6, 'zerza', 4564.00, '2025-12-05 16:57:42', 'images/skins/skin_69330f061731c.jpg', 'ezrzer', 'cs2', 1);

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
(16, 9, 11, 49, '2025-11-30 02:00:46', 'buy'),
(17, 9, 11, 50, '2025-11-30 02:00:46', 'buy');

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
  `is_deleted` tinyint(1) DEFAULT 0,
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `trade_conversations`
--

INSERT INTO `trade_conversations` (`id`, `skin_id`, `sender_id`, `receiver_id`, `message`, `image_path`, `is_deleted`, `updated_at`, `created_at`) VALUES
(1, 4, 6, 7, 'sqdsq', NULL, 0, NULL, '2025-11-17 22:38:55'),
(2, 4, 6, 7, 'sqdqs', NULL, 0, NULL, '2025-11-17 22:39:13'),
(3, 4, 6, 7, 'ssss', NULL, 0, NULL, '2025-11-17 22:39:15'),
(4, 4, 6, 7, 'dsqdq', NULL, 0, NULL, '2025-11-17 22:47:24'),
(5, 4, 6, 7, 'dddd', NULL, 0, NULL, '2025-11-17 22:47:44'),
(6, 4, 6, 7, 'sqdqs', NULL, 0, NULL, '2025-11-17 23:00:18'),
(8, 4, 6, 7, 'dsdsqds', NULL, 0, NULL, '2025-11-18 20:49:02'),
(9, 4, 6, 7, 'sdqd', NULL, 0, NULL, '2025-11-19 14:56:01'),
(10, 4, 6, 7, 'zeaze', NULL, 0, NULL, '2025-11-19 14:56:14'),
(11, 29, 8, 6, 'erzr', NULL, 0, NULL, '2025-11-21 23:44:33'),
(12, 4, 9, 7, '\"\'rzerez', NULL, 0, NULL, '2025-11-22 01:23:18'),
(13, 30, 9, 8, 'sdq', NULL, 0, NULL, '2025-11-22 01:24:40'),
(14, 29, 9, 6, 'sqdqsdqd', NULL, 0, NULL, '2025-11-22 01:25:20'),
(15, 29, 9, 6, 'zseaze', NULL, 0, NULL, '2025-11-22 01:25:21'),
(16, 29, 9, 6, 'zeazeaze', NULL, 0, NULL, '2025-11-22 01:25:23'),
(17, 30, 9, 8, 'sq', NULL, 0, NULL, '2025-11-22 01:26:50'),
(18, 30, 9, 8, 'qsqs', NULL, 0, NULL, '2025-11-22 01:27:52'),
(19, 29, 9, 6, 'qsqsq', NULL, 0, NULL, '2025-11-22 01:27:58'),
(20, 4, 9, 7, 'sd', NULL, 0, NULL, '2025-11-26 13:56:09'),
(33, 55, 11, 9, 'hey', NULL, 0, NULL, '2025-12-02 14:51:57'),
(34, 55, 9, 11, 'ds', NULL, 0, NULL, '2025-12-02 15:00:20'),
(35, 55, 9, 11, 'sup', NULL, 0, NULL, '2025-12-02 15:00:30'),
(42, 55, 11, 9, '', 'uploads/messages/msg_692f09095d8f51.31954123.png', 0, NULL, '2025-12-02 15:43:05'),
(44, 55, 9, 11, '', 'uploads/messages/msg_692f1eeb109a54.91643293.png', 0, NULL, '2025-12-02 17:16:27'),
(46, 55, 9, 11, 'yes', NULL, 0, NULL, '2025-12-05 12:54:56'),
(47, 55, 6, 9, 'dsfsdfsf', NULL, 0, NULL, '2025-12-05 16:57:48'),
(48, 55, 6, 9, '4@', NULL, 0, NULL, '2025-12-05 19:21:08'),
(49, 55, 6, 9, '', 'uploads/messages/msg_693330c0ea5927.07339786.jpg', 0, NULL, '2025-12-05 19:21:36'),
(50, 62, 9, 6, 'rretert', NULL, 0, NULL, '2025-12-05 20:29:54');

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
(42, 9, 33, 'bought', 'sqdqs', 568.00, 'custom', '2025-11-26 15:19:53', 1),
(43, 11, 34, 'created', 'zae', 45.00, 'custom', '2025-11-26 15:25:38', 1),
(44, 11, 35, 'created', 'zaeaze', 4144.00, 'custom', '2025-11-26 15:25:44', 1),
(45, 11, 36, 'created', 'zaeazea', 5455.00, 'custom', '2025-11-26 15:25:50', 1),
(46, 9, 35, 'bought', 'zaeaze', 4144.00, 'custom', '2025-11-26 15:26:07', 1),
(47, 9, 36, 'bought', 'zaeazea', 5455.00, 'custom', '2025-11-26 15:26:38', 1),
(48, 9, 34, 'bought', 'zae', 45.00, 'custom', '2025-11-26 15:30:47', 1),
(49, 11, 37, 'created', 'edsrzaer', 5454.00, 'custom', '2025-11-26 17:22:49', 1),
(50, 11, 38, 'created', 'zerzerze', 45475.00, 'custom', '2025-11-26 17:22:59', 1),
(51, 11, 39, 'created', 'sqdqsd', 1454.00, 'custom', '2025-11-26 17:23:06', 1),
(52, 9, 38, 'bought', 'zerzerze', 45475.00, 'custom', '2025-11-26 17:48:25', 1),
(53, 9, 39, 'bought', 'sqdqsd', 1454.00, 'custom', '2025-11-26 17:49:30', 1),
(54, 9, 40, 'created', 'sqdqsd', 777.00, 'fortnite', '2025-11-26 18:17:09', 1),
(55, 9, 40, 'updated', 'sqdqsdze', 777.00, 'fortnite', '2025-11-26 18:19:51', 1),
(56, 9, 37, 'bought', 'edsrzaer', 5454.00, 'custom', '2025-11-26 19:50:27', 1),
(57, 11, 41, 'created', 'zaazeaz', 585.00, 'custom', '2025-11-26 19:59:52', 1),
(58, 11, 42, 'created', 'ezrzer7785', 787.00, 'custom', '2025-11-26 20:00:00', 1),
(59, 11, 43, 'created', 'zeazea', 45874.00, 'custom', '2025-11-26 20:00:08', 1),
(60, 9, 42, 'bought', 'ezrzer7785', 787.00, 'custom', '2025-11-26 20:00:38', 1),
(61, 9, 41, 'bought', 'zaazeaz', 585.00, 'custom', '2025-11-26 20:05:04', 1),
(62, 9, 43, 'bought', 'zeazea', 45874.00, 'custom', '2025-11-26 20:14:28', 1),
(63, 11, 44, 'created', 'zeza', 47787.00, 'cs2', '2025-11-26 20:27:38', 1),
(64, 11, 45, 'created', 'zeaze', 45452.00, 'custom', '2025-11-26 20:27:49', 1),
(65, 9, 44, 'bought', 'zeza', 47787.00, 'cs2', '2025-11-26 20:28:08', 1),
(66, 9, 45, 'bought', 'zeaze', 45452.00, 'custom', '2025-11-26 20:36:50', 1),
(67, 9, 4, 'bought', 'dfdsfsd', 69.00, 'sdfds', '2025-11-26 20:38:52', 1),
(68, 11, 46, 'created', 'srzerez', 4568.00, 'custom', '2025-11-26 20:44:30', 1),
(69, 11, 47, 'created', 'dssqdqsd', 45855.00, 'custom', '2025-11-26 20:44:36', 1),
(70, 11, 48, 'created', 'zerezrzer', 57868.00, 'custom', '2025-11-26 20:44:49', 1),
(71, 9, 47, 'bought', 'dssqdqsd', 45855.00, 'custom', '2025-11-26 20:45:07', 1),
(72, 9, 46, 'bought', 'srzerez', 4568.00, 'custom', '2025-11-26 20:45:36', 1),
(73, 9, 48, 'bought', 'zerezrzer', 57868.00, 'custom', '2025-11-26 20:50:38', 1),
(76, 11, 49, 'created', 'blue dragon', 4564.00, 'cs2', '2025-11-27 08:31:09', 1),
(77, 11, 50, 'created', 'red dragon', 5454.00, 'custom', '2025-11-27 08:31:26', 1),
(78, 11, 51, 'created', 'oui', 4566.00, 'fortnite', '2025-11-27 08:31:39', 1),
(79, 11, 52, 'created', 'bom', 45878.00, 'cs2', '2025-11-27 08:31:51', 1),
(80, 9, 51, 'bought', 'oui', 4566.00, 'fortnite', '2025-11-27 09:06:24', 1),
(81, 9, 52, 'bought', 'bom', 45878.00, 'cs2', '2025-11-27 09:10:47', 1),
(82, 9, 49, 'bought', 'blue dragon', 4564.00, 'cs2', '2025-11-30 01:00:46', 1),
(83, 9, 50, 'bought', 'red dragon', 5454.00, 'custom', '2025-11-30 01:00:46', 1),
(84, 9, 53, 'created', 'szezae', 545.00, 'cs2', '2025-12-02 13:44:18', 1),
(85, 9, 54, 'created', 'zaeaz', 47.00, 'custom', '2025-12-02 14:20:57', 1),
(86, 9, 54, 'deleted', 'zaeaz', 47.00, 'custom', '2025-12-02 14:40:59', 1),
(87, 9, 53, 'deleted', 'szezae', 545.00, 'cs2', '2025-12-02 14:41:04', 1),
(88, 9, 40, 'deleted', 'sqdqsdze', 777.00, 'fortnite', '2025-12-02 14:41:09', 1),
(89, 9, 55, 'created', 'jig', 567878.00, 'cs2', '2025-12-02 14:41:22', 1),
(90, 9, 56, 'created', 'bo7', 465563.00, 'custom', '2025-12-02 14:41:31', 1),
(91, 9, 56, 'deleted', 'bo7', 465563.00, 'custom', '2025-12-04 00:03:14', 1),
(92, 9, 57, 'created', 'erze', 56.00, 'custom', '2025-12-05 12:51:38', 1),
(93, 9, 57, 'deleted', 'erze', 56.00, 'custom', '2025-12-05 12:51:48', 1),
(94, 9, 58, 'created', 'retdert', 5268.00, 'custom', '2025-12-05 12:59:06', 1),
(95, 9, 59, 'created', 'ezrzerzer', 74575.00, 'custom', '2025-12-05 13:04:23', 1),
(96, 6, 60, 'created', 'ezrzre', 4757.00, 'custom', '2025-12-05 13:05:10', 1),
(97, 6, 61, 'created', 'reztze', 777.00, 'custom', '2025-12-05 13:23:25', 1),
(98, 9, 58, 'deleted', 'retdert', 5268.00, 'custom', '2025-12-05 16:56:48', 1),
(99, 9, 59, 'deleted', 'ezrzerzer', 74575.00, 'custom', '2025-12-05 16:56:54', 1),
(100, 6, 61, 'deleted', 'reztze', 777.00, 'custom', '2025-12-05 16:57:12', 1),
(101, 6, 60, 'deleted', 'ezrzre', 4757.00, 'custom', '2025-12-05 16:57:15', 1),
(102, 6, 62, 'created', 'zerza', 4564.00, 'cs2', '2025-12-05 16:57:42', 1),
(103, 9, 55, 'updated', 'jig', 567878.00, 'valorant', '2025-12-05 17:52:47', 1),
(104, 9, 55, 'updated', 'jig', 567878.00, 'fortnite', '2025-12-05 18:01:59', 1),
(105, 9, 55, 'updated', 'jig', 5678781.00, 'fortnite', '2025-12-05 18:12:46', 1),
(106, 9, 55, 'updated', 'jig', 56787811.00, 'fortnite', '2025-12-05 18:12:49', 1),
(107, 9, 55, 'updated', 'jig', 99999999.99, 'fortnite', '2025-12-05 18:12:52', 1),
(108, 9, 55, 'updated', 'jig', 2.00, 'fortnite', '2025-12-05 18:13:22', 1),
(109, 9, 55, 'updated', 'jig', 21.00, 'fortnite', '2025-12-05 18:13:25', 1),
(110, 9, 55, 'updated', 'jig', 211.00, 'fortnite', '2025-12-05 18:13:27', 1),
(111, 9, 55, 'updated', 'hbibi', 211.00, 'fortnite', '2025-12-05 19:24:34', 1),
(112, 9, 55, 'updated', 'hbibi', 211000.00, 'fortnite', '2025-12-05 20:30:06', 1);

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
(4, 'MissTagada', 'dhrifmeriem1231230@gmail.com', NULL, '2005-12-10', '$2y$10$sKVc5L7xU9BC0MJNIGqVUuJDckzrsh4ZPGUfKSLyUWtW8.TwQspUK', 'Female', 'Admin', 'active', 'uploads/profiles/profile_691a176eacc73.jpg'),
(5, 'Lou', 'lou@gmail.com', NULL, '2005-10-15', '$2y$10$tD4qViTauGoJXlaSVlG72egrt.RX74tUCMi.U8tauQZtECPT8H6Ra', 'Male', 'Gamer', 'active', NULL),
(6, 'SkrrtTn', 'yassinebenmustapha05@gmail.com', '104603800901647082984', '2005-09-17', '$2y$10$TEb29MhesF/CSkw.n/D8u.kmcOD.kGnJqvs/FTXj1rB6fr1LwOI4C', 'Male', 'Admin', 'active', NULL),
(7, 'Fifi', 'ferielayari19@gmail.com', NULL, '2005-07-27', '$2y$10$Bwl8JszmGDaIZudfmvlUTOnlOw/REOqL7pTqsVv2WxyeDdm49/ary', 'Female', 'Gamer', 'active', NULL),
(8, 'kayokin', 'killerbeeftw1@gmail.com', NULL, '2025-11-27', '$2y$10$teF4Kc1GV9Zk1/Jk955bQOd8L2aJCFyMd4LSey0ttsYGXpi6PiBjy', 'Male', 'Gamer', 'active', 'uploads/profiles/profile_6920f9098caad.PNG'),
(9, 'bo9', 'vgsdqsdqsbfvvbvcb@gmail.com', NULL, '2025-11-03', '$2y$10$LNzHa2MQEd3wZuVfg41/TeNKkX0fD8XgqF5z0AZq/1pPW3zNrGUeO', 'Male', 'Admin', 'active', 'uploads/profiles/profile_69280bf20f01f.png'),
(10, 'ezra', 'ezra@gmail.com', NULL, '2025-11-05', '$2y$10$JH4Hqkn5A.U8gCPWvWzrTepgGrh3CRKIHAA7hj9xC0n80qLs5v4Qq', 'Male', 'Gamer', 'active', NULL),
(11, 'zzea', 'refgtreter@nasba.com', NULL, '2025-10-30', '$2y$10$r6mGCzNZ2QOsCBlDtVmpy.XErIUonCx.XB1giR8VoVbixMQ9EAKky', 'Male', 'Gamer', 'active', 'uploads/profiles/profile_6927156ce1af7.png'),
(12, 'ezrzer', 'sybau@gmail.com', NULL, '2025-08-13', '$2y$10$p36DPa13KaOPvkS833sxN.htMrfEeXH5i3QA335.Q5orw9kyrIFu.', 'Female', 'Gamer', 'pending', NULL),
(13, 'jih', 'vgbfvvbddddvcb@gmail.com', NULL, '2025-12-01', '12345678@', 'Male', 'Gamer', 'pending', NULL),
(14, 'sdqd', 'zedqszaea@gmail.com', NULL, '2025-12-01', '$2y$10$5NUn74tyUpppcBLr9pJ.deUgPPZ4jAlG5b/V21ecNpifY0NONARrC', 'Male', 'Gamer', 'pending', NULL);

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
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_visible_created` (`visible_in_trading`,`created_at`),
  ADD KEY `idx_user_visible` (`user_id`,`visible_in_trading`);

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
-- AUTO_INCREMENT for table `email_verifications`
--
ALTER TABLE `email_verifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

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
  MODIFY `skin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT for table `trade`
--
ALTER TABLE `trade`
  MODIFY `trade_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `trade_conversations`
--
ALTER TABLE `trade_conversations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `trade_history`
--
ALTER TABLE `trade_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=113;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

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
-- Constraints for table `email_verifications`
--
ALTER TABLE `email_verifications`
  ADD CONSTRAINT `email_verifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

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

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
