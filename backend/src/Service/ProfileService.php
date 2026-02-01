<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\Profile\PasswordChangeDTO;
use App\DTO\Profile\ProfileUpdateDTO;
use App\Entity\User;
use App\Enum\AuditAction;
use App\Exception\Profile\InvalidPasswordException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Service de gestion du profil utilisateur.
 * Inclut le changement de mot de passe avec validation CommonPasswords (NIST).
 */
final class ProfileService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly AuditLogService $auditLogService,
        private readonly LoggerInterface $logger,
        private readonly RequestStack $requestStack,
    ) {
    }

    /**
     * Change le mot de passe de l'utilisateur.
     * Le nouveau mot de passe est validé via DTO (NotCommonPassword, etc.).
     *
     * @throws InvalidPasswordException Si le mot de passe actuel est incorrect
     */
    public function changePassword(User $user, PasswordChangeDTO $dto): void
    {
        if (!$this->passwordHasher->isPasswordValid($user, $dto->currentPassword)) {
            $this->auditLogService->logPasswordChangeFailed(
                $user,
                'invalid_current_password',
                $this->requestStack->getCurrentRequest(),
            );

            throw new InvalidPasswordException('Mot de passe actuel incorrect');
        }

        if ($this->passwordHasher->isPasswordValid($user, $dto->newPassword)) {
            $this->auditLogService->logPasswordChangeFailed(
                $user,
                'same_as_current',
                $this->requestStack->getCurrentRequest(),
            );

            throw new InvalidPasswordException('Le nouveau mot de passe doit être différent de l\'ancien');
        }

        $hashedPassword = $this->passwordHasher->hashPassword($user, $dto->newPassword);
        $user->setPassword($hashedPassword);

        $this->entityManager->flush();

        $this->auditLogService->logPasswordChange(
            $user,
            $this->requestStack->getCurrentRequest(),
        );

        $this->logger->info('Password changed successfully', [
            'user_id' => $user->getId(),
        ]);
    }

    /**
     * Met à jour le profil utilisateur.
     */
    public function updateProfile(User $user, ProfileUpdateDTO $dto): User
    {
        $changes = [];

        if (null !== $dto->pseudo && $dto->pseudo !== $user->getPseudo()) {
            $changes['pseudo'] = ['old' => $user->getPseudo(), 'new' => $dto->pseudo];
            $user->setPseudo($dto->pseudo);
        }

        if (null !== $dto->timezone && $dto->timezone !== $user->getTimezone()) {
            $changes['timezone'] = ['old' => $user->getTimezone(), 'new' => $dto->timezone];
            $user->setTimezone($dto->timezone);
        }

        if (null !== $dto->language && $dto->language !== $user->getLanguage()) {
            $changes['language'] = ['old' => $user->getLanguage(), 'new' => $dto->language];
            $user->setLanguage($dto->language);
        }

        if (!empty($changes)) {
            $this->entityManager->flush();

            $this->auditLogService->log(
                AuditAction::PROFILE_UPDATED,
                $user,
                $user,
                'user',
                $user->getId(),
                ['changes' => $changes],
                $this->requestStack->getCurrentRequest(),
            );

            $this->logger->info('Profile updated', [
                'user_id' => $user->getId(),
                'changes' => array_keys($changes),
            ]);
        }

        return $user;
    }

    /**
     * Met à jour l'avatar de l'utilisateur.
     */
    public function updateAvatar(User $user, string $avatarPath): User
    {
        $oldAvatar = $user->getAvatar();
        $user->setAvatar($avatarPath);

        $this->entityManager->flush();

        $this->auditLogService->log(
            AuditAction::AVATAR_CHANGED,
            $user,
            $user,
            'user',
            $user->getId(),
            ['old_avatar' => $oldAvatar],
            $this->requestStack->getCurrentRequest(),
        );

        $this->logger->info('Avatar updated', [
            'user_id' => $user->getId(),
        ]);

        return $user;
    }

    /**
     * Supprime l'avatar de l'utilisateur.
     */
    public function removeAvatar(User $user): User
    {
        $oldAvatar = $user->getAvatar();

        if (null === $oldAvatar) {
            return $user;
        }

        $user->setAvatar(null);

        $this->entityManager->flush();

        $this->auditLogService->log(
            AuditAction::AVATAR_CHANGED,
            $user,
            $user,
            'user',
            $user->getId(),
            ['old_avatar' => $oldAvatar, 'removed' => true],
            $this->requestStack->getCurrentRequest(),
        );

        $this->logger->info('Avatar removed', [
            'user_id' => $user->getId(),
        ]);

        return $user;
    }
}
