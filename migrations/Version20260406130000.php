<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260406130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Daily check-in: user ownership and reflection fields (main challenges, biggest wins, notes)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE daily_checkin ADD user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE daily_checkin ADD main_challenges LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE daily_checkin ADD biggest_wins LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE daily_checkin ADD additional_notes LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE daily_checkin ADD CONSTRAINT FK_3E82CD65A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_3E82CD65A76ED395 ON daily_checkin (user_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE daily_checkin DROP FOREIGN KEY FK_3E82CD65A76ED395');
        $this->addSql('DROP INDEX IDX_3E82CD65A76ED395 ON daily_checkin');
        $this->addSql('ALTER TABLE daily_checkin DROP user_id, DROP main_challenges, DROP biggest_wins, DROP additional_notes');
    }
}
