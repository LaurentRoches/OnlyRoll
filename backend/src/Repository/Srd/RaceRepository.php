<?php

namespace App\Repository\Srd;

use App\Entity\Srd\Race;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class RaceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Race::class);
    }

    public function findWithFilters(array $filters = []): array
    {
        $page = max(1, (int)($filters['page'] ?? 1));
        $limit = min(50, max(1, (int)($filters['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.size', 'size')
            ->addSelect('size');

        if (!empty($filters['search'])) {
            $qb->andWhere('r.name LIKE :search')
               ->setParameter('search', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['size'])) {
            $qb->andWhere('size.slug = :size')
               ->setParameter('size', $filters['size']);
        }

        if (!empty($filters['source'])) {
            $qb->leftJoin('r.sources', 'src_filter')
               ->andWhere('src_filter.code = :source')
               ->setParameter('source', $filters['source']);
        }

        if (!empty($filters['speed_type'])) {
            $qb->innerJoin('r.speeds', 'spd_filter')
               ->andWhere('spd_filter.speedType = :speed_type')
               ->setParameter('speed_type', $filters['speed_type']);
        }

        if (!empty($filters['vision'])) {
            $qb->innerJoin('r.traits', 'trait_filter')
               ->andWhere('trait_filter.name LIKE :vision')
               ->setParameter('vision', '%' . $filters['vision'] . '%');
        }

        $countQb = clone $qb;
        $total = (int)$countQb->select('COUNT(DISTINCT r.id)')->getQuery()->getSingleScalarResult();

        $races = $qb->orderBy('r.name', 'ASC')
                    ->setFirstResult($offset)
                    ->setMaxResults($limit)
                    ->getQuery()
                    ->getResult();

        return [
            'data' => $races,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => (int)ceil($total / $limit),
        ];
    }

    public function findByName(string $name): ?Race
    {
        return $this->findOneBy(['name' => $name]);
    }
}
