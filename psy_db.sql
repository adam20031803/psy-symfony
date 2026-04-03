-- ============================================================
-- PSY_DB - Script de création des tables
-- Copiez-collez ce code dans phpMyAdmin > SQL
-- Base de données : psy_db
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

-- ============================================================
-- TABLE : user (créée EN PREMIER car référencée par les autres)
-- ============================================================
CREATE TABLE IF NOT EXISTS `user` (
    id          INT AUTO_INCREMENT NOT NULL,
    nom         VARCHAR(100) NOT NULL,
    prenom      VARCHAR(100) NOT NULL,
    email       VARCHAR(180) NOT NULL,
    password    VARCHAR(255) NOT NULL,
    role        VARCHAR(20)  DEFAULT 'user' NOT NULL,
    photo       VARCHAR(255) DEFAULT NULL,
    created_at  DATETIME     NOT NULL,
    is_active   TINYINT      DEFAULT 1 NOT NULL,
    UNIQUE INDEX UNIQ_8D93D649E7927C74 (email),
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8mb4;

-- ============================================================
-- TABLE : categorie
-- ============================================================
CREATE TABLE IF NOT EXISTS categorie (
    id          INT AUTO_INCREMENT NOT NULL,
    nom         VARCHAR(100) NOT NULL,
    description LONGTEXT DEFAULT NULL,
    icon        VARCHAR(100) DEFAULT NULL,
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8mb4;

-- ============================================================
-- TABLE : app_settings
-- ============================================================
CREATE TABLE IF NOT EXISTS app_settings (
    id          INT AUTO_INCREMENT NOT NULL,
    cle         VARCHAR(100) NOT NULL,
    valeur      LONGTEXT NOT NULL,
    description LONGTEXT DEFAULT NULL,
    UNIQUE INDEX UNIQ_EDC5C06841401D17 (cle),
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8mb4;

-- ============================================================
-- TABLE : recompense
-- ============================================================
CREATE TABLE IF NOT EXISTS recompense (
    id          INT AUTO_INCREMENT NOT NULL,
    nom         VARCHAR(255) NOT NULL,
    description LONGTEXT NOT NULL,
    points      INT NOT NULL,
    image       VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8mb4;

-- ============================================================
-- TABLE : workout
-- ============================================================
CREATE TABLE IF NOT EXISTS workout (
    id          INT AUTO_INCREMENT NOT NULL,
    nom         VARCHAR(255) NOT NULL,
    description LONGTEXT NOT NULL,
    duree       INT NOT NULL,
    type        VARCHAR(100) NOT NULL,
    niveau      VARCHAR(50) NOT NULL,
    image       VARCHAR(255) DEFAULT NULL,
    created_at  DATETIME NOT NULL,
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8mb4;

-- ============================================================
-- TABLE : challenge
-- ============================================================
CREATE TABLE IF NOT EXISTS challenge (
    id              INT AUTO_INCREMENT NOT NULL,
    titre           VARCHAR(255) NOT NULL,
    description     LONGTEXT NOT NULL,
    date_debut      DATE NOT NULL,
    date_fin        DATE NOT NULL,
    statut          VARCHAR(50) DEFAULT 'actif' NOT NULL,
    created_at      DATETIME NOT NULL,
    categorie_id    INT DEFAULT NULL,
    created_by_id   INT NOT NULL,
    INDEX IDX_D7098951BCF5E72D (categorie_id),
    INDEX IDX_D7098951B03A8386 (created_by_id),
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8mb4;

-- ============================================================
-- TABLE : challenge_chat
-- ============================================================
CREATE TABLE IF NOT EXISTS challenge_chat (
    id              INT AUTO_INCREMENT NOT NULL,
    message         LONGTEXT NOT NULL,
    created_at      DATETIME NOT NULL,
    challenge_id    INT NOT NULL,
    user_id         INT NOT NULL,
    INDEX IDX_6C32F52C98A21AC6 (challenge_id),
    INDEX IDX_6C32F52CA76ED395 (user_id),
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8mb4;

-- ============================================================
-- TABLE : challenge_coach
-- ============================================================
CREATE TABLE IF NOT EXISTS challenge_coach (
    id              INT AUTO_INCREMENT NOT NULL,
    assigned_at     DATETIME NOT NULL,
    challenge_id    INT NOT NULL,
    coach_id        INT NOT NULL,
    INDEX IDX_3B8BE4DE98A21AC6 (challenge_id),
    INDEX IDX_3B8BE4DE3C105691 (coach_id),
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8mb4;

-- ============================================================
-- TABLE : challenge_recompense
-- ============================================================
CREATE TABLE IF NOT EXISTS challenge_recompense (
    id              INT AUTO_INCREMENT NOT NULL,
    challenge_id    INT NOT NULL,
    recompense_id   INT NOT NULL,
    INDEX IDX_7E8BD9D798A21AC6 (challenge_id),
    INDEX IDX_7E8BD9D74D714096 (recompense_id),
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8mb4;

-- ============================================================
-- TABLE : coach_motivation
-- ============================================================
CREATE TABLE IF NOT EXISTS coach_motivation (
    id          INT AUTO_INCREMENT NOT NULL,
    message     LONGTEXT NOT NULL,
    created_at  DATETIME NOT NULL,
    coach_id    INT NOT NULL,
    INDEX IDX_8E9D041E3C105691 (coach_id),
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8mb4;

-- ============================================================
-- TABLE : commentaire
-- ============================================================
CREATE TABLE IF NOT EXISTS commentaire (
    id          INT AUTO_INCREMENT NOT NULL,
    contenu     LONGTEXT NOT NULL,
    created_at  DATETIME NOT NULL,
    post_id     INT NOT NULL,
    user_id     INT NOT NULL,
    INDEX IDX_67F068BC4B89032C (post_id),
    INDEX IDX_67F068BCA76ED395 (user_id),
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8mb4;

-- ============================================================
-- TABLE : daily_checkin
-- ============================================================
CREATE TABLE IF NOT EXISTS daily_checkin (
    id          INT AUTO_INCREMENT NOT NULL,
    date        DATE NOT NULL,
    mood_score  INT NOT NULL,
    notes       LONGTEXT DEFAULT NULL,
    created_at  DATETIME NOT NULL,
    user_id     INT NOT NULL,
    INDEX IDX_3E82CD65A76ED395 (user_id),
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8mb4;

-- ============================================================
-- TABLE : habitude
-- ============================================================
CREATE TABLE IF NOT EXISTS habitude (
    id              INT AUTO_INCREMENT NOT NULL,
    nom             VARCHAR(255) NOT NULL,
    description     LONGTEXT DEFAULT NULL,
    frequence       VARCHAR(50) NOT NULL,
    created_at      DATETIME NOT NULL,
    is_active       TINYINT DEFAULT 1 NOT NULL,
    user_id         INT NOT NULL,
    categorie_id    INT DEFAULT NULL,
    INDEX IDX_10DD3E5FA76ED395 (user_id),
    INDEX IDX_10DD3E5FBCF5E72D (categorie_id),
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8mb4;

-- ============================================================
-- TABLE : habit_completions
-- ============================================================
CREATE TABLE IF NOT EXISTS habit_completions (
    id              INT AUTO_INCREMENT NOT NULL,
    completed_at    DATETIME NOT NULL,
    notes           LONGTEXT DEFAULT NULL,
    habitude_id     INT NOT NULL,
    user_id         INT NOT NULL,
    INDEX IDX_F0AE56A4B80619B9 (habitude_id),
    INDEX IDX_F0AE56A4A76ED395 (user_id),
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8mb4;

-- ============================================================
-- TABLE : habit_streaks
-- ============================================================
CREATE TABLE IF NOT EXISTS habit_streaks (
    id              INT AUTO_INCREMENT NOT NULL,
    current_streak  INT DEFAULT 0 NOT NULL,
    longest_streak  INT DEFAULT 0 NOT NULL,
    last_completed  DATE DEFAULT NULL,
    habitude_id     INT NOT NULL,
    user_id         INT NOT NULL,
    UNIQUE INDEX UNIQ_28B91E24B80619B9 (habitude_id),
    INDEX IDX_28B91E24A76ED395 (user_id),
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8mb4;

-- ============================================================
-- TABLE : mental_entries
-- ============================================================
CREATE TABLE IF NOT EXISTS mental_entries (
    id          INT AUTO_INCREMENT NOT NULL,
    contenu     LONGTEXT NOT NULL,
    humeur      VARCHAR(50) DEFAULT NULL,
    created_at  DATETIME NOT NULL,
    updated_at  DATETIME DEFAULT NULL,
    user_id     INT NOT NULL,
    INDEX IDX_CE38E1B0A76ED395 (user_id),
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8mb4;

-- ============================================================
-- TABLE : mental_tips
-- ============================================================
CREATE TABLE IF NOT EXISTS mental_tips (
    id              INT AUTO_INCREMENT NOT NULL,
    titre           VARCHAR(255) NOT NULL,
    contenu         LONGTEXT NOT NULL,
    created_at      DATETIME NOT NULL,
    categorie_id    INT DEFAULT NULL,
    INDEX IDX_D66E2EC5BCF5E72D (categorie_id),
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8mb4;

-- ============================================================
-- TABLE : moods
-- ============================================================
CREATE TABLE IF NOT EXISTS moods (
    id          INT AUTO_INCREMENT NOT NULL,
    humeur      VARCHAR(50) NOT NULL,
    score       INT DEFAULT NULL,
    notes       LONGTEXT DEFAULT NULL,
    created_at  DATETIME NOT NULL,
    user_id     INT NOT NULL,
    INDEX IDX_4FD3A18CA76ED395 (user_id),
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8mb4;

-- ============================================================
-- TABLE : notification_log
-- ============================================================
CREATE TABLE IF NOT EXISTS notification_log (
    id          INT AUTO_INCREMENT NOT NULL,
    message     VARCHAR(255) NOT NULL,
    type        VARCHAR(50) NOT NULL,
    is_read     TINYINT DEFAULT 0 NOT NULL,
    created_at  DATETIME NOT NULL,
    user_id     INT NOT NULL,
    INDEX IDX_ED15DF2A76ED395 (user_id),
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8mb4;

-- ============================================================
-- TABLE : post
-- ============================================================
CREATE TABLE IF NOT EXISTS post (
    id              INT AUTO_INCREMENT NOT NULL,
    titre           VARCHAR(255) NOT NULL,
    contenu         LONGTEXT NOT NULL,
    image           VARCHAR(255) DEFAULT NULL,
    is_anonymous    TINYINT DEFAULT 0 NOT NULL,
    created_at      DATETIME NOT NULL,
    updated_at      DATETIME DEFAULT NULL,
    user_id         INT NOT NULL,
    categorie_id    INT DEFAULT NULL,
    INDEX IDX_5A8A6C8DA76ED395 (user_id),
    INDEX IDX_5A8A6C8DBCF5E72D (categorie_id),
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8mb4;

-- ============================================================
-- TABLE : post_share
-- ============================================================
CREATE TABLE IF NOT EXISTS post_share (
    id          INT AUTO_INCREMENT NOT NULL,
    shared_at   DATETIME NOT NULL,
    post_id     INT NOT NULL,
    user_id     INT NOT NULL,
    INDEX IDX_781D11B54B89032C (post_id),
    INDEX IDX_781D11B5A76ED395 (user_id),
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8mb4;

-- ============================================================
-- TABLE : reclamation
-- ============================================================
CREATE TABLE IF NOT EXISTS reclamation (
    id          INT AUTO_INCREMENT NOT NULL,
    sujet       VARCHAR(255) NOT NULL,
    description LONGTEXT NOT NULL,
    statut      VARCHAR(50) DEFAULT 'ouvert' NOT NULL,
    created_at  DATETIME NOT NULL,
    updated_at  DATETIME DEFAULT NULL,
    user_id     INT NOT NULL,
    INDEX IDX_CE606404A76ED395 (user_id),
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8mb4;

-- ============================================================
-- TABLE : reclamation_event
-- ============================================================
CREATE TABLE IF NOT EXISTS reclamation_event (
    id              INT AUTO_INCREMENT NOT NULL,
    action          VARCHAR(100) NOT NULL,
    commentaire     LONGTEXT DEFAULT NULL,
    created_at      DATETIME NOT NULL,
    reclamation_id  INT NOT NULL,
    user_id         INT NOT NULL,
    INDEX IDX_E6A239E92D6BA2D9 (reclamation_id),
    INDEX IDX_E6A239E9A76ED395 (user_id),
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8mb4;

-- ============================================================
-- TABLE : team
-- ============================================================
CREATE TABLE IF NOT EXISTS team (
    id              INT AUTO_INCREMENT NOT NULL,
    nom             VARCHAR(255) NOT NULL,
    description     LONGTEXT DEFAULT NULL,
    created_at      DATETIME NOT NULL,
    created_by_id   INT NOT NULL,
    INDEX IDX_C4E0A61FB03A8386 (created_by_id),
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8mb4;

-- ============================================================
-- TABLE : team_member
-- ============================================================
CREATE TABLE IF NOT EXISTS team_member (
    id          INT AUTO_INCREMENT NOT NULL,
    role        VARCHAR(50) DEFAULT 'member' NOT NULL,
    joined_at   DATETIME NOT NULL,
    team_id     INT NOT NULL,
    user_id     INT NOT NULL,
    INDEX IDX_6FFBDA1296CD8AE (team_id),
    INDEX IDX_6FFBDA1A76ED395 (user_id),
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8mb4;

-- ============================================================
-- TABLE : workout_plan
-- ============================================================
CREATE TABLE IF NOT EXISTS workout_plan (
    id              INT AUTO_INCREMENT NOT NULL,
    date_planifie   DATE NOT NULL,
    statut          VARCHAR(50) DEFAULT 'planifie' NOT NULL,
    created_at      DATETIME NOT NULL,
    user_id         INT NOT NULL,
    workout_id      INT NOT NULL,
    INDEX IDX_A5D45801A76ED395 (user_id),
    INDEX IDX_A5D45801A6CCCFC9 (workout_id),
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8mb4;

-- ============================================================
-- TABLE : workout_progress
-- ============================================================
CREATE TABLE IF NOT EXISTS workout_progress (
    id              INT AUTO_INCREMENT NOT NULL,
    date            DATE NOT NULL,
    duree_reelle    INT DEFAULT NULL,
    notes           LONGTEXT DEFAULT NULL,
    created_at      DATETIME NOT NULL,
    user_id         INT NOT NULL,
    workout_id      INT NOT NULL,
    INDEX IDX_FA701FF0A76ED395 (user_id),
    INDEX IDX_FA701FF0A6CCCFC9 (workout_id),
    PRIMARY KEY (id)
) DEFAULT CHARACTER SET utf8mb4;

-- ============================================================
-- TABLE : doctrine_migration_versions (pour Symfony)
-- ============================================================
CREATE TABLE IF NOT EXISTS doctrine_migration_versions (
    version        VARCHAR(191) NOT NULL,
    executed_at    DATETIME DEFAULT NULL,
    execution_time INT DEFAULT NULL,
    PRIMARY KEY (version)
) DEFAULT CHARACTER SET utf8mb4;

-- Marquer la migration comme déjà exécutée
INSERT IGNORE INTO doctrine_migration_versions (version, executed_at, execution_time)
VALUES ('DoctrineMigrations\\Version20260403143126', NOW(), 100);

-- ============================================================
-- CLÉS ÉTRANGÈRES (Foreign Keys)
-- ============================================================
ALTER TABLE challenge
    ADD CONSTRAINT FK_D7098951BCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie (id),
    ADD CONSTRAINT FK_D7098951B03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id);

ALTER TABLE challenge_chat
    ADD CONSTRAINT FK_6C32F52C98A21AC6 FOREIGN KEY (challenge_id) REFERENCES challenge (id),
    ADD CONSTRAINT FK_6C32F52CA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id);

ALTER TABLE challenge_coach
    ADD CONSTRAINT FK_3B8BE4DE98A21AC6 FOREIGN KEY (challenge_id) REFERENCES challenge (id),
    ADD CONSTRAINT FK_3B8BE4DE3C105691 FOREIGN KEY (coach_id) REFERENCES `user` (id);

ALTER TABLE challenge_recompense
    ADD CONSTRAINT FK_7E8BD9D798A21AC6 FOREIGN KEY (challenge_id) REFERENCES challenge (id),
    ADD CONSTRAINT FK_7E8BD9D74D714096 FOREIGN KEY (recompense_id) REFERENCES recompense (id);

ALTER TABLE coach_motivation
    ADD CONSTRAINT FK_8E9D041E3C105691 FOREIGN KEY (coach_id) REFERENCES `user` (id);

ALTER TABLE commentaire
    ADD CONSTRAINT FK_67F068BC4B89032C FOREIGN KEY (post_id) REFERENCES post (id),
    ADD CONSTRAINT FK_67F068BCA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id);

ALTER TABLE daily_checkin
    ADD CONSTRAINT FK_3E82CD65A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id);

ALTER TABLE habit_completions
    ADD CONSTRAINT FK_F0AE56A4B80619B9 FOREIGN KEY (habitude_id) REFERENCES habitude (id),
    ADD CONSTRAINT FK_F0AE56A4A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id);

ALTER TABLE habit_streaks
    ADD CONSTRAINT FK_28B91E24B80619B9 FOREIGN KEY (habitude_id) REFERENCES habitude (id),
    ADD CONSTRAINT FK_28B91E24A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id);

ALTER TABLE habitude
    ADD CONSTRAINT FK_10DD3E5FA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id),
    ADD CONSTRAINT FK_10DD3E5FBCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie (id);

ALTER TABLE mental_entries
    ADD CONSTRAINT FK_CE38E1B0A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id);

ALTER TABLE mental_tips
    ADD CONSTRAINT FK_D66E2EC5BCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie (id);

ALTER TABLE moods
    ADD CONSTRAINT FK_4FD3A18CA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id);

ALTER TABLE notification_log
    ADD CONSTRAINT FK_ED15DF2A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id);

ALTER TABLE post
    ADD CONSTRAINT FK_5A8A6C8DA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id),
    ADD CONSTRAINT FK_5A8A6C8DBCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie (id);

ALTER TABLE post_share
    ADD CONSTRAINT FK_781D11B54B89032C FOREIGN KEY (post_id) REFERENCES post (id),
    ADD CONSTRAINT FK_781D11B5A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id);

ALTER TABLE reclamation
    ADD CONSTRAINT FK_CE606404A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id);

ALTER TABLE reclamation_event
    ADD CONSTRAINT FK_E6A239E92D6BA2D9 FOREIGN KEY (reclamation_id) REFERENCES reclamation (id),
    ADD CONSTRAINT FK_E6A239E9A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id);

ALTER TABLE team
    ADD CONSTRAINT FK_C4E0A61FB03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id);

ALTER TABLE team_member
    ADD CONSTRAINT FK_6FFBDA1296CD8AE FOREIGN KEY (team_id) REFERENCES team (id),
    ADD CONSTRAINT FK_6FFBDA1A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id);

ALTER TABLE workout_plan
    ADD CONSTRAINT FK_A5D45801A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id),
    ADD CONSTRAINT FK_A5D45801A6CCCFC9 FOREIGN KEY (workout_id) REFERENCES workout (id);

ALTER TABLE workout_progress
    ADD CONSTRAINT FK_FA701FF0A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id),
    ADD CONSTRAINT FK_FA701FF0A6CCCFC9 FOREIGN KEY (workout_id) REFERENCES workout (id);

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- DONNÉES DE TEST (optionnel - décommenter si besoin)
-- ============================================================
-- INSERT INTO `user` (nom, prenom, email, password, role, created_at, is_active)
-- VALUES ('Admin', 'Super', 'admin@psy.com', '$2y$13$hash', 'admin', NOW(), 1);

-- INSERT INTO categorie (nom, description, icon)
-- VALUES ('Stress', 'Gestion du stress', 'fa-brain'),
--        ('Sommeil', 'Améliorer le sommeil', 'fa-moon'),
--        ('Sport', 'Activité physique', 'fa-dumbbell');

SELECT '✅ 27 tables créées avec succès dans psy_db !' AS resultat;
