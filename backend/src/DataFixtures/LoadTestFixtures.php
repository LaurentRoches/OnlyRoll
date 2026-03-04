<?php

namespace App\DataFixtures;

use App\Entity\Game;
use App\Entity\GamePlayer;
use App\Entity\User;
use App\Enum\GameStatus;
use App\Enum\PlayerRole;
use App\Enum\PlayerStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * Fixtures dédiées aux tests de charge K6.
 * Ne pas mélanger avec les fixtures de dev normales.
 *
 * Chargement : doctrine:fixtures:load --group=load-test --append
 *
 * Crée :
 * - 100 utilisateurs loadtest1@onlyroll.com … loadtest100@onlyroll.com
 * - 20 parties publiques variées (statuts + capacités mixtes)
 * - 3 parties "spike" avec maxPlayers=2 pour tester les race conditions
 */
class LoadTestFixtures extends Fixture implements FixtureGroupInterface
{
    public const LOAD_TEST_PASSWORD = 'LoadTest123!';
    public const CHAT_GAME_REFERENCE = 'load-test-chat-game';

    public static function getGroups(): array
    {
        return ['load-test'];
    }

    public function load(ObjectManager $manager): void
    {
        $users = $this->createUsers($manager);
        $this->createRegularGames($manager, $users);
        $this->createSpikeGames($manager, $users);

        $manager->flush();
    }

    /**
     * @return User[]
     */
    private function createUsers(ObjectManager $manager): array
    {
        $users = [];
        $hashedPassword = password_hash(self::LOAD_TEST_PASSWORD, \PASSWORD_BCRYPT);

        for ($i = 1; $i <= 100; ++$i) {
            $user = new User();
            $user->setPseudo("loadtest{$i}")
                ->setEmail("loadtest{$i}@onlyroll.com")
                ->setPassword($hashedPassword)
                ->setRoles(['ROLE_USER'])
                ->setIsVerified(true);
            $manager->persist($user);
            $users[] = $user;
        }

        return $users;
    }

    /**
     * Crée 20 parties publiques variées pour les scénarios d'auth, liste et chat.
     *
     * - 15 parties en PREPARATION (rejoignables)
     * - 5 parties IN_PROGRESS
     * - maxPlayers varie de 4 à 20
     *
     * @param User[] $users
     */
    private function createRegularGames(ObjectManager $manager, array $users): void
    {
        $gameNames = [
            'La Quête des Runes Oubliées',
            'Chroniques de Valdris',
            'Les Ombres de Mirkwood',
            'Territoire des Dragons',
            'L\'Éveil des Anciens',
            'Cités sous les Étoiles',
            'La Confrérie des Lames',
            'Portails vers l\'Inconnu',
            'Marches du Crépuscule',
            'Le Pacte des Dieux',
            'Arcanes et Destinées',
            'Ruines de Kharash',
            'Les Prophéties d\'Ezar',
            'Terres Sauvages',
            'Brume du Temps',
            'Forges de l\'Empire',
            'Echos du Passé',
            'La Tour Interdite',
            'Chemins de Gloire',
            'Nuit des Spectres',
        ];

        foreach ($gameNames as $index => $name) {
            $isInProgress = $index >= 15;
            $maxPlayers = match ($index % 5) {
                0 => 4,
                1 => 6,
                2 => 8,
                3 => 10,
                default => 20,
            };

            $game = new Game();
            $game->setName($name)
                ->setDescription("Partie de test de charge #{$index} — {$name}")
                ->setGameMaster($users[0])
                ->setMaxPlayers($maxPlayers)
                ->setIsPublic(true)
                ->setStatus($isInProgress ? GameStatus::IN_PROGRESS : GameStatus::PREPARATION);
            $manager->persist($game);

            $gmPlayer = new GamePlayer();
            $gmPlayer->setGame($game)
                ->setUser($users[0])
                ->setRole(PlayerRole::GAME_MASTER)
                ->setStatus(PlayerStatus::ACTIVE);
            $manager->persist($gmPlayer);

            if (0 === $index) {
                $this->addReference(self::CHAT_GAME_REFERENCE, $game);
            }
        }
    }

    /**
     * Crée 3 parties avec maxPlayers=2 pour tester les race conditions (scénario 4).
     * Chaque partie a 1 place libre (le GM occupe la première).
     *
     * @param User[] $users
     */
    private function createSpikeGames(ObjectManager $manager, array $users): void
    {
        for ($i = 1; $i <= 3; ++$i) {
            $game = new Game();
            $game->setName("Spike Test #{$i} — Place Limitée")
                ->setDescription('Partie spike test avec 1 place disponible')
                ->setGameMaster($users[$i])
                ->setMaxPlayers(2)
                ->setIsPublic(true)
                ->setStatus(GameStatus::PREPARATION);
            $manager->persist($game);

            $gmPlayer = new GamePlayer();
            $gmPlayer->setGame($game)
                ->setUser($users[$i])
                ->setRole(PlayerRole::GAME_MASTER)
                ->setStatus(PlayerStatus::ACTIVE);
            $manager->persist($gmPlayer);
        }
    }
}
