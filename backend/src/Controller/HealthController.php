<?php

declare(strict_types=1);

namespace App\Controller;

use Doctrine\DBAL\Connection;
use OpenApi\Attributes as OA;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur de santé de l'application.
 */
final class HealthController extends AbstractController
{
    public function __construct(
        private readonly Connection $connection,
        private readonly CacheItemPoolInterface $appCache,
    ) {
    }

    #[OA\Get(
        path: '/api/health',
        summary: 'Vérifie l\'état de santé de l\'API',
        tags: ['Système'],
        responses: [
            new OA\Response(response: 200, description: 'API opérationnelle'),
            new OA\Response(response: 500, description: 'Un service est indisponible'),
        ],
    )]
    #[Route('/api/health', name: 'api_health', methods: ['GET'])]
    public function health(): JsonResponse
    {
        $checks = ['db' => 'ok', 'redis' => 'ok'];
        $httpStatus = 200;

        try {
            $this->connection->executeQuery('SELECT 1');
        } catch (\Throwable) {
            $checks['db'] = 'error';
            $httpStatus = 500;
        }

        try {
            $this->appCache->hasItem('__health_probe__');
        } catch (\Throwable) {
            $checks['redis'] = 'error';
            $httpStatus = 500;
        }

        return $this->json(
            ['status' => $httpStatus === 200 ? 'ok' : 'error', 'checks' => $checks],
            $httpStatus,
        );
    }
}
