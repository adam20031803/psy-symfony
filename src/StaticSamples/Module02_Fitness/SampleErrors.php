<?php

declare(strict_types=1);

namespace App\StaticSamples\Module02_Fitness;

final class SampleErrors
{
    public function errorOne(): int
    {
        $x = 1; // déplacé avant return
        return $x;
    }

    public function errorTwo(): void
    {
        $t = [
            'id' => 1,
            'name' => 'Fitness' // ajouté
        ];

        echo $t['name'];
    }
}