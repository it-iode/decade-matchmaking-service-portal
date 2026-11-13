<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Auth;

use App\Domains\Auth\Exceptions\OceanExpertAuthenticationException;
use App\Domains\Auth\Services\OceanExpertAuthService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Tests\TestCase;

class OceanExpertAuthServiceTest extends TestCase
{
    private OceanExpertAuthService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.oceanexpert.auth_url', 'https://oceanexpert.test/auth');
        $this->service = new OceanExpertAuthService;
    }

    public function test_authenticate_returns_token_and_user_on_success(): void
    {
        Http::fake([
            'oceanexpert.test/*' => Http::response(['token' => 'abc-token'], 200),
        ]);

        $result = $this->service->authenticate('test@example.com', 'secret');

        $this->assertEquals('abc-token', $result['token']);
        $this->assertEquals('test@example.com', $result['user']['email']);
        $this->assertEquals('secret', $result['user']['password']);
    }

    public function test_authenticate_throws_when_url_not_configured(): void
    {
        Config::set('services.oceanexpert.auth_url', null);

        $this->expectException(RuntimeException::class);

        $this->service->authenticate('test@example.com', 'secret');
    }

    public function test_authenticate_throws_service_unavailable_with_context_on_server_error(): void
    {
        Http::fake([
            'oceanexpert.test/*' => Http::response('Internal error', 503),
        ]);

        try {
            $this->service->authenticate('test@example.com', 'secret');
            $this->fail('Expected OceanExpertAuthenticationException');
        } catch (OceanExpertAuthenticationException $e) {
            $this->assertSame('service_unavailable', $e->reason());
            $this->assertSame(OceanExpertAuthenticationException::LEVEL_ERROR, $e->logLevel());
            $this->assertTrue($e->isOperationalError());
            $this->assertSame(503, $e->context()['status']);
            $this->assertSame('Internal error', $e->context()['body']);
        }
    }

    public function test_authenticate_throws_invalid_credentials_on_failed_response(): void
    {
        Http::fake([
            'oceanexpert.test/*' => Http::response(['error' => ['message' => 'Bad credentials']], 401),
        ]);

        try {
            $this->service->authenticate('test@example.com', 'secret');
            $this->fail('Expected OceanExpertAuthenticationException');
        } catch (OceanExpertAuthenticationException $e) {
            $this->assertSame('credentials_invalid', $e->reason());
            $this->assertFalse($e->isOperationalError());
            $this->assertSame([], $e->context(), 'Upstream error body must not leak into context');
        }
    }

    public function test_authenticate_throws_invalid_credentials_on_error_payload_with_200(): void
    {
        Http::fake([
            'oceanexpert.test/*' => Http::response(['error' => ['message' => 'Bad credentials']], 200),
        ]);

        try {
            $this->service->authenticate('test@example.com', 'secret');
            $this->fail('Expected OceanExpertAuthenticationException');
        } catch (OceanExpertAuthenticationException $e) {
            $this->assertSame('credentials_invalid', $e->reason());
        }
    }

    public function test_authenticate_throws_invalid_response_when_token_missing(): void
    {
        Http::fake([
            'oceanexpert.test/*' => Http::response(['foo' => 'bar'], 200),
        ]);

        try {
            $this->service->authenticate('test@example.com', 'secret');
            $this->fail('Expected OceanExpertAuthenticationException');
        } catch (OceanExpertAuthenticationException $e) {
            $this->assertSame('invalid_response', $e->reason());
            $this->assertTrue($e->isOperationalError());
        }
    }

    public function test_service_does_not_log(): void
    {
        Http::fake([
            'oceanexpert.test/*' => Http::response('Internal error', 500),
        ]);

        Log::shouldReceive('channel')->never();

        try {
            $this->service->authenticate('test@example.com', 'secret');
        } catch (OceanExpertAuthenticationException) {
            // logging is the caller's responsibility
        }
    }
}
