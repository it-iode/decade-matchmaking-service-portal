<?php

namespace App\Domains\ReferenceData\Services;

use App\Domains\ReferenceData\Models\IOCPlatform;
use Illuminate\Database\Eloquent\Collection;

class IOCPlatformRepository
{
    /**
     * Get all IOC platforms
     */
    public function getAll(): Collection
    {
        return IOCPlatform::orderBy('name', 'asc')->get();
    }

    /**
     * Find IOC platform by ID
     */
    public function findById(int $id): ?IOCPlatform
    {
        return IOCPlatform::find($id);
    }
}
