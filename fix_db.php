<?php

$dbRaw = file_get_contents('.env.dev') ?: file_get_contents('.env');
preg_match('/DATABASE_URL="mysql:\/\/([^:]+):([^@]*)@([^:]+):(\d+)\/([^?]+)/', $dbRaw, $matches);

if (!$matches) {
    preg_match('/DATABASE_URL="([^"]+)"/', file_get_contents('.env'), $m);
    $url = $m[1] ?? '';
    preg_match('/mysql:\/\/([^:]+):([^@]*)@([^:]+):(\d+)\/([^?]+)/', $url, $matches);
}

if (!$matches) {
    die("Could not parse database URL");
}

$user = $matches[1];
$pass = $matches[2];
$host = $matches[3];
$port = $matches[4];
$db   = $matches[5];

$pdo = new PDO("mysql:host=$host;port=$port;dbname=$db", $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$queries = [
    "ALTER TABLE daily_checkin DROP FOREIGN KEY `FK_3E82CD65A76ED395`",
    "DROP INDEX IDX_3E82CD65A76ED395 ON daily_checkin",
    "ALTER TABLE daily_checkin ADD mood_rating INT NOT NULL, ADD energy_level INT NOT NULL, ADD productivity_level INT NOT NULL, ADD stress_level INT NOT NULL, ADD sleep_quality INT NOT NULL, ADD sleep_hours DOUBLE PRECISION NOT NULL, ADD what_could_improve LONGTEXT DEFAULT NULL, ADD gratitude LONGTEXT DEFAULT NULL, ADD emotion_data LONGTEXT DEFAULT NULL, ADD ai_insight LONGTEXT DEFAULT NULL, DROP mood_score, DROP created_at, DROP user_id, CHANGE date checkin_date DATE NOT NULL, CHANGE notes what_went_well LONGTEXT DEFAULT NULL",
    "ALTER TABLE habitude DROP FOREIGN KEY `FK_10DD3E5FA76ED395`",
    "ALTER TABLE habitude DROP FOREIGN KEY `FK_10DD3E5FBCF5E72D`",
    "DROP INDEX IDX_10DD3E5FA76ED395 ON habitude",
    "DROP INDEX IDX_10DD3E5FBCF5E72D ON habitude",
    "ALTER TABLE habitude ADD category VARCHAR(255) NOT NULL, ADD start_date DATE NOT NULL, ADD end_date DATE DEFAULT NULL, ADD active TINYINT NOT NULL, ADD last_completed_date DATE DEFAULT NULL, ADD current_streak INT NOT NULL, ADD longest_streak INT NOT NULL, ADD total_completions INT NOT NULL, DROP frequence, DROP created_at, DROP is_active, DROP categorie_id, CHANGE nom title VARCHAR(255) NOT NULL, CHANGE user_id frequency_type INT NOT NULL"
];

foreach ($queries as $q) {
    try {
        $pdo->exec($q);
        echo "OK: " . substr($q, 0, 40) . "\n";
    } catch (Exception $e) {
        echo "ERR: " . $e->getMessage() . "\n";
    }
}
echo "Done\n";
