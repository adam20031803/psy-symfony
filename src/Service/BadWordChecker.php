<?php

namespace App\Service;

/**
 * Checks if a string contains any forbidden/bad words.
 */
class BadWordChecker
{
    /** @var string[] */
    private array $badWords = [
        'insulte', 'haine', 'violence', 'spam', 'obscène',
    ];

    public function containsBadWord(string $value): bool
    {
        $lowerValue = mb_strtolower($value);
        foreach ($this->badWords as $word) {
            if (str_contains($lowerValue, $word)) {
                return true;
            }
        }

        return false;
    }
}

