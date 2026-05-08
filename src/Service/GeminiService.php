<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\Attribute\Autocomplete;

class GeminiService
{
    public function __construct(
        private readonly HttpClientInterface    $httpClient,
        private readonly OllamaFallbackService  $ollama,
        private readonly string                 $geminiApiKey,
    ) {}

    /**
     * @param string $prompt
     * @return string
     */
    public function generateResponse(string $prompt): string
    {
        // ── Try Gemini first ────────────────────────────────────
        if ($this->geminiApiKey && $this->geminiApiKey !== '') {
            try {
                $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $this->geminiApiKey;

                $response = $this->httpClient->request('POST', $url, [
                    'json' => [
                        'contents' => [
                            ['parts' => [['text' => $prompt]]]
                        ],
                        'generationConfig' => [
                            'temperature'     => 0.7,
                            'maxOutputTokens' => 1000,
                        ],
                        'safetySettings' => [
                            ['category' => 'HARM_CATEGORY_HARASSMENT',        'threshold' => 'BLOCK_ONLY_HIGH'],
                            ['category' => 'HARM_CATEGORY_HATE_SPEECH',       'threshold' => 'BLOCK_ONLY_HIGH'],
                            ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_ONLY_HIGH'],
                            ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_ONLY_HIGH'],
                        ]
                    ]
                ]);

                $data = $response->toArray();

                if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                    return $data['candidates'][0]['content']['parts'][0]['text'];
                }

                if (isset($data['candidates'][0]['finishReason'])) {
                    return "L'IA a terminé avec la raison : " . $data['candidates'][0]['finishReason'] . ". Cela arrive parfois sur des sujets sensibles.";
                }

            } catch (\Exception) {
                // fall through to Ollama
            }
        }

        // ── Fallback: Ollama local ───────────────────────────
        if ($this->ollama->isAvailable()) {
            return $this->ollama->generateResponse($prompt);
        }

        return "Je n'ai pas pu générer d'analyse pour le moment (service IA indisponible).";
    }
}
