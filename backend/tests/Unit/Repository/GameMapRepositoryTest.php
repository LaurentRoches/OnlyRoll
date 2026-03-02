<?php

declare(strict_types=1);

namespace App\Tests\Unit\Repository;

use App\Entity\Game;
use App\Entity\GameMap;
use App\Entity\User;
use App\Enum\MapGridType;
use App\Repository\GameMapRepository;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class GameMapRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    private GameMapRepository $repository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->repository = $this->entityManager->getRepository(GameMap::class);

        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
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

    public function testFindActiveMapByGame(): void
    {
        $user = $this->createUser('test@test.com', 'testuser');
        $game = $this->createGame('Test Game', $user);

        $activeMap = $this->createMap($game, 'Active Map');
        $activeMap->setIsActive(true);
        $this->entityManager->flush();

        $inactiveMap = $this->createMap($game, 'Inactive Map');
        $inactiveMap->setIsActive(false);
        $this->entityManager->flush();

        $found = $this->repository->findActiveMapByGame($game);

        $this->assertNotNull($found);
        $this->assertEquals('Active Map', $found->getName());
    }

    public function testFindActiveMapByGameReturnsNullWhenNoActiveMap(): void
    {
        $user = $this->createUser('test@test.com', 'testuser');
        $game = $this->createGame('Test Game', $user);

        $map = $this->createMap($game, 'Inactive Map');
        $map->setIsActive(false);
        $this->entityManager->flush();

        $found = $this->repository->findActiveMapByGame($game);

        $this->assertNull($found);
    }

    public function testFindMapsByGame(): void
    {
        $user = $this->createUser('test@test.com', 'testuser');
        $game = $this->createGame('Test Game', $user);

        $this->createMap($game, 'Map 1');
        $this->createMap($game, 'Map 2');
        $this->createMap($game, 'Map 3');

        $maps = $this->repository->findMapsByGame($game);

        $this->assertCount(3, $maps);
    }

    public function testFindMapsByGameActiveOnly(): void
    {
        $user = $this->createUser('test@test.com', 'testuser');
        $game = $this->createGame('Test Game', $user);

        $activeMap = $this->createMap($game, 'Active Map');
        $activeMap->setIsActive(true);
        $this->entityManager->flush();

        $this->createMap($game, 'Inactive Map 1');
        $this->createMap($game, 'Inactive Map 2');

        $maps = $this->repository->findMapsByGame($game, true);

        $this->assertCount(1, $maps);
    }

    public function testFindMapWithTokens(): void
    {
        $user = $this->createUser('test@test.com', 'testuser');
        $game = $this->createGame('Test Game', $user);
        $map = $this->createMap($game, 'Test Map');

        $mapWithTokens = $this->repository->findMapWithTokens($map->getId());

        $this->assertNotNull($mapWithTokens);
        $this->assertEquals('Test Map', $mapWithTokens->getName());
    }

    public function testFindMapWithTokensNotFound(): void
    {
        $mapWithTokens = $this->repository->findMapWithTokens(99999);

        $this->assertNull($mapWithTokens);
    }

    public function testActivateMap(): void
    {
        $user = $this->createUser('test@test.com', 'testuser');
        $game = $this->createGame('Test Game', $user);

        $map1 = $this->createMap($game, 'Map 1');
        $map1->setIsActive(true);
        $this->entityManager->flush();

        $map2 = $this->createMap($game, 'Map 2');
        $map2->setIsActive(false);
        $this->entityManager->flush();

        $this->repository->activateMap($map2);

        $this->entityManager->refresh($map1);
        $this->entityManager->refresh($map2);

        $this->assertFalse($map1->isActive());
        $this->assertTrue($map2->isActive());
    }

    public function testActivateMapThrowsExceptionWhenNoGame(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('La carte doit être associée à un jeu.');

        $map = new GameMap();
        $map->setName('Test Map');
        $map->setWidth(20);
        $map->setHeight(20);

        $this->repository->activateMap($map);
    }

    public function testCountMapsByGame(): void
    {
        $user = $this->createUser('test@test.com', 'testuser');
        $game1 = $this->createGame('Game 1', $user);
        $game2 = $this->createGame('Game 2', $user);

        $this->createMap($game1, 'Map 1');
        $this->createMap($game1, 'Map 2');
        $this->createMap($game2, 'Map 3');

        $counts = $this->repository->countMapsByGame();

        $this->assertEquals(2, $counts[$game1->getId()]);
        $this->assertEquals(1, $counts[$game2->getId()]);
    }

    public function testFindByGridType(): void
    {
        $user = $this->createUser('test@test.com', 'testuser');
        $game = $this->createGame('Test Game', $user);

        $map1 = $this->createMap($game, 'Square Map');
        $map1->setGridType(MapGridType::SQUARE);
        $this->entityManager->flush();

        $map2 = $this->createMap($game, 'Hex Map');
        $map2->setGridType(MapGridType::HEX);
        $this->entityManager->flush();

        $map3 = $this->createMap($game, 'Another Square Map');
        $map3->setGridType(MapGridType::SQUARE);
        $this->entityManager->flush();

        $squareMaps = $this->repository->findByGridType($game, MapGridType::SQUARE->value);

        $this->assertCount(2, $squareMaps);
        foreach ($squareMaps as $map) {
            $this->assertEquals(MapGridType::SQUARE, $map->getGridType());
        }
    }

    public function testFindByGridTypeReturnsEmptyWhenNoMatch(): void
    {
        $user = $this->createUser('test@test.com', 'testuser');
        $game = $this->createGame('Test Game', $user);

        $map = $this->createMap($game, 'Square Map');
        $map->setGridType(MapGridType::SQUARE);
        $this->entityManager->flush();

        $hexMaps = $this->repository->findByGridType($game, MapGridType::HEX->value);

        $this->assertCount(0, $hexMaps);
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
}
