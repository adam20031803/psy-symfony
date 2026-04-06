<?php

// src/Service/BadWordChecker.php

namespace App\Service;

/**
 * Service vérifiant la présence de mots interdits dans un texte.
 */
class BadWordChecker
{
    private array $forbidden = ['ahmed', 'adam', 'bahri', 'akram'];

    public function containsBadWord(string $text): bool
    {
        $textLower = strtolower($text);
        foreach ($this->forbidden as $word) {
            if (str_contains($textLower, $word)) {
                return true;
            }
        }
        
        return false;
    }
}
