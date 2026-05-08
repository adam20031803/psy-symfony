<?php

declare(strict_types=1);

namespace App\Tests\StaticSamples;

/**
 * Fichier volontairement incorrect — pour valider PHPStan / l’analyse statique.
 * Ne pas inclure dans l’autoload de production.
 */
final class IntentionalPhpStanViolations
{
    /** Erreur 1 : variable non définie */
    public function errorUndefinedVariable(): int
    {
        return $thisVariableDoesNotExist;
    }

    /** Erreur 2 : type d’argument incompatible */
    public function errorWrongArgumentType(): void
    {
        $this->expectsStringOnly(123);
    }

    private function expectsStringOnly(string $value): void
    {
    }

    /** Erreur 3 : code mort après return */
    public function errorDeadCode(): int
    {
        return 0;
        $unreachable = 1;

        return $unreachable;
    }

    /** Erreur 4 : clé de tableau inexistante */
    public function errorUndefinedArrayOffset(): void
    {
        $row = ['id' => 1];
        echo $row['title'];
    }

    /** Erreur 5 : appel de méthode sur une chaîne */
    public function errorMethodOnString(): void
    {
        $s = 'hello';
        $s->invalidMethod();
    }

    /** Erreur 6 : valeur de retour manquante sur un chemin */
    public function errorMissingReturn(bool $flag): int
    {
        if ($flag) {
            return 1;
        }
    }
}
