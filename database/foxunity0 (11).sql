-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : mer. 10 déc. 2025 à 15:48
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
-- Base de données : `foxunity0`
--

-- --------------------------------------------------------

--
-- Structure de la table `article`
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
-- Structure de la table `categorie`
--

CREATE TABLE `categorie` (
  `idCategorie` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `categorie`
--

INSERT INTO `categorie` (`idCategorie`, `nom`, `description`, `created_at`) VALUES
(1, 'Gaming News', 'Latest news in the gaming industry', '2025-11-16 18:12:41'),
(2, 'eSports', 'Competitive gaming updates', '2025-11-16 18:12:41'),
(3, 'Reviews', 'Game reviews and ratings', '2025-11-16 18:12:41');

-- --------------------------------------------------------

--
-- Structure de la table `email_verifications`
--

CREATE TABLE `email_verifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `email_verifications`
--

INSERT INTO `email_verifications` (`id`, `user_id`, `token`, `expires_at`, `created_at`) VALUES
(1, 12, '1fd5637ac14ccc2dde21326467d93149fce83d3ff786480c71b69d32b2e8efe9', '2025-12-02 16:48:20', '2025-12-02 15:45:20'),
(2, 13, '50e18756a2d27c8b36e94a34daf6e4c6907369f9ca7b5fa0a717279fc80bb3f7', '2025-12-02 16:49:35', '2025-12-02 15:46:35'),
(5, 14, 'f81e2675152270e525afc556a12c88f2e3c35ceaa6d77410e1af0bb28bcce40c', '2025-12-02 18:50:59', '2025-12-02 17:47:59'),
(6, 15, '73b4a40f57694fd35f64b678fcb38e364b0d7e0befd97c3de33a090672c9354a', '2025-12-10 12:38:38', '2025-12-10 11:35:38');

-- --------------------------------------------------------

--
-- Structure de la table `evenement`
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
-- Structure de la table `participation`
--

CREATE TABLE `participation` (
  `id_participation` int(11) NOT NULL,
  `id_evenement` int(11) NOT NULL,
  `id_gamer` int(11) NOT NULL,
  `date_participation` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `password_resets`
--

INSERT INTO `password_resets` (`id`, `user_id`, `token`, `expires_at`, `created_at`) VALUES
(32, 6, '09291a999da264e21041824e117b5d423466b01483b8edb2a9c1a4f307bc1d2f', '2025-12-05 21:34:03', '2025-12-05 20:31:03');

-- --------------------------------------------------------

--
-- Structure de la table `produit`
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
-- Déchargement des données de la table `produit`
--

INSERT INTO `produit` (`produit_id`, `name`, `description`, `price`, `stock`, `category`, `brand`, `created_at`, `updated_at`) VALUES
(1, 'Gaming Mouse', 'High-precision gaming mouse', 49.99, 100, 'Peripherals', 'Razer', '2025-11-16 18:12:41', '2025-11-16 18:12:41'),
(2, 'Mechanical Keyboard', 'RGB mechanical keyboard', 129.99, 50, 'Peripherals', 'Corsair', '2025-11-16 18:12:41', '2025-11-16 18:12:41'),
(3, 'Gaming Headset', 'Surround sound headset', 79.99, 75, 'Audio', 'HyperX', '2025-11-16 18:12:41', '2025-11-16 18:12:41');

-- --------------------------------------------------------

--
-- Structure de la table `purchase`
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
-- Structure de la table `reclamation`
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
-- Structure de la table `reponse`
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
-- Structure de la table `skins`
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
-- Déchargement des données de la table `skins`
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
(49, 9, 'blue dragon', 4564.00, '2025-11-27 08:31:09', 'images/skins/skin_69280c4db3290.png', 'zezae', 'cs2', 0, 0),
(50, 9, 'red dragon', 5454.00, '2025-11-27 08:31:26', 'images/skins/skin_69280c5e0fd34.png', 'terrt', 'custom', 0, 0),
(51, 9, 'oui', 4566.00, '2025-11-27 08:31:39', 'images/skins/skin_69280c6bdf3f4.png', 'zeazezae', 'fortnite', 0, 0),
(52, 9, 'bom', 45878.00, '2025-11-27 08:31:51', 'images/skins/skin_69280c771e919.png', 'zeazea', 'cs2', 0, 0),
(55, 9, 'hbibi', 211000.00, '2025-12-02 14:41:22', 'images/skins/skin_692efa924e035.png', 'rzaeeaz', 'fortnite', 1, 0),
(67, 6, 'zeaz', 5454.00, '2025-12-10 12:29:46', 'images/skins/skin_693967ba0c885.jpg', 'sqds', 'valorant', 0, 1),
(68, 6, 'fddffdfd', 555.00, '2025-12-10 12:31:00', 'images/skins/skin_69396804534b9.jpg', 'fff', 'custom', 1, 0),
(69, 9, 'rezrzer', 87878.00, '2025-12-10 12:35:32', 'images/skins/skin_693969145f022.jpg', 'ssdf', 'valorant', 1, 0);

-- --------------------------------------------------------

--
-- Structure de la table `trade`
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
-- Déchargement des données de la table `trade`
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
-- Structure de la table `trade_conversations`
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
-- Déchargement des données de la table `trade_conversations`
--

INSERT INTO `trade_conversations` (`id`, `skin_id`, `sender_id`, `receiver_id`, `message`, `image_path`, `negotiation_id`, `is_deleted`, `updated_at`, `created_at`) VALUES
(1, 4, 6, 7, 'sqdsq', NULL, NULL, 0, NULL, '2025-11-17 22:38:55'),
(2, 4, 6, 7, 'sqdqs', NULL, NULL, 0, NULL, '2025-11-17 22:39:13'),
(3, 4, 6, 7, 'ssss', NULL, NULL, 0, NULL, '2025-11-17 22:39:15'),
(4, 4, 6, 7, 'dsqdq', NULL, NULL, 0, NULL, '2025-11-17 22:47:24'),
(5, 4, 6, 7, 'dddd', NULL, NULL, 0, NULL, '2025-11-17 22:47:44'),
(6, 4, 6, 7, 'sqdqs', NULL, NULL, 0, NULL, '2025-11-17 23:00:18'),
(8, 4, 6, 7, 'dsdsqds', NULL, NULL, 0, NULL, '2025-11-18 20:49:02'),
(9, 4, 6, 7, 'sdqd', NULL, NULL, 0, NULL, '2025-11-19 14:56:01'),
(10, 4, 6, 7, 'zeaze', NULL, NULL, 0, NULL, '2025-11-19 14:56:14'),
(11, 29, 8, 6, 'erzr', NULL, NULL, 0, NULL, '2025-11-21 23:44:33'),
(12, 4, 9, 7, '\"\'rzerez', NULL, NULL, 0, NULL, '2025-11-22 01:23:18'),
(13, 30, 9, 8, 'sdq', NULL, NULL, 0, NULL, '2025-11-22 01:24:40'),
(14, 29, 9, 6, 'sqdqsdqd', NULL, NULL, 0, NULL, '2025-11-22 01:25:20'),
(15, 29, 9, 6, 'zseaze', NULL, NULL, 0, NULL, '2025-11-22 01:25:21'),
(16, 29, 9, 6, 'zeazeaze', NULL, NULL, 0, NULL, '2025-11-22 01:25:23'),
(17, 30, 9, 8, 'sq', NULL, NULL, 0, NULL, '2025-11-22 01:26:50'),
(18, 30, 9, 8, 'qsqs', NULL, NULL, 0, NULL, '2025-11-22 01:27:52'),
(19, 29, 9, 6, 'qsqsq', NULL, NULL, 0, NULL, '2025-11-22 01:27:58'),
(20, 4, 9, 7, 'sd', NULL, NULL, 0, NULL, '2025-11-26 13:56:09'),
(33, 55, 11, 9, 'hey', NULL, NULL, 0, NULL, '2025-12-02 14:51:57'),
(34, 55, 9, 11, 'ds', NULL, NULL, 0, NULL, '2025-12-02 15:00:20'),
(35, 55, 9, 11, 'sup', NULL, NULL, 0, NULL, '2025-12-02 15:00:30'),
(42, 55, 11, 9, '', 'uploads/messages/msg_692f09095d8f51.31954123.png', NULL, 0, NULL, '2025-12-02 15:43:05'),
(44, 55, 9, 11, '', 'uploads/messages/msg_692f1eeb109a54.91643293.png', NULL, 0, NULL, '2025-12-02 17:16:27'),
(46, 55, 9, 11, 'yes', NULL, NULL, 0, NULL, '2025-12-05 12:54:56'),
(47, 55, 6, 9, 'dsfsdfsf', NULL, NULL, 0, NULL, '2025-12-05 16:57:48'),
(48, 55, 6, 9, '4@', NULL, NULL, 0, NULL, '2025-12-05 19:21:08'),
(49, 55, 6, 9, '', 'uploads/messages/msg_693330c0ea5927.07339786.jpg', NULL, 0, NULL, '2025-12-05 19:21:36'),
(85, 67, 16, 6, 'rfezrezr', NULL, 'neg_693967d6495a3', 1, NULL, '2025-12-10 12:29:58'),
(86, 67, 6, 16, 'sdqs', NULL, 'neg_693967d6495a3', 1, NULL, '2025-12-10 12:30:13'),
(87, 68, 16, 6, 'dddd', NULL, 'neg_6939686b8c75a', 1, NULL, '2025-12-10 12:31:19'),
(88, 69, 6, 9, 'zezeze', NULL, 'neg_69396936e8885', 1, NULL, '2025-12-10 12:35:51'),
(89, 68, 9, 6, 'zeze', NULL, 'neg_69396e5196181', 1, NULL, '2025-12-10 12:56:46');

-- --------------------------------------------------------

--
-- Structure de la table `trade_history`
--

CREATE TABLE `trade_history` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `skin_id` int(11) NOT NULL,
  `action` enum('created','updated','deleted','buy','bought','negotiation_refused') NOT NULL,
  `skin_name` varchar(255) NOT NULL,
  `skin_price` decimal(10,2) NOT NULL,
  `skin_category` varchar(50) NOT NULL,
  `negotiation_id` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `visible_in_trading` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `trade_history`
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
(129, 16, 63, 'negotiation_refused', 'yes', 54475.00, 'custom', NULL, '2025-12-10 11:58:37', 1),
(131, 16, 63, 'negotiation_refused', 'yes', 54475.00, 'custom', NULL, '2025-12-10 11:58:58', 1),
(133, 16, 63, 'negotiation_refused', 'yes', 54475.00, 'custom', NULL, '2025-12-10 12:00:45', 1),
(135, 16, 63, 'negotiation_refused', 'yes', 54475.00, 'custom', 'neg_6939626eeead3', '2025-12-10 12:07:11', 1),
(137, 16, 63, 'negotiation_refused', 'yes', 54475.00, 'custom', 'neg_6939627e13e46', '2025-12-10 12:07:26', 1),
(139, 16, 63, 'negotiation_refused', 'yes', 54475.00, 'custom', 'neg_6939632136193', '2025-12-10 12:10:09', 1),
(141, 16, 63, 'negotiation_refused', 'yes', 54475.00, 'custom', 'neg_693963287bf1b', '2025-12-10 12:10:16', 1),
(145, 6, 64, 'created', 'zeaze', 4754.00, 'custom', NULL, '2025-12-10 12:21:11', 1),
(147, 16, 64, 'negotiation_refused', 'zeaze', 4754.00, 'custom', 'neg_693965f5d9a89', '2025-12-10 12:22:13', 1),
(148, 6, 65, 'created', 'erzear', 44554.00, 'custom', NULL, '2025-12-10 12:22:59', 1),
(149, 6, 65, 'deleted', 'erzear', 44554.00, 'custom', NULL, '2025-12-10 12:24:36', 1),
(150, 6, 64, 'deleted', 'zeaze', 4754.00, 'custom', NULL, '2025-12-10 12:24:45', 1),
(151, 6, 66, 'created', 'zeaze', 455.00, 'custom', NULL, '2025-12-10 12:25:02', 1),
(153, 16, 66, 'negotiation_refused', 'zeaze', 455.00, 'custom', 'neg_693966ca0a709', '2025-12-10 12:25:46', 1),
(154, 6, 66, 'deleted', 'zeaze', 455.00, 'custom', NULL, '2025-12-10 12:25:56', 1),
(155, 6, 67, 'created', 'zeaz', 5454.00, 'valorant', NULL, '2025-12-10 12:29:46', 1),
(156, 6, 67, 'negotiation_refused', 'zeaz', 5454.00, 'valorant', 'neg_693967d6495a3', '2025-12-10 12:30:14', 1),
(157, 16, 67, 'negotiation_refused', 'zeaz', 5454.00, 'valorant', 'neg_693967d6495a3', '2025-12-10 12:30:14', 1),
(158, 6, 67, 'deleted', 'zeaz', 5454.00, 'valorant', NULL, '2025-12-10 12:30:25', 1),
(159, 6, 68, 'created', 'fddffdfd', 555.00, 'custom', NULL, '2025-12-10 12:31:00', 1),
(160, 6, 68, 'negotiation_refused', 'fddffdfd', 555.00, 'custom', 'neg_6939686b8c75a', '2025-12-10 12:32:43', 1),
(161, 16, 68, 'negotiation_refused', 'fddffdfd', 555.00, 'custom', 'neg_6939686b8c75a', '2025-12-10 12:32:43', 1),
(162, 9, 69, 'created', 'rezrzer', 87878.00, 'valorant', NULL, '2025-12-10 12:35:32', 1),
(163, 9, 69, 'negotiation_refused', 'rezrzer', 87878.00, 'valorant', 'neg_69396936e8885', '2025-12-10 12:36:07', 1),
(164, 6, 69, 'negotiation_refused', 'rezrzer', 87878.00, 'valorant', 'neg_69396936e8885', '2025-12-10 12:36:07', 1),
(165, 6, 68, 'negotiation_refused', 'fddffdfd', 555.00, 'custom', 'neg_69396e5196181', '2025-12-10 12:57:53', 1),
(166, 9, 68, 'negotiation_refused', 'fddffdfd', 555.00, 'custom', 'neg_69396e5196181', '2025-12-10 12:57:53', 1);

-- --------------------------------------------------------

--
-- Structure de la table `trade_history_trading_view`
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

-- --------------------------------------------------------

--
-- Structure de la table `users`
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
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `google_id`, `dob`, `password`, `gender`, `role`, `status`, `image`) VALUES
(4, 'MissTagada', 'dhrifmeriem1231230@gmail.com', NULL, '2005-12-10', '$2y$10$sKVc5L7xU9BC0MJNIGqVUuJDckzrsh4ZPGUfKSLyUWtW8.TwQspUK', 'Female', 'Admin', 'active', 'uploads/profiles/profile_693986bed456a.png'),
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
(17, 'Meriem', 'misstagada1231230@gmail.com', NULL, '2003-10-12', '$2y$10$ncd8MA2UTjmg3jowgGEU1eJj4gO8nQA7wetHklAA6vZXLlPFzceSe', 'Female', 'Gamer', 'active', 'uploads/profiles/profile_6939863297ca4.jpg');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `article`
--
ALTER TABLE `article`
  ADD PRIMARY KEY (`idArticle`),
  ADD KEY `idx_article_pub` (`id_pub`),
  ADD KEY `idx_article_categorie` (`idCategorie`);

--
-- Index pour la table `categorie`
--
ALTER TABLE `categorie`
  ADD PRIMARY KEY (`idCategorie`);

--
-- Index pour la table `email_verifications`
--
ALTER TABLE `email_verifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `evenement`
--
ALTER TABLE `evenement`
  ADD PRIMARY KEY (`id_evenement`),
  ADD KEY `idx_evenement_createur` (`createur_id`),
  ADD KEY `idx_evenement_date` (`date_debut`);

--
-- Index pour la table `participation`
--
ALTER TABLE `participation`
  ADD PRIMARY KEY (`id_participation`),
  ADD UNIQUE KEY `unique_participation` (`id_evenement`,`id_gamer`),
  ADD KEY `id_gamer` (`id_gamer`);

--
-- Index pour la table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `produit`
--
ALTER TABLE `produit`
  ADD PRIMARY KEY (`produit_id`);

--
-- Index pour la table `purchase`
--
ALTER TABLE `purchase`
  ADD PRIMARY KEY (`purchase_id`),
  ADD UNIQUE KEY `transactionId` (`transactionId`),
  ADD KEY `produit_id` (`produit_id`),
  ADD KEY `idx_purchase_user` (`user_id`),
  ADD KEY `idx_purchase_date` (`purchaseDate`);

--
-- Index pour la table `reclamation`
--
ALTER TABLE `reclamation`
  ADD PRIMARY KEY (`id_reclamation`),
  ADD KEY `idx_reclamation_user` (`id_utilisateur`);

--
-- Index pour la table `reponse`
--
ALTER TABLE `reponse`
  ADD PRIMARY KEY (`id_reponse`),
  ADD KEY `id_admin` (`id_admin`),
  ADD KEY `idx_reponse_reclamation` (`id_reclamation`);

--
-- Index pour la table `skins`
--
ALTER TABLE `skins`
  ADD PRIMARY KEY (`skin_id`),
  ADD KEY `idx_skins_owner` (`owner_id`);

--
-- Index pour la table `trade`
--
ALTER TABLE `trade`
  ADD PRIMARY KEY (`trade_id`),
  ADD KEY `skin_id` (`skin_id`),
  ADD KEY `idx_trade_buyer` (`buyer_id`),
  ADD KEY `idx_trade_seller` (`seller_id`);

--
-- Index pour la table `trade_conversations`
--
ALTER TABLE `trade_conversations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_conversation_skin` (`skin_id`),
  ADD KEY `idx_conversation_sender` (`sender_id`),
  ADD KEY `idx_conversation_receiver` (`receiver_id`),
  ADD KEY `idx_conversation_created` (`created_at`),
  ADD KEY `idx_negotiation_id` (`negotiation_id`);

--
-- Index pour la table `trade_history`
--
ALTER TABLE `trade_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_visible_created` (`visible_in_trading`,`created_at`),
  ADD KEY `idx_user_visible` (`user_id`,`visible_in_trading`),
  ADD KEY `idx_negotiation_id` (`negotiation_id`);

--
-- Index pour la table `trade_history_trading_view`
--
ALTER TABLE `trade_history_trading_view`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `google_id` (`google_id`),
  ADD KEY `idx_user_email` (`email`),
  ADD KEY `idx_user_username` (`username`),
  ADD KEY `idx_google_id` (`google_id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `article`
--
ALTER TABLE `article`
  MODIFY `idArticle` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `categorie`
--
ALTER TABLE `categorie`
  MODIFY `idCategorie` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `email_verifications`
--
ALTER TABLE `email_verifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `evenement`
--
ALTER TABLE `evenement`
  MODIFY `id_evenement` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `participation`
--
ALTER TABLE `participation`
  MODIFY `id_participation` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

--
-- AUTO_INCREMENT pour la table `produit`
--
ALTER TABLE `produit`
  MODIFY `produit_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `purchase`
--
ALTER TABLE `purchase`
  MODIFY `purchase_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `reclamation`
--
ALTER TABLE `reclamation`
  MODIFY `id_reclamation` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `reponse`
--
ALTER TABLE `reponse`
  MODIFY `id_reponse` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `skins`
--
ALTER TABLE `skins`
  MODIFY `skin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=70;

--
-- AUTO_INCREMENT pour la table `trade`
--
ALTER TABLE `trade`
  MODIFY `trade_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT pour la table `trade_conversations`
--
ALTER TABLE `trade_conversations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=90;

--
-- AUTO_INCREMENT pour la table `trade_history`
--
ALTER TABLE `trade_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=167;

--
-- AUTO_INCREMENT pour la table `trade_history_trading_view`
--
ALTER TABLE `trade_history_trading_view`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `article`
--
ALTER TABLE `article`
  ADD CONSTRAINT `article_ibfk_1` FOREIGN KEY (`id_pub`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `article_ibfk_2` FOREIGN KEY (`idCategorie`) REFERENCES `categorie` (`idCategorie`) ON DELETE CASCADE;

--
-- Contraintes pour la table `email_verifications`
--
ALTER TABLE `email_verifications`
  ADD CONSTRAINT `email_verifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `evenement`
--
ALTER TABLE `evenement`
  ADD CONSTRAINT `evenement_ibfk_1` FOREIGN KEY (`createur_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `participation`
--
ALTER TABLE `participation`
  ADD CONSTRAINT `participation_ibfk_1` FOREIGN KEY (`id_evenement`) REFERENCES `evenement` (`id_evenement`) ON DELETE CASCADE,
  ADD CONSTRAINT `participation_ibfk_2` FOREIGN KEY (`id_gamer`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `purchase`
--
ALTER TABLE `purchase`
  ADD CONSTRAINT `purchase_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_ibfk_2` FOREIGN KEY (`produit_id`) REFERENCES `produit` (`produit_id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `reclamation`
--
ALTER TABLE `reclamation`
  ADD CONSTRAINT `reclamation_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `reponse`
--
ALTER TABLE `reponse`
  ADD CONSTRAINT `reponse_ibfk_1` FOREIGN KEY (`id_reclamation`) REFERENCES `reclamation` (`id_reclamation`) ON DELETE CASCADE,
  ADD CONSTRAINT `reponse_ibfk_2` FOREIGN KEY (`id_admin`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `skins`
--
ALTER TABLE `skins`
  ADD CONSTRAINT `skins_ibfk_1` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `trade`
--
ALTER TABLE `trade`
  ADD CONSTRAINT `trade_ibfk_1` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `trade_ibfk_3` FOREIGN KEY (`skin_id`) REFERENCES `skins` (`skin_id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `trade_conversations`
--
ALTER TABLE `trade_conversations`
  ADD CONSTRAINT `trade_conversations_ibfk_1` FOREIGN KEY (`skin_id`) REFERENCES `skins` (`skin_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `trade_conversations_ibfk_2` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `trade_history`
--
ALTER TABLE `trade_history`
  ADD CONSTRAINT `trade_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
