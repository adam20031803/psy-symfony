<?php

namespace App\Validator;

use App\Service\AiModerator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class BadWordValidator extends ConstraintValidator
{
    public function __construct(private AiModerator $moderator) {}

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof BadWord) {
            throw new UnexpectedTypeException($constraint, BadWord::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        $analysis = $this->moderator->checkContent((string) $value);

        if ($analysis['valid'] === false) {
            $this->context->buildViolation($analysis['reason'])
                ->setParameter('{{ value }}', $value)
                ->addViolation();
        }
    }
}
