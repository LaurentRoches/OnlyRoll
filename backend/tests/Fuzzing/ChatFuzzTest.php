<?php

declare(strict_types=1);

namespace App\Tests\Fuzzing;

use App\Entity\Game;
use App\Entity\GamePlayer;
use App\Entity\User;
use App\Enum\PlayerRole;
use App\Enum\PlayerStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Fuzz tests sur le système de chat.
 *
 * Surface d'attaque : POST /api/games/{gameId}/chat/messages
 *
 * Risques spécifiques au chat :
 *  - XSS stocké (message en BDD affiché à d'autres joueurs)
 *  - Injection de commandes (/roll, /emote) via payloads
 *  - Overflow du contenu du message
 *  - Types de message invalides
 */
class ChatFuzzTest extends WebTestCase
{
    private KernelBrowser $client;

    private EntityManagerInterface $entityManager;

    private User $fuzzUser;

    private int $gameId;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->cleanDatabase();

        $gm = (new User())
            ->setEmail('fuzz-gm@test.com')
            ->setPseudo('fuzz_gm')
            ->setPassword('$2y$13$hashed_password')
            ->setRoles(['ROLE_USER'])
            ->setIsVerified(true);
        $this->entityManager->persist($gm);

        $this->fuzzUser = (new User())
            ->setEmail('fuzz@test.com')
            ->setPseudo('fuzz_user')
            ->setPassword('$2y$13$hashed_password')
            ->setRoles(['ROLE_USER'])
            ->setIsVerified(true);
        $this->entityManager->persist($this->fuzzUser);

        $game = (new Game())
            ->setName('Fuzz Chat Game')
            ->setGameMaster($gm)
            ->setIsPublic(false)
            ->setMaxPlayers(6);
        $this->entityManager->persist($game);

        $player = (new GamePlayer())
            ->setGame($game)
            ->setUser($this->fuzzUser)
            ->setRole(PlayerRole::PLAYER)
            ->setStatus(PlayerStatus::ACTIVE);
        $this->entityManager->persist($player);

        $this->entityManager->flush();
        $this->gameId = $game->getId();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }

    /**
     * Fuzz sur le contenu du message.
     * Priorité haute : XSS stocké potentiel.
     */
    public function testFuzzMessageContent(): void
    {
        $this->client->loginUser($this->fuzzUser);
        $issues = [];

        $payloads = array_merge(
            FuzzPayloadProvider::xssPayloads(),
            FuzzPayloadProvider::sqlInjectionPayloads(),
            FuzzPayloadProvider::overflowPayloads(),
            FuzzPayloadProvider::encodingPayloads(),
            [
                '/roll 1d20',
                '/roll ' . str_repeat('d20+', 500),
                '@' . str_repeat('user', 500),
                "Ligne1\nLigne2\nLigne3",
                "\x00message\x00",
            ],
        );

        foreach ($payloads as $payload) {
            $this->client->request(
                'POST',
                "/api/games/{$this->gameId}/chat/messages",
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode(['content' => $payload, 'type' => 'chat'], \JSON_INVALID_UTF8_SUBSTITUTE),
            );

            $statusCode = $this->client->getResponse()->getStatusCode();

            if (!\in_array($statusCode, FuzzPayloadProvider::acceptableHttpCodes())) {
                $issues[] = \sprintf(
                    'content=%s → HTTP %d | %s',
                    json_encode(substr((string) $payload, 0, 60)),
                    $statusCode,
                    substr($this->client->getResponse()->getContent(), 0, 150),
                );
            }
        }

        $this->assertEmpty($issues, "Fuzzing chat content :\n" . implode("\n", $issues));
    }

    /**
     * Fuzz sur le type de message.
     * Cible : énumération non validée (valeurs valides : 'chat', 'emote', 'whisper', 'system', 'dice_roll').
     */
    public function testFuzzMessageType(): void
    {
        $this->client->loginUser($this->fuzzUser);
        $issues = [];

        $invalidTypes = [
            'admin', 'hack', '', null, 0, true,
            '<script>', 'message; DROP TABLE messages;',
            str_repeat('type', 100),
            'MESSAGE',
            'CHAT',
            'dice-roll',
        ];

        foreach ($invalidTypes as $type) {
            $this->client->request(
                'POST',
                "/api/games/{$this->gameId}/chat/messages",
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode(['content' => 'Test message', 'type' => $type], \JSON_INVALID_UTF8_SUBSTITUTE),
            );

            $statusCode = $this->client->getResponse()->getStatusCode();

            if (!\in_array($statusCode, FuzzPayloadProvider::acceptableHttpCodes())) {
                $issues[] = \sprintf('type=%s → HTTP %d', json_encode($type), $statusCode);
            }
        }

        $this->assertEmpty($issues, "Fuzzing message type :\n" . implode("\n", $issues));
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
