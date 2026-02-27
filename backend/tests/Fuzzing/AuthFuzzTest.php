<?php

declare(strict_types=1);

namespace App\Tests\Fuzzing;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fuzz tests sur les endpoints d'authentification.
 *
 * Comportement attendu pour TOUT payload invalide :
 *   - HTTP 400 : mauvaise structure de requête
 *   - HTTP 401 : credentials incorrects mais requête valide
 *   - HTTP 422 : validation échouée
 *
 * Comportement INACCEPTABLE :
 *   - HTTP 500 : erreur serveur non gérée
 *   - Timeout : blocage applicatif
 *   - Fuite de stack trace en production
 */
class AuthFuzzTest extends WebTestCase
{
    /**
     * Fuzz sur le champ email du login.
     * Vecteurs : SQLi, XSS, null, overflow, encodages.
     */
    public function testFuzzLoginEmailField(): void
    {
        $client = static::createClient();
        $issues = [];

        foreach (FuzzPayloadProvider::allTextPayloads() as $payload) {
            $client->request(
                'POST',
                '/api/login',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode(['email' => $payload, 'password' => 'ValidPass123!'], JSON_INVALID_UTF8_SUBSTITUTE)
            );

            $statusCode = $client->getResponse()->getStatusCode();

            if (!in_array($statusCode, FuzzPayloadProvider::acceptableHttpCodes())) {
                $issues[] = $this->formatIssue('login.email', $payload, $statusCode, $client->getResponse()->getContent());
            }
        }

        $this->assertEmpty($issues, "Fuzzing login[email] révèle des comportements inattendus :\n" . implode("\n", $issues));
    }

    /**
     * Fuzz sur le champ password du login.
     */
    public function testFuzzLoginPasswordField(): void
    {
        $client = static::createClient();
        $issues = [];

        foreach (FuzzPayloadProvider::allTextPayloads() as $payload) {
            $client->request(
                'POST',
                '/api/login',
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode(['email' => 'user@test.com', 'password' => $payload], JSON_INVALID_UTF8_SUBSTITUTE)
            );

            $statusCode = $client->getResponse()->getStatusCode();

            if (!in_array($statusCode, FuzzPayloadProvider::acceptableHttpCodes())) {
                $issues[] = $this->formatIssue('login.password', $payload, $statusCode, $client->getResponse()->getContent());
            }
        }

        $this->assertEmpty($issues, "Fuzzing login[password] révèle des comportements inattendus :\n" . implode("\n", $issues));
    }

    /**
     * Fuzz sur la structure JSON complète du login.
     * Envoie des JSON malformés ou avec des clés inattendues.
     */
    public function testFuzzLoginJsonStructure(): void
    {
        $client = static::createClient();
        $issues = [];

        $malformedBodies = [
            '',
            'not-json',
            '{"email": "test@test.com"}',
            '{}',
            '[]',
            '{"email":"a@a.com","password":"p","extra":"' . str_repeat('x', 5000) . '"}',
            '{"__proto__":{"admin":true},"email":"a@a.com","password":"p"}',
        ];

        foreach ($malformedBodies as $body) {
            $client->request('POST', '/api/login', [], [], ['CONTENT_TYPE' => 'application/json'], $body);
            $statusCode = $client->getResponse()->getStatusCode();

            if (!in_array($statusCode, FuzzPayloadProvider::acceptableHttpCodes())) {
                $issues[] = sprintf(
                    "body=%s → HTTP %d | %s",
                    substr($body, 0, 80),
                    $statusCode,
                    substr($client->getResponse()->getContent(), 0, 200)
                );
            }
        }

        $this->assertEmpty($issues, "Fuzzing structure JSON login :\n" . implode("\n", $issues));
    }

    /**
     * Fuzz sur les headers Authorization.
     * Cible : gestion des JWT malformés — doit retourner 401, jamais 500.
     */
    public function testFuzzAuthorizationHeader(): void
    {
        $client = static::createClient();
        $issues = [];

        $invalidTokens = [
            'Bearer ',
            'Bearer invalid.token.here',
            'Bearer ' . str_repeat('A', 5000),
            'Basic dXNlcjpwYXNz',
            str_repeat('Bearer valid', 100),
            "Bearer eyJ\x00alg\x00}",
        ];

        foreach ($invalidTokens as $authHeader) {
            $client->request('GET', '/api/games', [], [], [
                'HTTP_AUTHORIZATION' => $authHeader,
                'CONTENT_TYPE'       => 'application/json',
            ]);

            $statusCode = $client->getResponse()->getStatusCode();

            if ($statusCode === Response::HTTP_INTERNAL_SERVER_ERROR) {
                $issues[] = sprintf(
                    "Authorization: %s → HTTP %d",
                    substr($authHeader, 0, 60),
                    $statusCode
                );
            }
        }

        $this->assertEmpty($issues, "Fuzzing Authorization header :\n" . implode("\n", $issues));
    }

    /**
     * Fuzz sur le register — champs email, pseudo, password.
     */
    public function testFuzzRegisterFields(): void
    {
        $client = static::createClient();
        $issues = [];

        $fields = ['email', 'pseudo', 'password'];

        $i = 0;
        foreach ($fields as $field) {
            foreach (FuzzPayloadProvider::allTextPayloads() as $payload) {
                // Email et pseudo uniques par itération pour éviter les 409
                // quand un payload valide enregistre effectivement un compte
                $body = [
                    'email'    => "fuzz-reg-{$i}@test.com",
                    'pseudo'   => "fuzzreguser{$i}",
                    'password' => 'ValidPass123!',
                ];
                $body[$field] = $payload;
                ++$i;

                $client->request('POST', '/api/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($body, JSON_INVALID_UTF8_SUBSTITUTE));
                $statusCode = $client->getResponse()->getStatusCode();

                if (!in_array($statusCode, FuzzPayloadProvider::acceptableHttpCodes())) {
                    $issues[] = $this->formatIssue("register.$field", $payload, $statusCode, $client->getResponse()->getContent());
                }
            }
        }

        $this->assertEmpty($issues, "Fuzzing register fields :\n" . implode("\n", $issues));
    }

    private function formatIssue(string $field, mixed $payload, int $status, string $response): string
    {
        return sprintf(
            "[%s] payload=%s → HTTP %d | response=%s",
            $field,
            json_encode($payload),
            $status,
            substr($response, 0, 200)
        );
    }
}
