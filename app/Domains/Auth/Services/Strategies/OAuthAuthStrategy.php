<?php

declare(strict_types=1);

namespace App\Domains\Auth\Services\Strategies;

use App\Domains\Auth\Contracts\AuthenticationStrategyInterface;
use App\Domains\Auth\DTOs\AuthenticationResult;
use App\Domains\Auth\DTOs\OAuthMetadata;
use App\Domains\Auth\Exceptions\OAuthAuthenticationException;
use App\Domains\User\Models\User;
use App\Domains\User\Services\UserService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Throwable;

class OAuthAuthStrategy implements AuthenticationStrategyInterface
{
    public function __construct(
        private readonly UserService $userService
    ) {}

    /**
     * Authenticate user with OAuth provider
     *
     * @param  array{socialite_user: SocialiteUser, provider: string}  $credentials
     *
     * @throws OAuthAuthenticationException
     */
    public function authenticate(array $credentials): AuthenticationResult
    {
        if (! isset($credentials['socialite_user'], $credentials['provider'])) {
            throw OAuthAuthenticationException::missingCredentials();
        }

        /** @var SocialiteUser $socialiteUser */
        $socialiteUser = $credentials['socialite_user'];
        $provider = $credentials['provider'];

        // Validate email presence
        if (! $socialiteUser->getEmail()) {
            throw OAuthAuthenticationException::missingEmail($provider);
        }

        try {
            // Use database transaction for data consistency
            $user = DB::transaction(function () use ($socialiteUser, $provider) {
                $existingUser = User::where('email', $socialiteUser->getEmail())->first();

                $oauthData = [
                    'provider_id' => $socialiteUser->getId(),
                    'avatar' => $socialiteUser->getAvatar(),
                ];

                if ($existingUser) {
                    return $this->userService->updateUserWithOAuth(
                        $existingUser,
                        $oauthData,
                        $provider
                    );
                }

                // Create new user
                return $this->userService->createUserFromOAuth([
                    'email' => $socialiteUser->getEmail(),
                    'name' => $socialiteUser->getName() ?? '',
                    ...$oauthData,
                ], $provider);
            });

            return new AuthenticationResult(
                user: $user,
                authMethod: $provider,
                oauthMetadata: new OAuthMetadata(
                    provider: $provider,
                    providerId: $socialiteUser->getId(),
                    accessToken: $socialiteUser->token ?? null,
                    refreshToken: $socialiteUser->refreshToken ?? null,
                ),
            );
        } catch (Throwable $e) {
            Log::channel('auth')->error('OAuth authentication failed', [
                'provider' => $provider,
                'email' => $socialiteUser->getEmail(),
                'error' => $e->getMessage(),
            ]);

            if ($e instanceof OAuthAuthenticationException) {
                throw $e;
            }

            throw OAuthAuthenticationException::providerError($provider, $e->getMessage());
        }
    }

    /**
     * Check if this strategy supports the given credentials
     */
    public function supports(array $credentials): bool
    {
        return isset($credentials['socialite_user'], $credentials['provider']);
    }
}
