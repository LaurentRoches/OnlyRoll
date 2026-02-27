<?php

declare(strict_types=1);

namespace App\Tests\Fuzzing;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Fuzz tests sur les paramètres de chemin URL.
 *
 * Surface d'attaque : paramètres {id} dans les routes.
 *
 * Cible : IDs numériques remplacés par des valeurs inattendues.
 * Comportement attendu : 400 ou 404, jamais 500.
 */
class PathFuzzTest extends WebTestCase
{
    private KernelBrowser $client;

    private EntityManagerInterface $entityManager;

    private User $fuzzUser;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->cleanDatabase();

        $this->fuzzUser = (new User())
            ->setEmail('fuzz-path@test.com')
            ->setPseudo('fuzz_path_user')
            ->setPassword('$2y$13$hashed_password')
            ->setRoles(['ROLE_USER'])
            ->setIsVerified(true);

        $this->entityManager->persist($this->fuzzUser);
        $this->entityManager->flush();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }

    /**
     * Fuzz sur les IDs de ressources dans l'URL.
     * Cible : {id} path parameters — tout sauf entiers valides doit retourner
     * 400/404, jamais 500.
     */
    public function testFuzzResourceIds(): void
    {
        $this->client->loginUser($this->fuzzUser);
        $issues = [];

        $fuzzIds = [
            '0', '-1', '999999999',
            'abc', 'null', 'undefined',
            "' OR 1=1--",
            '../../../etc/passwd',
            str_repeat('9', 50),
            '1.5', '1e10',
            '%00', '%0d%0a',
        ];

        $endpoints = [
            '/api/games/%s',
            '/api/games/%s/maps',
        ];

        foreach ($endpoints as $endpoint) {
            foreach ($fuzzIds as $fuzzId) {
                $url = sprintf($endpoint, urlencode($fuzzId));
                $this->client->request('GET', $url);
                $statusCode = $this->client->getResponse()->getStatusCode();

                if ($statusCode === 500) {
                    $issues[] = sprintf(
                        "GET %s → HTTP 500 | %s",
                        $url,
                        substr($this->client->getResponse()->getContent(), 0, 200)
                    );
                }
            }
        }

        $this->assertEmpty($issues, "Fuzzing IDs dans URL :\n" . implode("\n", $issues));
    }

    private function cleanDatabase(): void
    {
        $conn = $this->entityManager->getConnection();
        $conn->executeStatement('SET FOREIGN_KEY_CHECKS = 0');

        foreach (['game_message', 'game_token', 'game_player', 'game_map', 'game', 'user'] as $table) {
            $conn->executeStatement("TRUNCATE TABLE $table");
        }

        $conn->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
    }
}
