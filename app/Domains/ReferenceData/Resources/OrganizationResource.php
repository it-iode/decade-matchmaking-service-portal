<?php

namespace App\Domains\ReferenceData\Resources;

use App\Domains\ReferenceData\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Organization
 */
class OrganizationResource extends JsonResource
{
    public static $wrap = null;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'website' => $this->link, // Map 'link' field to 'website' for frontend consistency
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
