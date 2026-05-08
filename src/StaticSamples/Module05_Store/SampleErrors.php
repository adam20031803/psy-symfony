<?php

declare(strict_types=1);

namespace App\StaticSamples\Module05_Store;

final class SampleErrors
{
    public function errorOne(): string
    {
        return [];
    }

    public function errorTwo(): void
    {
        $this->expectsPositive(-1);
    }

    /** @param positive-int $n */
    private function expectsPositive(int $n): void {}
}
