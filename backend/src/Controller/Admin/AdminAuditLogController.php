<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\DTO\Admin\AuditLogFilterDTO;
use App\Enum\AuditAction;
use App\Repository\AuditLogRepository;
use App\Service\AuditLogService;
use DateTimeImmutable;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Contrôleur de consultation des logs d'audit pour l'administration.
 */
#[Route('/api/admin/audit-logs', name: 'api_admin_audit_logs_')]
#[IsGranted('ROLE_ADMIN')]
final class AdminAuditLogController extends AbstractController
{
    public function __construct(
        private readonly AuditLogRepository $auditLogRepository,
        private readonly AuditLogService $auditLogService,
        private readonly ValidatorInterface $validator,
    ) {
    }

    /**
     * Liste les logs d'audit avec filtres et pagination.
     */
    #[OA\Get(
        path: '/api/admin/audit-logs',
        summary: 'Liste les logs d\'audit avec filtres et pagination',
        security: [['BearerAuth' => []]],
        tags: ['Administration'],
        parameters: [
            new OA\Parameter(name: 'userId', in: 'query', required: false, schema: new OA\Schema(type: 'integer'), description: 'Filtrer par ID utilisateur'),
            new OA\Parameter(name: 'targetUserId', in: 'query', required: false, schema: new OA\Schema(type: 'integer'), description: 'Filtrer par ID utilisateur cible'),
            new OA\Parameter(name: 'action', in: 'query', required: false, schema: new OA\Schema(type: 'string'), description: 'Filtrer par type d\'action'),
            new OA\Parameter(name: 'entityType', in: 'query', required: false, schema: new OA\Schema(type: 'string'), description: 'Filtrer par type d\'entité'),
            new OA\Parameter(name: 'dateFrom', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date-time'), description: 'Date de début'),
            new OA\Parameter(name: 'dateTo', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date-time'), description: 'Date de fin'),
            new OA\Parameter(name: 'severity', in: 'query', required: false, schema: new OA\Schema(type: 'string'), description: 'Filtrer par sévérité'),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1), description: 'Numéro de page'),
            new OA\Parameter(name: 'limit', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 50), description: 'Nombre d\'éléments par page'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Liste paginée des logs d\'audit'),
            new OA\Response(response: 400, description: 'Paramètres de filtre invalides'),
            new OA\Response(response: 429, description: 'Trop de requêtes'),
        ],
    )]
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request, #[Autowire(service: 'limiter.admin_action_limiter')] RateLimiterFactory $adminActionLimiter): JsonResponse
    {
        /** @var \App\Entity\User $admin */
        $admin = $this->getUser();

        $limiter = $adminActionLimiter->create($admin->getUserIdentifier());
        if (false === $limiter->consume(1)->isAccepted()) {
            return $this->json(
                ['error' => 'Trop de requêtes. Veuillez réessayer plus tard.'],
                Response::HTTP_TOO_MANY_REQUESTS,
            );
        }

        $filterDTO = new AuditLogFilterDTO();
        $filterDTO->userId = $request->query->getInt('userId') ?: null;
        $filterDTO->targetUserId = $request->query->getInt('targetUserId') ?: null;

        $actionString = $request->query->getString('action');
        $filterDTO->action = $actionString ? AuditAction::tryFrom($actionString) : null;

        $filterDTO->entityType = $request->query->getString('entityType') ?: null;

        $dateFromString = $request->query->getString('dateFrom');
        $filterDTO->dateFrom = $dateFromString ? new DateTimeImmutable($dateFromString) : null;

        $dateToString = $request->query->getString('dateTo');
        $filterDTO->dateTo = $dateToString ? new DateTimeImmutable($dateToString) : null;

        $filterDTO->severity = $request->query->getString('severity') ?: null;
        $filterDTO->page = (int) $request->query->get('page', 1);
        $filterDTO->limit = (int) $request->query->get('limit', 50);

        $errors = $this->validator->validate($filterDTO);
        if (\count($errors) > 0) {
            return $this->json(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        $result = $this->auditLogRepository->findWithFilters($filterDTO);

        $this->auditLogService->logAdminAccess($admin, 'audit_logs');

        return $this->json([
            'data' => $result['data'],
            'meta' => [
                'total' => $result['total'],
                'page' => $result['page'],
                'limit' => $result['limit'],
                'totalPages' => $result['totalPages'],
            ],
        ], Response::HTTP_OK, [], ['groups' => 'audit:read']);
    }

    /**
     * Récupère un log d'audit par son ID.
     */
    #[OA\Get(
        path: '/api/admin/audit-logs/{id}',
        summary: 'Récupère un log d\'audit par son ID',
        security: [['BearerAuth' => []]],
        tags: ['Administration'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), description: 'ID du log d\'audit'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Détails du log d\'audit'),
            new OA\Response(response: 404, description: 'Log non trouvé'),
            new OA\Response(response: 429, description: 'Trop de requêtes'),
        ],
    )]
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id, #[Autowire(service: 'limiter.admin_action_limiter')] RateLimiterFactory $adminActionLimiter): JsonResponse
    {
        /** @var \App\Entity\User $admin */
        $admin = $this->getUser();

        $limiter = $adminActionLimiter->create($admin->getUserIdentifier());
        if (false === $limiter->consume(1)->isAccepted()) {
            return $this->json(
                ['error' => 'Trop de requêtes. Veuillez réessayer plus tard.'],
                Response::HTTP_TOO_MANY_REQUESTS,
            );
        }

        $auditLog = $this->auditLogRepository->find($id);

        if (!$auditLog) {
            return $this->json(['error' => 'Log non trouvé'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($auditLog, Response::HTTP_OK, [], ['groups' => 'audit:read']);
    }

    /**
     * Récupère les logs d'audit pour un utilisateur spécifique.
     */
    #[OA\Get(
        path: '/api/admin/audit-logs/user/{userId}',
        summary: 'Récupère les logs d\'audit d\'un utilisateur spécifique',
        security: [['BearerAuth' => []]],
        tags: ['Administration'],
        parameters: [
            new OA\Parameter(name: 'userId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), description: 'ID de l\'utilisateur'),
            new OA\Parameter(name: 'limit', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 50), description: 'Nombre maximum de résultats'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Liste des logs d\'audit de l\'utilisateur'),
            new OA\Response(response: 429, description: 'Trop de requêtes'),
        ],
    )]
    #[Route('/user/{userId}', name: 'by_user', methods: ['GET'])]
    public function byUser(int $userId, Request $request, #[Autowire(service: 'limiter.admin_action_limiter')] RateLimiterFactory $adminActionLimiter): JsonResponse
    {
        /** @var \App\Entity\User $admin */
        $admin = $this->getUser();

        $limiter = $adminActionLimiter->create($admin->getUserIdentifier());
        if (false === $limiter->consume(1)->isAccepted()) {
            return $this->json(
                ['error' => 'Trop de requêtes. Veuillez réessayer plus tard.'],
                Response::HTTP_TOO_MANY_REQUESTS,
            );
        }

        $limit = (int) $request->query->get('limit', 50);
        $logs = $this->auditLogRepository->findByUser($userId, $limit);

        return $this->json($logs, Response::HTTP_OK, [], ['groups' => 'audit:read']);
    }

    /**
     * Récupère les statistiques des logs d'audit.
     */
    #[OA\Get(
        path: '/api/admin/audit-logs/statistics',
        summary: 'Récupère les statistiques des logs d\'audit',
        security: [['BearerAuth' => []]],
        tags: ['Administration'],
        responses: [
            new OA\Response(response: 200, description: 'Statistiques des logs d\'audit'),
            new OA\Response(response: 429, description: 'Trop de requêtes'),
        ],
    )]
    #[Route('/statistics', name: 'statistics', methods: ['GET'], priority: 10)]
    public function statistics(#[Autowire(service: 'limiter.admin_action_limiter')] RateLimiterFactory $adminActionLimiter): JsonResponse
    {
        /** @var \App\Entity\User $admin */
        $admin = $this->getUser();

        $limiter = $adminActionLimiter->create($admin->getUserIdentifier());
        if (false === $limiter->consume(1)->isAccepted()) {
            return $this->json(
                ['error' => 'Trop de requêtes. Veuillez réessayer plus tard.'],
                Response::HTTP_TOO_MANY_REQUESTS,
            );
        }

        $stats = $this->auditLogRepository->getStatistics();

        return $this->json($stats);
    }

    /**
     * Liste les types d'actions disponibles pour le filtrage.
     */
    #[OA\Get(
        path: '/api/admin/audit-logs/actions',
        summary: 'Liste les types d\'actions disponibles pour le filtrage',
        security: [['BearerAuth' => []]],
        tags: ['Administration'],
        responses: [
            new OA\Response(response: 200, description: 'Liste des actions disponibles avec leurs labels et sévérités'),
        ],
    )]
    #[Route('/actions', name: 'actions', methods: ['GET'], priority: 10)]
    public function actions(): JsonResponse
    {
        $actions = [];
        foreach (AuditAction::cases() as $action) {
            $actions[] = [
                'value' => $action->value,
                'label' => $action->getLabel(),
                'severity' => $action->getSeverity(),
            ];
        }

        return $this->json($actions);
    }
}
