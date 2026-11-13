<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Auth\Strategies;

use App\Domains\Auth\DTOs\AuthenticationResult;
use App\Domains\Auth\Exceptions\OceanExpertAuthenticationException;
use App\Domains\Auth\Services\OceanExpertAuthService;
use App\Domains\Auth\Services\OceanExpertSearchService;
use App\Domains\Auth\Services\Strategies\OceanExpertAuthStrategy;
use App\Domains\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

class OceanExpertAuthStrategyTest extends TestCase
{
    use RefreshDatabase;

    protected OceanExpertAuthStrategy $strategy;

    protected OceanExpertAuthService&MockInterface $authService;

    protected OceanExpertSearchService&MockInterface $searchService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authService = Mockery::mock(OceanExpertAuthService::class);
        $this->searchService = Mockery::mock(OceanExpertSearchService::class);

        $this->strategy = new OceanExpertAuthStrategy(
            $this->authService,
            $this->searchService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_supports_email_password_credentials(): void
    {
        $credentials = [
            'email' => 'test@example.com',
            'password' => 'password123',
        ];

        $this->assertTrue($this->strategy->supports($credentials));
    }

    public function test_does_not_support_credentials_without_email(): void
    {
        $credentials = ['password' => 'password123'];

        $this->assertFalse($this->strategy->supports($credentials));
    }

    public function test_does_not_support_oauth_credentials(): void
    {
        $credentials = [
            'email' => 'test@example.com',
            'password' => 'password123',
            'socialite_user' => Mockery::mock(),
        ];

        $this->assertFalse($this->strategy->supports($credentials));
    }

    public function test_authenticates_and_creates_new_user(): void
    {
        $credentials = [
            'email' => 'newuser@example.com',
            'password' => 'password123',
        ];

        $this->authService
            ->shouldReceive('authenticate')
            ->once()
            ->with('newuser@example.com', 'password123')
            ->andReturn([
                'token' => 'ocean-token',
                'user' => ['email' => 'newuser@example.com', 'password' => 'password123'],
            ]);

        $this->searchService
            ->shouldReceive('searchUserByEmail')
            ->once()
            ->with('newuser@example.com')
            ->andReturn([
                'name' => 'New User',
                'first_name' => 'New',
                'last_name' => 'User',
                'country' => 'USA',
                'city' => 'Boston',
            ]);

        $result = $this->strategy->authenticate($credentials);

        $this->assertInstanceOf(AuthenticationResult::class, $result);
        $this->assertInstanceOf(User::class, $result->user);
        $this->assertEquals('newuser@example.com', $result->user->email);
        $this->assertEquals('New User', $result->user->name);
        $this->assertEquals('ocean_expert', $result->authMethod);
        $this->assertEquals('ocean-token', $result->externalToken);
    }

    public function test_authenticates_and_updates_existing_user(): void
    {
        $existingUser = User::factory()->create([
            'email' => 'existing@example.com',
            'name' => 'Old Name',
        ]);

        $credentials = [
            'email' => 'existing@example.com',
            'password' => 'password123',
        ];

        $this->authService
            ->shouldReceive('authenticate')
            ->once()
            ->andReturn([
                'token' => 'ocean-token',
                'user' => ['email' => 'existing@example.com', 'password' => 'password123'],
            ]);

        $this->searchService
            ->shouldReceive('searchUserByEmail')
            ->once()
            ->andReturn([
                'name' => 'Updated Name',
                'first_name' => 'Updated',
                'last_name' => 'Name',
                'country' => 'Canada',
                'city' => 'Toronto',
            ]);

        $result = $this->strategy->authenticate($credentials);

        $this->assertEquals($existingUser->id, $result->user->id);
        $this->assertEquals('Updated Name', $result->user->name);
        $this->assertEquals('Canada', $result->user->country);
    }

    public function test_rethrows_invalid_credentials_without_logging(): void
    {
        $this->authService
            ->shouldReceive('authenticate')
            ->once()
            ->andThrow(OceanExpertAuthenticationException::invalidCredentials());

        // User errors are logged once by AuthenticationService, not here.
        Log::shouldReceive('channel')->never();

        try {
            $this->strategy->authenticate(['email' => 'test@example.com', 'password' => 'wrong']);
            $this->fail('Expected OceanExpertAuthenticationException');
        } catch (OceanExpertAuthenticationException $e) {
            $this->assertSame('credentials_invalid', $e->reason());
        }
    }

    public function test_logs_service_unavailable_as_error_with_context(): void
    {
        $this->authService
            ->shouldReceive('authenticate')
            ->once()
            ->andThrow(OceanExpertAuthenticationException::serviceUnavailable(503, 'upstream down'));

        $logger = Mockery::mock();
        $logger->shouldReceive('log')
            ->once()
            ->with('error', 'Ocean Expert authentication failed', [
                'email' => 'test@example.com',
                'reason' => 'service_unavailable',
                'status' => 503,
                'body' => 'upstream down',
            ]);
        Log::shouldReceive('channel')->with('auth')->once()->andReturn($logger);

        try {
            $this->strategy->authenticate(['email' => 'test@example.com', 'password' => 'secret']);
            $this->fail('Expected OceanExpertAuthenticationException');
        } catch (OceanExpertAuthenticationException $e) {
            $this->assertSame('service_unavailable', $e->reason());
        }
    }

    public function test_maps_missing_profile_to_user_not_found(): void
    {
        $this->authService
            ->shouldReceive('authenticate')
            ->once()
            ->andReturn([
                'token' => 'ocean-token',
                'user' => ['email' => 'ghost@example.com', 'password' => 'secret'],
            ]);

        $this->searchService
            ->shouldReceive('searchUserByEmail')
            ->once()
            ->andThrow(new RuntimeException('OceanExpert Profile Not found.'));

        Log::shouldReceive('channel')->never();

        try {
            $this->strategy->authenticate(['email' => 'ghost@example.com', 'password' => 'secret']);
            $this->fail('Expected OceanExpertAuthenticationException');
        } catch (OceanExpertAuthenticationException $e) {
            $this->assertSame('user_not_found', $e->reason());
            $this->assertFalse($e->isOperationalError());
        }
    }

    public function test_wraps_unexpected_throwable_as_api_error_and_logs(): void
    {
        $this->authService
            ->shouldReceive('authenticate')
            ->once()
            ->andThrow(new \Exception('boom'));

        $logger = Mockery::mock();
        $logger->shouldReceive('error')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                return $message === 'Ocean Expert authentication failed'
                    && $context['email'] === 'test@example.com'
                    && $context['reason'] === 'api_error'
                    && $context['exception'] instanceof \Exception;
            });
        Log::shouldReceive('channel')->with('auth')->once()->andReturn($logger);

        try {
            $this->strategy->authenticate(['email' => 'test@example.com', 'password' => 'secret']);
            $this->fail('Expected OceanExpertAuthenticationException');
        } catch (OceanExpertAuthenticationException $e) {
            $this->assertSame('api_error', $e->reason());
            $this->assertStringContainsString('boom', $e->getMessage());
        }
    }

    public function test_throws_exception_for_missing_email(): void
    {
        $credentials = ['password' => 'password123'];

        $this->expectException(OceanExpertAuthenticationException::class);
        $this->expectExceptionMessage('Invalid credentials provided');

        $this->strategy->authenticate($credentials);
    }
}
