<?php

declare(strict_types=1);

namespace Tests\Feature\Domains\Auth;

use App\Domains\Auth\Contracts\AuthenticationServiceInterface;
use App\Domains\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * OtpController::verify must honor url.intended (like the credentials and
 * OAuth logins) so flows interrupted by the auth middleware, or started from
 * a page with on-page sign-in (e.g. the ODC travel support submission page),
 * land back where they left off.
 */
class OtpIntendedRedirectTest extends TestCase
{
    use RefreshDatabase;

    private function mockSuccessfulOtpAuthentication(): void
    {
        $user = User::factory()->create();

        $this->mock(AuthenticationServiceInterface::class)
            ->shouldReceive('authenticateWithOtp')
            ->once()
            ->andReturn($user);
    }

    public function test_verify_redirects_to_intended_url_when_present(): void
    {
        $this->mockSuccessfulOtpAuthentication();

        $intended = url('/opportunity/odc-travel-support/create');

        $this->withSession(['otp_email' => 'user@example.org', 'url.intended' => $intended])
            ->postJson(route('otp.verify.submit'), ['code' => '123456'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('redirect', $intended)
            ->assertSessionMissing('url.intended');
    }

    public function test_verify_falls_back_to_home_without_intended_url(): void
    {
        $this->mockSuccessfulOtpAuthentication();

        $this->withSession(['otp_email' => 'user@example.org'])
            ->postJson(route('otp.verify.submit'), ['code' => '123456'])
            ->assertOk()
            ->assertJsonPath('redirect', route('user.home'));
    }
}
