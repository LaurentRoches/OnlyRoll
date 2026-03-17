<?php

namespace App\Repository\Srd;

use App\Entity\Srd\Monster;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MonsterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Monster::class);
    }

    public function findWithFilters(array $filters = []): array
    {
        $page = max(1, (int)($filters['page'] ?? 1));
        $limit = min(50, max(1, (int)($filters['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        $qb = $this->createQueryBuilder('m')
            ->leftJoin('m.type', 'type')
            ->leftJoin('m.size', 'size')
            ->addSelect('type', 'size');

        if (!empty($filters['search'])) {
            $qb->andWhere('m.name LIKE :search')
               ->setParameter('search', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['type'])) {
            $qb->andWhere('type.slug = :type')
               ->setParameter('type', $filters['type']);
        }

        if (!empty($filters['size'])) {
            $qb->andWhere('size.slug = :size')
               ->setParameter('size', $filters['size']);
        }

        if (!empty($filters['cr'])) {
            $qb->andWhere('m.cr = :cr')
               ->setParameter('cr', $filters['cr']);
        }

        if (!empty($filters['source'])) {
            $qb->leftJoin('m.sources', 'src_filter')
               ->andWhere('src_filter.code = :source')
               ->setParameter('source', $filters['source']);
        }

        $countQb = clone $qb;
        $total = (int)$countQb->select('COUNT(DISTINCT m.id)')->getQuery()->getSingleScalarResult();

        $monsters = $qb->orderBy('m.name', 'ASC')
                       ->setFirstResult($offset)
                       ->setMaxResults($limit)
                       ->getQuery()
                       ->getResult();

        return [
            'data' => $monsters,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => (int)ceil($total / $limit),
        ];
    }

    public function findByName(string $name): ?Monster
    {
        return $this->findOneBy(['name' => $name]);
    }
}
