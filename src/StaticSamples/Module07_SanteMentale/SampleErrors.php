<?php

declare(strict_types=1);

namespace App\StaticSamples\Module07_SanteMentale;

final class SampleErrors
{
    /** Correction 1 : variable définie */
    public function errorVariableInconnue(): string
    {
        $libelleSanteMentale = "Bien-être";
        return $libelleSanteMentale;
    }

    /** Correction 2 : type d’argument correct */
    public function errorTypeIncompatible(): void
    {
        $this->enregistrerScore(5); // int au lieu de string
    }

    /** @param positive-int $score Entre 1 et 10 */
    private function enregistrerScore(int $score): void
    {
        // logique ici
    }
}
