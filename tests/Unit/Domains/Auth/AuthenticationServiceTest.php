<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Auth;

use App\Domains\Auth\Contracts\AuthenticationServiceInterface;
use App\Domains\Auth\Events\UserAuthenticated;
use App\Domains\Auth\Exceptions\AccountBlockedException;
use App\Domains\Auth\Exceptions\OceanExpertAuthenticationException;
use App\Domains\Auth\Services\AuthenticationService;
use App\Domains\Auth\Services\OceanExpertAuthService;
use App\Domains\Auth\Services\OceanExpertSearchService;
use App\Domains\User\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class AuthenticationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AuthenticationService $service;

    protected OceanExpertAuthService $oceanExpertAuthService;

    protected OceanExpertSearchService $oceanExpertSearchService;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock Ocean Expert services
        $this->oceanExpertAuthService = Mockery::mock(OceanExpertAuthService::class);
        $this->oceanExpertSearchService = Mockery::mock(OceanExpertSearchService::class);

        $this->app->instance(OceanExpertAuthService::class, $this->oceanExpertAuthService);
        $this->app->instance(OceanExpertSearchService::class, $this->oceanExpertSearchService);

        // Resolve service from container
        $this->service = $this->app->make(AuthenticationServiceInterface::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_authenticates_with_valid_ocean_expert_credentials(): void
    {
        Event::fake([UserAuthenticated::class, Login::class]);
        RateLimiter::clear('test@example.com|127.0.0.1');

        // Mock Ocean Expert API responses
        $this->oceanExpertAuthService
            ->shouldReceive('authenticate')
            ->once()
            ->with('test@example.com', 'password123')
            ->andReturn([
                'token' => 'ocean-expert-token',
                'user' => ['email' => 'test@example.com', 'password' => 'password123'],
            ]);

        $this->oceanExpertSearchService
            ->shouldReceive('searchUserByEmail')
            ->once()
            ->with('test@example.com')
            ->andReturn([
                'name' => 'Test User',
                'first_name' => 'Test',
                'last_name' => 'User',
                'country' => 'USA',
                'city' => 'New York',
            ]);

        // Execute authentication
        $user = $this->service->authenticateWithCredentials('test@example.com', 'password123');

        // Assertions
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('test@example.com', $user->email);
        $this->assertEquals('Test User', $user->name);
        $this->assertTrue(Auth::check());
        $this->assertEquals('ocean-expert-token', Session::get('external_api_token'));
        $this->assertNotNull($user->last_login_at);

        Event::assertDispatched(UserAuthenticated::class);
        Event::assertDispatched(Login::class);
    }

    public function test_throws_exception_for_invalid_credentials(): void
    {
        RateLimiter::clear('test@example.com|127.0.0.1');

        // Mock failed authentication
        $this->oceanExpertAuthService
            ->shouldReceive('authenticate')
            ->once()
            ->with('test@example.com', 'wrong-password')
            ->andThrow(OceanExpertAuthenticationException::invalidCredentials());

        // Expect exception
        $this->expectException(OceanExpertAuthenticationException::class);

        $this->service->authenticateWithCredentials('test@example.com', 'wrong-password');
    }

    public function test_rate_limits_authentication_attempts(): void
    {
        $throttleKey = 'test@example.com|127.0.0.1';
        RateLimiter::clear($throttleKey);

        // Mock failed authentication
        $this->oceanExpertAuthService
            ->shouldReceive('authenticate')
            ->times(5)
            ->andThrow(OceanExpertAuthenticationException::invalidCredentials());

        // Make 5 failed attempts
        for ($i = 0; $i < 5; $i++) {
            try {
                $this->service->authenticateWithCredentials('test@example.com', 'wrong');
            } catch (OceanExpertAuthenticationException $e) {
                // Expected
            }
        }

        // 6th attempt should be rate limited
        $this->expectException(ValidationException::class);
        $this->service->authenticateWithCredentials('test@example.com', 'wrong');
    }

    public function test_blocks_authentication_for_blocked_user(): void
    {
        Event::fake();
        RateLimiter::clear('blocked@example.com|127.0.0.1');

        // Create a blocked user
        $user = User::factory()->create([
            'email' => 'blocked@example.com',
            'is_blocked' => true,
        ]);

        // Mock successful Ocean Expert authentication
        $this->oceanExpertAuthService
            ->shouldReceive('authenticate')
            ->once()
            ->andReturn([
                'token' => 'token',
                'user' => ['email' => 'blocked@example.com', 'password' => 'password'],
            ]);

        $this->oceanExpertSearchService
            ->shouldReceive('searchUserByEmail')
            ->once()
            ->andReturn([
                'name' => 'Blocked User',
                'first_name' => 'Blocked',
                'last_name' => 'User',
                'country' => 'USA',
                'city' => 'New York',
            ]);

        // Expect blocked exception
        $this->expectException(AccountBlockedException::class);

        $this->service->authenticateWithCredentials('blocked@example.com', 'password');
    }

    public function test_logout_clears_session_and_invalidates(): void
    {
        // Create and login a user
        $user = User::factory()->create();
        Auth::login($user);
        Session::put('external_api_token', 'test-token');

        // Verify user is authenticated
        $this->assertTrue(Auth::check());

        // Logout
        $this->service->logout();

        // Assertions
        $this->assertFalse(Auth::check());
        $this->assertNull(Session::get('external_api_token'));
    }

    public function test_session_regenerates_on_authentication(): void
    {
        Event::fake();
        RateLimiter::clear('test@example.com|127.0.0.1');

        $oldSessionId = Session::getId();

        // Mock Ocean Expert services
        $this->oceanExpertAuthService
            ->shouldReceive('authenticate')
            ->once()
            ->andReturn([
                'token' => 'token',
                'user' => ['email' => 'test@example.com', 'password' => 'password'],
            ]);

        $this->oceanExpertSearchService
            ->shouldReceive('searchUserByEmail')
            ->once()
            ->andReturn([
                'name' => 'Test User',
                'first_name' => 'Test',
                'last_name' => 'User',
                'country' => 'USA',
                'city' => 'New York',
            ]);

        $this->service->authenticateWithCredentials('test@example.com', 'password');

        // Session ID should be different after authentication
        $this->assertNotEquals($oldSessionId, Session::getId());
    }
}
