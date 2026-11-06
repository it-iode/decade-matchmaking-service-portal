<?php

declare(strict_types=1);

namespace App\Domains\ReferenceData\Services\Import;

use App\Domains\ReferenceData\Models\IOCPlatform;

class IOCPlatformImportService extends AbstractCsvImportService
{
    /**
     * Get the Eloquent model class for IOC platforms
     */
    protected function getModelClass(): string
    {
        return IOCPlatform::class;
    }

    /**
     * Get the expected number of columns (name, description, link, contact)
     */
    protected function getExpectedColumnCount(): int
    {
        return 4;
    }

    /**
     * Get the entity name for logging
     */
    protected function getEntityName(): string
    {
        return 'IOC Platform';
    }

    /**
     * Map CSV row to database field array
     */
    protected function mapRowToData(array $row): array
    {
        return [
            'name' => trim($row[0]),
            'description' => ! empty(trim($row[1])) ? trim($row[1]) : null,
            'link' => ! empty(trim($row[2])) ? trim($row[2]) : null,
            'contact' => ! empty(trim($row[3])) ? trim($row[3]) : null,
        ];
    }
}
