<?php

declare(strict_types=1);

namespace App\Domains\ReferenceData\Services;

use Illuminate\Database\Eloquent\Collection;

readonly class OrganizationService
{
    public function __construct(
        private OrganizationRepository $repository
    ) {}

    /**
     * Get all organizations
     */
    public function getAllOrganizations(): Collection
    {
        return $this->repository->getAll();
    }
}
