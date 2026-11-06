<?php

declare(strict_types=1);

namespace App\Domains\Auth\Controllers;

use App\Domains\Auth\Contracts\AuthenticationServiceInterface;
use App\Domains\Auth\Exceptions\OAuthAuthenticationException;
use App\Domains\Auth\Services\OAuthConfigService;
use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class SocialController extends Controller
{
    public function __construct(
        private readonly AuthenticationServiceInterface $authService,
        private readonly OAuthConfigService $oauthConfigService
    ) {}

    /**
     * Redirect to LinkedIn for authentication
     */
    public function linkedinRedirect(): RedirectResponse
    {
        return $this->redirectToProvider('linkedin-openid', 'LinkedIn');
    }

    /**
     * Handle LinkedIn callback
     */
    public function linkedinCallback(): RedirectResponse
    {
        return $this->handleProviderCallback('linkedin-openid', 'linkedin');
    }

    /**
     * Redirect to Google for authentication
     */
    public function googleRedirect(): RedirectResponse
    {
        return $this->redirectToProvider('google', 'Google');
    }

    /**
     * Handle Google callback
     */
    public function googleCallback(): RedirectResponse
    {
        return $this->handleProviderCallback('google', 'google');
    }

    /**
     * Generic redirect to OAuth provider
     */
    protected function redirectToProvider(string $driver, string $providerName): RedirectResponse
    {
        try {
            Log::info("Redirecting to {$providerName} OAuth", [
                'driver' => $driver,
            ]);

            $this->oauthConfigService->applyCredentials($driver);

            return Socialite::driver($driver)
                ->stateless()
                ->redirect();
        } catch (Exception $e) {
            Log::error("{$providerName} redirect error", [
                'driver' => $driver,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('sign.in')
                ->with('error', "Unable to redirect to {$providerName}. Please try again.");
        }
    }

    /**
     * Generic handler for OAuth provider callback
     */
    protected function handleProviderCallback(string $driver, string $provider): RedirectResponse
    {
        try {
            $this->oauthConfigService->applyCredentials($driver);

            $socialUser = Socialite::driver($driver)
                ->stateless()
                ->user();

            // Validate that email is provided
            if (! $socialUser->getEmail()) {
                Log::warning("{$provider} OAuth: Missing email", [
                    'provider' => $provider,
                    'provider_id' => $socialUser->getId(),
                ]);

                return redirect()->route('sign.in')
                    ->with('error', 'Unable to retrieve email from '.$this->getProviderDisplayName($provider).'. Please try another sign-in method.');
            }

            Log::info("{$provider} OAuth callback received", [
                'provider' => $provider,
                'email' => $socialUser->getEmail(),
                'provider_id' => $socialUser->getId(),
            ]);

            return $this->processOAuthUser($socialUser, $provider);
        } catch (Exception $e) {
            Log::error("{$provider} callback error", [
                'provider' => $provider,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->route('sign.in')
                ->with('error', 'Unable to authenticate with '.$this->getProviderDisplayName($provider).'. Please try again or use email/password.');
        }
    }

    /**
     * Process OAuth user (create or update)
     */
    protected function processOAuthUser($socialUser, string $provider): RedirectResponse
    {
        try {
            $user = $this->authService->authenticateWithOAuth($socialUser, $provider);

            $providerName = $this->getProviderDisplayName($provider);
            $message = $user->wasRecentlyCreated
                ? 'Welcome! Your account has been created successfully.'
                : "Successfully signed in with {$providerName}!";

            return redirect()
                ->intended('/home')
                ->with('status', $message);
        } catch (OAuthAuthenticationException $e) {
            return redirect()->route('sign.in')
                ->with('error', $e->getMessage());
        } catch (Exception $e) {
            Log::error('OAuth user processing error', [
                'provider' => $provider,
                'email' => $socialUser->getEmail() ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('sign.in')
                ->with('error', 'An error occurred during authentication. Please try again.');
        }
    }

    /**
     * Get provider display name for user messages
     */
    protected function getProviderDisplayName(string $provider): string
    {
        $displayNames = [
            'linkedin' => 'LinkedIn',
            'google' => 'Google',
        ];

        return $displayNames[$provider] ?? ucfirst($provider);
    }
}
