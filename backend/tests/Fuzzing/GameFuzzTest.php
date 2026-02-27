<?php

declare(strict_types=1);

namespace App\Tests\Fuzzing;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Fuzz tests sur les endpoints de gestion des parties.
 *
 * Surface d'attaque : POST /api/games, POST /api/games/join
 *
 * Cibles prioritaires :
 *  - maxPlayers : valeurs numériques limites
 *  - name : overflow, injection
 *  - inviteCode : brute-force et payloads spéciaux
 */
class GameFuzzTest extends WebTestCase
{
    private KernelBrowser $client;

    private EntityManagerInterface $entityManager;

    private User $fuzzUser;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->cleanDatabase();

        $this->fuzzUser = new User();
        $this->fuzzUser->setEmail('fuzz@test.com')
            ->setPseudo('fuzz_user')
            ->setPassword('$2y$13$hashed_password')
            ->setRoles(['ROLE_USER'])
            ->setIsVerified(true);

        $this->entityManager->persist($this->fuzzUser);
        $this->entityManager->flush();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }

    /**
     * Fuzz sur maxPlayers.
     * Cible : validation des bornes numériques métier (1-20 joueurs max).
     */
    public function testFuzzMaxPlayers(): void
    {
        $this->client->loginUser($this->fuzzUser);
        $issues = [];

        $extremeValues = [
            0, -1, -999, PHP_INT_MAX, PHP_INT_MIN,
            1.5, 'ten', null, '', true, false,
            '9999999999999999999',
            0x7FFFFFFF,
        ];

        foreach ($extremeValues as $value) {
            $this->client->request(
                'POST',
                '/api/games',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode([
                    'name'       => 'Fuzz Test Game',
                    'maxPlayers' => $value,
                    'isPublic'   => true,
                ], JSON_INVALID_UTF8_SUBSTITUTE)
            );

            $statusCode = $this->client->getResponse()->getStatusCode();

            if (!in_array($statusCode, FuzzPayloadProvider::acceptableHttpCodes())) {
                $issues[] = sprintf("maxPlayers=%s → HTTP %d", json_encode($value), $statusCode);
            }
        }

        $this->assertEmpty($issues, "Fuzzing maxPlayers :\n" . implode("\n", $issues));
    }

    /**
     * Fuzz sur le champ name de la partie.
     * Cible : SQLi, XSS, overflow dans le nom de partie.
     */
    public function testFuzzGameName(): void
    {
        $this->client->loginUser($this->fuzzUser);
        $issues = [];

        foreach (FuzzPayloadProvider::allTextPayloads() as $payload) {
            $this->client->request(
                'POST',
                '/api/games',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode([
                    'name'       => $payload,
                    'maxPlayers' => 5,
                    'isPublic'   => true,
                ], JSON_INVALID_UTF8_SUBSTITUTE)
            );

            $statusCode = $this->client->getResponse()->getStatusCode();

            if (!in_array($statusCode, FuzzPayloadProvider::acceptableHttpCodes())) {
                $issues[] = sprintf(
                    "name=%s → HTTP %d | %s",
                    json_encode($payload),
                    $statusCode,
                    substr($this->client->getResponse()->getContent(), 0, 150)
                );
            }
        }

        $this->assertEmpty($issues, "Fuzzing game name :\n" . implode("\n", $issues));
    }

    /**
     * Fuzz sur l'inviteCode pour rejoindre une partie.
     * Cible : brute-force implicite, injection via le code.
     *
     * NOTE : 404 est acceptable ici (code inexistant), 500 ne l'est pas.
     */
    public function testFuzzJoinByInviteCode(): void
    {
        $this->client->loginUser($this->fuzzUser);
        $issues = [];

        $codes = array_merge(
            FuzzPayloadProvider::sqlInjectionPayloads(),
            FuzzPayloadProvider::pathTraversalPayloads(),
            ['', null, str_repeat('A', 1000), '00000000', '../../../../']
        );

        foreach ($codes as $code) {
            $this->client->request(
                'POST',
                '/api/games/join',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode(['inviteCode' => $code], JSON_INVALID_UTF8_SUBSTITUTE)
            );

            $statusCode = $this->client->getResponse()->getStatusCode();

            $acceptableCodes = array_merge(FuzzPayloadProvider::acceptableHttpCodes(), [404]);
            if (!in_array($statusCode, $acceptableCodes)) {
                $issues[] = sprintf("inviteCode=%s → HTTP %d", json_encode($code), $statusCode);
            }
        }

        $this->assertEmpty($issues, "Fuzzing join inviteCode :\n" . implode("\n", $issues));
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
