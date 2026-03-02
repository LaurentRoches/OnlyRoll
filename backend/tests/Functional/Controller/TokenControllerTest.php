<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Game;
use App\Entity\GameMap;
use App\Entity\GamePlayer;
use App\Entity\GameToken;
use App\Entity\User;
use App\Enum\PlayerStatus;
use App\Enum\TokenType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class TokenControllerTest extends WebTestCase
{
    private $client;

    private EntityManagerInterface $entityManager;

    private User $gameMaster;

    private User $player;

    private Game $game;

    private GameMap $map;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = $this->client->getContainer()->get('doctrine')->getManager();

        $connection = $this->entityManager->getConnection();
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=0');

        $connection->executeStatement('TRUNCATE TABLE game_token');
        $connection->executeStatement('TRUNCATE TABLE game_player');
        $connection->executeStatement('TRUNCATE TABLE game_map');
        $connection->executeStatement('TRUNCATE TABLE game');
        $connection->executeStatement('TRUNCATE TABLE user');

        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=1');
        $this->entityManager->clear();

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

        $gmPlayer = new GamePlayer();
        $gmPlayer->setGame($this->game);
        $gmPlayer->setUser($this->gameMaster);
        $gmPlayer->setStatus(PlayerStatus::ACTIVE);
        $this->game->addGamePlayer($gmPlayer);

        $gamePlayer = new GamePlayer();
        $gamePlayer->setGame($this->game);
        $gamePlayer->setUser($this->player);
        $gamePlayer->setStatus(PlayerStatus::ACTIVE);

        $this->game->addGamePlayer($gamePlayer);

        $this->map = new GameMap();
        $this->map->setGame($this->game);
        $this->map->setName('Test Map');
        $this->map->setWidth(20);
        $this->map->setHeight(20);
        $this->map->setIsActive(true);

        $this->entityManager->persist($this->game);
        $this->entityManager->persist($this->map);
        $this->entityManager->flush();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }

    public function testListTokensWithoutAuthentication(): void
    {
        $this->client->request('GET', $this->getTokenUrl());
        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testListTokensForNonExistentGame(): void
    {
        $this->client->loginUser($this->player);
        $this->client->request('GET', '/api/games/99999/maps/' . $this->map->getId() . '/tokens');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testListTokensSuccess(): void
    {
        $token1 = $this->createToken('Token 1', true);
        $token2 = $this->createToken('Token 2', true);

        $this->client->loginUser($this->player);
        $this->client->request('GET', $this->getTokenUrl());

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(2, $data);
    }

    public function testListTokensPlayerSeesOnlyVisible(): void
    {
        $visibleToken = $this->createToken('Visible', true);
        $hiddenToken = $this->createToken('Hidden', false);

        $this->client->loginUser($this->player);
        $this->client->request('GET', $this->getTokenUrl());

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(1, $data);
        $this->assertEquals('Visible', $data[0]['name']);
    }

    public function testListTokensGameMasterSeesAll(): void
    {
        $visibleToken = $this->createToken('Visible', true);
        $hiddenToken = $this->createToken('Hidden', false);

        $this->client->loginUser($this->gameMaster);
        $this->client->request('GET', $this->getTokenUrl());

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(2, $data);
    }

    public function testShowTokenSuccess(): void
    {
        $token = $this->createToken('Test Token', true);

        $this->client->loginUser($this->player);
        $this->client->request('GET', $this->getTokenUrl($token->getId()));

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Test Token', $data['name']);
    }

    public function testShowHiddenTokenAsPlayerForbidden(): void
    {
        $token = $this->createToken('Hidden Token', false);

        $this->client->loginUser($this->player);
        $this->client->request('GET', $this->getTokenUrl($token->getId()));

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testShowHiddenTokenAsGameMasterSuccess(): void
    {
        $token = $this->createToken('Hidden Token', false);

        $this->client->loginUser($this->gameMaster);
        $this->client->request('GET', $this->getTokenUrl($token->getId()));

        $this->assertResponseIsSuccessful();
    }

    public function testCreateTokenAsPlayerForbidden(): void
    {
        $this->client->loginUser($this->player);
        $this->client->request(
            'POST',
            $this->getTokenUrl(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'New Token',
                'type' => 'character',
                'x' => 5,
                'y' => 5,
            ]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testCreateTokenAsGameMasterSuccess(): void
    {
        $this->client->loginUser($this->gameMaster);
        $this->client->request(
            'POST',
            $this->getTokenUrl(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'New Token',
                'type' => 'character',
                'x' => 5,
                'y' => 10,
                'size' => 1.0,
                'isVisible' => true,
            ]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('New Token', $data['name']);
        $this->assertEquals(5, $data['x']);
        $this->assertEquals(10, $data['y']);
    }

    public function testCreateTokenWithInvalidData(): void
    {
        $this->client->loginUser($this->gameMaster);
        $this->client->request(
            'POST',
            $this->getTokenUrl(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => '',
                'type' => 'invalid_type',
                'x' => -1,
            ]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testMoveTokenSuccess(): void
    {
        $token = $this->createToken('Movable Token', true);

        $this->client->loginUser($this->gameMaster);
        $this->client->request(
            'POST',
            $this->getTokenUrl($token->getId()) . '/move',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['x' => 15, 'y' => 20]),
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(15, $data['x']);
        $this->assertEquals(20, $data['y']);
    }

    public function testMoveLockedTokenFails(): void
    {
        $token = $this->createToken('Locked Token', true, 5, 5, true);

        $this->client->loginUser($this->player);
        $this->client->request(
            'POST',
            $this->getTokenUrl($token->getId()) . '/move',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['x' => 15, 'y' => 20]),
        );

        $this->entityManager->refresh($token);
        $this->assertEquals(5, $token->getX());
        $this->assertEquals(5, $token->getY());
    }

    public function testToggleVisibilityAsGameMasterSuccess(): void
    {
        $token = $this->createToken('Test Token', true);

        $this->client->loginUser($this->gameMaster);
        $this->client->request('POST', $this->getTokenUrl($token->getId()) . '/toggle-visibility');

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertFalse($data['isVisible']);
    }

    public function testToggleVisibilityAsPlayerForbidden(): void
    {
        $token = $this->createToken('Test Token', true);

        $this->client->loginUser($this->player);
        $this->client->request('POST', $this->getTokenUrl($token->getId()) . '/toggle-visibility');

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testToggleLockAsGameMasterSuccess(): void
    {
        $token = $this->createToken('Test Token', true);

        $this->client->loginUser($this->gameMaster);
        $this->client->request('POST', $this->getTokenUrl($token->getId()) . '/toggle-lock');

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($data['isLocked']);
    }

    public function testToggleLockAsPlayerForbidden(): void
    {
        $token = $this->createToken('Test Token', true);

        $this->client->loginUser($this->player);
        $this->client->request('POST', $this->getTokenUrl($token->getId()) . '/toggle-lock');

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testDeleteTokenAsGameMasterSuccess(): void
    {
        $token = $this->createToken('Token to Delete', true);
        $tokenId = $token->getId();

        $this->client->loginUser($this->gameMaster);
        $this->client->request('DELETE', $this->getTokenUrl($tokenId));

        $this->assertResponseIsSuccessful();

        $deletedToken = $this->entityManager->getRepository(GameToken::class)->find($tokenId);
        $this->assertNull($deletedToken);
    }

    public function testDeleteTokenAsPlayerForbidden(): void
    {
        $token = $this->createToken('Test Token', true);

        $this->client->loginUser($this->player);
        $this->client->request('DELETE', $this->getTokenUrl($token->getId()));

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testTokenBelongsToCorrectMap(): void
    {
        $otherMap = new GameMap();
        $otherMap->setGame($this->game);
        $otherMap->setName('Other Map');
        $otherMap->setWidth(20);
        $otherMap->setHeight(20);
        $this->entityManager->persist($otherMap);

        $token = new GameToken();
        $token->setMap($otherMap);
        $token->setName('Other Map Token');
        $token->setType(TokenType::CHARACTER);
        $token->setX(5);
        $token->setY(5);
        $this->entityManager->persist($token);
        $this->entityManager->flush();

        $this->client->loginUser($this->gameMaster);
        $this->client->request('GET', $this->getTokenUrl($token->getId()));

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    private function getTokenUrl(?int $tokenId = null): string
    {
        $base = '/api/games/' . $this->game->getId() . '/maps/' . $this->map->getId() . '/tokens';

        return $tokenId ? $base . '/' . $tokenId : $base;
    }

    public function testUpdateTokenAsGameMasterSuccess(): void
    {
        $token = $this->createToken('Original Name', true);

        $this->client->loginUser($this->gameMaster);
        $this->client->request(
            'PUT',
            $this->getTokenUrl($token->getId()),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'Updated Name',
                'size' => 2.0,
            ]),
        );

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Updated Name', $data['name']);
        $this->assertEquals(2.0, $data['size']);
    }

    public function testUpdateTokenAsPlayerForbidden(): void
    {
        $token = $this->createToken('Test Token', true);

        $this->client->loginUser($this->player);
        $this->client->request(
            'PUT',
            $this->getTokenUrl($token->getId()),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['name' => 'Hacked Name']),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testUpdateTokenNotFound(): void
    {
        $this->client->loginUser($this->gameMaster);
        $this->client->request(
            'PUT',
            $this->getTokenUrl(99999),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['name' => 'New Name']),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testManagePermissionsAsGameMasterSuccess(): void
    {
        $token = $this->createToken('Test Token', true);

        $this->client->loginUser($this->gameMaster);
        $this->client->request(
            'POST',
            $this->getTokenUrl($token->getId()) . '/permissions',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'action' => 'add',
                'userId' => $this->player->getId(),
            ]),
        );

        $this->assertResponseIsSuccessful();
    }

    public function testManagePermissionsAsPlayerForbidden(): void
    {
        $token = $this->createToken('Test Token', true);

        $this->client->loginUser($this->player);
        $this->client->request(
            'POST',
            $this->getTokenUrl($token->getId()) . '/permissions',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'controllableBy' => [$this->player->getId()],
            ]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testShowTokenNotFound(): void
    {
        $this->client->loginUser($this->gameMaster);
        $this->client->request('GET', $this->getTokenUrl(99999));
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testMoveTokenNotFound(): void
    {
        $this->client->loginUser($this->gameMaster);
        $this->client->request(
            'POST',
            $this->getTokenUrl(99999) . '/move',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['x' => 15, 'y' => 20]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testToggleVisibilityNotFound(): void
    {
        $this->client->loginUser($this->gameMaster);
        $this->client->request('POST', $this->getTokenUrl(99999) . '/toggle-visibility');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testToggleLockNotFound(): void
    {
        $this->client->loginUser($this->gameMaster);
        $this->client->request('POST', $this->getTokenUrl(99999) . '/toggle-lock');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testDeleteTokenNotFound(): void
    {
        $this->client->loginUser($this->gameMaster);
        $this->client->request('DELETE', $this->getTokenUrl(99999));
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testListTokensForNonExistentMap(): void
    {
        $this->client->loginUser($this->player);
        $this->client->request('GET', '/api/games/' . $this->game->getId() . '/maps/99999/tokens');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testCreateTokenForNonExistentMap(): void
    {
        $this->client->loginUser($this->gameMaster);
        $this->client->request(
            'POST',
            '/api/games/' . $this->game->getId() . '/maps/99999/tokens',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'New Token',
                'type' => 'character',
                'x' => 5,
                'y' => 5,
            ]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testListTokensForbiddenForNonParticipant(): void
    {
        $otherUser = new User();
        $otherUser->setPseudo('other');
        $otherUser->setEmail('other@test.com');
        $otherUser->setPassword('password');
        $this->entityManager->persist($otherUser);
        $this->entityManager->flush();

        $this->client->loginUser($otherUser);
        $this->client->request('GET', $this->getTokenUrl());
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testShowTokenForbiddenForNonParticipant(): void
    {
        $token = $this->createToken('Test Token', true);

        $otherUser = new User();
        $otherUser->setPseudo('other');
        $otherUser->setEmail('other@test.com');
        $otherUser->setPassword('password');
        $this->entityManager->persist($otherUser);
        $this->entityManager->flush();

        $this->client->loginUser($otherUser);
        $this->client->request('GET', $this->getTokenUrl($token->getId()));
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testMoveTokenWithInvalidData(): void
    {
        $token = $this->createToken('Test Token', true);

        $this->client->loginUser($this->gameMaster);
        $this->client->request(
            'POST',
            $this->getTokenUrl($token->getId()) . '/move',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['x' => -1, 'y' => -1]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testManagePermissionsNotFound(): void
    {
        $this->client->loginUser($this->gameMaster);
        $this->client->request(
            'POST',
            $this->getTokenUrl(99999) . '/permissions',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['controllableBy' => []]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testManagePermissionsWithInvalidAction(): void
    {
        $token = $this->createToken('Test Token', true);

        $this->client->loginUser($this->gameMaster);
        $this->client->request(
            'POST',
            $this->getTokenUrl($token->getId()) . '/permissions',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'action' => 'invalid',
                'userId' => $this->player->getId(),
            ]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testManagePermissionsWithMissingUserId(): void
    {
        $token = $this->createToken('Test Token', true);

        $this->client->loginUser($this->gameMaster);
        $this->client->request(
            'POST',
            $this->getTokenUrl($token->getId()) . '/permissions',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'action' => 'add',
            ]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testAddPermissionsSuccess(): void
    {
        $token = $this->createToken('Test Token', true);

        $this->client->loginUser($this->gameMaster);

        $this->client->request(
            'POST',
            $this->getTokenUrl($token->getId()) . '/permissions',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'action' => 'add',
                'userId' => $this->player->getId(),
            ]),
        );

        $this->assertResponseIsSuccessful();
    }

    public function testRemovePermissionsSuccess(): void
    {
        $token = $this->createToken('Test Token', true);

        $settings = $token->getSettings() ?? [];
        $settings['controllableBy'] = [$this->player->getId()];
        $token->setSettings($settings);
        $this->entityManager->flush();

        $this->client->loginUser($this->gameMaster);
        $this->client->request(
            'POST',
            $this->getTokenUrl($token->getId()) . '/permissions',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'action' => 'remove',
                'userId' => $this->player->getId(),
            ]),
        );

        $this->assertResponseIsSuccessful();
    }

    public function testUpdateTokenForNonExistentGame(): void
    {
        $token = $this->createToken('Test Token', true);

        $this->client->loginUser($this->gameMaster);
        $this->client->request(
            'PUT',
            '/api/games/99999/maps/' . $this->map->getId() . '/tokens/' . $token->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['name' => 'New Name']),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testUpdateTokenForNonExistentMap(): void
    {
        $token = $this->createToken('Test Token', true);

        $this->client->loginUser($this->gameMaster);
        $this->client->request(
            'PUT',
            '/api/games/' . $this->game->getId() . '/maps/99999/tokens/' . $token->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['name' => 'New Name']),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testCreateTokenForNonExistentGame(): void
    {
        $this->client->loginUser($this->gameMaster);
        $this->client->request(
            'POST',
            '/api/games/99999/maps/' . $this->map->getId() . '/tokens',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'name' => 'New Token',
                'type' => 'character',
                'x' => 5,
                'y' => 5,
            ]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testMoveTokenForbiddenForNonParticipant(): void
    {
        $token = $this->createToken('Test Token', true);

        $otherUser = new User();
        $otherUser->setPseudo('other');
        $otherUser->setEmail('other@test.com');
        $otherUser->setPassword('password');
        $this->entityManager->persist($otherUser);
        $this->entityManager->flush();

        $this->client->loginUser($otherUser);
        $this->client->request(
            'POST',
            $this->getTokenUrl($token->getId()) . '/move',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['x' => 10, 'y' => 10]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testToggleVisibilityForNonExistentGame(): void
    {
        $token = $this->createToken('Test Token', true);

        $this->client->loginUser($this->gameMaster);
        $this->client->request(
            'POST',
            '/api/games/99999/maps/' . $this->map->getId() . '/tokens/' . $token->getId() . '/toggle-visibility',
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testToggleLockForNonExistentGame(): void
    {
        $token = $this->createToken('Test Token', true);

        $this->client->loginUser($this->gameMaster);
        $this->client->request(
            'POST',
            '/api/games/99999/maps/' . $this->map->getId() . '/tokens/' . $token->getId() . '/toggle-lock',
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testDeleteTokenForNonExistentGame(): void
    {
        $token = $this->createToken('Test Token', true);

        $this->client->loginUser($this->gameMaster);
        $this->client->request(
            'DELETE',
            '/api/games/99999/maps/' . $this->map->getId() . '/tokens/' . $token->getId(),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testManagePermissionsForNonExistentGame(): void
    {
        $token = $this->createToken('Test Token', true);

        $this->client->loginUser($this->gameMaster);
        $this->client->request(
            'POST',
            '/api/games/99999/maps/' . $this->map->getId() . '/tokens/' . $token->getId() . '/permissions',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'action' => 'add',
                'userId' => $this->player->getId(),
            ]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    // ==================== MULTIPART/FORM-DATA TESTS ====================

    public function testCreateTokenWithMultipartFormData(): void
    {
        $this->client->loginUser($this->gameMaster);

        $this->client->request(
            'POST',
            $this->getTokenUrl(),
            [
                'name' => 'Test Token Multipart',
                'type' => 'character',
                'x' => '10',
                'y' => '15',
                'size' => '2.0',
                'rotation' => '90',
                'isVisible' => 'true',
                'isLocked' => 'false',
                'layer' => 'tokens',
            ],
            [],
            [
                'CONTENT_TYPE' => 'multipart/form-data',
            ],
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Test Token Multipart', $response['name']);
        $this->assertEquals(10, $response['x']);
        $this->assertEquals(15, $response['y']);
    }

    public function testCreateTokenWithMultipartFormDataMissingName(): void
    {
        $this->client->loginUser($this->gameMaster);

        $this->client->request(
            'POST',
            $this->getTokenUrl(),
            [
                'type' => 'character',
                'x' => '10',
            ],
            [],
            ['CONTENT_TYPE' => 'multipart/form-data'],
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testUpdateTokenWithMultipartFormDataPut(): void
    {
        $token = $this->createToken('Original Token', true);

        $this->client->loginUser($this->gameMaster);

        $boundary = '----WebKitFormBoundary' . uniqid();

        $data = "--{$boundary}\r\n";
        $data .= "Content-Disposition: form-data; name=\"name\"\r\n\r\n";
        $data .= "Updated via Multipart\r\n";
        $data .= "--{$boundary}\r\n";
        $data .= "Content-Disposition: form-data; name=\"x\"\r\n\r\n";
        $data .= "20\r\n";
        $data .= "--{$boundary}\r\n";
        $data .= "Content-Disposition: form-data; name=\"y\"\r\n\r\n";
        $data .= "25\r\n";
        $data .= "--{$boundary}--\r\n";

        $this->client->request(
            'PUT',
            $this->getTokenUrl($token->getId()),
            [],
            [],
            ['CONTENT_TYPE' => 'multipart/form-data; boundary=' . $boundary],
            $data,
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Updated via Multipart', $response['name']);
        $this->assertEquals(20, $response['x']);
        $this->assertEquals(25, $response['y']);
    }

    public function testUpdateTokenWithMultipartFormDataPatch(): void
    {
        $token = $this->createToken('Original Token', true);

        $this->client->loginUser($this->gameMaster);

        $boundary = '----WebKitFormBoundary' . uniqid();

        $data = "--{$boundary}\r\n";
        $data .= "Content-Disposition: form-data; name=\"size\"\r\n\r\n";
        $data .= "3.5\r\n";
        $data .= "--{$boundary}\r\n";
        $data .= "Content-Disposition: form-data; name=\"rotation\"\r\n\r\n";
        $data .= "180\r\n";
        $data .= "--{$boundary}--\r\n";

        $this->client->request(
            'PATCH',
            $this->getTokenUrl($token->getId()),
            [],
            [],
            ['CONTENT_TYPE' => 'multipart/form-data; boundary=' . $boundary],
            $data,
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals(3.5, $response['size']);
        $this->assertEquals(180, $response['rotation']);
    }

    private function createToken(
        string $name,
        bool $visible,
        int $x = 5,
        int $y = 5,
        bool $locked = false,
    ): GameToken {
        $token = new GameToken();
        $token->setMap($this->map);
        $token->setName($name);
        $token->setType(TokenType::CHARACTER);
        $token->setX($x);
        $token->setY($y);
        $token->setIsVisible($visible);
        $token->setIsLocked($locked);
        $this->entityManager->persist($token);
        $this->entityManager->flush();

        return $token;
    }
}
