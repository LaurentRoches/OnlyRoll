<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\PasswordResetToken;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PasswordResetToken>
 */
class PasswordResetTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PasswordResetToken::class);
    }

    /**
     * Trouve un token valide par son hash (non expiré et non utilisé).
     */
    public function findValidTokenByHash(string $tokenHash): ?PasswordResetToken
    {
        return $this->createQueryBuilder('t')
            ->where('t.tokenHash = :hash')
            ->andWhere('t.expiresAt > :now')
            ->andWhere('t.usedAt IS NULL')
            ->setParameter('hash', $tokenHash)
            ->setParameter('now', new DateTimeImmutable())
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
