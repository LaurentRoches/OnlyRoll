<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventSubscriber;

use App\Entity\User;
use App\EventSubscriber\AuthenticationSuccessSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests unitaires pour AuthenticationSuccessSubscriber.
 */
final class AuthenticationSuccessSubscriberTest extends TestCase
{
    private RequestStack $requestStack;
    private EntityManagerInterface&MockObject $entityManager;
    private AuthenticationSuccessSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->requestStack = new RequestStack();
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->subscriber = new AuthenticationSuccessSubscriber($this->requestStack, $this->entityManager);
    }

    private function createMockUser(): User
    {
        $user = $this->createMock(User::class);
        $user->method('getUserIdentifier')->willReturn('test@example.com');
        return $user;
    }

    public function testGetSubscribedEvents(): void
    {
        $events = AuthenticationSuccessSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey(Events::AUTHENTICATION_SUCCESS, $events);
        $this->assertSame('onAuthenticationSuccess', $events[Events::AUTHENTICATION_SUCCESS]);
    }

    public function testOnAuthenticationSuccessWithoutToken(): void
    {
        $response = new Response();
        $event = new AuthenticationSuccessEvent([], $this->createMockUser(), $response);

        $this->subscriber->onAuthenticationSuccess($event);

        $cookies = $response->headers->getCookies();
        $this->assertEmpty($cookies);
    }

    public function testOnAuthenticationSuccessWithTokenAndRememberMe(): void
    {
        $response = new Response();
        $data = [
            'token' => 'test_jwt_token',
            'user' => ['id' => 1, 'email' => 'test@example.com'],
        ];

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['rememberMe' => true])
        );
        $this->requestStack->push($request);

        $event = new AuthenticationSuccessEvent($data, $this->createMockUser(), $response);
        $this->subscriber->onAuthenticationSuccess($event);

        $cookies = $response->headers->getCookies();
        $this->assertCount(3, $cookies);

        $cookiesByName = [];
        foreach ($cookies as $cookie) {
            $cookiesByName[$cookie->getName()] = $cookie;
        }

        $this->assertArrayHasKey('jwt_token', $cookiesByName);
        $jwtCookie = $cookiesByName['jwt_token'];
        $this->assertSame('test_jwt_token', $jwtCookie->getValue());
        $this->assertTrue($jwtCookie->isHttpOnly());
        $this->assertSame('/', $jwtCookie->getPath());
        $this->assertSame('lax', $jwtCookie->getSameSite());

        $this->assertArrayHasKey('remember_me', $cookiesByName);
        $rememberMeCookie = $cookiesByName['remember_me'];
        $this->assertSame('1', $rememberMeCookie->getValue());
        $this->assertTrue($rememberMeCookie->isHttpOnly());

        $this->assertArrayHasKey('last_activity', $cookiesByName);
        $lastActivityCookie = $cookiesByName['last_activity'];
        $this->assertTrue($lastActivityCookie->isHttpOnly());

        $eventData = $event->getData();
        $this->assertTrue($eventData['success']);
        $this->assertSame('Authentification réussie', $eventData['message']);
        $this->assertArrayHasKey('user', $eventData);
    }

    public function testOnAuthenticationSuccessWithTokenWithoutRememberMe(): void
    {
        $response = new Response();
        $data = [
            'token' => 'test_jwt_token',
            'user' => ['id' => 1, 'email' => 'test@example.com'],
        ];

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['rememberMe' => false])
        );
        $this->requestStack->push($request);

        $event = new AuthenticationSuccessEvent($data, $this->createMockUser(), $response);
        $this->subscriber->onAuthenticationSuccess($event);

        $cookies = $response->headers->getCookies();
        $this->assertCount(2, $cookies);

        $cookiesByName = [];
        foreach ($cookies as $cookie) {
            $cookiesByName[$cookie->getName()] = $cookie;
        }

        $this->assertArrayHasKey('jwt_token', $cookiesByName);
        $jwtCookie = $cookiesByName['jwt_token'];
        $this->assertSame('test_jwt_token', $jwtCookie->getValue());

        $this->assertArrayHasKey('last_activity', $cookiesByName);

        $this->assertArrayNotHasKey('remember_me', $cookiesByName);
    }

    public function testOnAuthenticationSuccessWithTokenWithoutRequest(): void
    {
        $response = new Response();
        $data = [
            'token' => 'test_jwt_token',
            'user' => ['id' => 1, 'email' => 'test@example.com'],
        ];

        $event = new AuthenticationSuccessEvent($data, $this->createMockUser(), $response);
        $this->subscriber->onAuthenticationSuccess($event);

        $cookies = $response->headers->getCookies();
        $this->assertCount(2, $cookies);

        $cookiesByName = [];
        foreach ($cookies as $cookie) {
            $cookiesByName[$cookie->getName()] = $cookie;
        }

        $this->assertArrayHasKey('jwt_token', $cookiesByName);
        $jwtCookie = $cookiesByName['jwt_token'];
        $this->assertSame('test_jwt_token', $jwtCookie->getValue());

        $this->assertArrayHasKey('last_activity', $cookiesByName);

        $this->assertArrayNotHasKey('remember_me', $cookiesByName);
    }

    public function testOnAuthenticationSuccessWithTokenAndInvalidJson(): void
    {
        $response = new Response();
        $data = [
            'token' => 'test_jwt_token',
        ];

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            'invalid json'
        );
        $this->requestStack->push($request);

        $event = new AuthenticationSuccessEvent($data, $this->createMockUser(), $response);
        $this->subscriber->onAuthenticationSuccess($event);

        $cookies = $response->headers->getCookies();
        $this->assertCount(2, $cookies);

        $cookiesByName = [];
        foreach ($cookies as $cookie) {
            $cookiesByName[$cookie->getName()] = $cookie;
        }

        $this->assertArrayHasKey('jwt_token', $cookiesByName);
        $this->assertArrayHasKey('last_activity', $cookiesByName);

        $this->assertArrayNotHasKey('remember_me', $cookiesByName);
    }
}
