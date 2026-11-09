<?php

declare(strict_types=1);

namespace Tests\Feature\Domains\Request;

use App\Domains\Request\Enums\DecadeChallenge;
use App\Domains\Request\Events\RequestSubmitted;
use App\Domains\Request\Models\Request as OCDRequest;
use App\Domains\Request\Resources\DetailResource;
use App\Domains\User\Models\User;
use Database\Seeders\AddRequestStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Feature tests for the ranked Decade Challenges question (issue #205).
 *
 * Covers submit-mode validation (primary required, distinct ranks,
 * contiguous ranks), draft-mode partial persistence, and the round-trip
 * from form payload into `request_details.decade_challenges` and back
 * out through the DetailResource read shape.
 */
class RequestDecadeChallengesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AddRequestStatus::class);
        Role::findOrCreate('user');

        Event::fake([RequestSubmitted::class]);
    }

    private function actingUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        return $user;
    }

    /**
     * A complete, valid submit payload; override fields per test.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'mode' => 'submit',
            'is_related_decade_action' => 'no',
            'request_link_type' => 'no',
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane.doe@example.org',
            'capacity_development_title' => 'Ocean literacy training programme',
            'related_activity' => 'training',
            'delivery_format' => 'online',
            'target_languages' => ['english'],
            'decade_challenges' => [
                'primary' => DecadeChallenge::MARINE_POLLUTION->value,
                'secondary' => null,
                'tertiary' => null,
            ],
            'support_types' => ['funding-to-organize-a-workshop-ou-formation'],
            'gap_description' => 'We lack regional training capacity.',
            'has_partner' => 'no',
            'needs_financial_support' => 'no',
            'support_months' => 6,
            'completion_date' => '2027-06-30',
            'risks' => 'Limited attendance during field season.',
            'personnel_expertise' => 'Marine biologists and educators.',
            'direct_beneficiaries' => 'Early-career ocean professionals.',
            'direct_beneficiaries_number' => 25,
            'expected_outcomes' => 'Improved regional monitoring capacity.',
            'success_metrics' => 'Number of trained participants.',
            'long_term_impact' => 'Sustained regional observation network.',
        ], $overrides);
    }

    #[Test]
    public function submitting_without_a_primary_challenge_is_rejected(): void
    {
        $response = $this->actingAs($this->actingUser())->post(
            route('request.submit'),
            $this->validPayload([
                'decade_challenges' => [
                    'primary' => null,
                    'secondary' => DecadeChallenge::ECOSYSTEMS_BIODIVERSITY->value,
                    'tertiary' => null,
                ],
            ])
        );

        $response->assertSessionHasErrors(['decade_challenges.primary', 'decade_challenges']);
        $this->assertDatabaseCount('requests', 0);
    }

    #[Test]
    public function submitting_with_only_a_primary_challenge_is_accepted(): void
    {
        $response = $this->actingAs($this->actingUser())->post(
            route('request.submit'),
            $this->validPayload()
        );

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('request.me.list'));

        $this->assertSame(
            [
                'primary' => DecadeChallenge::MARINE_POLLUTION->value,
                'secondary' => null,
                'tertiary' => null,
            ],
            OCDRequest::first()->detail->decade_challenges
        );
    }

    #[Test]
    public function submitting_duplicate_ranks_is_rejected(): void
    {
        $response = $this->actingAs($this->actingUser())->post(
            route('request.submit'),
            $this->validPayload([
                'decade_challenges' => [
                    'primary' => DecadeChallenge::MARINE_POLLUTION->value,
                    'secondary' => DecadeChallenge::MARINE_POLLUTION->value,
                    'tertiary' => null,
                ],
            ])
        );

        $response->assertSessionHasErrors(['decade_challenges']);
        $this->assertDatabaseCount('requests', 0);
    }

    #[Test]
    public function submitting_a_tertiary_without_a_secondary_is_rejected(): void
    {
        $response = $this->actingAs($this->actingUser())->post(
            route('request.submit'),
            $this->validPayload([
                'decade_challenges' => [
                    'primary' => DecadeChallenge::MARINE_POLLUTION->value,
                    'secondary' => null,
                    'tertiary' => DecadeChallenge::CLIMATE_CHANGE_SOLUTIONS->value,
                ],
            ])
        );

        $response->assertSessionHasErrors(['decade_challenges.tertiary', 'decade_challenges']);
        $this->assertDatabaseCount('requests', 0);
    }

    #[Test]
    public function submitting_an_unknown_challenge_value_is_rejected(): void
    {
        $response = $this->actingAs($this->actingUser())->post(
            route('request.submit'),
            $this->validPayload([
                'decade_challenges' => [
                    'primary' => 'not-a-challenge',
                    'secondary' => null,
                    'tertiary' => null,
                ],
            ])
        );

        $response->assertSessionHasErrors(['decade_challenges.primary', 'decade_challenges']);
        $this->assertDatabaseCount('requests', 0);
    }

    #[Test]
    public function three_ranked_challenges_round_trip_through_storage_and_the_resource(): void
    {
        $user = $this->actingUser();

        $this->actingAs($user)->post(
            route('request.submit'),
            $this->validPayload([
                'decade_challenges' => [
                    'primary' => DecadeChallenge::MARINE_POLLUTION->value,
                    'secondary' => DecadeChallenge::ECOSYSTEMS_BIODIVERSITY->value,
                    'tertiary' => DecadeChallenge::CLIMATE_CHANGE_SOLUTIONS->value,
                ],
            ])
        )->assertSessionHasNoErrors();

        $request = OCDRequest::with('detail')->first();

        $this->assertSame(
            [
                'primary' => DecadeChallenge::MARINE_POLLUTION->value,
                'secondary' => DecadeChallenge::ECOSYSTEMS_BIODIVERSITY->value,
                'tertiary' => DecadeChallenge::CLIMATE_CHANGE_SOLUTIONS->value,
            ],
            $request->detail->decade_challenges
        );

        $httpRequest = \Illuminate\Http\Request::create('/', 'GET');
        $httpRequest->setUserResolver(fn () => $user);

        $resource = (new DetailResource($request->detail))->toArray($httpRequest);

        $this->assertSame(
            [
                'primary' => [
                    'value' => DecadeChallenge::MARINE_POLLUTION->value,
                    'label' => DecadeChallenge::MARINE_POLLUTION->label(),
                ],
                'secondary' => [
                    'value' => DecadeChallenge::ECOSYSTEMS_BIODIVERSITY->value,
                    'label' => DecadeChallenge::ECOSYSTEMS_BIODIVERSITY->label(),
                ],
                'tertiary' => [
                    'value' => DecadeChallenge::CLIMATE_CHANGE_SOLUTIONS->value,
                    'label' => DecadeChallenge::CLIMATE_CHANGE_SOLUTIONS->label(),
                ],
            ],
            $resource['decade_challenges']
        );
    }

    #[Test]
    public function a_draft_persists_a_partial_challenge_selection(): void
    {
        $response = $this->actingAs($this->actingUser())->post(route('request.submit'), [
            'mode' => 'draft',
            'capacity_development_title' => 'Draft with partial ranking',
            'decade_challenges' => [
                'primary' => null,
                'secondary' => DecadeChallenge::NOURISH_POPULATION->value,
                'tertiary' => null,
            ],
        ]);

        $response->assertSessionHasNoErrors();

        $request = OCDRequest::with('detail')->first();

        $this->assertSame('draft', $request->status->status_code);
        $this->assertSame(
            [
                'primary' => null,
                'secondary' => DecadeChallenge::NOURISH_POPULATION->value,
                'tertiary' => null,
            ],
            $request->detail->decade_challenges
        );
    }

    #[Test]
    public function the_form_page_exposes_the_ten_decade_challenge_options(): void
    {
        $this->actingAs($this->actingUser())
            ->get(route('request.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('request/Create')
                ->has('formOptions.decade_challenges', 10)
                ->missing('formOptions.subthemes')
            );
    }
}
