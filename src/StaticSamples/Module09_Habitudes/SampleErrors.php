<?php

declare(strict_types=1);

namespace App\StaticSamples\Module09_Habitudes;

final class SampleErrors
{
    public function errorStreak(): void
    {
        $habitudes = [
            'eau' => true,
            'sport' => false // ajouté
        ];

        echo $habitudes['sport'];
    }

    public function errorCompletion(bool $ok): int
    {
        if ($ok) {
            return 100;
        }

        return 0; // ajouté
    }
}