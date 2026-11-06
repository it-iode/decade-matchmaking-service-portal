<?php

declare(strict_types=1);

namespace App\Domains\Opportunity\Notifications;

use App\Domains\Opportunity\Enums\Status;
use App\Domains\Opportunity\Models\Opportunity;
use App\Infrastructure\Email\Notifications\AbstractMandrillNotification;

/**
 * Notifies an opportunity creator that the status of their opportunity changed.
 */
class OpportunityStatusChangedNotification extends AbstractMandrillNotification
{
    public function __construct(
        private readonly Opportunity $opportunity,
        private readonly mixed $previousStatus,
        private readonly Status $newStatus
    ) {}

    /**
     * @return array{template: string, variables: array<string, mixed>}
     */
    public function toMandrill(object $notifiable): array
    {
        return [
            'template' => 'opportunity.updated',
            'variables' => [
                'Opportunity_Title' => $this->opportunity->title,
                'Opportunity_Link' => route('opportunity.show', $this->opportunity->id),
                'user_name' => $notifiable->name,
                'Previous_Status' => $this->statusLabel($this->previousStatus),
                'Current_Status' => $this->newStatus->label(),
            ] + $this->baseVariables($notifiable),
        ];
    }

    /**
     * Resolve a human-readable label from a status value (enum, numeric, or unknown).
     */
    private function statusLabel(mixed $status): string
    {
        if ($status instanceof Status) {
            return $status->label();
        }

        if (is_numeric($status)) {
            return Status::tryFrom((int) $status)?->label() ?? 'Unknown';
        }

        return 'Unknown';
    }
}
