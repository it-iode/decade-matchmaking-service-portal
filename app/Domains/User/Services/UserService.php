<?php

declare(strict_types=1);

namespace App\Domains\User\Services;

use App\Domains\User\Events\UserRoleChanged;
use App\Domains\User\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

readonly class UserService
{
    private const PARTNER_CANDIDATE_ROLES = ['partner', 'administrator'];

    public function __construct(
        private UserRepository $repository,
        private UserAnalyticsService $analytics
    ) {}

    /**
     * Get paginated users for admin grid
     */
    public function getUsersPaginated(
        array $searchFilters = [],
        array $sortFilters = []
    ): LengthAwarePaginator {
        return $this->repository->getPaginated($searchFilters, $sortFilters);
    }

    /**
     * Assign roles to user
     *
     * @throws Throwable
     */
    public function assignRoles(User $user, array $roleNames): User
    {
        return DB::transaction(function () use ($user, $roleNames) {
            $previousRoles = $user->roles;
            $user->syncRoles($roleNames);
            Log::info('User roles updated', [
                'user_id' => $user->id,
                'roles' => $roleNames,
                'updated_by' => auth()->id(),
            ]);
            UserRoleChanged::dispatch($user, $previousRoles->toArray(), $roleNames);

            return $user->fresh(['roles']);
        });
    }

    /**
     * Block/unblock user account
     *
     * @throws Throwable
     */
    public function toggleBlockStatus(User $user, bool $blocked): User
    {
        return DB::transaction(function () use ($user, $blocked) {
            $this->repository->update($user, [
                'is_blocked' => $blocked,
            ]);

            if ($blocked) {
                // Terminate all active sessions
                DB::table('sessions')->where('user_id', $user->id)->delete();
            }

            Log::info('User block status updated', [
                'user_id' => $user->id,
                'blocked' => $blocked,
                'updated_by' => auth()->id(),
            ]);

            return $user->fresh();
        });
    }

    /**
     * Get user details with statistics
     */
    public function getUserDetails(User $user): array
    {
        return [
            'user' => $user->load(['roles', 'permissions']),
            'statistics' => $this->analytics->getUserStatistics($user),
            'activity' => $this->analytics->getUserActivitySummary($user),
        ];
    }

    public function searchUsers(string $query, int $limit = 20): Collection
    {
        return $this->repository->searchByQuery($query, $limit);
    }

    public function getAllAdmins(): Collection
    {
        return User::role('administrator')->get();
    }

    /**
     * Get users eligible to be selected as offer partners (partners and administrators).
     *
     * @return Collection<int, User>
     */
    public function getPartnerCandidates(): Collection
    {
        return $this->repository->getSelectionCandidatesByRoles(self::PARTNER_CANDIDATE_ROLES);
    }

    /**
     * Create a new user from OAuth authentication data
     *
     * @param  array{email: string, name: string, provider_id: string, avatar: ?string}  $oauthData
     * @param  string  $provider  The OAuth provider name (e.g., 'google', 'linkedin')
     * @return User The created user
     *
     * @throws Throwable
     */
    public function createUserFromOAuth(array $oauthData, string $provider): User
    {
        return DB::transaction(function () use ($oauthData, $provider) {
            $nameParts = $this->parseFullName($oauthData['name'] ?? null);

            $user = $this->repository->create([
                'name' => $oauthData['name'] ?? '',
                'email' => $oauthData['email'],
                'provider' => $provider,
                'provider_id' => $oauthData['provider_id'],
                'avatar' => $oauthData['avatar'] ?? null,
                'first_name' => $nameParts['first_name'],
                'last_name' => $nameParts['last_name'],
                'email_verified_at' => now(), // OAuth emails are verified by provider
                'password' => null, // No password needed for social auth
            ]);

            Log::info('Created new user from OAuth', [
                'user_id' => $user->id,
                'provider' => $provider,
                'email' => $user->email,
            ]);

            return $user;
        });
    }

    /**
     * Update existing user with OAuth authentication data
     *
     * @param  User  $user  The existing user to update
     * @param  array{provider_id: string, avatar: ?string}  $oauthData  OAuth data to update
     * @param  string  $provider  The OAuth provider name
     * @return User The updated user
     *
     * @throws Throwable
     */
    public function updateUserWithOAuth(User $user, array $oauthData, string $provider): User
    {
        return DB::transaction(function () use ($user, $oauthData, $provider) {
            // Only update OAuth data if user has no provider or same provider
            if (! $user->provider || $user->provider === $provider) {
                $this->repository->update($user, [
                    'provider' => $provider,
                    'provider_id' => $oauthData['provider_id'],
                    'avatar' => $oauthData['avatar'] ?? null,
                ]);

                Log::info('Updated existing user with OAuth data', [
                    'user_id' => $user->id,
                    'provider' => $provider,
                ]);
            } else {
                Log::info('User signed in with different provider', [
                    'user_id' => $user->id,
                    'existing_provider' => $user->provider,
                    'new_provider' => $provider,
                ]);
            }

            return $user->fresh();
        });
    }

    /**
     * Parse full name into first and last name components
     *
     * @param  string|null  $fullName  The full name to parse
     * @return array{first_name: string, last_name: string}
     */
    private function parseFullName(?string $fullName): array
    {
        if (! $fullName) {
            return ['first_name' => '', 'last_name' => ''];
        }

        $nameParts = explode(' ', trim($fullName), 2);

        return [
            'first_name' => $nameParts[0] ?? '',
            'last_name' => $nameParts[1] ?? '',
        ];
    }
}
