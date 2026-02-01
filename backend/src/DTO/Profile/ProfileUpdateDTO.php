<?php

declare(strict_types=1);

namespace App\DTO\Profile;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO pour la mise à jour du profil utilisateur.
 */
final class ProfileUpdateDTO
{
    #[Assert\Length(
        min: 3,
        max: 50,
        minMessage: 'Le pseudo doit faire au moins {{ limit }} caractères',
        maxMessage: 'Le pseudo ne peut pas dépasser {{ limit }} caractères'
    )]
    public ?string $pseudo = null;

    #[Assert\Timezone(message: 'Fuseau horaire invalide')]
    public ?string $timezone = null;

    #[Assert\Choice(choices: ['fr', 'en'], message: 'Langue non supportée')]
    public ?string $language = null;
}
