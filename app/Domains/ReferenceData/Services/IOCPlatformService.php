<?php

declare(strict_types=1);

namespace App\Domains\ReferenceData\Services;

use Illuminate\Database\Eloquent\Collection;

readonly class IOCPlatformService
{
    public function __construct(
        private IOCPlatformRepository $repository
    ) {}

    /**
     * Get all IOC platforms
     */
    public function getAllPlatforms(): Collection
    {
        return $this->repository->getAll();
    }
}
