<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

/**
 * Contrôleur de santé de l'application.
 */
final class HealthController extends AbstractController
{
    #[OA\Get(
        path: '/api/health',
        summary: 'Vérifie l\'état de santé de l\'API',
        tags: ['Système'],
        responses: [
            new OA\Response(response: 200, description: 'API opérationnelle'),
        ]
    )]
    #[Route('/api/health', name: 'api_health', methods: ['GET'])]
    public function health(): JsonResponse
    {
        return $this->json(['status' => 'ok'], 200);
    }
}
