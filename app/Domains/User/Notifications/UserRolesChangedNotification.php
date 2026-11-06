<?php

declare(strict_types=1);

namespace App\Domains\User\Notifications;

use App\Infrastructure\Email\Notifications\AbstractMandrillNotification;

/**
 * Notifies a user that their roles have changed.
 */
class UserRolesChangedNotification extends AbstractMandrillNotification
{
    /**
     * @return array{template: string, variables: array<string, mixed>}
     */
    public function toMandrill(object $notifiable): array
    {
        return [
            'template' => 'user.roles_changed',
            'variables' => [
                'name' => $notifiable->name,
                'portal_url' => route('user.home'),
            ],
        ];
    }
}
