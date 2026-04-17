<?php
require 'vendor/autoload.php';
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->load(__DIR__.'/.env');

$url = $_ENV['DATABASE_URL'];
$config = parse_url($url);
$host = $config['host'];
$user = $config['user'];
$pass = $config['pass'] ?? '';
$path = ltrim($config['path'], '/');
$db = explode('?', $path)[0];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $tables = ['user', 'challenge_task', 'user_challenge_task', 'smart_meeting'];
    foreach ($tables as $table) {
        echo "--- TABLE: $table ---\n";
        try {
            $stmt = $pdo->query("SHOW CREATE TABLE `$table` ");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            echo $row['Create Table'] . "\n\n";
        } catch (Exception $e) {
            echo "Error checking $table: " . $e->getMessage() . "\n\n";
        }
    }
} catch (Exception $e) { echo $e->getMessage(); }
