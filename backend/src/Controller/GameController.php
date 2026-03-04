<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\Game\CreateGameDTO;
use App\DTO\Game\GameFilterDTO;
use App\DTO\Game\JoinGameDTO;
use App\DTO\Game\UpdateGameDTO;
use App\Enum\GameStatus;
use App\Repository\GamePlayerRepository;
use App\Repository\GameRepository;
use App\Repository\UserRepository;
use App\Service\GameService;
use App\Service\MercureTokenService;
use Exception;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Contrôleur de gestion des parties de jeu.
 */
#[Route('/api/games', name: 'api_game_')]
final class GameController extends AbstractController
{
    public function __construct(
        private readonly GameService $gameService,
        private readonly GameRepository $gameRepository,
        private readonly GamePlayerRepository $gamePlayerRepository,
        private readonly UserRepository $userRepository,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
        private readonly MercureTokenService $mercureTokenService,
    ) {
    }

    /**
     * Liste toutes les parties publiques avec filtres et pagination.
     */
    #[OA\Get(
        path: '/api/games',
        summary: 'Liste les parties publiques avec filtres et pagination',
        security: [['BearerAuth' => []]],
        tags: ['Parties'],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'title', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'gameMaster', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['waiting', 'in_progress', 'paused', 'finished', 'archived'])),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 12)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Liste des parties paginée'),
            new OA\Response(response: 400, description: 'Paramètres invalides'),
        ],
    )]
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $filterDTO = new GameFilterDTO();
        $filterDTO->search = $request->query->getString('search') ?: null;
        $filterDTO->title = $request->query->getString('title') ?: null;
        $filterDTO->gameMaster = $request->query->getString('gameMaster') ?: null;
        $statusString = $request->query->getString('status');
        $filterDTO->status = $statusString ? GameStatus::tryFrom($statusString) : null;
        $filterDTO->page = (int) $request->query->get('page', 1);
        $filterDTO->limit = (int) $request->query->get('limit', 12);

        $errors = $this->validator->validate($filterDTO);
        if (\count($errors) > 0) {
            return $this->json(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        $result = $this->gameRepository->findPublicGamesWithFilters($filterDTO);

        return $this->json([
            'data' => $result['data'],
            'meta' => [
                'total' => $result['total'],
                'page' => $result['page'],
                'limit' => $result['limit'],
                'totalPages' => $result['totalPages'],
            ],
        ], Response::HTTP_OK, [], ['groups' => 'game:list']);
    }

    /**
     * Liste les parties de l'utilisateur connecté.
     */
    #[OA\Get(
        path: '/api/games/my-games',
        summary: 'Liste les parties de l\'utilisateur connecté',
        security: [['BearerAuth' => []]],
        tags: ['Parties'],
        responses: [
            new OA\Response(response: 200, description: 'Liste des parties de l\'utilisateur'),
        ],
    )]
    #[Route('/my-games', name: 'my_games', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function myGames(): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $games = $this->gameRepository->findUserGames($user);

        return $this->json($games, Response::HTTP_OK, [], ['groups' => 'game:list']);
    }

    /**
     * Détails d'une partie.
     */
    #[OA\Get(
        path: '/api/games/{id}',
        summary: 'Détails d\'une partie',
        security: [['BearerAuth' => []]],
        tags: ['Parties'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Détails de la partie'),
            new OA\Response(response: 403, description: 'Accès refusé'),
            new OA\Response(response: 404, description: 'Partie introuvable'),
        ],
    )]
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function show(int $id): JsonResponse
    {
        $game = $this->gameRepository->findGameWithPlayers($id);

        if (!$game) {
            return $this->json(['error' => 'Partie introuvable'], Response::HTTP_NOT_FOUND);
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$game->canBeViewedBy($user)) {
            return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        return $this->json($game, Response::HTTP_OK, [], ['groups' => 'game:read']);
    }

    /**
     * Créer une nouvelle partie.
     */
    #[OA\Post(
        path: '/api/games',
        summary: 'Créer une nouvelle partie',
        security: [['BearerAuth' => []]],
        tags: ['Parties'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title'],
                properties: [
                    new OA\Property(property: 'title', type: 'string'),
                    new OA\Property(property: 'description', type: 'string'),
                    new OA\Property(property: 'maxPlayers', type: 'integer'),
                    new OA\Property(property: 'isPublic', type: 'boolean'),
                    new OA\Property(property: 'password', type: 'string'),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 201, description: 'Partie créée'),
            new OA\Response(response: 400, description: 'Données invalides'),
        ],
    )]
    #[Route('', name: 'create', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(Request $request): JsonResponse
    {
        try {
            $dto = $this->serializer->deserialize(
                $request->getContent(),
                CreateGameDTO::class,
                'json',
            );
        }
        catch (\Symfony\Component\Serializer\Exception\NotNormalizableValueException $e) {
            return $this->json(['error' => 'Invalid data: ' . $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        $errors = $this->validator->validate($dto);
        if (\count($errors) > 0) {
            return $this->json(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$user->isVerified()) {
            return $this->json(
                ['error' => 'Vérifiez votre adresse email avant de créer une partie.', 'code' => 'email_not_verified'],
                Response::HTTP_FORBIDDEN,
            );
        }

        try {
            $game = $this->gameService->createGame($dto, $user);

            return $this->json($game, Response::HTTP_CREATED, [], ['groups' => 'game:read']);
        }
        catch (InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
        catch (Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Mettre à jour une partie.
     */
    #[OA\Put(
        path: '/api/games/{id}',
        summary: 'Mettre à jour une partie',
        security: [['BearerAuth' => []]],
        tags: ['Parties'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'title', type: 'string'),
                    new OA\Property(property: 'description', type: 'string'),
                    new OA\Property(property: 'maxPlayers', type: 'integer'),
                    new OA\Property(property: 'isPublic', type: 'boolean'),
                    new OA\Property(property: 'status', type: 'string'),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Partie mise à jour'),
            new OA\Response(response: 403, description: 'Accès refusé'),
            new OA\Response(response: 404, description: 'Partie introuvable'),
        ],
    )]
    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'])]
    #[IsGranted('ROLE_USER')]
    public function update(int $id, Request $request): JsonResponse
    {
        $game = $this->gameRepository->find($id);

        if (!$game) {
            return $this->json(['error' => 'Partie introuvable'], Response::HTTP_NOT_FOUND);
        }

        $dto = $this->serializer->deserialize(
            $request->getContent(),
            UpdateGameDTO::class,
            'json',
        );

        $errors = $this->validator->validate($dto);
        if (\count($errors) > 0) {
            return $this->json(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        try {
            $game = $this->gameService->updateGame($game, $dto, $user);

            return $this->json($game, Response::HTTP_OK, [], ['groups' => 'game:read']);
        }
        catch (Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_FORBIDDEN);
        }
    }

    /**
     * Rejoindre une partie par code d'invitation
     * Endpoint dédié pour rejoindre avec un code plutôt qu'un ID
     */
    #[OA\Post(
        path: '/api/games/join',
        summary: 'Rejoindre une partie par code d\'invitation',
        security: [['BearerAuth' => []]],
        tags: ['Parties'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['inviteCode'],
                properties: [
                    new OA\Property(property: 'inviteCode', type: 'string'),
                    new OA\Property(property: 'password', type: 'string'),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Partie rejointe'),
            new OA\Response(response: 400, description: 'Code requis'),
            new OA\Response(response: 404, description: 'Code invalide'),
        ],
    )]
    #[Route('/join', name: 'join_by_code', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function joinByCode(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $inviteCode = $data['inviteCode'] ?? null;
        $password = $data['password'] ?? null;

        if (!$inviteCode) {
            return $this->json(
                ['error' => 'Code d\'invitation requis'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        $game = $this->gameRepository->findByInviteCode($inviteCode);

        if (!$game) {
            return $this->json(
                ['error' => 'Code d\'invitation invalide'],
                Response::HTTP_NOT_FOUND,
            );
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$user->isVerified()) {
            return $this->json(
                ['error' => 'Vérifiez votre adresse email avant de rejoindre une partie.', 'code' => 'email_not_verified'],
                Response::HTTP_FORBIDDEN,
            );
        }

        try {
            $gameId = $game->getId();
            if (null === $gameId) {
                return $this->json(['error' => 'ID de partie invalide'], Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $gamePlayer = $this->gameService->joinGame($gameId, $user, $password);

            return $this->json(
                $gamePlayer,
                Response::HTTP_OK,
                [],
                ['groups' => 'game:read'],
            );
        }
        catch (Exception $e) {
            return $this->json(['error' => $e->getMessage()], $e->getCode() ?: Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Rejoindre une partie par ID.
     */
    #[OA\Post(
        path: '/api/games/{id}/join',
        summary: 'Rejoindre une partie par ID',
        security: [['BearerAuth' => []]],
        tags: ['Parties'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'password', type: 'string'),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Partie rejointe'),
            new OA\Response(response: 400, description: 'Erreur'),
        ],
    )]
    #[Route('/{id}/join', name: 'join', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function join(int $id, Request $request): JsonResponse
    {
        $dto = $this->serializer->deserialize(
            $request->getContent(),
            JoinGameDTO::class,
            'json',
        );

        $errors = $this->validator->validate($dto);
        if (\count($errors) > 0) {
            return $this->json(['errors' => (string) $errors], Response::HTTP_BAD_REQUEST);
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$user->isVerified()) {
            return $this->json(
                ['error' => 'Vérifiez votre adresse email avant de rejoindre une partie.', 'code' => 'email_not_verified'],
                Response::HTTP_FORBIDDEN,
            );
        }

        try {
            $gamePlayer = $this->gameService->joinGame($id, $user, $dto->password);

            return $this->json($gamePlayer, Response::HTTP_OK, [], ['groups' => 'game:read']);
        }
        catch (Exception $e) {
            return $this->json(['error' => $e->getMessage()], $e->getCode());
        }
    }

    /**
     * Quitter une partie.
     */
    #[OA\Post(
        path: '/api/games/{id}/leave',
        summary: 'Quitter une partie',
        security: [['BearerAuth' => []]],
        tags: ['Parties'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Partie quittée'),
            new OA\Response(response: 404, description: 'Partie introuvable'),
        ],
    )]
    #[Route('/{id}/leave', name: 'leave', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function leave(int $id): JsonResponse
    {
        $game = $this->gameRepository->find($id);

        if (!$game) {
            return $this->json(['error' => 'Partie introuvable'], Response::HTTP_NOT_FOUND);
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        try {
            $this->gameService->leaveGame($game, $user);

            return $this->json(['message' => 'Vous avez quitté la partie'], Response::HTTP_OK);
        }
        catch (Exception $e) {
            return $this->json(['error' => $e->getMessage()], $e->getCode());
        }
    }

    /**
     * Supprimer/Archiver une partie.
     */
    #[OA\Delete(
        path: '/api/games/{id}',
        summary: 'Supprimer/archiver une partie',
        security: [['BearerAuth' => []]],
        tags: ['Parties'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Partie archivée'),
            new OA\Response(response: 404, description: 'Partie introuvable'),
        ],
    )]
    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function delete(int $id): JsonResponse
    {
        $game = $this->gameRepository->find($id);

        if (!$game) {
            return $this->json(['error' => 'Partie introuvable'], Response::HTTP_NOT_FOUND);
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        try {
            $this->gameService->deleteGame($game, $user);

            return $this->json(['message' => 'Partie archivée avec succès'], Response::HTTP_OK);
        }
        catch (Exception $e) {
            return $this->json(['error' => $e->getMessage()], $e->getCode());
        }
    }

    /**
     * Inviter un utilisateur à rejoindre une partie (MJ uniquement).
     */
    #[Route('/{id}/invite', name: 'invite', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function invite(int $id, Request $request): JsonResponse
    {
        $game = $this->gameRepository->find($id);

        if (!$game) {
            return $this->json(['error' => 'Partie introuvable'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);
        $targetUserId = $data['userId'] ?? null;

        if (!$targetUserId) {
            return $this->json(['error' => 'userId requis'], Response::HTTP_BAD_REQUEST);
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $targetUser = $this->userRepository->find($targetUserId);

        if (!$targetUser) {
            return $this->json(['error' => 'Utilisateur introuvable'], Response::HTTP_NOT_FOUND);
        }

        try {
            $this->gameService->invitePlayer($game, $user, $targetUser);

            return $this->json(['message' => 'Invitation envoyée'], Response::HTTP_OK);
        }
        catch (Exception $e) {
            return $this->json(['error' => $e->getMessage()], $e->getCode() ?: Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Accepter une invitation à rejoindre une partie.
     */
    #[Route('/{id}/accept-invitation', name: 'accept_invitation', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function acceptInvitation(int $id): JsonResponse
    {
        $game = $this->gameRepository->find($id);

        if (!$game) {
            return $this->json(['error' => 'Partie introuvable'], Response::HTTP_NOT_FOUND);
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        try {
            $this->gameService->acceptInvitation($game, $user);

            return $this->json(['message' => 'Invitation acceptée'], Response::HTTP_OK);
        }
        catch (Exception $e) {
            return $this->json(['error' => $e->getMessage()], $e->getCode() ?: Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Refuser une invitation à rejoindre une partie.
     */
    #[Route('/{id}/decline-invitation', name: 'decline_invitation', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function declineInvitation(int $id): JsonResponse
    {
        $game = $this->gameRepository->find($id);

        if (!$game) {
            return $this->json(['error' => 'Partie introuvable'], Response::HTTP_NOT_FOUND);
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        try {
            $this->gameService->declineInvitation($game, $user);

            return $this->json(['message' => 'Invitation refusée'], Response::HTTP_OK);
        }
        catch (Exception $e) {
            return $this->json(['error' => $e->getMessage()], $e->getCode() ?: Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Expulser un joueur de la partie (MJ uniquement).
     */
    #[Route('/{id}/players/{playerId}/kick', name: 'kick_player', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function kickPlayer(int $id, int $playerId): JsonResponse
    {
        $game = $this->gameRepository->find($id);

        if (!$game) {
            return $this->json(['error' => 'Partie introuvable'], Response::HTTP_NOT_FOUND);
        }

        $gamePlayer = $this->gamePlayerRepository->find($playerId);

        if (!$gamePlayer || $gamePlayer->getGame()?->getId() !== $id) {
            return $this->json(['error' => 'Joueur introuvable dans cette partie'], Response::HTTP_NOT_FOUND);
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        try {
            $this->gameService->kickPlayer($game, $user, $gamePlayer);

            return $this->json(['message' => 'Joueur expulsé'], Response::HTTP_OK);
        }
        catch (Exception $e) {
            return $this->json(['error' => $e->getMessage()], $e->getCode() ?: Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Obtenir un token JWT Mercure pour s'abonner aux événements de la partie.
     */
    #[OA\Get(
        path: '/api/games/{id}/mercure-token',
        summary: 'Obtenir un token Mercure pour les événements de la partie',
        security: [['BearerAuth' => []]],
        tags: ['Parties'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Token Mercure généré'),
            new OA\Response(response: 403, description: 'Accès refusé'),
            new OA\Response(response: 404, description: 'Partie introuvable'),
        ],
    )]
    #[Route('/{id}/mercure-token', name: 'mercure_token', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getMercureToken(int $id): JsonResponse
    {
        $game = $this->gameRepository->find($id);

        if (!$game) {
            return $this->json(['error' => 'Partie introuvable'], Response::HTTP_NOT_FOUND);
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$game->canBeViewedBy($user)) {
            return $this->json(['error' => 'Accès refusé'], Response::HTTP_FORBIDDEN);
        }

        $token = $this->mercureTokenService->generateTokenForGame($user, $id);

        $response = $this->json([
            'token' => $token,
            'expiresIn' => 3600,
        ], Response::HTTP_OK);

        $response->headers->setCookie(
            new \Symfony\Component\HttpFoundation\Cookie(
                'mercureAuthorization',
                $token,
                time() + 3600,
                '/.well-known/mercure',
                null,
                true,
                false,
                false,
                'none',
            ),
        );

        return $response;
    }

    /**
     * Obtenir un token JWT Mercure pour s'abonner aux événements de présence de plusieurs parties.
     */
    #[OA\Post(
        path: '/api/games/mercure-presence-token',
        summary: 'Obtenir un token Mercure pour les événements de présence',
        security: [['BearerAuth' => []]],
        tags: ['Parties'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['gameIds'],
                properties: [
                    new OA\Property(property: 'gameIds', type: 'array', items: new OA\Items(type: 'integer')),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Token Mercure généré'),
            new OA\Response(response: 400, description: 'gameIds requis'),
        ],
    )]
    #[Route('/mercure-presence-token', name: 'mercure_presence_token', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function getMercurePresenceToken(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $gameIds = $data['gameIds'] ?? [];

        if (empty($gameIds) || !\is_array($gameIds)) {
            return $this->json(['error' => 'gameIds requis'], Response::HTTP_BAD_REQUEST);
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $token = $this->mercureTokenService->generateTokenForPresence($user, $gameIds);

        $response = $this->json([
            'token' => $token,
            'expiresIn' => 3600,
        ], Response::HTTP_OK);

        $response->headers->setCookie(
            new \Symfony\Component\HttpFoundation\Cookie(
                'mercureAuthorization',
                $token,
                time() + 3600,
                '/.well-known/mercure',
                null,
                true,
                false,
                false,
                'none',
            ),
        );

        return $response;
    }
}
