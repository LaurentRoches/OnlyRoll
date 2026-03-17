<?php

namespace App\Repository\Wiki;

use App\Entity\Wiki\WikiArticle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class WikiArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WikiArticle::class);
    }

    public function findBySrdEntity(string $srdTable, int $srdId): ?WikiArticle
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.translations', 't')
            ->addSelect('t')
            ->where('a.srdTable = :table')
            ->andWhere('a.srdId = :id')
            ->setParameter('table', $srdTable)
            ->setParameter('id', $srdId)
            ->getQuery()
            ->getOneOrNullResult();
    }


}
