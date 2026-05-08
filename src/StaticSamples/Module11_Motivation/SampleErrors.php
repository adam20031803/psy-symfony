<?php

declare(strict_types=1);

namespace App\StaticSamples\Module11_Motivation;

final class SampleErrors
{
    public function errorScore(): void
    {
        /** @var list<string> $messages */
        $messages = ['Allez !'];

        echo $messages[0];
    }

    public function errorSeuil(): void
    {
        $this->notifierSiSupérieurÀ(5);
    }

    /** @param positive-int $niveau */
    private function notifierSiSupérieurÀ(int $niveau): void
    {
    }
}