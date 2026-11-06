<?php

declare(strict_types=1);

namespace App\Domains\Request\Resources;

use App\Domains\Request\Enums\PublicRequestStatus;
use Illuminate\Http\Request;

/**
 * Public Request Resource
 *
 * Extends RequestResource to provide simplified status labels for public-facing request lists.
 * Uses PublicRequestStatus enum to map technical status codes to user-friendly labels.
 */
class PublicRequestResource extends RequestResource
{
    /**
     * Transform the resource into an array with simplified status labels.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);

        // Transform status label if status is loaded
        if (isset($data['status']) && is_object($data['status'])) {
            $data['status'] = $this->transformStatus($data['status']);
        }

        return $data;
    }

    /**
     * Transform status object to use simplified labels for public display.
     *
     * Uses the PublicRequestStatus enum to determine the appropriate public-facing label
     * based on the technical status code.
     *
     * @param  object  $status  The status object containing status_code and status_label
     * @return object The transformed status object with updated label
     */
    private function transformStatus(object $status): object
    {
        $statusCode = $status->status_code ?? '';

        // Get the public status enum from the technical status code
        $publicStatus = PublicRequestStatus::fromTechnicalStatus($statusCode);

        // Use the enum's label if a mapping exists, otherwise fall back to original label
        $statusLabel = $publicStatus?->label() ?? $status->status_label ?? $statusCode;

        // Clone the status object and update the label
        $transformedStatus = clone $status;
        $transformedStatus->status_label = $statusLabel;

        return $transformedStatus;
    }
}
