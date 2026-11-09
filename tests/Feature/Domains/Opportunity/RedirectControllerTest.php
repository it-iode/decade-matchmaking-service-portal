<?php

declare(strict_types=1);

namespace Tests\Feature\Domains\Opportunity;

use App\Domains\Opportunity\Enums\Status;
use App\Domains\Opportunity\Models\Opportunity;
use App\Domains\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RedirectControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'administrator']);
        Role::create(['name' => 'partner']);
        Role::create(['name' => 'user']);

        config(['services.opportunity_click.ip_pepper' => 'test-pepper']);

        // routes/web.php uses require_once for sub-route files, which means PHP's
        // global include-once tracking prevents re-loading them when the Laravel
        // application is re-bootstrapped between tests. Explicitly re-require the
        // opportunity routes into the fresh router instance to guarantee the named
        // route is always available regardless of test execution order.
        // refreshNameLookups() must follow so that named-route resolution
        // (e.g. route('opportunity.go')) sees the new registrations.
        $router = $this->app->make('router');
        if (! $router->has('opportunity.go')) {
            require base_path('routes/opportunity.php');
            $router->getRoutes()->refreshNameLookups();
            $router->getRoutes()->refreshActionLookups();
        }
    }

    #[Test]
    public function test_redirects_active_opportunity_with_security_headers(): void
    {
        $opportunity = Opportunity::factory()->active()->create(['url' => 'https://example.com/apply']);

        $response = $this->get(route('opportunity.go', ['identifier' => $opportunity->id]));

        $response->assertRedirect('https://example.com/apply');
        $response->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $response->assertHeader('Referrer-Policy', 'no-referrer-when-downgrade');

        // The controller sets no-store, max-age=0; the session middleware appends
        // "private" so the final header contains all three directives.
        $cacheControl = $response->headers->get('Cache-Control');
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('max-age=0', $cacheControl);
    }

    #[Test]
    #[DataProvider('nonActiveStatusProvider')]
    public function test_returns_404_for_non_active_status(Status $status): void
    {
        $opportunity = Opportunity::factory()->create(['status' => $status]);

        $response = $this->get(route('opportunity.go', ['identifier' => $opportunity->id]));

        $response->assertNotFound();
    }

    public static function nonActiveStatusProvider(): array
    {
        return array_map(
            fn (Status $status) => [$status],
            array_filter(Status::cases(), fn (Status $s) => $s !== Status::ACTIVE)
        );
    }

    #[Test]
    public function test_returns_404_when_stored_url_has_non_http_scheme(): void
    {
        // UrlNormalizerCast::set leaves "javascript:" intact — it matches the
        // scheme regex pattern and is returned unchanged, so the factory
        // persists it as-is. The controller then aborts 404 post-normalization.
        $opportunity = Opportunity::factory()->active()->create(['url' => 'javascript:alert(1)']);

        $response = $this->get(route('opportunity.go', ['identifier' => $opportunity->id]));

        $response->assertNotFound();
    }

    #[Test]
    public function test_accepts_both_numeric_id_and_public_id(): void
    {
        $opportunity = Opportunity::factory()->active()->create(['url' => 'https://example.com/apply']);

        $responseById = $this->get(route('opportunity.go', ['identifier' => $opportunity->id]));
        $responseById->assertRedirect('https://example.com/apply');

        $responseByPublicId = $this->get(route('opportunity.go', ['identifier' => $opportunity->public_id]));
        $responseByPublicId->assertRedirect('https://example.com/apply');
    }

    #[Test]
    public function test_records_click_with_hashed_ip_and_truncated_ua(): void
    {
        $opportunity = Opportunity::factory()->active()->create(['url' => 'https://example.com/apply']);
        $user = User::factory()->create();

        $longUserAgent = str_repeat('A', 600);

        $response = $this->actingAs($user)
            ->withServerVariables(['REMOTE_ADDR' => '203.0.113.42'])
            ->withHeaders(['User-Agent' => $longUserAgent])
            ->get(route('opportunity.go', ['identifier' => $opportunity->id]));

        $response->assertRedirect('https://example.com/apply');

        $this->assertDatabaseHas('opportunity_clicks', [
            'opportunity_id' => $opportunity->id,
            'user_id' => $user->id,
            'ip_hash' => hash('sha256', '203.0.113.42'.'test-pepper'),
        ]);

        $click = DB::table('opportunity_clicks')
            ->where('opportunity_id', $opportunity->id)
            ->first();

        $this->assertNotNull($click);
        $this->assertLessThanOrEqual(512, strlen($click->user_agent));
    }

    #[Test]
    public function test_records_null_user_for_guest_click(): void
    {
        $opportunity = Opportunity::factory()->active()->create(['url' => 'https://example.com/apply']);

        $response = $this->get(route('opportunity.go', ['identifier' => $opportunity->id]));

        $response->assertRedirect('https://example.com/apply');

        $this->assertDatabaseHas('opportunity_clicks', [
            'opportunity_id' => $opportunity->id,
            'user_id' => null,
        ]);
    }

    #[Test]
    public function test_skips_click_record_for_bot_user_agent(): void
    {
        $opportunity = Opportunity::factory()->active()->create(['url' => 'https://example.com/apply']);

        $response = $this->withHeaders(['User-Agent' => 'Googlebot/2.1'])
            ->get(route('opportunity.go', ['identifier' => $opportunity->id]));

        $response->assertRedirect('https://example.com/apply');

        $this->assertDatabaseMissing('opportunity_clicks', [
            'opportunity_id' => $opportunity->id,
        ]);
    }
}
