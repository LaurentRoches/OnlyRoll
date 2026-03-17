<?php

declare(strict_types=1);

namespace App\Repository\Srd;

use App\Entity\Srd\SrdTranslation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SrdTranslationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SrdTranslation::class);
    }

    public function findTranslation(string $srdTable, int $srdId, string $locale): ?SrdTranslation
    {
        return $this->findOneBy([
            'srdTable' => $srdTable,
            'srdId' => $srdId,
            'locale' => $locale,
        ]);
    }

    /**
     * Charge en bulk les traductions pour une liste d'IDs (évite N+1).
     *
     * @return array<int, SrdTranslation> Indexé par srdId
     */
    public function findTranslationsForIds(string $srdTable, array $ids, string $locale): array
    {
        if (empty($ids)) {
            return [];
        }

        $results = $this->createQueryBuilder('t')
            ->where('t.srdTable = :table')
            ->andWhere('t.srdId IN (:ids)')
            ->andWhere('t.locale = :locale')
            ->setParameter('table', $srdTable)
            ->setParameter('ids', $ids)
            ->setParameter('locale', $locale)
            ->getQuery()
            ->getResult();

        $indexed = [];
        foreach ($results as $translation) {
            $indexed[$translation->getSrdId()] = $translation;
        }

        return $indexed;
    }

    /**
     * @return array<int, SrdTranslation> Indexé par srdId
     */
    public function findAllForTable(string $srdTable, string $locale): array
    {
        $results = $this->createQueryBuilder('t')
            ->where('t.srdTable = :table')
            ->andWhere('t.locale = :locale')
            ->setParameter('table', $srdTable)
            ->setParameter('locale', $locale)
            ->getQuery()
            ->getResult();

        $indexed = [];
        foreach ($results as $translation) {
            $indexed[$translation->getSrdId()] = $translation;
        }

        return $indexed;
    }
}
