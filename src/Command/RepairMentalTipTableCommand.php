<?php

declare(strict_types=1);

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Répare la table mental_tip (PK + AUTO_INCREMENT, FK mood) pour coller à l'entité Doctrine.
 * À lancer si phpMyAdmin affiche « pas de colonne unique » ou plusieurs lignes avec id = 0.
 */
#[AsCommand(
    name: 'app:repair-mental-tip-table',
    description: 'Recrée mental_tip avec id AUTO_INCREMENT et rattache les moods invalides',
)]
final class RepairMentalTipTableCommand extends Command
{
    public function __construct(
        private readonly Connection $connection,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'normalize-moods-only',
            null,
            InputOption::VALUE_NONE,
            'Corrige mood_id invalides (0 ou humeur inexistante) sans recréer la table'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $conn = $this->connection;

        $db = $conn->fetchOne('SELECT DATABASE()');
        if (!\is_string($db) || '' === $db) {
            $io->error('Aucune base sélectionnée (DATABASE_URL / connexion).');

            return Command::FAILURE;
        }

        $tableExists = (int) $conn->fetchOne(
            'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ? AND table_name = ?',
            [$db, 'mental_tip']
        );
        if (0 === $tableExists) {
            $io->warning('Table mental_tip absente. Lancez les migrations Doctrine ou bin/console doctrine:schema:update.');

            return Command::FAILURE;
        }

        $moodCount = (int) $conn->fetchOne('SELECT COUNT(*) FROM mood');
        if (0 === $moodCount) {
            $io->error('La table mood est vide : ajoutez au moins une humeur avant de réparer mental_tip.');

            return Command::FAILURE;
        }

        $io->comment('Base active : '.$db);

        if ($input->getOption('normalize-moods-only')) {
            $n = $this->normalizeInvalidMoodIds($conn);
            $io->success(sprintf('mood_id corrigés sur %d ligne(s) (référence : premier id dans mood).', $n));

            return Command::SUCCESS;
        }

        $conn->executeStatement('SET FOREIGN_KEY_CHECKS=0');
        try {
            $conn->executeStatement('DROP TABLE IF EXISTS mental_tip_repair');
            // Pas de FOREIGN KEY ici : si mood.id n’est pas PK (bases XAMPP / imports), MySQL refuse errno 150.
            // Doctrine applique toujours la relation côté application.
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

        $count = (int) $conn->fetchOne('SELECT COUNT(*) FROM mental_tip');
        $this->normalizeInvalidMoodIds($conn);
        $io->success(sprintf('Table mental_tip recréée : %d ligne(s). Chaque conseil a un id unique (AUTO_INCREMENT).', $count));

        return Command::SUCCESS;
    }

    /** @return int nombre de lignes mises à jour */
    private function normalizeInvalidMoodIds(Connection $conn): int
    {
        return $conn->executeStatement(
            'UPDATE mental_tip t
             CROSS JOIN (SELECT MIN(id) AS mid FROM mood) x
             SET t.mood_id = x.mid
             WHERE t.mood_id IS NULL
                OR t.mood_id = 0
                OR NOT EXISTS (SELECT 1 FROM mood m WHERE m.id = t.mood_id)'
        );
    }
}
