<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Admin;

use App\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class AdminUserControllerTest extends WebTestCase
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

    public function testListUsersRequiresAdminRole(): void
    {
        $this->client->loginUser($this->regularUser);
        $this->client->request('GET', '/api/admin/users');

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testListUsersAsAdmin(): void
    {
        $this->client->loginUser($this->adminUser);
        $this->client->request('GET', '/api/admin/users');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('meta', $response);
        $this->assertGreaterThanOrEqual(2, $response['meta']['total']);
    }

    public function testListUsersWithPagination(): void
    {
        for ($i = 1; $i <= 25; ++$i) {
            $this->createUser("user{$i}@example.com", "User{$i}", ['ROLE_USER']);
        }

        $this->client->loginUser($this->adminUser);
        $this->client->request('GET', '/api/admin/users?page=1&limit=10');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertCount(10, $response['data']);
        $this->assertSame(1, $response['meta']['page']);
        $this->assertSame(10, $response['meta']['limit']);
    }

    public function testListUsersWithSearchFilter(): void
    {
        $this->client->loginUser($this->adminUser);
        $this->client->request('GET', '/api/admin/users?search=admin');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertGreaterThanOrEqual(1, \count($response['data']));
    }

    public function testListUsersWithStatusFilter(): void
    {
        $inactiveUser = $this->createUser('inactive@example.com', 'InactiveUser', ['ROLE_USER']);
        $inactiveUser->setIsActive(false);
        $this->entityManager->flush();

        $this->client->loginUser($this->adminUser);
        $this->client->request('GET', '/api/admin/users?status=inactive');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertGreaterThanOrEqual(1, \count($response['data']));
    }

    public function testShowUserAsAdmin(): void
    {
        $this->client->loginUser($this->adminUser);
        $this->client->request('GET', '/api/admin/users/' . $this->regularUser->getId());

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame($this->regularUser->getId(), $response['id']);
        $this->assertSame('RegularUser', $response['pseudo']);
    }

    public function testShowUserReturnsNotFoundForInvalidId(): void
    {
        $this->client->loginUser($this->adminUser);
        $this->client->request('GET', '/api/admin/users/99999');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testUpdateUserAsAdmin(): void
    {
        $this->client->loginUser($this->adminUser);
        $this->client->request('PUT', '/api/admin/users/' . $this->regularUser->getId(), [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'pseudo' => 'UpdatedPseudo',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame('UpdatedPseudo', $response['pseudo']);
    }

    public function testUpdateOwnRoleIsForbidden(): void
    {
        $this->client->loginUser($this->adminUser);
        $this->client->request('PUT', '/api/admin/users/' . $this->adminUser->getId(), [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'roles' => ['ROLE_USER'],
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testUpdateOwnIsActiveIsForbidden(): void
    {
        $this->client->loginUser($this->adminUser);
        $this->client->request('PUT', '/api/admin/users/' . $this->adminUser->getId(), [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'isActive' => false,
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testDeleteUserAsAdmin(): void
    {
        $this->client->loginUser($this->adminUser);
        $this->client->request('DELETE', '/api/admin/users/' . $this->regularUser->getId());

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $this->entityManager->refresh($this->regularUser);
        $this->assertNotNull($this->regularUser->getDeletedAt());
        $this->assertFalse($this->regularUser->isActive());
    }

    public function testDeleteOwnAccountIsForbidden(): void
    {
        $this->client->loginUser($this->adminUser);
        $this->client->request('DELETE', '/api/admin/users/' . $this->adminUser->getId());

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testRestoreDeletedUser(): void
    {
        $this->regularUser->setDeletedAt(new DateTimeImmutable());
        $this->regularUser->setIsActive(false);
        $this->entityManager->flush();

        $this->client->loginUser($this->adminUser);
        $this->client->request('POST', '/api/admin/users/' . $this->regularUser->getId() . '/restore');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $this->entityManager->refresh($this->regularUser);
        $this->assertNull($this->regularUser->getDeletedAt());
        $this->assertTrue($this->regularUser->isActive());
    }

    public function testRestoreNonDeletedUserReturnsBadRequest(): void
    {
        $this->client->loginUser($this->adminUser);
        $this->client->request('POST', '/api/admin/users/' . $this->regularUser->getId() . '/restore');

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testLockUserAccount(): void
    {
        $this->client->loginUser($this->adminUser);
        $this->client->request('POST', '/api/admin/users/' . $this->regularUser->getId() . '/lock', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'minutes' => 60,
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $this->entityManager->refresh($this->regularUser);
        $this->assertTrue($this->regularUser->isLocked());
    }

    public function testLockOwnAccountIsForbidden(): void
    {
        $this->client->loginUser($this->adminUser);
        $this->client->request('POST', '/api/admin/users/' . $this->adminUser->getId() . '/lock', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'minutes' => 60,
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testUnlockUserAccount(): void
    {
        $this->regularUser->setLockedUntil(new DateTimeImmutable('+1 hour'));
        $this->entityManager->flush();

        $this->client->loginUser($this->adminUser);
        $this->client->request('POST', '/api/admin/users/' . $this->regularUser->getId() . '/unlock');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $this->entityManager->refresh($this->regularUser);
        $this->assertFalse($this->regularUser->isLocked());
    }

    public function testUnlockNonLockedUserReturnsBadRequest(): void
    {
        $this->client->loginUser($this->adminUser);
        $this->client->request('POST', '/api/admin/users/' . $this->regularUser->getId() . '/unlock');

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testGetStatisticsAsAdmin(): void
    {
        $this->client->loginUser($this->adminUser);
        $this->client->request('GET', '/api/admin/users/statistics');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('total', $response);
        $this->assertArrayHasKey('active', $response);
        $this->assertArrayHasKey('admins', $response);
    }

    public function testListUsersRequiresAuthentication(): void
    {
        $this->client->request('GET', '/api/admin/users');

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
            } catch (\Exception $e) {
            }
        }

        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
    }
}
