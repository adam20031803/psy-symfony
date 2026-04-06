<?php

// src/Validator/PostValidator.php

namespace App\Validator;

use App\Service\BadWordChecker;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Validateur contenant la méthode de callback pour vérifier les mots interdits.
 */
class PostValidator
{
    public static function checkBadWords(mixed $value, ExecutionContextInterface $context): void
    {
        if (null === $value || '' === $value) {
            return;
        }

        // Instancie le service manuellement comme demandé
        $checker = new BadWordChecker();
        if ($checker->containsBadWord((string) $value)) {
            $context->buildViolation('Ce champ contient un mot interdit.')
                ->addViolation();
        }
    }
}
