<?php

declare(strict_types=1);

namespace App\Repository;

use App\DTO\Admin\AuditLogFilterDTO;
use App\Entity\AuditLog;
use App\Enum\AuditAction;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AuditLog>
 */
class AuditLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuditLog::class);
    }

    /**
     * @return AuditLog[]
     */
    public function findByUser(int $userId, int $limit = 50): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.performer = :userId OR a.targetUser = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return AuditLog[]
     */
    public function findByAction(AuditAction $action, int $limit = 50): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.action = :action')
            ->setParameter('action', $action)
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return AuditLog[]
     */
    public function findRecent(int $limit = 100): array
    {
        return $this->createQueryBuilder('a')
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Liste les logs d'audit avec filtres et pagination.
     *
     * @return array{data: AuditLog[], total: int, page: int, limit: int, totalPages: int}
     */
    public function findWithFilters(AuditLogFilterDTO $filter): array
    {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.performer', 'p')
            ->leftJoin('a.targetUser', 't');

        $this->applyFilters($qb, $filter);

        $qb->orderBy('a.createdAt', 'DESC');

        $countQb = clone $qb;
        $countQb->select('COUNT(a.id)');
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
    private function applyFilters(QueryBuilder $qb, AuditLogFilterDTO $filter): void
    {
        if ($filter->userId) {
            $qb->andWhere('p.id = :userId')
                ->setParameter('userId', $filter->userId);
        }

        if ($filter->targetUserId) {
            $qb->andWhere('t.id = :targetUserId')
                ->setParameter('targetUserId', $filter->targetUserId);
        }

        if ($filter->action) {
            $qb->andWhere('a.action = :action')
                ->setParameter('action', $filter->action);
        }

        if ($filter->entityType) {
            $qb->andWhere('a.entityType = :entityType')
                ->setParameter('entityType', $filter->entityType);
        }

        if ($filter->dateFrom) {
            $qb->andWhere('a.createdAt >= :dateFrom')
                ->setParameter('dateFrom', $filter->dateFrom);
        }

        if ($filter->dateTo) {
            $qb->andWhere('a.createdAt <= :dateTo')
                ->setParameter('dateTo', $filter->dateTo);
        }

        if ($filter->severity) {
            $actionsWithSeverity = array_filter(
                AuditAction::cases(),
                fn (AuditAction $action) => $action->getSeverity() === $filter->severity,
            );

            if (!empty($actionsWithSeverity)) {
                $qb->andWhere('a.action IN (:actions)')
                    ->setParameter('actions', $actionsWithSeverity);
            }
        }
    }

    /**
     * Récupère les statistiques des logs d'audit.
     *
     * @return array<string, mixed>
     */
    public function getStatistics(): array
    {
        $stats = [];

        $stats['total'] = (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $today = new DateTimeImmutable('today');
        $stats['today'] = (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.createdAt >= :today')
            ->setParameter('today', $today)
            ->getQuery()
            ->getSingleScalarResult();

        $oneDayAgo = new DateTimeImmutable('-24 hours');

        $warningActions = array_filter(
            AuditAction::cases(),
            fn (AuditAction $action) => $action->getSeverity() === 'warning',
        );
        $stats['warningsToday'] = (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.action IN (:actions)')
            ->andWhere('a.createdAt >= :date')
            ->setParameter('actions', $warningActions)
            ->setParameter('date', $oneDayAgo)
            ->getQuery()
            ->getSingleScalarResult();

        $highActions = array_filter(
            AuditAction::cases(),
            fn (AuditAction $action) => $action->getSeverity() === 'high',
        );
        $stats['highSeverityToday'] = (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.action IN (:actions)')
            ->andWhere('a.createdAt >= :date')
            ->setParameter('actions', $highActions)
            ->setParameter('date', $oneDayAgo)
            ->getQuery()
            ->getSingleScalarResult();

        $stats['failedLoginsToday'] = (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.action = :action')
            ->andWhere('a.createdAt >= :date')
            ->setParameter('action', AuditAction::LOGIN_FAILED)
            ->setParameter('date', $oneDayAgo)
            ->getQuery()
            ->getSingleScalarResult();

        return $stats;
    }
}
