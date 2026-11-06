<?php

declare(strict_types=1);

namespace App\Domains\ReferenceData\Services\Import;

use App\Domains\ReferenceData\Models\Organization;

class OrganizationImportService extends AbstractCsvImportService
{
    /**
     * Get the Eloquent model class for organizations
     */
    protected function getModelClass(): string
    {
        return Organization::class;
    }

    /**
     * Get the expected number of columns (name, description, link)
     */
    protected function getExpectedColumnCount(): int
    {
        return 3;
    }

    /**
     * Get the entity name for logging
     */
    protected function getEntityName(): string
    {
        return 'Organization';
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
        ];
    }
}
