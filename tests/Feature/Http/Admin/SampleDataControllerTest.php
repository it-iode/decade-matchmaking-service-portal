<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Admin;

use App\Domains\Opportunity\Enums\Type as OpportunityType;
use App\Domains\Request\Models\Request as OCDRequest;
use App\Domains\Request\Models\Status;
use App\Domains\User\Models\User;
use App\Http\Controllers\Admin\SampleDataController;
use Database\Seeders\AddRequestStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SampleDataControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('administrator');
        Role::findOrCreate('partner');
        Role::findOrCreate('user');
        $this->seed(AddRequestStatus::class);
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('administrator');

        return $admin;
    }

    #[Test]
    public function guests_are_redirected_to_login(): void
    {
        $this->get(route('admin.sample-data.index'))->assertRedirect();
        $this->post(route('admin.sample-data.run'), ['confirmation' => 'RESET'])->assertRedirect();
    }

    #[Test]
    public function non_admins_are_forbidden(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        $this->actingAs($user)->get(route('admin.sample-data.index'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.sample-data.run'), ['confirmation' => 'RESET'])->assertForbidden();
    }

    #[Test]
    public function admin_sees_the_page_with_a_plan_and_no_summary(): void
    {
        User::factory()->count(3)->create();

        $this->actingAs($this->admin())
            ->get(route('admin.sample-data.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/SampleData')
                ->where('environment', 'testing')
                ->where('summary', null)
                ->has('plan.keep.users')
                ->has('plan.delete.users')
                ->where('plan.protected_emails', ['n.kim1@unesco.org', 'labidi.saddem@gmail.com'])
            );
    }

    #[Test]
    public function run_requires_the_confirmation_phrase(): void
    {
        $admin = $this->admin();
        User::factory()->count(3)->create();

        $this->actingAs($admin)
            ->from(route('admin.sample-data.index'))
            ->post(route('admin.sample-data.run'), ['confirmation' => 'reset'])
            ->assertRedirect(route('admin.sample-data.index'))
            ->assertSessionHasErrors('confirmation');

        $this->assertDatabaseCount('users', 4);
    }

    #[Test]
    public function run_purges_generates_and_flashes_a_summary(): void
    {
        $admin = $this->admin();

        // A protected account with a request, and a throwaway user with a request.
        $protected = User::factory()->create(['email' => 'labidi.saddem@gmail.com']);
        $protected->assignRole('administrator');
        $protectedRequest = OCDRequest::create(['user_id' => $protected->id, 'status_id' => Status::getDraftStatus()->id]);
        $throwaway = User::factory()->create();
        OCDRequest::create(['user_id' => $throwaway->id, 'status_id' => Status::getDraftStatus()->id]);

        $response = $this->actingAs($admin)
            ->post(route('admin.sample-data.run'), ['confirmation' => 'RESET']);

        $response->assertRedirect(route('admin.sample-data.index'));
        $response->assertSessionHas('success');
        $response->assertSessionHas(SampleDataController::SUMMARY_SESSION_KEY);

        $summary = session(SampleDataController::SUMMARY_SESSION_KEY);
        $this->assertSame('testing', $summary['environment']);
        $this->assertSame(1, $summary['protected']['users']);
        $this->assertSame(1, $summary['protected']['requests']);
        $this->assertSame(2, $summary['deleted']['users']); // admin (not protected) + throwaway
        $this->assertSame(1, $summary['deleted']['requests']);
        $this->assertSame(2, $summary['created']['admins']);
        $this->assertSame(8, $summary['created']['partners']);
        $this->assertSame(30, $summary['created']['users']);
        $expectedOpportunities = count(OpportunityType::cases()) * 4; // 4 per type
        $this->assertSame($expectedOpportunities, $summary['created']['opportunities']);
        $this->assertSame(32, $summary['created']['requests']);
        $this->assertGreaterThan(0, $summary['created']['offers']);

        // Protected rows survived (including the role, stored under the morph alias), throwaway rows are gone.
        $this->assertDatabaseHas('users', ['id' => $protected->id, 'email' => 'labidi.saddem@gmail.com']);
        $this->assertTrue($protected->fresh()->hasRole('administrator'));
        $this->assertDatabaseHas('requests', ['id' => $protectedRequest->id]);
        $this->assertDatabaseMissing('users', ['id' => $throwaway->id]);
        $this->assertDatabaseMissing('users', ['id' => $admin->id]);

        $this->assertDatabaseCount('users', 1 + 2 + 8 + 30);
        $this->assertDatabaseCount('opportunities', $expectedOpportunities);
        $this->assertDatabaseCount('requests', 1 + 32);
        $this->assertDatabaseHas('users', ['email' => 'partner1@sample.oceandecade.test']);
        $this->assertTrue(User::where('email', 'admin1@sample.oceandecade.test')->first()->hasRole('administrator'));
    }

    #[Test]
    public function the_summary_is_shown_once_after_a_run(): void
    {
        $this->actingAs($this->admin())
            ->withSession([SampleDataController::SUMMARY_SESSION_KEY => ['environment' => 'testing', 'created' => []]])
            ->get(route('admin.sample-data.index'))
            ->assertInertia(fn (Assert $page) => $page->where('summary.environment', 'testing'));
    }
}
