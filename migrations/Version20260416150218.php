<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260416150218 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE ai_conversation (id INT AUTO_INCREMENT NOT NULL, session_token VARCHAR(64) NOT NULL, phase VARCHAR(30) NOT NULL, mood_score INT DEFAULT NULL, energy_level INT DEFAULT NULL, personality_type VARCHAR(50) DEFAULT NULL, communication_style VARCHAR(30) DEFAULT NULL, total_messages INT NOT NULL, insights_json LONGTEXT DEFAULT NULL, goals_json LONGTEXT DEFAULT NULL, habits_json LONGTEXT DEFAULT NULL, blockers_json LONGTEXT DEFAULT NULL, strengths_json LONGTEXT DEFAULT NULL, last_activity_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, UNIQUE INDEX UNIQ_D13CEF47844A19ED (session_token), INDEX IDX_D13CEF47A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE ai_insight (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(30) NOT NULL, title VARCHAR(255) NOT NULL, content LONGTEXT NOT NULL, action_suggested LONGTEXT DEFAULT NULL, confidence DOUBLE PRECISION DEFAULT NULL, is_read TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX IDX_C2311D08A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE ai_message (id INT AUTO_INCREMENT NOT NULL, role VARCHAR(10) NOT NULL, content LONGTEXT NOT NULL, message_type VARCHAR(30) NOT NULL, quick_replies LONGTEXT DEFAULT NULL, emotion_detected VARCHAR(30) DEFAULT NULL, data_collected LONGTEXT DEFAULT NULL, is_read TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, conversation_id INT NOT NULL, INDEX IDX_8AB83EAC9AC0396 (conversation_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE ai_user_profile (id INT AUTO_INCREMENT NOT NULL, wake_up_time VARCHAR(10) DEFAULT NULL, sleep_time VARCHAR(10) DEFAULT NULL, work_schedule VARCHAR(50) DEFAULT NULL, exercise_frequency VARCHAR(30) DEFAULT NULL, stress_triggers LONGTEXT DEFAULT NULL, motivation_drivers LONGTEXT DEFAULT NULL, top_challenges LONGTEXT DEFAULT NULL, achievements LONGTEXT DEFAULT NULL, personal_values LONGTEXT DEFAULT NULL, current_mood_avg DOUBLE PRECISION DEFAULT NULL, streak_days INT NOT NULL, total_xp INT NOT NULL, level INT NOT NULL, badges_json LONGTEXT DEFAULT NULL, last_checkin_at DATETIME DEFAULT NULL, onboarding_complete TINYINT(1) NOT NULL, updated_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, UNIQUE INDEX UNIQ_82E87DABA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE challenge_participation (id INT AUTO_INCREMENT NOT NULL, statut VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, completed_at DATETIME DEFAULT NULL, user_id INT NOT NULL, challenge_id INT NOT NULL, INDEX IDX_223360DCA76ED395 (user_id), INDEX IDX_223360DC98A21AC6 (challenge_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE challenge_task (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, why_recommended LONGTEXT DEFAULT NULL, completion_criteria LONGTEXT DEFAULT NULL, difficulty INT NOT NULL, estimated_minutes INT NOT NULL, points INT NOT NULL, sort_order INT NOT NULL, progress_pct INT NOT NULL, done TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, challenge_id INT NOT NULL, INDEX IDX_5BD1DCA398A21AC6 (challenge_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user_challenge_task (id INT AUTO_INCREMENT NOT NULL, is_done TINYINT(1) NOT NULL, completed_at DATETIME NOT NULL, user_id INT NOT NULL, task_id INT NOT NULL, INDEX IDX_305403FAA76ED395 (user_id), INDEX IDX_305403FA8DB60186 (task_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user_recompense (id INT AUTO_INCREMENT NOT NULL, unlocked_at DATETIME NOT NULL, user_id INT NOT NULL, recompense_id INT NOT NULL, source_challenge_id INT DEFAULT NULL, INDEX IDX_B9FC0632A76ED395 (user_id), INDEX IDX_B9FC06324D714096 (recompense_id), INDEX IDX_B9FC06324E48C67E (source_challenge_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE ai_conversation ADD CONSTRAINT FK_D13CEF47A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE ai_insight ADD CONSTRAINT FK_C2311D08A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE ai_message ADD CONSTRAINT FK_8AB83EAC9AC0396 FOREIGN KEY (conversation_id) REFERENCES ai_conversation (id)');
        $this->addSql('ALTER TABLE ai_user_profile ADD CONSTRAINT FK_82E87DABA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE challenge_participation ADD CONSTRAINT FK_223360DCA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE challenge_participation ADD CONSTRAINT FK_223360DC98A21AC6 FOREIGN KEY (challenge_id) REFERENCES challenge (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE challenge_task ADD CONSTRAINT FK_5BD1DCA398A21AC6 FOREIGN KEY (challenge_id) REFERENCES challenge (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_challenge_task ADD CONSTRAINT FK_305403FAA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_challenge_task ADD CONSTRAINT FK_305403FA8DB60186 FOREIGN KEY (task_id) REFERENCES challenge_task (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_recompense ADD CONSTRAINT FK_B9FC0632A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_recompense ADD CONSTRAINT FK_B9FC06324D714096 FOREIGN KEY (recompense_id) REFERENCES recompense (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_recompense ADD CONSTRAINT FK_B9FC06324E48C67E FOREIGN KEY (source_challenge_id) REFERENCES challenge (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE daily_checkin DROP FOREIGN KEY FK_3E82CD65A76ED395');
        $this->addSql('DROP INDEX fk_3e82cd65a76ed395 ON daily_checkin');
        $this->addSql('CREATE INDEX IDX_3E82CD65A76ED395 ON daily_checkin (user_id)');
        $this->addSql('ALTER TABLE daily_checkin ADD CONSTRAINT FK_3E82CD65A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE habitude DROP FOREIGN KEY FK_8A9C4A5CA76ED395');
        $this->addSql('DROP INDEX idx_8a9c4a5ca76ed395 ON habitude');
        $this->addSql('CREATE INDEX IDX_10DD3E5FA76ED395 ON habitude (user_id)');
        $this->addSql('ALTER TABLE habitude ADD CONSTRAINT FK_8A9C4A5CA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE post_like DROP FOREIGN KEY fk_pl_user');
        $this->addSql('DROP INDEX fk_pl_user ON post_like');
        $this->addSql('CREATE INDEX IDX_653627B8A76ED395 ON post_like (user_id)');
        $this->addSql('ALTER TABLE post_like ADD CONSTRAINT fk_pl_user FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE post_share DROP FOREIGN KEY FK_781D11B5A76ED395');
        $this->addSql('ALTER TABLE post_share DROP FOREIGN KEY FK_781D11B54B89032C');
        $this->addSql('ALTER TABLE post_share ADD CONSTRAINT FK_781D11B5A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE post_share ADD CONSTRAINT FK_781D11B54B89032C FOREIGN KEY (post_id) REFERENCES post (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE program_exercise DROP FOREIGN KEY FK_2FEF2929E934951A');
        $this->addSql('ALTER TABLE program_exercise DROP FOREIGN KEY FK_762E40723EB8070A');
        $this->addSql('ALTER TABLE smart_meeting ADD CONSTRAINT FK_94616A8798A21AC6 FOREIGN KEY (challenge_id) REFERENCES challenge (id)');
        $this->addSql('DROP INDEX UNIQ_8D93D6496B7BA4B6 ON user');
        $this->addSql('ALTER TABLE user DROP password_reset_token, DROP password_reset_requested_at, DROP cv');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ai_conversation DROP FOREIGN KEY FK_D13CEF47A76ED395');
        $this->addSql('ALTER TABLE ai_insight DROP FOREIGN KEY FK_C2311D08A76ED395');
        $this->addSql('ALTER TABLE ai_message DROP FOREIGN KEY FK_8AB83EAC9AC0396');
        $this->addSql('ALTER TABLE ai_user_profile DROP FOREIGN KEY FK_82E87DABA76ED395');
        $this->addSql('ALTER TABLE challenge_participation DROP FOREIGN KEY FK_223360DCA76ED395');
        $this->addSql('ALTER TABLE challenge_participation DROP FOREIGN KEY FK_223360DC98A21AC6');
        $this->addSql('ALTER TABLE challenge_task DROP FOREIGN KEY FK_5BD1DCA398A21AC6');
        $this->addSql('ALTER TABLE user_challenge_task DROP FOREIGN KEY FK_305403FAA76ED395');
        $this->addSql('ALTER TABLE user_challenge_task DROP FOREIGN KEY FK_305403FA8DB60186');
        $this->addSql('ALTER TABLE user_recompense DROP FOREIGN KEY FK_B9FC0632A76ED395');
        $this->addSql('ALTER TABLE user_recompense DROP FOREIGN KEY FK_B9FC06324D714096');
        $this->addSql('ALTER TABLE user_recompense DROP FOREIGN KEY FK_B9FC06324E48C67E');
        $this->addSql('DROP TABLE ai_conversation');
        $this->addSql('DROP TABLE ai_insight');
        $this->addSql('DROP TABLE ai_message');
        $this->addSql('DROP TABLE ai_user_profile');
        $this->addSql('DROP TABLE challenge_participation');
        $this->addSql('DROP TABLE challenge_task');
        $this->addSql('DROP TABLE user_challenge_task');
        $this->addSql('DROP TABLE user_recompense');
        $this->addSql('ALTER TABLE daily_checkin DROP FOREIGN KEY FK_3E82CD65A76ED395');
        $this->addSql('DROP INDEX idx_3e82cd65a76ed395 ON daily_checkin');
        $this->addSql('CREATE INDEX FK_3E82CD65A76ED395 ON daily_checkin (user_id)');
        $this->addSql('ALTER TABLE daily_checkin ADD CONSTRAINT FK_3E82CD65A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE habitude DROP FOREIGN KEY FK_10DD3E5FA76ED395');
        $this->addSql('DROP INDEX idx_10dd3e5fa76ed395 ON habitude');
        $this->addSql('CREATE INDEX IDX_8A9C4A5CA76ED395 ON habitude (user_id)');
        $this->addSql('ALTER TABLE habitude ADD CONSTRAINT FK_10DD3E5FA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE post_like DROP FOREIGN KEY FK_653627B8A76ED395');
        $this->addSql('DROP INDEX idx_653627b8a76ed395 ON post_like');
        $this->addSql('CREATE INDEX fk_pl_user ON post_like (user_id)');
        $this->addSql('ALTER TABLE post_like ADD CONSTRAINT FK_653627B8A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE post_share DROP FOREIGN KEY FK_781D11B54B89032C');
        $this->addSql('ALTER TABLE post_share DROP FOREIGN KEY FK_781D11B5A76ED395');
        $this->addSql('ALTER TABLE post_share ADD CONSTRAINT FK_781D11B54B89032C FOREIGN KEY (post_id) REFERENCES post (id)');
        $this->addSql('ALTER TABLE post_share ADD CONSTRAINT FK_781D11B5A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE smart_meeting DROP FOREIGN KEY FK_94616A8798A21AC6');
        $this->addSql('ALTER TABLE user ADD password_reset_token VARCHAR(100) DEFAULT NULL, ADD password_reset_requested_at DATETIME DEFAULT NULL, ADD cv VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D6496B7BA4B6 ON user (password_reset_token)');
    }
}
