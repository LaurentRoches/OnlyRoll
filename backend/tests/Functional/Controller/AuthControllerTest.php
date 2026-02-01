<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class AuthControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $this->cleanDatabase();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }

    // ==================== REGISTER ====================

    public function testRegisterWithValidData(): void
    {
        $this->client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'test@example.com',
            'pseudo' => 'TestUser',
            'password' => 'SecurePassword123!',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('message', $response);
        $this->assertArrayHasKey('user', $response);
        $this->assertSame('User created successfully', $response['message']);
        $this->assertSame('test@example.com', $response['user']['email']);
        $this->assertSame('TestUser', $response['user']['pseudo']);

        $user = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => 'test@example.com']);

        $this->assertNotNull($user);
        $this->assertSame('TestUser', $user->getPseudo());
        $this->assertTrue($user->isVerified());
    }

    public function testRegisterWithExistingEmail(): void
    {
        $this->createUser('existing@example.com', 'ExistingUser');

        $this->client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'existing@example.com',
            'pseudo' => 'NewUser',
            'password' => 'Password123!',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_CONFLICT);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $response);
        $this->assertSame('Email already exists', $response['error']);
    }

    public function testRegisterWithExistingPseudo(): void
    {
        $this->createUser('existing@example.com', 'ExistingPseudo');

        $this->client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'newemail@example.com',
            'pseudo' => 'ExistingPseudo',
            'password' => 'Password123!',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_CONFLICT);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $response);
        $this->assertSame('Pseudo already exists', $response['error']);
    }

    public function testRegisterWithInvalidEmail(): void
    {
        $this->client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'invalid-email',
            'pseudo' => 'TestUser',
            'password' => 'Password123!',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testRegisterWithMissingFields(): void
    {
        $this->client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'test@example.com',
            'password' => 'Password123!',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testRegisterWithEmptyPassword(): void
    {
        $this->client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode([
            'email' => 'test@example.com',
            'pseudo' => 'TestUser',
            'password' => '',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testRegisterWithInvalidJson(): void
    {
        $this->client->request('POST', '/api/register', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], 'invalid json');

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    // ==================== ME ====================

    public function testMeWithAuthenticatedUser(): void
    {
        $user = $this->createUser('auth@example.com', 'AuthUser');

        $this->client->loginUser($user);

        $this->client->request('GET', '/api/me');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('id', $response);
        $this->assertArrayHasKey('email', $response);
        $this->assertArrayHasKey('pseudo', $response);
        $this->assertArrayHasKey('roles', $response);
        $this->assertArrayHasKey('isVerified', $response);
        $this->assertArrayHasKey('timezone', $response);
        $this->assertArrayHasKey('language', $response);
        $this->assertArrayHasKey('createdAt', $response);
        $this->assertArrayHasKey('updatedAt', $response);

        $this->assertSame('auth@example.com', $response['email']);
        $this->assertSame('AuthUser', $response['pseudo']);
        $this->assertSame(['ROLE_USER'], $response['roles']);
    }

    public function testMeWithoutAuthentication(): void
    {
        $this->client->request('GET', '/api/me');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testMeReturnsCorrectDataFormat(): void
    {
        $user = $this->createUser('format@example.com', 'FormatUser');
        $user->setTimezone('Europe/Paris');
        $user->setLanguage('fr');
        $user->setAvatar('avatar.jpg');

        $this->entityManager->flush();

        $this->client->loginUser($user);
        $this->client->request('GET', '/api/me');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $response = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertSame('Europe/Paris', $response['timezone']);
        $this->assertSame('fr', $response['language']);
        $this->assertSame('avatar.jpg', $response['avatar']);

        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/', $response['createdAt']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/', $response['updatedAt']);
    }

    // ==================== LOGOUT ====================

    public function testLogout(): void
    {
        $this->client->request('POST', '/api/logout');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $response);
        $this->assertSame('Déconnexion réussie', $response['message']);
    }

    public function testLogoutClearsCookie(): void
    {
        $this->client->request('POST', '/api/logout');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $response = $this->client->getResponse();
        $cookies = $response->headers->getCookies();

        $jwtCookie = null;
        foreach ($cookies as $cookie) {
            if ($cookie->getName() === 'jwt_token') {
                $jwtCookie = $cookie;
                break;
            }
        }

        if ($jwtCookie) {
            $this->assertLessThan(time(), $jwtCookie->getExpiresTime());
        }
    }

    public function testLogoutWithAuthenticatedUser(): void
    {
        $user = $this->createUser('logout@example.com', 'LogoutUser');
        $this->client->loginUser($user);

        $this->client->request('POST', '/api/logout');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('Déconnexion réussie', $response['message']);
    }

    // ==================== HELPERS ====================

    private function createUser(string $email, string $pseudo): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setPseudo($pseudo);
        $user->setPassword('$2y$13$hashed_password');
        $user->setRoles(['ROLE_USER']);
        $user->setIsVerified(true);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function cleanDatabase(): void
    {
        $this->entityManager->getConnection()->executeStatement('SET FOREIGN_KEY_CHECKS = 0');

        $this->entityManager->getConnection()->executeStatement('TRUNCATE TABLE game_message');
        $this->entityManager->getConnection()->executeStatement('TRUNCATE TABLE game_token');
        $this->entityManager->getConnection()->executeStatement('TRUNCATE TABLE game_player');
        $this->entityManager->getConnection()->executeStatement('TRUNCATE TABLE game_map');
        $this->entityManager->getConnection()->executeStatement('TRUNCATE TABLE game');
        $this->entityManager->getConnection()->executeStatement('TRUNCATE TABLE user');

        $this->entityManager->getConnection()->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
    }
}
