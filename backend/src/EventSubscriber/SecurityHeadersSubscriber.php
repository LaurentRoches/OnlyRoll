<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Subscriber pour ajouter les headers de sécurité OWASP sur toutes les réponses.
 *
 * Respecte les recommandations OWASP Top 10:2025 :
 * - A02: Security Misconfiguration (CSP, X-Frame-Options, etc.)
 * - A08: Data Integrity Failures (SRI via CSP)
 */
final class SecurityHeadersSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly string $appEnv,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', -10],
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $response = $event->getResponse();

        // X-Content-Type-Options: Empêche le MIME-sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // X-Frame-Options: Protection contre le clickjacking
        $response->headers->set('X-Frame-Options', 'DENY');

        // X-XSS-Protection: Protection XSS (legacy, mais toujours utile)
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Referrer-Policy: Contrôle les informations envoyées dans le header Referer
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions-Policy: Restreint l'accès aux APIs du navigateur
        $response->headers->set(
            'Permissions-Policy',
            'geolocation=(), microphone=(), camera=(), payment=(), usb=()'
        );

        // Content-Security-Policy
        $csp = $this->buildContentSecurityPolicy();
        $response->headers->set('Content-Security-Policy', $csp);

        // Strict-Transport-Security (HSTS) - Seulement en production
        if ('prod' === $this->appEnv) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains; preload'
            );
        }
    }

    private function buildContentSecurityPolicy(): string
    {
        $directives = [
            "default-src 'self'",
            "script-src 'self'",
            "style-src 'self' 'unsafe-inline'", // unsafe-inline nécessaire pour certains frameworks CSS
            "img-src 'self' data: blob:",
            "font-src 'self'",
            "connect-src 'self' wss://*.onlyroll.fr",
            "frame-ancestors 'none'",
            "form-action 'self'",
            "base-uri 'self'",
            "object-src 'none'",
        ];

        return implode('; ', $directives);
    }
}
