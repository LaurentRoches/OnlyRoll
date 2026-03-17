<?php

namespace App\Repository\Srd;

use App\Entity\Srd\SrdClass;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ClassRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SrdClass::class);
    }

    public function findWithFilters(array $filters = []): array
    {
        $page = max(1, (int)($filters['page'] ?? 1));
        $limit = min(50, max(1, (int)($filters['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        $qb = $this->createQueryBuilder('c');

        if (!empty($filters['search'])) {
            $qb->andWhere('c.name LIKE :search')
               ->setParameter('search', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['source'])) {
            $qb->leftJoin('c.sources', 'src_filter')
               ->andWhere('src_filter.code = :source')
               ->setParameter('source', $filters['source']);
        }

        $countQb = clone $qb;
        $total = (int)$countQb->select('COUNT(DISTINCT c.id)')->getQuery()->getSingleScalarResult();

        $classes = $qb->orderBy('c.name', 'ASC')
                      ->setFirstResult($offset)
                      ->setMaxResults($limit)
                      ->getQuery()
                      ->getResult();

        return [
            'data' => $classes,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => (int)ceil($total / $limit),
        ];
    }

    public function findByName(string $name): ?SrdClass
    {
        return $this->findOneBy(['name' => $name]);
    }
}
