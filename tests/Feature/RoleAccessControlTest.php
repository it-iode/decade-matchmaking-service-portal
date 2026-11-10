<?php

namespace Tests\Feature;

use App\Domains\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleAccessControlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles that exist in the system
        Role::create(['name' => 'administrator']);
        Role::create(['name' => 'partner']);
        Role::create(['name' => 'user']);
    }

    public function test_user_without_role_sees_access_denied_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get('/opportunity/create'); // Partner-only route

        $response->assertStatus(403);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Auth/AccessDenied')
            ->has('requiredRoles')
            ->where('contactEmail', 'cdf@unesco.org')
            ->where('attemptedRoute', 'opportunity.create')
        );
    }

    public function test_user_with_correct_role_can_access_protected_route(): void
    {
        $user = User::factory()->create();
        $user->assignRole('partner');

        $response = $this->actingAs($user)
            ->get('/opportunity/create');

        $response->assertStatus(200);
    }

    public function test_admin_can_access_admin_routes(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('administrator');

        $response = $this->actingAs($admin)
            ->get('/admin/dashboard');

        $response->assertStatus(200);
    }

    public function test_non_admin_user_cannot_access_admin_routes(): void
    {
        $user = User::factory()->create();
        $user->assignRole('user');

        $response = $this->actingAs($user)
            ->get('/admin/dashboard');

        $response->assertStatus(403);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Auth/AccessDenied')
            ->where('requiredRoles', ['administrator'])
            ->where('contactEmail', 'cdf@unesco.org')
        );
    }

    public function test_api_request_returns_json_for_unauthorized_access(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withHeaders(['Accept' => 'application/json'])
            ->get('/opportunity/create');

        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Access denied. Please contact administrator for the required role(s).',
            'required_roles' => ['partner'],
            'contact_email' => 'cdf@unesco.org',
        ]);
    }

    public function test_access_denied_route_displays_correct_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get('/access-denied?roles[]=administrator&route=admin.dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Auth/AccessDenied')
            ->where('requiredRoles', ['administrator'])
            ->where('contactEmail', 'cdf@unesco.org')
            ->where('attemptedRoute', 'admin.dashboard')
        );
    }

    public function test_guest_user_redirected_to_login(): void
    {
        $response = $this->get('/opportunity/create');

        $response->assertRedirect('/sign-in');
    }

    public function test_user_with_multiple_roles_can_access_any_permitted_route(): void
    {
        $user = User::factory()->create();
        $user->assignRole(['user', 'partner']);

        // Test user-only route
        $response = $this->actingAs($user)
            ->get('/subscriptions');
        $response->assertStatus(200);

        // Test partner-only route
        $response = $this->actingAs($user)
            ->get('/opportunity/create');
        $response->assertStatus(200);
    }

    public function test_role_pipe_separated_middleware_works(): void
    {
        $user = User::factory()->create();
        $partner = User::factory()->create();
        $admin = User::factory()->create();

        $user->assignRole('user');
        $partner->assignRole('partner');
        $admin->assignRole('administrator');

        // Test a route that accepts user|partner|administrator (like request viewing)
        $response = $this->actingAs($user)
            ->get('/me/request/list');
        $response->assertStatus(200);

        $response = $this->actingAs($partner)
            ->get('/me/request/list');
        $response->assertStatus(200);

        $response = $this->actingAs($admin)
            ->get('/me/request/list');
        $response->assertStatus(200);
    }
}
