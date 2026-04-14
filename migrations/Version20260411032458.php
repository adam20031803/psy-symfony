<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260411032458 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ai_insight DROP FOREIGN KEY FK_C2311D08A76ED395');
        $this->addSql('DROP INDEX idx_insight_user ON ai_insight');
        $this->addSql('CREATE INDEX IDX_C2311D08A76ED395 ON ai_insight (user_id)');
        $this->addSql('ALTER TABLE ai_insight ADD CONSTRAINT FK_C2311D08A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE ai_message DROP FOREIGN KEY FK_msg_conv');
        $this->addSql('ALTER TABLE ai_message DROP FOREIGN KEY FK_msg_conv');
        $this->addSql('ALTER TABLE ai_message CHANGE role role VARCHAR(10) NOT NULL, CHANGE message_type message_type VARCHAR(30) NOT NULL, CHANGE quick_replies quick_replies LONGTEXT DEFAULT NULL, CHANGE emotion_detected emotion_detected VARCHAR(30) DEFAULT NULL, CHANGE data_collected data_collected LONGTEXT DEFAULT NULL, CHANGE is_read is_read TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE ai_message ADD CONSTRAINT FK_8AB83EAC9AC0396 FOREIGN KEY (conversation_id) REFERENCES ai_conversation (id)');
        $this->addSql('DROP INDEX idx_msg_conv ON ai_message');
        $this->addSql('CREATE INDEX IDX_8AB83EAC9AC0396 ON ai_message (conversation_id)');
        $this->addSql('ALTER TABLE ai_message ADD CONSTRAINT FK_msg_conv FOREIGN KEY (conversation_id) REFERENCES ai_conversation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ai_user_profile DROP FOREIGN KEY FK_profile_user');
        $this->addSql('ALTER TABLE ai_user_profile DROP FOREIGN KEY FK_profile_user');
        $this->addSql('ALTER TABLE ai_user_profile ADD personal_values LONGTEXT DEFAULT NULL, DROP `values`, CHANGE wake_up_time wake_up_time VARCHAR(10) DEFAULT NULL, CHANGE sleep_time sleep_time VARCHAR(10) DEFAULT NULL, CHANGE work_schedule work_schedule VARCHAR(50) DEFAULT NULL, CHANGE exercise_frequency exercise_frequency VARCHAR(30) DEFAULT NULL, CHANGE stress_triggers stress_triggers LONGTEXT DEFAULT NULL, CHANGE motivation_drivers motivation_drivers LONGTEXT DEFAULT NULL, CHANGE top_challenges top_challenges LONGTEXT DEFAULT NULL, CHANGE achievements achievements LONGTEXT DEFAULT NULL, CHANGE current_mood_avg current_mood_avg DOUBLE PRECISION DEFAULT NULL, CHANGE streak_days streak_days INT NOT NULL, CHANGE total_xp total_xp INT NOT NULL, CHANGE level level INT NOT NULL, CHANGE badges_json badges_json LONGTEXT DEFAULT NULL, CHANGE onboarding_complete onboarding_complete TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE ai_user_profile ADD CONSTRAINT FK_82E87DABA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('DROP INDEX uq_profile_user ON ai_user_profile');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_82E87DABA76ED395 ON ai_user_profile (user_id)');
        $this->addSql('ALTER TABLE ai_user_profile ADD CONSTRAINT FK_profile_user FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ai_insight DROP FOREIGN KEY FK_C2311D08A76ED395');
        $this->addSql('DROP INDEX idx_c2311d08a76ed395 ON ai_insight');
        $this->addSql('CREATE INDEX IDX_insight_user ON ai_insight (user_id)');
        $this->addSql('ALTER TABLE ai_insight ADD CONSTRAINT FK_C2311D08A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE ai_message DROP FOREIGN KEY FK_8AB83EAC9AC0396');
        $this->addSql('ALTER TABLE ai_message DROP FOREIGN KEY FK_8AB83EAC9AC0396');
        $this->addSql('ALTER TABLE ai_message CHANGE role role VARCHAR(10) NOT NULL COMMENT \'user | assistant\', CHANGE message_type message_type VARCHAR(30) DEFAULT \'text\' NOT NULL COMMENT \'text|question|tip|insight|challenge|quick_reply|mood_check\', CHANGE quick_replies quick_replies TEXT DEFAULT NULL COMMENT \'JSON array de boutons réponse rapide\', CHANGE emotion_detected emotion_detected VARCHAR(30) DEFAULT NULL COMMENT \'Émotion détectée dans le message\', CHANGE data_collected data_collected TEXT DEFAULT NULL COMMENT \'Donnée spécifique extraite de ce message (JSON)\', CHANGE is_read is_read TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE ai_message ADD CONSTRAINT FK_msg_conv FOREIGN KEY (conversation_id) REFERENCES ai_conversation (id) ON DELETE CASCADE');
        $this->addSql('DROP INDEX idx_8ab83eac9ac0396 ON ai_message');
        $this->addSql('CREATE INDEX IDX_msg_conv ON ai_message (conversation_id)');
        $this->addSql('ALTER TABLE ai_message ADD CONSTRAINT FK_8AB83EAC9AC0396 FOREIGN KEY (conversation_id) REFERENCES ai_conversation (id)');
        $this->addSql('ALTER TABLE ai_user_profile DROP FOREIGN KEY FK_82E87DABA76ED395');
        $this->addSql('ALTER TABLE ai_user_profile DROP FOREIGN KEY FK_82E87DABA76ED395');
        $this->addSql('ALTER TABLE ai_user_profile ADD `values` TEXT DEFAULT NULL COMMENT \'Valeurs importantes (JSON)\', DROP personal_values, CHANGE wake_up_time wake_up_time VARCHAR(10) DEFAULT NULL COMMENT \'Heure de réveil habituelle\', CHANGE sleep_time sleep_time VARCHAR(10) DEFAULT NULL COMMENT \'Heure de coucher habituelle\', CHANGE work_schedule work_schedule VARCHAR(50) DEFAULT NULL COMMENT \'Horaires de travail\', CHANGE exercise_frequency exercise_frequency VARCHAR(30) DEFAULT NULL COMMENT \'Fréquence exercice physique\', CHANGE stress_triggers stress_triggers TEXT DEFAULT NULL COMMENT \'Déclencheurs de stress (JSON)\', CHANGE motivation_drivers motivation_drivers TEXT DEFAULT NULL COMMENT \'Ce qui motive le plus (JSON)\', CHANGE top_challenges top_challenges TEXT DEFAULT NULL COMMENT \'Défis majeurs dans la vie (JSON)\', CHANGE achievements achievements TEXT DEFAULT NULL COMMENT \'Réussites passées (JSON)\', CHANGE current_mood_avg current_mood_avg FLOAT DEFAULT NULL COMMENT \'Humeur moyenne 30 jours\', CHANGE streak_days streak_days INT DEFAULT 0 NOT NULL COMMENT \'Jours consécutifs de chat\', CHANGE total_xp total_xp INT DEFAULT 0 NOT NULL COMMENT \'Points d expérience gagnés\', CHANGE level level INT DEFAULT 1 NOT NULL COMMENT \'Niveau de progression\', CHANGE badges_json badges_json TEXT DEFAULT NULL COMMENT \'Badges débloqués (JSON)\', CHANGE onboarding_complete onboarding_complete TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE ai_user_profile ADD CONSTRAINT FK_profile_user FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('DROP INDEX uniq_82e87daba76ed395 ON ai_user_profile');
        $this->addSql('CREATE UNIQUE INDEX UQ_profile_user ON ai_user_profile (user_id)');
        $this->addSql('ALTER TABLE ai_user_profile ADD CONSTRAINT FK_82E87DABA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }
}
