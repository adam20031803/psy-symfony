<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class BadWord extends Constraint
{
    public string $message = 'Le texte "{{ value }}" contient des mots non autorisés.';
}
