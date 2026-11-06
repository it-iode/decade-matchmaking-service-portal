<?php

declare(strict_types=1);

namespace App\Domains\Offer\Notifications;

use App\Domains\Offer\Models\Offer;
use App\Domains\User\Models\User;
use App\Infrastructure\Email\Notifications\AbstractMandrillNotification;

/**
 * Notifies the offering partner that their offer was rejected.
 */
class OfferRejectedNotification extends AbstractMandrillNotification
{
    public function __construct(
        private readonly Offer $offer,
        private readonly User $rejectedBy
    ) {}

    /**
     * @return array{template: string, variables: array<string, mixed>}
     */
    public function toMandrill(object $notifiable): array
    {
        return [
            'template' => 'offer.rejected',
            'variables' => [
                'Offer_ID' => $this->offer->id,
                'Request_Title' => $this->offer->request?->detail?->capacity_development_title ?? 'N/A',
                'Request_Link' => route('request.matched.show', $this->offer->request_id),
                'Rejected_By' => $this->rejectedBy->name ?? 'Request Owner',
                'user_name' => $notifiable->name,
            ] + $this->baseVariables($notifiable),
        ];
    }
}
