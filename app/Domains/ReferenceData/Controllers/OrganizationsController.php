<?php

namespace App\Domains\ReferenceData\Controllers;

use App\Domains\ReferenceData\Resources\OrganizationResource;
use App\Domains\ReferenceData\Services\OrganizationService;
use App\Http\Controllers\Controller;

class OrganizationsController extends Controller
{
    public function __construct(
        private readonly OrganizationService $organizationService
    ) {}

    /**
     * Get all organizations
     */
    public function index()
    {
        $organizations = $this->organizationService->getAllOrganizations();

        return [
            'organizations' => OrganizationResource::collection($organizations),
        ];
    }
}
