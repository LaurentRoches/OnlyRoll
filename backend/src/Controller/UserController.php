<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\GamePlayerRepository;
use App\Repository\UserRepository;
use App\Service\MercureTokenService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur pour les opérations liées aux utilisateurs (recherche, invitations).
 */
#[IsGranted('ROLE_USER')]
final class UserController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly GamePlayerRepository $gamePlayerRepository,
        private readonly MercureTokenService $mercureTokenService,
    ) {
    }

    /**
     * Recherche des utilisateurs par pseudo (autocomplétion pour invitations).
     */
    #[Route('/api/users/search', name: 'api_users_search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $pseudo = trim($request->query->getString('pseudo'));

        if (\strlen($pseudo) < 2) {
            return $this->json(['data' => []], Response::HTTP_OK);
        }

        /** @var \App\Entity\User $currentUser */
        $currentUser = $this->getUser();

        $users = $this->userRepository->searchByPseudo($pseudo, 10, $currentUser);

        $data = array_map(static fn ($u) => [
            'id' => $u->getId(),
            'pseudo' => $u->getPseudo(),
            'avatar' => $u->getAvatar(),
        ], $users);

        return $this->json(['data' => $data], Response::HTTP_OK);
    }

    /**
     * Retourne les invitations en attente de l'utilisateur connecté.
     */
    #[Route('/api/user/invitations', name: 'api_user_invitations', methods: ['GET'])]
    public function invitations(): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $pendingPlayers = $this->gamePlayerRepository->findPendingInvitationsForUser($user);

        $data = array_map(static function ($gp) {
            $game = $gp->getGame();
            $gameMaster = $game?->getGameMaster();

            return [
                'id' => $gp->getId(),
                'game' => [
                    'id' => $game?->getId(),
                    'name' => $game?->getName(),
                ],
                'gameMaster' => [
                    'id' => $gameMaster?->getId(),
                    'pseudo' => $gameMaster?->getPseudo(),
                ],
                'invitedAt' => $gp->getJoinedAt()?->format('c'),
            ];
        }, $pendingPlayers);

        return $this->json(['data' => $data], Response::HTTP_OK);
    }

    /**
     * Génère un token JWT Mercure pour s'abonner aux notifications de l'utilisateur connecté.
     */
    #[Route('/api/user/mercure-notification-token', name: 'api_user_mercure_notification_token', methods: ['GET'])]
    public function mercureNotificationToken(): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $token = $this->mercureTokenService->generateTokenForUserNotifications($user);

        $response = $this->json([
            'token' => $token,
            'expiresIn' => 3600,
        ], Response::HTTP_OK);

        $response->headers->setCookie(
            new Cookie(
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
