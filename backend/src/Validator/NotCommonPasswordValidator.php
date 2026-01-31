<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Wikimedia\CommonPasswords\CommonPasswords;

/**
 * Validateur pour la contrainte NotCommonPassword.
 * Vérifie que le mot de passe n'est pas dans la liste des mots de passe courants.
 */
class NotCommonPasswordValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof NotCommonPassword) {
            throw new UnexpectedTypeException($constraint, NotCommonPassword::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!\is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        if (CommonPasswords::isCommon($value)) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}
