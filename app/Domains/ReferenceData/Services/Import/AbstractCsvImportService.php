<?php

declare(strict_types=1);

namespace App\Domains\ReferenceData\Services\Import;

use App\Domains\ReferenceData\Contracts\CsvImportServiceInterface;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

abstract class AbstractCsvImportService implements CsvImportServiceInterface
{
    /**
     * Get the Eloquent model class for this import
     */
    abstract protected function getModelClass(): string;

    /**
     * Get the expected number of columns in CSV
     */
    abstract protected function getExpectedColumnCount(): int;

    /**
     * Get the entity name for logging
     */
    abstract protected function getEntityName(): string;

    /**
     * Map CSV row to database field array
     *
     * @param  array  $row  CSV row data
     * @return array Database field mapping
     */
    abstract protected function mapRowToData(array $row): array;

    /**
     * Import from stored CSV file path
     *
     * @return array{imported: int, total_processed: int}
     */
    public function importFromCsv(string $filePath): array
    {
        try {
            $fullPath = storage_path('app/public/'.$filePath);

            if (! file_exists($fullPath)) {
                throw new Exception('CSV file not found at path: '.$filePath);
            }

            $csvData = $this->parseCsvFileFromPath($fullPath);

            $this->validateCsvStructure($csvData);

            Log::info("Starting {$this->getEntityName()} CSV import from stored file", [
                'file_path' => $filePath,
                'full_path' => $fullPath,
            ]);

            return $this->processImportWithoutTransaction($csvData);
        } catch (Exception $e) {
            Log::error("{$this->getEntityName()} CSV import failed from stored file: ".$e->getMessage(), [
                'file_path' => $filePath,
            ]);

            throw $e;
        }
    }

    /**
     * Parse CSV file from storage path and return data array
     */
    private function parseCsvFileFromPath(string $filePath): array
    {
        Log::info("Parsing {$this->getEntityName()} CSV file from storage", [
            'file_path' => $filePath,
            'file_size' => filesize($filePath),
        ]);

        $csvData = array_map('str_getcsv', file($filePath));

        Log::info("Raw {$this->getEntityName()} CSV data parsed from storage", [
            'total_rows' => count($csvData),
            'first_row' => $csvData[0] ?? 'No data',
        ]);

        // Remove header row if present
        $header = array_shift($csvData);

        Log::info("{$this->getEntityName()} CSV structure from storage", [
            'header' => $header,
            'data_rows' => count($csvData),
            'sample_row' => $csvData[0] ?? 'No data rows',
        ]);

        return [
            'header' => $header,
            'data' => $csvData,
        ];
    }

    /**
     * Validate CSV structure
     */
    private function validateCsvStructure(array $csvData): void
    {
        $header = $csvData['header'];
        $data = $csvData['data'];

        if (empty($data) || count($header) !== $this->getExpectedColumnCount()) {
            throw new Exception(
                "Invalid CSV format. Expected {$this->getExpectedColumnCount()} columns."
            );
        }
    }

    /**
     * Process the import without transaction management
     *
     * @return array{imported: int, total_processed: int}
     */
    private function processImportWithoutTransaction(array $csvData): array
    {
        $modelClass = $this->getModelClass();
        $tableName = (new $modelClass)->getTable();

        Log::info("Truncating {$tableName} table before import");

        // Truncate the table to remove all existing data
        $modelClass::truncate();

        // Prepare data for bulk insert
        $recordsData = [];
        foreach ($csvData['data'] as $row) {
            // Basic validation - skip empty rows and ensure minimum required columns
            if (! empty(array_filter($row)) && count($row) >= $this->getExpectedColumnCount() && ! empty(trim($row[0]))) {
                $recordsData[] = array_merge(
                    $this->mapRowToData($row),
                    [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }

        Log::info("Bulk inserting {$this->getEntityName()} records", [
            'records_to_insert' => count($recordsData),
        ]);

        // Bulk insert all records
        if (! empty($recordsData)) {
            $modelClass::insert($recordsData);
        }

        Log::info("{$this->getEntityName()} CSV import completed successfully", [
            'imported' => count($recordsData),
            'total_processed' => count($csvData['data']),
        ]);

        return [
            'imported' => count($recordsData),
            'total_processed' => count($csvData['data']),
        ];
    }
}
