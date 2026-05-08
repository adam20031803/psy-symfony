<?php

declare(strict_types=1);

namespace App\StaticSamples\Module01_Auth;

/** Échantillons volontaires pour PHPStan — ne pas utiliser en prod. */
final class SampleErrors
{
    public function errorOne(): int
    {
        return $notDefined;
    }

    public function errorTwo(): void
    {
        $this->needsString(123);
    }

    private function needsString(string $s): void {}
}
