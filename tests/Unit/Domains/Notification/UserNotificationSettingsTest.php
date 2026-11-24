<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Notification;

use App\Domains\Notification\Models\UserNotificationSetting;
use App\Domains\Opportunity\Enums\Type;
use App\Domains\Request\Enums\DecadeChallenge;
use App\Domains\User\Models\User;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Pure (no-database) tests for the opt-out helper logic on the User model.
 */
class UserNotificationSettingsTest extends TestCase
{
    /**
     * Build a User with its notificationSetting relation pre-populated (or
     * explicitly null) so the helpers never hit the database.
     *
     * @param  array<string, mixed>|null  $setting  Null = no settings row.
     */
    private function makeUser(?array $setting): User
    {
        $user = new User;
        $user->setRelation(
            'notificationSetting',
            $setting === null ? null : new UserNotificationSetting($setting)
        );

        return $user;
    }

    #[Test]
    public function a_user_with_no_settings_is_subscribed_and_enabled_for_everything(): void
    {
        $user = $this->makeUser(null);

        $this->assertTrue($user->isSubscribedToEmails());
        $this->assertTrue($user->notificationEnabledFor('opportunity', Type::WEBINAR->value));
        $this->assertCount(count(Type::cases()), $user->enabledOpportunityTypes());
        $this->assertCount(count(DecadeChallenge::cases()), $user->enabledRequestChallenges());
    }

    #[Test]
    public function a_disabled_master_switch_disables_everything(): void
    {
        $user = $this->makeUser(['email_notifications_enabled' => false]);

        $this->assertFalse($user->isSubscribedToEmails());
        $this->assertFalse($user->notificationEnabledFor('opportunity', Type::WEBINAR->value));
        $this->assertSame([], $user->enabledOpportunityTypes());
        $this->assertSame([], $user->enabledRequestChallenges());
    }

    #[Test]
    public function opted_out_values_are_excluded_but_others_remain(): void
    {
        $user = $this->makeUser([
            'email_notifications_enabled' => true,
            'opportunity' => [Type::WEBINAR->value],
            'request' => [DecadeChallenge::MARINE_POLLUTION->value],
        ]);

        $this->assertFalse($user->notificationEnabledFor('opportunity', Type::WEBINAR->value));
        $this->assertTrue($user->notificationEnabledFor('opportunity', Type::FELLOWSHIPS->value));
        $this->assertNotContains(Type::WEBINAR->value, $user->enabledOpportunityTypes());
        $this->assertContains(Type::FELLOWSHIPS->value, $user->enabledOpportunityTypes());

        $this->assertFalse($user->notificationEnabledFor('request', DecadeChallenge::MARINE_POLLUTION->value));
        $this->assertNotContains(DecadeChallenge::MARINE_POLLUTION->value, $user->enabledRequestChallenges());
        $this->assertSame(
            count(Type::cases()) - 1,
            count($user->enabledOpportunityTypes())
        );
    }
}
