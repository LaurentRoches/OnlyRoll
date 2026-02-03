<?php

declare(strict_types=1);

namespace App\DTO\Security;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO pour les options de génération de mot de passe.
 */
final class GeneratePasswordDTO
{
    #[Assert\Range(
        min: 12,
        max: 128,
        notInRangeMessage: 'La longueur doit être entre {{ min }} et {{ max }} caractères'
    )]
    public int $length = 16;

    public bool $includeLowercase = true;

    public bool $includeUppercase = true;

    public bool $includeDigits = true;

    public bool $includeSpecial = true;

    #[Assert\Type('bool')]
    public bool $excludeAmbiguous = false;

    #[Assert\Range(
        min: 1,
        max: 10,
        notInRangeMessage: 'Le nombre de mots de passe doit être entre {{ min }} et {{ max }}'
    )]
    public int $count = 1;
}
