<?php

declare(strict_types=1);

namespace App\Domains\Request\Listeners;

use App\Domains\Request\Events\RequestValidated;
use App\Domains\User\Models\User;
use App\Infrastructure\Email\Jobs\SendTransactionalEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Listener for RequestValidated event.
 *
 * This listener handles instant email notifications when a request is validated/approved.
 * It sends emails to users who have matching Decade Challenge preferences.
 *
 * NOTE: This is separate from the weekly newsletter system. This sends instant
 * notifications for validated requests matching user preferences.
 */
class SendRequestValidatedNotifications implements ShouldQueue
{
    /**
     * Handle the event.
     *
     * @param  RequestValidated  $event  The request validated event
     */
    public function handle(RequestValidated $event): void
    {
        $request = $event->request;

        try {
            // Ranked Decade Challenges from the normalized store (authoritative).
            $requestChallenges = $this->extractChallengeValues($request->detail?->decade_challenges);

            if (empty($requestChallenges)) {
                Log::info('Request has no Decade Challenges, skipping instant notifications', [
                    'request_id' => $request->id,
                ]);

                return;
            }

            // Opt-out model: notify partners who are subscribed (master switch on)
            // and have not opted out of at least one of this request's challenges.
            $matchingUsers = User::role('partner')
                ->whereHas('notificationSetting', fn ($q) => $q->where('email_notifications_enabled', true))
                ->where('is_blocked', false)
                ->with('notificationSetting')
                ->get()
                ->filter(function (User $user) use ($requestChallenges) {
                    foreach ($requestChallenges as $challenge) {
                        if ($user->notificationEnabledFor('request', $challenge)) {
                            return true;
                        }
                    }

                    return false;
                });

            if ($matchingUsers->isEmpty()) {
                Log::info('No users with matching Decade Challenge preferences found', [
                    'request_id' => $request->id,
                    'decade_challenges' => $requestChallenges,
                ]);

                return;
            }

            // Send email to each matching user
            $sentCount = 0;
            $skippedCount = 0;

            foreach ($matchingUsers as $user) {
                // Prevent duplicate notifications within 24 hours
                $cacheKey = "request_notification:{$request->id}:{$user->id}";

                if (Cache::has($cacheKey)) {
                    Log::debug('Skipping duplicate notification', [
                        'request_id' => $request->id,
                        'user_id' => $user->id,
                    ]);
                    $skippedCount++;

                    continue;
                }
                // Mark as sent for 24 hours
                Cache::put($cacheKey, true, now()->addDay());
                dispatch(new SendTransactionalEmail(
                    'request.notification.instant',
                    $user,
                    [
                        'Request_Title' => $request->detail?->capacity_development_title ?? 'N/A',
                        'Request_Link' => route('request.public.show', $request->id),
                        'user_name' => $user->name,
                        'Request_Challenges' => implode(', ', $requestChallenges),
                        'UNSUB' => route('unsubscribe.show', $user->id),
                        'UPDATE_PROFILE' => route('notification.preferences.index'),
                    ]
                ));

                $sentCount++;
            }

            Log::info('Request validated notifications dispatched', [
                'request_id' => $request->id,
                'sent' => $sentCount,
                'skipped' => $skippedCount,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send request validated notifications', [
                'request_id' => $request->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Flatten the ranked {primary, secondary, tertiary} object into the
     * list of non-null challenge values, in rank order.
     *
     * @param  mixed  $challenges  Raw `decade_challenges` value from the detail model
     * @return array<int, string>
     */
    private function extractChallengeValues(mixed $challenges): array
    {
        if (! is_array($challenges)) {
            return [];
        }

        return array_values(array_filter([
            $challenges['primary'] ?? null,
            $challenges['secondary'] ?? null,
            $challenges['tertiary'] ?? null,
        ], fn ($value) => is_string($value) && $value !== ''));
    }
}
