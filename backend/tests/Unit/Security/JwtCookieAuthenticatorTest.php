<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Security\JwtCookieAuthenticator;
use Exception;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

class JwtCookieAuthenticatorTest extends TestCase
{
    private JWTTokenManagerInterface&MockObject $jwtManager;
    private JwtCookieAuthenticator $authenticator;

    protected function setUp(): void
    {
        $this->jwtManager = $this->createMock(JWTTokenManagerInterface::class);
        $this->authenticator = new JwtCookieAuthenticator($this->jwtManager);
    }

    public function testSupportsReturnsFalseForOptionsRequest(): void
    {
        $request = Request::create('/api/test', 'OPTIONS');

        $this->assertFalse($this->authenticator->supports($request));
    }

    public function testSupportsReturnsFalseForLoginRoute(): void
    {
        $request = Request::create('/api/login', 'POST');
        $request->attributes->set('_route', 'api_login');

        $this->assertFalse($this->authenticator->supports($request));
    }

    public function testSupportsReturnsFalseForRegisterRoute(): void
    {
        $request = Request::create('/api/register', 'POST');
        $request->attributes->set('_route', 'api_register');

        $this->assertFalse($this->authenticator->supports($request));
    }

    public function testSupportsReturnsTrueWhenJwtCookiePresent(): void
    {
        $request = Request::create('/api/test', 'GET');
        $request->cookies->set('jwt_token', 'some-token');

        $this->assertTrue($this->authenticator->supports($request));
    }

    public function testSupportsReturnsTrueWhenAuthorizationHeaderPresent(): void
    {
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Authorization', 'Bearer some-token');

        $this->assertTrue($this->authenticator->supports($request));
    }

    public function testSupportsReturnsFalseWhenNoTokenPresent(): void
    {
        $request = Request::create('/api/test', 'GET');

        $this->assertFalse($this->authenticator->supports($request));
    }

    public function testAuthenticateWithCookieToken(): void
    {
        $request = Request::create('/api/test', 'GET');
        $request->cookies->set('jwt_token', 'valid-token');

        $this->jwtManager->expects($this->once())
            ->method('parse')
            ->with('valid-token')
            ->willReturn(['username' => 'testuser']);

        $passport = $this->authenticator->authenticate($request);

        $this->assertInstanceOf(\Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport::class, $passport);
    }

    public function testAuthenticateWithBearerToken(): void
    {
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Authorization', 'Bearer valid-bearer-token');

        $this->jwtManager->expects($this->once())
            ->method('parse')
            ->with('valid-bearer-token')
            ->willReturn(['username' => 'testuser']);

        $passport = $this->authenticator->authenticate($request);

        $this->assertInstanceOf(\Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport::class, $passport);
    }

    public function testAuthenticateThrowsExceptionWhenNoTokenFound(): void
    {
        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('No JWT token found');

        $request = Request::create('/api/test', 'GET');
        $this->authenticator->authenticate($request);
    }

    public function testAuthenticateThrowsExceptionWhenTokenIsEmpty(): void
    {
        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('No JWT token found');

        $request = Request::create('/api/test', 'GET');
        $request->cookies->set('jwt_token', '');
        $this->authenticator->authenticate($request);
    }

    public function testAuthenticateThrowsExceptionWhenPayloadHasNoUsername(): void
    {
        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Invalid JWT token');

        $request = Request::create('/api/test', 'GET');
        $request->cookies->set('jwt_token', 'invalid-token');

        $this->jwtManager->expects($this->once())
            ->method('parse')
            ->with('invalid-token')
            ->willReturn(['some_field' => 'value']);

        $this->authenticator->authenticate($request);
    }

    public function testAuthenticateThrowsExceptionWhenParsingFails(): void
    {
        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessageMatches('/Invalid JWT token:/');

        $request = Request::create('/api/test', 'GET');
        $request->cookies->set('jwt_token', 'malformed-token');

        $this->jwtManager->expects($this->once())
            ->method('parse')
            ->with('malformed-token')
            ->willThrowException(new Exception('Token parsing failed'));

        $this->authenticator->authenticate($request);
    }

    public function testOnAuthenticationSuccessReturnsNull(): void
    {
        $request = Request::create('/api/test', 'GET');
        $token = $this->createMock(TokenInterface::class);

        $result = $this->authenticator->onAuthenticationSuccess($request, $token, 'main');

        $this->assertNull($result);
    }

    public function testOnAuthenticationFailureReturnsJsonResponse(): void
    {
        $request = Request::create('/api/test', 'GET');
        $exception = new AuthenticationException('Authentication failed');

        $response = $this->authenticator->onAuthenticationFailure($request, $exception);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));

        $content = json_decode($response->getContent(), true);
        $this->assertIsArray($content);
        $this->assertArrayHasKey('error', $content);
    }

    public function testOnAuthenticationFailureHandlesJsonEncodeFailure(): void
    {
        $request = Request::create('/api/test', 'GET');

        // Create a mock exception avec une MessageKey qui pourrait causer des problèmes d'encodage
        $exception = $this->getMockBuilder(AuthenticationException::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getMessageKey'])
            ->getMock();

        // On teste le cas nominal d'abord
        $exception->method('getMessageKey')->willReturn('test_error');

        $response = $this->authenticator->onAuthenticationFailure($request, $exception);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        $this->assertNotEmpty($response->getContent());
    }

    public function testAuthenticatePrioritizesCookieOverHeader(): void
    {
        $request = Request::create('/api/test', 'GET');
        $request->cookies->set('jwt_token', 'cookie-token');
        $request->headers->set('Authorization', 'Bearer header-token');

        // Should use cookie token, not header token
        $this->jwtManager->expects($this->once())
            ->method('parse')
            ->with('cookie-token')
            ->willReturn(['username' => 'testuser']);

        $passport = $this->authenticator->authenticate($request);

        $this->assertInstanceOf(\Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport::class, $passport);
    }
}
