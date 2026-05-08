<?php

namespace App\Controller;

use App\Service\OllamaFallbackService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/test-ollama')]
class OllamaTestController extends AbstractController
{
    public function __construct(
        private readonly OllamaFallbackService $ollama,
    ) {}

    /**
     * Quick status check — is Ollama running?
     * GET /test-ollama/status
     */
    #[Route('/status', name: 'test_ollama_status', methods: ['GET'])]
    public function status(): JsonResponse
    {
        $available = $this->ollama->isAvailable();

        return $this->json([
            'ollama_running' => $available,
            'models'         => ['gemma4:26b', 'llama3:latest'],
            'message'        => $available
                ? '✅ Ollama est disponible et prêt.'
                : '❌ Ollama est hors ligne. Lance-le avec : ollama serve',
        ]);
    }

    /**
     * Test a simple prompt against Ollama.
     * GET /test-ollama/ask?q=Bonjour
     */
    #[Route('/ask', name: 'test_ollama_ask', methods: ['GET'])]
    public function ask(Request $request): JsonResponse
    {
        $question = $request->query->get('q', 'Dis bonjour en français et présente-toi en 2 phrases.');

        if (!$this->ollama->isAvailable()) {
            return $this->json([
                'error'   => true,
                'message' => 'Ollama est hors ligne. Lance-le avec : ollama serve',
            ], 503);
        }

        $start    = microtime(true);
        $response = $this->ollama->generateResponseFreeform($question);
        $elapsed  = round(microtime(true) - $start, 2);

        return $this->json([
            'error'       => false,
            'models'      => ['gemma4:26b', 'llama3:latest'],
            'question'    => $question,
            'response'    => $response,
            'time_seconds'=> $elapsed,
        ]);
    }

    /**
     * Test multi-turn chat format (same as GroqService).
     * POST /test-ollama/chat
     * Body: {"message": "Bonjour !"}
     */
    #[Route('/chat', name: 'test_ollama_chat', methods: ['POST'])]
    public function chat(Request $request): JsonResponse
    {
        $data    = json_decode($request->getContent(), true);
        $message = trim($data['message'] ?? '');

        if (empty($message)) {
            return $this->json(['error' => 'message field is required'], 400);
        }

        if (!$this->ollama->isAvailable()) {
            return $this->json(['error' => 'Ollama est hors ligne.'], 503);
        }

        $start  = microtime(true);
        $result = $this->ollama->generateChatResponse([
            ['role' => 'system', 'content' => 'Tu es un assistant de bien-être bienveillant. Réponds en français.'],
            ['role' => 'user',   'content' => $message],
        ]);

        return $this->json([
            'models'       => ['gemma4:26b', 'llama3:latest'],
            'response'     => $result,
            'time_seconds' => round(microtime(true) - $start, 2),
        ]);
    }
}
