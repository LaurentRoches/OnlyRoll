<?php

declare(strict_types=1);

namespace App\DTO\Security;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO pour l'évaluation de la force d'un mot de passe.
 */
final class EvaluatePasswordDTO
{
    #[Assert\NotBlank(message: 'Le mot de passe est requis')]
    #[Assert\Length(
        max: 128,
        maxMessage: 'Le mot de passe ne peut pas dépasser {{ limit }} caractères',
    )]
    public string $password;
}
