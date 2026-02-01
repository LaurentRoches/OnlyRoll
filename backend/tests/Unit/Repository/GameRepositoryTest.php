<?php

declare(strict_types=1);

namespace App\Tests\Unit\Repository;

use App\DTO\Game\GameFilterDTO;
use App\Entity\Game;
use App\Entity\GamePlayer;
use App\Entity\User;
use App\Enum\GameStatus;
use App\Enum\PlayerStatus;
use App\Repository\GameRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class GameRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private GameRepository $repository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->repository = $this->entityManager->getRepository(Game::class);

        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
        $connection->executeStatement('TRUNCATE TABLE game_player');
        $connection->executeStatement('TRUNCATE TABLE game');
        $connection->executeStatement('TRUNCATE TABLE user');
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }

    public function testFindPublicGamesWithFilters(): void
    {
        $user = $this->createUser('gm@test.com', 'GameMaster');
        $game1 = $this->createGame('Epic Adventure', $user, true, GameStatus::IN_PROGRESS);
        $game2 = $this->createGame('Secret Quest', $user, false, GameStatus::IN_PROGRESS);

        $filters = new GameFilterDTO();
        $filters->page = 1;
        $filters->limit = 10;

        $result = $this->repository->findPublicGamesWithFilters($filters);

        $this->assertCount(1, $result['data']);
        $this->assertEquals('Epic Adventure', $result['data'][0]->getName());
        $this->assertEquals(1, $result['total']);
        $this->assertEquals(1, $result['page']);
        $this->assertEquals(1, $result['totalPages']);
    }

    public function testFindPublicGamesWithSearchFilter(): void
    {
        $user = $this->createUser('gm@test.com', 'GameMaster');
        $this->createGame('Epic Adventure', $user, true, GameStatus::IN_PROGRESS);
        $this->createGame('Dragon Quest', $user, true, GameStatus::IN_PROGRESS);
        $this->createGame('Space Journey', $user, true, GameStatus::IN_PROGRESS);

        $filters = new GameFilterDTO();
        $filters->page = 1;
        $filters->limit = 10;
        $filters->search = 'Dragon';

        $result = $this->repository->findPublicGamesWithFilters($filters);

        $this->assertCount(1, $result['data']);
        $this->assertEquals('Dragon Quest', $result['data'][0]->getName());
    }

    public function testFindPublicGamesWithTitleFilter(): void
    {
        $user = $this->createUser('gm@test.com', 'GameMaster');
        $this->createGame('Epic Adventure', $user, true, GameStatus::IN_PROGRESS);
        $this->createGame('Another Epic', $user, true, GameStatus::IN_PROGRESS);

        $filters = new GameFilterDTO();
        $filters->page = 1;
        $filters->limit = 10;
        $filters->title = 'Adventure';

        $result = $this->repository->findPublicGamesWithFilters($filters);

        $this->assertCount(1, $result['data']);
        $this->assertEquals('Epic Adventure', $result['data'][0]->getName());
    }

    public function testFindPublicGamesWithGameMasterFilter(): void
    {
        $user1 = $this->createUser('gm1@test.com', 'MasterOne');
        $user2 = $this->createUser('gm2@test.com', 'MasterTwo');
        $this->createGame('Game 1', $user1, true, GameStatus::IN_PROGRESS);
        $this->createGame('Game 2', $user2, true, GameStatus::IN_PROGRESS);

        $filters = new GameFilterDTO();
        $filters->page = 1;
        $filters->limit = 10;
        $filters->gameMaster = 'MasterOne';

        $result = $this->repository->findPublicGamesWithFilters($filters);

        $this->assertCount(1, $result['data']);
        $this->assertEquals('Game 1', $result['data'][0]->getName());
    }

    public function testFindPublicGamesWithStatusFilter(): void
    {
        $user = $this->createUser('gm@test.com', 'GameMaster');
        $this->createGame('Active Game', $user, true, GameStatus::IN_PROGRESS);
        $this->createGame('Paused Game', $user, true, GameStatus::PAUSED);

        $filters = new GameFilterDTO();
        $filters->page = 1;
        $filters->limit = 10;
        $filters->status = GameStatus::PAUSED;

        $result = $this->repository->findPublicGamesWithFilters($filters);

        $this->assertCount(1, $result['data']);
        $this->assertEquals('Paused Game', $result['data'][0]->getName());
    }

    public function testFindPublicGamesExcludesArchivedByDefault(): void
    {
        $user = $this->createUser('gm@test.com', 'GameMaster');
        $this->createGame('Active Game', $user, true, GameStatus::IN_PROGRESS);
        $this->createGame('Archived Game', $user, true, GameStatus::ARCHIVED);

        $filters = new GameFilterDTO();
        $filters->page = 1;
        $filters->limit = 10;

        $result = $this->repository->findPublicGamesWithFilters($filters);

        $this->assertCount(1, $result['data']);
        $this->assertEquals('Active Game', $result['data'][0]->getName());
    }

    public function testFindPublicGamesWithPagination(): void
    {
        $user = $this->createUser('gm@test.com', 'GameMaster');
        for ($i = 1; $i <= 15; ++$i) {
            $this->createGame("Game $i", $user, true, GameStatus::IN_PROGRESS);
        }

        $filters = new GameFilterDTO();
        $filters->page = 2;
        $filters->limit = 10;

        $result = $this->repository->findPublicGamesWithFilters($filters);

        $this->assertCount(5, $result['data']);
        $this->assertEquals(15, $result['total']);
        $this->assertEquals(2, $result['totalPages']);
    }

    public function testFindPublicGames(): void
    {
        $user = $this->createUser('gm@test.com', 'GameMaster');
        $this->createGame('Public Game', $user, true, GameStatus::IN_PROGRESS);
        $this->createGame('Private Game', $user, false, GameStatus::IN_PROGRESS);

        $games = $this->repository->findPublicGames();

        $this->assertCount(1, $games);
        $this->assertEquals('Public Game', $games[0]->getName());
    }

    public function testFindPublicGamesWithSearch(): void
    {
        $user = $this->createUser('gm@test.com', 'GameMaster');
        $this->createGame('Epic Adventure', $user, true, GameStatus::IN_PROGRESS);
        $this->createGame('Space Journey', $user, true, GameStatus::IN_PROGRESS);

        $games = $this->repository->findPublicGames('Epic');

        $this->assertCount(1, $games);
        $this->assertEquals('Epic Adventure', $games[0]->getName());
    }

    public function testFindPublicGamesWithStatus(): void
    {
        $user = $this->createUser('gm@test.com', 'GameMaster');
        $this->createGame('Active Game', $user, true, GameStatus::IN_PROGRESS);
        $this->createGame('Paused Game', $user, true, GameStatus::PAUSED);

        $games = $this->repository->findPublicGames(null, GameStatus::PAUSED);

        $this->assertCount(1, $games);
        $this->assertEquals('Paused Game', $games[0]->getName());
    }

    public function testFindPublicGamesExcludesArchivedByDefaultInLegacyMethod(): void
    {
        $user = $this->createUser('gm@test.com', 'GameMaster');
        $this->createGame('Active Game', $user, true, GameStatus::IN_PROGRESS);
        $this->createGame('Archived Game', $user, true, GameStatus::ARCHIVED);

        $games = $this->repository->findPublicGames();

        $this->assertCount(1, $games);
    }

    public function testFindPublicGamesIncludesArchivedWhenRequested(): void
    {
        $user = $this->createUser('gm@test.com', 'GameMaster');
        $this->createGame('Active Game', $user, true, GameStatus::IN_PROGRESS);
        $this->createGame('Archived Game', $user, true, GameStatus::ARCHIVED);

        $games = $this->repository->findPublicGames(null, null, false);

        $this->assertCount(2, $games);
    }

    public function testFindUserGames(): void
    {
        $user1 = $this->createUser('user1@test.com', 'user1');
        $user2 = $this->createUser('user2@test.com', 'user2');
        $game1 = $this->createGame('Game 1', $user1, true, GameStatus::IN_PROGRESS);
        $game2 = $this->createGame('Game 2', $user1, true, GameStatus::IN_PROGRESS);
        $game3 = $this->createGame('Game 3', $user2, true, GameStatus::IN_PROGRESS);

        $this->createGamePlayer($game1, $user1);
        $this->createGamePlayer($game2, $user1);
        $this->createGamePlayer($game3, $user2);

        $userGames = $this->repository->findUserGames($user1);

        $this->assertCount(2, $userGames);
    }

    public function testFindGameWithPlayers(): void
    {
        $user1 = $this->createUser('user1@test.com', 'user1');
        $user2 = $this->createUser('user2@test.com', 'user2');
        $game = $this->createGame('Test Game', $user1, true, GameStatus::IN_PROGRESS);

        $this->createGamePlayer($game, $user1);
        $this->createGamePlayer($game, $user2);

        $gameWithPlayers = $this->repository->findGameWithPlayers($game->getId());

        $this->assertNotNull($gameWithPlayers);
        $this->assertCount(2, $gameWithPlayers->getGamePlayers());
        $this->assertNotNull($gameWithPlayers->getGameMaster());
    }

    public function testFindGameWithPlayersReturnsNullWhenNotFound(): void
    {
        $game = $this->repository->findGameWithPlayers(99999);

        $this->assertNull($game);
    }

    public function testFindByInviteCode(): void
    {
        $user = $this->createUser('gm@test.com', 'GameMaster');
        $game = $this->createGame('Test Game', $user, true, GameStatus::IN_PROGRESS);

        $inviteCode = $game->getInviteCode();
        $this->assertNotNull($inviteCode);

        $found = $this->repository->findByInviteCode($inviteCode);

        $this->assertNotNull($found);
        $this->assertEquals('Test Game', $found->getName());
    }

    public function testFindByInviteCodeReturnsNullWhenNotFound(): void
    {
        $found = $this->repository->findByInviteCode('INVALID');

        $this->assertNull($found);
    }

    public function testCountUserGamesByStatus(): void
    {
        $user1 = $this->createUser('user1@test.com', 'user1');
        $user2 = $this->createUser('user2@test.com', 'user2');
        $game1 = $this->createGame('Game 1', $user1, true, GameStatus::IN_PROGRESS);
        $game2 = $this->createGame('Game 2', $user1, true, GameStatus::IN_PROGRESS);
        $game3 = $this->createGame('Game 3', $user1, true, GameStatus::PAUSED);

        $this->createGamePlayer($game1, $user1);
        $this->createGamePlayer($game2, $user1);
        $this->createGamePlayer($game3, $user1);

        $game4 = $this->createGame('Game 4', $user2, true, GameStatus::IN_PROGRESS);
        $this->createGamePlayer($game4, $user2);

        $counts = $this->repository->countUserGamesByStatus($user1);

        $this->assertEquals(2, $counts[GameStatus::IN_PROGRESS->value]);
        $this->assertEquals(1, $counts[GameStatus::PAUSED->value]);
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

    private function createGame(
        string $name,
        User $gameMaster,
        bool $isPublic = true,
        GameStatus $status = GameStatus::IN_PROGRESS
    ): Game {
        $game = new Game();
        $game->setName($name);
        $game->setGameMaster($gameMaster);
        $game->setIsPublic($isPublic);
        $game->setStatus($status);
        $this->entityManager->persist($game);
        $this->entityManager->flush();

        return $game;
    }

    private function createGamePlayer(Game $game, User $user, PlayerStatus $status = PlayerStatus::ACTIVE): GamePlayer
    {
        $gamePlayer = new GamePlayer();
        $gamePlayer->setGame($game);
        $gamePlayer->setUser($user);
        $gamePlayer->setStatus($status);
        $game->addGamePlayer($gamePlayer);
        $this->entityManager->persist($gamePlayer);
        $this->entityManager->flush();

        return $gamePlayer;
    }
}
