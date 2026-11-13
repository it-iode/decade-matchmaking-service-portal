<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Notification;

use App\Domains\Notification\Services\NotificationPreferenceService;
use App\Domains\Opportunity\Enums\Type;
use App\Domains\Request\Enums\DecadeChallenge;
use App\Domains\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NotificationPreferenceServiceTest extends TestCase
{
    use RefreshDatabase;

    private NotificationPreferenceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(NotificationPreferenceService::class);
        Role::findOrCreate('partner');
    }

    #[Test]
    public function toggling_an_enabled_value_disables_it_and_stores_an_opt_out(): void
    {
        $user = User::factory()->create();

        $nowEnabled = $this->service->toggle($user, 'opportunity', Type::WEBINAR->value, false);

        $this->assertFalse($nowEnabled);
        $this->assertEqualsCanonicalizing(
            [Type::WEBINAR->value],
            $user->fresh()->notificationSetting->opportunity
        );
        $this->assertFalse($user->fresh()->notificationEnabledFor('opportunity', Type::WEBINAR->value));
    }

    #[Test]
    public function toggling_a_disabled_value_re_enables_it_and_resumes_master_switch(): void
    {
        $user = User::factory()
            ->unsubscribedFromEmails()
            ->optedOutOf('opportunity', [Type::WEBINAR->value])
            ->create();

        $nowEnabled = $this->service->toggle($user, 'opportunity', Type::WEBINAR->value, true);

        $fresh = $user->fresh();
        $this->assertTrue($nowEnabled);
        $this->assertTrue($fresh->isSubscribedToEmails());
        $this->assertNull($fresh->notificationSetting->opportunity); // empty set collapses to null
    }

    #[Test]
    public function toggling_is_idempotent_when_called_twice_with_the_same_state(): void
    {
        $user = User::factory()->create();

        // Disable twice — second call must not duplicate the opt-out entry.
        $this->service->toggle($user, 'opportunity', Type::WEBINAR->value, false);
        $this->service->toggle($user, 'opportunity', Type::WEBINAR->value, false);

        $optOuts = $user->fresh()->notificationSetting->opportunity;
        $this->assertCount(1, array_filter($optOuts, fn ($v) => $v === Type::WEBINAR->value));
    }

    #[Test]
    public function unsubscribe_from_all_disables_the_master_switch(): void
    {
        $user = User::factory()->create();

        $this->service->unsubscribeFromAllNotifications($user);

        $this->assertFalse($user->fresh()->isSubscribedToEmails());
    }

    #[Test]
    public function get_settings_hides_the_request_card_from_non_partners(): void
    {
        $user = User::factory()->create();

        $settings = $this->service->getSettings($user);

        $this->assertNull($settings['request']);
        $this->assertCount(count(Type::cases()), $settings['opportunity']);
        $this->assertTrue($settings['opportunity'][0]['enabled']);
    }

    #[Test]
    public function get_settings_shows_the_request_card_to_partners_with_opt_out_state(): void
    {
        $user = User::factory()
            ->optedOutOf('request', [DecadeChallenge::MARINE_POLLUTION->value])
            ->create();
        $user->assignRole('partner');

        $settings = $this->service->getSettings($user->fresh());

        $this->assertNotNull($settings['request']);
        $this->assertCount(count(DecadeChallenge::cases()), $settings['request']);

        $otherOption = collect($settings['request'])->firstWhere('value', DecadeChallenge::MARINE_POLLUTION->value);
        $this->assertFalse($otherOption['enabled']);
    }

    #[Test]
    public function toggling_an_invalid_value_is_rejected(): void
    {
        $user = User::factory()->create();

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        $this->service->toggle($user, 'opportunity', 'not-a-real-type', false);
    }
}
