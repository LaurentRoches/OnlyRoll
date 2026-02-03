<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Game;
use App\Entity\GameMessage;
use App\Entity\GamePlayer;
use App\Entity\User;
use App\Enum\MessageType;
use App\Enum\PlayerStatus;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ChatControllerTest extends WebTestCase
{
    private $client;

    private EntityManagerInterface $entityManager;

    private User $gameMaster;

    private User $player;

    private Game $game;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()->get('doctrine')->getManager();

        $this->cleanDatabase();

        $this->gameMaster = new User();
        $this->gameMaster->setPseudo('gamemaster');
        $this->gameMaster->setEmail('gm@test.com');
        $this->gameMaster->setPassword('password');

        $this->player = new User();
        $this->player->setPseudo('player1');
        $this->player->setEmail('player@test.com');
        $this->player->setPassword('password');

        $this->entityManager->persist($this->gameMaster);
        $this->entityManager->persist($this->player);

        $this->game = new Game();
        $this->game->setName('Test Game');
        $this->game->setGameMaster($this->gameMaster);
        $this->game->setIsPublic(false);
        $this->game->setMaxPlayers(6);

        $gamePlayer = new GamePlayer();
        $gamePlayer->setGame($this->game);
        $gamePlayer->setUser($this->player);
        $gamePlayer->setStatus(PlayerStatus::ACTIVE);

        $this->game->addGamePlayer($gamePlayer);
        $this->entityManager->persist($this->game);
        $this->entityManager->flush();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }

    private function cleanDatabase(): void
    {
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 0');

        $tables = ['game_message', 'game_player', 'game_token', 'game_map', 'game', 'user'];
        foreach ($tables as $table) {
            $connection->executeStatement("TRUNCATE TABLE $table");
        }

        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
    }

    public function testGetMessagesWithoutAuthentication(): void
    {
        $this->client->request('GET', '/api/games/' . $this->game->getId() . '/chat/messages');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testGetMessagesForNonExistentGame(): void
    {
        $this->client->loginUser($this->player);
        $this->client->request('GET', '/api/games/99999/chat/messages');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testGetMessagesWithoutPermission(): void
    {
        $outsider = new User();
        $outsider->setPseudo('outsider');
        $outsider->setEmail('outsider@test.com');
        $outsider->setPassword('password');
        $this->entityManager->persist($outsider);
        $this->entityManager->flush();

        $this->client->loginUser($outsider);
        $this->client->request('GET', '/api/games/' . $this->game->getId() . '/chat/messages');

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testGetMessagesSuccess(): void
    {
        for ($i = 0; $i < 3; ++$i) {
            $message = new GameMessage();
            $message->setGame($this->game);
            $message->setUser($this->player);
            $message->setType(MessageType::CHAT);
            $message->setContent("Test message $i");
            $this->entityManager->persist($message);
        }
        $this->entityManager->flush();

        $this->client->loginUser($this->player);
        $this->client->request('GET', '/api/games/' . $this->game->getId() . '/chat/messages');

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(3, $data);
    }

    public function testGetMessagesWithLimit(): void
    {
        for ($i = 0; $i < 10; ++$i) {
            $message = new GameMessage();
            $message->setGame($this->game);
            $message->setUser($this->player);
            $message->setType(MessageType::CHAT);
            $message->setContent("Test message $i");
            $this->entityManager->persist($message);
        }
        $this->entityManager->flush();

        $this->client->loginUser($this->player);
        $this->client->request('GET', '/api/games/' . $this->game->getId() . '/chat/messages?limit=5');

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(5, $data);
    }

    public function testSendMessageSuccess(): void
    {
        $this->client->loginUser($this->player);
        $this->client->request(
            'POST',
            '/api/games/' . $this->game->getId() . '/chat/messages',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'type' => MessageType::CHAT->value,
                'content' => 'Hello everyone!',
                'isInCharacter' => false,
            ]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Hello everyone!', $data['content']);
    }

    public function testSendMessageWithInvalidData(): void
    {
        $this->client->loginUser($this->player);
        $this->client->request(
            'POST',
            '/api/games/' . $this->game->getId() . '/chat/messages',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'type' => 'invalid_type',
                'content' => '',
            ]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testGetMessagesByType(): void
    {
        $chatMessage = new GameMessage();
        $chatMessage->setGame($this->game);
        $chatMessage->setUser($this->player);
        $chatMessage->setType(MessageType::CHAT);
        $chatMessage->setContent('Chat message');

        $systemMessage = new GameMessage();
        $systemMessage->setGame($this->game);
        $systemMessage->setUser($this->player);
        $systemMessage->setType(MessageType::SYSTEM);
        $systemMessage->setContent('System message');

        $this->entityManager->persist($chatMessage);
        $this->entityManager->persist($systemMessage);
        $this->entityManager->flush();

        $this->client->loginUser($this->player);
        $this->client->request(
            'GET',
            '/api/games/' . $this->game->getId() . '/chat/messages/type/' . MessageType::CHAT->value,
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(MessageType::CHAT->value, $data[0]['type']);
    }

    public function testRollDiceSuccess(): void
    {
        $this->client->loginUser($this->player);
        $this->client->request(
            'POST',
            '/api/games/' . $this->game->getId() . '/chat/roll-dice',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['formula' => '2d6+3']),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(MessageType::DICE_ROLL->value, $data['type']);
        $this->assertArrayHasKey('diceResult', $data);
        $this->assertArrayHasKey('total', $data['diceResult']);
    }

    public function testRollDiceWithInvalidFormula(): void
    {
        $this->client->loginUser($this->player);
        $this->client->request(
            'POST',
            '/api/games/' . $this->game->getId() . '/chat/roll-dice',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['formula' => 'invalid']),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testRollDiceWithoutFormula(): void
    {
        $this->client->loginUser($this->player);
        $this->client->request(
            'POST',
            '/api/games/' . $this->game->getId() . '/chat/roll-dice',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testRollDiceWithTooManyDice(): void
    {
        $this->client->loginUser($this->player);
        $this->client->request(
            'POST',
            '/api/games/' . $this->game->getId() . '/chat/roll-dice',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['formula' => '101d6']),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testGetStatsAsGameMaster(): void
    {
        $message = new GameMessage();
        $message->setGame($this->game);
        $message->setUser($this->player);
        $message->setType(MessageType::CHAT);
        $message->setContent('Test');
        $this->entityManager->persist($message);
        $this->entityManager->flush();

        $this->client->loginUser($this->gameMaster);
        $this->client->request('GET', '/api/games/' . $this->game->getId() . '/chat/stats');

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('stats', $data);
        $this->assertArrayHasKey('total', $data);
    }

    public function testGetStatsAsPlayerForbidden(): void
    {
        $this->client->loginUser($this->player);
        $this->client->request('GET', '/api/games/' . $this->game->getId() . '/chat/stats');

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testGetMessagesSinceSuccess(): void
    {
        $oldMessage = new GameMessage();
        $oldMessage->setGame($this->game);
        $oldMessage->setUser($this->player);
        $oldMessage->setType(MessageType::CHAT);
        $oldMessage->setContent('Old message');
        $this->entityManager->persist($oldMessage);
        $this->entityManager->flush();

        $since = new DateTimeImmutable();
        sleep(1);

        $newMessage = new GameMessage();
        $newMessage->setGame($this->game);
        $newMessage->setUser($this->player);
        $newMessage->setType(MessageType::CHAT);
        $newMessage->setContent('New message');
        $this->entityManager->persist($newMessage);
        $this->entityManager->flush();

        $this->client->loginUser($this->player);
        $this->client->request(
            'GET',
            '/api/games/' . $this->game->getId() . '/chat/messages/since?since=' . urlencode($since->format('c')),
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertGreaterThanOrEqual(1, \count($data));
    }

    public function testGetMessagesSinceWithoutParameter(): void
    {
        $this->client->loginUser($this->player);
        $this->client->request('GET', '/api/games/' . $this->game->getId() . '/chat/messages/since');

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testGetMessagesSinceWithInvalidDate(): void
    {
        $this->client->loginUser($this->player);
        $this->client->request(
            'GET',
            '/api/games/' . $this->game->getId() . '/chat/messages/since?since=invalid-date',
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testGetDiceRolls(): void
    {
        $diceMessage = new GameMessage();
        $diceMessage->setGame($this->game);
        $diceMessage->setUser($this->player);
        $diceMessage->setType(MessageType::DICE_ROLL);
        $diceMessage->setContent('2d6+3');
        $diceMessage->setDiceResult(['total' => 10, 'rolls' => [3, 4], 'modifier' => 3]);

        $chatMessage = new GameMessage();
        $chatMessage->setGame($this->game);
        $chatMessage->setUser($this->player);
        $chatMessage->setType(MessageType::CHAT);
        $chatMessage->setContent('Chat');

        $this->entityManager->persist($diceMessage);
        $this->entityManager->persist($chatMessage);
        $this->entityManager->flush();

        $this->client->loginUser($this->player);
        $this->client->request('GET', '/api/games/' . $this->game->getId() . '/chat/dice-rolls');

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(1, $data);
        $this->assertEquals(MessageType::DICE_ROLL->value, $data[0]['type']);
    }

    public function testGetDiceRollsForNonExistentGame(): void
    {
        $this->client->loginUser($this->player);
        $this->client->request('GET', '/api/games/99999/chat/dice-rolls');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testGetDiceRollsWithoutPermission(): void
    {
        $outsider = new User();
        $outsider->setPseudo('outsider');
        $outsider->setEmail('outsider2@test.com');
        $outsider->setPassword('password');
        $this->entityManager->persist($outsider);
        $this->entityManager->flush();

        $this->client->loginUser($outsider);
        $this->client->request('GET', '/api/games/' . $this->game->getId() . '/chat/dice-rolls');

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testGetDiceRollsWithCustomLimit(): void
    {
        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('DELETE FROM game_message WHERE game_id = :gameId', [
            'gameId' => $this->game->getId()
        ]);

        for ($i = 0; $i < 5; ++$i) {
            $message = new GameMessage();
            $message->setGame($this->game);
            $message->setUser($this->player);
            $message->setType(MessageType::DICE_ROLL);
            $message->setContent("1d20");
            $message->setDiceResult(['total' => 15, 'rolls' => [15]]);
            $this->entityManager->persist($message);
        }
        $this->entityManager->flush();

        $this->client->loginUser($this->player);
        $this->client->request('GET', '/api/games/' . $this->game->getId() . '/chat/dice-rolls?limit=3');

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(3, $data);
    }

    public function testSendMessageForNonExistentGame(): void
    {
        $this->client->loginUser($this->player);
        $this->client->request(
            'POST',
            '/api/games/99999/chat/messages',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['type' => MessageType::CHAT->value, 'content' => 'Test']),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testSendMessageWithoutPermission(): void
    {
        $outsider = new User();
        $outsider->setPseudo('outsider3');
        $outsider->setEmail('outsider3@test.com');
        $outsider->setPassword('password');
        $this->entityManager->persist($outsider);
        $this->entityManager->flush();

        $this->client->loginUser($outsider);
        $this->client->request(
            'POST',
            '/api/games/' . $this->game->getId() . '/chat/messages',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['type' => MessageType::CHAT->value, 'content' => 'Test']),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testSendMessageWithMalformedJson(): void
    {
        $this->client->loginUser($this->player);
        $this->client->request(
            'POST',
            '/api/games/' . $this->game->getId() . '/chat/messages',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            'invalid-json',
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testGetMessagesByTypeForNonExistentGame(): void
    {
        $this->client->loginUser($this->player);
        $this->client->request('GET', '/api/games/99999/chat/messages/type/' . MessageType::CHAT->value);

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testGetMessagesByTypeWithoutPermission(): void
    {
        $outsider = new User();
        $outsider->setPseudo('outsider4');
        $outsider->setEmail('outsider4@test.com');
        $outsider->setPassword('password');
        $this->entityManager->persist($outsider);
        $this->entityManager->flush();

        $this->client->loginUser($outsider);
        $this->client->request('GET', '/api/games/' . $this->game->getId() . '/chat/messages/type/' . MessageType::CHAT->value);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testGetMessagesByTypeWithInvalidType(): void
    {
        $this->client->loginUser($this->player);
        $this->client->request('GET', '/api/games/' . $this->game->getId() . '/chat/messages/type/invalid_type');

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testRollDiceForNonExistentGame(): void
    {
        $this->client->loginUser($this->player);
        $this->client->request(
            'POST',
            '/api/games/99999/chat/roll-dice',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['formula' => '2d6']),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testRollDiceWithoutPermission(): void
    {
        $outsider = new User();
        $outsider->setPseudo('outsider5');
        $outsider->setEmail('outsider5@test.com');
        $outsider->setPassword('password');
        $this->entityManager->persist($outsider);
        $this->entityManager->flush();

        $this->client->loginUser($outsider);
        $this->client->request(
            'POST',
            '/api/games/' . $this->game->getId() . '/chat/roll-dice',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['formula' => '2d6']),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testRollDiceWithTooFewSides(): void
    {
        $this->client->loginUser($this->player);
        $this->client->request(
            'POST',
            '/api/games/' . $this->game->getId() . '/chat/roll-dice',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['formula' => '1d1']),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testRollDiceWithTooManySides(): void
    {
        $this->client->loginUser($this->player);
        $this->client->request(
            'POST',
            '/api/games/' . $this->game->getId() . '/chat/roll-dice',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['formula' => '1d1001']),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testRollDiceWithNonExistentRecipient(): void
    {
        $this->client->loginUser($this->player);
        $this->client->request(
            'POST',
            '/api/games/' . $this->game->getId() . '/chat/roll-dice',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['formula' => '2d6', 'recipientId' => 99999]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testRollDiceWithRecipientNotInGame(): void
    {
        $nonPlayer = new User();
        $nonPlayer->setPseudo('nonplayer');
        $nonPlayer->setEmail('nonplayer@test.com');
        $nonPlayer->setPassword('password');
        $this->entityManager->persist($nonPlayer);
        $this->entityManager->flush();

        $this->client->loginUser($this->player);
        $this->client->request(
            'POST',
            '/api/games/' . $this->game->getId() . '/chat/roll-dice',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['formula' => '2d6', 'recipientId' => $nonPlayer->getId()]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testRollDiceWithValidRecipient(): void
    {
        $player2 = new User();
        $player2->setPseudo('player2');
        $player2->setEmail('player2@test.com');
        $player2->setPassword('password');
        $this->entityManager->persist($player2);

        $gamePlayer2 = new GamePlayer();
        $gamePlayer2->setGame($this->game);
        $gamePlayer2->setUser($player2);
        $gamePlayer2->setStatus(PlayerStatus::ACTIVE);
        $this->game->addGamePlayer($gamePlayer2);
        $this->entityManager->flush();

        $this->client->loginUser($this->player);
        $this->client->request(
            'POST',
            '/api/games/' . $this->game->getId() . '/chat/roll-dice',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['formula' => '1d20', 'recipientId' => $player2->getId()]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(MessageType::DICE_ROLL->value, $data['type']);
    }

    public function testGetStatsForNonExistentGame(): void
    {
        $this->client->loginUser($this->gameMaster);
        $this->client->request('GET', '/api/games/99999/chat/stats');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testGetMessagesSinceForNonExistentGame(): void
    {
        $this->client->loginUser($this->player);
        $since = new DateTimeImmutable();
        $this->client->request(
            'GET',
            '/api/games/99999/chat/messages/since?since=' . urlencode($since->format('c')),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testGetMessagesSinceWithoutPermission(): void
    {
        $outsider = new User();
        $outsider->setPseudo('outsider6');
        $outsider->setEmail('outsider6@test.com');
        $outsider->setPassword('password');
        $this->entityManager->persist($outsider);
        $this->entityManager->flush();

        $this->client->loginUser($outsider);
        $since = new DateTimeImmutable();
        $this->client->request(
            'GET',
            '/api/games/' . $this->game->getId() . '/chat/messages/since?since=' . urlencode($since->format('c')),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testGetMessagesWithLimitClampingMin(): void
    {
        $this->client->loginUser($this->player);
        $this->client->request('GET', '/api/games/' . $this->game->getId() . '/chat/messages?limit=0');

        $this->assertResponseIsSuccessful();
    }

    public function testGetMessagesWithLimitClampingMax(): void
    {
        $this->client->loginUser($this->player);
        $this->client->request('GET', '/api/games/' . $this->game->getId() . '/chat/messages?limit=300');

        $this->assertResponseIsSuccessful();
    }
}
