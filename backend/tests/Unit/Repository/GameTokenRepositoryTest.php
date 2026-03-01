<?php

declare(strict_types=1);

namespace App\Tests\Unit\Repository;

use App\Entity\Game;
use App\Entity\GameMap;
use App\Entity\GameToken;
use App\Entity\User;
use App\Enum\TokenLayer;
use App\Enum\TokenType;
use App\Repository\GameTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class GameTokenRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    private GameTokenRepository $repository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->repository = $this->entityManager->getRepository(GameToken::class);

        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
        $connection->executeStatement('TRUNCATE TABLE game_token');
        $connection->executeStatement('TRUNCATE TABLE game_map');
        $connection->executeStatement('TRUNCATE TABLE game');
        $connection->executeStatement('TRUNCATE TABLE user');
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }

    public function testFindByMap(): void
    {
        $user = $this->createUser('test@test.com', 'testuser');
        $game = $this->createGame('Test Game', $user);
        $map = $this->createMap($game, 'Test Map');

        $token1 = $this->createToken($map, 'Token 1');
        $token2 = $this->createToken($map, 'Token 2');

        $tokens = $this->repository->findByMap($map);

        $this->assertCount(2, $tokens);
    }

    public function testFindVisibleByMap(): void
    {
        $user = $this->createUser('test@test.com', 'testuser');
        $game = $this->createGame('Test Game', $user);
        $map = $this->createMap($game, 'Test Map');

        $this->createToken($map, 'Visible 1', true);
        $this->createToken($map, 'Visible 2', true);
        $this->createToken($map, 'Hidden', false);

        $tokens = $this->repository->findVisibleByMap($map, $user);

        $this->assertCount(2, $tokens);
    }

    public function testFindVisibleTokensByMap(): void
    {
        $user = $this->createUser('test@test.com', 'testuser');
        $game = $this->createGame('Test Game', $user);
        $map = $this->createMap($game, 'Test Map');

        $this->createToken($map, 'Visible 1', true);
        $this->createToken($map, 'Visible 2', true);
        $this->createToken($map, 'Hidden', false);

        $tokens = $this->repository->findVisibleTokensByMap($map);

        $this->assertCount(2, $tokens);
    }

    public function testFindTokensByType(): void
    {
        $user = $this->createUser('test@test.com', 'testuser');
        $game = $this->createGame('Test Game', $user);
        $map = $this->createMap($game, 'Test Map');

        $token1 = $this->createToken($map, 'Character 1');
        $token1->setType(TokenType::CHARACTER);
        $this->entityManager->flush();

        $token2 = $this->createToken($map, 'Character 2');
        $token2->setType(TokenType::CHARACTER);
        $this->entityManager->flush();

        $token3 = $this->createToken($map, 'Object 1');
        $token3->setType(TokenType::OBJECT);
        $this->entityManager->flush();

        $tokens = $this->repository->findTokensByType($map, TokenType::CHARACTER->value);

        $this->assertCount(2, $tokens);
    }

    public function testFindTokensByLayer(): void
    {
        $user = $this->createUser('test@test.com', 'testuser');
        $game = $this->createGame('Test Game', $user);
        $map = $this->createMap($game, 'Test Map');

        $token1 = $this->createToken($map, 'Token 1');
        $token1->setLayer(TokenLayer::TOKENS);
        $this->entityManager->flush();

        $token2 = $this->createToken($map, 'Token 2');
        $token2->setLayer(TokenLayer::TOKENS);
        $this->entityManager->flush();

        $token3 = $this->createToken($map, 'Token 3');
        $token3->setLayer(TokenLayer::OBJECTS);
        $this->entityManager->flush();

        $tokens = $this->repository->findTokensByLayer($map, TokenLayer::TOKENS->value);

        $this->assertCount(2, $tokens);
    }

    public function testFindLockedTokens(): void
    {
        $user = $this->createUser('test@test.com', 'testuser');
        $game = $this->createGame('Test Game', $user);
        $map = $this->createMap($game, 'Test Map');

        $token1 = $this->createToken($map, 'Locked 1');
        $token1->setIsLocked(true);
        $this->entityManager->flush();

        $token2 = $this->createToken($map, 'Locked 2');
        $token2->setIsLocked(true);
        $this->entityManager->flush();

        $this->createToken($map, 'Unlocked');

        $tokens = $this->repository->findLockedTokens($map);

        $this->assertCount(2, $tokens);
    }

    public function testCountByMap(): void
    {
        $user = $this->createUser('test@test.com', 'testuser');
        $game = $this->createGame('Test Game', $user);
        $map = $this->createMap($game, 'Test Map');

        $this->createToken($map, 'Token 1');
        $this->createToken($map, 'Token 2');
        $this->createToken($map, 'Token 3');

        $count = $this->repository->countByMap($map);

        $this->assertEquals(3, $count);
    }

    public function testFindTokensByMapWithVisibleOnlyFlag(): void
    {
        $user = $this->createUser('test@test.com', 'testuser');
        $game = $this->createGame('Test Game', $user);
        $map = $this->createMap($game, 'Test Map');

        $this->createToken($map, 'Visible 1', true);
        $this->createToken($map, 'Visible 2', true);
        $this->createToken($map, 'Hidden', false);

        $allTokens = $this->repository->findTokensByMap($map, false);
        $visibleTokens = $this->repository->findTokensByMap($map, true);

        $this->assertCount(3, $allTokens);
        $this->assertCount(2, $visibleTokens);
    }

    public function testFindTokensInArea(): void
    {
        $user = $this->createUser('test@test.com', 'testuser');
        $game = $this->createGame('Test Game', $user);
        $map = $this->createMap($game, 'Test Map');

        $token1 = $this->createToken($map, 'Token 1');
        $token1->setX(5);
        $token1->setY(5);
        $this->entityManager->flush();

        $token2 = $this->createToken($map, 'Token 2');
        $token2->setX(7);
        $token2->setY(7);
        $this->entityManager->flush();

        $token3 = $this->createToken($map, 'Token 3');
        $token3->setX(15);
        $token3->setY(15);
        $this->entityManager->flush();

        $tokens = $this->repository->findTokensInArea($map, 4, 4, 5, 5);

        $this->assertCount(2, $tokens);
    }

    public function testFindTokenAtPosition(): void
    {
        $user = $this->createUser('test@test.com', 'testuser');
        $game = $this->createGame('Test Game', $user);
        $map = $this->createMap($game, 'Test Map');

        $token = $this->createToken($map, 'Token 1');
        $token->setX(10);
        $token->setY(15);
        $this->entityManager->flush();

        $foundToken = $this->repository->findTokenAtPosition($map, 10, 15);
        $notFoundToken = $this->repository->findTokenAtPosition($map, 1, 1);

        $this->assertNotNull($foundToken);
        $this->assertEquals('Token 1', $foundToken->getName());
        $this->assertNull($notFoundToken);
    }

    public function testCountTokensByType(): void
    {
        $user = $this->createUser('test@test.com', 'testuser');
        $game = $this->createGame('Test Game', $user);
        $map = $this->createMap($game, 'Test Map');

        $token1 = $this->createToken($map, 'Character 1');
        $token1->setType(TokenType::CHARACTER);
        $this->entityManager->flush();

        $token2 = $this->createToken($map, 'Character 2');
        $token2->setType(TokenType::CHARACTER);
        $this->entityManager->flush();

        $token3 = $this->createToken($map, 'Object 1');
        $token3->setType(TokenType::OBJECT);
        $this->entityManager->flush();

        $counts = $this->repository->countTokensByType($map);

        $this->assertArrayHasKey('character', $counts);
        $this->assertArrayHasKey('object', $counts);
        $this->assertEquals(2, $counts['character']);
        $this->assertEquals(1, $counts['object']);
    }

    public function testDeleteAllTokensByMap(): void
    {
        $user = $this->createUser('test@test.com', 'testuser');
        $game = $this->createGame('Test Game', $user);
        $map = $this->createMap($game, 'Test Map');

        $this->createToken($map, 'Token 1');
        $this->createToken($map, 'Token 2');
        $this->createToken($map, 'Token 3');

        $this->assertEquals(3, $this->repository->countByMap($map));

        $deleted = $this->repository->deleteAllTokensByMap($map);

        $this->assertEquals(3, $deleted);
        $this->assertEquals(0, $this->repository->countByMap($map));
    }

    public function testMoveTokens(): void
    {
        $user = $this->createUser('test@test.com', 'testuser');
        $game = $this->createGame('Test Game', $user);
        $map = $this->createMap($game, 'Test Map');

        $token1 = $this->createToken($map, 'Token 1');
        $token1->setX(1);
        $token1->setY(1);
        $this->entityManager->flush();

        $token2 = $this->createToken($map, 'Token 2');
        $token2->setX(2);
        $token2->setY(2);
        $token2->setIsLocked(true);
        $this->entityManager->flush();

        $positions = [
            $token1->getId() => ['x' => 10, 'y' => 10],
            $token2->getId() => ['x' => 20, 'y' => 20],
        ];

        $this->repository->moveTokens($positions);

        $this->entityManager->refresh($token1);
        $this->entityManager->refresh($token2);

        $this->assertEquals(10, $token1->getX());
        $this->assertEquals(10, $token1->getY());
        $this->assertEquals(2, $token2->getX());
        $this->assertEquals(2, $token2->getY());
    }

    private function createUser(string $email, string $pseudo): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setPseudo($pseudo);
        $user->setPassword('password');
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function createGame(string $name, User $gameMaster): Game
    {
        $game = new Game();
        $game->setName($name);
        $game->setGameMaster($gameMaster);
        $this->entityManager->persist($game);
        $this->entityManager->flush();

        return $game;
    }

    private function createMap(Game $game, string $name): GameMap
    {
        $map = new GameMap();
        $map->setGame($game);
        $map->setName($name);
        $map->setWidth(20);
        $map->setHeight(20);
        $this->entityManager->persist($map);
        $this->entityManager->flush();

        return $map;
    }

    private function createToken(GameMap $map, string $name, bool $visible = true): GameToken
    {
        $token = new GameToken();
        $token->setMap($map);
        $token->setName($name);
        $token->setType(TokenType::CHARACTER);
        $token->setX(5);
        $token->setY(5);
        $token->setIsVisible($visible);
        $this->entityManager->persist($token);
        $this->entityManager->flush();

        return $token;
    }
}
