<?php

declare(strict_types=1);

namespace App\Tests\Fuzzing;

use App\Entity\Game;
use App\Entity\GameMap;
use App\Entity\GamePlayer;
use App\Entity\User;
use App\Enum\PlayerRole;
use App\Enum\PlayerStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Fuzz tests sur la gestion des tokens de carte.
 *
 * Surface d'attaque :
 *  - POST /api/games/{gameId}/maps/{mapId}/tokens
 *  - GET  /api/games/{gameId}/maps/{mapId}/tokens (test IDOR)
 *
 * Risques spécifiques :
 *  - Coordonnées impossibles (NaN, Infinity, hors grille)
 *  - Taille de token invalide (0, négative, astronomique)
 *  - IDOR : accès à un token d'une autre partie
 */
class TokenFuzzTest extends WebTestCase
{
    private KernelBrowser $client;

    private EntityManagerInterface $entityManager;

    private User $fuzzGm;

    private User $otherUser;

    private int $gameId;

    private int $mapId;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->cleanDatabase();

        $this->fuzzGm = (new User())
            ->setEmail('fuzz-gm@test.com')
            ->setPseudo('fuzz_gm')
            ->setPassword('$2y$13$hashed_password')
            ->setRoles(['ROLE_USER'])
            ->setIsVerified(true);
        $this->entityManager->persist($this->fuzzGm);

        // Utilisateur étranger — non membre de la partie (pour test IDOR)
        $this->otherUser = (new User())
            ->setEmail('other-user@test.com')
            ->setPseudo('other_user')
            ->setPassword('$2y$13$hashed_password')
            ->setRoles(['ROLE_USER'])
            ->setIsVerified(true);
        $this->entityManager->persist($this->otherUser);

        $game = (new Game())
            ->setName('Fuzz Token Game')
            ->setGameMaster($this->fuzzGm)
            ->setIsPublic(false)
            ->setMaxPlayers(6);
        $this->entityManager->persist($game);

        $gmPlayer = (new GamePlayer())
            ->setGame($game)
            ->setUser($this->fuzzGm)
            ->setRole(PlayerRole::GAME_MASTER)
            ->setStatus(PlayerStatus::ACTIVE);
        $this->entityManager->persist($gmPlayer);

        $map = (new GameMap())
            ->setGame($game)
            ->setName('Fuzz Map')
            ->setWidth(20)
            ->setHeight(20)
            ->setIsActive(true);
        $this->entityManager->persist($map);

        $this->entityManager->flush();
        $this->gameId = $game->getId();
        $this->mapId = $map->getId();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }

    /**
     * Fuzz sur les coordonnées X/Y du token.
     * Cible : validation des bornes de la grille, NaN, Infinity.
     */
    public function testFuzzTokenCoordinates(): void
    {
        $this->client->loginUser($this->fuzzGm);
        $issues = [];

        $coordValues = [
            -1, -999, \PHP_INT_MIN,
            \PHP_INT_MAX, 999999,
            0.5, 1.99999,
            'NaN', 'Infinity', '-Infinity',
            null, '', 'dix', true, [],
        ];

        foreach ($coordValues as $coord) {
            $this->client->request(
                'POST',
                "/api/games/{$this->gameId}/maps/{$this->mapId}/tokens",
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode([
                    'x' => $coord,
                    'y' => $coord,
                    'name' => 'Fuzz Token',
                    'type' => 'character',
                ], \JSON_INVALID_UTF8_SUBSTITUTE),
            );

            $statusCode = $this->client->getResponse()->getStatusCode();

            if (!\in_array($statusCode, FuzzPayloadProvider::acceptableHttpCodes())) {
                $issues[] = \sprintf('x=y=%s → HTTP %d', json_encode($coord), $statusCode);
            }
        }

        $this->assertEmpty($issues, "Fuzzing token coordinates :\n" . implode("\n", $issues));
    }

    /**
     * Fuzz IDOR — tentative d'accès aux tokens d'une partie par un utilisateur non membre.
     * Cible : vérification des autorisations par partie.
     *
     * Comportement attendu : 401 ou 403, jamais 200.
     */
    public function testFuzzTokenIdor(): void
    {
        $this->client->loginUser($this->otherUser);

        $this->client->request(
            'GET',
            "/api/games/{$this->gameId}/maps/{$this->mapId}/tokens",
        );

        $statusCode = $this->client->getResponse()->getStatusCode();

        $this->assertContains(
            $statusCode,
            [401, 403],
            "IDOR : un utilisateur étranger a pu accéder aux tokens de la partie (HTTP $statusCode)",
        );
    }

    private function cleanDatabase(): void
    {
        $conn = $this->entityManager->getConnection();
        $conn->executeStatement('SET FOREIGN_KEY_CHECKS = 0');

        foreach (['game_message', 'game_token', 'game_player', 'game_map', 'game', 'user'] as $table) {
            $conn->executeStatement("TRUNCATE TABLE $table");
        }

        $conn->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
    }
}
