<?php

declare(strict_types=1);

namespace Tests\Feature\Http;

use App\Domains\Opportunity\Enums\Status;
use App\Domains\Opportunity\Enums\Type;
use App\Domains\Opportunity\Models\Opportunity;
use App\Domains\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class IndexControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_signed_in_user_receives_open_odc_travel_support_sorted_by_closing_date(): void
    {
        $later = Opportunity::factory()->odcTravelSupport()->create(['closing_date' => now()->addDays(10)]);
        $today = Opportunity::factory()->odcTravelSupport()->create(['closing_date' => now()]);
        Opportunity::factory()->odcTravelSupport()->closed()->create(['closing_date' => now()->addDays(5)]);
        Opportunity::factory()->odcTravelSupport()->create(['closing_date' => now()->subDay()]);
        Opportunity::factory()->ofType(Type::TRAVEL_GRANTS)->create(['closing_date' => now()->addDays(3)]);

        $this->actingAs(User::factory()->create())
            ->get(route('index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Index')
                ->has('odcTravelSupport', 2)
                ->where('odcTravelSupport.0.id', $today->id)
                ->where('odcTravelSupport.1.id', $later->id)
                ->where('odcTravelSupport.0.type.value', Type::ODC_TRAVEL_SUPPORT->value)
                ->where('odcTravelSupport.0.coverage_activity', null)
                ->has('formOptions.target_audience')
            );
    }

    public function test_guest_receives_open_odc_travel_support_without_creator_pii(): void
    {
        $open = Opportunity::factory()->odcTravelSupport()->create([
            'status' => Status::ACTIVE,
            'closing_date' => now()->addDays(10),
        ]);

        $this->get(route('index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Index')
                ->has('odcTravelSupport', 1)
                ->where('odcTravelSupport.0.id', $open->id)
                ->where('odcTravelSupport.0.type.value', Type::ODC_TRAVEL_SUPPORT->value)
                // Guests must not receive the creator relationship (email and other PII).
                ->missing('odcTravelSupport.0.user')
                ->missing('odcTravelSupport.0.permissions')
                ->has('formOptions.target_audience')
            );
    }

    public function test_signed_in_user_receives_creator_relationship_on_odc_travel_support(): void
    {
        Opportunity::factory()->odcTravelSupport()->create(['closing_date' => now()->addDays(10)]);

        $this->actingAs(User::factory()->create())
            ->get(route('index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Index')
                ->has('odcTravelSupport', 1)
                ->has('odcTravelSupport.0.user')
            );
    }
}
