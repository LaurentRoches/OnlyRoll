<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Tests fonctionnels pour HealthController.
 */
final class HealthControllerTest extends WebTestCase
{
    public function testHealthEndpointReturnsOk(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/health');

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('status', $data);
        $this->assertSame('ok', $data['status']);
    }

    public function testHealthEndpointReturnsJson(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/health');

        $this->assertResponseHeaderSame('Content-Type', 'application/json');
    }
}
