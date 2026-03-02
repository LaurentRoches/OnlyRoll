<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ProfileControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    private EntityManagerInterface $entityManager;

    private UserPasswordHasherInterface $passwordHasher;

    private User $testUser;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $this->cleanDatabase();

        $this->testUser = $this->createUser('test@example.com', 'TestUser', 'CurrentPassword1!');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }

    public function testShowProfileReturnsCurrentUser(): void
    {
        $this->client->loginUser($this->testUser);
        $this->client->request('GET', '/api/profile');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame($this->testUser->getId(), $response['id']);
        $this->assertSame('TestUser', $response['pseudo']);
        $this->assertSame('test@example.com', $response['email']);
    }

    public function testShowProfileRequiresAuthentication(): void
    {
        $this->client->request('GET', '/api/profile');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testUpdateProfileUpdatesPseudo(): void
    {
        $this->client->loginUser($this->testUser);
        $this->client->request('PUT', '/api/profile', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'pseudo' => 'NewPseudo',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame('NewPseudo', $response['pseudo']);
    }

    public function testUpdateProfileUpdatesTimezone(): void
    {
        $this->client->loginUser($this->testUser);
        $this->client->request('PUT', '/api/profile', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'timezone' => 'Europe/Paris',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame('Europe/Paris', $response['timezone']);
    }

    public function testUpdateProfileUpdatesLanguage(): void
    {
        $this->client->loginUser($this->testUser);
        $this->client->request('PUT', '/api/profile', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'language' => 'fr',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame('fr', $response['language']);
    }

    public function testUpdateProfileWithInvalidPseudoReturnsBadRequest(): void
    {
        $this->client->loginUser($this->testUser);
        $this->client->request('PUT', '/api/profile', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'pseudo' => 'ab',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testUpdateProfileWithInvalidTimezoneReturnsBadRequest(): void
    {
        $this->client->loginUser($this->testUser);
        $this->client->request('PUT', '/api/profile', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'timezone' => 'Invalid/Timezone',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testUpdateProfileRequiresAuthentication(): void
    {
        $this->client->request('PUT', '/api/profile', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'pseudo' => 'NewPseudo',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testChangePasswordSuccessfully(): void
    {
        $this->client->loginUser($this->testUser);
        $this->client->request('PUT', '/api/profile/password', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'currentPassword' => 'CurrentPassword1!',
            'newPassword' => 'NewSecurePassword1!',
            'confirmPassword' => 'NewSecurePassword1!',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame('Mot de passe modifié avec succès', $response['message']);
    }

    public function testChangePasswordWithWrongCurrentPassword(): void
    {
        $this->client->loginUser($this->testUser);
        $this->client->request('PUT', '/api/profile/password', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'currentPassword' => 'WrongPassword!',
            'newPassword' => 'NewSecurePassword1!',
            'confirmPassword' => 'NewSecurePassword1!',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testChangePasswordWithWeakNewPassword(): void
    {
        $this->client->loginUser($this->testUser);
        $this->client->request('PUT', '/api/profile/password', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'currentPassword' => 'CurrentPassword1!',
            'newPassword' => 'weak',
            'confirmPassword' => 'weak',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testChangePasswordWithMismatchedConfirmation(): void
    {
        $this->client->loginUser($this->testUser);
        $this->client->request('PUT', '/api/profile/password', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'currentPassword' => 'CurrentPassword1!',
            'newPassword' => 'NewSecurePassword1!',
            'confirmPassword' => 'DifferentPassword1!',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testChangePasswordRequiresAuthentication(): void
    {
        $this->client->request('PUT', '/api/profile/password', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'currentPassword' => 'CurrentPassword1!',
            'newPassword' => 'NewSecurePassword1!',
            'confirmPassword' => 'NewSecurePassword1!',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testDeleteAvatarSuccessfully(): void
    {
        $this->testUser->setAvatar('/uploads/avatars/test.png');
        $this->entityManager->flush();

        $this->client->loginUser($this->testUser);
        $this->client->request('DELETE', '/api/profile/avatar');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame('Avatar supprimé avec succès', $response['message']);
    }

    public function testDeleteAvatarRequiresAuthentication(): void
    {
        $this->client->request('DELETE', '/api/profile/avatar');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    private function createUser(string $email, string $pseudo, string $password): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setPseudo($pseudo);
        $user->setRoles(['ROLE_USER']);
        $user->setIsVerified(true);

        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

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
