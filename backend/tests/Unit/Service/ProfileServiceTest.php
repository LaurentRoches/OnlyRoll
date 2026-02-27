<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\DTO\Profile\PasswordChangeDTO;
use App\DTO\Profile\ProfileUpdateDTO;
use App\Entity\User;
use App\Enum\AuditAction;
use App\Exception\Profile\InvalidPasswordException;
use App\Service\AuditLogService;
use App\Service\ProfileService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ProfileServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;

    private UserPasswordHasherInterface&MockObject $passwordHasher;

    private AuditLogService&MockObject $auditLogService;

    private LoggerInterface&MockObject $logger;

    private RequestStack&MockObject $requestStack;

    private ProfileService $profileService;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->auditLogService = $this->createMock(AuditLogService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->requestStack = $this->createMock(RequestStack::class);

        $this->requestStack->method('getCurrentRequest')->willReturn(null);

        $this->profileService = new ProfileService(
            $this->entityManager,
            $this->passwordHasher,
            $this->auditLogService,
            $this->logger,
            $this->requestStack,
        );
    }

    public function testChangePasswordSuccessfully(): void
    {
        $user = new User();
        $user->setPassword('old_hashed_password');

        $dto = new PasswordChangeDTO();
        $dto->currentPassword = 'currentPassword123';
        $dto->newPassword = 'NewSecurePassword1!';
        $dto->confirmPassword = 'NewSecurePassword1!';

        $this->passwordHasher->expects($this->exactly(2))
            ->method('isPasswordValid')
            ->willReturnOnConsecutiveCalls(true, false);

        $this->passwordHasher->expects($this->once())
            ->method('hashPassword')
            ->with($user, 'NewSecurePassword1!')
            ->willReturn('new_hashed_password');

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->auditLogService->expects($this->once())
            ->method('logPasswordChange');

        $this->logger->expects($this->once())
            ->method('info');

        $this->profileService->changePassword($user, $dto);

        $this->assertSame('new_hashed_password', $user->getPassword());
    }

    public function testChangePasswordThrowsExceptionOnInvalidCurrentPassword(): void
    {
        $user = new User();

        $dto = new PasswordChangeDTO();
        $dto->currentPassword = 'wrongPassword';
        $dto->newPassword = 'NewSecurePassword1!';

        $this->passwordHasher->expects($this->once())
            ->method('isPasswordValid')
            ->with($user, 'wrongPassword')
            ->willReturn(false);

        $this->auditLogService->expects($this->once())
            ->method('logPasswordChangeFailed')
            ->with($user, 'invalid_current_password', $this->anything());

        $this->expectException(InvalidPasswordException::class);
        $this->expectExceptionMessage('Mot de passe actuel incorrect');

        $this->profileService->changePassword($user, $dto);
    }

    public function testChangePasswordThrowsExceptionWhenNewPasswordSameAsCurrent(): void
    {
        $user = new User();

        $dto = new PasswordChangeDTO();
        $dto->currentPassword = 'currentPassword';
        $dto->newPassword = 'currentPassword';

        $this->passwordHasher->expects($this->exactly(2))
            ->method('isPasswordValid')
            ->willReturn(true);

        $this->auditLogService->expects($this->once())
            ->method('logPasswordChangeFailed')
            ->with($user, 'same_as_current', $this->anything());

        $this->expectException(InvalidPasswordException::class);
        $this->expectExceptionMessage('Le nouveau mot de passe doit être différent de l\'ancien');

        $this->profileService->changePassword($user, $dto);
    }

    public function testUpdateProfileUpdatesPseudo(): void
    {
        $user = new User();
        $user->setPseudo('OldPseudo');

        $dto = new ProfileUpdateDTO();
        $dto->pseudo = 'NewPseudo';

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->auditLogService->expects($this->once())
            ->method('log')
            ->with(
                AuditAction::PROFILE_UPDATED,
                $user,
                $user,
                'user',
                $this->anything(),
                $this->callback(function ($details) {
                    return isset($details['changes']['pseudo']);
                }),
                $this->anything(),
            );

        $this->logger->expects($this->once())
            ->method('info');

        $result = $this->profileService->updateProfile($user, $dto);

        $this->assertSame('NewPseudo', $result->getPseudo());
    }

    public function testUpdateProfileUpdatesTimezone(): void
    {
        $user = new User();
        $user->setTimezone('UTC');

        $dto = new ProfileUpdateDTO();
        $dto->timezone = 'Europe/Paris';

        $this->entityManager->expects($this->once())
            ->method('flush');

        $result = $this->profileService->updateProfile($user, $dto);

        $this->assertSame('Europe/Paris', $result->getTimezone());
    }

    public function testUpdateProfileUpdatesLanguage(): void
    {
        $user = new User();
        $user->setLanguage('en');

        $dto = new ProfileUpdateDTO();
        $dto->language = 'fr';

        $this->entityManager->expects($this->once())
            ->method('flush');

        $result = $this->profileService->updateProfile($user, $dto);

        $this->assertSame('fr', $result->getLanguage());
    }

    public function testUpdateProfileDoesNotFlushWhenNoChanges(): void
    {
        $user = new User();
        $user->setPseudo('TestUser');
        $user->setTimezone('UTC');
        $user->setLanguage('en');

        $dto = new ProfileUpdateDTO();
        $dto->pseudo = 'TestUser';
        $dto->timezone = 'UTC';
        $dto->language = 'en';

        $this->entityManager->expects($this->never())
            ->method('flush');

        $this->profileService->updateProfile($user, $dto);
    }

    public function testUpdateProfileHandlesNullValues(): void
    {
        $user = new User();
        $user->setPseudo('TestUser');

        $dto = new ProfileUpdateDTO();

        $this->entityManager->expects($this->never())
            ->method('flush');

        $result = $this->profileService->updateProfile($user, $dto);

        $this->assertSame('TestUser', $result->getPseudo());
    }

    public function testUpdateAvatarSetsAvatarPath(): void
    {
        $user = new User();
        $user->setAvatar('/old/avatar.png');

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->auditLogService->expects($this->once())
            ->method('log')
            ->with(
                AuditAction::AVATAR_CHANGED,
                $user,
                $user,
                'user',
                $this->anything(),
                $this->callback(function ($details) {
                    return isset($details['old_avatar']) && $details['old_avatar'] === '/old/avatar.png';
                }),
                $this->anything(),
            );

        $this->logger->expects($this->once())
            ->method('info');

        $result = $this->profileService->updateAvatar($user, '/new/avatar.png');

        $this->assertSame('/new/avatar.png', $result->getAvatar());
    }

    public function testRemoveAvatarClearsAvatar(): void
    {
        $user = new User();
        $user->setAvatar('/uploads/avatars/test.png');

        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->auditLogService->expects($this->once())
            ->method('log')
            ->with(
                AuditAction::AVATAR_CHANGED,
                $user,
                $user,
                'user',
                $this->anything(),
                $this->callback(function ($details) {
                    return isset($details['removed']) && $details['removed'] === true;
                }),
                $this->anything(),
            );

        $this->logger->expects($this->once())
            ->method('info');

        $result = $this->profileService->removeAvatar($user);

        $this->assertNull($result->getAvatar());
    }

    public function testRemoveAvatarDoesNothingWhenNoAvatar(): void
    {
        $user = new User();

        $this->entityManager->expects($this->never())
            ->method('flush');

        $result = $this->profileService->removeAvatar($user);

        $this->assertNull($result->getAvatar());
    }
}
