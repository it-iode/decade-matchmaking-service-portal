<?php

declare(strict_types=1);

namespace App\Infrastructure\Email\Channels;

use App\Domains\User\Models\User;
use App\Infrastructure\Email\Jobs\SendTransactionalEmail;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * @see \App\Infrastructure\Email\Contracts\MandrillNotification
 */
class MandrillChannel
{
    /**
     * Send the given notification via Mandrill.
     */
    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toMandrill')) {
            Log::warning('MandrillChannel: Notification does not implement toMandrill()', [
                'notification' => get_class($notification),
            ]);

            return;
        }

        $data = $notification->toMandrill($notifiable);

        if (! isset($data['template'])) {
            Log::warning('MandrillChannel: No template specified in notification data', [
                'notification' => get_class($notification),
            ]);

            return;
        }

        $recipient = $this->resolveRecipient($notifiable, $notification);

        if ($recipient === null) {
            Log::warning('MandrillChannel: Unable to resolve a recipient email', [
                'notification' => get_class($notification),
                'notifiable' => get_class($notifiable),
            ]);

            return;
        }

        $queue = $data['queue'] ?? Config::get('mail-templates.queue.queue_name', 'default');

        $logContext = [
            'template' => $data['template'],
            'queue' => $queue,
            'notification' => get_class($notification),
        ];

        Log::info('MandrillChannel: Queueing email', $logContext);

        try {
            SendTransactionalEmail::dispatch(
                $data['template'],
                $recipient,
                $data['variables'] ?? [],
                ['queue' => $queue],
            );
        } catch (Throwable $e) {
            Log::error('MandrillChannel: Failed to queue email', $logContext + [
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        Log::info('MandrillChannel: Email job queued', $logContext);
    }

    /**
     * Resolve a recipient that SendTransactionalEmail accepts (User or [email, name]).
     *
     * @return User|array{email: string, name: string}|null
     */
    private function resolveRecipient(object $notifiable, Notification $notification): User|array|null
    {
        if ($notifiable instanceof User) {
            return $notifiable;
        }

        // On-demand / anonymous notifications expose their routes here.
        if (method_exists($notifiable, 'routeNotificationFor')) {
            $route = $notifiable->routeNotificationFor('mandrill', $notification)
                ?? $notifiable->routeNotificationFor('mail', $notification);

            $recipient = $this->recipientFromRoute($route);

            if ($recipient !== null) {
                return $recipient;
            }
        }

        return $this->recipientFromEmailProperty($notifiable);
    }

    /**
     * Build a recipient from a notifiable exposing email/name properties.
     *
     * @return array{email: string, name: string}|null
     */
    private function recipientFromEmailProperty(object $notifiable): ?array
    {
        if (! isset($notifiable->email) || ! is_string($notifiable->email) || $notifiable->email === '') {
            return null;
        }

        return [
            'email' => $notifiable->email,
            'name' => is_string($notifiable->name ?? null) ? $notifiable->name : 'User',
        ];
    }

    /**
     * Normalise a routeNotificationFor() value into an [email, name] pair.
     *
     * Supports the string form ('email@x') and both array shapes
     * (['email@x'] and ['email@x' => 'Name']).
     *
     * @return array{email: string, name: string}|null
     */
    private function recipientFromRoute(mixed $route): ?array
    {
        if (is_string($route) && $route !== '') {
            return ['email' => $route, 'name' => 'User'];
        }

        if (! is_array($route) || $route === []) {
            return null;
        }

        $first = array_key_first($route);
        $email = is_string($first) ? $first : (string) reset($route);
        $name = is_string($first) ? (string) $route[$first] : 'User';

        return $email !== '' ? ['email' => $email, 'name' => $name] : null;
    }
}
