<?php

declare(strict_types=1);

namespace App\Domains\Request\Resources;

use App\Domains\Offer\Resources\OfferResource;
use App\Domains\Request\Models\Request as OCDRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Pure data transformation resource for Request.
 *
 * Note: Actions are NOT included here. They are passed separately
 * from the controller to maintain Single Responsibility Principle.
 *
 * @mixin OCDRequest
 */
class RequestResource extends JsonResource
{
    public static $wrap = null;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Eager load relationships to avoid N+1 queries
        $this->resource->loadMissing(['user']);

        return [
            'id' => $this->id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'status' => $this->whenLoaded('status'),
            'user' => $this->whenLoaded('user'),
            'matched_partner' => $this->whenLoaded('matchedPartner'),
            'offers' => $this->getAuthorizedOffers($request),
            'detail' => new DetailResource($this->whenLoaded('detail')),
            'subscriptions' => $this->whenLoaded('subscriptions'),
            'subscribers' => $this->whenLoaded('subscribers'),
        ];
    }

    /**
     * Get offers that the current user is authorized to view.
     */
    private function getAuthorizedOffers(Request $request): ?AnonymousResourceCollection
    {
        if (! $this->relationLoaded('offers') || ! $request->user()?->can('viewOffers', $this->resource)) {
            return null;
        }

        return OfferResource::collection($this->whenLoaded('offers'));
    }
}
