<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Admin;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class AdminDashboardControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    private EntityManagerInterface $entityManager;

    private User $adminUser;

    private User $regularUser;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $this->cleanDatabase();

        $this->adminUser = $this->createUser('admin@example.com', 'AdminUser', ['ROLE_USER', 'ROLE_ADMIN']);
        $this->regularUser = $this->createUser('user@example.com', 'RegularUser', ['ROLE_USER']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }

    public function testStatsRequiresAdminRole(): void
    {
        $this->client->loginUser($this->regularUser);
        $this->client->request('GET', '/api/admin/dashboard/stats');

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testStatsAsAdmin(): void
    {
        $this->client->loginUser($this->adminUser);
        $this->client->request('GET', '/api/admin/dashboard/stats');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('users', $response);
        $this->assertArrayHasKey('audit', $response);
        $this->assertArrayHasKey('games', $response);

        $this->assertArrayHasKey('total', $response['users']);
        $this->assertArrayHasKey('active', $response['users']);

        $this->assertArrayHasKey('total', $response['audit']);

        $this->assertArrayHasKey('total', $response['games']);
    }

    public function testStatsRequiresAuthentication(): void
    {
        $this->client->request('GET', '/api/admin/dashboard/stats');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testRecentActivityRequiresAdminRole(): void
    {
        $this->client->loginUser($this->regularUser);
        $this->client->request('GET', '/api/admin/dashboard/recent-activity');

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testRecentActivityAsAdmin(): void
    {
        $this->client->loginUser($this->adminUser);
        $this->client->request('GET', '/api/admin/dashboard/recent-activity');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertIsArray($response);
    }

    public function testRecentActivityRequiresAuthentication(): void
    {
        $this->client->request('GET', '/api/admin/dashboard/recent-activity');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    private function createUser(string $email, string $pseudo, array $roles): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setPseudo($pseudo);
        $user->setPassword('$2y$13$hashed_password');
        $user->setRoles($roles);
        $user->setIsVerified(true);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function cleanDatabase(): void
    {
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 0');

        $tables = ['audit_log', 'game_message', 'game_token', 'game_player', 'game_map', 'game', 'user'];
        foreach ($tables as $table) {
            try {
                $connection->executeStatement("TRUNCATE TABLE $table");
            }
            catch (Exception $e) {
            }
        }

        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
        $this->entityManager->clear();
    }
}
