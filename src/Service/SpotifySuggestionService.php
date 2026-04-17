<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Suggestions Spotify liées au mood : API officielle (client credentials) si
 * SPOTIFY_CLIENT_ID / SPOTIFY_CLIENT_SECRET sont définis, sinon liens de recherche + titres de secours.
 */
final class SpotifySuggestionService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly MoodMediaQueryBuilder $moodMediaQueryBuilder,
        private readonly string $spotifyClientId,
        private readonly string $spotifyClientSecret,
    ) {
    }

    /**
     * @return array<int, array{title: string, artist: string, url: string}>
     */
    public function getTracksForMood(string $mood, int $limit = 8): array
    {
        $limit = max(1, min(20, $limit));
        $clientId = trim($this->spotifyClientId);
        $clientSecret = trim($this->spotifyClientSecret);

        if ('' === $clientId || '' === $clientSecret) {
            return $this->fallbackSuggestions($mood, $limit);
        }

        $token = $this->fetchAccessToken($clientId, $clientSecret);
        if (null === $token) {
            return $this->fallbackSuggestions($mood, $limit);
        }

        $query = $this->moodMediaQueryBuilder->spotifyApiSearchQuery($mood);
        try {
            $response = $this->httpClient->request('GET', 'https://api.spotify.com/v1/search', [
                'headers' => [
                    'Authorization' => 'Bearer '.$token,
                    'Accept' => 'application/json',
                ],
                'query' => [
                    'q' => $query,
                    'type' => 'track',
                    'limit' => $limit,
                ],
                'timeout' => 12,
            ]);
            $data = $response->toArray(false);
        } catch (\Throwable) {
            return $this->fallbackSuggestions($mood, $limit);
        }

        if (($data['tracks']['items'] ?? null) === null || !is_array($data['tracks']['items'])) {
            return $this->fallbackSuggestions($mood, $limit);
        }

        $items = [];
        foreach ($data['tracks']['items'] as $track) {
            if (!is_array($track)) {
                continue;
            }
            $title = trim((string) ($track['name'] ?? ''));
            $artist = trim((string) ($track['artists'][0]['name'] ?? ''));
            $url = trim((string) ($track['external_urls']['spotify'] ?? ''));
            if ('' === $title || '' === $url) {
                continue;
            }
            $items[] = [
                'title' => $title,
                'artist' => '' !== $artist ? $artist : 'Artiste inconnu',
                'url' => $url,
            ];
        }

        return [] !== $items ? $items : $this->fallbackSuggestions($mood, $limit);
    }

    /**
     * @return array<int, array{title: string, artist: string, url: string}>
     */
    private function fallbackSuggestions(string $mood, int $limit): array
    {
        $mood = trim($mood);
        $q = rawurlencode($mood.' musique');
        $head = [
            [
                'title' => 'Recherche Spotify : '.$mood,
                'artist' => 'Ouvre dans Spotify',
                'url' => 'https://open.spotify.com/search/'.$q,
            ],
        ];
        $rest = $this->moodMediaQueryBuilder->spotifyFallbackRows($mood, max(1, $limit - 1));

        return array_slice(array_merge($head, $rest), 0, $limit);
    }

    private function fetchAccessToken(string $clientId, string $clientSecret): ?string
    {
        try {
            $response = $this->httpClient->request('POST', 'https://accounts.spotify.com/api/token', [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Authorization' => 'Basic '.base64_encode($clientId.':'.$clientSecret),
                ],
                'body' => 'grant_type=client_credentials',
                'timeout' => 10,
            ]);
            $data = $response->toArray(false);
            $token = trim((string) ($data['access_token'] ?? ''));

            return '' !== $token ? $token : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
