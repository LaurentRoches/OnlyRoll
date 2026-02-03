<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Admin;

use App\DTO\Admin\UserFilterDTO;
use App\DTO\Admin\UserUpdateDTO;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\Admin\AdminUserService;
use App\Service\AuditLogService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class AdminUserServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;

    private UserRepository&MockObject $userRepository;

    private AuditLogService&MockObject $auditLogService;

    private LoggerInterface&MockObject $logger;

    private RequestStack&MockObject $requestStack;

    private AdminUserService $adminUserService;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->auditLogService = $this->createMock(AuditLogService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->requestStack = $this->createMock(RequestStack::class);

        $this->requestStack->method('getCurrentRequest')->willReturn(null);

        $this->adminUserService = new AdminUserService(
            $this->entityManager,
            $this->userRepository,
            $this->auditLogService,
            $this->logger,
            $this->requestStack,
        );
    }

    public function testListUsersReturnsFilteredResults(): void
    {
        $filter = new UserFilterDTO();
        $filter->search = 'test';
        $filter->page = 1;
        $filter->limit = 20;

        $expectedResult = [
            'data' => [],
            'total' => 10,
            'page' => 1,
            'limit' => 20,
            'totalPages' => 1,
        ];

        $this->userRepository->expects($this->once())
            ->method('findWithFilters')
            ->with($filter)
            ->willReturn($expectedResult);

        $result = $this->adminUserService->listUsers($filter);

        $this->assertSame($expectedResult, $result);
    }

    public function testGetUserReturnsUser(): void
    {
        $user = new User();

        $this->userRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($user);

        $result = $this->adminUserService->getUser(1);

        $this->assertSame($user, $result);
    }

    public function testGetUserReturnsNullWhenNotFound(): void
    {
        $this->userRepository->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $result = $this->adminUserService->getUser(999);

        $this->assertNull($result);
    }

    public function testUpdateUserUpdatesPseudo(): void
    {
        $user = new User();
        $user->setPseudo('OldPseudo');

        $admin = new User();

        $dto = new UserUpdateDTO();
        $dto->pseudo = 'NewPseudo';

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->auditLogService->expects($this->once())
            ->method('log');

        $this->logger->expects($this->once())
            ->method('info');

        $result = $this->adminUserService->updateUser($user, $dto, $admin);

        $this->assertSame('NewPseudo', $result->getPseudo());
    }

    public function testUpdateUserUpdatesEmail(): void
    {
        $user = new User();
        $user->setEmail('old@example.com');

        $admin = new User();

        $dto = new UserUpdateDTO();
        $dto->email = 'new@example.com';

        $this->entityManager->expects($this->once())
            ->method('flush');

        $result = $this->adminUserService->updateUser($user, $dto, $admin);

        $this->assertSame('new@example.com', $result->getEmail());
    }

    public function testUpdateUserUpdatesIsActive(): void
    {
        $user = new User();

        $admin = new User();

        $dto = new UserUpdateDTO();
        $dto->isActive = false;

        $this->entityManager->expects($this->once())
            ->method('flush');

        $result = $this->adminUserService->updateUser($user, $dto, $admin);

        $this->assertFalse($result->isActive());
    }

    public function testUpdateUserUpdatesRoles(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_USER']);

        $admin = new User();

        $dto = new UserUpdateDTO();
        $dto->roles = ['ROLE_USER', 'ROLE_ADMIN'];

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->auditLogService->expects($this->exactly(2))
            ->method('log');

        $result = $this->adminUserService->updateUser($user, $dto, $admin);

        $this->assertContains('ROLE_ADMIN', $result->getRoles());
    }

    public function testUpdateUserDoesNotFlushWhenNoChanges(): void
    {
        $user = new User();
        $user->setPseudo('TestUser');

        $admin = new User();

        $dto = new UserUpdateDTO();
        $dto->pseudo = 'TestUser';

        $this->entityManager->expects($this->never())
            ->method('flush');

        $this->adminUserService->updateUser($user, $dto, $admin);
    }

    public function testSoftDeleteSetsDeletedAtAndDeactivates(): void
    {
        $user = new User();

        $admin = new User();

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->auditLogService->expects($this->once())
            ->method('log');

        $this->logger->expects($this->once())
            ->method('info');

        $this->adminUserService->softDelete($user, $admin);

        $this->assertNotNull($user->getDeletedAt());
        $this->assertFalse($user->isActive());
    }

    public function testSoftDeleteDoesNothingWhenAlreadyDeleted(): void
    {
        $user = new User();
        $user->setDeletedAt(new DateTimeImmutable());

        $admin = new User();

        $this->entityManager->expects($this->never())
            ->method('flush');

        $this->adminUserService->softDelete($user, $admin);
    }

    public function testRestoreRemovesDeletedAtAndActivates(): void
    {
        $user = new User();
        $user->setDeletedAt(new DateTimeImmutable());
        $user->setIsActive(false);

        $admin = new User();

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->auditLogService->expects($this->once())
            ->method('log');

        $this->logger->expects($this->once())
            ->method('info');

        $this->adminUserService->restore($user, $admin);

        $this->assertNull($user->getDeletedAt());
        $this->assertTrue($user->isActive());
    }

    public function testRestoreDoesNothingWhenNotDeleted(): void
    {
        $user = new User();

        $admin = new User();

        $this->entityManager->expects($this->never())
            ->method('flush');

        $this->adminUserService->restore($user, $admin);
    }

    public function testLockAccountSetsLockedUntil(): void
    {
        $user = new User();

        $admin = new User();

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->auditLogService->expects($this->once())
            ->method('logAccountLocked');

        $this->logger->expects($this->once())
            ->method('info');

        $this->adminUserService->lockAccount($user, $admin, 60);

        $this->assertNotNull($user->getLockedUntil());
        $this->assertGreaterThan(new DateTimeImmutable(), $user->getLockedUntil());
    }

    public function testLockAccountWithZeroMinutesLocksForOneYear(): void
    {
        $user = new User();

        $admin = new User();

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->adminUserService->lockAccount($user, $admin, 0);

        $this->assertNotNull($user->getLockedUntil());

        $oneYearFromNow = (new DateTimeImmutable())->modify('+11 months');
        $this->assertGreaterThan($oneYearFromNow, $user->getLockedUntil());
    }

    public function testUnlockAccountClearsLockAndResets(): void
    {
        $user = new User();
        $user->setLockedUntil(new DateTimeImmutable('+1 hour'));
        $user->setFailedLoginAttempts(5);

        $admin = new User();

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->auditLogService->expects($this->once())
            ->method('logAccountUnlocked');

        $this->logger->expects($this->once())
            ->method('info');

        $this->adminUserService->unlockAccount($user, $admin);

        $this->assertNull($user->getLockedUntil());
        $this->assertSame(0, $user->getFailedLoginAttempts());
    }

    public function testUnlockAccountDoesNothingWhenNotLocked(): void
    {
        $user = new User();

        $admin = new User();

        $this->entityManager->expects($this->never())
            ->method('flush');

        $this->adminUserService->unlockAccount($user, $admin);
    }

    public function testActivateUserSetsIsActive(): void
    {
        $user = new User();
        $user->setIsActive(false);

        $admin = new User();

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->auditLogService->expects($this->once())
            ->method('log');

        $this->logger->expects($this->once())
            ->method('info');

        $this->adminUserService->activateUser($user, $admin);

        $this->assertTrue($user->isActive());
    }

    public function testActivateUserDoesNothingWhenAlreadyActive(): void
    {
        $user = new User();

        $admin = new User();

        $this->entityManager->expects($this->never())
            ->method('flush');

        $this->adminUserService->activateUser($user, $admin);
    }

    public function testDeactivateUserSetsIsActiveFalse(): void
    {
        $user = new User();

        $admin = new User();

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->auditLogService->expects($this->once())
            ->method('log');

        $this->logger->expects($this->once())
            ->method('info');

        $this->adminUserService->deactivateUser($user, $admin);

        $this->assertFalse($user->isActive());
    }

    public function testDeactivateUserDoesNothingWhenAlreadyInactive(): void
    {
        $user = new User();
        $user->setIsActive(false);

        $admin = new User();

        $this->entityManager->expects($this->never())
            ->method('flush');

        $this->adminUserService->deactivateUser($user, $admin);
    }

    public function testGetStatisticsReturnsStats(): void
    {
        $expectedStats = [
            'total' => 100,
            'active' => 90,
            'inactive' => 5,
            'deleted' => 3,
            'locked' => 2,
            'admins' => 3,
            'newThisWeek' => 10,
            'activeToday' => 25,
        ];

        $this->userRepository->expects($this->once())
            ->method('getStatistics')
            ->willReturn($expectedStats);

        $result = $this->adminUserService->getStatistics();

        $this->assertSame($expectedStats, $result);
    }
}
