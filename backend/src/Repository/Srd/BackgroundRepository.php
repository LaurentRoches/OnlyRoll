<?php

namespace App\Repository\Srd;

use App\Entity\Srd\Background;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class BackgroundRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Background::class);
    }

    public function findWithFilters(array $filters = []): array
    {
        $page = max(1, (int)($filters['page'] ?? 1));
        $limit = min(50, max(1, (int)($filters['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        $qb = $this->createQueryBuilder('b');

        if (!empty($filters['search'])) {
            $qb->andWhere('b.name LIKE :search')
               ->setParameter('search', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['source'])) {
            $qb->leftJoin('b.sources', 'src_filter')
               ->andWhere('src_filter.code = :source')
               ->setParameter('source', $filters['source']);
        }

        $countQb = clone $qb;
        $total = (int)$countQb->select('COUNT(DISTINCT b.id)')->getQuery()->getSingleScalarResult();

        $backgrounds = $qb->orderBy('b.name', 'ASC')
                          ->setFirstResult($offset)
                          ->setMaxResults($limit)
                          ->getQuery()
                          ->getResult();

        return [
            'data' => $backgrounds,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => (int)ceil($total / $limit),
        ];
    }

    public function findByName(string $name): ?Background
    {
        return $this->findOneBy(['name' => $name]);
    }
}
