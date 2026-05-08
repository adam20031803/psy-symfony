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
 * Recrée la table mood avec id AUTO_INCREMENT + UNIQUE(mood_name), et réassocie mental_tip / mental_entry.
 * Indispensable si phpMyAdmin signale « pas de colonne unique » ou si plusieurs lignes ont id = 0.
 */
#[AsCommand(
    name: 'app:repair-mood-table',
    description: 'Répare la table mood (PK, ids uniques) et les références mood_id des tables liées',
)]
final class RepairMoodTableCommand extends Command
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
            [$db, 'mood']
        );
        if (0 === $exists) {
            $io->warning('Table mood introuvable.');

            return Command::FAILURE;
        }

        $io->comment('Base : '.$db);

        $conn->executeStatement('SET FOREIGN_KEY_CHECKS=0');
        try {
            $conn->executeStatement('DROP TABLE IF EXISTS mood_backup');
            $conn->executeStatement('CREATE TABLE mood_backup AS SELECT * FROM mood');
            $conn->executeStatement(
                'ALTER TABLE mood_backup CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
            );

            $conn->executeStatement('DROP TABLE IF EXISTS mood_new');
            $conn->executeStatement(
                'CREATE TABLE mood_new (
                    id INT AUTO_INCREMENT NOT NULL,
                    mood_name VARCHAR(60) NOT NULL,
                    created_at DATETIME NOT NULL,
                    PRIMARY KEY (id),
                    UNIQUE INDEX UNIQ_mood_mood_name (mood_name)
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB'
            );

            $conn->executeStatement(
                'INSERT INTO mood_new (mood_name, created_at)
                 SELECT mood_name, MIN(created_at) FROM mood_backup GROUP BY mood_name'
            );

            // Références encore cohérentes (ancien id > 0 et nom qui existe encore)
            $conn->executeStatement(
                'UPDATE mental_tip mt
                 INNER JOIN mood_backup mb ON mb.id = mt.mood_id AND mb.id > 0
                 INNER JOIN mood_new mn ON mn.mood_name COLLATE utf8mb4_unicode_ci = mb.mood_name COLLATE utf8mb4_unicode_ci
                 SET mt.mood_id = mn.id'
            );

            $conn->executeStatement(
                'UPDATE mental_entry me
                 INNER JOIN mood_backup mb ON mb.id = me.mood_id AND mb.id > 0
                 INNER JOIN mood_new mn ON mn.mood_name COLLATE utf8mb4_unicode_ci = mb.mood_name COLLATE utf8mb4_unicode_ci
                 SET me.mood_id = mn.id'
            );

            $firstId = $conn->fetchOne('SELECT MIN(id) FROM mood_new');
            if ($firstId !== false && $firstId !== null) {
                $conn->executeStatement(
                    'UPDATE mental_tip SET mood_id = ? WHERE mood_id NOT IN (SELECT id FROM mood_new)',
                    [$firstId]
                );
                $conn->executeStatement(
                    'UPDATE mental_tip SET mood_id = ? WHERE mood_id IS NULL OR mood_id = 0',
                    [$firstId]
                );
                $conn->executeStatement(
                    'UPDATE mental_entry SET mood_id = ? WHERE mood_id NOT IN (SELECT id FROM mood_new)',
                    [$firstId]
                );
                $conn->executeStatement(
                    'UPDATE mental_entry SET mood_id = ? WHERE mood_id IS NULL OR mood_id = 0',
                    [$firstId]
                );
            }

            $conn->executeStatement('DROP TABLE mood');
            $conn->executeStatement('RENAME TABLE mood_new TO mood');
            $conn->executeStatement('DROP TABLE mood_backup');
        } finally {
            $conn->executeStatement('SET FOREIGN_KEY_CHECKS=1');
        }

        $n = (int) $conn->fetchOne('SELECT COUNT(*) FROM mood');
        $io->success(sprintf('Table mood réparée : %d humeur(s) avec id unique (AUTO_INCREMENT). Les tips/entries orphelins ont été rattachés au premier id.', $n));

        return Command::SUCCESS;
    }
}
