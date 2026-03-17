<?php

namespace App\Repository\Wiki;

use App\Entity\User;
use App\Entity\Wiki\WikiFavorite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class WikiFavoriteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WikiFavorite::class);
    }

    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.user = :user')
            ->setParameter('user', $user)
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByUserAndTable(User $user, string $srdTable): array
    {
        return $this->createQueryBuilder('f')
            ->where('f.user = :user')
            ->andWhere('f.srdTable = :table')
            ->setParameter('user', $user)
            ->setParameter('table', $srdTable)
            ->getQuery()
            ->getResult();
    }

    public function isFavorited(User $user, string $srdTable, int $srdId): bool
    {
        $count = $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->where('f.user = :user')
            ->andWhere('f.srdTable = :table')
            ->andWhere('f.srdId = :id')
            ->setParameter('user', $user)
            ->setParameter('table', $srdTable)
            ->setParameter('id', $srdId)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    /**
     * Returns array of srd_id values for a given user and table.
     * @return int[]
     */
    public function getFavoritedIds(User $user, string $srdTable): array
    {
        $results = $this->createQueryBuilder('f')
            ->select('f.srdId')
            ->where('f.user = :user')
            ->andWhere('f.srdTable = :table')
            ->setParameter('user', $user)
            ->setParameter('table', $srdTable)
            ->getQuery()
            ->getResult();

        return array_column($results, 'srdId');
    }

    public function findOne(User $user, string $srdTable, int $srdId): ?WikiFavorite
    {
        return $this->createQueryBuilder('f')
            ->where('f.user = :user')
            ->andWhere('f.srdTable = :table')
            ->andWhere('f.srdId = :id')
            ->setParameter('user', $user)
            ->setParameter('table', $srdTable)
            ->setParameter('id', $srdId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
