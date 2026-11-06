<?php

namespace App\Domains\ReferenceData\Controllers;

use App\Domains\ReferenceData\Resources\IOCPlatformResource;
use App\Domains\ReferenceData\Services\IOCPlatformService;
use App\Http\Controllers\Controller;

class IOCPlatformsController extends Controller
{
    public function __construct(
        private readonly IOCPlatformService $platformService
    ) {}

    /**
     * Get all IOC platforms
     */
    public function index()
    {
        $platforms = $this->platformService->getAllPlatforms();

        return [
            'platforms' => IOCPlatformResource::collection($platforms),
        ];
    }
}
