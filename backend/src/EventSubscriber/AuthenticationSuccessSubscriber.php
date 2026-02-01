<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\User;
use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Subscriber pour gérer le succès d'authentification JWT.
 */
final class AuthenticationSuccessSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::AUTHENTICATION_SUCCESS => 'onAuthenticationSuccess',
        ];
    }

    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        $data = $event->getData();
        $response = $event->getResponse();

        $token = $data['token'] ?? null;

        if (!$token) {
            return;
        }

        // Mettre à jour la date de dernière connexion
        $user = $event->getUser();
        if ($user instanceof User) {
            $user->setLastLogin(new DateTimeImmutable());
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }

        $request = $this->requestStack->getCurrentRequest();
        $rememberMe = false;

        if ($request) {
            $requestData = json_decode($request->getContent(), true);
            $rememberMe = $requestData['rememberMe'] ?? false;
        }

        $expirationTime = $rememberMe ? '+30 days' : '+2 hours';

        $isProduction = ($_ENV['APP_ENV'] ?? 'dev') === 'prod';
        $isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';

        $cookie = Cookie::create('jwt_token')
            ->withValue($token)
            ->withExpires(new DateTime($expirationTime))
            ->withPath('/')
            ->withDomain(null)
            ->withSecure($isProduction && $isHttps)
            ->withHttpOnly(true)
            ->withSameSite(Cookie::SAMESITE_LAX);

        $response->headers->setCookie($cookie);

        if ($rememberMe) {
            $rememberMeCookie = Cookie::create('remember_me')
                ->withValue('1')
                ->withExpires(new DateTime('+30 days'))
                ->withPath('/')
                ->withDomain(null)
                ->withSecure($isProduction && $isHttps)
                ->withHttpOnly(true)
                ->withSameSite(Cookie::SAMESITE_LAX);

            $response->headers->setCookie($rememberMeCookie);
        }

        $lastActivityCookie = Cookie::create('last_activity')
            ->withValue((string) time())
            ->withExpires(new DateTime($expirationTime))
            ->withPath('/')
            ->withDomain(null)
            ->withSecure($isProduction && $isHttps)
            ->withHttpOnly(true)
            ->withSameSite(Cookie::SAMESITE_LAX);

        $response->headers->setCookie($lastActivityCookie);

        $event->setData([
            'success' => true,
            'message' => 'Authentification réussie',
            'user' => $data['user'] ?? null,
        ]);
    }
}
