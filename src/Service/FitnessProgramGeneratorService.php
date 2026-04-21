<?php

namespace App\Service;

use App\Entity\Exercise;
use App\Entity\Program;
use Doctrine\ORM\EntityManagerInterface;

class FitnessProgramGeneratorService
{
    private GeminiService $geminiService;
    private GroqService $groqService;
    private EntityManagerInterface $em;

    public function __construct(
        GeminiService $geminiService,
        GroqService $groqService,
        EntityManagerInterface $em
    ) {
        $this->geminiService = $geminiService;
        $this->groqService = $groqService;
        $this->em = $em;
    }

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

        // Try Gemini first
        $response = $this->geminiService->generateResponse($prompt);
        
        $cleanResponse = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($response));
        $json = json_decode($cleanResponse, true);

        $isGeminiError = str_starts_with($response, 'Erreur') || 
                         str_starts_with($response, "L'IA a") || 
                         str_starts_with($response, "Je n'ai pas pu") || 
                         str_contains($response, 'API Key') || 
                         empty(trim($response));

        // Fallback to Groq if Gemini fails or JSON is invalid
        if ($isGeminiError || !$json || !isset($json['exercises'])) {
            $response = $this->groqService->generateResponse($prompt);
            $cleanResponse = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($response));
            $json = json_decode($cleanResponse, true);
        }

        if (!$json || !isset($json['exercises'])) {
            return null; // Failed to parse from both providers
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
}
