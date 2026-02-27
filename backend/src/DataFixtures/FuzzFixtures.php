<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Game;
use App\Entity\GameMap;
use App\Entity\GamePlayer;
use App\Entity\User;
use App\Enum\GameStatus;
use App\Enum\PlayerRole;
use App\Enum\PlayerStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Fixtures pour les tests de fuzzing manuels (hors CI).
 *
 * Usage : doctrine:fixtures:load --group=fuzz --append --no-interaction --env=test
 *
 * Crée :
 *  - fuzz@test.com / FuzzPass123!          (joueur)
 *  - fuzz-gm@test.com / FuzzPass123!       (maître de jeu)
 *  - other-user@test.com / OtherPass123!   (utilisateur étranger pour tests IDOR)
 *  - Une partie avec une carte (pour ChatFuzzTest et TokenFuzzTest)
 */
class FuzzFixtures extends Fixture implements FixtureGroupInterface
{
    public static function getGroups(): array
    {
        return ['fuzz'];
    }

    public function load(ObjectManager $manager): void
    {
        $hashedFuzz = password_hash('FuzzPass123!', \PASSWORD_BCRYPT);
        $hashedOther = password_hash('OtherPass123!', \PASSWORD_BCRYPT);

        $fuzzUser = (new User())
            ->setEmail('fuzz@test.com')
            ->setPseudo('fuzz_user')
            ->setPassword($hashedFuzz)
            ->setRoles(['ROLE_USER'])
            ->setIsVerified(true);
        $manager->persist($fuzzUser);

        $fuzzGm = (new User())
            ->setEmail('fuzz-gm@test.com')
            ->setPseudo('fuzz_gm')
            ->setPassword($hashedFuzz)
            ->setRoles(['ROLE_USER'])
            ->setIsVerified(true);
        $manager->persist($fuzzGm);

        $otherUser = (new User())
            ->setEmail('other-user@test.com')
            ->setPseudo('other_user')
            ->setPassword($hashedOther)
            ->setRoles(['ROLE_USER'])
            ->setIsVerified(true);
        $manager->persist($otherUser);

        $game = (new Game())
            ->setName('Fuzz Test Game')
            ->setGameMaster($fuzzGm)
            ->setMaxPlayers(6)
            ->setIsPublic(false)
            ->setStatus(GameStatus::PREPARATION);
        $manager->persist($game);

        $manager->persist(
            (new GamePlayer())
                ->setGame($game)
                ->setUser($fuzzGm)
                ->setRole(PlayerRole::GAME_MASTER)
                ->setStatus(PlayerStatus::ACTIVE),
        );

        $manager->persist(
            (new GamePlayer())
                ->setGame($game)
                ->setUser($fuzzUser)
                ->setRole(PlayerRole::PLAYER)
                ->setStatus(PlayerStatus::ACTIVE),
        );

        $map = (new GameMap())
            ->setGame($game)
            ->setName('Fuzz Map')
            ->setWidth(20)
            ->setHeight(20)
            ->setIsActive(true);
        $manager->persist($map);

        $manager->flush();
    }
}
