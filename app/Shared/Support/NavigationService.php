<?php

declare(strict_types=1);

namespace App\Shared\Support;

use App\Domains\User\Models\User;

class NavigationService
{
    public function getNavigationItems(?User $user = null): array
    {
        if (! $user) {
            return $this->getGuestNavigation();
        }

        $items = [];

        // Home - available to all authenticated users
        $items[] = [
            'id' => 'home',
            'label' => 'Home',
            'route' => 'user.home',
            'icon' => 'HomeIcon',
            'visible' => true,
        ];

        // Admin Dashboard - only for administrators
        if ($user->hasRole('administrator')) {
            $items[] = [
                'id' => 'dashboard',
                'label' => 'Dashboard',
                'route' => 'admin.dashboard.index',
                'icon' => 'ChartBarIcon',
                'badge' => $this->getUnreadNotificationsBadge($user),
                'visible' => true,
            ];
        }

        if ($user->hasRole('user') || $user->hasRole('partner')) {
            $items[] = [
                'id' => 'my-requests',
                'label' => 'My Requests List',
                'route' => 'request.me.list',
                'icon' => 'DocumentTextIcon',
                'visible' => true,
            ];
        }

        // My Opportunities - only for partners
        if ($user->hasRole('partner')) {
            $items[] = [
                'id' => 'my-opportunities',
                'label' => 'My Opportunities List',
                'route' => 'me.opportunity.list',
                'icon' => 'BriefcaseIcon',
                'visible' => true,
            ];
        }

        // Add divider before settings section
        $items[] = [
            'id' => 'settings-divider',
            'divider' => true,
        ];

        // SystemNotification Settings
        $items[] = [
            'id' => 'notification-settings',
            'label' => 'Notifications settings',
            'route' => 'notification.preferences.index',
            'icon' => 'BellIcon',
            'visible' => true,
        ];

        // Add divider before sign out
        $items[] = [
            'id' => 'signout-divider',
            'divider' => true,
        ];

        // Sign Out
        $items[] = [
            'id' => 'sign-out',
            'label' => 'Sign Out',
            'action' => 'sign-out',
            'icon' => 'ArrowRightOnRectangleIcon',
            'visible' => true,
        ];

        return [
            'items' => $items,
            'user' => [
                'displayName' => trim($user->first_name.' '.$user->last_name),
                'avatar' => $user->avatar_url ?? null,
            ],
        ];
    }

    private function getGuestNavigation(): array
    {
        return [
            'items' => [],
            'user' => null,
        ];
    }

    private function getUnreadNotificationsBadge(User $user): ?array
    {
        $count = $user->notifications()->where('is_read', false)->count();

        if ($count === 0) {
            return null;
        }

        return [
            'value' => $count,
            'variant' => 'danger',
        ];
    }
}
