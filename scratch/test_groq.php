<?php
require __DIR__ . '/../vendor/autoload.php';

use App\Service\GroqService;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpClient\HttpClient;

(new Dotenv())->bootEnv(__DIR__ . '/../.env');

$apiKey = $_ENV['GROQ_API_KEY'];
$client = HttpClient::create();
$groq = new GroqService($client, $apiKey);

echo "Testing Groq...\n";
$response = $groq->generateResponse("Dis 'Bonjour' en JSON.");
echo "Response: " . $response . "\n";
