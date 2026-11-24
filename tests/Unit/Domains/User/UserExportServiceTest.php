<?php

namespace Tests\Unit\Domains\User;

use App\Domains\User\Models\User;
use App\Domains\User\Services\UserExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserExportServiceTest extends TestCase
{
    use RefreshDatabase;

    private UserExportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(UserExportService::class);
    }

    #[Test]
    public function it_exports_users_to_csv_with_correct_headers()
    {
        // Arrange: Create test users
        User::factory()->count(3)->create();

        // Act: Generate CSV
        $response = $this->service->exportUsersCsv();

        // Assert: Check response
        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class, $response);
        $this->assertEquals('text/csv; charset=UTF-8', $response->headers->get('Content-Type'));

        // Get CSV content
        ob_start();
        $response->sendContent();
        $csv = ob_get_clean();

        // Check header columns (fputcsv will quote "user name" because it contains a space)
        $this->assertStringContainsString('id,', $csv);
        $this->assertStringContainsString('email,', $csv);
        $this->assertStringContainsString('provider,', $csv);
        $this->assertStringContainsString('roles,', $csv);
    }

    #[Test]
    public function it_formats_roles_as_comma_separated_string()
    {
        // Arrange: Create roles
        Role::create(['name' => 'administrator']);
        Role::create(['name' => 'partner']);

        // Create user with multiple roles
        $user = User::factory()->create(['name' => 'Test Admin']);
        $user->assignRole(['administrator', 'partner']);

        // Act
        ob_start();
        $this->service->exportUsersCsv()->sendContent();
        $csv = ob_get_clean();

        // Assert
        $this->assertStringContainsString('administrator,partner', $csv);
    }

    #[Test]
    public function it_handles_users_with_no_roles()
    {
        // Arrange
        User::factory()->create(['name' => 'Test User']);

        // Act & Assert
        ob_start();
        $this->service->exportUsersCsv()->sendContent();
        $csv = ob_get_clean();

        $lines = explode("\n", trim($csv));
        $this->assertGreaterThanOrEqual(2, count($lines)); // Header + at least 1 user
    }

    #[Test]
    public function it_formats_oauth_provider_correctly()
    {
        // Arrange
        User::factory()->create(['provider' => 'google', 'name' => 'Google User']);
        User::factory()->create(['provider' => 'linkedin', 'name' => 'LinkedIn User']);
        User::factory()->create(['provider' => null, 'name' => 'Regular User']);

        // Act
        ob_start();
        $this->service->exportUsersCsv()->sendContent();
        $csv = ob_get_clean();

        // Assert
        $this->assertStringContainsString('google', $csv);
        $this->assertStringContainsString('linkedin', $csv);
    }

    #[Test]
    public function it_formats_is_blocked_as_yes_no()
    {
        // Arrange
        User::factory()->create(['is_blocked' => true, 'name' => 'Blocked User']);
        User::factory()->create(['is_blocked' => false, 'name' => 'Active User']);

        // Act
        ob_start();
        $this->service->exportUsersCsv()->sendContent();
        $csv = ob_get_clean();

        // Assert
        $this->assertStringContainsString('Yes', $csv);
        $this->assertStringContainsString('No', $csv);
    }

    #[Test]
    public function it_handles_empty_user_list()
    {
        // Arrange: No users created

        // Act
        ob_start();
        $this->service->exportUsersCsv()->sendContent();
        $csv = ob_get_clean();

        // Assert: Should have header only
        $lines = explode("\n", trim($csv));
        $this->assertCount(1, $lines);
        $this->assertStringContainsString('id,', $csv);
        $this->assertStringContainsString('email,', $csv);
    }

    #[Test]
    public function it_includes_all_required_columns()
    {
        // Arrange
        Role::create(['name' => 'partner']);
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'provider' => 'google',
            'country' => 'USA',
            'is_blocked' => false,
        ]);
        $user->assignRole('partner');

        // Act
        ob_start();
        $this->service->exportUsersCsv()->sendContent();
        $csv = ob_get_clean();

        // Assert all required fields are present
        $this->assertStringContainsString('Test User', $csv);
        $this->assertStringContainsString('test@example.com', $csv);
        $this->assertStringContainsString('google', $csv);
        $this->assertStringContainsString('partner', $csv);
        $this->assertStringContainsString('USA', $csv);
        $this->assertStringContainsString('No', $csv); // is_blocked = false
    }

    #[Test]
    public function it_formats_dates_correctly()
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        ob_start();
        $this->service->exportUsersCsv()->sendContent();
        $csv = ob_get_clean();

        // Assert: Date should be in Y-m-d H:i:s format
        $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $csv);
    }

    #[Test]
    public function it_handles_null_values_gracefully()
    {
        // Arrange: User with null country and provider
        User::factory()->create([
            'name' => 'Minimal User',
            'email' => 'minimal@example.com',
            'country' => null,
            'provider' => null,
        ]);

        // Act
        ob_start();
        $this->service->exportUsersCsv()->sendContent();
        $csv = ob_get_clean();

        // Assert: Should not fail, empty strings should appear
        $this->assertStringContainsString('Minimal User', $csv);
        $this->assertStringContainsString('minimal@example.com', $csv);
    }

    #[Test]
    public function it_generates_unique_filename_with_timestamp()
    {
        // Act
        $response = $this->service->exportUsersCsv();

        // Assert
        $contentDisposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('attachment; filename="users_', $contentDisposition);
        $this->assertStringContainsString('.csv"', $contentDisposition);

        // Verify timestamp format (YYYY-MM-DD_HHMMSS)
        $this->assertMatchesRegularExpression('/users_\d{4}-\d{2}-\d{2}_\d{6}\.csv/', $contentDisposition);
    }
}
