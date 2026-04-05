<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260404135706 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE categorie CHANGE icon icon VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE challenge CHANGE statut statut VARCHAR(50) DEFAULT \'actif\' NOT NULL');
        $this->addSql('ALTER TABLE habit_streaks CHANGE last_completed last_completed DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE mental_entries CHANGE humeur humeur VARCHAR(50) DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE post CHANGE image image VARCHAR(255) DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE reclamation CHANGE statut statut VARCHAR(50) DEFAULT \'ouvert\' NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE recompense CHANGE image image VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE team_member CHANGE role role VARCHAR(50) DEFAULT \'member\' NOT NULL');
        $this->addSql('ALTER TABLE user CHANGE role role VARCHAR(20) DEFAULT \'user\' NOT NULL, CHANGE photo photo VARCHAR(255) DEFAULT NULL, CHANGE telephone telephone VARCHAR(20) DEFAULT NULL, CHANGE password_reset_token password_reset_token VARCHAR(100) DEFAULT NULL, CHANGE password_reset_requested_at password_reset_requested_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE workout CHANGE image image VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE workout_plan CHANGE statut statut VARCHAR(50) DEFAULT \'planifie\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE categorie CHANGE icon icon VARCHAR(100) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE challenge CHANGE statut statut VARCHAR(50) DEFAULT \'\'\'actif\'\'\' NOT NULL');
        $this->addSql('ALTER TABLE habit_streaks CHANGE last_completed last_completed DATE DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE mental_entries CHANGE humeur humeur VARCHAR(50) DEFAULT \'NULL\', CHANGE updated_at updated_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE post CHANGE image image VARCHAR(255) DEFAULT \'NULL\', CHANGE updated_at updated_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE reclamation CHANGE statut statut VARCHAR(50) DEFAULT \'\'\'ouvert\'\'\' NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE recompense CHANGE image image VARCHAR(255) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE team_member CHANGE role role VARCHAR(50) DEFAULT \'\'\'member\'\'\' NOT NULL');
        $this->addSql('ALTER TABLE `user` CHANGE telephone telephone VARCHAR(20) DEFAULT \'NULL\', CHANGE role role VARCHAR(20) DEFAULT \'\'\'user\'\'\' NOT NULL, CHANGE photo photo VARCHAR(255) DEFAULT \'NULL\', CHANGE password_reset_token password_reset_token VARCHAR(100) DEFAULT \'NULL\', CHANGE password_reset_requested_at password_reset_requested_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE workout CHANGE image image VARCHAR(255) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE workout_plan CHANGE statut statut VARCHAR(50) DEFAULT \'\'\'planifie\'\'\' NOT NULL');
    }
}
