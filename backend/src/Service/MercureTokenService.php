<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use DateTimeImmutable;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;

/**
 * Service pour générer des tokens JWT Mercure pour l'authentification des abonnements.
 */
readonly class MercureTokenService
{
    private Configuration $jwtConfig;

    public function __construct(
        private string $mercureJwtSecret,
    ) {
        // Configuration JWT pour Mercure (utilise HMAC HS256)
        $this->jwtConfig = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText($this->mercureJwtSecret)
        );
    }

    /**
     * Génère un token JWT Mercure pour un utilisateur et une partie.
     *
     * @param User $user Utilisateur qui se connecte
     * @param int $gameId ID de la partie à laquelle s'abonner
     *
     * @return string Token JWT signé
     */
    public function generateTokenForGame(User $user, int $gameId): string
    {
        $now = new DateTimeImmutable();

        // Topics auxquels l'utilisateur peut s'abonner pour cette partie
        $subscribeTopics = [
            sprintf('game/%d/chat', $gameId),
            sprintf('game/%d/token', $gameId),
            sprintf('game/%d/map', $gameId),
            sprintf('game/%d/dice', $gameId),
            sprintf('game/%d/player', $gameId),
            sprintf('game/%d/presence', $gameId),
            sprintf('game/%d/system', $gameId),
        ];

        // Construction du token
        $token = $this->jwtConfig->builder()
            ->issuedAt($now)
            ->expiresAt($now->modify('+1 hour'))
            ->withClaim('mercure', [
                'subscribe' => $subscribeTopics,
            ])
            ->getToken($this->jwtConfig->signer(), $this->jwtConfig->signingKey());

        return $token->toString();
    }

    /**
     * Génère un token JWT Mercure pour les événements de présence de plusieurs parties.
     *
     * @param User $user Utilisateur qui se connecte
     * @param array<int> $gameIds IDs des parties à suivre
     *
     * @return string Token JWT signé
     */
    public function generateTokenForPresence(User $user, array $gameIds): string
    {
        $now = new DateTimeImmutable();

        // Topics de présence pour toutes les parties
        $subscribeTopics = array_map(
            fn (int $gameId) => sprintf('game/%d/presence', $gameId),
            $gameIds
        );

        // Construction du token
        $token = $this->jwtConfig->builder()
            ->issuedAt($now)
            ->expiresAt($now->modify('+1 hour'))
            ->withClaim('mercure', [
                'subscribe' => $subscribeTopics,
            ])
            ->getToken($this->jwtConfig->signer(), $this->jwtConfig->signingKey());

        return $token->toString();
    }
}
