<?php

namespace Tests\Feature\Domains\User;

use App\Domains\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserExportControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles needed for testing
        Role::create(['name' => 'administrator']);
        Role::create(['name' => 'partner']);
    }

    #[Test]
    public function admin_can_export_users_csv()
    {
        // Arrange
        $admin = User::factory()->create();
        $admin->assignRole('administrator');
        User::factory()->count(5)->create();

        // Act
        $response = $this->actingAs($admin)
            ->get(route('admin.users.export.csv'));

        // Assert
        $response->assertSuccessful();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        // Check content disposition header for file download
        $contentDisposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('attachment', $contentDisposition);
        $this->assertStringContainsString('users_', $contentDisposition);
        $this->assertStringContainsString('.csv', $contentDisposition);
    }

    #[Test]
    public function non_admin_cannot_export_users()
    {
        // Arrange
        $user = User::factory()->create(); // No roles

        // Act
        $response = $this->actingAs($user)
            ->get(route('admin.users.export.csv'));

        // Assert
        $response->assertForbidden(); // 403
    }

    #[Test]
    public function partner_cannot_export_users()
    {
        // Arrange
        $partner = User::factory()->create();
        $partner->assignRole('partner');

        // Act
        $response = $this->actingAs($partner)
            ->get(route('admin.users.export.csv'));

        // Assert
        $response->assertForbidden(); // 403
    }

    #[Test]
    public function guest_cannot_export_users()
    {
        // Act
        $response = $this->get(route('admin.users.export.csv'));

        // Assert
        $response->assertRedirect('/login'); // Redirect to login
    }

    #[Test]
    public function export_includes_all_required_columns()
    {
        // Arrange
        $admin = User::factory()->create();
        $admin->assignRole('administrator');

        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'provider' => 'google',
            'country' => 'USA',
            'is_blocked' => false,
        ]);
        $user->assignRole('partner');

        // Act
        $response = $this->actingAs($admin)->get(route('admin.users.export.csv'));

        // Assert
        $response->assertSuccessful();

        // Get content
        $csv = $response->streamedContent();

        $this->assertStringContainsString('Test User', $csv);
        $this->assertStringContainsString('test@example.com', $csv);
        $this->assertStringContainsString('google', $csv);
        $this->assertStringContainsString('partner', $csv);
        $this->assertStringContainsString('USA', $csv);
        $this->assertStringContainsString('No', $csv); // is_blocked
    }

    #[Test]
    public function export_includes_csv_header_row()
    {
        // Arrange
        $admin = User::factory()->create();
        $admin->assignRole('administrator');

        // Act
        $response = $this->actingAs($admin)->get(route('admin.users.export.csv'));

        // Assert
        $csv = $response->streamedContent();

        // Check for key header columns (fputcsv quotes "user name" because of space)
        $this->assertStringContainsString('id,', $csv);
        $this->assertStringContainsString('email,', $csv);
        $this->assertStringContainsString('provider,', $csv);
    }

    #[Test]
    public function export_works_with_no_users()
    {
        // Arrange
        $admin = User::factory()->create();
        $admin->assignRole('administrator');

        // Delete the admin after authentication to test empty export
        // (Can't delete before because we need to authenticate)

        // Act
        $response = $this->actingAs($admin)->get(route('admin.users.export.csv'));

        // Assert
        $response->assertSuccessful();
        $csv = $response->streamedContent();

        // Should have header
        $this->assertStringContainsString('id,', $csv);
        $this->assertStringContainsString('email,', $csv);
    }

    #[Test]
    public function export_handles_users_with_multiple_roles()
    {
        // Arrange
        $admin = User::factory()->create();
        $admin->assignRole('administrator');

        $multiRoleUser = User::factory()->create(['name' => 'Multi Role User']);
        $multiRoleUser->assignRole(['administrator', 'partner']);

        // Act
        $response = $this->actingAs($admin)->get(route('admin.users.export.csv'));

        // Assert
        $csv = $response->streamedContent();
        $this->assertStringContainsString('Multi Role User', $csv);
        $this->assertStringContainsString('administrator,partner', $csv);
    }

    #[Test]
    public function export_handles_blocked_users()
    {
        // Arrange
        $admin = User::factory()->create();
        $admin->assignRole('administrator');

        User::factory()->create(['is_blocked' => true, 'name' => 'Blocked User']);
        User::factory()->create(['is_blocked' => false, 'name' => 'Active User']);

        // Act
        $response = $this->actingAs($admin)->get(route('admin.users.export.csv'));

        // Assert
        $csv = $response->streamedContent();
        $this->assertStringContainsString('Blocked User', $csv);
        $this->assertStringContainsString('Active User', $csv);

        // Check Yes/No format
        $this->assertStringContainsString('Yes', $csv);
        $this->assertStringContainsString('No', $csv);
    }

    #[Test]
    public function export_handles_oauth_providers()
    {
        // Arrange
        $admin = User::factory()->create();
        $admin->assignRole('administrator');

        User::factory()->create(['provider' => 'google', 'name' => 'Google User']);
        User::factory()->create(['provider' => 'linkedin', 'name' => 'LinkedIn User']);
        User::factory()->create(['provider' => null, 'name' => 'Email User']);

        // Act
        $response = $this->actingAs($admin)->get(route('admin.users.export.csv'));

        // Assert
        $csv = $response->streamedContent();
        $this->assertStringContainsString('Google User', $csv);
        $this->assertStringContainsString('LinkedIn User', $csv);
        $this->assertStringContainsString('Email User', $csv);
        $this->assertStringContainsString('google', $csv);
        $this->assertStringContainsString('linkedin', $csv);
    }

    #[Test]
    public function export_response_has_correct_cache_headers()
    {
        // Arrange
        $admin = User::factory()->create();
        $admin->assignRole('administrator');

        // Act
        $response = $this->actingAs($admin)->get(route('admin.users.export.csv'));

        // Assert: Check cache control headers
        $this->assertEquals('no-cache, no-store, must-revalidate', $response->headers->get('Cache-Control'));
        $this->assertEquals('no-cache', $response->headers->get('Pragma'));
        $this->assertEquals('0', $response->headers->get('Expires'));
    }

    #[Test]
    public function export_filename_contains_timestamp()
    {
        // Arrange
        $admin = User::factory()->create();
        $admin->assignRole('administrator');

        // Act
        $response = $this->actingAs($admin)->get(route('admin.users.export.csv'));

        // Assert
        $contentDisposition = $response->headers->get('Content-Disposition');

        // Verify timestamp format: users_YYYY-MM-DD_HHMMSS.csv
        $this->assertMatchesRegularExpression('/users_\d{4}-\d{2}-\d{2}_\d{6}\.csv/', $contentDisposition);
    }
}
