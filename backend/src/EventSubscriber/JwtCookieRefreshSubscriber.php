<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use DateTime;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class JwtCookieRefreshSubscriber implements EventSubscriberInterface
{
    private const COOKIE_NAME = 'jwt_token';
    private const REMEMBER_ME_COOKIE = 'remember_me';

    private const SHORT_EXPIRATION = '+2 hours';
    private const LONG_EXPIRATION = '+30 days';

    private const REFRESH_THRESHOLD_SHORT = 3600;
    private const REFRESH_THRESHOLD_LONG = 86400;

    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly string $appEnv,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', -20],
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();

        $token = $this->tokenStorage->getToken();
        if (null === $token || !$token->getUser()) {
            return;
        }

        $jwtToken = $request->cookies->get(self::COOKIE_NAME);
        if (!$jwtToken) {
            return;
        }

        $isRememberMe = $request->cookies->has(self::REMEMBER_ME_COOKIE)
            && '1' === $request->cookies->get(self::REMEMBER_ME_COOKIE);

        $expiration = $isRememberMe ? self::LONG_EXPIRATION : self::SHORT_EXPIRATION;
        $threshold = $isRememberMe ? self::REFRESH_THRESHOLD_LONG : self::REFRESH_THRESHOLD_SHORT;

        if (!$this->shouldRefresh($request, $threshold)) {
            return;
        }

        $isProduction = 'prod' === $this->appEnv;
        $isHttps = $request->isSecure();

        $cookie = Cookie::create(self::COOKIE_NAME)
            ->withValue($jwtToken)
            ->withExpires(new DateTime($expiration))
            ->withPath('/')
            ->withDomain(null)
            ->withSecure($isProduction && $isHttps)
            ->withHttpOnly(true)
            ->withSameSite(Cookie::SAMESITE_LAX);

        $response->headers->setCookie($cookie);

        $lastActivityCookie = Cookie::create('last_activity')
            ->withValue((string) time())
            ->withExpires(new DateTime($expiration))
            ->withPath('/')
            ->withDomain(null)
            ->withSecure($isProduction && $isHttps)
            ->withHttpOnly(true)
            ->withSameSite(Cookie::SAMESITE_LAX);

        $response->headers->setCookie($lastActivityCookie);
    }

    private function shouldRefresh(
        \Symfony\Component\HttpFoundation\Request $request,
        int $threshold,
    ): bool {
        $lastActivity = $request->cookies->get('last_activity');

        if (null === $lastActivity) {
            return true;
        }

        $lastActivityTime = (int) $lastActivity;
        $timeSinceLastRefresh = time() - $lastActivityTime;

        return $timeSinceLastRefresh > ($threshold / 2);
    }
}
