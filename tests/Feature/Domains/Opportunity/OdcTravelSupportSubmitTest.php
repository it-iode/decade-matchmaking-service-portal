<?php

declare(strict_types=1);

namespace Tests\Feature\Domains\Opportunity;

use App\Domains\Opportunity\Enums\CoverageActivity;
use App\Domains\Opportunity\Enums\Status;
use App\Domains\Opportunity\Enums\TargetAudience;
use App\Domains\Opportunity\Enums\ThematicAreas;
use App\Domains\Opportunity\Enums\Type;
use App\Domains\Opportunity\Models\Opportunity;
use App\Domains\User\Models\User;
use App\Shared\Enums\Language;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\Support\ReloadsOpportunityRoutes;
use Tests\TestCase;

class OdcTravelSupportSubmitTest extends TestCase
{
    use RefreshDatabase;
    use ReloadsOpportunityRoutes;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'administrator']);
        Role::create(['name' => 'partner']);
        Role::create(['name' => 'user']);

        $this->reloadOpportunityRoutes('opportunity.odc-travel-support.submit');
    }

    /**
     * The payload the home-page dialog sends: no coverage, location, thematic areas or languages.
     *
     * @return array<string, mixed>
     */
    private function reducedPayload(array $overrides = []): array
    {
        return array_merge([
            'co_organizers' => ['UNESCO'],
            'title' => 'Travel support for early-career researchers',
            'type' => Type::ODC_TRAVEL_SUPPORT->value,
            'closing_date' => now()->addWeeks(2)->toDateString(),
            'target_audience' => [TargetAudience::RESEARCHERS->value],
            'summary' => 'Covers flights and accommodation.',
            'url' => 'https://example.org/apply',
            'key_words' => ['travel', 'conference'],
        ], $overrides);
    }

    /**
     * @return array<string, mixed>
     */
    private function fullPayload(array $overrides = []): array
    {
        return array_merge($this->reducedPayload([
            'type' => Type::TRAINING->value,
            'coverage_activity' => CoverageActivity::GLOBAL->value,
            'implementation_location' => 'Global',
            'thematic_areas' => [ThematicAreas::MARINE_BIODIVERSITY->value],
            'target_languages' => [Language::ENGLISH->value],
        ]), $overrides);
    }

    public function test_user_without_any_role_can_submit(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('opportunity.odc-travel-support.submit'), $this->reducedPayload());

        $response->assertRedirect(route('index'));
        $response->assertSessionHas('success');

        $opportunity = Opportunity::firstOrFail();
        $this->assertSame(Type::ODC_TRAVEL_SUPPORT, $opportunity->type);
        $this->assertSame(Status::PENDING_REVIEW, $opportunity->status);
        $this->assertSame($user->id, $opportunity->user_id);
        $this->assertNull($opportunity->coverage_activity);
        $this->assertNull($opportunity->implementation_location);
        $this->assertNull($opportunity->thematic_areas);
        $this->assertNull($opportunity->target_languages);
    }

    public function test_user_with_only_user_role_can_submit(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        $this->actingAs($user)
            ->post(route('opportunity.odc-travel-support.submit'), $this->reducedPayload())
            ->assertRedirect(route('index'));

        $this->assertDatabaseHas('opportunities', ['type' => Type::ODC_TRAVEL_SUPPORT->value]);
    }

    public function test_type_is_forced_server_side(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('opportunity.odc-travel-support.submit'), $this->reducedPayload(['type' => Type::TRAINING->value]))
            ->assertRedirect(route('index'));

        $this->actingAs($user)
            ->post(route('opportunity.odc-travel-support.submit'), array_diff_key($this->reducedPayload(), ['type' => null]))
            ->assertRedirect(route('index'));

        $this->assertSame(2, Opportunity::where('type', Type::ODC_TRAVEL_SUPPORT->value)->count());
        $this->assertSame(0, Opportunity::where('type', Type::TRAINING->value)->count());
    }

    public function test_required_fields_are_still_validated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('index'))
            ->post(route('opportunity.odc-travel-support.submit'), $this->reducedPayload(['title' => '', 'summary' => '']))
            ->assertRedirect(route('index'))
            ->assertSessionHasErrors(['title', 'summary'])
            ->assertSessionDoesntHaveErrors(['coverage_activity', 'implementation_location', 'thematic_areas', 'target_languages']);

        $this->assertDatabaseCount('opportunities', 0);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->post(route('opportunity.odc-travel-support.submit'), $this->reducedPayload())
            ->assertRedirect();

        $this->assertDatabaseCount('opportunities', 0);
    }

    public function test_partner_only_submit_route_still_rejects_non_partners(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        $this->actingAs($user)
            ->post(route('opportunity.submit'), $this->reducedPayload())
            ->assertStatus(403);
    }

    public function test_partner_can_submit_reduced_type_on_regular_route(): void
    {
        $partner = User::factory()->create();
        $partner->assignRole('partner');

        $this->actingAs($partner)
            ->post(route('opportunity.submit'), $this->reducedPayload())
            ->assertRedirect(route('me.opportunity.list'));

        $this->assertDatabaseHas('opportunities', [
            'type' => Type::ODC_TRAVEL_SUPPORT->value,
            'coverage_activity' => null,
        ]);
    }

    public function test_array_type_input_is_a_validation_error_not_a_server_error(): void
    {
        $partner = User::factory()->create();
        $partner->assignRole('partner');

        $this->actingAs($partner)
            ->from(route('index'))
            ->post(route('opportunity.submit'), $this->fullPayload(['type' => ['x']]))
            ->assertRedirect(route('index'))
            ->assertSessionHasErrors('type');
    }

    public function test_omitted_fields_remain_required_for_other_types(): void
    {
        $partner = User::factory()->create();
        $partner->assignRole('partner');

        $this->actingAs($partner)
            ->from(route('index'))
            ->post(route('opportunity.submit'), $this->reducedPayload(['type' => Type::TRAINING->value]))
            ->assertRedirect(route('index'))
            ->assertSessionHasErrors(['coverage_activity', 'implementation_location', 'thematic_areas', 'target_languages']);
    }

    public function test_switching_an_existing_opportunity_to_odc_clears_omitted_fields(): void
    {
        $partner = User::factory()->create();
        $partner->assignRole('partner');

        $opportunity = Opportunity::factory()->for($partner)->create([
            'type' => Type::TRAINING,
            'coverage_activity' => CoverageActivity::GLOBAL,
            'implementation_location' => 'Global',
            'thematic_areas' => [ThematicAreas::MARINE_BIODIVERSITY->value],
            'target_languages' => [Language::ENGLISH->value],
        ]);

        $this->actingAs($partner)
            ->post(route('opportunity.submit', $opportunity), $this->reducedPayload())
            ->assertRedirect(route('me.opportunity.list'));

        $opportunity->refresh();
        $this->assertSame(Type::ODC_TRAVEL_SUPPORT, $opportunity->type);
        $this->assertNull($opportunity->coverage_activity);
        $this->assertNull($opportunity->implementation_location);
        $this->assertNull($opportunity->thematic_areas);
        $this->assertNull($opportunity->target_languages);
    }

    public function test_non_partner_creator_can_view_own_pending_submission(): void
    {
        $user = User::factory()->create();
        $opportunity = Opportunity::factory()->odcTravelSupport()->for($user)->create([
            'status' => Status::PENDING_REVIEW,
        ]);

        // Status-change emails link the creator to opportunity.show.
        $this->assertTrue($user->can('view', $opportunity));
        $this->assertFalse(User::factory()->create()->can('view', $opportunity));

        $this->actingAs($user)
            ->get(route('opportunity.show', $opportunity))
            ->assertOk()
            ->assertInertia(fn (\Inertia\Testing\AssertableInertia $page) => $page
                ->where('opportunity.permissions.can_view', true)
                ->where('opportunity.coverage_activity', null));
    }
}
