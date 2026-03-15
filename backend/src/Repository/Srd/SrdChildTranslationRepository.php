<?php

declare(strict_types=1);

namespace App\Repository\Srd;

use App\Entity\Srd\SrdChildTranslation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SrdChildTranslationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SrdChildTranslation::class);
    }

    public function findTranslation(string $childTable, int $childId, string $locale): ?SrdChildTranslation
    {
        return $this->findOneBy([
            'childTable' => $childTable,
            'childId' => $childId,
            'locale' => $locale,
        ]);
    }

    /**
     * @return array<int, SrdChildTranslation> Indexé par childId
     */
    public function findTranslationsForIds(string $childTable, array $ids, string $locale): array
    {
        if (empty($ids)) {
            return [];
        }

        $results = $this->createQueryBuilder('t')
            ->where('t.childTable = :table')
            ->andWhere('t.childId IN (:ids)')
            ->andWhere('t.locale = :locale')
            ->setParameter('table', $childTable)
            ->setParameter('ids', $ids)
            ->setParameter('locale', $locale)
            ->getQuery()
            ->getResult();

        $indexed = [];
        foreach ($results as $translation) {
            $indexed[$translation->getChildId()] = $translation;
        }

        return $indexed;
    }
}
