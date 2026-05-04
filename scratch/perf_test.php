<?php
require __DIR__ . '/../vendor/autoload.php';

$start = microtime(true);
$e = new App\Entity\Habitude();
$e->setTitle('Test')->setCategory('Sante')->setFrequencyType(3)->setStartDate(new DateTime());
$end1 = microtime(true);

$u = new App\Entity\User();
$u->setNom('Doe')->setPrenom('John')->setEmail('j@example.com');
$end2 = microtime(true);

$c = new App\Entity\Challenge();
for ($i = 0; $i < 100; $i++) {
    $chat = new App\Entity\ChallengeChat();
    $c->getChats(); // simulate N+1 access pattern
}
$end3 = microtime(true);

echo "=== Performance Metrics ===" . PHP_EOL;
echo "Habitude entity create+set: " . round(($end1 - $start) * 1000, 3) . " ms" . PHP_EOL;
echo "User entity create+set:     " . round(($end2 - $end1) * 1000, 3) . " ms" . PHP_EOL;
echo "Challenge + 100 chats loop: " . round(($end3 - $end2) * 1000, 3) . " ms" . PHP_EOL;
echo "Total execution:            " . round(($end3 - $start) * 1000, 3) . " ms" . PHP_EOL;
echo "Peak Memory:                " . round(memory_get_peak_usage(true) / 1024 / 1024, 2) . " MB" . PHP_EOL;
