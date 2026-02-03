<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\User;
use App\Service\MercureTokenService;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\UnencryptedToken;
use PHPUnit\Framework\TestCase;

class MercureTokenServiceTest extends TestCase
{
    private MercureTokenService $mercureTokenService;
    private string $mercureJwtSecret = 'test-secret-key-for-mercure-min-32-chars-required';

    protected function setUp(): void
    {
        $this->mercureTokenService = new MercureTokenService($this->mercureJwtSecret);
    }

    public function testGenerateTokenForGame(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPseudo('testuser');
        $gameId = 123;

        $token = $this->mercureTokenService->generateTokenForGame($user, $gameId);

        $this->assertNotEmpty($token);
        $this->assertIsString($token);

        $parser = new Parser(new JoseEncoder());
        /** @var UnencryptedToken $parsedToken */
        $parsedToken = $parser->parse($token);

        $this->assertTrue($parsedToken->hasBeenIssuedBefore(new \DateTimeImmutable('+1 minute')));
        $this->assertFalse($parsedToken->isExpired(new \DateTimeImmutable()));

        $mercureClaim = $parsedToken->claims()->get('mercure');
        $this->assertIsArray($mercureClaim);
        $this->assertArrayHasKey('subscribe', $mercureClaim);

        $subscribeTopics = $mercureClaim['subscribe'];
        $this->assertIsArray($subscribeTopics);
        $this->assertContains('game/123/chat', $subscribeTopics);
        $this->assertContains('game/123/token', $subscribeTopics);
        $this->assertContains('game/123/map', $subscribeTopics);
        $this->assertContains('game/123/dice', $subscribeTopics);
        $this->assertContains('game/123/player', $subscribeTopics);
        $this->assertContains('game/123/presence', $subscribeTopics);
        $this->assertContains('game/123/system', $subscribeTopics);
        $this->assertCount(7, $subscribeTopics);
    }

    public function testGenerateTokenForPresence(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPseudo('testuser');
        $gameIds = [1, 2, 3, 42];

        $token = $this->mercureTokenService->generateTokenForPresence($user, $gameIds);

        $this->assertNotEmpty($token);
        $this->assertIsString($token);

        $parser = new Parser(new JoseEncoder());
        /** @var UnencryptedToken $parsedToken */
        $parsedToken = $parser->parse($token);

        $this->assertTrue($parsedToken->hasBeenIssuedBefore(new \DateTimeImmutable('+1 minute')));
        $this->assertFalse($parsedToken->isExpired(new \DateTimeImmutable()));

        $mercureClaim = $parsedToken->claims()->get('mercure');
        $this->assertIsArray($mercureClaim);
        $this->assertArrayHasKey('subscribe', $mercureClaim);

        $subscribeTopics = $mercureClaim['subscribe'];
        $this->assertIsArray($subscribeTopics);
        $this->assertContains('game/1/presence', $subscribeTopics);
        $this->assertContains('game/2/presence', $subscribeTopics);
        $this->assertContains('game/3/presence', $subscribeTopics);
        $this->assertContains('game/42/presence', $subscribeTopics);
        $this->assertCount(4, $subscribeTopics);
    }

    public function testGenerateTokenForPresenceWithEmptyGameIds(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPseudo('testuser');
        $gameIds = [];

        $token = $this->mercureTokenService->generateTokenForPresence($user, $gameIds);

        $this->assertNotEmpty($token);
        $parser = new Parser(new JoseEncoder());
        /** @var UnencryptedToken $parsedToken */
        $parsedToken = $parser->parse($token);

        $mercureClaim = $parsedToken->claims()->get('mercure');
        $this->assertIsArray($mercureClaim['subscribe']);
        $this->assertCount(0, $mercureClaim['subscribe']);
    }

    public function testTokenExpiresAfterOneHour(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPseudo('testuser');
        $gameId = 1;

        $token = $this->mercureTokenService->generateTokenForGame($user, $gameId);

        $parser = new Parser(new JoseEncoder());
        /** @var UnencryptedToken $parsedToken */
        $parsedToken = $parser->parse($token);

        $this->assertFalse($parsedToken->isExpired(new \DateTimeImmutable()));

        $this->assertTrue($parsedToken->isExpired(new \DateTimeImmutable('+2 hours')));
    }
}
