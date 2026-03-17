<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\GameRepository;
use App\Service\PresenceService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur pour gérer la présence en temps réel des joueurs.
 */
#[Route('/api/games/{gameId}/presence', name: 'api_presence_')]
#[IsGranted('ROLE_USER')]
final class PresenceController extends AbstractController
{
    public function __construct(
        private readonly PresenceService $presenceService,
        private readonly GameRepository $gameRepository,
    ) {
    }

    /**
     * Notifie que l'utilisateur a rejoint la partie.
     */
    #[OA\Post(
        path: '/api/games/{gameId}/presence/join',
        summary: 'Notifie l\'arrivée de l\'utilisateur dans la partie',
        security: [['BearerAuth' => []]],
        tags: ['Présence'],
        parameters: [
            new OA\Parameter(name: 'gameId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Présence enregistrée'),
            new OA\Response(response: 403, description: 'Accès refusé'),
            new OA\Response(response: 404, description: 'Partie introuvable'),
        ],
    )]
    #[Route('/join', name: 'join', methods: ['POST'])]
    public function join(int $gameId): JsonResponse
    {
        $game = $this->gameRepository->find($gameId);

        if (!$game) {
            return $this->json(
                ['error' => 'Partie introuvable'],
                Response::HTTP_NOT_FOUND,
            );
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$game->canBeViewedBy($user)) {
            return $this->json(
                ['error' => 'Accès refusé'],
                Response::HTTP_FORBIDDEN,
            );
        }

        $this->presenceService->userJoined($game, $user);

        return $this->json([
            'success' => true,
            'onlineUsers' => $this->presenceService->getOnlineUserIds($gameId),
        ], Response::HTTP_OK);
    }

    /**
     * Notifie que l'utilisateur a quitté la partie.
     */
    #[OA\Post(
        path: '/api/games/{gameId}/presence/leave',
        summary: 'Notifie le départ de l\'utilisateur',
        security: [['BearerAuth' => []]],
        tags: ['Présence'],
        parameters: [
            new OA\Parameter(name: 'gameId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Départ enregistré'),
            new OA\Response(response: 404, description: 'Partie introuvable'),
        ],
    )]
    #[Route('/leave', name: 'leave', methods: ['POST'])]
    public function leave(int $gameId): JsonResponse
    {
        $game = $this->gameRepository->find($gameId);

        if (!$game) {
            return $this->json(
                ['error' => 'Partie introuvable'],
                Response::HTTP_NOT_FOUND,
            );
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $this->presenceService->userLeft($game, $user);

        return $this->json([
            'success' => true,
        ], Response::HTTP_OK);
    }

    /**
     * Heartbeat pour indiquer que l'utilisateur est toujours actif.
     * À appeler régulièrement (toutes les 30 secondes).
     */
    #[OA\Post(
        path: '/api/games/{gameId}/presence/heartbeat',
        summary: 'Heartbeat de présence (toutes les 30s)',
        security: [['BearerAuth' => []]],
        tags: ['Présence'],
        parameters: [
            new OA\Parameter(name: 'gameId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Heartbeat enregistré avec liste des utilisateurs en ligne'),
            new OA\Response(response: 403, description: 'Accès refusé'),
            new OA\Response(response: 404, description: 'Partie introuvable'),
        ],
    )]
    #[Route('/heartbeat', name: 'heartbeat', methods: ['POST'])]
    public function heartbeat(int $gameId): JsonResponse
    {
        $game = $this->gameRepository->find($gameId);

        if (!$game) {
            return $this->json(
                ['error' => 'Partie introuvable'],
                Response::HTTP_NOT_FOUND,
            );
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$game->canBeViewedBy($user)) {
            return $this->json(
                ['error' => 'Accès refusé'],
                Response::HTTP_FORBIDDEN,
            );
        }

        $this->presenceService->heartbeat($game, $user);

        return $this->json([
            'success' => true,
            'onlineUsers' => $this->presenceService->getOnlineUserIds($gameId),
            'onlineCount' => $this->presenceService->getOnlineCount($gameId),
        ], Response::HTTP_OK);
    }

    /**
     * Récupère la liste des utilisateurs actuellement en ligne dans la partie.
     */
    #[OA\Get(
        path: '/api/games/{gameId}/presence/online',
        summary: 'Liste des utilisateurs en ligne dans la partie',
        security: [['BearerAuth' => []]],
        tags: ['Présence'],
        parameters: [
            new OA\Parameter(name: 'gameId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Utilisateurs en ligne'),
            new OA\Response(response: 403, description: 'Accès refusé'),
            new OA\Response(response: 404, description: 'Partie introuvable'),
        ],
    )]
    #[Route('/online', name: 'online', methods: ['GET'])]
    public function getOnlineUsers(int $gameId): JsonResponse
    {
        $game = $this->gameRepository->find($gameId);

        if (!$game) {
            return $this->json(
                ['error' => 'Partie introuvable'],
                Response::HTTP_NOT_FOUND,
            );
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$game->canBeViewedBy($user)) {
            return $this->json(
                ['error' => 'Accès refusé'],
                Response::HTTP_FORBIDDEN,
            );
        }

        return $this->json([
            'onlineUsers' => $this->presenceService->getOnlineUserIds($gameId),
            'onlineCount' => $this->presenceService->getOnlineCount($gameId),
        ], Response::HTTP_OK);
    }
}
