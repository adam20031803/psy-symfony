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

$pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
$tables = ['user', 'challenge', 'challenge_task', 'user_challenge_task', 'smart_meeting'];
$output = "";

foreach ($tables as $table) {
    $output .= "--- TABLE: $table ---\n";
    try {
        $stmt = $pdo->query("SHOW CREATE TABLE `$table` ");
        if ($stmt) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $output .= $row['Create Table'] . "\n\n";
        } else {
            $output .= "Table $table not found.\n\n";
        }
    } catch (Exception $e) {
        $output .= "Error checking $table: " . $e->getMessage() . "\n\n";
    }
}
file_put_contents('db_debug.txt', $output);
echo "Debug info written to db_debug.txt\n";
