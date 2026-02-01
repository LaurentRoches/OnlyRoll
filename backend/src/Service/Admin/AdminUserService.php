<?php

declare(strict_types=1);

namespace App\Service\Admin;

use App\DTO\Admin\UserFilterDTO;
use App\DTO\Admin\UserUpdateDTO;
use App\Entity\User;
use App\Enum\AuditAction;
use App\Repository\UserRepository;
use App\Service\AuditLogService;
use DateInterval;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Service d'administration des utilisateurs.
 * CRUD avec soft delete et gestion du verrouillage de compte.
 */
final class AdminUserService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserRepository $userRepository,
        private readonly AuditLogService $auditLogService,
        private readonly LoggerInterface $logger,
        private readonly RequestStack $requestStack,
    ) {
    }

    /**
     * Liste les utilisateurs avec filtres et pagination.
     *
     * @return array{data: User[], total: int, page: int, limit: int, totalPages: int}
     */
    public function listUsers(UserFilterDTO $filter): array
    {
        return $this->userRepository->findWithFilters($filter);
    }

    /**
     * Récupère un utilisateur par son ID.
     */
    public function getUser(int $id): ?User
    {
        return $this->userRepository->find($id);
    }

    /**
     * Met à jour un utilisateur.
     *
     * @param array<string, mixed> $oldValues Valeurs avant modification pour l'audit
     */
    public function updateUser(User $user, UserUpdateDTO $dto, User $admin): User
    {
        $changes = [];

        if (null !== $dto->pseudo && $dto->pseudo !== $user->getPseudo()) {
            $changes['pseudo'] = ['old' => $user->getPseudo(), 'new' => $dto->pseudo];
            $user->setPseudo($dto->pseudo);
        }

        if (null !== $dto->email && $dto->email !== $user->getEmail()) {
            $changes['email'] = ['old' => $user->getEmail(), 'new' => $dto->email];
            $user->setEmail($dto->email);
        }

        if (null !== $dto->isActive && $dto->isActive !== $user->isActive()) {
            $changes['isActive'] = ['old' => $user->isActive(), 'new' => $dto->isActive];
            $user->setIsActive($dto->isActive);
        }

        if (null !== $dto->isVerified && $dto->isVerified !== $user->isVerified()) {
            $changes['isVerified'] = ['old' => $user->isVerified(), 'new' => $dto->isVerified];
            $user->setIsVerified($dto->isVerified);
        }

        if (null !== $dto->roles) {
            $oldRoles = $user->getRoles();
            $newRoles = array_values(array_unique(array_merge(['ROLE_USER'], $dto->roles)));

            if ($oldRoles !== $newRoles) {
                $changes['roles'] = ['old' => $oldRoles, 'new' => $newRoles];
                $user->setRoles($newRoles);

                $this->auditLogService->log(
                    AuditAction::USER_ROLE_CHANGED,
                    $admin,
                    $user,
                    'user',
                    $user->getId(),
                    ['roles_changed' => $changes['roles']],
                    $this->requestStack->getCurrentRequest(),
                );
            }
        }

        if (!empty($changes)) {
            $this->entityManager->flush();

            $this->auditLogService->log(
                AuditAction::USER_UPDATED,
                $admin,
                $user,
                'user',
                $user->getId(),
                ['changes' => $changes],
                $this->requestStack->getCurrentRequest(),
            );

            $this->logger->info('User updated by admin', [
                'admin_id' => $admin->getId(),
                'user_id' => $user->getId(),
                'changes' => array_keys($changes),
            ]);
        }

        return $user;
    }

    /**
     * Soft delete un utilisateur.
     */
    public function softDelete(User $user, User $admin): void
    {
        if ($user->isDeleted()) {
            return;
        }

        $user->setDeletedAt(new DateTimeImmutable());
        $user->setIsActive(false);

        $this->entityManager->flush();

        $this->auditLogService->log(
            AuditAction::USER_SOFT_DELETED,
            $admin,
            $user,
            'user',
            $user->getId(),
            [],
            $this->requestStack->getCurrentRequest(),
        );

        $this->logger->info('User soft deleted', [
            'admin_id' => $admin->getId(),
            'user_id' => $user->getId(),
        ]);
    }

    /**
     * Restaure un utilisateur supprimé.
     */
    public function restore(User $user, User $admin): void
    {
        if (!$user->isDeleted()) {
            return;
        }

        $user->setDeletedAt(null);
        $user->setIsActive(true);

        $this->entityManager->flush();

        $this->auditLogService->log(
            AuditAction::USER_RESTORED,
            $admin,
            $user,
            'user',
            $user->getId(),
            [],
            $this->requestStack->getCurrentRequest(),
        );

        $this->logger->info('User restored', [
            'admin_id' => $admin->getId(),
            'user_id' => $user->getId(),
        ]);
    }

    /**
     * Verrouille un compte utilisateur.
     *
     * @param int $minutes Durée du verrouillage en minutes (0 = indéfini, max 1 an)
     */
    public function lockAccount(User $user, User $admin, int $minutes = 0): void
    {
        if ($minutes > 0) {
            $lockUntil = (new DateTimeImmutable())->add(new DateInterval("PT{$minutes}M"));
        } else {
            // Verrouillage pour 1 an si 0 = indéfini
            $lockUntil = (new DateTimeImmutable())->add(new DateInterval('P1Y'));
        }

        $user->setLockedUntil($lockUntil);

        $this->entityManager->flush();

        $this->auditLogService->logAccountLocked(
            $user,
            $admin,
            $this->requestStack->getCurrentRequest(),
        );

        $this->logger->info('User account locked', [
            'admin_id' => $admin->getId(),
            'user_id' => $user->getId(),
            'locked_until' => $lockUntil->format('c'),
        ]);
    }

    /**
     * Déverrouille un compte utilisateur.
     */
    public function unlockAccount(User $user, User $admin): void
    {
        if (!$user->isLocked()) {
            return;
        }

        $user->setLockedUntil(null);
        $user->resetFailedLoginAttempts();

        $this->entityManager->flush();

        $this->auditLogService->logAccountUnlocked(
            $user,
            $admin,
            $this->requestStack->getCurrentRequest(),
        );

        $this->logger->info('User account unlocked', [
            'admin_id' => $admin->getId(),
            'user_id' => $user->getId(),
        ]);
    }

    /**
     * Active un utilisateur.
     */
    public function activateUser(User $user, User $admin): void
    {
        if ($user->isActive()) {
            return;
        }

        $user->setIsActive(true);

        $this->entityManager->flush();

        $this->auditLogService->log(
            AuditAction::USER_ACTIVATED,
            $admin,
            $user,
            'user',
            $user->getId(),
            [],
            $this->requestStack->getCurrentRequest(),
        );

        $this->logger->info('User activated', [
            'admin_id' => $admin->getId(),
            'user_id' => $user->getId(),
        ]);
    }

    /**
     * Désactive un utilisateur.
     */
    public function deactivateUser(User $user, User $admin): void
    {
        if (!$user->isActive()) {
            return;
        }

        $user->setIsActive(false);

        $this->entityManager->flush();

        $this->auditLogService->log(
            AuditAction::USER_DEACTIVATED,
            $admin,
            $user,
            'user',
            $user->getId(),
            [],
            $this->requestStack->getCurrentRequest(),
        );

        $this->logger->info('User deactivated', [
            'admin_id' => $admin->getId(),
            'user_id' => $user->getId(),
        ]);
    }

    /**
     * Récupère les statistiques globales des utilisateurs.
     *
     * @return array<string, int>
     */
    public function getStatistics(): array
    {
        return $this->userRepository->getStatistics();
    }
}
