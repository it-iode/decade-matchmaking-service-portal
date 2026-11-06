<?php

declare(strict_types=1);

namespace App\Domains\Request\Actions;

use App\Domains\Request\Models\Request;
use App\Domains\Request\Services\RequestContextService;
use App\Domains\Request\Services\RequestService;
use App\Domains\User\Models\User;
use App\Shared\Contracts\ActionProviderInterface;

/**
 * Provides available actions for requests.
 */
readonly class RequestActionProvider implements ActionProviderInterface
{
    public function __construct(
        private RequestService $requestService
    ) {}

    /**
     * Get available actions for a request.
     *
     * @param  mixed  $entity  The request entity
     * @param  User|null  $user  The current user
     * @param  string|null  $context  The UI context (admin, user, etc.)
     * @return array<int, array<string, mixed>>
     */
    public function getActions(mixed $entity, ?User $user = null, ?string $context = null): array
    {
        if (! $entity instanceof Request) {
            return [];
        }

        $actions = [];
        $isAdmin = $context === 'admin';

        // Express Interest - for partners on user context
        if (! $isAdmin && $user && $user->can('expressInterest', $entity)) {
            $actions[] = [
                'key' => 'express_interest',
                'label' => 'Express Interest',
                'route' => route('request.express.interest', ['id' => $entity->id]),
                'method' => 'POST',
                'enabled' => true,
                'style' => [
                    'color' => 'blue',
                    'icon' => 'check',
                    'variant' => 'solid',
                ],
            ];
        }
        if ($user && $user->can('view', $entity)) {
            $actions[] = [
                'key' => 'view',
                'label' => 'View Request',
                'route' => route($this->getViewRouteName($context ?? RequestContextService::CONTEXT_USER_OWN), ['id' => $entity->id]),
                'method' => 'GET',
                'enabled' => true,
                'style' => [
                    'color' => 'blue',
                    'icon' => 'eye',
                    'variant' => 'solid',
                ],
            ];
        }
        // Edit - for request owners
        if ($user && $user->can('update', $entity)) {
            $actions[] = [
                'key' => 'edit',
                'label' => 'Edit Request',
                'route' => route('request.edit', ['id' => $entity->id]),
                'method' => 'GET',
                'enabled' => true,
                'style' => [
                    'color' => 'blue',
                    'icon' => 'pencil-square',
                    'variant' => 'solid',
                ],
            ];
        }

        // Export PDF - for anyone who can view
        if ($user && $user->can('exportPdf', $entity)) {
            $actions[] = [
                'key' => 'export_pdf',
                'label' => 'Export PDF',
                'route' => route('request.pdf', ['id' => $entity->id]),
                'method' => 'GET',
                'enabled' => true,
                'style' => [
                    'color' => 'blue',
                    'icon' => 'document-text',
                    'variant' => 'outline',
                ],
                'metadata' => [
                    'handler' => 'route',
                    'open_in_new_tab' => true,
                ],
            ];
        }

        // Admin-only actions
        if ($isAdmin && $user) {
            // Update Status
            if ($user->can('updateStatus', $entity)) {
                $actions[] = [
                    'key' => 'update_status',
                    'label' => 'Update Status',
                    'route' => null,
                    'method' => 'POST',
                    'enabled' => true,
                    'style' => [
                        'color' => 'blue',
                        'icon' => 'pencil',
                        'variant' => 'solid',
                    ],
                    'metadata' => [
                        'handler' => 'dialog',
                        'dialog_component' => 'UpdateStatusDialog',
                        'dialog_props' => [
                            'requestId' => $entity->id,
                            'currentStatus' => $entity->status,
                            'availableStatuses' => RequestService::getAvailableStatuses(),
                        ],
                    ],
                ];
            }

            // Add Offer
            if ($user->can('manageOffers', $entity)) {
                $actions[] = [
                    'key' => 'add_offer',
                    'label' => 'Add Offer',
                    'route' => route('admin.offer.create', ['request_id' => $entity->id]),
                    'method' => 'GET',
                    'enabled' => true,
                    'style' => [
                        'color' => 'green',
                        'icon' => 'plus',
                        'variant' => 'solid',
                    ],
                ];
            }

            // View Offers
            if ($user->can('manageOffers', $entity) && $entity->offers()->count() > 0) {
                $actions[] = [
                    'key' => 'view_offers',
                    'label' => 'View All Offers',
                    'route' => route('admin.offer.list', ['request' => $entity->id]),
                    'method' => 'GET',
                    'enabled' => true,
                    'style' => [
                        'color' => 'blue',
                        'icon' => 'document-text',
                        'variant' => 'outline',
                    ],
                ];
            }

            // Delete
            if ($user->can('delete', $entity)) {
                $actions[] = [
                    'key' => 'delete',
                    'label' => 'Delete Request',
                    'route' => route('user.request.destroy', ['id' => $entity->id]),
                    'method' => 'DELETE',
                    'enabled' => true,
                    'style' => [
                        'color' => 'red',
                        'icon' => 'trash',
                        'variant' => 'outline',
                    ],
                    'confirm' => 'This action cannot be undone. Are you sure you want to delete this request?',
                ];
            }
        }

        return array_values($actions);
    }

    /**
     * Get the appropriate view route name based on context.
     */
    private function getViewRouteName(string $context): string
    {
        return match ($context) {
            RequestContextService::CONTEXT_ADMIN => 'admin.request.show',
            RequestContextService::CONTEXT_USER_OWN => 'request.me.show',
            RequestContextService::CONTEXT_PUBLIC => 'request.public.show',
            RequestContextService::CONTEXT_MATCHED => 'request.matched.show',
            RequestContextService::CONTEXT_SUBSCRIBED => 'request.subscribed.show',
            default => 'request.me.show', // Safe fallback: an existing route (matches the CONTEXT_USER_OWN default used by callers)
        };
    }
}
