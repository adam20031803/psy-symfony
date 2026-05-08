<?php

namespace App\Service;

use App\Entity\Exercise;
use App\Entity\Program;
use Doctrine\ORM\EntityManagerInterface;

class FitnessProgramGeneratorService
{
    public function __construct(
        private readonly GeminiService          $geminiService,
        private readonly GroqService            $groqService,
        private readonly OllamaFallbackService  $ollama,
        private readonly EntityManagerInterface $em,
    ) {}


    public function generateProgram(array $data): ?Program
    {
        $goal = $data['goal'] ?? 'General fitness';
        $level = $data['level'] ?? 'Débutant';
        $days = $data['days'] ?? 3;

        $prompt = "Tu es un coach sportif expert. Crée un programme de fitness avec des exercices.
Réponds UNIQUEMENT au format JSON strict.

Objectif: $goal
Niveau: $level
Jours par semaine: $days

Le JSON doit avoir la structure suivante:
{
  \"title\": \"Nom motivant du programme\",
  \"goal\": \"L'objectif résumé\",
  \"durationWeeks\": 4,
  \"level\": \"$level\",
  \"exercises\": [
    {
      \"name\": \"Nom de l'exercice\",
      \"description\": \"Comment faire\",
      \"category\": \"Cardio ou Musculation ou Yoga ou HIIT ou Flexibilité\",
      \"difficulty\": \"$level\",
      \"duration\": 10,
      \"calories\": 100,
      \"imageUrl\": \"https://via.placeholder.com/500\"
    }
  ]
}";

        $json = null;

        // ── 1) Ollama local first (avoids cloud quota; uses format=json + long timeout) ──
        if ($this->ollama->isAvailable()) {
            $response = $this->ollama->generateResponse($prompt, true);
            $json     = $this->decodeAiJson($response);
        }

        // ── 2) Gemini ──
        if (!$json || !isset($json['exercises'])) {
            $response = $this->geminiService->generateResponse($prompt);
            $json     = $this->decodeAiJson($response);
        }

        // ── 3) Groq ──
        if (!$json || !isset($json['exercises'])) {
            $response = $this->groqService->generateResponse($prompt);
            $json     = $this->decodeAiJson($response);
        }

        // ── 4) Ollama again without format=json (older Ollama / model quirks) ──
        if ((!$json || !isset($json['exercises'])) && $this->ollama->isAvailable()) {
            $response = $this->ollama->generateResponse($prompt, false);
            $json     = $this->decodeAiJson($response);
        }

        if (!$json || !isset($json['exercises']) || !is_array($json['exercises']) || $json['exercises'] === []) {
            return null;
        }

        $program = new Program();
        $program->setTitle(substr($json['title'] ?? 'Nouveau Programme via IA', 0, 150));
        $program->setGoal(substr($json['goal'] ?? $goal, 0, 255));
        $program->setDurationWeeks((int)($json['durationWeeks'] ?? 4));
        // Validate level
        $allowedLevels = ['Débutant', 'Intermédiaire', 'Avancé'];
        $pLevel = in_array($json['level'] ?? '', $allowedLevels) ? $json['level'] : 'Débutant';
        $program->setLevel($pLevel);
        $program->setIsPublished(true);

        $this->em->persist($program);

        foreach ($json['exercises'] as $exData) {
            $ex = new Exercise();
            $ex->setName(substr($exData['name'] ?? 'Exercice', 0, 100));
            $ex->setDescription($exData['description'] ?? 'Faites de votre mieux.');

            $allowedCats = ['Cardio', 'Musculation', 'Yoga', 'HIIT', 'Flexibilité'];
            $c = in_array($exData['category'] ?? '', $allowedCats) ? $exData['category'] : 'Cardio';
            $ex->setCategory($c);

            $ex->setDifficulty($pLevel);
            $ex->setDuration((int)($exData['duration'] ?? 10));
            $ex->setCalories((float)($exData['calories'] ?? 100.0));
            $ex->setImageUrl($exData['imageUrl'] ?? null);

            $this->em->persist($ex);
            $program->addExercise($ex);
        }

        $this->em->flush();

        return $program;
    }

    /**
     * @return array<string,mixed>|null
     */
    private function decodeAiJson(string $response): ?array
    {
        $clean = preg_replace('/^```(?:json)?\s*|\s*```$/im', '', trim($response));
        $clean = trim((string) $clean);

        $decoded = json_decode($clean, true);
        if (is_array($decoded) && isset($decoded['exercises'])) {
            return $decoded;
        }

        $start = strpos($clean, '{');
        $end   = strrpos($clean, '}');
        if ($start !== false && $end !== false && $end > $start) {
            $slice = substr($clean, $start, $end - $start + 1);
            $decoded = json_decode($slice, true);
            if (is_array($decoded) && isset($decoded['exercises'])) {
                return $decoded;
            }
        }

        return null;
    }
}
