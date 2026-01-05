<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventSubscriber;

use App\Entity\User;
use App\EventSubscriber\AuthenticationSuccessSubscriber;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
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
    private AuthenticationSuccessSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->requestStack = new RequestStack();
        $this->subscriber = new AuthenticationSuccessSubscriber($this->requestStack);
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

        // Vérifie qu'aucun cookie n'a été ajouté
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

        // Créer une requête avec rememberMe = true
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

        // Vérifie qu'un cookie a été ajouté
        $cookies = $response->headers->getCookies();
        $this->assertCount(1, $cookies);

        $cookie = $cookies[0];
        $this->assertSame('jwt_token', $cookie->getName());
        $this->assertSame('test_jwt_token', $cookie->getValue());
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame('/', $cookie->getPath());
        $this->assertSame('lax', $cookie->getSameSite());

        // Vérifie que les données ont été modifiées
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

        // Créer une requête avec rememberMe = false
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

        // Vérifie qu'un cookie a été ajouté
        $cookies = $response->headers->getCookies();
        $this->assertCount(1, $cookies);

        $cookie = $cookies[0];
        $this->assertSame('jwt_token', $cookie->getName());
        $this->assertSame('test_jwt_token', $cookie->getValue());
    }

    public function testOnAuthenticationSuccessWithTokenWithoutRequest(): void
    {
        $response = new Response();
        $data = [
            'token' => 'test_jwt_token',
            'user' => ['id' => 1, 'email' => 'test@example.com'],
        ];

        // Pas de requête dans le RequestStack
        $event = new AuthenticationSuccessEvent($data, $this->createMockUser(), $response);
        $this->subscriber->onAuthenticationSuccess($event);

        // Vérifie qu'un cookie a été ajouté avec le comportement par défaut (rememberMe = false)
        $cookies = $response->headers->getCookies();
        $this->assertCount(1, $cookies);

        $cookie = $cookies[0];
        $this->assertSame('jwt_token', $cookie->getName());
        $this->assertSame('test_jwt_token', $cookie->getValue());
    }

    public function testOnAuthenticationSuccessWithTokenAndInvalidJson(): void
    {
        $response = new Response();
        $data = [
            'token' => 'test_jwt_token',
        ];

        // Créer une requête avec un JSON invalide
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

        // Vérifie qu'un cookie a été ajouté malgré le JSON invalide
        $cookies = $response->headers->getCookies();
        $this->assertCount(1, $cookies);
    }
}
