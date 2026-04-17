<?php

namespace App\Service;

/**
 * Suggestions YouTube (recherches) alignées sur le mood via MoodMediaQueryBuilder.
 */
final class YoutubeSuggestionService
{
    public function __construct(
        private readonly MoodMediaQueryBuilder $queries,
    ) {
    }

    /**
     * @return list<array{title: string, channel: string, url: string}>
     */
    public function getSuggestionsForMood(string $moodRaw): array
    {
        return $this->queries->youtubeSuggestionRows($moodRaw);
    }
}
