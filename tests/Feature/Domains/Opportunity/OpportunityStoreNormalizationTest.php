<?php

declare(strict_types=1);

namespace Tests\Feature\Domains\Opportunity;

use App\Domains\Opportunity\Enums\CoverageActivity;
use App\Domains\Opportunity\Enums\TargetAudience;
use App\Domains\Opportunity\Enums\ThematicAreas;
use App\Domains\Opportunity\Enums\Type;
use App\Domains\Opportunity\Models\Opportunity;
use App\Domains\User\Models\User;
use App\Shared\Enums\Language;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OpportunityStoreNormalizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'administrator']);
        Role::create(['name' => 'partner']);
        Role::create(['name' => 'user']);

        // routes/web.php uses require_once for sub-route files, which means PHP's
        // global include-once tracking prevents re-loading them when the Laravel
        // application is re-bootstrapped between tests. Explicitly re-require the
        // opportunity routes into the fresh router instance to guarantee the named
        // route is always available regardless of test execution order.
        // After adding routes, refreshNameLookups() must be called so that named
        // route resolution (e.g. route('opportunity.submit')) finds the new entries.
        $router = $this->app->make('router');
        if (! $router->has('opportunity.submit')) {
            require base_path('routes/opportunity.php');
            $router->getRoutes()->refreshNameLookups();
            $router->getRoutes()->refreshActionLookups();
        }
    }

    #[Test]
    public function test_opportunity_form_submission_normalizes_schemeless_url(): void
    {
        $user = User::factory()->create();
        $user->assignRole('partner');

        $raw = Opportunity::factory()->raw([
            'url' => 'www.google.com',
        ]);

        $payload = [
            'title' => $raw['title'],
            'type' => $raw['type'] instanceof Type ? $raw['type']->value : $raw['type'],
            'closing_date' => now()->addWeeks(2)->toDateString(),
            'coverage_activity' => $raw['coverage_activity'] instanceof CoverageActivity
                ? $raw['coverage_activity']->value
                : $raw['coverage_activity'],
            'implementation_location' => 'Global',
            'summary' => $raw['summary'],
            'url' => $raw['url'],
            'co_organizers' => ['UNESCO'],
            'thematic_areas' => [ThematicAreas::MARINE_BIODIVERSITY->value],
            'target_audience' => [TargetAudience::RESEARCHERS->value],
            'target_languages' => [Language::ENGLISH->value],
            'key_words' => ['ocean', 'research'],
        ];

        $response = $this->actingAs($user)->post(route('opportunity.submit'), $payload);

        $response->assertStatus(302);

        $this->assertDatabaseHas('opportunities', ['url' => 'https://www.google.com']);
    }
}
