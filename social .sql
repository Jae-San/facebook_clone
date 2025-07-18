-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : lun. 14 juil. 2025 à 11:09
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
-- Base de données : `social`
--

-- --------------------------------------------------------

--
-- Structure de la table `comments`
--

CREATE TABLE `comments` (
  `id` int(11) NOT NULL,
  `post_body` text NOT NULL,
  `posted_by` varchar(60) NOT NULL,
  `posted_to` varchar(60) NOT NULL,
  `date_added` datetime NOT NULL,
  `removed` varchar(3) NOT NULL DEFAULT 'no',
  `post_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `comments`
--

INSERT INTO `comments` (`id`, `post_body`, `posted_by`, `posted_to`, `date_added`, `removed`, `post_id`) VALUES
(6, 'Hi', 'lauriane_degan', 'lauriane_degan', '2025-07-14 10:50:30', 'no', 16),
(7, 'Hi', 'lauriane_degan', 'lauriane_degan', '2025-07-14 11:05:18', 'no', 17);

-- --------------------------------------------------------

--
-- Structure de la table `friend_requests`
--

CREATE TABLE `friend_requests` (
  `id` int(11) NOT NULL,
  `user_to` varchar(50) NOT NULL,
  `user_from` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `likes`
--

CREATE TABLE `likes` (
  `id` int(11) NOT NULL,
  `username` varchar(60) NOT NULL,
  `post_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `user_to` varchar(50) NOT NULL,
  `user_from` varchar(50) NOT NULL,
  `body` text NOT NULL,
  `date` datetime NOT NULL,
  `opened` varchar(3) NOT NULL DEFAULT 'no',
  `viewed` varchar(3) NOT NULL DEFAULT 'no',
  `deleted` varchar(3) NOT NULL DEFAULT 'no',
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_to` varchar(50) NOT NULL,
  `user_from` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(100) NOT NULL,
  `datetime` datetime NOT NULL,
  `opened` varchar(3) NOT NULL DEFAULT 'no',
  `viewed` varchar(3) NOT NULL DEFAULT 'no'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `posts`
--

CREATE TABLE `posts` (
  `id` int(11) NOT NULL,
  `body` text NOT NULL,
  `added_by` varchar(60) NOT NULL,
  `user_to` varchar(60) NOT NULL DEFAULT 'none',
  `date_added` datetime NOT NULL,
  `user_closed` varchar(3) NOT NULL DEFAULT 'no',
  `deleted` varchar(3) NOT NULL DEFAULT 'no',
  `likes` int(11) NOT NULL DEFAULT 0,
  `image` varchar(500) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `posts`
--

INSERT INTO `posts` (`id`, `body`, `added_by`, `user_to`, `date_added`, `user_closed`, `deleted`, `likes`, `image`) VALUES
(10, 'Hi ', 'lauriane_degan', 'none', '2025-07-13 23:53:54', 'no', 'no', 0, 'assets/images/posts/68742af2f23e7Capture d\'écran 2025-07-10 144203.png'),
(11, 'hiiiiiiiiiii', 'lauriane_degan', 'none', '2025-07-13 23:54:10', 'no', 'no', 0, 'assets/images/posts/68742b02a2e4bCapture d\'écran 2025-07-07 142403.png'),
(12, 'Hiii', 'lauriane_degan', 'none', '2025-07-14 07:43:21', 'no', 'no', 0, ''),
(13, 'Hello', 'lauriane_degan', 'none', '2025-07-14 07:49:04', 'no', 'no', 0, 'assets/images/posts/68749a50335abCapture d\'écran 2025-07-07 142403.png'),
(14, 'Bonjour ', 'lauriane_degan', 'none', '2025-07-14 08:23:42', 'no', 'no', 0, ''),
(15, 'hu', 'lauriane_degan', 'none', '2025-07-14 08:41:16', 'no', 'no', 0, ''),
(16, 'hiu', 'lauriane_degan', 'none', '2025-07-14 08:43:46', 'no', 'no', 0, 'assets/images/posts/6874a7229b878Capture_d__ecran_2024-12-10_231325.png'),
(17, 'petit petitement test', 'lauriane_degan', 'none', '2025-07-14 08:44:12', 'no', 'no', 0, 'assets/images/posts/6874a73c8200cCapture_d__ecran_2025-07-07_131926.png');

-- --------------------------------------------------------

--
-- Structure de la table `trends`
--

CREATE TABLE `trends` (
  `id` int(11) NOT NULL,
  `title` varchar(50) NOT NULL,
  `hits` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `trends`
--

INSERT INTO `trends` (`id`, `title`, `hits`) VALUES
(26, 'Jai', 1),
(27, 'Trouv', 1),
(28, 'Soucis', 1),
(29, 'Hi', 1),
(30, 'Hiiiiiiiiiii', 1),
(31, 'Hiii', 1),
(32, 'Hello', 1),
(33, 'Bonjour', 1),
(34, 'Hu', 1),
(35, 'Hiu', 1),
(36, 'Petit', 1),
(37, 'Petitement', 1),
(38, 'Test', 1);

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(25) NOT NULL,
  `last_name` varchar(25) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `signup_date` date NOT NULL,
  `profile_pic` varchar(255) NOT NULL,
  `num_posts` int(11) NOT NULL DEFAULT 0,
  `num_likes` int(11) NOT NULL DEFAULT 0,
  `user_closed` varchar(3) NOT NULL DEFAULT 'no',
  `friend_array` text NOT NULL,
  `isconfirm` int(11) DEFAULT 0,
  `token` varchar(255) DEFAULT '',
  `tokenExpire` datetime DEFAULT NULL,
  `role` VARCHAR(20) NOT NULL DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `username`, `email`, `password`, `signup_date`, `profile_pic`, `num_posts`, `num_likes`, `user_closed`, `friend_array`, `isconfirm`, `token`, `tokenExpire`, `role`) VALUES
(1, 'Test', 'User', 'groupedeux', 'groupdeux@gmail.com', 'b1e2e2e2e2e2e2e2e2e2e2e2e2e2e2e2', '2024-01-01', 'assets/images/profile_pics/defaults/head_deep_blue.png', 0, 0, 'no', ',', 0, '', NULL, 'user'),
(21, 'Lauriane', 'Degan', 'lauriane_degan', 'Laurianelaurie029@gmail.com', 'b10a9d3c493ca15187d9895d53e740dc', '2025-07-13', 'assets/images/profile_pics/defaults/head_emerald.png', 8, 0, 'no', ',', 1, '', NULL, 'user'),
(2, 'Admin', 'Test', 'groupedeuxesgis', 'groupedeuxesgis@gmail.com', 'b8e2e2e2e2e2e2e2e2e2e2e2e2e2e2e2', '2024-01-01', 'assets/images/profile_pics/defaults/head_amethyst.png', 0, 0, 'no', ',', 1, '', NULL, 'admin'),
(3, 'Modo', 'Test', 'tpesgis', 'tpesgis@gmail.com', 'B2E2E2E2E2E2E2E2E2E2E2E2E2E2E2E2', '2024-01-01', 'assets/images/profile_pics/defaults/head_wisteria.png', 0, 0, 'no', ',', 1, '', NULL, 'moderator');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `posted_by` (`posted_by`),
  ADD KEY `posted_to` (`posted_to`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `idx_comments_post_id` (`post_id`,`date_added`);

--
-- Index pour la table `friend_requests`
--
ALTER TABLE `friend_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_to_user_from` (`user_to`,`user_from`),
  ADD KEY `user_from` (`user_from`);

--
-- Index pour la table `likes`
--
ALTER TABLE `likes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username_post_id` (`username`,`post_id`),
  ADD KEY `post_id` (`post_id`);

--
-- Index pour la table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_to` (`user_to`),
  ADD KEY `user_from` (`user_from`),
  ADD KEY `date` (`date`),
  ADD KEY `idx_messages_users` (`user_to`,`user_from`);

--
-- Index pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_to` (`user_to`),
  ADD KEY `user_from` (`user_from`),
  ADD KEY `datetime` (`datetime`),
  ADD KEY `idx_notifications_user_to` (`user_to`,`viewed`);

--
-- Index pour la table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `added_by` (`added_by`),
  ADD KEY `user_to` (`user_to`),
  ADD KEY `date_added` (`date_added`),
  ADD KEY `idx_posts_deleted_date` (`deleted`,`date_added`);

--
-- Index pour la table `trends`
--
ALTER TABLE `trends`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `title` (`title`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `comments`
--
ALTER TABLE `comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `friend_requests`
--
ALTER TABLE `friend_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `likes`
--
ALTER TABLE `likes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT pour la table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT pour la table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `posts`
--
ALTER TABLE `posts`