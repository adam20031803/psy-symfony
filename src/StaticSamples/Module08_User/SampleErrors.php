<?php

declare(strict_types=1);

namespace App\StaticSamples\Module08_User;

final class SampleErrors
{
    public function errorProfilIncomplet(): string
    {
        $emailUtilisateur = 'user@example.com';

        return $emailUtilisateur;
    }

    public function errorAgeInvalide(): void
    {
        $this->setAge(25);
    }

    private function setAge(int $age): void
    {
    }
}