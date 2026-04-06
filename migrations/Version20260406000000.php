<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260406000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add user_id column to habitude table and create necessary indexes';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE habitude ADD user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE habitude ADD CONSTRAINT FK_8A9C4A5CA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_8A9C4A5CA76ED395 ON habitude (user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE habitude DROP FOREIGN KEY FK_8A9C4A5CA76ED395');
        $this->addSql('DROP INDEX IDX_8A9C4A5CA76ED395 ON habitude');
        $this->addSql('ALTER TABLE habitude DROP user_id');
    }
}
