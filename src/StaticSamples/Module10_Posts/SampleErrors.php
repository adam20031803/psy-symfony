<?php

declare(strict_types=1);

namespace App\StaticSamples\Module10_Posts;

final class SampleErrors
{
    public function errorContenu(): void
    {
        $titre = new Titre();
        $titre->publier();
    }

    public function errorLikeCount(): int
    {
        return 0;
    }
}

/** Classe ajoutée pour corriger l’erreur */
class Titre
{
    public function publier(): void
    {
        // logique ici
    }
}