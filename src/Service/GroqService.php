<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class GroqService
{
    private string $apiKey;
    private HttpClientInterface $httpClient;

    public function __construct(HttpClientInterface $httpClient, string $groqApiKey)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $groqApiKey;
    }

    /**
     * @param string $prompt
     * @return string
     */
    public function generateResponse(string $prompt): string
    {
        if (!$this->apiKey || $this->apiKey === '') {
            return "Groq API Key is missing.";
        }

        try {
            $url = "https://api.groq.com/openai/v1/chat/completions";

            $response = $this->httpClient->request('POST', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ],
                'json' => [
                    'model' => 'llama-3.3-70b-versatile',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Tu es un assistant de bien-être bienveillant. Réponds uniquement au format JSON comme demandé.'
                        ],
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ]
                    ],
                    'temperature' => 0.7,
                    'max_tokens' => 1024,
                    'response_format' => ['type' => 'json_object']
                ]
            ]);

            // Note: Some models support 'json_object', but Groq uses 'json_object' or just expects it in prompt.
            // Simplified for compatibility:
            $data = $response->toArray();
            
            if (isset($data['choices'][0]['message']['content'])) {
                return $data['choices'][0]['message']['content'];
            }

            return "Je n'ai pas pu générer d'analyse Groq pour le moment.";

        } catch (\Exception $e) {
            return "Erreur Groq: " . $e->getMessage();
        }
    }

    /**
     * Free-form text response (no JSON constraint) — for AI advice, analysis, etc.
     */
    public function generateResponseFreeform(string $prompt): string
    {
        if (!$this->apiKey || $this->apiKey === '') {
            return "Groq API Key is missing.";
        }

        try {
            $response = $this->httpClient->request('POST', 'https://api.groq.com/openai/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ],
                'json' => [
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

            return "Je n'ai pas pu générer de conseils pour le moment.";

        } catch (\Exception $e) {
            return "Erreur Groq (freeform): " . $e->getMessage();
        }
    }
}
