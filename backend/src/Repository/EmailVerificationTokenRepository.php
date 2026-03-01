<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\EmailVerificationToken;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EmailVerificationToken>
 */
class EmailVerificationTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmailVerificationToken::class);
    }

    /**
     * Trouve un token valide (non expiré et non utilisé).
     */
    public function findValidToken(string $token): ?EmailVerificationToken
    {
        return $this->createQueryBuilder('t')
            ->where('t.token = :token')
            ->andWhere('t.expiresAt > :now')
            ->andWhere('t.usedAt IS NULL')
            ->setParameter('token', $token)
            ->setParameter('now', new DateTimeImmutable())
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve le dernier token créé pour un utilisateur.
     */
    public function findLastTokenForUser(User $user): ?EmailVerificationToken
    {
        return $this->createQueryBuilder('t')
            ->where('t.user = :user')
            ->setParameter('user', $user)
            ->orderBy('t.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Invalide tous les tokens non utilisés d'un utilisateur.
     */
    public function invalidatePreviousTokens(User $user): void
    {
        $this->createQueryBuilder('t')
            ->update()
            ->set('t.usedAt', ':now')
            ->where('t.user = :user')
            ->andWhere('t.usedAt IS NULL')
            ->setParameter('now', new DateTimeImmutable())
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
    }
}
