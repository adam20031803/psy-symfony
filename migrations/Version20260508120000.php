<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Corrige mental_tip quand id n'est pas PK auto_increment (plusieurs id=0, etc.) :
 * Doctrine ne peut pas hydrater plusieurs lignes avec le même id.
 * Repare aussi mood_id invalide (0 ou FK cassée) en pointant vers le premier mood existant.
 */
final class Version20260508120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rebuild mental_tip with PK + AUTO_INCREMENT and unique ids';
    }

    public function up(Schema $schema): void
    {
        $conn = $this->connection;
        $db = $conn->fetchOne('SELECT DATABASE()');
        if (!\is_string($db) || '' === $db) {
            return;
        }

        $tableExists = (int) $conn->fetchOne(
            'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ? AND table_name = ?',
            [$db, 'mental_tip']
        );
        if (0 === $tableExists) {
            return;
        }

        $hasPk = (int) $conn->fetchOne(
            'SELECT COUNT(*) FROM information_schema.table_constraints
             WHERE table_schema = ? AND table_name = ? AND constraint_type = ?',
            [$db, 'mental_tip', 'PRIMARY KEY']
        );

        $rowCount = (int) $conn->fetchOne('SELECT COUNT(*) FROM mental_tip');
        $distinctIds = (int) $conn->fetchOne('SELECT COUNT(DISTINCT id) FROM mental_tip');
        $hasDuplicateIds = $rowCount > 0 && $distinctIds < $rowCount;

        if ($hasPk > 0 && !$hasDuplicateIds) {
            $extra = (string) $conn->fetchOne(
                'SELECT COALESCE(EXTRA, \'\') FROM information_schema.columns
                 WHERE table_schema = ? AND table_name = ? AND column_name = ?',
                [$db, 'mental_tip', 'id']
            );
            if (str_contains(strtolower($extra), 'auto_increment')) {
                return;
            }
        }

        $moodCount = (int) $conn->fetchOne('SELECT COUNT(*) FROM mood');
        if (0 === $moodCount) {
            $this->write('mental_tip repair skipped: table mood is empty.');
            return;
        }

        $conn->executeStatement('SET FOREIGN_KEY_CHECKS=0');
        try {
            $conn->executeStatement('DROP TABLE IF EXISTS mental_tip_repair');
            // FK omise si mood.id n’est pas PK (errno 150). Doctrine garde la relation Mappable.
            $conn->executeStatement(
                'CREATE TABLE mental_tip_repair (
                    id INT AUTO_INCREMENT NOT NULL,
                    mood_id INT NOT NULL,
                    tip_text VARCHAR(255) NOT NULL,
                    PRIMARY KEY (id),
                    INDEX IDX_mental_tip_mood (mood_id)
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
            );

            $conn->executeStatement(
                'INSERT INTO mental_tip_repair (mood_id, tip_text)
                 SELECT mt.mood_id, mt.tip_text
                 FROM mental_tip mt
                 WHERE EXISTS (SELECT 1 FROM mood m WHERE m.id = mt.mood_id)'
            );

            $conn->executeStatement(
                'INSERT INTO mental_tip_repair (mood_id, tip_text)
                 SELECT (SELECT MIN(id) FROM mood), mt.tip_text
                 FROM mental_tip mt
                 WHERE mt.mood_id IS NULL
                    OR mt.mood_id = 0
                    OR NOT EXISTS (SELECT 1 FROM mood m WHERE m.id = mt.mood_id)'
            );

            $conn->executeStatement('DROP TABLE mental_tip');
            $conn->executeStatement('RENAME TABLE mental_tip_repair TO mental_tip');
        } finally {
            $conn->executeStatement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException();
    }
}
