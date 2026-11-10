<?php

namespace Tests\Feature\Domains\Opportunity;

use App\Domains\Opportunity\Models\Opportunity;
use App\Domains\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OpportunityExportControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'administrator']);
        Role::create(['name' => 'partner']);
    }

    #[Test]
    public function admin_can_export_opportunities_csv(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('administrator');

        $response = $this->actingAs($admin)
            ->get(route('admin.opportunity.export.csv'));

        $response->assertSuccessful();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $contentDisposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('attachment', $contentDisposition);
        $this->assertStringContainsString('opportunities_', $contentDisposition);
        $this->assertStringContainsString('.csv', $contentDisposition);
    }

    #[Test]
    public function non_admin_cannot_export_opportunities(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('admin.opportunity.export.csv'));

        $response->assertForbidden();
    }

    #[Test]
    public function guest_cannot_export_opportunities(): void
    {
        $response = $this->get(route('admin.opportunity.export.csv'));

        $response->assertRedirect('/login');
    }

    #[Test]
    public function export_csv_contains_summary_header(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('administrator');

        $response = $this->actingAs($admin)
            ->get(route('admin.opportunity.export.csv'));

        $response->assertSuccessful();

        $csv = $response->streamedContent();
        $lines = explode(PHP_EOL, trim($csv));
        $headers = str_getcsv($lines[0]);

        $this->assertContains('Summary', $headers, 'CSV header row must include a "Summary" column.');
    }

    #[Test]
    public function export_csv_summary_column_contains_opportunity_summary(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('administrator');

        $knownSummary = 'This is a unique and identifiable summary value for testing purposes.';

        Opportunity::factory()->create([
            'summary' => $knownSummary,
            'user_id' => User::factory()->create()->id,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.opportunity.export.csv'));

        $response->assertSuccessful();

        $csv = $response->streamedContent();
        $lines = array_filter(explode(PHP_EOL, trim($csv)));
        $linesValues = array_values($lines);

        $headers = str_getcsv($linesValues[0]);
        $summaryIndex = array_search('Summary', $headers, true);

        $this->assertNotFalse($summaryIndex, 'Summary column must exist in headers.');

        // Parse first data row (index 1 after header)
        $dataRow = str_getcsv($linesValues[1]);

        $this->assertArrayHasKey($summaryIndex, $dataRow, 'Data row must have the same number of columns as headers.');
        $this->assertSame($knownSummary, $dataRow[$summaryIndex], 'Summary value must appear at the Summary column index.');
    }

    #[Test]
    public function export_csv_header_count_matches_data_row_column_count(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('administrator');

        Opportunity::factory()->create([
            'user_id' => User::factory()->create()->id,
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.opportunity.export.csv'));

        $response->assertSuccessful();

        $csv = $response->streamedContent();
        $lines = array_values(array_filter(explode(PHP_EOL, trim($csv))));

        $headers = str_getcsv($lines[0]);
        $dataRow = str_getcsv($lines[1]);

        $this->assertCount(
            count($headers),
            $dataRow,
            'Header column count must equal data row column count — array alignment guard.'
        );
    }
}
