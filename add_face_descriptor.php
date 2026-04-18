<?php
$pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=psy_db', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$cols = $pdo->query('SHOW COLUMNS FROM user LIKE "face_descriptor"')->fetchAll();
if (count($cols) > 0) {
    echo 'Column face_descriptor already exists.' . PHP_EOL;
} else {
    $pdo->exec('ALTER TABLE `user` ADD COLUMN `face_descriptor` JSON NULL DEFAULT NULL');
    echo 'Column face_descriptor added successfully.' . PHP_EOL;
}
