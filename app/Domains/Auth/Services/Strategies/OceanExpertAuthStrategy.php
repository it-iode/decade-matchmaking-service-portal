<?php

declare(strict_types=1);

namespace App\Domains\Auth\Services\Strategies;

use App\Domains\Auth\Contracts\AuthenticationStrategyInterface;
use App\Domains\Auth\DTOs\AuthenticationResult;
use App\Domains\Auth\Exceptions\OceanExpertAuthenticationException;
use App\Domains\Auth\Services\OceanExpertAuthService;
use App\Domains\Auth\Services\OceanExpertSearchService;
use App\Domains\User\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class OceanExpertAuthStrategy implements AuthenticationStrategyInterface
{
    public function __construct(
        private readonly OceanExpertAuthService $authService,
        private readonly OceanExpertSearchService $searchService
    ) {}

    /**
     * Authenticate user against Ocean Expert API
     *
     * @param  array{email: string, password: string}  $credentials
     *
     * @throws OceanExpertAuthenticationException
     */
    public function authenticate(array $credentials): AuthenticationResult
    {
        if (! isset($credentials['email'], $credentials['password'])) {
            throw OceanExpertAuthenticationException::invalidCredentials();
        }

        $email = $credentials['email'];
        $password = $credentials['password'];

        try {
            ['token' => $token] = $this->authService->authenticate($email, $password);

            $profile = $this->fetchProfile($email);

            $user = $this->syncLocalUser($email, $password, $profile);

            return new AuthenticationResult(
                user: $user,
                authMethod: 'ocean_expert',
                externalToken: $token,
            );
        } catch (OceanExpertAuthenticationException $e) {
            $this->logOperationalFailure($e, $email);

            throw $e;
        } catch (Throwable $e) {
            $wrapped = OceanExpertAuthenticationException::apiError($e->getMessage());

            Log::channel('auth')->error('Ocean Expert authentication failed', [
                'email' => $email,
                'reason' => $wrapped->reason(),
                'exception' => $e,
            ]);

            throw $wrapped;
        }
    }

    /**
     * Check if this strategy supports the given credentials
     */
    public function supports(array $credentials): bool
    {
        return isset($credentials['email'], $credentials['password'])
            && ! isset($credentials['socialite_user']);
    }

    /**
     * Fetch the Ocean Expert profile, translating "not found" into a domain exception
     *
     * @return array<string, mixed>
     *
     * @throws OceanExpertAuthenticationException
     */
    private function fetchProfile(string $email): array
    {
        try {
            return $this->searchService->searchUserByEmail($email);
        } catch (RuntimeException) {
            throw OceanExpertAuthenticationException::userNotFound($email);
        }
    }

    /**
     * Log failures caused by Ocean Expert or by us, not by the user.
     *
     * User errors (wrong password, unknown account) are already logged as a
     * warning by AuthenticationService::logAuthenticationFailure(); repeating
     * them here would duplicate the audit trail.
     */
    private function logOperationalFailure(OceanExpertAuthenticationException $e, string $email): void
    {
        if (! $e->isOperationalError()) {
            return;
        }

        Log::channel('auth')->log($e->logLevel(), 'Ocean Expert authentication failed', [
            'email' => $email,
            'reason' => $e->reason(),
            ...$e->context(),
        ]);
    }

    /**
     * Synchronize local user with Ocean Expert profile data
     *
     * @param  string  $email  User email
     * @param  string  $password  User password
     * @param  array<string, mixed>  $profile  Ocean Expert profile data
     *
     * @throws Throwable
     */
    private function syncLocalUser(string $email, string $password, array $profile): User
    {
        return DB::transaction(function () use ($email, $password, $profile) {
            $userData = [
                'name' => $profile['name'] ?? ($profile['first_name'].' '.$profile['last_name']),
                'password' => Hash::make($password),
                'first_name' => $profile['first_name'] ?? null,
                'last_name' => $profile['last_name'] ?? null,
                'country' => $profile['country'] ?? null,
                'city' => $profile['city'] ?? null,
            ];

            return User::updateOrCreate(
                ['email' => $email],
                $userData
            );
        });
    }
}
