<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\AuditLog;
use App\Entity\User;
use App\Enum\AuditAction;
use App\Service\AuditLogService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class AuditLogServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;

    private RequestStack&MockObject $requestStack;

    private LoggerInterface&MockObject $logger;

    private AuditLogService $auditLogService;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->auditLogService = new AuditLogService(
            $this->entityManager,
            $this->requestStack,
            $this->logger,
            'test-secret',
        );
    }

    public function testLogCreatesAuditLog(): void
    {
        $user = $this->createUser(1);
        $targetUser = $this->createUser(2);

        $this->requestStack->method('getCurrentRequest')->willReturn(null);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(AuditLog::class));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->logger->expects($this->once())
            ->method('info');

        $auditLog = $this->auditLogService->log(
            AuditAction::USER_UPDATED,
            $user,
            $targetUser,
            'user',
            2,
            ['field' => 'value'],
        );

        $this->assertInstanceOf(AuditLog::class, $auditLog);
        $this->assertSame(AuditAction::USER_UPDATED, $auditLog->getAction());
        $this->assertSame($user, $auditLog->getPerformer());
        $this->assertSame($targetUser, $auditLog->getTargetUser());
    }

    public function testLogWithRequestExtractsIpAndUserAgent(): void
    {
        $request = new Request();
        $request->server->set('REMOTE_ADDR', '192.168.1.1');
        $request->headers->set('User-Agent', 'Mozilla/5.0 Test Browser');

        $this->requestStack->method('getCurrentRequest')->willReturn($request);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (AuditLog $auditLog) {
                $this->assertNotSame('192.168.1.1', $auditLog->getIpAddress());
                $this->assertNotNull($auditLog->getIpAddress());
                $this->assertSame('Mozilla/5.0 Test Browser', $auditLog->getUserAgent());

                return true;
            }));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->auditLogService->log(
            AuditAction::LOGIN_SUCCESS,
            null,
            null,
            'user',
            null,
            [],
            $request,
        );
    }

    public function testHashIpAddressReturnsConsistentHash(): void
    {
        $ip = '192.168.1.1';

        $hash1 = $this->auditLogService->hashIpAddress($ip);
        $hash2 = $this->auditLogService->hashIpAddress($ip);

        $this->assertSame($hash1, $hash2);
        $this->assertNotSame($ip, $hash1);
    }

    public function testHashIpAddressReturnsNullForNull(): void
    {
        $result = $this->auditLogService->hashIpAddress(null);

        $this->assertNull($result);
    }

    public function testHashIpAddressReturnsNullForEmptyString(): void
    {
        $result = $this->auditLogService->hashIpAddress('');

        $this->assertNull($result);
    }

    public function testLogSanitizesSensitiveData(): void
    {
        $this->requestStack->method('getCurrentRequest')->willReturn(null);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (AuditLog $auditLog) {
                $details = $auditLog->getDetails();
                $this->assertArrayNotHasKey('password', $details ?? []);
                $this->assertArrayNotHasKey('newPassword', $details ?? []);
                $this->assertArrayHasKey('username', $details ?? []);

                return true;
            }));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->auditLogService->log(
            AuditAction::USER_UPDATED,
            null,
            null,
            'user',
            1,
            [
                'username' => 'testuser',
                'password' => 'secret123',
                'newPassword' => 'newsecret456',
            ],
        );
    }

    public function testLogLoginFailure(): void
    {
        $this->requestStack->method('getCurrentRequest')->willReturn(null);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (AuditLog $auditLog) {
                $this->assertSame(AuditAction::LOGIN_FAILED, $auditLog->getAction());
                $this->assertNull($auditLog->getPerformer());
                $details = $auditLog->getDetails();
                $this->assertArrayHasKey('email', $details ?? []);
                $this->assertSame('test@example.com', $details['email']);

                return true;
            }));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->auditLogService->logLoginFailure('test@example.com');
    }

    public function testLogLoginSuccess(): void
    {
        $user = $this->createUser(1);

        $this->requestStack->method('getCurrentRequest')->willReturn(null);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (AuditLog $auditLog) use ($user) {
                $this->assertSame(AuditAction::LOGIN_SUCCESS, $auditLog->getAction());
                $this->assertSame($user, $auditLog->getPerformer());
                $this->assertSame($user, $auditLog->getTargetUser());

                return true;
            }));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->auditLogService->logLoginSuccess($user);
    }

    public function testLogPasswordChange(): void
    {
        $user = $this->createUser(1);

        $this->requestStack->method('getCurrentRequest')->willReturn(null);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (AuditLog $auditLog) {
                $this->assertSame(AuditAction::PASSWORD_CHANGE, $auditLog->getAction());
                $this->assertEmpty($auditLog->getDetails());

                return true;
            }));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->auditLogService->logPasswordChange($user);
    }

    public function testLogPasswordChangeFailed(): void
    {
        $user = $this->createUser(1);

        $this->requestStack->method('getCurrentRequest')->willReturn(null);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (AuditLog $auditLog) {
                $this->assertSame(AuditAction::PASSWORD_CHANGE_FAILED, $auditLog->getAction());
                $details = $auditLog->getDetails();
                $this->assertArrayHasKey('reason', $details ?? []);
                $this->assertSame('invalid_password', $details['reason']);

                return true;
            }));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->auditLogService->logPasswordChangeFailed($user, 'invalid_password');
    }

    public function testLogAccountLocked(): void
    {
        $user = $this->createUser(1);
        $admin = $this->createUser(2);
        $lockedUntil = new DateTimeImmutable('+1 hour');
        $user->method('getLockedUntil')->willReturn($lockedUntil);

        $this->requestStack->method('getCurrentRequest')->willReturn(null);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (AuditLog $auditLog) use ($admin, $user) {
                $this->assertSame(AuditAction::ACCOUNT_LOCKED, $auditLog->getAction());
                $this->assertSame($admin, $auditLog->getPerformer());
                $this->assertSame($user, $auditLog->getTargetUser());

                return true;
            }));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->auditLogService->logAccountLocked($user, $admin);
    }

    public function testLogAccountUnlocked(): void
    {
        $user = $this->createUser(1);
        $admin = $this->createUser(2);

        $this->requestStack->method('getCurrentRequest')->willReturn(null);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (AuditLog $auditLog) use ($admin, $user) {
                $this->assertSame(AuditAction::ACCOUNT_UNLOCKED, $auditLog->getAction());
                $this->assertSame($admin, $auditLog->getPerformer());
                $this->assertSame($user, $auditLog->getTargetUser());

                return true;
            }));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->auditLogService->logAccountUnlocked($user, $admin);
    }

    public function testLogAdminAccess(): void
    {
        $admin = $this->createUser(1);

        $this->requestStack->method('getCurrentRequest')->willReturn(null);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (AuditLog $auditLog) use ($admin) {
                $this->assertSame(AuditAction::ADMIN_ACCESS, $auditLog->getAction());
                $this->assertSame($admin, $auditLog->getPerformer());
                $details = $auditLog->getDetails();
                $this->assertArrayHasKey('section', $details ?? []);
                $this->assertSame('dashboard', $details['section']);

                return true;
            }));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->auditLogService->logAdminAccess($admin, 'dashboard');
    }

    public function testLogAdminAction(): void
    {
        $admin = $this->createUser(1);
        $targetUser = $this->createUser(2);

        $this->requestStack->method('getCurrentRequest')->willReturn(null);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (AuditLog $auditLog) use ($admin, $targetUser) {
                $this->assertSame(AuditAction::ADMIN_ACTION, $auditLog->getAction());
                $this->assertSame($admin, $auditLog->getPerformer());
                $this->assertSame($targetUser, $auditLog->getTargetUser());
                $details = $auditLog->getDetails();
                $this->assertArrayHasKey('admin_action', $details ?? []);
                $this->assertSame('update_user', $details['admin_action']);

                return true;
            }));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->auditLogService->logAdminAction(
            $admin,
            $targetUser,
            'update_user',
            ['extra' => 'data'],
        );
    }

    public function testLogTruncatesLongUserAgent(): void
    {
        $longUserAgent = str_repeat('A', 600);
        $request = new Request();
        $request->headers->set('User-Agent', $longUserAgent);

        $this->requestStack->method('getCurrentRequest')->willReturn($request);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (AuditLog $auditLog) {
                $userAgent = $auditLog->getUserAgent();
                $this->assertLessThanOrEqual(500, mb_strlen($userAgent ?? ''));

                return true;
            }));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->auditLogService->log(
            AuditAction::LOGIN_SUCCESS,
            null,
            null,
            null,
            null,
            [],
            $request,
        );
    }

    private function createUser(int $id): User&MockObject
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($id);
        $user->method('getUserIdentifier')->willReturn("user{$id}@example.com");

        return $user;
    }
}
