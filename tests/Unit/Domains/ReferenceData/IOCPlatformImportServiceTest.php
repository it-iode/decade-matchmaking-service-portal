<?php

namespace Tests\Unit\Domains\ReferenceData;

use App\Domains\ReferenceData\Models\IOCPlatform;
use App\Domains\ReferenceData\Services\Import\IOCPlatformImportService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class IOCPlatformImportServiceTest extends TestCase
{
    use RefreshDatabase;

    private IOCPlatformImportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new IOCPlatformImportService;
    }

    #[Test]
    public function it_can_import_platforms_from_valid_csv()
    {
        // Create a temporary CSV file
        $csvContent = "name,description,link,contact\n";
        $csvContent .= "Platform 1,Description 1,https://example1.com,contact1@example.com\n";
        $csvContent .= 'Platform 2,Description 2,https://example2.com,contact2@example.com';

        $tempFile = tempnam(sys_get_temp_dir(), 'ioc_platforms_test_');
        file_put_contents($tempFile, $csvContent);

        // Copy to storage path that the service expects
        $storagePath = 'test_platforms.csv';
        $fullStoragePath = storage_path('app/public/'.$storagePath);

        // Ensure directory exists
        $directory = dirname($fullStoragePath);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        copy($tempFile, $fullStoragePath);

        // Run the import
        $result = $this->service->import($storagePath);

        // Assert results
        $this->assertEquals(2, $result['imported']);
        $this->assertEquals(2, $result['total_processed']);

        // Assert database records
        $this->assertDatabaseHas('ioc_platforms', [
            'name' => 'Platform 1',
            'description' => 'Description 1',
            'link' => 'https://example1.com',
            'contact' => 'contact1@example.com',
        ]);

        $this->assertDatabaseHas('ioc_platforms', [
            'name' => 'Platform 2',
            'description' => 'Description 2',
            'link' => 'https://example2.com',
            'contact' => 'contact2@example.com',
        ]);

        // Cleanup
        unlink($tempFile);
        unlink($fullStoragePath);
    }

    #[Test]
    public function it_throws_exception_for_missing_file()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('CSV file not found at path: nonexistent.csv');

        $this->service->import('nonexistent.csv');
    }

    #[Test]
    public function it_validates_csv_structure()
    {
        // Create a CSV with wrong number of columns
        $csvContent = "name,description\n";
        $csvContent .= 'Platform 1,Description 1';

        $tempFile = tempnam(sys_get_temp_dir(), 'ioc_platforms_test_');
        file_put_contents($tempFile, $csvContent);

        $storagePath = 'invalid_platforms.csv';
        $fullStoragePath = storage_path('app/public/'.$storagePath);

        $directory = dirname($fullStoragePath);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        copy($tempFile, $fullStoragePath);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid CSV format. Expected 4 columns: name, description, link, contact.');

        $this->service->import($storagePath);

        // Cleanup
        unlink($tempFile);
        unlink($fullStoragePath);
    }

    #[Test]
    public function it_truncates_existing_platforms_before_import()
    {
        // Create existing platform
        IOCPlatform::create([
            'name' => 'Existing Platform',
            'description' => 'Old description',
            'link' => 'https://old.com',
            'contact' => 'old@example.com',
        ]);

        $this->assertEquals(1, IOCPlatform::count());

        // Create CSV with new data
        $csvContent = "name,description,link,contact\n";
        $csvContent .= 'New Platform,New Description,https://new.com,new@example.com';

        $tempFile = tempnam(sys_get_temp_dir(), 'ioc_platforms_test_');
        file_put_contents($tempFile, $csvContent);

        $storagePath = 'new_platforms.csv';
        $fullStoragePath = storage_path('app/public/'.$storagePath);

        $directory = dirname($fullStoragePath);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        copy($tempFile, $fullStoragePath);

        // Import
        $result = $this->service->import($storagePath);

        // Assert old data was removed and new data was added
        $this->assertEquals(1, IOCPlatform::count());
        $this->assertEquals(1, $result['imported']);

        $this->assertDatabaseMissing('ioc_platforms', [
            'name' => 'Existing Platform',
        ]);

        $this->assertDatabaseHas('ioc_platforms', [
            'name' => 'New Platform',
        ]);

        // Cleanup
        unlink($tempFile);
        unlink($fullStoragePath);
    }

    #[Test]
    public function it_handles_empty_optional_fields()
    {
        // Create CSV with some empty fields
        $csvContent = "name,description,link,contact\n";
        $csvContent .= "Platform 1,,https://example.com,\n";
        $csvContent .= 'Platform 2,Description 2,,contact@example.com';

        $tempFile = tempnam(sys_get_temp_dir(), 'ioc_platforms_test_');
        file_put_contents($tempFile, $csvContent);

        $storagePath = 'platforms_with_empty_fields.csv';
        $fullStoragePath = storage_path('app/public/'.$storagePath);

        $directory = dirname($fullStoragePath);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        copy($tempFile, $fullStoragePath);

        // Import
        $result = $this->service->import($storagePath);

        // Assert results
        $this->assertEquals(2, $result['imported']);

        $this->assertDatabaseHas('ioc_platforms', [
            'name' => 'Platform 1',
            'description' => null,
            'link' => 'https://example.com',
            'contact' => null,
        ]);

        $this->assertDatabaseHas('ioc_platforms', [
            'name' => 'Platform 2',
            'description' => 'Description 2',
            'link' => null,
            'contact' => 'contact@example.com',
        ]);

        // Cleanup
        unlink($tempFile);
        unlink($fullStoragePath);
    }
}
