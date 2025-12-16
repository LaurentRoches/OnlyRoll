<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventListener;

use App\EventListener\CorsListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Tests unitaires pour CorsListener.
 */
final class CorsListenerTest extends TestCase
{
    private CorsListener $listener;
    private HttpKernelInterface $kernel;

    protected function setUp(): void
    {
        $this->listener = new CorsListener();
        $this->kernel = $this->createMock(HttpKernelInterface::class);
    }

    public function testGetSubscribedEvents(): void
    {
        $events = CorsListener::getSubscribedEvents();

        $this->assertArrayHasKey(KernelEvents::REQUEST, $events);
        $this->assertArrayHasKey(KernelEvents::RESPONSE, $events);
        $this->assertSame(['onKernelRequest', 9999], $events[KernelEvents::REQUEST]);
        $this->assertSame(['onKernelResponse', 9999], $events[KernelEvents::RESPONSE]);
    }

    public function testOnKernelRequestWithOptionsRequest(): void
    {
        $request = Request::create('http://localhost/api/test', 'OPTIONS');
        $event = new RequestEvent(
            $this->kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->listener->onKernelRequest($event);

        $response = $event->getResponse();
        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(200, $response->getStatusCode());

        // Vérifie les headers CORS
        $this->assertSame('http://localhost:5173', $response->headers->get('Access-Control-Allow-Origin'));
        $this->assertSame('GET, POST, PUT, PATCH, DELETE, OPTIONS', $response->headers->get('Access-Control-Allow-Methods'));
        $this->assertSame('Content-Type, Authorization, X-Requested-With, Accept', $response->headers->get('Access-Control-Allow-Headers'));
        $this->assertSame('true', $response->headers->get('Access-Control-Allow-Credentials'));
        $this->assertSame('3600', $response->headers->get('Access-Control-Max-Age'));
    }

    public function testOnKernelRequestWithNonOptionsRequest(): void
    {
        $request = Request::create('http://localhost/api/test', 'GET');
        $event = new RequestEvent(
            $this->kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST
        );

        $this->listener->onKernelRequest($event);

        // Vérifie qu'aucune réponse n'a été définie
        $this->assertNull($event->getResponse());
    }

    public function testOnKernelRequestWithSubRequest(): void
    {
        $request = Request::create('http://localhost/api/test', 'OPTIONS');
        $event = new RequestEvent(
            $this->kernel,
            $request,
            HttpKernelInterface::SUB_REQUEST
        );

        $this->listener->onKernelRequest($event);

        // Vérifie qu'aucune réponse n'a été définie pour une sub-request
        $this->assertNull($event->getResponse());
    }

    public function testOnKernelResponseWithOriginHeader(): void
    {
        $request = Request::create('http://localhost/api/test', 'GET');
        $request->headers->set('Origin', 'http://localhost:5173');

        $response = new Response();
        $event = new ResponseEvent(
            $this->kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response
        );

        $this->listener->onKernelResponse($event);

        // Vérifie les headers CORS
        $this->assertSame('http://localhost:5173', $response->headers->get('Access-Control-Allow-Origin'));
        $this->assertSame('GET, POST, PUT, PATCH, DELETE, OPTIONS', $response->headers->get('Access-Control-Allow-Methods'));
        $this->assertSame('Content-Type, Authorization, X-Requested-With, Accept', $response->headers->get('Access-Control-Allow-Headers'));
        $this->assertSame('true', $response->headers->get('Access-Control-Allow-Credentials'));
        $this->assertSame('Link, X-Total-Count, Content-Type', $response->headers->get('Access-Control-Expose-Headers'));
    }

    public function testOnKernelResponseWithoutOriginHeader(): void
    {
        $request = Request::create('http://localhost/api/test', 'GET');
        $response = new Response();
        $event = new ResponseEvent(
            $this->kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response
        );

        $this->listener->onKernelResponse($event);

        // Vérifie qu'aucun header CORS n'a été ajouté
        $this->assertNull($response->headers->get('Access-Control-Allow-Origin'));
    }

    public function testOnKernelResponseWithSubRequest(): void
    {
        $request = Request::create('http://localhost/api/test', 'GET');
        $request->headers->set('Origin', 'http://localhost:5173');

        $response = new Response();
        $event = new ResponseEvent(
            $this->kernel,
            $request,
            HttpKernelInterface::SUB_REQUEST,
            $response
        );

        $this->listener->onKernelResponse($event);

        // Vérifie qu'aucun header CORS n'a été ajouté pour une sub-request
        $this->assertNull($response->headers->get('Access-Control-Allow-Origin'));
    }
}
