<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Vérifie l'état du compte utilisateur avant et après l'authentification.
 */
final class UserChecker implements UserCheckerInterface
{
    /**
     * Vérifie l'utilisateur avant l'authentification (vérification du mot de passe).
     */
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if ($user->isDeleted()) {
            throw new CustomUserMessageAccountStatusException(
                'Ce compte a été supprimé.'
            );
        }

        if (!$user->isActive()) {
            throw new CustomUserMessageAccountStatusException(
                'Ce compte est désactivé.'
            );
        }

        if ($user->isLocked()) {
            throw new CustomUserMessageAccountStatusException(
                'Ce compte est temporairement verrouillé. Veuillez réessayer plus tard.'
            );
        }
    }

    /**
     * Vérifie l'utilisateur après l'authentification réussie.
     */
    public function checkPostAuth(UserInterface $user): void
    {
        // Pas de vérifications supplémentaires après authentification pour l'instant
    }
}
