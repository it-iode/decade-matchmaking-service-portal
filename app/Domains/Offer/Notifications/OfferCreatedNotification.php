<?php

declare(strict_types=1);

namespace App\Domains\Offer\Notifications;

use App\Domains\Offer\Models\Offer;
use App\Infrastructure\Email\Notifications\AbstractMandrillNotification;

/**
 * Notifies a request owner that a new offer was made on their request.
 */
class OfferCreatedNotification extends AbstractMandrillNotification
{
    public function __construct(private readonly Offer $offer) {}

    /**
     * @return array{template: string, variables: array<string, mixed>}
     */
    public function toMandrill(object $notifiable): array
    {
        return [
            'template' => 'offer.created',
            'variables' => [
                'Offer_ID' => $this->offer->id,
                'Request_Title' => $this->offer->request?->detail?->capacity_development_title ?? 'N/A',
                'Request_Link' => route('request.me.show', $this->offer->request_id),
                'Partner_Name' => $this->offer->matchedPartner?->name ?? 'Unknown Partner',
                'user_name' => $notifiable->name,
            ] + $this->baseVariables($notifiable),
        ];
    }
}
