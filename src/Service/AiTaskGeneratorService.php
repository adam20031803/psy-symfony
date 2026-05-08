<?php

namespace App\Service;

use App\Entity\Challenge;
use App\Entity\ChallengeTask;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class AiTaskGeneratorService
{
    public function __construct(
        private readonly EntityManagerInterface  $em,
        private readonly LoggerInterface         $logger,
        private readonly OllamaFallbackService   $ollama,
        private readonly string                  $geminiApiKey,
    ) {}

    public function generateAndPersist(Challenge $challenge): array
    {
        $prompt = $this->buildPrompt($challenge);
        $result = $this->callGemini($prompt);

        // ── Fallback: Ollama local if Gemini failed ────────────
        if (isset($result['error']) && $this->ollama->isAvailable()) {
            $this->logger->warning('Gemini failed for task generation, trying Ollama fallback.');
            $raw     = $this->ollama->generateResponse($prompt);
            $jsonStr = $this->extractJson($raw);
            $data    = json_decode($jsonStr, true);
            if (is_array($data) && isset($data['tasks'])) {
                return $this->persistTasks($challenge, $data['tasks']);
            }
        }

        if (isset($result['error'])) {
            return $this->fallbackTasks($challenge, $result['error']);
        }

        $raw      = $result['text'];
        $jsonStr  = $this->extractJson($raw);
        $data     = json_decode($jsonStr, true);

        if (!is_array($data) || !isset($data['tasks'])) {
            $this->logger->error('Gemini: JSON parse failed or missing tasks key', [
                'raw'        => substr($raw, 0, 500),
            ]);
            return $this->fallbackTasks($challenge, "Erreur de formatage IA.");
        }

        // On peut stocker le score de complexité sociale dans le challenge si on ajoute la colonne plus tard
        // Pour l'instant, on se concentre sur les tâches
        return $this->persistTasks($challenge, $data['tasks']);
    }

    private function buildPrompt(Challenge $challenge): string
{
    $cat   = $challenge->getCategorie()?->getNom() ?? 'développement personnel';
    $titre = $challenge->getTitre();
    $desc  = $challenge->getDescription();
    $days  = 30;

    if ($challenge->getDateDebut() && $challenge->getDateFin()) {
        $days = max(1, (int) $challenge->getDateDebut()->diff($challenge->getDateFin())->days);
    }

    return "Expert en coaching ({$cat}). Génère 7 tâches progressives pour ce challenge :
TITRE: {$titre}
DESCRIPTION: {$desc}
DURÉE: {$days} jours

Analyses également le besoin de collaboration pour ce challenge spécifique.

Réponds UNIQUEMENT avec un objet JSON valide contenant :
- tasks (tableau d'objets):
    - title (max 50 chars)
    - description (1 phrase)
    - why_recommended (1 phrase)
    - completion_criteria (1 phrase)
    - difficulty (int 1-5)
    - estimated_minutes (int)
    - points (int 20-200)
- social_complexity_score (int 1-10): Score de besoin de collaboration.
- collaborative_focus (1 phrase): Pourquoi le groupe doit travailler ensemble sur ce sujet.";
}

    private function callGemini(string $prompt): array
    {
        if (empty($this->geminiApiKey)) return ['error' => 'Clé API manquante dans .env'];

        // Use gemini-2.5-flash — the currently available model
        $url  = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $this->geminiApiKey;

        $body = json_encode([
            'contents' => [['parts' => [['text' => $prompt]]]],
            'generationConfig' => [
                'temperature'     => 0.7,
                'maxOutputTokens' => 4096,
                'responseMimeType' => 'application/json',
            ],
        ]);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        $response  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($httpCode === 200 && !empty($response)) {
            $data = json_decode($response, true);
            $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
            if ($text) return ['text' => $text];
        }

        $errorMsg = $curlError ?: "Code HTTP $httpCode";
        if (!empty($response)) {
            $data     = json_decode($response, true);
            $errorMsg = $data['error']['message'] ?? $errorMsg;
        }

        $this->logger->error('Gemini API call failed', ['code' => $httpCode, 'error' => $errorMsg]);
        return ['error' => $errorMsg];
    }


    private function extractJson(string $raw): string
    {
        $cleaned = trim($raw);
        $cleaned = preg_replace('/^```json\s*/i', '', $cleaned);
        $cleaned = preg_replace('/^```\s*/i',     '', $cleaned);
        $cleaned = preg_replace('/\s*```\s*$/i',  '', $cleaned);
        $cleaned = trim($cleaned);

        // Our prompt asks for an OBJECT with a 'tasks' key — find { ... }
        $start = strpos($cleaned, '{');
        $end   = strrpos($cleaned, '}');
        if ($start !== false && $end !== false && $end > $start) {
            $json = substr($cleaned, $start, $end - $start + 1);
            json_decode($json);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $json;
            }
            $this->logger->warning('Gemini object JSON invalid', [
                'length'  => strlen($raw),
                'preview' => substr($raw, -300),
            ]);
        }

        // Fallback: try a bare array [] and wrap it
        $start = strpos($cleaned, '[');
        $end   = strrpos($cleaned, ']');
        if ($start !== false && $end !== false && $end > $start) {
            $json = substr($cleaned, $start, $end - $start + 1);
            $arr  = json_decode($json, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($arr)) {
                return json_encode(['tasks' => $arr, 'social_complexity_score' => 5, 'collaborative_focus' => '']);
            }
        }

        return $cleaned;
    }
    private function persistTasks(Challenge $challenge, array $tasks): array
    {
        $repo = $this->em->getRepository(ChallengeTask::class);
        foreach ($repo->findBy(['challenge' => $challenge]) as $old) {
            $this->em->remove($old);
        }
        $this->em->flush();

        foreach ($tasks as $i => $t) {
            if (!isset($t['title'])) continue;

            $task = new ChallengeTask();
            $task->setChallenge($challenge);
            $task->setTitle(mb_substr($t['title'], 0, 255));
            $task->setDescription($t['description'] ?? '');
            $task->setWhyRecommended($t['why_recommended'] ?? null);
            $task->setCompletionCriteria($t['completion_criteria'] ?? null);
            $task->setDifficulty(max(1, min(5, (int)($t['difficulty'] ?? 1))));
            $task->setEstimatedMinutes(max(5, (int)($t['estimated_minutes'] ?? 15)));
            $task->setPoints(max(10, min(500, (int)($t['points'] ?? 50))));
            $task->setSortOrder($i);
            $this->em->persist($task);
        }
        $this->em->flush();

        return $tasks;
    }

    private function fallbackTasks(Challenge $challenge, string $error = ''): array
    {
        $defaults = [
            [
                'title' => 'Oops! L\'IA a eu un petit souci 🤖',
                'description' => 'Impossible de générer des tâches personnalisées pour le moment.',
                'why_recommended' => 'Raison technique : ' . $error,
                'completion_criteria' => 'Vérifiez votre quota ou connexion',
                'difficulty' => 1,
                'estimated_minutes' => 5,
                'points' => 0
            ],
            [
                'title' => 'Préparation du challenge',
                'description' => 'Fixez vos objectifs pour ce challenge en attendant le retour de l\'IA.',
                'why_recommended' => 'Clarifier la vision.',
                'completion_criteria' => '3 points notés',
                'difficulty' => 1,
                'estimated_minutes' => 15,
                'points' => 20
            ]
        ];
        return $this->persistTasks($challenge, $defaults);
    }
}