<?php

namespace App\Repository\Srd;

use App\Entity\Srd\Feat;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FeatRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Feat::class);
    }

    public function findWithFilters(array $filters = []): array
    {
        $page = max(1, (int)($filters['page'] ?? 1));
        $limit = min(50, max(1, (int)($filters['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        $qb = $this->createQueryBuilder('f');

        if (!empty($filters['search'])) {
            $qb->andWhere('f.name LIKE :search')
               ->setParameter('search', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['source'])) {
            $qb->leftJoin('f.sources', 'src_filter')
               ->andWhere('src_filter.code = :source')
               ->setParameter('source', $filters['source']);
        }

        $countQb = clone $qb;
        $total = (int)$countQb->select('COUNT(DISTINCT f.id)')->getQuery()->getSingleScalarResult();

        $feats = $qb->orderBy('f.name', 'ASC')
                    ->setFirstResult($offset)
                    ->setMaxResults($limit)
                    ->getQuery()
                    ->getResult();

        return [
            'data' => $feats,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => (int)ceil($total / $limit),
        ];
    }

    public function findByName(string $name): ?Feat
    {
        return $this->findOneBy(['name' => $name]);
    }
}
