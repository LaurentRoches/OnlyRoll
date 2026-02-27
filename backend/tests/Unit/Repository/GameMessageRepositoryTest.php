<?php

declare(strict_types=1);

namespace App\Tests\Unit\Repository;

use App\Entity\Game;
use App\Entity\GameMessage;
use App\Entity\User;
use App\Enum\GameStatus;
use App\Enum\MessageType;
use App\Repository\GameMessageRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class GameMessageRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private GameMessageRepository $repository;
    private Game $game;
    private User $user;
    private User $recipient;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = $kernel->getContainer()->get('doctrine')->getManager();
        $this->repository = $this->entityManager->getRepository(GameMessage::class);

        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
        $connection->executeStatement('TRUNCATE TABLE game_message');
        $connection->executeStatement('TRUNCATE TABLE game');
        $connection->executeStatement('TRUNCATE TABLE user');
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1');

        $this->user = new User();
        $this->user->setEmail('user@test.com');
        $this->user->setPseudo('user');
        $this->user->setPassword('password');
        $this->entityManager->persist($this->user);

        $this->recipient = new User();
        $this->recipient->setEmail('recipient@test.com');
        $this->recipient->setPseudo('recipient');
        $this->recipient->setPassword('password');
        $this->entityManager->persist($this->recipient);

        $this->game = new Game();
        $this->game->setName('Test Game');
        $this->game->setStatus(GameStatus::IN_PROGRESS);
        $this->game->setGameMaster($this->user);
        $this->entityManager->persist($this->game);

        $this->entityManager->flush();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }

    public function testFindRecentMessages(): void
    {
        for ($i = 0; $i < 60; $i++) {
            $message = new GameMessage();
            $message->setGame($this->game);
            $message->setUser($this->user);
            $message->setContent("Message $i");
            $message->setType(MessageType::CHAT);
            $this->entityManager->persist($message);
        }
        $this->entityManager->flush();

        $messages = $this->repository->findRecentMessages($this->game);

        $this->assertCount(50, $messages);

        foreach ($messages as $message) {
            $this->assertSame($this->game, $message->getGame());
            $this->assertEquals(MessageType::CHAT, $message->getType());
            $this->assertMatchesRegularExpression('/^Message \d+$/', $message->getContent());
        }
    }

    public function testFindMessagesByType(): void
    {
        $diceMessage = new GameMessage();
        $diceMessage->setGame($this->game);
        $diceMessage->setUser($this->user);
        $diceMessage->setContent('Dice roll');
        $diceMessage->setType(MessageType::DICE_ROLL);
        $this->entityManager->persist($diceMessage);

        $chatMessage = new GameMessage();
        $chatMessage->setGame($this->game);
        $chatMessage->setUser($this->user);
        $chatMessage->setContent('Chat message');
        $chatMessage->setType(MessageType::CHAT);
        $this->entityManager->persist($chatMessage);

        $this->entityManager->flush();

        $diceMessages = $this->repository->findMessagesByType($this->game, MessageType::DICE_ROLL);

        $this->assertCount(1, $diceMessages);
        $this->assertEquals('Dice roll', $diceMessages[0]->getContent());
    }

    public function testFindByType(): void
    {
        $systemMessage = new GameMessage();
        $systemMessage->setGame($this->game);
        $systemMessage->setUser($this->user);
        $systemMessage->setContent('System notification');
        $systemMessage->setType(MessageType::SYSTEM);
        $this->entityManager->persist($systemMessage);
        $this->entityManager->flush();

        $messages = $this->repository->findByType($this->game, MessageType::SYSTEM);

        $this->assertCount(1, $messages);
        $this->assertEquals('System notification', $messages[0]->getContent());
    }

    public function testFindWhispersForUser(): void
    {
        $whisperToUser = new GameMessage();
        $whisperToUser->setGame($this->game);
        $whisperToUser->setUser($this->recipient);
        $whisperToUser->setRecipient($this->user);
        $whisperToUser->setContent('Whisper to user');
        $whisperToUser->setType(MessageType::WHISPER);
        $this->entityManager->persist($whisperToUser);

        $whisperFromUser = new GameMessage();
        $whisperFromUser->setGame($this->game);
        $whisperFromUser->setUser($this->user);
        $whisperFromUser->setRecipient($this->recipient);
        $whisperFromUser->setContent('Whisper from user');
        $whisperFromUser->setType(MessageType::WHISPER);
        $this->entityManager->persist($whisperFromUser);

        $otherWhisper = new GameMessage();
        $otherWhisper->setGame($this->game);
        $otherWhisper->setUser($this->recipient);
        $otherWhisper->setContent('Other whisper');
        $otherWhisper->setType(MessageType::WHISPER);
        $this->entityManager->persist($otherWhisper);

        $this->entityManager->flush();

        $whispers = $this->repository->findWhispersForUser($this->game, $this->user);

        $this->assertCount(2, $whispers);
    }

    public function testFindDiceRolls(): void
    {
        $diceMessage = new GameMessage();
        $diceMessage->setGame($this->game);
        $diceMessage->setUser($this->user);
        $diceMessage->setContent('Rolled 1d20');
        $diceMessage->setType(MessageType::DICE_ROLL);
        $this->entityManager->persist($diceMessage);
        $this->entityManager->flush();

        $diceRolls = $this->repository->findDiceRolls($this->game);

        $this->assertCount(1, $diceRolls);
        $this->assertEquals('Rolled 1d20', $diceRolls[0]->getContent());
    }

    public function testFindVisibleMessagesForUser(): void
    {
        $publicMessage = new GameMessage();
        $publicMessage->setGame($this->game);
        $publicMessage->setUser($this->user);
        $publicMessage->setContent('Public');
        $publicMessage->setType(MessageType::CHAT);
        $this->entityManager->persist($publicMessage);

        $whisperToUser = new GameMessage();
        $whisperToUser->setGame($this->game);
        $whisperToUser->setUser($this->recipient);
        $whisperToUser->setRecipient($this->user);
        $whisperToUser->setContent('Whisper to user');
        $whisperToUser->setType(MessageType::WHISPER);
        $this->entityManager->persist($whisperToUser);

        $otherWhisper = new GameMessage();
        $otherWhisper->setGame($this->game);
        $otherWhisper->setUser($this->recipient);
        $otherWhisper->setContent('Other whisper');
        $otherWhisper->setType(MessageType::WHISPER);
        $this->entityManager->persist($otherWhisper);

        $this->entityManager->flush();

        $visibleMessages = $this->repository->findVisibleMessagesForUser($this->game, $this->user);

        $this->assertCount(2, $visibleMessages);
    }

    public function testFindVisibleForUser(): void
    {
        $message = new GameMessage();
        $message->setGame($this->game);
        $message->setUser($this->user);
        $message->setContent('Test');
        $message->setType(MessageType::CHAT);
        $this->entityManager->persist($message);
        $this->entityManager->flush();

        $messages = $this->repository->findVisibleForUser($this->game, $this->user);

        $this->assertCount(1, $messages);
    }

    public function testFindByCharacterMode(): void
    {
        $icMessage = new GameMessage();
        $icMessage->setGame($this->game);
        $icMessage->setUser($this->user);
        $icMessage->setContent('In character');
        $icMessage->setType(MessageType::CHAT);
        $icMessage->setIsInCharacter(true);
        $this->entityManager->persist($icMessage);

        $oocMessage = new GameMessage();
        $oocMessage->setGame($this->game);
        $oocMessage->setUser($this->user);
        $oocMessage->setContent('Out of character');
        $oocMessage->setType(MessageType::CHAT);
        $oocMessage->setIsInCharacter(false);
        $this->entityManager->persist($oocMessage);

        $this->entityManager->flush();

        $icMessages = $this->repository->findByCharacterMode($this->game, true);
        $oocMessages = $this->repository->findByCharacterMode($this->game, false);

        $this->assertCount(1, $icMessages);
        $this->assertCount(1, $oocMessages);
        $this->assertEquals('In character', $icMessages[0]->getContent());
        $this->assertEquals('Out of character', $oocMessages[0]->getContent());
    }

    public function testFindMessagesSince(): void
    {
        $now = new DateTimeImmutable();

        $recentMessage = new GameMessage();
        $recentMessage->setGame($this->game);
        $recentMessage->setUser($this->user);
        $recentMessage->setContent('Recent');
        $recentMessage->setType(MessageType::CHAT);
        $this->entityManager->persist($recentMessage);
        $this->entityManager->flush();

        $messages = $this->repository->findMessagesSince($this->game, $now->modify('-1 minute'));

        $this->assertCount(1, $messages);
        $this->assertEquals('Recent', $messages[0]->getContent());
    }

    public function testCountMessagesByType(): void
    {
        $chatMessage1 = new GameMessage();
        $chatMessage1->setGame($this->game);
        $chatMessage1->setUser($this->user);
        $chatMessage1->setContent('Chat 1');
        $chatMessage1->setType(MessageType::CHAT);
        $this->entityManager->persist($chatMessage1);

        $chatMessage2 = new GameMessage();
        $chatMessage2->setGame($this->game);
        $chatMessage2->setUser($this->user);
        $chatMessage2->setContent('Chat 2');
        $chatMessage2->setType(MessageType::CHAT);
        $this->entityManager->persist($chatMessage2);

        $diceMessage = new GameMessage();
        $diceMessage->setGame($this->game);
        $diceMessage->setUser($this->user);
        $diceMessage->setContent('Dice');
        $diceMessage->setType(MessageType::DICE_ROLL);
        $this->entityManager->persist($diceMessage);

        $this->entityManager->flush();

        $counts = $this->repository->countMessagesByType($this->game);

        $this->assertEquals(2, $counts['chat']);
        $this->assertEquals(1, $counts['dice_roll']);
    }

    public function testCountMessagesByGame(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $message = new GameMessage();
            $message->setGame($this->game);
            $message->setUser($this->user);
            $message->setContent("Message $i");
            $message->setType(MessageType::CHAT);
            $this->entityManager->persist($message);
        }
        $this->entityManager->flush();

        $count = $this->repository->countMessagesByGame($this->game);

        $this->assertEquals(5, $count);
    }

    public function testGetStatsByGame(): void
    {
        $message = new GameMessage();
        $message->setGame($this->game);
        $message->setUser($this->user);
        $message->setContent('Test');
        $message->setType(MessageType::CHAT);
        $this->entityManager->persist($message);
        $this->entityManager->flush();

        $stats = $this->repository->getStatsByGame($this->game);

        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('byType', $stats);
        $this->assertEquals(1, $stats['total']);
        $this->assertIsArray($stats['byType']);
    }

    public function testDeleteOldMessages(): void
    {
        $now = new DateTimeImmutable();

        $message = new GameMessage();
        $message->setGame($this->game);
        $message->setUser($this->user);
        $message->setContent('Test');
        $message->setType(MessageType::CHAT);
        $this->entityManager->persist($message);
        $this->entityManager->flush();

        $deleted = $this->repository->deleteOldMessages($this->game, $now->modify('+1 day'));

        $this->assertEquals(1, $deleted);
        $this->assertEquals(0, $this->repository->countMessagesByGame($this->game));
    }

    public function testDeleteOlderThan(): void
    {
        $now = new DateTimeImmutable();

        $message = new GameMessage();
        $message->setGame($this->game);
        $message->setUser($this->user);
        $message->setContent('Test');
        $message->setType(MessageType::CHAT);
        $this->entityManager->persist($message);
        $this->entityManager->flush();

        $deleted = $this->repository->deleteOlderThan($this->game, $now->modify('+1 day'));

        $this->assertEquals(1, $deleted);
    }

    public function testFindByUserInGame(): void
    {
        $userMessage = new GameMessage();
        $userMessage->setGame($this->game);
        $userMessage->setUser($this->user);
        $userMessage->setContent('User message');
        $userMessage->setType(MessageType::CHAT);
        $this->entityManager->persist($userMessage);

        $otherMessage = new GameMessage();
        $otherMessage->setGame($this->game);
        $otherMessage->setUser($this->recipient);
        $otherMessage->setContent('Other message');
        $otherMessage->setType(MessageType::CHAT);
        $this->entityManager->persist($otherMessage);

        $this->entityManager->flush();

        $messages = $this->repository->findByUserInGame($this->game, $this->user);

        $this->assertCount(1, $messages);
        $this->assertEquals('User message', $messages[0]->getContent());
    }

    public function testFindSince(): void
    {
        $now = new DateTimeImmutable();

        $message = new GameMessage();
        $message->setGame($this->game);
        $message->setUser($this->user);
        $message->setContent('Recent');
        $message->setType(MessageType::CHAT);
        $this->entityManager->persist($message);
        $this->entityManager->flush();

        $messages = $this->repository->findSince($this->game, $now->modify('-1 minute'));

        $this->assertCount(1, $messages);
    }

    public function testFindVisibleSince(): void
    {
        $now = new DateTimeImmutable();

        $publicMessage = new GameMessage();
        $publicMessage->setGame($this->game);
        $publicMessage->setUser($this->user);
        $publicMessage->setContent('Public');
        $publicMessage->setType(MessageType::CHAT);
        $this->entityManager->persist($publicMessage);

        $whisper = new GameMessage();
        $whisper->setGame($this->game);
        $whisper->setUser($this->recipient);
        $whisper->setContent('Whisper');
        $whisper->setType(MessageType::WHISPER);
        $this->entityManager->persist($whisper);

        $this->entityManager->flush();

        $messages = $this->repository->findVisibleSince($this->game, $now->modify('-1 minute'), $this->user);

        $this->assertCount(1, $messages);
        $this->assertEquals('Public', $messages[0]->getContent());
    }
}
