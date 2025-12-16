<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Game;
use App\Entity\User;
use App\Enum\PlayerRole;
use App\Repository\GameRepository;
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
        $this->client = static::createClient();
        $container = static::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);

        // Créer un utilisateur de test
        $userRepository = $container->get(UserRepository::class);
        $this->user = $userRepository->findOneBy(['email' => 'presence@example.com']);

        if (!$this->user) {
            $this->user = new User();
            $this->user->setEmail('presence@example.com');
            $this->user->setPseudo('PresenceUser');
            $this->user->setPassword('$2y$13$hashedpassword');
            $this->entityManager->persist($this->user);
            $this->entityManager->flush();
        }

        // Créer un jeu de test
        $gameService = $container->get(GameService::class);
        $this->game = $gameService->createGame(
            'Test Presence Game',
            $this->user,
            null,
            4,
            false
        );

        $this->entityManager->flush();
    }

    protected function tearDown(): void
    {
        // Nettoyer les données de test
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

    public function testHeartbeatWithAuthentication(): void
    {
        $this->client->loginUser($this->user);

        // Join d'abord
        $this->client->request('POST', '/api/games/' . $this->game->getId() . '/presence/join');
        $this->assertResponseIsSuccessful();

        // Puis heartbeat
        $this->client->request('POST', '/api/games/' . $this->game->getId() . '/presence/heartbeat');

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('onlineUsers', $data);
        $this->assertArrayHasKey('onlineCount', $data);
        $this->assertIsInt($data['onlineCount']);
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

    public function testGetOnlineUsersWithAuthentication(): void
    {
        $this->client->loginUser($this->user);

        // Join d'abord
        $this->client->request('POST', '/api/games/' . $this->game->getId() . '/presence/join');
        $this->assertResponseIsSuccessful();

        // Puis récupérer la liste
        $this->client->request('GET', '/api/games/' . $this->game->getId() . '/presence/online');

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('onlineUsers', $data);
        $this->assertArrayHasKey('onlineCount', $data);
        $this->assertIsArray($data['onlineUsers']);
        $this->assertIsInt($data['onlineCount']);
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

    public function testJoinLeaveFlow(): void
    {
        $this->client->loginUser($this->user);

        // Join
        $this->client->request('POST', '/api/games/' . $this->game->getId() . '/presence/join');
        $this->assertResponseIsSuccessful();
        $joinData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertContains($this->user->getId(), $joinData['onlineUsers']);

        // Vérifier que l'utilisateur est en ligne
        $this->client->request('GET', '/api/games/' . $this->game->getId() . '/presence/online');
        $this->assertResponseIsSuccessful();
        $onlineData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertContains($this->user->getId(), $onlineData['onlineUsers']);

        // Leave
        $this->client->request('POST', '/api/games/' . $this->game->getId() . '/presence/leave');
        $this->assertResponseIsSuccessful();
    }
}
