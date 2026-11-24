<?php

declare(strict_types=1);

namespace Tests\Feature\Domains\Notification;

use App\Domains\Opportunity\Enums\Type;
use App\Domains\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NotificationPreferencesToggleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::findOrCreate('partner');
    }

    #[Test]
    public function the_preferences_page_renders_settings_for_an_authenticated_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('notification.preferences.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('notification-preferences/List')
                ->has('settings.opportunity')
                ->where('settings.request', null)
                ->where('settings.master_enabled', true)
            );
    }

    #[Test]
    public function toggling_persists_an_opt_out_for_the_current_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('notification.preferences.toggle'), [
                'entity_type' => 'opportunity',
                'attribute_value' => Type::WEBINAR->value,
                'enabled' => false,
            ])
            ->assertRedirect();

        $this->assertEqualsCanonicalizing(
            [Type::WEBINAR->value],
            $user->fresh()->notificationSetting->opportunity
        );
    }

    #[Test]
    public function toggling_validates_the_entity_and_value(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('notification.preferences.toggle'), [
                'entity_type' => 'nonsense',
                'attribute_value' => Type::WEBINAR->value,
                'enabled' => false,
            ])
            ->assertSessionHasErrors('entity_type');
    }

    #[Test]
    public function toggling_requires_an_enabled_boolean(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('notification.preferences.toggle'), [
                'entity_type' => 'opportunity',
                'attribute_value' => Type::WEBINAR->value,
                // enabled omitted
            ])
            ->assertSessionHasErrors('enabled');
    }

    #[Test]
    public function toggling_is_idempotent_via_http(): void
    {
        $user = User::factory()->create();

        // Disable the same type twice — only one opt-out entry should exist.
        $this->actingAs($user)
            ->put(route('notification.preferences.toggle'), [
                'entity_type' => 'opportunity',
                'attribute_value' => Type::WEBINAR->value,
                'enabled' => false,
            ])
            ->assertRedirect();

        $this->actingAs($user)
            ->put(route('notification.preferences.toggle'), [
                'entity_type' => 'opportunity',
                'attribute_value' => Type::WEBINAR->value,
                'enabled' => false,
            ])
            ->assertRedirect();

        $optOuts = $user->fresh()->notificationSetting->opportunity;
        $this->assertCount(1, array_filter($optOuts, fn ($v) => $v === Type::WEBINAR->value));
    }

    #[Test]
    public function guests_cannot_toggle_preferences(): void
    {
        $this->put(route('notification.preferences.toggle'), [
            'entity_type' => 'opportunity',
            'attribute_value' => Type::WEBINAR->value,
        ])->assertRedirect(route('login'));
    }
}
