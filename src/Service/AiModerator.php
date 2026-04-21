<?php

namespace App\Service;

class AiModerator
{
    public function __construct(
        private GeminiService $gemini,
        private GroqService $groq
    ) {}

    /**
     * Analyzes text for inappropriate content.
     * Returns ['valid' => true] or ['valid' => false, 'reason' => '...']
     */
    public function checkContent(string $text): array
    {
        if (empty(trim($text))) {
            return ['valid' => true];
        }

        $prompt = <<<PROMPT
You are a high-end AI content moderator for 'Atomic You', a wellness and psychology community.
Analyze the following text provided by a user.
Detect if it contains:
1. Offensive language or insults.
2. Hate speech or harassment.
3. Inappropriate nonsense (bêtises) or spam.
4. Highly aggressive or negative sentiment unsuitable for a wellness community.

Text to analyze: "{$text}"

Response format:
Respond ONLY with "VALID" or "BLOCKED: <reason in French>".
PROMPT;

        // Try Gemini first
        $response = $this->gemini->generateResponse($prompt);
        $result = $this->parseResponse($response);

        if ($result['valid'] === false || $result['checked_by_ai'] === true) {
             return $result;
        }

        // Fallback to Groq
        $response = $this->groq->generateResponseFreeform($prompt);
        return $this->parseResponse($response);
    }

    private function parseResponse(string $response): array
    {
        $response = trim($response);
        if (stripos($response, 'VALID') === 0) {
            return ['valid' => true, 'checked_by_ai' => true];
        }

        if (stripos($response, 'BLOCKED') === 0) {
            $reason = substr($response, 8); // Remove "BLOCKED: "
            return ['valid' => false, 'reason' => trim($reason) ?: 'Contenu inapproprié.', 'checked_by_ai' => true];
        }

        return ['valid' => true, 'checked_by_ai' => false]; // Ambiguous/Error: allow or retry
    }
}
