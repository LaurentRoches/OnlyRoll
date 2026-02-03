<?php

declare(strict_types=1);

namespace App\Tests\Unit\Repository;

use App\Entity\Game;
use App\Entity\GamePlayer;
use App\Entity\User;
use App\Enum\PlayerStatus;
use App\Repository\GamePlayerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class GamePlayerRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private GamePlayerRepository $repository;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->repository = $this->entityManager->getRepository(GamePlayer::class);

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

    public function testFindPlayerInGame(): void
    {
        $user = $this->createUser('test@test.com', 'testuser');
        $game = $this->createGame('Test Game', $user);
        $gamePlayer = $this->createGamePlayer($game, $user);

        $found = $this->repository->findPlayerInGame($game, $user);

        $this->assertNotNull($found);
        $this->assertEquals($gamePlayer->getId(), $found->getId());
    }

    public function testFindPlayerInGameReturnsNullWhenNotFound(): void
    {
        $user = $this->createUser('test@test.com', 'testuser');
        $game = $this->createGame('Test Game', $user);

        $found = $this->repository->findPlayerInGame($game, $user);

        $this->assertNull($found);
    }

    public function testIsUserInGame(): void
    {
        $user = $this->createUser('test@test.com', 'testuser');
        $game = $this->createGame('Test Game', $user);
        $this->createGamePlayer($game, $user);

        $result = $this->repository->isUserInGame($game, $user);

        $this->assertTrue($result);
    }

    public function testIsUserInGameReturnsFalseWhenNotInGame(): void
    {
        $user = $this->createUser('test@test.com', 'testuser');
        $game = $this->createGame('Test Game', $user);

        $result = $this->repository->isUserInGame($game, $user);

        $this->assertFalse($result);
    }

    public function testFindActivePlayersInGame(): void
    {
        $user1 = $this->createUser('user1@test.com', 'user1');
        $user2 = $this->createUser('user2@test.com', 'user2');
        $user3 = $this->createUser('user3@test.com', 'user3');
        $game = $this->createGame('Test Game', $user1);

        $this->createGamePlayer($game, $user1, PlayerStatus::ACTIVE);
        $this->createGamePlayer($game, $user2, PlayerStatus::ACTIVE);
        $this->createGamePlayer($game, $user3, PlayerStatus::PENDING);

        $activePlayers = $this->repository->findActivePlayersInGame($game);

        $this->assertCount(2, $activePlayers);
    }

    public function testFindPendingInvitations(): void
    {
        $user1 = $this->createUser('user1@test.com', 'user1');
        $user2 = $this->createUser('user2@test.com', 'user2');
        $user3 = $this->createUser('user3@test.com', 'user3');
        $game = $this->createGame('Test Game', $user1);

        $this->createGamePlayer($game, $user1, PlayerStatus::ACTIVE);
        $this->createGamePlayer($game, $user2, PlayerStatus::PENDING);
        $this->createGamePlayer($game, $user3, PlayerStatus::PENDING);

        $pendingPlayers = $this->repository->findPendingInvitations($game);

        $this->assertCount(2, $pendingPlayers);
    }

    public function testCountTotalPlayers(): void
    {
        $user1 = $this->createUser('user1@test.com', 'user1');
        $user2 = $this->createUser('user2@test.com', 'user2');
        $user3 = $this->createUser('user3@test.com', 'user3');
        $game = $this->createGame('Test Game', $user1);

        $this->createGamePlayer($game, $user1, PlayerStatus::ACTIVE);
        $this->createGamePlayer($game, $user2, PlayerStatus::ACTIVE);
        $this->createGamePlayer($game, $user3, PlayerStatus::PENDING);

        $count = $this->repository->countTotalPlayers($game);

        $this->assertEquals(3, $count);
    }

    public function testFindParticipatingPlayers(): void
    {
        $user1 = $this->createUser('user1@test.com', 'user1');
        $user2 = $this->createUser('user2@test.com', 'user2');
        $user3 = $this->createUser('user3@test.com', 'user3');
        $user4 = $this->createUser('user4@test.com', 'user4');
        $game = $this->createGame('Test Game', $user1);

        $this->createGamePlayer($game, $user1, PlayerStatus::ACTIVE);
        $this->createGamePlayer($game, $user2, PlayerStatus::INACTIVE);
        $this->createGamePlayer($game, $user3, PlayerStatus::PENDING);
        $this->createGamePlayer($game, $user4, PlayerStatus::LEFT);

        $participants = $this->repository->findParticipatingPlayers($game);

        $this->assertCount(2, $participants);
    }

    public function testCountPlayersByStatus(): void
    {
        $user1 = $this->createUser('user1@test.com', 'user1');
        $user2 = $this->createUser('user2@test.com', 'user2');
        $user3 = $this->createUser('user3@test.com', 'user3');
        $user4 = $this->createUser('user4@test.com', 'user4');
        $game = $this->createGame('Test Game', $user1);

        $this->createGamePlayer($game, $user1, PlayerStatus::ACTIVE);
        $this->createGamePlayer($game, $user2, PlayerStatus::ACTIVE);
        $this->createGamePlayer($game, $user3, PlayerStatus::PENDING);
        $this->createGamePlayer($game, $user4, PlayerStatus::INACTIVE);

        $counts = $this->repository->countPlayersByStatus($game);

        $this->assertEquals(2, $counts[PlayerStatus::ACTIVE->value]);
        $this->assertEquals(1, $counts[PlayerStatus::PENDING->value]);
        $this->assertEquals(1, $counts[PlayerStatus::INACTIVE->value]);
    }

    public function testCanUserJoinGameReturnsTrueWhenSpaceAvailable(): void
    {
        $user1 = $this->createUser('user1@test.com', 'user1');
        $user2 = $this->createUser('user2@test.com', 'user2');
        $game = $this->createGame('Test Game', $user1);
        $game->setMaxPlayers(5);
        $this->entityManager->flush();

        $this->createGamePlayer($game, $user1, PlayerStatus::ACTIVE);

        $canJoin = $this->repository->canUserJoinGame($game, $user2);

        $this->assertTrue($canJoin);
    }

    public function testCanUserJoinGameReturnsFalseWhenUserAlreadyInGame(): void
    {
        $user = $this->createUser('test@test.com', 'testuser');
        $game = $this->createGame('Test Game', $user);

        $this->createGamePlayer($game, $user, PlayerStatus::ACTIVE);

        $canJoin = $this->repository->canUserJoinGame($game, $user);

        $this->assertFalse($canJoin);
    }

    public function testCanUserJoinGameReturnsFalseWhenGameIsFull(): void
    {
        $user1 = $this->createUser('user1@test.com', 'user1');
        $user2 = $this->createUser('user2@test.com', 'user2');
        $user3 = $this->createUser('user3@test.com', 'user3');
        $game = $this->createGame('Test Game', $user1);
        $game->setMaxPlayers(2);
        $this->entityManager->flush();

        $this->createGamePlayer($game, $user1, PlayerStatus::ACTIVE);
        $this->createGamePlayer($game, $user2, PlayerStatus::ACTIVE);

        $canJoin = $this->repository->canUserJoinGame($game, $user3);

        $this->assertFalse($canJoin);
    }

    public function testFindFormerPlayers(): void
    {
        $user1 = $this->createUser('user1@test.com', 'user1');
        $user2 = $this->createUser('user2@test.com', 'user2');
        $user3 = $this->createUser('user3@test.com', 'user3');
        $user4 = $this->createUser('user4@test.com', 'user4');
        $game = $this->createGame('Test Game', $user1);

        $this->createGamePlayer($game, $user1, PlayerStatus::ACTIVE);
        $this->createGamePlayer($game, $user2, PlayerStatus::LEFT);
        $this->createGamePlayer($game, $user3, PlayerStatus::KICKED);
        $this->createGamePlayer($game, $user4, PlayerStatus::PENDING);

        $formerPlayers = $this->repository->findFormerPlayers($game);

        $this->assertCount(2, $formerPlayers);
    }

    public function testFindUserGamesWithRole(): void
    {
        $user = $this->createUser('user@test.com', 'user');
        $gm = $this->createUser('gm@test.com', 'gamemaster');
        $game1 = $this->createGame('Game 1', $gm);
        $game2 = $this->createGame('Game 2', $gm);
        $game3 = $this->createGame('Game 3', $gm);

        $gp1 = $this->createGamePlayer($game1, $user);
        $gp1->setRole(\App\Enum\PlayerRole::GAME_MASTER);
        $this->entityManager->flush();

        $gp2 = $this->createGamePlayer($game2, $user);
        $gp2->setRole(\App\Enum\PlayerRole::PLAYER);
        $this->entityManager->flush();

        $gp3 = $this->createGamePlayer($game3, $user);
        $gp3->setRole(\App\Enum\PlayerRole::GAME_MASTER);
        $this->entityManager->flush();

        $gmGames = $this->repository->findUserGamesWithRole($user, \App\Enum\PlayerRole::GAME_MASTER);

        $this->assertCount(2, $gmGames);
    }

    public function testFindUserHostedGames(): void
    {
        $user = $this->createUser('user@test.com', 'user');
        $gm = $this->createUser('gm@test.com', 'gamemaster');
        $game1 = $this->createGame('Game 1', $gm);
        $game2 = $this->createGame('Game 2', $gm);
        $game3 = $this->createGame('Game 3', $gm);

        $gp1 = $this->createGamePlayer($game1, $user);
        $gp1->setRole(\App\Enum\PlayerRole::GAME_MASTER);
        $this->entityManager->flush();

        $gp2 = $this->createGamePlayer($game2, $user);
        $gp2->setRole(\App\Enum\PlayerRole::PLAYER);
        $this->entityManager->flush();

        $gp3 = $this->createGamePlayer($game3, $user);
        $gp3->setRole(\App\Enum\PlayerRole::GAME_MASTER);
        $this->entityManager->flush();

        $hostedGames = $this->repository->findUserHostedGames($user);

        $this->assertCount(2, $hostedGames);
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

    private function createGamePlayer(Game $game, User $user, PlayerStatus $status = PlayerStatus::ACTIVE): GamePlayer
    {
        $gamePlayer = new GamePlayer();
        $gamePlayer->setGame($game);
        $gamePlayer->setUser($user);
        $gamePlayer->setStatus($status);
        $this->entityManager->persist($gamePlayer);
        $this->entityManager->flush();

        return $gamePlayer;
    }
}
