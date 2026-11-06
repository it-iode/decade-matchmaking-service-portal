<?php

declare(strict_types=1);

namespace App\Domains\User\Services\Invitation;

use App\Domains\User\Models\User;
use App\Domains\User\Models\UserInvitation;
use App\Infrastructure\Email\Jobs\SendTransactionalEmail;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class InvitationService
{
    private const EXPIRATION_DAYS = 7;

    public function __construct(
        private readonly InvitationRepository $repository
    ) {}

    /**
     * Get paginated invitations with search and sorting
     */
    public function getInvitationsPaginated(array $searchFilters = [], array $sortFilters = []): LengthAwarePaginator
    {
        return $this->repository->getPaginated($searchFilters, $sortFilters);
    }

    /**
     * Get invitation statistics
     *
     * @return array{total: int, pending: int, accepted: int, expired: int}
     */
    public function getStatistics(): array
    {
        return $this->repository->getStatistics();
    }

    /**
     * Create an invitation (user account created on acceptance)
     */
    public function invite(string $email, string $name, User $inviter): UserInvitation
    {
        return DB::transaction(function () use ($email, $name, $inviter) {
            $normalizedEmail = strtolower(trim($email));

            // Cancel any existing pending invitations for this email
            UserInvitation::where('email', $normalizedEmail)
                ->whereNull('accepted_at')
                ->delete();

            // Create invitation with name (NO user creation yet)
            $invitation = UserInvitation::create([
                'name' => $name,
                'email' => $normalizedEmail,
                'token' => Str::random(64),
                'invited_by' => $inviter->id,
                'expires_at' => now()->addDays(self::EXPIRATION_DAYS),
            ]);

            $this->sendInvitationEmail($invitation);

            Log::info('User invitation sent', [
                'email' => $normalizedEmail,
                'name' => $name,
                'invited_by' => $inviter->id,
                'invitation_id' => $invitation->id,
            ]);

            return $invitation;
        });
    }

    /**
     * Validate an invitation token
     */
    public function validateToken(string $token): ?UserInvitation
    {
        $invitation = UserInvitation::where('token', $token)->first();

        if (! $invitation) {
            return null;
        }

        if ($invitation->isExpired() || $invitation->isAccepted()) {
            return null;
        }

        return $invitation;
    }

    /**
     * Accept an invitation - create user account and mark as accepted
     */
    public function accept(UserInvitation $invitation): User
    {
        return DB::transaction(function () use ($invitation) {
            // Check if user already exists (edge case)
            $user = User::where('email', $invitation->email)->first();

            if (! $user) {
                // Create user with name from invitation
                $user = User::create([
                    'name' => $invitation->name,
                    'email' => $invitation->email,
                    'email_verified_at' => now(),
                    'password' => null, // OTP only
                ]);

                Log::info('User created from invitation', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'invitation_id' => $invitation->id,
                ]);
            } else {
                // Update existing user's email verification
                $user->update(['email_verified_at' => now()]);

                Log::info('Existing user verified via invitation', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'invitation_id' => $invitation->id,
                ]);
            }

            // Mark invitation as accepted
            $invitation->update(['accepted_at' => now()]);

            Log::info('Invitation accepted', [
                'user_id' => $user->id,
                'email' => $user->email,
                'invitation_id' => $invitation->id,
            ]);

            return $user;
        });
    }

    /**
     * Resend an existing invitation
     */
    public function resend(UserInvitation $invitation): UserInvitation
    {
        // Reset expiration and generate new token
        $invitation->update([
            'token' => Str::random(64),
            'expires_at' => now()->addDays(self::EXPIRATION_DAYS),
        ]);

        $invitation = $invitation->fresh();

        $this->sendInvitationEmail($invitation);

        Log::info('User invitation resent', [
            'email' => $invitation->email,
            'invitation_id' => $invitation->id,
        ]);

        return $invitation;
    }

    /**
     * Send the invitation email
     */
    private function sendInvitationEmail(UserInvitation $invitation): void
    {
        // Ensure inviter relationship is loaded
        $invitation->loadMissing('inviter');

        SendTransactionalEmail::dispatch(
            'user.invitation',
            ['email' => $invitation->email, 'name' => $invitation->name],
            [
                'user_name' => $invitation->name,
                'Link' => route('invitation.show', ['token' => $invitation->token]),
                'inviter_name' => $invitation->inviter?->name ?? 'Ocean Decade Portal Admin',
                'expires_at' => $invitation->expires_at->format('F j, Y'),
            ]
        );
    }
}
