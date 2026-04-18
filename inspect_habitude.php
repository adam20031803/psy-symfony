<?php
$pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=psy_db', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== habitude table columns ===\n";
foreach ($pdo->query('SHOW COLUMNS FROM habitude') as $col) {
    echo $col['Field'] . ' | ' . $col['Type'] . ' | null=' . $col['Null'] . ' | default=' . $col['Default'] . "\n";
}
