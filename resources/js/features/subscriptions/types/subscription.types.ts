import { PaginationLinkProps, User } from '@/types';
import { OCDRequest } from '@features/requests/types';

/**
 * Subscription source type
 */
export type SubscriptionSource = 'admin' | 'user';

/**
 * Request subscription entity
 */
export interface RequestSubscription {
    id: number;
    user_id: number;
    request_id: number;
    subscribed_by_admin: boolean;
    admin_user_id?: number;
    created_at: string;
    updated_at: string;
    user?: User;
    request?: OCDRequest;
    admin_user?: User;
}

/**
 * Subscription statistics for admin dashboard
 */
export interface SubscriptionStats {
    total_subscriptions: number;
    admin_created_subscriptions: number;
    user_created_subscriptions: number;
    unique_subscribers: number;
    unique_subscribed_requests: number;
}

/**
 * Paginated subscriptions response
 */
export interface SubscriptionsPagination {
    current_page: number;
    data: RequestSubscription[];
    first_page_url: string;
    from: number;
    last_page: number;
    last_page_url: string;
    links: PaginationLinkProps[];
    next_page_url: string | null;
    path: string;
    per_page: number;
    prev_page_url: string | null;
    to: number;
    total: number;
}

/**
 * Form options for subscription creation
 */
export interface SubscriptionFormOptions {
    users: Array<{ value: number; label: string }>;
    requests: Array<{ value: number; label: string }>;
}

export type SubscriptionsList = RequestSubscription[];
