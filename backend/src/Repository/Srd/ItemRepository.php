<?php

namespace App\Repository\Srd;

use App\Entity\Srd\Item;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Item::class);
    }

    public function findWithFilters(array $filters = []): array
    {
        $page = max(1, (int)($filters['page'] ?? 1));
        $limit = min(50, max(1, (int)($filters['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        $qb = $this->createQueryBuilder('i')
            ->leftJoin('i.category', 'category')
            ->leftJoin('i.rarity', 'rarity')
            ->addSelect('category', 'rarity');

        if (!empty($filters['search'])) {
            $qb->andWhere('i.name LIKE :search')
               ->setParameter('search', '%' . $filters['search'] . '%');
        }

        if (!empty($filters['category'])) {
            $qb->andWhere('category.slug = :category')
               ->setParameter('category', $filters['category']);
        }

        if (!empty($filters['rarity'])) {
            $qb->andWhere('rarity.slug = :rarity')
               ->setParameter('rarity', $filters['rarity']);
        }

        if (!empty($filters['source'])) {
            $qb->leftJoin('i.sources', 'src_filter')
               ->andWhere('src_filter.code = :source')
               ->setParameter('source', $filters['source']);
        }

        $countQb = clone $qb;
        $total = (int)$countQb->select('COUNT(DISTINCT i.id)')->getQuery()->getSingleScalarResult();

        $items = $qb->orderBy('i.name', 'ASC')
                    ->setFirstResult($offset)
                    ->setMaxResults($limit)
                    ->getQuery()
                    ->getResult();

        return [
            'data' => $items,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => (int)ceil($total / $limit),
        ];
    }

    public function findByName(string $name): ?Item
    {
        return $this->findOneBy(['name' => $name]);
    }
}
