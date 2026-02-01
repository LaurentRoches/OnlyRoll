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
