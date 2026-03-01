<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Admin;

use App\Entity\AuditLog;
use App\Entity\User;
use App\Enum\AuditAction;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class AdminAuditLogControllerTest extends WebTestCase
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

        $this->createAuditLog(AuditAction::LOGIN_SUCCESS, $this->regularUser, $this->regularUser);
        $this->createAuditLog(AuditAction::LOGIN_FAILED, null, null);
        $this->createAuditLog(AuditAction::PASSWORD_CHANGE, $this->regularUser, $this->regularUser);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }

    public function testListAuditLogsRequiresAdminRole(): void
    {
        $this->client->loginUser($this->regularUser);
        $this->client->request('GET', '/api/admin/audit-logs');

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testListAuditLogsAsAdmin(): void
    {
        $this->client->loginUser($this->adminUser);
        $this->client->request('GET', '/api/admin/audit-logs');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('meta', $response);
        $this->assertGreaterThanOrEqual(3, $response['meta']['total']);
    }

    public function testListAuditLogsWithPagination(): void
    {
        $this->client->loginUser($this->adminUser);
        $this->client->request('GET', '/api/admin/audit-logs?page=1&limit=2');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertCount(2, $response['data']);
        $this->assertSame(1, $response['meta']['page']);
        $this->assertSame(2, $response['meta']['limit']);
    }

    public function testListAuditLogsWithActionFilter(): void
    {
        $this->client->loginUser($this->adminUser);
        $this->client->request('GET', '/api/admin/audit-logs?action=login_success');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertGreaterThanOrEqual(1, \count($response['data']));
    }

    public function testListAuditLogsRequiresAuthentication(): void
    {
        $this->client->request('GET', '/api/admin/audit-logs');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testShowAuditLogAsAdmin(): void
    {
        $auditLog = $this->createAuditLog(AuditAction::ADMIN_ACCESS, $this->adminUser, null);

        $this->client->loginUser($this->adminUser);
        $this->client->request('GET', '/api/admin/audit-logs/' . $auditLog->getId());

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame($auditLog->getId(), $response['id']);
        $this->assertSame('admin_access', $response['action']);
    }

    public function testShowAuditLogReturnsNotFoundForInvalidId(): void
    {
        $this->client->loginUser($this->adminUser);
        $this->client->request('GET', '/api/admin/audit-logs/99999');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testByUserReturnsUserAuditLogs(): void
    {
        $this->client->loginUser($this->adminUser);
        $this->client->request('GET', '/api/admin/audit-logs/user/' . $this->regularUser->getId());

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertIsArray($response);
        $this->assertGreaterThanOrEqual(2, \count($response));
    }

    public function testStatisticsReturnsAuditStats(): void
    {
        $this->client->loginUser($this->adminUser);
        $this->client->request('GET', '/api/admin/audit-logs/statistics');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('total', $response);
        $this->assertArrayHasKey('today', $response);
        $this->assertArrayHasKey('failedLoginsToday', $response);
    }

    public function testActionsReturnsAvailableActions(): void
    {
        $this->client->loginUser($this->adminUser);
        $this->client->request('GET', '/api/admin/audit-logs/actions');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertIsArray($response);
        $this->assertNotEmpty($response);

        $firstAction = $response[0];
        $this->assertArrayHasKey('value', $firstAction);
        $this->assertArrayHasKey('label', $firstAction);
        $this->assertArrayHasKey('severity', $firstAction);
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

    private function createAuditLog(AuditAction $action, ?User $performer, ?User $targetUser): AuditLog
    {
        $auditLog = new AuditLog();
        $auditLog->setAction($action);
        $auditLog->setPerformer($performer);
        $auditLog->setTargetUser($targetUser);
        $auditLog->setEntityType('user');
        $auditLog->setIpAddress('hashed_ip_address');

        $this->entityManager->persist($auditLog);
        $this->entityManager->flush();

        return $auditLog;
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
