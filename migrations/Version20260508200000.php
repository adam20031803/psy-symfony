<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Répare mental_entry.mood_id = 0 ou FK cassée : Doctrine ne peut pas charger Mood id(0).
 */
final class Version20260508200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix mental_entry rows pointing to invalid mood_id (0 or missing mood)';
    }

    public function up(Schema $schema): void
    {
        $conn = $this->connection;
        $db = $conn->fetchOne('SELECT DATABASE()');
        if (!\is_string($db) || '' === $db) {
            return;
        }

        $entryExists = (int) $conn->fetchOne(
            'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ? AND table_name = ?',
            [$db, 'mental_entry']
        );
        if (0 === $entryExists) {
            return;
        }

        $moodExists = (int) $conn->fetchOne(
            'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ? AND table_name = ?',
            [$db, 'mood']
        );
        if (0 === $moodExists) {
            $this->write('mental_entry mood fix skipped: table mood does not exist.');

            return;
        }

        $firstMoodId = $conn->fetchOne('SELECT MIN(id) FROM mood');
        if (null === $firstMoodId || false === $firstMoodId) {
            $this->write('mental_entry mood fix skipped: mood table has no rows.');

            return;
        }

        $firstId = (int) $firstMoodId;

        // mood_id = 0 ou référence vers une ligne mood inexistante
        $conn->executeStatement(
            'UPDATE mental_entry e
             SET e.mood_id = ?
             WHERE e.mood_id = 0
                OR e.mood_id NOT IN (SELECT m.id FROM mood m)',
            [$firstId]
        );
    }

    public function down(Schema $schema): void
    {
        // irréversible : on ne restaure pas les anciens mood_id invalides
    }
}
