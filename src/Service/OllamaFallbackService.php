<?php

namespace App\Service;

use Psr\Log\LoggerInterface;

/**
 * Ollama local fallback service.
 *
 * Supports both the modern /api/chat endpoint (Ollama >= 0.1.14)
 * and the legacy /api/generate endpoint for older installs.
 *
 * Mirrors GeminiService / GroqService method signatures so it can
 * be swapped in transparently.
 */
class OllamaFallbackService
{
    private const BASE_URL = 'http://localhost:11434';
    /** Fast model first for structured JSON; heavy model second */
    private const MODELS   = ['llama3:latest', 'gemma4:26b'];
    private const DEFAULT_TIMEOUT = 60;

    /** Cached after first call */
    private ?bool $chatEndpointAvailable = null;

    public function __construct(
        private readonly LoggerInterface     $logger,
    ) {}

    // ─────────────────────────────────────────────────────────────────
    //  PUBLIC API  (same signatures as GeminiService / GroqService)
    // ─────────────────────────────────────────────────────────────────

    /**
     * Single-turn JSON-format prompt.
     *
     * @param bool $ollamaJsonMode If true, asks Ollama for JSON via API `format: json` (more reliable parsing).
     */
    public function generateResponse(string $prompt, bool $ollamaJsonMode = false): string
    {
        return $this->callOllama([
            ['role' => 'system', 'content' => 'Tu es un assistant de bien-être bienveillant. Réponds uniquement au format JSON comme demandé.'],
            ['role' => 'user',   'content' => $prompt],
        ], $ollamaJsonMode);
    }

    /** Single-turn freeform text prompt */
    public function generateResponseFreeform(string $prompt): string
    {
        return $this->callOllama([
            ['role' => 'system', 'content' => 'Tu es un assistant de bien-être bienveillant. Réponds en français de façon claire et bienveillante.'],
            ['role' => 'user',   'content' => $prompt],
        ]);
    }

    /** Multi-turn chat (same array format as GroqService) */
    public function generateChatResponse(array $messages): string
    {
        return $this->callOllama($messages);
    }

    // ─────────────────────────────────────────────────────────────────
    //  CONNECTIVITY CHECK
    // ─────────────────────────────────────────────────────────────────

    public function isAvailable(): bool
    {
        try {
            [$status] = $this->request('GET', self::BASE_URL . '/api/tags', null, 3);
            return $status === 200;
        } catch (\Throwable) {
            return false;
        }
    }

    // ─────────────────────────────────────────────────────────────────
    //  INTERNAL — tries /api/chat first, falls back to /api/generate
    // ─────────────────────────────────────────────────────────────────

    private function callOllama(array $messages, bool $jsonFormat = false): string
    {
        foreach (self::MODELS as $model) {
            $result = $this->callGenerate($messages, $model, $jsonFormat);
            if ($result !== null) {
                return $result;
            }
        }

        return 'Désolé, le service IA local (Ollama) est indisponible.';
    }

    /** POST /api/chat  (Ollama >= 0.1.14) */
    private function callChat(array $messages, string $model, bool &$chatEndpointSupported): ?string
    {
        try {
            [$status, $data] = $this->request(
                'POST',
                self::BASE_URL . '/api/chat',
                [
                    'model'    => $model,
                    'messages' => $messages,
                    'stream'   => false,
                ],
                $this->getTimeoutForModel($model)
            );

            if ($status === 404) {
                $chatEndpointSupported = false;
                return null; // endpoint not supported — trigger legacy fallback
            }
            if ($status !== 200) {
                $this->logger->warning(sprintf('[Ollama] /api/chat returned status %d for model %s', $status, $model));
                return null;
            }

            $content = trim((string)($data['message']['content'] ?? ''));
            if ($content === '') {
                $this->logger->warning(sprintf('[Ollama] Empty /api/chat response for model %s', $model));
                return null;
            }

            return $content;

        } catch (\Throwable $e) {
            $this->logger->error(sprintf('[Ollama] /api/chat error for model %s: %s', $model, $e->getMessage()));
            return null;
        }
    }

    /** POST /api/generate  (all Ollama versions) */
    private function callGenerate(array $messages, string $model, bool $jsonFormat = false): ?string
    {
        // Flatten the message array into a single prompt string
        $prompt = '';
        foreach ($messages as $msg) {
            $role    = $msg['role'] ?? 'user';
            $content = $msg['content'] ?? '';
            if ($role === 'system') {
                $prompt .= "Instruction système : {$content}\n\n";
            } else {
                $prompt .= $content . "\n";
            }
        }

        $payload = [
            'model'  => $model,
            'prompt' => trim($prompt),
            'stream' => false,
        ];
        if ($jsonFormat) {
            $payload['format'] = 'json';
        }

        try {
            [, $data] = $this->request(
                'POST',
                self::BASE_URL . '/api/generate',
                $payload,
                $this->getTimeoutForModel($model, $jsonFormat)
            );

            $content = trim((string)($data['response'] ?? ''));
            if ($content === '') {
                $this->logger->warning(sprintf('[Ollama] Empty /api/generate response for model %s', $model));
                return null;
            }

            return $content;

        } catch (\Throwable $e) {
            $this->logger->error(sprintf('[Ollama] /api/generate error for model %s: %s', $model, $e->getMessage()));
            return null;
        }
    }

    private function getTimeoutForModel(string $model, bool $longJsonGeneration = false): int
    {
        if ($longJsonGeneration) {
            return $model === 'gemma4:26b' ? 150 : 180;
        }

        if ($model === 'gemma4:26b') {
            return 45;
        }

        return 60;
    }

    /**
     * Lightweight local HTTP helper using raw cURL.
     *
     * @return array{0:int,1:array<string,mixed>}
     */
    private function request(string $method, string $url, ?array $payload, int $timeout): array
    {
        $ch = curl_init($url);
        if ($ch === false) {
            throw new \RuntimeException('Failed to initialize cURL.');
        }

        $json = $payload !== null
            ? json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : '';

        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json'],
            CURLOPT_CUSTOMREQUEST  => $method,
        ];

        if ($method !== 'GET' && $payload !== null) {
            $options[CURLOPT_POSTFIELDS] = $json;
        }

        curl_setopt_array($ch, $options);

        $raw = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            throw new \RuntimeException(sprintf('HTTP request failed for %s %s: %s', $method, $url, $error));
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            throw new \RuntimeException(sprintf('Invalid JSON response from %s', $url));
        }

        return [$status, $decoded];
    }
}
