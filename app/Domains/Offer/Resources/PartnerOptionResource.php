<?php

declare(strict_types=1);

namespace App\Domains\Offer\Resources;

use App\Domains\User\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class PartnerOptionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'value' => $this->id,
            'label' => "{$this->name} ({$this->email})",
        ];
    }
}
