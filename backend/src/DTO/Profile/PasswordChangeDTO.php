<?php

declare(strict_types=1);

namespace App\DTO\Profile;

use App\Validator\NotCommonPassword;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO pour le changement de mot de passe utilisateur.
 * Valide selon les normes NIST SP 800-63B.
 */
final class PasswordChangeDTO
{
    #[Assert\NotBlank(message: 'Le mot de passe actuel est requis')]
    public string $currentPassword = '';

    #[Assert\NotBlank(message: 'Le nouveau mot de passe est requis')]
    #[Assert\Length(
        min: 12,
        minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères'
    )]
    #[Assert\Regex(
        pattern: '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/',
        message: 'Le mot de passe doit contenir majuscule, minuscule, chiffre et caractère spécial (@$!%*?&)'
    )]
    #[NotCommonPassword]
    public string $newPassword = '';

    #[Assert\NotBlank(message: 'La confirmation du mot de passe est requise')]
    #[Assert\EqualTo(propertyPath: 'newPassword', message: 'Les mots de passe ne correspondent pas')]
    public string $confirmPassword = '';
}
