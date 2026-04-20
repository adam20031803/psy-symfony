<?php

// src/Service/BadWordChecker.php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Service vérifiant la présence de mots interdits dans un texte.
 * Permet désormais d'utiliser une API externe si BADWORD_API_KEY est configurée.
 */
class BadWordChecker
{
    private array $forbidden = ['ahmed', 'adam', 'bahri', 'akram'];
    private HttpClientInterface $client;
    private string $apiKey;

    public function __construct(HttpClientInterface $client, string $badwordApiKey = '')
    {
        $this->client = $client;
        $this->apiKey = $badwordApiKey;
    }

    public function containsBadWord(string $text): bool
    {
        // 1. Si la clé API est vide, on utilise le tableau local de bas de secours.
        if (empty(trim($this->apiKey))) {
            $textLower = strtolower($text);
            foreach ($this->forbidden as $word) {
                if (str_contains($textLower, $word)) {
                    return true;
                }
            }
            return false;
        }

        // 2. Si l'API est configurée, appel à une API (ex: API Ninjas Profanity Filter)
        try {
            // Remarque: L'URL exacte dépend du fournisseur d'API choisi. (Ici API-Ninjas en exemple)
            $response = $this->client->request('GET', 'https://api.api-ninjas.com/v1/profanityfilter', [
                'headers' => [
                    'X-Api-Key' => $this->apiKey
                ],
                'query' => [
                    'text' => $text
                ],
                'timeout' => 3 // On ne veut pas bloquer l'app trop longtemps
            ]);

            if ($response->getStatusCode() === 200) {
                $data = $response->toArray();
                return $data['has_profanity'] ?? false;
            }
            return false;
        } catch (\Throwable $e) {
            // En cas d'erreur de réseau/API, on peut logguer l'erreur et rendre "false"
            return false;
        }
    }

    /**
     * Censure un texte en remplaçant les mots interdits par ****
     */
    public function censorText(string $text): string
    {
        // 1. Si pas d'API, on utilise la liste locale
        if (empty(trim($this->apiKey))) {
            foreach ($this->forbidden as $word) {
                // Remplacement insensible à la casse
                $pattern = '/' . preg_quote($word, '/') . '/i';
                $text = preg_replace($pattern, '****', $text);
            }
            return $text;
        }

        // 2. Si l'API est configurée
        try {
            $response = $this->client->request('GET', 'https://api.api-ninjas.com/v1/profanityfilter', [
                'headers' => [
                    'X-Api-Key' => $this->apiKey
                ],
                'query' => [
                    'text' => $text
                ],
                'timeout' => 4
            ]);

            if ($response->getStatusCode() === 200) {
                $data = $response->toArray();
                // Si l'API retourne la version censurée, on l'utilise, sinon le texte original
                return $data['censored'] ?? $text;
            }
        } catch (\Throwable $e) {
            // En cas de problème de connexion, on retourne le texte tel quel (ou on peut tomber sur la locale)
        }

        return $text;
    }
}
