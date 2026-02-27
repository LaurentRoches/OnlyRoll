<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\Map\CreateMapDTO;
use App\DTO\Map\UpdateMapDTO;
use App\Enum\MapGridType;
use App\Repository\GameMapRepository;
use App\Repository\GameRepository;
use App\Service\FileUploader;
use App\Service\MapService;
use Doctrine\ORM\EntityManagerInterface;
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
 * Contrôleur de gestion des cartes de jeu.
 */
#[Route('/api/games/{gameId}/maps', name: 'api_map_')]
#[IsGranted('ROLE_USER')]
final class MapController extends AbstractController
{
    public function __construct(
        private readonly MapService $mapService,
        private readonly GameRepository $gameRepository,
        private readonly GameMapRepository $mapRepository,
        private readonly FileUploader $fileUploader,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Parse multipart/form-data for PUT/PATCH requests.
     * PHP only automatically parses multipart data for POST requests.
     */
    private function parseMultipartFormData(Request $request): ?JsonResponse
    {
        $contentType = $request->headers->get('Content-Type') ?? '';

        if (!preg_match('/boundary=(.+)$/i', $contentType, $matches)) {
            return null;
        }

        $boundary = trim($matches[1]);
        $rawData = $request->getContent();

        $parts = preg_split('/--' . preg_quote($boundary, '/') . '/', $rawData);

        if (false === $parts) {
            return $this->json(['error' => 'Erreur lors du parsing du contenu multipart'], Response::HTTP_BAD_REQUEST);
        }

        foreach ($parts as $part) {
            $part = trim($part);
            if (empty($part) || $part === '--') {
                continue;
            }

            $divider = str_contains($part, "\r\n\r\n") ? "\r\n\r\n" : "\n\n";
            $sections = explode($divider, $part, 2);

            if (\count($sections) < 2) {
                continue;
            }

            [$headers, $body] = $sections;

            if (preg_match('/name="([^"]+)"/', $headers, $nameMatch)) {
                $name = $nameMatch[1];
                $value = rtrim($body, "\r\n");

                $request->request->set($name, $value);
            }
        }

        return null;
    }

    /**
     * Liste toutes les cartes d'une partie.
     */
    #[OA\Get(
        path: '/api/games/{gameId}/maps',
        summary: 'Liste toutes les cartes d\'une partie',
        security: [['BearerAuth' => []]],
        tags: ['Cartes'],
        parameters: [
            new OA\Parameter(name: 'gameId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Liste des cartes'),
            new OA\Response(response: 403, description: 'Accès refusé'),
            new OA\Response(response: 404, description: 'Partie introuvable'),
        ],
    )]
    #[Route('', name: 'list', methods: ['GET'])]
    public function list(int $gameId): JsonResponse
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

        $maps = $this->mapService->getMapsByGame($game);

        return $this->json(
            $maps,
            Response::HTTP_OK,
            [],
            ['groups' => 'map:list'],
        );
    }

    /**
     * Récupère la carte active d'une partie.
     */
    #[OA\Get(
        path: '/api/games/{gameId}/maps/active',
        summary: 'Récupère la carte active d\'une partie',
        security: [['BearerAuth' => []]],
        tags: ['Cartes'],
        parameters: [
            new OA\Parameter(name: 'gameId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Carte active'),
            new OA\Response(response: 404, description: 'Aucune carte active ou partie introuvable'),
        ],
    )]
    #[Route('/active', name: 'active', methods: ['GET'])]
    public function getActive(int $gameId): JsonResponse
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

        $activeMap = $this->mapService->getActiveMap($game);

        if (!$activeMap) {
            return $this->json(
                ['error' => 'Aucune carte active'],
                Response::HTTP_NOT_FOUND,
            );
        }

        return $this->json($activeMap, Response::HTTP_OK, [], ['groups' => 'map:read']);
    }

    /**
     * Détails d'une carte.
     */
    #[OA\Get(
        path: '/api/games/{gameId}/maps/{id}',
        summary: 'Détails d\'une carte',
        security: [['BearerAuth' => []]],
        tags: ['Cartes'],
        parameters: [
            new OA\Parameter(name: 'gameId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Détails de la carte'),
            new OA\Response(response: 403, description: 'Accès refusé'),
            new OA\Response(response: 404, description: 'Carte introuvable'),
        ],
    )]
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $gameId, int $id): JsonResponse
    {
        $game = $this->gameRepository->find($gameId);

        if (!$game) {
            return $this->json(
                ['error' => 'Partie introuvable'],
                Response::HTTP_NOT_FOUND,
            );
        }

        $map = $this->mapRepository->find($id);

        $mapGame = $map?->getGame();
        if (!$map || !$mapGame || $mapGame->getId() !== $gameId) {
            return $this->json(
                ['error' => 'Carte introuvable'],
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

        return $this->json(
            $map,
            Response::HTTP_OK,
            [],
            ['groups' => 'map:read'],
        );
    }

    /**
     * Créer une nouvelle carte avec upload d'image.
     * Supporte à la fois JSON et multipart/form-data.
     */
    #[OA\Post(
        path: '/api/games/{gameId}/maps',
        summary: 'Créer une nouvelle carte (MJ uniquement)',
        security: [['BearerAuth' => []]],
        tags: ['Cartes'],
        parameters: [
            new OA\Parameter(name: 'gameId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name'],
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'description', type: 'string'),
                    new OA\Property(property: 'gridSize', type: 'integer', default: 50),
                    new OA\Property(property: 'gridType', type: 'string', default: 'square'),
                    new OA\Property(property: 'width', type: 'integer', default: 20),
                    new OA\Property(property: 'height', type: 'integer', default: 20),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 201, description: 'Carte créée'),
            new OA\Response(response: 400, description: 'Données invalides'),
            new OA\Response(response: 403, description: 'Réservé au MJ'),
        ],
    )]
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(int $gameId, Request $request): JsonResponse
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

        if (!$game->isGameMaster($user)) {
            return $this->json(
                ['error' => 'Seul le maître du jeu peut créer des cartes'],
                Response::HTTP_FORBIDDEN,
            );
        }

        try {
            $imageUrl = null;
            $imageFile = $request->files->get('image');

            if ($imageFile) {
                $imageUrl = $this->fileUploader->uploadMapImage($imageFile);
            }

            $contentType = $request->headers->get('Content-Type') ?? '';

            if (str_contains($contentType, 'multipart/form-data')) {
                $dto = new CreateMapDTO();

                $name = $request->request->get('name');
                if (!\is_string($name)) {
                    return $this->json(
                        ['error' => 'Le nom est requis et doit être une chaîne de caractères'],
                        Response::HTTP_BAD_REQUEST,
                    );
                }
                $dto->name = $name;

                $description = $request->request->get('description');
                $dto->description = \is_string($description) ? $description : null;

                $dto->gridSize = (int) $request->request->get('gridSize', 50);

                $gridTypeString = $request->request->get('gridType', 'square');
                $dto->gridType = \is_string($gridTypeString) ? (MapGridType::tryFrom($gridTypeString) ?? MapGridType::SQUARE) : MapGridType::SQUARE;

                $dto->width = (int) $request->request->get('width', 20);
                $dto->height = (int) $request->request->get('height', 20);

                $dto->imageUrl = $imageUrl;
            }
            else {
                $dto = $this->serializer->deserialize(
                    $request->getContent(),
                    CreateMapDTO::class,
                    'json',
                );

                if ($imageUrl) {
                    $dto->imageUrl = $imageUrl;
                }
            }

            $errors = $this->validator->validate($dto);
            if (\count($errors) > 0) {
                return $this->json(
                    ['errors' => (string) $errors],
                    Response::HTTP_BAD_REQUEST,
                );
            }

            $map = $this->mapService->createMap($game, $dto);

            return $this->json(
                $map,
                Response::HTTP_CREATED,
                [],
                ['groups' => 'map:read'],
            );
        }
        catch (InvalidArgumentException $e) {
            return $this->json(
                ['error' => $e->getMessage()],
                Response::HTTP_BAD_REQUEST,
            );
        }
        catch (Exception $e) {
            return $this->json(
                ['error' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }

    /**
     * Mettre à jour une carte.
     */
    #[OA\Put(
        path: '/api/games/{gameId}/maps/{id}',
        summary: 'Mettre à jour une carte (MJ uniquement)',
        security: [['BearerAuth' => []]],
        tags: ['Cartes'],
        parameters: [
            new OA\Parameter(name: 'gameId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'description', type: 'string'),
                    new OA\Property(property: 'gridSize', type: 'integer'),
                    new OA\Property(property: 'gridType', type: 'string'),
                    new OA\Property(property: 'width', type: 'integer'),
                    new OA\Property(property: 'height', type: 'integer'),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Carte mise à jour'),
            new OA\Response(response: 403, description: 'Réservé au MJ'),
            new OA\Response(response: 404, description: 'Carte introuvable'),
        ],
    )]
    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'])]
    public function update(int $gameId, int $id, Request $request): JsonResponse
    {
        $game = $this->gameRepository->find($gameId);

        if (!$game) {
            return $this->json(
                ['error' => 'Partie introuvable'],
                Response::HTTP_NOT_FOUND,
            );
        }

        $map = $this->mapRepository->find($id);

        $mapGame = $map?->getGame();
        if (!$map || !$mapGame || $mapGame->getId() !== $gameId) {
            return $this->json(
                ['error' => 'Carte introuvable'],
                Response::HTTP_NOT_FOUND,
            );
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$game->isGameMaster($user)) {
            return $this->json(
                ['error' => 'Seul le maître du jeu peut modifier des cartes'],
                Response::HTTP_FORBIDDEN,
            );
        }

        try {
            $imageUrl = null;
            $imageFile = $request->files->get('image');

            if ($imageFile) {
                if ($map->getImageUrl()) {
                    $this->fileUploader->deleteFile($map->getImageUrl());
                }

                $imageUrl = $this->fileUploader->uploadMapImage($imageFile);
            }

            $contentType = $request->headers->get('Content-Type') ?? '';

            if (str_contains($contentType, 'multipart/form-data')) {
                $dto = new UpdateMapDTO();

                if (\in_array($request->getMethod(), ['PUT', 'PATCH'], true)) {
                    $this->parseMultipartFormData($request);
                }

                $name = $request->request->get('name');
                if (\is_string($name) && !empty($name)) {
                    $dto->name = $name;
                }

                $description = $request->request->get('description');
                if (\is_string($description)) {
                    $dto->description = $description;
                }

                $gridSize = $request->request->get('gridSize');
                if (null !== $gridSize) {
                    $dto->gridSize = (int) $gridSize;
                }

                $gridTypeString = $request->request->get('gridType');
                if (\is_string($gridTypeString)) {
                    $dto->gridType = MapGridType::tryFrom($gridTypeString);
                }

                $width = $request->request->get('width');
                if (null !== $width) {
                    $dto->width = (int) $width;
                }

                $height = $request->request->get('height');
                if (null !== $height) {
                    $dto->height = (int) $height;
                }

                if ($imageUrl) {
                    $dto->imageUrl = $imageUrl;
                }
            }
            else {
                $dto = $this->serializer->deserialize(
                    $request->getContent(),
                    UpdateMapDTO::class,
                    'json',
                );

                if (isset($imageUrl)) {
                    $dto->imageUrl = $imageUrl;
                }
            }

            $errors = $this->validator->validate($dto);
            if (\count($errors) > 0) {
                return $this->json(
                    ['errors' => (string) $errors],
                    Response::HTTP_BAD_REQUEST,
                );
            }

            $map = $this->mapService->updateMap($map, $dto);

            return $this->json(
                $map,
                Response::HTTP_OK,
                [],
                ['groups' => 'map:read'],
            );
        }
        catch (Exception $e) {
            return $this->json(
                ['error' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }

    /**
     * Activer une carte.
     */
    #[OA\Post(
        path: '/api/games/{gameId}/maps/{id}/activate',
        summary: 'Activer une carte (MJ uniquement)',
        security: [['BearerAuth' => []]],
        tags: ['Cartes'],
        parameters: [
            new OA\Parameter(name: 'gameId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Carte activée'),
            new OA\Response(response: 403, description: 'Réservé au MJ'),
            new OA\Response(response: 404, description: 'Carte introuvable'),
        ],
    )]
    #[Route('/{id}/activate', name: 'activate', methods: ['POST'])]
    public function activate(int $gameId, int $id): JsonResponse
    {
        $game = $this->gameRepository->find($gameId);

        if (!$game) {
            return $this->json(
                ['error' => 'Partie introuvable'],
                Response::HTTP_NOT_FOUND,
            );
        }

        $map = $this->mapRepository->find($id);

        $mapGame = $map?->getGame();
        if (!$map || !$mapGame || $mapGame->getId() !== $gameId) {
            return $this->json(
                ['error' => 'Carte introuvable'],
                Response::HTTP_NOT_FOUND,
            );
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$game->isGameMaster($user)) {
            return $this->json(
                ['error' => 'Seul le maître du jeu peut activer des cartes'],
                Response::HTTP_FORBIDDEN,
            );
        }

        try {
            $map = $this->mapService->activateMap($map);

            return $this->json(
                $map,
                Response::HTTP_OK,
                [],
                ['groups' => 'map:read'],
            );
        }
        catch (Exception $e) {
            return $this->json(
                ['error' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }

    /**
     * Supprimer une carte.
     */
    #[OA\Delete(
        path: '/api/games/{gameId}/maps/{id}',
        summary: 'Supprimer une carte (MJ uniquement)',
        security: [['BearerAuth' => []]],
        tags: ['Cartes'],
        parameters: [
            new OA\Parameter(name: 'gameId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Carte supprimée'),
            new OA\Response(response: 403, description: 'Réservé au MJ'),
            new OA\Response(response: 404, description: 'Carte introuvable'),
        ],
    )]
    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $gameId, int $id): JsonResponse
    {
        $game = $this->gameRepository->find($gameId);

        if (!$game) {
            return $this->json(
                ['error' => 'Partie introuvable'],
                Response::HTTP_NOT_FOUND,
            );
        }

        $map = $this->mapRepository->find($id);

        $mapGame = $map?->getGame();
        if (!$map || !$mapGame || $mapGame->getId() !== $gameId) {
            return $this->json(
                ['error' => 'Carte introuvable'],
                Response::HTTP_NOT_FOUND,
            );
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$game->isGameMaster($user)) {
            return $this->json(
                ['error' => 'Seul le maître du jeu peut supprimer des cartes'],
                Response::HTTP_FORBIDDEN,
            );
        }

        try {
            if ($map->getImageUrl()) {
                $this->fileUploader->deleteFile($map->getImageUrl());
            }

            $this->mapService->deleteMap($map);

            return $this->json(
                ['message' => 'Carte supprimée avec succès'],
                Response::HTTP_OK,
            );
        }
        catch (Exception $e) {
            return $this->json(
                ['error' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }

    /**
     * Mettre à jour les paramètres d'une carte (ex: grille).
     */
    #[OA\Patch(
        path: '/api/games/{gameId}/maps/{id}/settings',
        summary: 'Mettre à jour les paramètres d\'une carte (MJ uniquement)',
        security: [['BearerAuth' => []]],
        tags: ['Cartes'],
        parameters: [
            new OA\Parameter(name: 'gameId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                description: 'Paramètres à fusionner avec les existants',
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Paramètres mis à jour'),
            new OA\Response(response: 400, description: 'Données invalides'),
            new OA\Response(response: 403, description: 'Réservé au MJ'),
        ],
    )]
    #[Route('/{id}/settings', name: 'update_settings', methods: ['PATCH'])]
    public function updateSettings(int $gameId, int $id, Request $request): JsonResponse
    {
        $game = $this->gameRepository->find($gameId);

        if (!$game) {
            return $this->json(
                ['error' => 'Partie introuvable'],
                Response::HTTP_NOT_FOUND,
            );
        }

        $map = $this->mapRepository->find($id);

        $mapGame = $map?->getGame();
        if (!$map || !$mapGame || $mapGame->getId() !== $gameId) {
            return $this->json(
                ['error' => 'Carte introuvable'],
                Response::HTTP_NOT_FOUND,
            );
        }

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$game->isGameMaster($user)) {
            return $this->json(
                ['error' => 'Seul le maître du jeu peut modifier les paramètres'],
                Response::HTTP_FORBIDDEN,
            );
        }

        try {
            $data = json_decode($request->getContent(), true);

            if (null === $data || !\is_array($data)) {
                return $this->json(
                    ['error' => 'Données invalides'],
                    Response::HTTP_BAD_REQUEST,
                );
            }

            $settings = $map->getSettings() ?? [];

            $settings = array_merge($settings, $data);

            $map->setSettings($settings);

            $this->entityManager->flush();

            $this->mapService->publishMapUpdate($map);

            return $this->json(
                $map,
                Response::HTTP_OK,
                [],
                ['groups' => 'map:read'],
            );
        }
        catch (Exception $e) {
            return $this->json(
                ['error' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }
}
