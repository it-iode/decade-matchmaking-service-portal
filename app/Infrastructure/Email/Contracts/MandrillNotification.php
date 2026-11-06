<?php

declare(strict_types=1);

namespace App\Infrastructure\Email\Contracts;

/**
 * Contract for notifications delivered through the custom Mandrill channel.
 *
 * Any notification that lists 'mandrill' in its via() method should implement
 * this interface. The channel resolves the template, variables and (optionally)
 * the target queue from the payload returned by toMandrill().
 *
 * @see \App\Infrastructure\Email\Channels\MandrillChannel
 */
interface MandrillNotification
{
    /**
     * Get the Mandrill representation of the notification.
     *
     * @return array{template: string, variables?: array<string, mixed>, queue?: string}
     */
    public function toMandrill(object $notifiable): array;
}
