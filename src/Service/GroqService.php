<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class GroqService
{
    public function __construct(
        private readonly HttpClientInterface    $httpClient,
        private readonly OllamaFallbackService  $ollama,
        private readonly string                 $groqApiKey,
    ) {}

    /**
     * @param string $prompt
     * @return string
     */
    public function generateResponse(string $prompt): string
    {
        if ($this->groqApiKey && $this->groqApiKey !== '') {
            try {
                $url      = "https://api.groq.com/openai/v1/chat/completions";
                $response = $this->httpClient->request('POST', $url, [
                    'headers' => ['Authorization' => 'Bearer ' . $this->groqApiKey],
                    'json'    => [
                        'model'           => 'llama-3.3-70b-versatile',
                        'messages'        => [
                            ['role' => 'system', 'content' => 'Tu es un assistant de bien-être bienveillant. Réponds uniquement au format JSON comme demandé.'],
                            ['role' => 'user',   'content' => $prompt]
                        ],
                        'temperature'     => 0.7,
                        'max_tokens'      => 1024,
                        'response_format' => ['type' => 'json_object']
                    ]
                ]);
                $data = $response->toArray();
                if (isset($data['choices'][0]['message']['content'])) {
                    return $data['choices'][0]['message']['content'];
                }
            } catch (\Exception) {
                // fall through to Ollama
            }
        }

        // ── Fallback: Ollama ──────────────────────────────────
        if ($this->ollama->isAvailable()) {
            return $this->ollama->generateResponse($prompt);
        }

        return "Je n'ai pas pu générer d'analyse Groq pour le moment.";
    }

    /**
     * Free-form text response (no JSON constraint) — for AI advice, analysis, etc.
     */
    public function generateResponseFreeform(string $prompt): string
    {
        if ($this->groqApiKey && $this->groqApiKey !== '') {
            try {
                $response = $this->httpClient->request('POST', 'https://api.groq.com/openai/v1/chat/completions', [
                    'headers' => ['Authorization' => 'Bearer ' . $this->groqApiKey],
                    'json'    => [
                        'model'       => 'llama-3.3-70b-versatile',
                        'messages'    => [
                            ['role' => 'system', 'content' => 'Tu es un expert en coaching sportif. Réponds en français de façon claire et structurée.'],
                            ['role' => 'user',   'content' => $prompt],
                        ],
                        'temperature' => 0.7,
                        'max_tokens'  => 1500,
                    ]
                ]);
                $data = $response->toArray();
                if (isset($data['choices'][0]['message']['content'])) {
                    return $data['choices'][0]['message']['content'];
                }
            } catch (\Exception) {
                // fall through to Ollama
            }
        }

        // ── Fallback: Ollama ──────────────────────────────────
        if ($this->ollama->isAvailable()) {
            return $this->ollama->generateResponseFreeform($prompt);
        }

        return "Je n'ai pas pu générer de conseils pour le moment.";
    }

    /**
     * Generate response from a conversational array of messages.
     * @param array $messages Array of messages like [['role' => 'system', 'content' => '...'], ...]
     * @return string
     */
    public function generateChatResponse(array $messages): string
    {
        if ($this->groqApiKey && $this->groqApiKey !== '') {
            try {
                $response = $this->httpClient->request('POST', 'https://api.groq.com/openai/v1/chat/completions', [
                    'headers' => ['Authorization' => 'Bearer ' . $this->groqApiKey],
                    'json'    => [
                        'model'       => 'llama-3.3-70b-versatile',
                        'messages'    => $messages,
                        'temperature' => 0.7,
                        'max_tokens'  => 1500,
                    ]
                ]);
                $data = $response->toArray();
                if (isset($data['choices'][0]['message']['content'])) {
                    return $data['choices'][0]['message']['content'];
                }
            } catch (\Exception) {
                // fall through to Ollama
            }
        }

        // ── Fallback: Ollama ──────────────────────────────────
        if ($this->ollama->isAvailable()) {
            return $this->ollama->generateChatResponse($messages);
        }

        return "Je n'ai pas pu générer de réponse pour le moment.";
    }
}
