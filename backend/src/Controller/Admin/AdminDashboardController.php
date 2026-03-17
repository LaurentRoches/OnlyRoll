<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Repository\AuditLogRepository;
use App\Repository\GameRepository;
use App\Repository\UserRepository;
use App\Service\AuditLogService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur du tableau de bord d'administration.
 */
#[Route('/api/admin/dashboard', name: 'api_admin_dashboard_')]
#[IsGranted('ROLE_ADMIN')]
final class AdminDashboardController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly GameRepository $gameRepository,
        private readonly AuditLogRepository $auditLogRepository,
        private readonly AuditLogService $auditLogService,
    ) {
    }

    /**
     * Récupère les statistiques globales pour le dashboard.
     */
    #[OA\Get(
        path: '/api/admin/dashboard/stats',
        summary: 'Récupère les statistiques globales du tableau de bord',
        security: [['BearerAuth' => []]],
        tags: ['Administration'],
        responses: [
            new OA\Response(response: 200, description: 'Statistiques globales (utilisateurs, audit, parties)'),
            new OA\Response(response: 429, description: 'Trop de requêtes'),
        ],
    )]
    #[Route('/stats', name: 'stats', methods: ['GET'])]
    public function stats(
        #[Autowire(service: 'limiter.admin_action_limiter')]
        RateLimiterFactory $adminActionLimiter,
    ): JsonResponse {
        /** @var \App\Entity\User $admin */
        $admin = $this->getUser();

        $limiter = $adminActionLimiter->create($admin->getUserIdentifier());
        if (false === $limiter->consume(1)->isAccepted()) {
            return $this->json(
                ['error' => 'Trop de requêtes. Veuillez réessayer plus tard.'],
                Response::HTTP_TOO_MANY_REQUESTS,
            );
        }

        $this->auditLogService->logAdminAccess($admin, 'dashboard');

        $userStats = $this->userRepository->getStatistics();

        $auditStats = $this->auditLogRepository->getStatistics();

        $gameStats = [
            'total' => $this->gameRepository->count([]),
            'public' => $this->gameRepository->count(['isPublic' => true]),
        ];

        return $this->json([
            'users' => $userStats,
            'audit' => $auditStats,
            'games' => $gameStats,
        ]);
    }

    /**
     * Récupère les logs d'audit récents pour le dashboard.
     */
    #[OA\Get(
        path: '/api/admin/dashboard/recent-activity',
        summary: 'Récupère les 20 derniers logs d\'audit pour le tableau de bord',
        security: [['BearerAuth' => []]],
        tags: ['Administration'],
        responses: [
            new OA\Response(response: 200, description: 'Liste des activités récentes'),
            new OA\Response(response: 429, description: 'Trop de requêtes'),
        ],
    )]
    #[Route('/recent-activity', name: 'recent_activity', methods: ['GET'])]
    public function recentActivity(
        #[Autowire(service: 'limiter.admin_action_limiter')]
        RateLimiterFactory $adminActionLimiter,
    ): JsonResponse {
        /** @var \App\Entity\User $admin */
        $admin = $this->getUser();

        $limiter = $adminActionLimiter->create($admin->getUserIdentifier());
        if (false === $limiter->consume(1)->isAccepted()) {
            return $this->json(
                ['error' => 'Trop de requêtes. Veuillez réessayer plus tard.'],
                Response::HTTP_TOO_MANY_REQUESTS,
            );
        }

        $recentLogs = $this->auditLogRepository->findRecent(20);

        return $this->json($recentLogs, Response::HTTP_OK, [], ['groups' => 'audit:list']);
    }
}
