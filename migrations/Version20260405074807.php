<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260405074807 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE mental_entry (id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE mental_tip (id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE mood (id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE mental_entries DROP FOREIGN KEY fk_entries_mood');
        $this->addSql('ALTER TABLE mental_tips DROP FOREIGN KEY fk_tips_mood');
        $this->addSql('DROP TABLE app_settings');
        $this->addSql('DROP TABLE chat_messages');
        $this->addSql('DROP TABLE mental_entries');
        $this->addSql('DROP TABLE mental_tips');
        $this->addSql('DROP TABLE moods');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE app_settings (id INT NOT NULL, sms_enabled TINYINT(1) DEFAULT 0 NOT NULL, phone_number VARCHAR(30) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE chat_messages (id INT AUTO_INCREMENT NOT NULL, role VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, content TEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE mental_entries (id INT AUTO_INCREMENT NOT NULL, mood_id INT NOT NULL, entry_date DATE NOT NULL, emotion_level INT NOT NULL, activity VARCHAR(120) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, note VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_general_ci`, INDEX fk_entries_mood (mood_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE mental_tips (id INT AUTO_INCREMENT NOT NULL, mood_id INT NOT NULL, tip_text VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, INDEX fk_tips_mood (mood_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE moods (id INT AUTO_INCREMENT NOT NULL, mood_name VARCHAR(60) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, created_at DATETIME DEFAULT \'current_timestamp()\' NOT NULL, UNIQUE INDEX uq_mood_name (mood_name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE mental_entries ADD CONSTRAINT fk_entries_mood FOREIGN KEY (mood_id) REFERENCES moods (id) ON UPDATE CASCADE');
        $this->addSql('ALTER TABLE mental_tips ADD CONSTRAINT fk_tips_mood FOREIGN KEY (mood_id) REFERENCES moods (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('DROP TABLE mental_entry');
        $this->addSql('DROP TABLE mental_tip');
        $this->addSql('DROP TABLE mood');
    }
}
