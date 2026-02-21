<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\DTO\Admin\UserFilterDTO;
use App\DTO\Admin\UserUpdateDTO;
use App\Service\Admin\AdminUserService;
use App\Service\AuditLogService;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Attributes as OA;

/**
 * Contrôleur de gestion des utilisateurs pour l'administration.
 */
#[Route('/api/admin/users', name: 'api_admin_users_')]
#[IsGranted('ROLE_ADMIN')]
final class AdminUserController extends AbstractController
{
    public function __construct(
        private readonly AdminUserService $adminUserService,
        private readonly AuditLogService $auditLogService,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
    ) {
    }

    /**
     * Liste tous les utilisateurs avec filtres et pagination.
     */
    #[OA\Get(
        path: '/api/admin/users',
        summary: 'Liste tous les utilisateurs avec filtres et pagination',
        security: [['BearerAuth' => []]],
        tags: ['Administration'],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string'), description: 'Recherche par nom ou email'),
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', default: 'all'), description: 'Filtrer par statut'),
            new OA\Parameter(name: 'role', in: 'query', required: false, schema: new OA\Schema(type: 'string'), description: 'Filtrer par rôle'),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1), description: 'Numéro de page'),
            new OA\Parameter(name: 'limit', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 20), description: 'Nombre d\'éléments par page'),
            new OA\Parameter(name: 'sortBy', in: 'query', required: false, schema: new OA\Schema(type: 'string', default: 'createdAt'), description: 'Champ de tri'),
            new OA\Parameter(name: 'sortDirection', in: 'query', required: false, schema: new OA\Schema(type: 'string', default: 'DESC'), description: 'Direction du tri (ASC ou DESC)'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Liste paginée des utilisateurs'),
            new OA\Response(response: 400, description: 'Paramètres de filtre invalides'),
            new OA\Response(response: 429, description: 'Trop de requêtes'),
        ]
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

        $filterDTO = new UserFilterDTO();
        $filterDTO->search = $request->query->getString('search') ?: null;
        $filterDTO->status = $request->query->getString('status') ?: 'all';
        $filterDTO->role = $request->query->getString('role') ?: null;
        $filterDTO->page = (int) $request->query->get('page', 1);
        $filterDTO->limit = (int) $request->query->get('limit', 20);
        $filterDTO->sortBy = $request->query->getString('sortBy') ?: 'createdAt';
        $filterDTO->sortDirection = $request->query->getString('sortDirection') ?: 'DESC';

        $errors = $this->validator->validate($filterDTO);
        if (\count($errors) > 0) {
            return $this->json(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        $result = $this->adminUserService->listUsers($filterDTO);

        $this->auditLogService->logAdminAccess($admin, 'users_list');

        return $this->json([
            'data' => $result['data'],
            'meta' => [
                'total' => $result['total'],
                'page' => $result['page'],
                'limit' => $result['limit'],
                'totalPages' => $result['totalPages'],
            ],
        ], Response::HTTP_OK, [], ['groups' => ['user:read', 'admin:user:read']]);
    }

    /**
     * Récupère les détails d'un utilisateur.
     */
    #[OA\Get(
        path: '/api/admin/users/{id}',
        summary: 'Récupère les détails d\'un utilisateur',
        security: [['BearerAuth' => []]],
        tags: ['Administration'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), description: 'ID de l\'utilisateur'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Détails de l\'utilisateur'),
            new OA\Response(response: 404, description: 'Utilisateur non trouvé'),
            new OA\Response(response: 429, description: 'Trop de requêtes'),
        ]
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

        $user = $this->adminUserService->getUser($id);

        if (!$user) {
            return $this->json(['error' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($user, Response::HTTP_OK, [], ['groups' => ['user:read', 'admin:user:read']]);
    }

    /**
     * Met à jour un utilisateur.
     */
    #[OA\Put(
        path: '/api/admin/users/{id}',
        summary: 'Met à jour un utilisateur',
        security: [['BearerAuth' => []]],
        tags: ['Administration'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), description: 'ID de l\'utilisateur'),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'pseudo', type: 'string'),
                    new OA\Property(property: 'email', type: 'string'),
                    new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string')),
                    new OA\Property(property: 'isActive', type: 'boolean'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Utilisateur mis à jour'),
            new OA\Response(response: 400, description: 'Données invalides'),
            new OA\Response(response: 403, description: 'Modification de son propre compte interdite pour certaines actions'),
            new OA\Response(response: 404, description: 'Utilisateur non trouvé'),
            new OA\Response(response: 429, description: 'Trop de requêtes'),
        ]
    )]
    #[OA\Patch(
        path: '/api/admin/users/{id}',
        summary: 'Met à jour partiellement un utilisateur',
        security: [['BearerAuth' => []]],
        tags: ['Administration'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), description: 'ID de l\'utilisateur'),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'pseudo', type: 'string'),
                    new OA\Property(property: 'email', type: 'string'),
                    new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string')),
                    new OA\Property(property: 'isActive', type: 'boolean'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Utilisateur mis à jour'),
            new OA\Response(response: 400, description: 'Données invalides'),
            new OA\Response(response: 403, description: 'Modification de son propre compte interdite pour certaines actions'),
            new OA\Response(response: 404, description: 'Utilisateur non trouvé'),
            new OA\Response(response: 429, description: 'Trop de requêtes'),
        ]
    )]
    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'])]
    public function update(int $id, Request $request, #[Autowire(service: 'limiter.admin_action_limiter')] RateLimiterFactory $adminActionLimiter): JsonResponse
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

        $user = $this->adminUserService->getUser($id);

        if (!$user) {
            return $this->json(['error' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        // Empêcher la modification de son propre compte pour certaines actions critiques
        if ($user->getId() === $admin->getId()) {
            $data = json_decode($request->getContent(), true);
            if (isset($data['roles']) || isset($data['isActive'])) {
                return $this->json(
                    ['error' => 'Vous ne pouvez pas modifier vos propres rôles ou statut'],
                    Response::HTTP_FORBIDDEN,
                );
            }
        }

        try {
            $dto = $this->serializer->deserialize(
                $request->getContent(),
                UserUpdateDTO::class,
                'json',
            );

            $errors = $this->validator->validate($dto);
            if (\count($errors) > 0) {
                return $this->json(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
            }

            $updatedUser = $this->adminUserService->updateUser($user, $dto, $admin);

            return $this->json($updatedUser, Response::HTTP_OK, [], ['groups' => ['user:read', 'admin:user:read']]);
        } catch (Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Supprime un utilisateur (soft delete).
     */
    #[OA\Delete(
        path: '/api/admin/users/{id}',
        summary: 'Supprime un utilisateur (suppression logique)',
        security: [['BearerAuth' => []]],
        tags: ['Administration'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), description: 'ID de l\'utilisateur'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Utilisateur supprimé avec succès'),
            new OA\Response(response: 403, description: 'Suppression de son propre compte interdite'),
            new OA\Response(response: 404, description: 'Utilisateur non trouvé'),
            new OA\Response(response: 429, description: 'Trop de requêtes'),
        ]
    )]
    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id, #[Autowire(service: 'limiter.admin_action_limiter')] RateLimiterFactory $adminActionLimiter): JsonResponse
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

        $user = $this->adminUserService->getUser($id);

        if (!$user) {
            return $this->json(['error' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        if ($user->getId() === $admin->getId()) {
            return $this->json(
                ['error' => 'Vous ne pouvez pas supprimer votre propre compte'],
                Response::HTTP_FORBIDDEN,
            );
        }

        $this->adminUserService->softDelete($user, $admin);

        return $this->json(['message' => 'Utilisateur supprimé avec succès']);
    }

    /**
     * Restaure un utilisateur supprimé.
     */
    #[OA\Post(
        path: '/api/admin/users/{id}/restore',
        summary: 'Restaure un utilisateur supprimé',
        security: [['BearerAuth' => []]],
        tags: ['Administration'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), description: 'ID de l\'utilisateur'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Utilisateur restauré avec succès'),
            new OA\Response(response: 400, description: 'L\'utilisateur n\'est pas supprimé'),
            new OA\Response(response: 404, description: 'Utilisateur non trouvé'),
            new OA\Response(response: 429, description: 'Trop de requêtes'),
        ]
    )]
    #[Route('/{id}/restore', name: 'restore', methods: ['POST'])]
    public function restore(int $id, #[Autowire(service: 'limiter.admin_action_limiter')] RateLimiterFactory $adminActionLimiter): JsonResponse
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

        $user = $this->adminUserService->getUser($id);

        if (!$user) {
            return $this->json(['error' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        if (!$user->isDeleted()) {
            return $this->json(['error' => 'L\'utilisateur n\'est pas supprimé'], Response::HTTP_BAD_REQUEST);
        }

        $this->adminUserService->restore($user, $admin);

        return $this->json(['message' => 'Utilisateur restauré avec succès']);
    }

    /**
     * Verrouille un compte utilisateur.
     */
    #[OA\Post(
        path: '/api/admin/users/{id}/lock',
        summary: 'Verrouille un compte utilisateur',
        security: [['BearerAuth' => []]],
        tags: ['Administration'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), description: 'ID de l\'utilisateur'),
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'minutes', type: 'integer', description: 'Durée du verrouillage en minutes (0 = indéfini)', example: 60),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Compte verrouillé avec succès'),
            new OA\Response(response: 403, description: 'Verrouillage de son propre compte interdit'),
            new OA\Response(response: 404, description: 'Utilisateur non trouvé'),
            new OA\Response(response: 429, description: 'Trop de requêtes'),
        ]
    )]
    #[Route('/{id}/lock', name: 'lock', methods: ['POST'])]
    public function lock(int $id, Request $request, #[Autowire(service: 'limiter.admin_action_limiter')] RateLimiterFactory $adminActionLimiter): JsonResponse
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

        $user = $this->adminUserService->getUser($id);

        if (!$user) {
            return $this->json(['error' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        if ($user->getId() === $admin->getId()) {
            return $this->json(
                ['error' => 'Vous ne pouvez pas verrouiller votre propre compte'],
                Response::HTTP_FORBIDDEN,
            );
        }

        $data = json_decode($request->getContent(), true);
        $minutes = (int) ($data['minutes'] ?? 0);

        $this->adminUserService->lockAccount($user, $admin, $minutes);

        return $this->json(['message' => 'Compte verrouillé avec succès']);
    }

    /**
     * Déverrouille un compte utilisateur.
     */
    #[OA\Post(
        path: '/api/admin/users/{id}/unlock',
        summary: 'Déverrouille un compte utilisateur',
        security: [['BearerAuth' => []]],
        tags: ['Administration'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), description: 'ID de l\'utilisateur'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Compte déverrouillé avec succès'),
            new OA\Response(response: 400, description: 'Le compte n\'est pas verrouillé'),
            new OA\Response(response: 404, description: 'Utilisateur non trouvé'),
            new OA\Response(response: 429, description: 'Trop de requêtes'),
        ]
    )]
    #[Route('/{id}/unlock', name: 'unlock', methods: ['POST'])]
    public function unlock(int $id, #[Autowire(service: 'limiter.admin_action_limiter')] RateLimiterFactory $adminActionLimiter): JsonResponse
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

        $user = $this->adminUserService->getUser($id);

        if (!$user) {
            return $this->json(['error' => 'Utilisateur non trouvé'], Response::HTTP_NOT_FOUND);
        }

        if (!$user->isLocked()) {
            return $this->json(['error' => 'Le compte n\'est pas verrouillé'], Response::HTTP_BAD_REQUEST);
        }

        $this->adminUserService->unlockAccount($user, $admin);

        return $this->json(['message' => 'Compte déverrouillé avec succès']);
    }

    /**
     * Récupère les statistiques des utilisateurs.
     */
    #[OA\Get(
        path: '/api/admin/users/statistics',
        summary: 'Récupère les statistiques des utilisateurs',
        security: [['BearerAuth' => []]],
        tags: ['Administration'],
        responses: [
            new OA\Response(response: 200, description: 'Statistiques des utilisateurs'),
            new OA\Response(response: 429, description: 'Trop de requêtes'),
        ]
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

        $stats = $this->adminUserService->getStatistics();

        return $this->json($stats);
    }
}
