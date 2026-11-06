<?php

declare(strict_types=1);

namespace App\Domains\Offer\Resources;

use App\Domains\Document\Resources\DocumentResource;
use App\Domains\Offer\Actions\OfferActionProvider;
use App\Domains\Offer\Models\Offer;
use App\Domains\Request\Resources\RequestResource;
use App\Shared\Http\BaseResource;
use Illuminate\Http\Request;

/**
 * @mixin Offer
 */
class OfferResource extends BaseResource
{
    protected function actionProvider(): ?string
    {
        return OfferActionProvider::class;
    }

    /**
     * @return array<string, mixed>
     */
    protected function fields(Request $request): array
    {
        return [
            'id' => $this->id,
            'description' => $this->description,
            'status' => [
                'value' => $this->status->value,
                'label' => $this->status->label(),
            ],
            'is_accepted' => $this->is_accepted,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relationships
            'request' => $this->whenLoaded('request', function () {
                return new RequestResource($this->request);
            }),
            'matched_partner' => $this->whenLoaded('matchedPartner'),
            'documents' => $this->whenLoaded('documents', function () {
                return DocumentResource::collection($this->documents);
            }),
        ];
    }
}
