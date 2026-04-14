<?php

$dbUrl = "mysql:host=127.0.0.1;dbname=psy_db;charset=utf8mb4";
$user = "root";
$pass = "";

try {
    $pdo = new PDO($dbUrl, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Challenge Participation
    $pdo->exec("CREATE TABLE IF NOT EXISTS challenge_participation (
        id INT AUTO_INCREMENT NOT NULL,
        user_id INT NOT NULL,
        challenge_id INT NOT NULL,
        statut VARCHAR(20) NOT NULL,
        created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
        completed_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
        INDEX IDX_USER (user_id),
        INDEX IDX_CHALLENGE (challenge_id),
        PRIMARY KEY(id),
        CONSTRAINT FK_CP_USER FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE,
        CONSTRAINT FK_CP_CHALLENGE FOREIGN KEY (challenge_id) REFERENCES challenge (id) ON DELETE CASCADE
    ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB");

    // 2. User Challenge Task
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_challenge_task (
        id INT AUTO_INCREMENT NOT NULL,
        user_id INT NOT NULL,
        task_id INT NOT NULL,
        is_done TINYINT(1) NOT NULL,
        completed_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
        INDEX IDX_UCT_USER (user_id),
        INDEX IDX_UCT_TASK (task_id),
        PRIMARY KEY(id),
        CONSTRAINT FK_UCT_USER FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE,
        CONSTRAINT FK_UCT_TASK FOREIGN KEY (task_id) REFERENCES challenge_task (id) ON DELETE CASCADE
    ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB");

    // 3. User Recompense
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_recompense (
        id INT AUTO_INCREMENT NOT NULL,
        user_id INT NOT NULL,
        recompense_id INT NOT NULL,
        source_challenge_id INT DEFAULT NULL,
        unlocked_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
        INDEX IDX_UR_USER (user_id),
        INDEX IDX_UR_RECOMPENSE (recompense_id),
        INDEX IDX_UR_SOURCECHAL (source_challenge_id),
        PRIMARY KEY(id),
        CONSTRAINT FK_UR_USER FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE,
        CONSTRAINT FK_UR_RECOMPENSE FOREIGN KEY (recompense_id) REFERENCES recompense (id) ON DELETE CASCADE,
        CONSTRAINT FK_UR_SOURCE_CHALLENGE FOREIGN KEY (source_challenge_id) REFERENCES challenge (id) ON DELETE SET NULL
    ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB");

    echo "Tables créées avec succès.";

} catch (PDOException $e) {
    echo "Erreur: " . $e->getMessage();
}
