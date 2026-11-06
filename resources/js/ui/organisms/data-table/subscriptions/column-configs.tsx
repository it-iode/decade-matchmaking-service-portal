import React from 'react';
import { RequestSubscription } from '@features/subscriptions/types';
import { formatDate } from '@shared/utils';
import { Badge } from '@ui/primitives/badge';

type SortField = 'id' | 'user_name' | 'request_title' | 'created_at';

interface TableColumn {
    key: string;
    label: string;
    sortable?: boolean;
    sortField?: SortField;
    render: (subscription: RequestSubscription) => React.ReactNode;
    className?: string;
    headerClassName?: string;
}

/**
 * Get source badge color based on subscription source
 */
const getSourceBadgeColor = (isAdminCreated: boolean): 'blue' | 'zinc' => {
    return isAdminCreated ? 'blue' : 'zinc';
};

/**
 * Get source label for subscription
 */
const getSourceLabel = (subscription: RequestSubscription): string => {
    if (subscription.subscribed_by_admin) {
        return subscription.admin_user?.name || 'Admin';
    }
    return 'Self';
};

/**
 * Column configurations for subscriptions table
 */
export const subscriptionColumns: TableColumn[] = [
    {
        key: 'user',
        label: 'User',
        sortable: true,
        sortField: 'user_name' as const,
        render: (subscription: RequestSubscription) => (
            <div className="flex flex-col max-w-xs">
                <span className="font-medium truncate">
                    {subscription.user?.name || 'Unknown User'}
                </span>
                <span className="text-xs text-zinc-500 truncate">
                    {subscription.user?.email}
                </span>
            </div>
        ),
    },
    {
        key: 'request',
        label: 'Request',
        sortable: true,
        sortField: 'request_title' as const,
        render: (subscription: RequestSubscription) => (
            <div className="flex flex-col max-w-xs">
                <span className="font-medium truncate">
                    {subscription.request?.detail?.capacity_development_title || `Request #${subscription.request_id}`}
                </span>
                <span className="text-xs text-zinc-500 truncate">
                    {subscription.request?.user?.name || 'Unknown Requester'}
                </span>
            </div>
        ),
    },
    {
        key: 'source',
        label: 'Source',
        sortable: false,
        render: (subscription: RequestSubscription) => (
            <Badge color={getSourceBadgeColor(subscription.subscribed_by_admin)}>
                {getSourceLabel(subscription)}
            </Badge>
        ),
    },
    {
        key: 'subscribed',
        label: 'Subscribed',
        sortable: true,
        sortField: 'created_at' as const,
        render: (subscription: RequestSubscription) => (
            <div className="flex flex-col">
                <span className="text-zinc-600 dark:text-zinc-300">
                    {formatDate(subscription.created_at, 'en-US', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric',
                    })}
                </span>
                <span className="text-xs text-zinc-400">
                    {formatDate(subscription.created_at, 'en-US', {
                        hour: '2-digit',
                        minute: '2-digit',
                    })}
                </span>
            </div>
        ),
    },
];
