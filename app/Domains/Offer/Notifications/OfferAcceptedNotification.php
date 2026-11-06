<?php

declare(strict_types=1);

namespace App\Domains\Offer\Notifications;

use App\Domains\Offer\Models\Offer;
use App\Domains\User\Models\User;
use App\Infrastructure\Email\Notifications\AbstractMandrillNotification;

/**
 * Notifies a recipient (admin, partner, or requester) that an offer was accepted.
 *
 * The same template is delivered to multiple audiences; the recipient's role in the
 * exchange is carried explicitly via $recipientType so overlapping recipients (e.g. an
 * admin who is also the requester) receive correctly-typed messages.
 */
class OfferAcceptedNotification extends AbstractMandrillNotification
{
    public function __construct(
        private readonly Offer $offer,
        private readonly User $acceptedBy,
        private readonly string $recipientType
    ) {}

    /**
     * @return array{template: string, variables: array<string, mixed>}
     */
    public function toMandrill(object $notifiable): array
    {
        return [
            'template' => 'offer.accepted',
            'variables' => [
                'Offer_ID' => $this->offer->id,
                'Request_Title' => $this->offer->request?->detail?->capacity_development_title ?? 'N/A',
                // Deep-link each audience to a request view they can reach that also
                // surfaces the active offer: the requester to their own request, the
                // partner to the matched view (role:user group), and the admin to the
                // admin view (role:administrator group). request.public.show is avoided
                // because the PUBLIC context hides the active offer.
                'Request_Link' => match ($this->recipientType) {
                    'requester' => route('request.me.show', $this->offer->request_id),
                    'admin' => route('admin.request.show', $this->offer->request_id),
                    default => route('request.matched.show', $this->offer->request_id),
                },
                'Partner_Name' => $this->offer->matchedPartner?->name ?? 'N/A',
                'Accepted_By' => $this->acceptedBy->name ?? 'N/A',
                'user_name' => $notifiable->name,
                'recipient_type' => $this->recipientType,
            ] + $this->baseVariables($notifiable),
        ];
    }
}
