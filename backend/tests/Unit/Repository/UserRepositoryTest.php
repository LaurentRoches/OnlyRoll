<?php

declare(strict_types=1);

namespace App\Tests\Unit\Repository;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

class UserRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private UserRepository $repository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->repository = $this->entityManager->getRepository(User::class);

        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
        $connection->executeStatement('TRUNCATE TABLE user');
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }

    public function testUpgradePasswordSuccess(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPseudo('testuser_' . uniqid());
        $user->setPassword('oldPassword');
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $newPassword = 'newHashedPassword';
        $this->repository->upgradePassword($user, $newPassword);

        $this->assertEquals($newPassword, $user->getPassword());
    }

    public function testUpgradePasswordThrowsExceptionForUnsupportedUser(): void
    {
        $this->expectException(UnsupportedUserException::class);
        $this->expectExceptionMessageMatches('/Instances of ".*" are not supported/');

        $unsupportedUser = new class implements PasswordAuthenticatedUserInterface {
            public function getPassword(): ?string
            {
                return 'password';
            }
        };

        $this->repository->upgradePassword($unsupportedUser, 'newPassword');
    }
}
