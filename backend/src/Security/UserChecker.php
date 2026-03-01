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
    public function __construct(
        private readonly string $adminEmail = 'admin@onlyroll.fr',
    ) {
    }

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
                json_encode([
                    'code' => 'account_deleted',
                    'message' => 'Ce compte a été supprimé.',
                    'adminEmail' => $this->adminEmail,
                ]),
            );
        }

        if (!$user->isActive()) {
            throw new CustomUserMessageAccountStatusException(
                'Ce compte est désactivé.',
            );
        }

        if ($user->isLocked()) {
            throw new CustomUserMessageAccountStatusException(
                'Ce compte est temporairement verrouillé. Veuillez réessayer plus tard.',
            );
        }

        if (!$user->isVerified()) {
            throw new CustomUserMessageAccountStatusException(
                json_encode([
                    'code' => 'account_not_verified',
                    'email' => $user->getEmail(),
                ]),
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
