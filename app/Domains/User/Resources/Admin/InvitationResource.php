<?php

declare(strict_types=1);

namespace App\Domains\User\Resources\Admin;

use App\Domains\User\Enums\Invitation\Status;
use App\Domains\User\Models\UserInvitation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvitationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var UserInvitation $invitation */
        $invitation = $this->resource;

        $status = $this->computeStatus($invitation);

        return [
            'id' => $invitation->id,
            'name' => $invitation->name,
            'email' => $invitation->email,
            'status' => [
                'value' => $status->value,
                'label' => $status->label(),
                'color' => $status->color(),
            ],
            'inviter' => $this->whenLoaded('inviter', fn () => [
                'id' => $invitation->inviter->id,
                'name' => $invitation->inviter->name,
                'email' => $invitation->inviter->email,
            ]),
            'expires_at' => $invitation->expires_at->toIso8601String(),
            'accepted_at' => $invitation->accepted_at?->toIso8601String(),
            'created_at' => $invitation->created_at->toIso8601String(),
            'is_resendable' => ! $invitation->isAccepted(),
            'is_cancellable' => ! $invitation->isAccepted(),
        ];
    }

    /**
     * Compute the status based on invitation state
     */
    private function computeStatus(UserInvitation $invitation): Status
    {
        if ($invitation->isAccepted()) {
            return Status::ACCEPTED;
        }

        if ($invitation->isExpired()) {
            return Status::EXPIRED;
        }

        return Status::PENDING;
    }
}
