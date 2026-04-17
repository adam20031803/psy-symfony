<?php

namespace App\Service;

/**
 * Détecte une « famille » de mood et produit des requêtes distinctes pour Spotify / YouTube.
 */
final class MoodMediaQueryBuilder
{
    /**
     * @return non-empty-string
     */
    public function detectCategory(string $mood): string
    {
        $m = mb_strtolower(trim($mood));

        $rules = [
            'stress' => ['stress', 'anx', 'angoiss', 'panique', 'peur', 'nerf', 'overwhel'],
            'sad' => ['trist', 'sad', 'mal', 'depress', 'pleur', 'vide', 'seul', 'solitud', 'chagrin', 'malheur'],
            'tired' => ['fatigu', 'epuis', 'coup', 'somnol', 'dormir', 'lass'],
            'happy' => ['heur', 'joy', 'content', 'ravi', 'super', 'génial', 'fête', 'fière'],
            'calm' => ['calme', 'zen', 'paix', 'serein', 'doux', 'méditat', 'relax'],
            'angry' => ['coler', 'énerv', 'enerve', 'fur', 'rage', 'frustr'],
            'sport' => ['sport', 'gym', 'run', 'cardio', 'musc', 'workout'],
        ];

        foreach ($rules as $category => $keywords) {
            foreach ($keywords as $kw) {
                if (str_contains($m, $kw)) {
                    return $category;
                }
            }
        }

        return 'neutral';
    }

    /**
     * Requête pour l’API Spotify Search (le libellé du mood en premier pour différencier les résultats).
     */
    public function spotifyApiSearchQuery(string $moodRaw): string
    {
        $moodRaw = trim($moodRaw);
        $cat = $this->detectCategory($moodRaw);
        $token = '' !== $moodRaw ? mb_strtolower($moodRaw) : 'bien être';

        $tail = match ($cat) {
            'stress' => 'lofi calm breathing meditation instrumental sleep',
            'sad' => 'hope healing gentle acoustic piano emotional comfort',
            'tired' => 'soft beats ambient recharge focus low energy',
            'happy' => 'upbeat dance pop celebration sunshine energy',
            'calm' => 'ambient peaceful nature sounds piano zen',
            'angry' => 'cool down walk slow tempo acoustic release',
            'sport' => 'workout gym running motivation edm rock high bpm',
            default => 'chill playlist vibes acoustic french',
        };

        return $token.' '.$tail;
    }

    /**
     * Plusieurs recherches Spotify (secours), chaque URL est unique selon mood + catégorie.
     *
     * @return list<array{title: string, artist: string, url: string}>
     */
    public function spotifyFallbackRows(string $moodRaw, int $max = 8): array
    {
        $moodRaw = trim($moodRaw);
        $m = mb_strtolower($moodRaw);
        $cat = $this->detectCategory($moodRaw);

        $packs = match ($cat) {
            'stress' => [
                ['t' => 'Détente & respiration', 'q' => $moodRaw.' musique détente anti-stress'],
                ['t' => 'Lofi calme', 'q' => $moodRaw.' lofi calm study'],
                ['t' => 'Méditation douce', 'q' => 'meditation musique douce '.$moodRaw],
                ['t' => 'Sommeil léger', 'q' => 'sleep music peaceful '.$moodRaw],
                ['t' => 'Nature & piano', 'q' => 'piano nature relaxation '.$moodRaw],
            ],
            'sad' => [
                ['t' => 'Réconfort', 'q' => $moodRaw.' musique réconfortante douce'],
                ['t' => 'Acoustique apaisante', 'q' => $moodRaw.' acoustic hopeful songs'],
                ['t' => 'Piano émotion', 'q' => 'sad piano emotional '.$moodRaw],
                ['t' => 'Voix chaude', 'q' => $moodRaw.' chanson française triste douce'],
                ['t' => 'Relever la tête', 'q' => 'uplifting gentle '.$moodRaw.' playlist'],
            ],
            'tired' => [
                ['t' => 'Recharge douce', 'q' => $moodRaw.' musique douce réveil'],
                ['t' => 'Lofi énergie basse', 'q' => $moodRaw.' lofi chill energy'],
                ['t' => 'Ambient focus', 'q' => 'ambient focus soft '.$moodRaw],
                ['t' => 'Café lent', 'q' => $moodRaw.' café acoustique lent'],
            ],
            'happy' => [
                ['t' => 'Feel good', 'q' => $moodRaw.' feel good pop playlist'],
                ['t' => 'Danse & joie', 'q' => $moodRaw.' dance party happy music'],
                ['t' => 'Indie soleil', 'q' => 'indie happy sunshine '.$moodRaw],
                ['t' => 'Hits positifs', 'q' => $moodRaw.' hits positifs français'],
            ],
            'calm' => [
                ['t' => 'Zen & paix', 'q' => $moodRaw.' zen musique paix'],
                ['t' => 'Ambient spa', 'q' => 'ambient spa meditation '.$moodRaw],
                ['t' => 'Piano minimal', 'q' => $moodRaw.' piano minimal calme'],
                ['t' => 'Nature sons', 'q' => 'nature sounds calm '.$moodRaw],
            ],
            'angry' => [
                ['t' => 'Descente en température', 'q' => $moodRaw.' musique calmer colère'],
                ['t' => 'Acoustique lente', 'q' => 'slow acoustic cool down '.$moodRaw],
                ['t' => 'Jazz cool', 'q' => $moodRaw.' jazz cool relax'],
            ],
            'sport' => [
                ['t' => 'Workout intense', 'q' => $moodRaw.' workout motivation edm'],
                ['t' => 'Running', 'q' => 'running music high bpm '.$moodRaw],
                ['t' => 'Hip-hop gym', 'q' => $moodRaw.' hip hop gym playlist'],
                ['t' => 'Rock énergie', 'q' => 'rock workout energy '.$moodRaw],
            ],
            default => [
                ['t' => 'Ambiance « '.$moodRaw.' »', 'q' => $moodRaw.' musique ambiance playlist'],
                ['t' => 'Bien-être', 'q' => 'wellbeing chill music '.$moodRaw],
                ['t' => 'Découverte', 'q' => $moodRaw.' indie français découverte'],
                ['t' => 'Focus doux', 'q' => 'focus study instrumental '.$moodRaw],
            ],
        };

        $rows = [];
        foreach ($packs as $i => $p) {
            $rows[] = [
                'title' => $p['t'],
                'artist' => 'Spotify · '.($i + 1),
                'url' => 'https://open.spotify.com/search/'.rawurlencode($p['q']),
            ];
        }

        $n = 0;
        while (count($rows) < $max) {
            ++$n;
            $rows[] = [
                'title' => 'Plus pour « '.$moodRaw.' » ('.$n.')',
                'artist' => 'Spotify',
                'url' => 'https://open.spotify.com/search/'.rawurlencode($moodRaw.' '.$cat.' musique '.$n),
            ];
        }

        return array_slice($rows, 0, max(1, min($max, count($rows))));
    }

    /**
     * Suggestions YouTube (liens recherche), toutes différentes selon mood / catégorie.
     *
     * @return list<array{title: string, channel: string, url: string}>
     */
    public function youtubeSuggestionRows(string $moodRaw): array
    {
        $moodRaw = trim($moodRaw);
        $cat = $this->detectCategory($moodRaw);

        $packs = match ($cat) {
            'stress' => [
                ['t' => 'Respiration guidée', 'q' => 'respiration guidée stress '.$moodRaw],
                ['t' => 'ASMR calme', 'q' => 'asmr calme anti anxiété '.$moodRaw],
                ['t' => 'Yoga 10 min', 'q' => 'yoga 10 minutes débutant stress'],
                ['t' => 'Musique lofi', 'q' => 'lofi hip hop relax '.$moodRaw],
                ['t' => 'Méditation courte', 'q' => 'méditation guidée courte français'],
            ],
            'sad' => [
                ['t' => 'Message positif', 'q' => 'motivation douce tristesse '.$moodRaw],
                ['t' => 'Musique réconfort', 'q' => 'musique réconfortante piano '.$moodRaw],
                ['t' => 'Journal + musique', 'q' => 'self care evening music '.$moodRaw],
                ['t' => 'Documentaire bien-être', 'q' => 'ted talk français émotions'],
                ['t' => 'Chanson apaisante', 'q' => 'chanson douce français '.$moodRaw],
            ],
            'tired' => [
                ['t' => 'Réveil en douceur', 'q' => 'morning stretch gentle '.$moodRaw],
                ['t' => 'Café & jazz lent', 'q' => 'coffee jazz slow '.$moodRaw],
                ['t' => 'Micro-sieste', 'q' => 'power nap music soft'],
                ['t' => 'Hydratation routine', 'q' => 'healthy morning routine français'],
            ],
            'happy' => [
                ['t' => 'Playlist fête', 'q' => 'playlist joie danse '.$moodRaw],
                ['t' => 'Hits positifs', 'q' => 'positive hits workout dance '.$moodRaw],
                ['t' => 'Karaoké fun', 'q' => 'karaoke party français fun'],
                ['t' => 'Vlog bonne humeur', 'q' => 'day in my life happy vlog music'],
            ],
            'calm' => [
                ['t' => 'Forêt & rivière', 'q' => 'forêt sons nature relaxation '.$moodRaw],
                ['t' => 'Piano zen', 'q' => 'piano zen méditation '.$moodRaw],
                ['t' => 'Gratitude 5 min', 'q' => 'gratitude meditation 5 minutes français'],
            ],
            'angry' => [
                ['t' => 'Respiration colère', 'q' => 'respiration colère calmer français'],
                ['t' => 'Marche lente', 'q' => 'walking meditation calm '.$moodRaw],
                ['t' => 'Jazz cool', 'q' => 'cool jazz relax '.$moodRaw],
            ],
            'sport' => [
                ['t' => 'HIIT motivation', 'q' => 'hiit workout music motivation '.$moodRaw],
                ['t' => 'Running 170 bpm', 'q' => 'running music 170 bpm'],
                ['t' => 'Stretching post-sport', 'q' => 'stretching après sport français'],
                ['t' => 'Hype gym', 'q' => 'gym hype playlist '.$moodRaw],
            ],
            default => [
                ['t' => 'Tout sur « '.$moodRaw.' »', 'q' => $moodRaw.' musique bien-être français'],
                ['t' => 'Podcast humeur', 'q' => 'podcast émotions français '.$moodRaw],
                ['t' => 'Ambiance focus', 'q' => 'focus music study '.$moodRaw],
            ],
        };

        $rows = [];
        foreach ($packs as $i => $p) {
            $rows[] = [
                'title' => $p['t'],
                'channel' => 'YouTube · '.($i + 1),
                'url' => 'https://www.youtube.com/results?search_query='.rawurlencode($p['q']),
            ];
        }

        $target = 6;
        $n = 0;
        while (count($rows) < $target) {
            ++$n;
            $rows[] = [
                'title' => 'Autre idée « '.$moodRaw.' » ('.$n.')',
                'channel' => 'YouTube',
                'url' => 'https://www.youtube.com/results?search_query='.rawurlencode($moodRaw.' bien-être vidéo '.$cat.' '.$n),
            ];
        }

        return array_slice($rows, 0, $target);
    }
}
