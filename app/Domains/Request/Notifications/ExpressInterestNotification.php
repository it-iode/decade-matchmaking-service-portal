<?php

declare(strict_types=1);

namespace App\Domains\Request\Notifications;

use App\Domains\Request\Models\Request;
use App\Infrastructure\Email\Notifications\AbstractMandrillNotification;

/**
 * Notifies a partner that their expression of interest was recorded.
 */
class ExpressInterestNotification extends AbstractMandrillNotification
{
    public function __construct(private readonly Request $request) {}

    /**
     * @return array{template: string, variables: array<string, mixed>}
     */
    public function toMandrill(object $notifiable): array
    {
        return [
            'template' => 'request.express-interest.partner',
            'variables' => [
                'Request_Title' => $this->request->detail->capacity_development_title ?? 'N/A',
            ] + $this->baseVariables($notifiable),
        ];
    }
}
