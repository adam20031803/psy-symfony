-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : dim. 05 avr. 2026 à 16:37
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
-- Base de données : `psy_db`
--

-- --------------------------------------------------------

--
-- Structure de la table `app_settings`
--

CREATE TABLE `app_settings` (
  `id` int(11) NOT NULL,
  `cle` varchar(100) NOT NULL,
  `valeur` longtext NOT NULL,
  `description` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `categorie`
--

CREATE TABLE `categorie` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `description` longtext DEFAULT NULL,
  `icon` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `categorie`
--

INSERT INTO `categorie` (`id`, `nom`, `description`, `icon`) VALUES
(1, 'Confiance en soi', NULL, NULL),
(2, 'Gestion du stress', NULL, NULL),
(3, 'Psychologie positive', NULL, NULL),
(4, 'Productivité', NULL, NULL),
(5, 'Bien-être mental', NULL, NULL),
(6, 'hhhhh', NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `challenge`
--

CREATE TABLE `challenge` (
  `id` int(11) NOT NULL,
  `titre` varchar(255) NOT NULL,
  `description` longtext NOT NULL,
  `date_debut` date NOT NULL,
  `date_fin` date NOT NULL,
  `statut` varchar(50) NOT NULL DEFAULT 'actif',
  `created_at` datetime NOT NULL,
  `categorie_id` int(11) DEFAULT NULL,
  `created_by_id` int(11) NOT NULL,
  `media_url` varchar(255) DEFAULT NULL,
  `media_type` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `challenge`
--

INSERT INTO `challenge` (`id`, `titre`, `description`, `date_debut`, `date_fin`, `statut`, `created_at`, `categorie_id`, `created_by_id`, `media_url`, `media_type`) VALUES
(1, 'jnnnn', 'nnnnn', '2026-04-04', '2026-05-04', 'annule', '2026-04-04 16:26:21', 4, 1, '/uploads/challenges/69d137d0b5201.mp4', 'video'),
(2, 'hhhh', 'dddddd', '2026-04-04', '2026-05-04', 'inactif', '2026-04-04 16:32:49', NULL, 1, NULL, NULL),
(3, 'abdou', 'fffff', '2026-04-04', '2026-04-05', 'actif', '2026-04-04 17:38:47', NULL, 1, NULL, NULL),
(4, 'ggggg', 'hhhhh', '2026-04-18', '2026-05-01', 'termine', '2026-04-04 18:02:10', 6, 1, '/uploads/challenges/69d25f71b6430.mp4', 'video'),
(5, '', '', '2026-04-04', '2026-05-04', 'actif', '2026-04-04 18:17:39', NULL, 1, NULL, NULL),
(6, 'uyyyyy', 'jjjjjjjjjjjjjjjjjjjjjj', '2026-04-05', '2026-04-17', 'termine', '2026-04-05 14:32:28', 3, 3, '/uploads/challenges/69d25fc044b44.mp4', 'video'),
(7, 'kkkkk0', 'hhhhh', '2026-04-05', '2026-04-06', 'actif', '2026-04-05 14:40:02', 2, 3, NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `challenge_chat`
--

CREATE TABLE `challenge_chat` (
  `id` int(11) NOT NULL,
  `message` longtext NOT NULL,
  `created_at` datetime NOT NULL,
  `challenge_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `challenge_coach`
--

CREATE TABLE `challenge_coach` (
  `id` int(11) NOT NULL,
  `assigned_at` datetime NOT NULL,
  `challenge_id` int(11) NOT NULL,
  `coach_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `challenge_coach`
--

INSERT INTO `challenge_coach` (`id`, `assigned_at`, `challenge_id`, `coach_id`) VALUES
(1, '2026-04-04 17:09:23', 1, 2);

-- --------------------------------------------------------

--
-- Structure de la table `challenge_recompense`
--

CREATE TABLE `challenge_recompense` (
  `id` int(11) NOT NULL,
  `challenge_id` int(11) NOT NULL,
  `recompense_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `challenge_recompense`
--

INSERT INTO `challenge_recompense` (`id`, `challenge_id`, `recompense_id`) VALUES
(1, 1, 1);

-- --------------------------------------------------------

--
-- Structure de la table `coach_motivation`
--

CREATE TABLE `coach_motivation` (
  `id` int(11) NOT NULL,
  `message` longtext NOT NULL,
  `created_at` datetime NOT NULL,
  `coach_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `commentaire`
--

CREATE TABLE `commentaire` (
  `id` int(11) NOT NULL,
  `contenu` longtext NOT NULL,
  `created_at` datetime NOT NULL,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `daily_checkin`
--

CREATE TABLE `daily_checkin` (
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `mood_score` int(11) NOT NULL,
  `notes` longtext DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `doctrine_migration_versions`
--

CREATE TABLE `doctrine_migration_versions` (
  `version` varchar(191) NOT NULL,
  `executed_at` datetime DEFAULT NULL,
  `execution_time` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `habitude`
--

CREATE TABLE `habitude` (
  `id` int(11) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `description` longtext DEFAULT NULL,
  `frequence` varchar(50) NOT NULL,
  `created_at` datetime NOT NULL,
  `is_active` tinyint(4) NOT NULL DEFAULT 1,
  `user_id` int(11) NOT NULL,
  `categorie_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `habit_completions`
--

CREATE TABLE `habit_completions` (
  `id` int(11) NOT NULL,
  `completed_at` datetime NOT NULL,
  `notes` longtext DEFAULT NULL,
  `habitude_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `habit_streaks`
--

CREATE TABLE `habit_streaks` (
  `id` int(11) NOT NULL,
  `current_streak` int(11) NOT NULL DEFAULT 0,
  `longest_streak` int(11) NOT NULL DEFAULT 0,
  `last_completed` date DEFAULT NULL,
  `habitude_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `mental_entries`
--

CREATE TABLE `mental_entries` (
  `id` int(11) NOT NULL,
  `contenu` longtext NOT NULL,
  `humeur` varchar(50) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `mental_tips`
--

CREATE TABLE `mental_tips` (
  `id` int(11) NOT NULL,
  `titre` varchar(255) NOT NULL,
  `contenu` longtext NOT NULL,
  `created_at` datetime NOT NULL,
  `categorie_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `moods`
--

CREATE TABLE `moods` (
  `id` int(11) NOT NULL,
  `humeur` varchar(50) NOT NULL,
  `score` int(11) DEFAULT NULL,
  `notes` longtext DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `notification_log`
--

CREATE TABLE `notification_log` (
  `id` int(11) NOT NULL,
  `message` varchar(255) NOT NULL,
  `type` varchar(50) NOT NULL,
  `is_read` tinyint(4) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `post`
--

CREATE TABLE `post` (
  `id` int(11) NOT NULL,
  `titre` varchar(255) NOT NULL,
  `contenu` longtext NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `is_anonymous` tinyint(4) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `categorie_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `post_share`
--

CREATE TABLE `post_share` (
  `id` int(11) NOT NULL,
  `shared_at` datetime NOT NULL,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `reclamation`
--

CREATE TABLE `reclamation` (
  `id` int(11) NOT NULL,
  `sujet` varchar(255) NOT NULL,
  `description` longtext NOT NULL,
  `statut` varchar(50) NOT NULL DEFAULT 'ouvert',
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `reclamation_event`
--

CREATE TABLE `reclamation_event` (
  `id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `commentaire` longtext DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `reclamation_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `recompense`
--

CREATE TABLE `recompense` (
  `id` int(11) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `description` longtext NOT NULL,
  `points` int(11) NOT NULL,
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `recompense`
--

INSERT INTO `recompense` (`id`, `nom`, `description`, `points`, `image`) VALUES
(1, 'ggggg', 'gggggg', 100, 'badge.png');

-- --------------------------------------------------------

--
-- Structure de la table `team`
--

CREATE TABLE `team` (
  `id` int(11) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `description` longtext DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `created_by_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `team_member`
--

CREATE TABLE `team_member` (
  `id` int(11) NOT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'member',
  `joined_at` datetime NOT NULL,
  `team_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(180) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'user',
  `photo` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `is_active` tinyint(4) NOT NULL DEFAULT 1,
  `age` int(11) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `password_reset_token` varchar(100) DEFAULT NULL,
  `password_reset_requested_at` datetime DEFAULT NULL,
  `cv` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `user`
--

INSERT INTO `user` (`id`, `nom`, `prenom`, `email`, `password`, `role`, `photo`, `created_at`, `is_active`, `age`, `telephone`, `password_reset_token`, `password_reset_requested_at`, `cv`) VALUES
(1, 'Admin', 'User', 'admin69d11f8d2aa91@test.com', 'password123', 'user', NULL, '2026-04-04 16:26:21', 1, NULL, NULL, NULL, NULL, NULL),
(2, 'abdou', 'mhdi', 'mahdiabderrahmen8@gmail.com', 'coach_69d12515c4125', 'coach', NULL, '2026-04-04 16:49:57', 1, 32, '99095610', '12345678', NULL, NULL),
(3, '', 'Abderrahmen', 'abderrahmen.mehdi@esprit.tn', '$2y$13$wkatVCqGnPQsXOqFlIwaoubWPzDHocIyMzrg2Q7kUMbfVF0LRGSOu', 'user', NULL, '2026-04-04 17:13:28', 1, 23, '99095610', NULL, NULL, NULL),
(4, 'Mahdi', 'Abderrahmen', 'mahdiabderrahmen@gmail.com', 'coach_69d25a6edea49', 'coach', NULL, '2026-04-05 14:49:50', 1, 32, '13456789', NULL, NULL, '/uploads/cv/69d25a6eded8b.pdf'),
(5, 'Mahdi', 'Abderrahmen', 'mahdiabderrahme@gmail.com', 'coach_69d26156b6388', 'coach', '/uploads/photos/69d26156c0832.jpg', '2026-04-05 15:19:18', 1, 32, '12345678', NULL, NULL, '/uploads/cv/69d26156b6a66.pdf');

-- --------------------------------------------------------

--
-- Structure de la table `workout`
--

CREATE TABLE `workout` (
  `id` int(11) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `description` longtext NOT NULL,
  `duree` int(11) NOT NULL,
  `type` varchar(100) NOT NULL,
  `niveau` varchar(50) NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `workout_plan`
--

CREATE TABLE `workout_plan` (
  `id` int(11) NOT NULL,
  `date_planifie` date NOT NULL,
  `statut` varchar(50) NOT NULL DEFAULT 'planifie',
  `created_at` datetime NOT NULL,
  `user_id` int(11) NOT NULL,
  `workout_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `workout_progress`
--

CREATE TABLE `workout_progress` (
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `duree_reelle` int(11) DEFAULT NULL,
  `notes` longtext DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `user_id` int(11) NOT NULL,
  `workout_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `app_settings`
--
ALTER TABLE `app_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UNIQ_EDC5C06841401D17` (`cle`);

--
-- Index pour la table `categorie`
--
ALTER TABLE `categorie`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `challenge`
--
ALTER TABLE `challenge`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_D7098951BCF5E72D` (`categorie_id`),
  ADD KEY `IDX_D7098951B03A8386` (`created_by_id`);

--
-- Index pour la table `challenge_chat`
--
ALTER TABLE `challenge_chat`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_6C32F52C98A21AC6` (`challenge_id`),
  ADD KEY `IDX_6C32F52CA76ED395` (`user_id`);

--
-- Index pour la table `challenge_coach`
--
ALTER TABLE `challenge_coach`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_3B8BE4DE98A21AC6` (`challenge_id`),
  ADD KEY `IDX_3B8BE4DE3C105691` (`coach_id`);

--
-- Index pour la table `challenge_recompense`
--
ALTER TABLE `challenge_recompense`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_7E8BD9D798A21AC6` (`challenge_id`),
  ADD KEY `IDX_7E8BD9D74D714096` (`recompense_id`);

--
-- Index pour la table `coach_motivation`
--
ALTER TABLE `coach_motivation`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_8E9D041E3C105691` (`coach_id`);

--
-- Index pour la table `commentaire`
--
ALTER TABLE `commentaire`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_67F068BC4B89032C` (`post_id`),
  ADD KEY `IDX_67F068BCA76ED395` (`user_id`);

--
-- Index pour la table `daily_checkin`
--
ALTER TABLE `daily_checkin`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_3E82CD65A76ED395` (`user_id`);

--
-- Index pour la table `doctrine_migration_versions`
--
ALTER TABLE `doctrine_migration_versions`
  ADD PRIMARY KEY (`version`);

--
-- Index pour la table `habitude`
--
ALTER TABLE `habitude`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_10DD3E5FA76ED395` (`user_id`),
  ADD KEY `IDX_10DD3E5FBCF5E72D` (`categorie_id`);

--
-- Index pour la table `habit_completions`
--
ALTER TABLE `habit_completions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_F0AE56A4B80619B9` (`habitude_id`),
  ADD KEY `IDX_F0AE56A4A76ED395` (`user_id`);

--
-- Index pour la table `habit_streaks`
--
ALTER TABLE `habit_streaks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UNIQ_28B91E24B80619B9` (`habitude_id`),
  ADD KEY `IDX_28B91E24A76ED395` (`user_id`);

--
-- Index pour la table `mental_entries`
--
ALTER TABLE `mental_entries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_CE38E1B0A76ED395` (`user_id`);

--
-- Index pour la table `mental_tips`
--
ALTER TABLE `mental_tips`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_D66E2EC5BCF5E72D` (`categorie_id`);

--
-- Index pour la table `moods`
--
ALTER TABLE `moods`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_4FD3A18CA76ED395` (`user_id`);

--
-- Index pour la table `notification_log`
--
ALTER TABLE `notification_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_ED15DF2A76ED395` (`user_id`);

--
-- Index pour la table `post`
--
ALTER TABLE `post`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_5A8A6C8DA76ED395` (`user_id`),
  ADD KEY `IDX_5A8A6C8DBCF5E72D` (`categorie_id`);

--
-- Index pour la table `post_share`
--
ALTER TABLE `post_share`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_781D11B54B89032C` (`post_id`),
  ADD KEY `IDX_781D11B5A76ED395` (`user_id`);

--
-- Index pour la table `reclamation`
--
ALTER TABLE `reclamation`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_CE606404A76ED395` (`user_id`);

--
-- Index pour la table `reclamation_event`
--
ALTER TABLE `reclamation_event`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_E6A239E92D6BA2D9` (`reclamation_id`),
  ADD KEY `IDX_E6A239E9A76ED395` (`user_id`);

--
-- Index pour la table `recompense`
--
ALTER TABLE `recompense`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `team`
--
ALTER TABLE `team`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_C4E0A61FB03A8386` (`created_by_id`);

--
-- Index pour la table `team_member`
--
ALTER TABLE `team_member`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_6FFBDA1296CD8AE` (`team_id`),
  ADD KEY `IDX_6FFBDA1A76ED395` (`user_id`);

--
-- Index pour la table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UNIQ_8D93D649E7927C74` (`email`),
  ADD UNIQUE KEY `UNIQ_8D93D6496B7BA4B6` (`password_reset_token`);

--
-- Index pour la table `workout`
--
ALTER TABLE `workout`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `workout_plan`
--
ALTER TABLE `workout_plan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_A5D45801A76ED395` (`user_id`),
  ADD KEY `IDX_A5D45801A6CCCFC9` (`workout_id`);

--
-- Index pour la table `workout_progress`
--
ALTER TABLE `workout_progress`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_FA701FF0A76ED395` (`user_id`),
  ADD KEY `IDX_FA701FF0A6CCCFC9` (`workout_id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `app_settings`
--
ALTER TABLE `app_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `categorie`
--
ALTER TABLE `categorie`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `challenge`
--
ALTER TABLE `challenge`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `challenge_chat`
--
ALTER TABLE `challenge_chat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `challenge_coach`
--
ALTER TABLE `challenge_coach`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `challenge_recompense`
--
ALTER TABLE `challenge_recompense`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `coach_motivation`
--
ALTER TABLE `coach_motivation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `commentaire`
--
ALTER TABLE `commentaire`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `daily_checkin`
--
ALTER TABLE `daily_checkin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `habitude`
--
ALTER TABLE `habitude`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `habit_completions`
--
ALTER TABLE `habit_completions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `habit_streaks`
--
ALTER TABLE `habit_streaks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `mental_entries`
--
ALTER TABLE `mental_entries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `mental_tips`
--
ALTER TABLE `mental_tips`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `moods`
--
ALTER TABLE `moods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `notification_log`
--
ALTER TABLE `notification_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `post`
--
ALTER TABLE `post`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `post_share`
--
ALTER TABLE `post_share`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `reclamation`
--
ALTER TABLE `reclamation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `reclamation_event`
--
ALTER TABLE `reclamation_event`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `recompense`
--
ALTER TABLE `recompense`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `team`
--
ALTER TABLE `team`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `team_member`
--
ALTER TABLE `team_member`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `workout`
--
ALTER TABLE `workout`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `workout_plan`
--
ALTER TABLE `workout_plan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `workout_progress`
--
ALTER TABLE `workout_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `challenge`
--
ALTER TABLE `challenge`
  ADD CONSTRAINT `FK_D7098951B03A8386` FOREIGN KEY (`created_by_id`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `FK_D7098951BCF5E72D` FOREIGN KEY (`categorie_id`) REFERENCES `categorie` (`id`);

--
-- Contraintes pour la table `challenge_chat`
--
ALTER TABLE `challenge_chat`
  ADD CONSTRAINT `FK_6C32F52C98A21AC6` FOREIGN KEY (`challenge_id`) REFERENCES `challenge` (`id`),
  ADD CONSTRAINT `FK_6C32F52CA76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);

--
-- Contraintes pour la table `challenge_coach`
--
ALTER TABLE `challenge_coach`
  ADD CONSTRAINT `FK_3B8BE4DE3C105691` FOREIGN KEY (`coach_id`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `FK_3B8BE4DE98A21AC6` FOREIGN KEY (`challenge_id`) REFERENCES `challenge` (`id`);

--
-- Contraintes pour la table `challenge_recompense`
--
ALTER TABLE `challenge_recompense`
  ADD CONSTRAINT `FK_7E8BD9D74D714096` FOREIGN KEY (`recompense_id`) REFERENCES `recompense` (`id`),
  ADD CONSTRAINT `FK_7E8BD9D798A21AC6` FOREIGN KEY (`challenge_id`) REFERENCES `challenge` (`id`);

--
-- Contraintes pour la table `coach_motivation`
--
ALTER TABLE `coach_motivation`
  ADD CONSTRAINT `FK_8E9D041E3C105691` FOREIGN KEY (`coach_id`) REFERENCES `user` (`id`);

--
-- Contraintes pour la table `commentaire`
--
ALTER TABLE `commentaire`
  ADD CONSTRAINT `FK_67F068BC4B89032C` FOREIGN KEY (`post_id`) REFERENCES `post` (`id`),
  ADD CONSTRAINT `FK_67F068BCA76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);

--
-- Contraintes pour la table `daily_checkin`
--
ALTER TABLE `daily_checkin`
  ADD CONSTRAINT `FK_3E82CD65A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);

--
-- Contraintes pour la table `habitude`
--
ALTER TABLE `habitude`
  ADD CONSTRAINT `FK_10DD3E5FA76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `FK_10DD3E5FBCF5E72D` FOREIGN KEY (`categorie_id`) REFERENCES `categorie` (`id`);

--
-- Contraintes pour la table `habit_completions`
--
ALTER TABLE `habit_completions`
  ADD CONSTRAINT `FK_F0AE56A4A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `FK_F0AE56A4B80619B9` FOREIGN KEY (`habitude_id`) REFERENCES `habitude` (`id`);

--
-- Contraintes pour la table `habit_streaks`
--
ALTER TABLE `habit_streaks`
  ADD CONSTRAINT `FK_28B91E24A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `FK_28B91E24B80619B9` FOREIGN KEY (`habitude_id`) REFERENCES `habitude` (`id`);

--
-- Contraintes pour la table `mental_entries`
--
ALTER TABLE `mental_entries`
  ADD CONSTRAINT `FK_CE38E1B0A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);

--
-- Contraintes pour la table `mental_tips`
--
ALTER TABLE `mental_tips`
  ADD CONSTRAINT `FK_D66E2EC5BCF5E72D` FOREIGN KEY (`categorie_id`) REFERENCES `categorie` (`id`);

--
-- Contraintes pour la table `moods`
--
ALTER TABLE `moods`
  ADD CONSTRAINT `FK_4FD3A18CA76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);

--
-- Contraintes pour la table `notification_log`
--
ALTER TABLE `notification_log`
  ADD CONSTRAINT `FK_ED15DF2A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);

--
-- Contraintes pour la table `post`
--
ALTER TABLE `post`
  ADD CONSTRAINT `FK_5A8A6C8DA76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `FK_5A8A6C8DBCF5E72D` FOREIGN KEY (`categorie_id`) REFERENCES `categorie` (`id`);

--
-- Contraintes pour la table `post_share`
--
ALTER TABLE `post_share`
  ADD CONSTRAINT `FK_781D11B54B89032C` FOREIGN KEY (`post_id`) REFERENCES `post` (`id`),
  ADD CONSTRAINT `FK_781D11B5A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);

--
-- Contraintes pour la table `reclamation`
--
ALTER TABLE `reclamation`
  ADD CONSTRAINT `FK_CE606404A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);

--
-- Contraintes pour la table `reclamation_event`
--
ALTER TABLE `reclamation_event`
  ADD CONSTRAINT `FK_E6A239E92D6BA2D9` FOREIGN KEY (`reclamation_id`) REFERENCES `reclamation` (`id`),
  ADD CONSTRAINT `FK_E6A239E9A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);

--
-- Contraintes pour la table `team`
--
ALTER TABLE `team`
  ADD CONSTRAINT `FK_C4E0A61FB03A8386` FOREIGN KEY (`created_by_id`) REFERENCES `user` (`id`);

--
-- Contraintes pour la table `team_member`
--
ALTER TABLE `team_member`
  ADD CONSTRAINT `FK_6FFBDA1296CD8AE` FOREIGN KEY (`team_id`) REFERENCES `team` (`id`),
  ADD CONSTRAINT `FK_6FFBDA1A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);

--
-- Contraintes pour la table `workout_plan`
--
ALTER TABLE `workout_plan`
  ADD CONSTRAINT `FK_A5D45801A6CCCFC9` FOREIGN KEY (`workout_id`) REFERENCES `workout` (`id`),
  ADD CONSTRAINT `FK_A5D45801A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);

--
-- Contraintes pour la table `workout_progress`
--
ALTER TABLE `workout_progress`
  ADD CONSTRAINT `FK_FA701FF0A6CCCFC9` FOREIGN KEY (`workout_id`) REFERENCES `workout` (`id`),
  ADD CONSTRAINT `FK_FA701FF0A76ED395` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
