<?php

declare(strict_types=1);

namespace App\Domains\Notification\Services;

use App\Domains\Opportunity\Enums\Type;
use App\Domains\Opportunity\Services\OpportunityQueryBuilder;
use App\Domains\User\Models\User;
use App\Infrastructure\Email\Jobs\SendTransactionalEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Sends the weekly opportunity newsletter under the opt-out model.
 *
 * Responsibilities are split into small pieces:
 *  - {@see subscribedAudience()} is the single source of truth for WHO receives
 *    the email (non-blocked users with the master switch on).
 *  - {@see contentFor()} / {@see renderForTypes()} build WHAT each user gets,
 *    memoising the rendered HTML per opt-out "signature" so users sharing the
 *    same allowed-type set reuse one Blade render.
 *  - {@see dispatchNewsletterEmail()} queues the actual email.
 *  - {@see sendWeeklyNewsletter()} is a thin orchestrator over the above.
 *
 * Scaling: the open-opportunity pool is loaded once per run and filtered in
 * memory; users are streamed in chunks of 500 to bound peak memory.
 */
class NewsletterService
{
    /**
     * Maximum number of opportunities included in a single email.
     */
    public const MAX_OPPORTUNITIES = 15;

    public function __construct(
        private readonly OpportunityQueryBuilder $opportunityQueryBuilder
    ) {}

    /**
     * The audience for every newsletter operation: non-blocked users whose
     * master email switch is on. A JOIN on the 1:1 settings table keeps the
     * filter in SQL so it composes with chunkById and stays consistent between
     * sending and reporting.
     */
    public function subscribedAudience(): Builder
    {
        return User::query()
            ->join('user_notification_settings as ns', 'ns.user_id', '=', 'users.id')
            ->where('users.is_blocked', false)
            ->where('ns.email_notifications_enabled', true)
            ->select('users.*');
    }

    /**
     * Send the weekly opportunity newsletter to the subscribed audience.
     *
     * @return array{processed: int, sent: int, failed: int, errors: array}
     */
    public function sendWeeklyNewsletter(): array
    {
        Log::info('[NewsletterService] Starting weekly opportunity newsletter send');

        $stats = ['processed' => 0, 'sent' => 0, 'failed' => 0, 'errors' => []];

        // Load the open-opportunity pool exactly once for the whole run.
        $pool = $this->opportunityQueryBuilder
            ->buildActiveOpenByClosingDateQuery()
            ->get();

        Log::info('[NewsletterService] Loaded open opportunity pool', [
            'pool_size' => $pool->count(),
        ]);

        // Rendered HTML memoised per opt-out signature (see renderForTypes()).
        $renderCache = [];

        $this->subscribedAudience()
            ->with('notificationSetting')
            ->chunkById(500, function (Collection $users) use ($pool, &$renderCache, &$stats): void {
                foreach ($users as $user) {
                    $stats['processed']++;

                    try {
                        $content = $this->contentFor($user, $pool, $renderCache);

                        // Nothing relevant for this user (opted out of everything
                        // or no open opportunity matches their allowed types).
                        if ($content === null) {
                            continue;
                        }

                        $this->dispatchNewsletterEmail($user, $content['html'], $content['count'], $content['total']);
                        $stats['sent']++;
                    } catch (\Throwable $e) {
                        $stats['failed']++;
                        $stats['errors'][] = [
                            'user_id' => $user->id,
                            'email' => $user->email,
                            'error' => $e->getMessage(),
                        ];

                        Log::error('[NewsletterService] Failed to send newsletter to user', [
                            'user_id' => $user->id,
                            'email' => $user->email,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                    }
                }
            }, 'users.id', 'id');

        Log::info('[NewsletterService] Weekly opportunity newsletter send completed', [
            'processed' => $stats['processed'],
            'sent' => $stats['sent'],
            'failed' => $stats['failed'],
            'render_cache_entries' => count($renderCache),
        ]);

        return $stats;
    }

    /**
     * Resolve the opportunity block for a user, reusing the per-signature cache.
     *
     * @param  Collection<int, \App\Domains\Opportunity\Models\Opportunity>  $pool
     * @param  array<string, array{html: string, count: int, total: int}>  $renderCache
     * @return array{html: string, count: int, total: int}|null Null when the user gets no email.
     */
    private function contentFor(User $user, Collection $pool, array &$renderCache): ?array
    {
        $enabledTypes = $user->enabledOpportunityTypes();

        if (empty($enabledTypes)) {
            return null;
        }

        sort($enabledTypes);
        $signature = implode(',', $enabledTypes);

        $content = $renderCache[$signature]
            ??= $this->renderForTypes($enabledTypes, $pool);

        return $content['count'] === 0 ? null : $content;
    }

    /**
     * Filter the pool to a set of allowed types and render the email block.
     *
     * @param  array<int, string>  $enabledTypes
     * @param  Collection<int, \App\Domains\Opportunity\Models\Opportunity>  $pool
     * @return array{html: string, count: int, total: int}
     */
    private function renderForTypes(array $enabledTypes, Collection $pool): array
    {
        $matching = $pool->filter(
            fn ($opportunity) => in_array($this->typeValue($opportunity->type), $enabledTypes, true)
        );

        $total = $matching->count();
        $items = $matching->take(self::MAX_OPPORTUNITIES)->values();

        $html = $items->isEmpty() ? '' : view('emails.newsletter.opportunities', [
            'opportunities' => $items->toArray(),
            'total' => $total,
            'shown' => $items->count(),
            'view_all_url' => route('opportunity.list'),
        ])->render();

        return ['html' => $html, 'count' => $items->count(), 'total' => $total];
    }

    /**
     * Normalise an opportunity's type to its string value.
     */
    private function typeValue(mixed $type): string
    {
        return $type instanceof Type ? $type->value : (string) $type;
    }

    /**
     * Queue the newsletter email for a user with pre-rendered HTML.
     *
     * Per-user personalisation (UNSUB, UPDATE_PROFILE, user_name) lives in the
     * Mandrill template variables, not in the shared HTML block.
     */
    private function dispatchNewsletterEmail(User $user, string $opportunitiesHtml, int $opportunityCount, int $total): void
    {
        $variables = [
            'UNSUB' => route('unsubscribe.show', $user->id),
            'UPDATE_PROFILE' => route('notification.preferences.index'),
            'user_name' => $user->name ?? $user->email,
            'opportunity_count' => $opportunityCount,
            'view_all_url' => route('opportunity.list'),
        ];

        $options = [
            'template_content' => [
                ['name' => 'opportunities_section', 'content' => $opportunitiesHtml],
            ],
        ];

        SendTransactionalEmail::dispatch('opportunity.newsletter.weekly', $user, $variables, $options);

        Log::debug('[NewsletterService] Opportunity newsletter email queued', [
            'user_id' => $user->id,
            'email' => $user->email,
            'opportunity_count' => $opportunityCount,
            'total_open' => $total,
        ]);
    }

    /**
     * Newsletter statistics for monitoring.
     *
     * @return array{subscribed_users: int, last_send: mixed, last_send_stats: array}
     */
    public function getNewsletterStats(): array
    {
        return [
            'subscribed_users' => $this->subscribedAudience()->count(),
            'last_send' => cache()->get('newsletter:last_send_date'),
            'last_send_stats' => cache()->get('newsletter:last_send_stats', []),
        ];
    }
}
