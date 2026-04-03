<?php

/**
 * Script to run Doctrine migrations programmatically
 * Usage: php migrate.php
 */

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\ConsoleOutput;

require __DIR__ . '/vendor/autoload.php';

// Boot the Symfony kernel
$kernel = new \App\Kernel('dev', false);
$kernel->boot();

$application = new Application($kernel);
$application->setAutoExit(false);

$output = new ConsoleOutput();

echo "\n=== Doctrine Migrations ===\n\n";

// Run the migration
$input = new ArrayInput([
    'command'          => 'doctrine:migrations:migrate',
    '--no-interaction' => true,
]);

$exitCode = $application->run($input, $output);

if ($exitCode === 0) {
    echo "\n✅ Migration exécutée avec succès !\n\n";
} else {
    echo "\n❌ Erreur lors de la migration (code: $exitCode)\n\n";
}

$kernel->shutdown();
