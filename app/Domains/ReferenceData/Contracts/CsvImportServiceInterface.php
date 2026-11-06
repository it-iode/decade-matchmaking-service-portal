<?php

declare(strict_types=1);

namespace App\Domains\ReferenceData\Contracts;

interface CsvImportServiceInterface
{
    /**
     * Import data from CSV file
     *
     * @param  string  $filePath  Relative path from storage/app/public/
     * @return array{imported: int, total_processed: int} Import statistics
     */
    public function importFromCsv(string $filePath): array;
}
