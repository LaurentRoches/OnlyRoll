<?php

declare(strict_types=1);

namespace App\DTO\Admin;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO pour la mise à jour d'un utilisateur par un admin.
 */
final class UserUpdateDTO
{
    #[Assert\Length(min: 3, max: 50, minMessage: 'Le pseudo doit faire au moins {{ limit }} caractères')]
    public ?string $pseudo = null;

    #[Assert\Email(message: 'Format d\'email invalide')]
    #[Assert\Length(max: 180)]
    public ?string $email = null;

    public ?bool $isActive = null;

    public ?bool $isVerified = null;

    /**
     * @var array<string>|null
     */
    #[Assert\All([
        new Assert\Choice(choices: ['ROLE_USER', 'ROLE_ADMIN'], message: 'Rôle invalide'),
    ])]
    public ?array $roles = null;
}
