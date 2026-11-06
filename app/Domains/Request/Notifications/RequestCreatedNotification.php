<?php

declare(strict_types=1);

namespace App\Domains\Request\Notifications;

use App\Domains\Request\Models\Request;
use App\Infrastructure\Email\Notifications\AbstractMandrillNotification;

/**
 * Confirms to the requester that their request was created.
 */
class RequestCreatedNotification extends AbstractMandrillNotification
{
    public function __construct(private readonly Request $request) {}

    /**
     * @return array{template: string, variables: array<string, mixed>}
     */
    public function toMandrill(object $notifiable): array
    {
        return [
            'template' => 'request.created',
            'variables' => [
                'Request_Title' => $this->request->detail->getAttribute('capacity_development_title') ?? 'N/A',
                'Request_Link' => route('request.me.show', $this->request->id),
                'user_name' => $notifiable->name,
            ] + $this->baseVariables($notifiable),
        ];
    }
}
