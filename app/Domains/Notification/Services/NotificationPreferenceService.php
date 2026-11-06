<?php

declare(strict_types=1);

namespace App\Domains\Notification\Services;

use App\Domains\Notification\Models\RequestSubscription;
use App\Domains\Opportunity\Enums\Type;
use App\Domains\Request\Enums\DecadeChallenge;
use App\Domains\User\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Manages a user's notification settings under the opt-out model.
 *
 * There is no per-preference entity: every taxonomy value is enabled by
 * default. State lives in the user's {@see \App\Domains\Notification\Models\UserNotificationSetting}
 * row as a master switch (`email_notifications_enabled`) plus a set of disabled
 * values per entity column (`opportunity`, `request`).
 */
class NotificationPreferenceService
{
    public const ENTITY_OPPORTUNITY = 'opportunity';

    public const ENTITY_REQUEST = 'request';

    /**
     * Build the toggle matrix shown on the preferences page.
     *
     * The opportunity card is available to every user; the request card only
     * to partners (mirrors who can receive request notifications).
     *
     * @return array{
     *     master_enabled: bool,
     *     opportunity: array<int, array{value: string, label: string, enabled: bool}>,
     *     request: array<int, array{value: string, label: string, enabled: bool}>|null
     * }
     */
    public function getSettings(User $user): array
    {
        return [
            'master_enabled' => $user->isSubscribedToEmails(),
            'opportunity' => $this->buildCard($user, self::ENTITY_OPPORTUNITY, Type::getOptions()),
            'request' => $user->is_partner
                ? $this->buildCard($user, self::ENTITY_REQUEST, DecadeChallenge::getOptions())
                : null,
        ];
    }

    /**
     * Set a single taxonomy value to the desired enabled state (idempotent).
     *
     * Calling this method twice with the same `$enabled` value has no additional
     * effect — it is safe against rapid-click races. Enabling any value also
     * re-asserts the master switch so a globally-unsubscribed user can resume
     * notifications from the preferences page.
     *
     * @return bool The resulting enabled state of the value.
     */
    public function toggle(User $user, string $entity, string $value, bool $enabled): bool
    {
        $this->assertValidEntity($entity);
        $this->assertValidValue($entity, $value);

        DB::transaction(function () use ($user, $entity, $value, $enabled): void {
            // Lock the settings row so concurrent toggles cannot lose an update.
            $setting = $user->notificationSetting()
                ->lockForUpdate()
                ->firstOrCreate([], ['email_notifications_enabled' => true]);

            $entityOptOuts = $setting->{$entity} ?? [];

            if ($enabled) {
                // Desired state: enabled — remove from opt-outs and resume master switch.
                $entityOptOuts = array_values(array_filter(
                    $entityOptOuts,
                    fn ($v) => $v !== $value
                ));
                $setting->email_notifications_enabled = true;
            } else {
                // Desired state: disabled — add to opt-outs (idempotent: check first).
                if (! in_array($value, $entityOptOuts, true)) {
                    $entityOptOuts[] = $value;
                }
            }

            // Collapse an empty set to null so "no opt-outs" has one representation.
            $setting->{$entity} = empty($entityOptOuts) ? null : array_values($entityOptOuts);
            $setting->save();

            $user->setRelation('notificationSetting', $setting);
        });

        return $enabled;
    }

    /**
     * Re-enable all email notifications for the user.
     *
     * Symmetric counterpart to {@see unsubscribeFromAllNotifications()}: it
     * flips the master switch back on without touching per-value opt-outs, so a
     * globally-unsubscribed user can resume emails from the preferences page.
     */
    public function resubscribe(User $user): void
    {
        $setting = $user->notificationSetting()
            ->firstOrCreate([], ['email_notifications_enabled' => true]);

        $setting->email_notifications_enabled = true;
        $setting->save();

        $user->setRelation('notificationSetting', $setting);
    }

    /**
     * Globally unsubscribe the user from all email notifications.
     * Optionally also removes their request subscriptions.
     */
    public function unsubscribeFromAllNotifications(User $user, bool $removeSubscriptions = false): bool
    {
        DB::beginTransaction();

        try {
            $setting = $user->notificationSetting()
                ->firstOrCreate([], ['email_notifications_enabled' => true]);

            $setting->email_notifications_enabled = false;
            $setting->save();

            $user->setRelation('notificationSetting', $setting);

            if ($removeSubscriptions) {
                RequestSubscription::where('user_id', $user->id)->delete();
            }

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to unsubscribe user from notifications', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Map a taxonomy option list to {value, label, enabled} for a user.
     *
     * @param  array<int, array{value: string, label: string}>  $options
     * @return array<int, array{value: string, label: string, enabled: bool}>
     */
    private function buildCard(User $user, string $entity, array $options): array
    {
        $optOuts = $user->notificationSetting?->{$entity} ?? [];

        return array_map(
            fn (array $option) => [
                'value' => $option['value'],
                'label' => $option['label'],
                'enabled' => ! in_array($option['value'], $optOuts, true),
            ],
            $options
        );
    }

    private function assertValidEntity(string $entity): void
    {
        if (! in_array($entity, [self::ENTITY_OPPORTUNITY, self::ENTITY_REQUEST], true)) {
            abort(422, "Invalid notification entity: {$entity}");
        }
    }

    private function assertValidValue(string $entity, string $value): void
    {
        $valid = $entity === self::ENTITY_OPPORTUNITY
            ? Type::tryFrom($value)
            : DecadeChallenge::tryFrom($value);

        if ($valid === null) {
            abort(422, "Invalid notification value: {$value}");
        }
    }
}
