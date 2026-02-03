<?php

declare(strict_types=1);

namespace App\Repository;

use App\DTO\Admin\UserFilterDTO;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(\sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Recherche un utilisateur par email.
     */
    public function findOneByEmail(string $email): ?User
    {
        return $this->createQueryBuilder('u')
            ->where('u.email = :email')
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Liste les utilisateurs avec filtres et pagination (admin).
     *
     * @return array{data: User[], total: int, page: int, limit: int, totalPages: int}
     */
    public function findWithFilters(UserFilterDTO $filter): array
    {
        $qb = $this->createQueryBuilder('u');

        $this->applyFilters($qb, $filter);

        $sortField = match ($filter->sortBy) {
            'id' => 'u.id',
            'email' => 'u.email',
            'pseudo' => 'u.pseudo',
            'lastLogin' => 'u.lastLogin',
            default => 'u.createdAt',
        };
        $qb->orderBy($sortField, $filter->sortDirection);

        $countQb = clone $qb;
        $countQb->select('COUNT(u.id)');
        $total = (int) $countQb->getQuery()->getSingleScalarResult();

        $offset = ($filter->page - 1) * $filter->limit;
        $qb->setFirstResult($offset)
            ->setMaxResults($filter->limit);

        $data = $qb->getQuery()->getResult();

        return [
            'data' => $data,
            'total' => $total,
            'page' => $filter->page,
            'limit' => $filter->limit,
            'totalPages' => (int) ceil($total / $filter->limit),
        ];
    }

    /**
     * Applique les filtres à la requête.
     */
    private function applyFilters(QueryBuilder $qb, UserFilterDTO $filter): void
    {
        if ($filter->search) {
            $qb->andWhere('u.email LIKE :search OR u.pseudo LIKE :search')
                ->setParameter('search', '%' . $filter->search . '%');
        }

        switch ($filter->status) {
            case 'active':
                $qb->andWhere('u.isActive = :isActive')
                    ->andWhere('u.deletedAt IS NULL')
                    ->setParameter('isActive', true);
                break;
            case 'inactive':
                $qb->andWhere('u.isActive = :isActive')
                    ->andWhere('u.deletedAt IS NULL')
                    ->setParameter('isActive', false);
                break;
            case 'deleted':
                $qb->andWhere('u.deletedAt IS NOT NULL');
                break;
            case 'locked':
                $qb->andWhere('u.lockedUntil IS NOT NULL')
                    ->andWhere('u.lockedUntil > :now')
                    ->setParameter('now', new \DateTimeImmutable());
                break;
            case 'all':
            default:
                break;
        }

        if ($filter->role) {
            $qb->andWhere('u.roles LIKE :role')
                ->setParameter('role', '%"' . $filter->role . '"%');
        }
    }

    /**
     * Récupère les statistiques des utilisateurs.
     *
     * @return array<string, int>
     */
    public function getStatistics(): array
    {
        $stats = [];

        $stats['total'] = (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $stats['active'] = (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.isActive = :active')
            ->andWhere('u.deletedAt IS NULL')
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleScalarResult();

        $stats['inactive'] = (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.isActive = :active')
            ->andWhere('u.deletedAt IS NULL')
            ->setParameter('active', false)
            ->getQuery()
            ->getSingleScalarResult();

        $stats['deleted'] = (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.deletedAt IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();

        $stats['locked'] = (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.lockedUntil IS NOT NULL')
            ->andWhere('u.lockedUntil > :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getSingleScalarResult();

        $stats['admins'] = (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%"ROLE_ADMIN"%')
            ->getQuery()
            ->getSingleScalarResult();

        $oneWeekAgo = new \DateTimeImmutable('-7 days');
        $stats['newThisWeek'] = (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.createdAt >= :date')
            ->setParameter('date', $oneWeekAgo)
            ->getQuery()
            ->getSingleScalarResult();

        $oneDayAgo = new \DateTimeImmutable('-24 hours');
        $stats['activeToday'] = (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.lastLogin >= :date')
            ->setParameter('date', $oneDayAgo)
            ->getQuery()
            ->getSingleScalarResult();

        return $stats;
    }
}
