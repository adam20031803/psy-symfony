<?php

namespace App\Controller\Api;


use App\Service\SpotifySuggestionService;
use App\Service\YoutubeSuggestionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api', name: 'api_')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
final class MentalApiController extends AbstractController
{
    public function __construct(
        private readonly SpotifySuggestionService $spotifySuggestionService,
        private readonly YoutubeSuggestionService $youtubeSuggestionService,
        private readonly \App\Service\GroqService $groqService,
    ) {
    }

    #[Route('/chatbot/ask', name: 'chatbot_ask', methods: ['POST'])]
    public function askChatbot(Request $request): JsonResponse
    {
        /** @var array{message?: string} $payload */
        $payload = json_decode($request->getContent(), true) ?? [];
        $message = trim((string) ($payload['message'] ?? ''));
        $session = $request->getSession();

        if ('' === $message) {
            return $this->json([
                'success' => true,
                'phase' => 'coaching',
                'answer' => 'Bonjour ! Je suis là pour t\'écouter et t\'accompagner sur ta santé mentale. Que ressens-tu ou de quoi aimerais-tu parler aujourd\'hui ?',
            ]);
        }

        $history = $session->get('chatbot_history', []);
        
        // Add user message to history
        $history[] = ['role' => 'user', 'content' => $message];

        // Prepare context for Groq
        $systemMessage = [
            'role' => 'system',
            'content' => 'Tu es un psychologue et coach en santé mentale bienveillant. Ton but est de donner des conseils sur la santé mentale de l\'utilisateur. Avant de donner une réponse complète ou des conseils génériques, pose des questions pertinentes et empathiques pour mieux comprendre sa situation afin de donner les meilleurs recommandations possibles. Sois toujours chaleureux, concis et constructif.'
        ];

        $messagesForGroq = array_merge([$systemMessage], $history);

        // Call Groq
        $answer = $this->groqService->generateChatResponse($messagesForGroq);

        // Add AI answer to history
        $history[] = ['role' => 'assistant', 'content' => $answer];

        // Keep history manageable (e.g., last 20 messages)
        if (count($history) > 20) {
            $history = array_slice($history, -20);
        }

        $session->set('chatbot_history', $history);

        return $this->json([
            'success' => true,
            'phase' => 'coaching',
            'answer' => $answer,
        ]);
    }

    #[Route('/chatbot/reset', name: 'chatbot_reset', methods: ['POST'])]
    public function resetChatbot(Request $request): JsonResponse
    {
        $request->getSession()->remove('chatbot_history');

        return $this->json([
            'success' => true,
            'message' => 'Chat réinitialisé.',
        ]);
    }

    #[Route('/mental/entries/advice', name: 'mental_entries_advice', methods: ['POST'])]
    public function entryAdvice(Request $request): JsonResponse
    {
        /** @var array{mood?: string, emotionLevel?: int|string, activity?: string, note?: string} $payload */
        $payload = json_decode($request->getContent(), true) ?? [];
        $mood = trim((string) ($payload['mood'] ?? 'neutre'));
        $emotionLevel = (int) ($payload['emotionLevel'] ?? 5);
        $activity = trim((string) ($payload['activity'] ?? ''));
        $note = trim((string) ($payload['note'] ?? ''));

        $advice = sprintf(
            'Mood %s, niveau %d/10. Fais une action courte de 5 minutes: respiration, eau, puis reprends %s. %s',
            $mood,
            max(1, min(10, $emotionLevel)),
            '' !== $activity ? $activity : 'ta priorité',
            '' !== $note ? 'Note utile: garde uniquement la prochaine étape.' : 'Ensuite, note en une phrase ton ressenti.'
        );
        $advice .= ' Tu peux aussi cliquer sur « Spotify » ou « YouTube » pour des suggestions liées à ce mood.';

        return $this->json([
            'success' => true,
            'advice' => $advice,
        ]);
    }

    #[Route('/mental/entries/media', name: 'mental_entries_media', methods: ['POST'])]
    public function entryMedia(Request $request): JsonResponse
    {
        /** @var array{mood?: string} $payload */
        $payload = json_decode($request->getContent(), true) ?? [];
        $moodRaw = trim((string) ($payload['mood'] ?? ''));
        $mood = mb_strtolower($moodRaw);

        if ('' === $mood) {
            return $this->json([
                'success' => false,
                'message' => 'Mood requis.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $spotify = $this->spotifySuggestionService->getTracksForMood($moodRaw, 8);
        $youtube = $this->youtubeSuggestionService->getSuggestionsForMood($moodRaw);

        return $this->json([
            'success' => true,
            'spotify' => $spotify,
            'youtube' => $youtube,
        ]);
    }

    #[Route('/mental/entries/weather-advice', name: 'mental_entries_weather_advice', methods: ['POST'])]
    public function weatherAdvice(Request $request): JsonResponse
    {
        /** @var array{city?: string, mood?: string} $payload */
        $payload = json_decode($request->getContent(), true) ?? [];
        $city = trim((string) ($payload['city'] ?? 'Tunis'));
        $mood = trim((string) ($payload['mood'] ?? ''));

        if ('' === $mood) {
            return $this->json([
                'success' => false,
                'message' => 'Mood requis.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Pas d’API météo : température stable pour la même ville + mood + jour
        // (évite random_int qui change à chaque clic).
        $normalizedCity = mb_strtolower(trim(preg_replace('/\s+/u', ' ', $city) ?? ''));
        $normalizedMood = mb_strtolower(trim(preg_replace('/\s+/u', ' ', $mood) ?? ''));
        $dayKey = (new \DateTimeImmutable('today'))->format('Y-m-d');
        $hash = crc32($normalizedCity.'|'.$normalizedMood.'|'.$dayKey);
        $temp = 16 + (abs($hash) % 19);
        $feelsLike = $temp - 1;

        $desc = $temp >= 29 ? 'chaud et sec' : ($temp <= 19 ? 'frais' : 'agréable');
        $advice = sprintf(
            'Avec un mood "%s" et une météo %s, prends 5 minutes dehors puis reviens sur une tâche simple.',
            $mood,
            $desc
        );

        return $this->json([
            'success' => true,
            'mood' => $mood,
            'weather' => [
                'city' => $city,
                'description' => $desc,
                'temp' => $temp,
                'feelsLike' => $feelsLike,
            ],
            'advice' => $advice,
        ]);
    }


}
