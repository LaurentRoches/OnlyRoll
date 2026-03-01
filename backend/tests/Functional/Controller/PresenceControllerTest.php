<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\DTO\Game\CreateGameDTO;
use App\Entity\Game;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\GameService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests fonctionnels pour PresenceController.
 */
final class PresenceControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    private EntityManagerInterface $entityManager;

    private User $user;

    private Game $game;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $container = $this->client->getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);

        $userRepository = $container->get(UserRepository::class);
        $this->user = $userRepository->findOneBy(['email' => 'presence@example.com']) ?? new User();

        if (!$this->user->getId()) {
            $this->user->setEmail('presence@example.com');
            $this->user->setPseudo('PresenceUser');
            $this->user->setPassword('$2y$13$hashedpassword');
            $this->entityManager->persist($this->user);
            $this->entityManager->flush();
        }

        $gameService = $container->get(GameService::class);
        $dto = new CreateGameDTO();
        $dto->name = 'Test Presence Game';
        $dto->description = null;
        $dto->maxPlayers = 4;
        $dto->isPublic = true;
        $this->game = $gameService->createGame($dto, $this->user);

        $this->entityManager->flush();
    }

    protected function tearDown(): void
    {
        if ($this->game) {
            $this->entityManager->remove($this->game);
        }

        $this->entityManager->flush();
        parent::tearDown();
    }

    public function testJoinWithoutAuthentication(): void
    {
        $this->client->request('POST', '/api/games/' . $this->game->getId() . '/presence/join');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testJoinWithAuthentication(): void
    {
        $this->client->loginUser($this->user);
        $this->client->request('POST', '/api/games/' . $this->game->getId() . '/presence/join');

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('onlineUsers', $data);
        $this->assertIsArray($data['onlineUsers']);
        $this->assertContains($this->user->getId(), $data['onlineUsers']);
    }

    public function testJoinWithNonExistentGame(): void
    {
        $this->client->loginUser($this->user);
        $this->client->request('POST', '/api/games/99999/presence/join');

        $this->assertResponseStatusCodeSame(404);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);
        $this->assertSame('Partie introuvable', $data['error']);
    }

    public function testLeaveWithAuthentication(): void
    {
        $this->client->loginUser($this->user);
        $this->client->request('POST', '/api/games/' . $this->game->getId() . '/presence/leave');

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertTrue($data['success']);
    }

    public function testLeaveWithNonExistentGame(): void
    {
        $this->client->loginUser($this->user);
        $this->client->request('POST', '/api/games/99999/presence/leave');

        $this->assertResponseStatusCodeSame(404);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);
        $this->assertSame('Partie introuvable', $data['error']);
    }

    public function testHeartbeatWithNonExistentGame(): void
    {
        $this->client->loginUser($this->user);
        $this->client->request('POST', '/api/games/99999/presence/heartbeat');

        $this->assertResponseStatusCodeSame(404);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);
        $this->assertSame('Partie introuvable', $data['error']);
    }

    public function testGetOnlineUsersWithNonExistentGame(): void
    {
        $this->client->loginUser($this->user);
        $this->client->request('GET', '/api/games/99999/presence/online');

        $this->assertResponseStatusCodeSame(404);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $data);
        $this->assertSame('Partie introuvable', $data['error']);
    }
}
