<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use DateTime;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Subscriber pour rafraîchir automatiquement le cookie JWT lors de l'activité utilisateur.
 *
 * Implémente une "sliding session" : à chaque requête authentifiée, le cookie
 * est renouvelé avec une nouvelle date d'expiration. Cela évite que la session
 * expire pendant une partie de jeu active.
 *
 * OWASP A07 - Identification and Authentication Failures :
 * - Maintient la session active tant que l'utilisateur est actif
 * - Expire automatiquement après inactivité
 */
final class JwtCookieRefreshSubscriber implements EventSubscriberInterface
{
    private const COOKIE_NAME = 'jwt_token';
    private const REMEMBER_ME_COOKIE = 'remember_me';

    // Durées d'expiration
    private const SHORT_EXPIRATION = '+2 hours';      // Session normale
    private const LONG_EXPIRATION = '+30 days';       // Remember me

    // Ne pas rafraîchir si le cookie expire dans plus de X temps
    // (évite de réécrire le cookie à chaque requête)
    private const REFRESH_THRESHOLD_SHORT = 3600;     // 1 heure
    private const REFRESH_THRESHOLD_LONG = 86400;     // 1 jour

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

        // Vérifier si l'utilisateur est authentifié
        $token = $this->tokenStorage->getToken();
        if (null === $token || !$token->getUser()) {
            return;
        }

        // Récupérer le JWT depuis le cookie
        $jwtToken = $request->cookies->get(self::COOKIE_NAME);
        if (!$jwtToken) {
            return;
        }

        // Vérifier si c'est une session "remember me"
        $isRememberMe = $request->cookies->has(self::REMEMBER_ME_COOKIE)
            && '1' === $request->cookies->get(self::REMEMBER_ME_COOKIE);

        // Calculer la nouvelle expiration
        $expiration = $isRememberMe ? self::LONG_EXPIRATION : self::SHORT_EXPIRATION;
        $threshold = $isRememberMe ? self::REFRESH_THRESHOLD_LONG : self::REFRESH_THRESHOLD_SHORT;

        // Ne rafraîchir que si nécessaire (évite de modifier la réponse à chaque requête)
        if (!$this->shouldRefresh($request, $threshold)) {
            return;
        }

        // Paramètres de sécurité du cookie
        $isProduction = 'prod' === $this->appEnv;
        $isHttps = $request->isSecure();

        // Créer le nouveau cookie avec expiration renouvelée
        $cookie = Cookie::create(self::COOKIE_NAME)
            ->withValue($jwtToken)
            ->withExpires(new DateTime($expiration))
            ->withPath('/')
            ->withDomain(null)
            ->withSecure($isProduction && $isHttps)
            ->withHttpOnly(true)
            ->withSameSite(Cookie::SAMESITE_LAX);

        $response->headers->setCookie($cookie);

        // Mettre à jour le cookie de marqueur "last_activity"
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

    /**
     * Détermine si le cookie doit être rafraîchi.
     *
     * On ne rafraîchit que si le dernier rafraîchissement date de plus
     * de $threshold secondes (pour éviter de modifier chaque réponse).
     */
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

        // Rafraîchir si plus de la moitié du seuil est passée
        return $timeSinceLastRefresh > ($threshold / 2);
    }
}
