import { User, Role } from '@/types';

export interface UserStatus {
    value: 'active' | 'blocked' | 'unverified';
    label: string;
    color: string;
}

export interface UserStatistics {
    total_requests: number;
    active_requests: number;
    total_offers: number;
    total_opportunities: number;
    notifications_received: number;
    unread_notifications: number;
    account_age_days: number;
    last_activity: string;
}

export interface UserActivity {
    recent_requests: number;
    recent_offers: number;
    activity_timeline: ActivityItem[];
}

export interface ActivityItem {
    type: string;
    title: string;
    date: string;
}

export interface UserManagement extends User {
    roles: Role[];
    permissions: string[];
    status: UserStatus;
    is_blocked: boolean;
    email_verified: boolean;
    email_verified_at?: string;
    created_at: string;
    updated_at: string;
    requests_count?: number;
    notifications_count?: number;
    avatar_url: string;
    is_social_user: boolean;
    provider?: string;
}

export interface UserDetailData extends UserManagement {
    statistics?: UserStatistics;
    activity?: UserActivity;
}

export interface UsersPagination {
    current_page: number;
    data: UserManagement[];
    first_page_url: string;
    from: number;
    last_page: number;
    last_page_url: string;
    links: PaginationLink[];
    next_page_url: string | null;
    path: string;
    per_page: number;
    prev_page_url: string | null;
    to: number;
    total: number;
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface UserTableColumn {
    key: string;
    label: string;
    sortable?: boolean;
    sortField?: string;
    render: (user: UserManagement) => React.ReactNode;
    className?: string;
    headerClassName?: string;
}

export interface UserAction {
    key: string;
    label: string;
    icon?: string;
    onClick: () => void;
    className?: string;
    divider?: boolean;
}

export interface RoleOption {
    id: number;
    name: string;
    label: string;
}

export interface StatusOption {
    value: string;
    label: string;
}

export interface UserFilters {
    search?: string;
    status?: string;
    role?: string;
}

export interface SortFilters {
    sort?: string;
    direction?: 'asc' | 'desc';
    per_page?: number;
}

// User Invitation Types
export interface InvitationResult {
    success: boolean;
    message: string;
    name?: string;
    email?: string;
}

export interface UserInvitationFormProps {
    onSuccess?: (result: InvitationResult) => void;
    onError?: (error: InvitationResult) => void;
    className?: string;
    title?: string;
    description?: string;
    inviteRoute?: string;
    showCard?: boolean;
}

export interface InvitationErrorResponse {
    response?: {
        status?: number;
        data?: {
            message?: string;
            error?: string;
            errors?: Record<string, string[]>;
        };
    };
}
