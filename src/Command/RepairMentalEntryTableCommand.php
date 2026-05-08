<?php

declare(strict_types=1);

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Recrée mental_entry avec id AUTO_INCREMENT + PRIMARY KEY.
 * Si plusieurs lignes ont id = 0 ou pas de PK, Doctrine n’affiche qu’une entrée par id — d’où 2 lignes au lieu de 6.
 */
#[AsCommand(
    name: 'app:repair-mental-entry-table',
    description: 'Répare la table mental_entry (PK, ids uniques AUTO_INCREMENT) et mood_id invalides',
)]
final class RepairMentalEntryTableCommand extends Command
{
    public function __construct(
        private readonly Connection $connection,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $conn = $this->connection;

        $db = $conn->fetchOne('SELECT DATABASE()');
        if (!\is_string($db) || '' === $db) {
            $io->error('Base de données non sélectionnée.');

            return Command::FAILURE;
        }

        $exists = (int) $conn->fetchOne(
            'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ? AND table_name = ?',
            [$db, 'mental_entry']
        );
        if (0 === $exists) {
            $io->warning('Table mental_entry introuvable.');

            return Command::FAILURE;
        }

        $moodCount = (int) $conn->fetchOne('SELECT COUNT(*) FROM mood');
        if (0 === $moodCount) {
            $io->error('La table mood est vide : ajoutez au moins une humeur avant de réparer mental_entry.');

            return Command::FAILURE;
        }

        $io->comment('Base : '.$db);

        $conn->executeStatement('SET FOREIGN_KEY_CHECKS=0');
        try {
            $conn->executeStatement('DROP TABLE IF EXISTS mental_entry_backup');
            $conn->executeStatement('CREATE TABLE mental_entry_backup AS SELECT * FROM mental_entry');
            $conn->executeStatement(
                'ALTER TABLE mental_entry_backup CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
            );

            $conn->executeStatement('DROP TABLE IF EXISTS mental_entry_repair');
            $conn->executeStatement(
                'CREATE TABLE mental_entry_repair (
                    id INT AUTO_INCREMENT NOT NULL,
                    mood_id INT NOT NULL,
                    entry_date DATE NOT NULL,
                    emotion_level INT NOT NULL,
                    activity VARCHAR(120) NOT NULL,
                    note VARCHAR(255) DEFAULT NULL,
                    PRIMARY KEY (id),
                    INDEX IDX_mental_entry_mood (mood_id)
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
            );

            $conn->executeStatement(
                'INSERT INTO mental_entry_repair (entry_date, emotion_level, activity, note, mood_id)
                 SELECT
                     DATE(b.entry_date) AS entry_date,
                     b.emotion_level,
                     b.activity,
                     b.note,
                     CASE
                         WHEN EXISTS (
                             SELECT 1 FROM mood m
                             WHERE m.id = b.mood_id AND b.mood_id IS NOT NULL AND b.mood_id <> 0
                         ) THEN b.mood_id
                         ELSE (SELECT MIN(id) FROM mood)
                     END AS mood_id
                 FROM mental_entry_backup b
                 ORDER BY b.entry_date ASC, b.emotion_level ASC, b.activity ASC'
            );

            $conn->executeStatement('DROP TABLE mental_entry');
            $conn->executeStatement('RENAME TABLE mental_entry_repair TO mental_entry');
            $conn->executeStatement('DROP TABLE mental_entry_backup');
        } finally {
            $conn->executeStatement('SET FOREIGN_KEY_CHECKS=1');
        }

        $n = (int) $conn->fetchOne('SELECT COUNT(*) FROM mental_entry');
        $io->success(sprintf(
            'Table mental_entry réparée : %d entrée(s), chaque ligne a un id unique (AUTO_INCREMENT). Les mood_id invalides ont été rattachés au premier id de mood.',
            $n
        ));

        return Command::SUCCESS;
    }
}
