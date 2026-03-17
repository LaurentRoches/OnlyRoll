<?php

namespace App\Repository\Srd;

use App\Entity\Srd\Spell;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SpellRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Spell::class);
    }

    public function findWithFilters(array $filters = []): array
    {
        $page = max(1, (int)($filters['page'] ?? 1));
        $limit = min(50, max(1, (int)($filters['limit'] ?? 20)));
        $offset = ($page - 1) * $limit;

        $qb = $this->createQueryBuilder('s')
            ->leftJoin('s.school', 'school')
            ->addSelect('school');

        if (!empty($filters['search'])) {
            $qb->andWhere('s.name LIKE :search')
               ->setParameter('search', '%' . $filters['search'] . '%');
        }

        if (isset($filters['level']) && $filters['level'] !== '') {
            $qb->andWhere('s.level = :level')
               ->setParameter('level', (int)$filters['level']);
        }

        if (!empty($filters['school'])) {
            $qb->andWhere('school.slug = :school')
               ->setParameter('school', $filters['school']);
        }

        if (!empty($filters['source'])) {
            $qb->leftJoin('s.sources', 'src_filter')
               ->andWhere('src_filter.code = :source')
               ->setParameter('source', $filters['source']);
        }

        if (!empty($filters['class'])) {
            $qb->innerJoin('s.spellClasses', 'sc')
               ->innerJoin('sc.srdClass', 'cls')
               ->andWhere('cls.name = :class')
               ->setParameter('class', $filters['class']);
        }

        if (!empty($filters['damage_type'])) {
            $qb->innerJoin('s.damageTypes', 'sdt')
               ->innerJoin('sdt.damageType', 'dt')
               ->andWhere('dt.slug = :damage_type')
               ->setParameter('damage_type', $filters['damage_type']);
        }

        if (isset($filters['components']) && is_array($filters['components'])) {
            foreach ($filters['components'] as $component) {
                $compAlias = 'comp_' . strtolower($component);
                $qb->innerJoin('s.components', $compAlias)
                   ->andWhere($compAlias . '.type = :comp_type_' . strtolower($component))
                   ->setParameter('comp_type_' . strtolower($component), strtoupper($component));
            }
        }

        $countQb = clone $qb;
        $total = (int)$countQb->select('COUNT(DISTINCT s.id)')->getQuery()->getSingleScalarResult();

        $spells = $qb->select('s', 'school')
                     ->orderBy('s.level', 'ASC')
                     ->addOrderBy('s.name', 'ASC')
                     ->setFirstResult($offset)
                     ->setMaxResults($limit)
                     ->getQuery()
                     ->getResult();

        return [
            'data' => $spells,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => (int)ceil($total / $limit),
        ];
    }

    public function findSimilarSpells(Spell $spell, int $limit = 5): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.school', 'school')
            ->where('s.school = :school')
            ->andWhere('s.id != :id')
            ->andWhere('ABS(s.level - :level) <= 2')
            ->setParameter('school', $spell->getSchool())
            ->setParameter('id', $spell->getId())
            ->setParameter('level', $spell->getLevel())
            ->orderBy('ABS(s.level - :level)', 'ASC')
            ->addOrderBy('s.name', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByName(string $name): ?Spell
    {
        return $this->createQueryBuilder('s')
            ->where('s.name = :name')
            ->setParameter('name', $name)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
