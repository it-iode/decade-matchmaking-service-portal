<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Opportunity;

use App\Domains\Opportunity\Enums\Type;
use App\Domains\Opportunity\Models\Opportunity;
use App\Domains\Opportunity\Services\OpportunityExportService;
use App\Domains\Opportunity\Services\OpportunityRepository;
use Illuminate\Support\LazyCollection;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OpportunityExportServiceTest extends TestCase
{
    private OpportunityRepository&MockObject $repository;

    private OpportunityExportService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(OpportunityRepository::class);
        $this->service = new OpportunityExportService($this->repository);
    }

    #[Test]
    public function export_csv_response_has_correct_content_type(): void
    {
        $this->repository
            ->method('getOpportunitiesForExport')
            ->willReturn(LazyCollection::empty());

        $response = $this->service->exportOpportunitiesCsv();

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertSame('text/csv; charset=UTF-8', $response->headers->get('Content-Type'));
    }

    #[Test]
    public function export_csv_contains_summary_header(): void
    {
        $this->repository
            ->method('getOpportunitiesForExport')
            ->willReturn(LazyCollection::empty());

        $response = $this->service->exportOpportunitiesCsv();

        ob_start();
        $response->sendContent();
        $csv = ob_get_clean();

        $lines = array_values(array_filter(explode(PHP_EOL, trim((string) $csv))));
        $headers = str_getcsv($lines[0]);

        $this->assertContains('Summary', $headers, 'CSV header row must include a "Summary" column.');
    }

    #[Test]
    public function export_csv_summary_value_appears_at_same_index_as_summary_header(): void
    {
        $knownSummary = 'This is a unique and identifiable summary for regression testing.';

        $opportunity = $this->buildMinimalOpportunity($knownSummary);

        $this->repository
            ->method('getOpportunitiesForExport')
            ->willReturn(LazyCollection::make([$opportunity]));

        $response = $this->service->exportOpportunitiesCsv();

        ob_start();
        $response->sendContent();
        $csv = ob_get_clean();

        $lines = array_values(array_filter(explode(PHP_EOL, trim((string) $csv))));
        $headers = str_getcsv($lines[0]);
        $dataRow = str_getcsv($lines[1]);

        $summaryIndex = array_search('Summary', $headers, true);

        $this->assertNotFalse($summaryIndex, 'Summary column must exist in headers.');
        $this->assertArrayHasKey($summaryIndex, $dataRow, 'Data row must have as many columns as headers.');
        $this->assertSame($knownSummary, $dataRow[$summaryIndex], 'Summary value must appear at the Summary column index.');
    }

    #[Test]
    public function export_csv_header_count_matches_data_row_column_count(): void
    {
        $opportunity = $this->buildMinimalOpportunity('Any summary text.');

        $this->repository
            ->method('getOpportunitiesForExport')
            ->willReturn(LazyCollection::make([$opportunity]));

        $response = $this->service->exportOpportunitiesCsv();

        ob_start();
        $response->sendContent();
        $csv = ob_get_clean();

        $lines = array_values(array_filter(explode(PHP_EOL, trim((string) $csv))));
        $headers = str_getcsv($lines[0]);
        $dataRow = str_getcsv($lines[1]);

        $this->assertCount(
            count($headers),
            $dataRow,
            'Header column count must equal data row column count — array alignment guard.'
        );
    }

    #[Test]
    public function export_csv_header_row_has_exactly_twenty_columns(): void
    {
        $this->repository
            ->method('getOpportunitiesForExport')
            ->willReturn(LazyCollection::empty());

        $response = $this->service->exportOpportunitiesCsv();

        ob_start();
        $response->sendContent();
        $csv = ob_get_clean();

        $lines = array_values(array_filter(explode(PHP_EOL, trim((string) $csv))));
        $headers = str_getcsv($lines[0]);

        $this->assertCount(20, $headers, 'Header row must have exactly 20 columns after adding Summary.');
    }

    #[Test]
    public function export_csv_summary_header_is_immediately_after_title(): void
    {
        $this->repository
            ->method('getOpportunitiesForExport')
            ->willReturn(LazyCollection::empty());

        $response = $this->service->exportOpportunitiesCsv();

        ob_start();
        $response->sendContent();
        $csv = ob_get_clean();

        $lines = array_values(array_filter(explode(PHP_EOL, trim((string) $csv))));
        $headers = str_getcsv($lines[0]);

        $titleIndex = array_search('Title', $headers, true);
        $summaryIndex = array_search('Summary', $headers, true);

        $this->assertNotFalse($titleIndex, '"Title" header must exist.');
        $this->assertNotFalse($summaryIndex, '"Summary" header must exist.');
        $this->assertSame(
            $titleIndex + 1,
            $summaryIndex,
            '"Summary" must be the column immediately after "Title".'
        );
    }

    #[Test]
    public function export_csv_null_summary_outputs_empty_string(): void
    {
        $opportunity = $this->buildMinimalOpportunity(null);

        $this->repository
            ->method('getOpportunitiesForExport')
            ->willReturn(LazyCollection::make([$opportunity]));

        $response = $this->service->exportOpportunitiesCsv();

        ob_start();
        $response->sendContent();
        $csv = ob_get_clean();

        $lines = array_values(array_filter(explode(PHP_EOL, trim((string) $csv))));
        $headers = str_getcsv($lines[0]);
        $dataRow = str_getcsv($lines[1]);

        $summaryIndex = array_search('Summary', $headers, true);

        $this->assertSame('', $dataRow[$summaryIndex], 'Null summary must produce an empty string in the CSV.');
    }

    #[Test]
    public function reduced_form_type_exports_not_applicable_for_omitted_fields(): void
    {
        $row = $this->exportSingleRow($this->buildMinimalOpportunity('Summary', Type::ODC_TRAVEL_SUPPORT));

        foreach (['Thematic Areas', 'Coverage activity', 'Implementation location', 'Target languages'] as $column) {
            $this->assertSame('N/A', $row[$column], "{$column} must read N/A for ODC travel support rows.");
        }
    }

    #[Test]
    public function other_types_keep_empty_strings_for_empty_fields(): void
    {
        $row = $this->exportSingleRow($this->buildMinimalOpportunity('Summary', Type::TRAINING));

        foreach (['Thematic Areas', 'Coverage activity', 'Implementation location', 'Target languages'] as $column) {
            $this->assertSame('', $row[$column], "{$column} must stay empty for legacy rows.");
        }
    }

    /**
     * Export one opportunity and return its data row keyed by header name.
     *
     * @return array<string, string>
     */
    private function exportSingleRow(Opportunity $opportunity): array
    {
        $this->repository
            ->method('getOpportunitiesForExport')
            ->willReturn(LazyCollection::make([$opportunity]));

        $response = $this->service->exportOpportunitiesCsv();

        ob_start();
        $response->sendContent();
        $csv = ob_get_clean();

        $lines = array_values(array_filter(explode(PHP_EOL, trim((string) $csv))));

        return array_combine(str_getcsv($lines[0]), str_getcsv($lines[1]));
    }

    /**
     * Build a minimal Opportunity-like object with only the properties the
     * export service reads. We use a partial Mockery mock so the `user`
     * relation (which requires a live DB connection) returns null safely,
     * while real attribute reads work normally via setRawAttributes().
     */
    private function buildMinimalOpportunity(?string $summary, ?Type $type = null): Opportunity
    {
        /** @var Opportunity&\Mockery\MockInterface $opportunity */
        $opportunity = \Mockery::mock(Opportunity::class)->makePartial();
        $opportunity->setRawAttributes([
            'title' => 'Test Opportunity',
            'summary' => $summary,
            'type' => $type?->value,
        ]);
        $opportunity->shouldReceive('getAttribute')
            ->with('user')
            ->andReturnNull()
            ->byDefault();
        $opportunity->shouldReceive('getAttribute')
            ->with('type')
            ->andReturn($type)
            ->byDefault();

        return $opportunity;
    }
}
