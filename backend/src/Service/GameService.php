<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\Game\CreateGameDTO;
use App\DTO\Game\UpdateGameDTO;
use App\Entity\Game;
use App\Entity\GamePlayer;
use App\Entity\User;
use App\Enum\GameStatus;
use App\Enum\PlayerRole;
use App\Enum\PlayerStatus;
use App\Exception\Game\AccessDeniedException;
use App\Exception\Game\GameFullException;
use App\Exception\Game\GameNotFoundException;
use App\Exception\Game\InvalidPasswordException;
use App\Repository\GameMapRepository;
use App\Repository\GamePlayerRepository;
use App\Repository\GameRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;

/**
 * Service de gestion des parties de jeu.
 */
final class GameService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly GameRepository $gameRepository,
        private readonly GamePlayerRepository $gamePlayerRepository,
        private readonly GameMapRepository $gameMapRepository,
        private readonly LoggerInterface $logger,
        private readonly MercurePublisher $mercurePublisher,
        private readonly FileUploader $fileUploader,
    ) {
    }

    /**
     * Crée une nouvelle partie.
     *
     * @param CreateGameDTO $dto Données de la partie à créer
     * @param User $gameMaster Utilisateur maître de jeu
     *
     * @return Game La partie créée
     *
     * @throws InvalidArgumentException Si le mot de passe est invalide pour une partie privée
     */
    public function createGame(CreateGameDTO $dto, User $gameMaster): Game
    {
        $this->logger->info('Creating new game', [
            'name' => $dto->name,
            'game_master_id' => $gameMaster->getId(),
        ]);

        if (!$dto->isPublic) {
            if (empty($dto->password)) {
                throw new InvalidArgumentException('Le mot de passe est requis pour une partie privée');
            }

            if (\strlen($dto->password) < 4 || \strlen($dto->password) > 50) {
                throw new InvalidArgumentException('Le mot de passe doit faire entre 4 et 50 caractères');
            }
        }

        $game = new Game();
        $game->setName($dto->name)
            ->setDescription($dto->description)
            ->setGameMaster($gameMaster)
            ->setMaxPlayers($dto->maxPlayers)
            ->setIsPublic($dto->isPublic);

        if ($dto->password && !$dto->isPublic) {
            $game->setPassword(password_hash($dto->password, \PASSWORD_ARGON2ID));
        }

        $this->entityManager->persist($game);

        $gmPlayer = new GamePlayer();
        $gmPlayer->setGame($game)
                ->setUser($gameMaster)
                ->setRole(PlayerRole::GAME_MASTER)
                ->setStatus(PlayerStatus::ACTIVE);

        $this->entityManager->persist($gmPlayer);
        $this->entityManager->flush();

        $this->logger->info('Game created successfully', ['game_id' => $game->getId()]);

        return $game;
    }

    /**
     * Met à jour une partie.
     */
    public function updateGame(Game $game, UpdateGameDTO $dto, User $user): Game
    {
        if (!$game->isGameMaster($user)) {
            throw new AccessDeniedException('Seul le MJ peut modifier cette partie');
        }

        if (null !== $dto->name) {
            $game->setName($dto->name);
        }

        if (null !== $dto->description) {
            $game->setDescription($dto->description);
        }

        if (null !== $dto->maxPlayers) {
            $game->setMaxPlayers($dto->maxPlayers);
        }

        if (null !== $dto->isPublic) {
            $game->setIsPublic($dto->isPublic);
        }

        if (null !== $dto->status) {
            $this->updateGameStatus($game, $dto->status);
        }

        $this->entityManager->flush();

        return $game;
    }

    /**
     * Permet à un joueur de rejoindre une partie.
     */
    public function joinGame(int $gameId, User $user, ?string $password = null): GamePlayer
    {
        $game = $this->gameRepository->findGameWithPlayers($gameId);

        if (!$game) {
            throw new GameNotFoundException();
        }

        $this->validateGameJoinability($game, $user, $password);

        $gamePlayer = new GamePlayer();
        $gamePlayer->setGame($game)
                   ->setUser($user)
                   ->setRole(PlayerRole::PLAYER)
                   ->setStatus(PlayerStatus::ACTIVE);

        $this->entityManager->persist($gamePlayer);
        $this->entityManager->flush();

        $this->logger->info('User joined game', [
            'user_id' => $user->getId(),
            'game_id' => $game->getId(),
        ]);

        $this->mercurePublisher->publishPlayerEvent($gameId, [
            'action' => 'joined',
            'userId' => $user->getId(),
            'userName' => $user->getPseudo(),
            'role' => $gamePlayer->getRole()->value,
        ]);

        return $gamePlayer;
    }

    /**
     * Permet à un joueur de quitter une partie.
     */
    public function leaveGame(Game $game, User $user): void
    {
        $gamePlayer = $this->gamePlayerRepository->findPlayerInGame($game, $user);

        if (!$gamePlayer) {
            throw new GameNotFoundException('Vous ne faites pas partie de cette partie');
        }

        if ($game->isGameMaster($user)) {
            throw new AccessDeniedException('Le MJ ne peut pas quitter sa propre partie');
        }

        $this->entityManager->remove($gamePlayer);
        $this->entityManager->flush();

        $this->logger->info('User left game', [
            'user_id' => $user->getId(),
            'game_id' => $game->getId(),
        ]);

        $gameId = $game->getId();
        if (null !== $gameId) {
            $this->mercurePublisher->publishPlayerEvent($gameId, [
                'action' => 'left',
                'userId' => $user->getId(),
                'userName' => $user->getPseudo(),
            ]);
        }
    }

    /**
     * Supprime une partie définitivement (hard delete + nettoyage fichiers).
     */
    public function deleteGame(Game $game, User $user): void
    {
        if (!$game->isGameMaster($user)) {
            throw new AccessDeniedException();
        }

        $gameId = $game->getId();

        $maps = $this->gameMapRepository->findMapsByGame($game);
        foreach ($maps as $map) {
            $imageUrl = $map->getImageUrl();
            if ($imageUrl) {
                $this->fileUploader->deleteFile($imageUrl);
            }
        }

        $this->entityManager->remove($game);
        $this->entityManager->flush();

        $this->logger->info('Game deleted permanently', ['game_id' => $gameId]);
    }

    /**
     * Invite un utilisateur à rejoindre une partie (crée une entrée PENDING).
     */
    public function invitePlayer(Game $game, User $inviter, User $target): GamePlayer
    {
        if (!$game->isGameMaster($inviter)) {
            throw new AccessDeniedException('Seul le MJ peut inviter des joueurs');
        }

        if ($this->gamePlayerRepository->isUserInGame($game, $target)) {
            throw new AccessDeniedException('Ce joueur est déjà dans la partie ou a déjà été invité');
        }

        $gameId = $game->getId();
        if (null === $gameId) {
            throw new GameNotFoundException('ID de partie invalide');
        }

        $currentCount = $this->gamePlayerRepository->countActiveAndPendingPlayers($game);
        if ($currentCount >= $game->getMaxPlayers()) {
            throw new GameFullException('La partie est pleine, impossible d\'inviter d\'autres joueurs');
        }

        $gamePlayer = new GamePlayer();
        $gamePlayer->setGame($game)
            ->setUser($target)
            ->setRole(PlayerRole::PLAYER)
            ->setStatus(PlayerStatus::PENDING);

        $this->entityManager->persist($gamePlayer);
        $this->entityManager->flush();

        $this->logger->info('Player invited to game', [
            'inviter_id' => $inviter->getId(),
            'target_id' => $target->getId(),
            'game_id' => $gameId,
        ]);

        $targetId = $target->getId();
        if (null !== $targetId) {
            $this->mercurePublisher->publishUserNotification($targetId, [
                'action' => 'invitation_received',
                'game' => [
                    'id' => $gameId,
                    'name' => $game->getName(),
                ],
                'gameMaster' => [
                    'id' => $inviter->getId(),
                    'pseudo' => $inviter->getPseudo(),
                ],
                'invitationId' => $gamePlayer->getId(),
            ]);
        }

        $this->mercurePublisher->publishPlayerEvent($gameId, [
            'action' => 'invited',
            'userId' => $target->getId(),
            'userName' => $target->getPseudo(),
        ]);

        return $gamePlayer;
    }

    /**
     * Accepte une invitation à rejoindre une partie.
     */
    public function acceptInvitation(Game $game, User $user): GamePlayer
    {
        $gamePlayer = $this->gamePlayerRepository->findPlayerInGame($game, $user);

        if (!$gamePlayer || PlayerStatus::PENDING !== $gamePlayer->getStatus()) {
            throw new AccessDeniedException('Aucune invitation en attente pour cette partie');
        }

        $gamePlayer->activate();
        $this->entityManager->flush();

        $gameId = $game->getId();
        $this->logger->info('Invitation accepted', [
            'user_id' => $user->getId(),
            'game_id' => $gameId,
        ]);

        if (null !== $gameId) {
            $this->mercurePublisher->publishPlayerEvent($gameId, [
                'action' => 'accepted',
                'userId' => $user->getId(),
                'userName' => $user->getPseudo(),
                'role' => $gamePlayer->getRole()->value,
            ]);
        }

        return $gamePlayer;
    }

    /**
     * Refuse une invitation à rejoindre une partie.
     */
    public function declineInvitation(Game $game, User $user): void
    {
        $gamePlayer = $this->gamePlayerRepository->findPlayerInGame($game, $user);

        if (!$gamePlayer || PlayerStatus::PENDING !== $gamePlayer->getStatus()) {
            throw new AccessDeniedException('Aucune invitation en attente pour cette partie');
        }

        $gameId = $game->getId();
        $userId = $user->getId();

        $this->entityManager->remove($gamePlayer);
        $this->entityManager->flush();

        $this->logger->info('Invitation declined', [
            'user_id' => $userId,
            'game_id' => $gameId,
        ]);

        if (null !== $gameId) {
            $this->mercurePublisher->publishPlayerEvent($gameId, [
                'action' => 'declined',
                'userId' => $userId,
                'userName' => $user->getPseudo(),
            ]);
        }
    }

    /**
     * Expulse un joueur de la partie (MJ uniquement).
     */
    public function kickPlayer(Game $game, User $requester, GamePlayer $target): void
    {
        if (!$game->isGameMaster($requester)) {
            throw new AccessDeniedException('Seul le MJ peut expulser des joueurs');
        }

        if (PlayerRole::GAME_MASTER === $target->getRole()) {
            throw new AccessDeniedException('Impossible d\'expulser le MJ');
        }

        $targetUser = $target->getUser();
        $this->entityManager->remove($target);
        $this->entityManager->flush();

        $gameId = $game->getId();
        $this->logger->info('Player kicked from game', [
            'requester_id' => $requester->getId(),
            'target_id' => $targetUser?->getId(),
            'game_id' => $gameId,
        ]);

        if (null !== $gameId) {
            $this->mercurePublisher->publishPlayerEvent($gameId, [
                'action' => 'kicked',
                'userId' => $targetUser?->getId(),
                'userName' => $targetUser?->getPseudo(),
            ]);
        }
    }

    /**
     * Valide qu'un utilisateur peut rejoindre une partie.
     */
    private function validateGameJoinability(Game $game, User $user, ?string $password): void
    {
        if ($this->gamePlayerRepository->isUserInGame($game, $user)) {
            throw new AccessDeniedException('Vous faites déjà partie de cette partie');
        }

        if ($game->isFull()) {
            throw new GameFullException();
        }

        if (!$game->isPublic()) {
            $gamePassword = $game->getPassword();
            if (!$password || !$gamePassword || !password_verify($password, $gamePassword)) {
                throw new InvalidPasswordException();
            }
        }

        if (!\in_array($game->getStatus(), [GameStatus::PREPARATION, GameStatus::IN_PROGRESS])) {
            throw new AccessDeniedException('Cette partie n\'accepte plus de nouveaux joueurs');
        }
    }

    /**
     * Met à jour le statut avec logique métier.
     */
    private function updateGameStatus(Game $game, GameStatus $newStatus): void
    {
        $oldStatus = $game->getStatus();

        if (GameStatus::IN_PROGRESS === $newStatus && GameStatus::PREPARATION === $oldStatus) {
            $game->setStartedAt(new DateTimeImmutable());
        }

        if (GameStatus::COMPLETED === $newStatus) {
            $game->setCompletedAt(new DateTimeImmutable());
        }

        $game->setStatus($newStatus);
    }
}
