<?php

declare(strict_types=1);

namespace App\Validator;

use Attribute;
use Symfony\Component\Validator\Constraint;

/**
 * Contrainte de validation pour interdire les mots de passe trop courants.
 * Utilise la base de données wikimedia/common-passwords.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class NotCommonPassword extends Constraint
{
    public string $message = 'Ce mot de passe est trop courant. Veuillez en choisir un plus sécurisé.';
}
