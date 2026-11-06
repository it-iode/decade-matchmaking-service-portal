export {
    OCDRequest, OCDRequestList, RequestPermissions, OCDRequestStatus,
    RequestSubscription, SubscriptionStats
} from "@/features/requests";
export {RequestOffer, RequestOfferList} from "@/types/offer";
export {Opportunity, OpportunitiesList, OpportunityFormOptions, OpportunitiesPagination} from "@/types/opportunity";
export type {EntityAction} from "@/types/actions";
export type {
    NotificationEntityType,
    NotificationOption,
    NotificationSettings,
    NotificationPreferencesPageProps
} from "@features/notification-preferences";
export {
    UserManagement,
    UserDetailData,
    UsersPagination,
    UserStatus,
    UserStatistics,
    UserActivity,
    UserAction,
    RoleOption,
    StatusOption,
    UserFilters,
    SortFilters
} from "@features/users";

export interface UIStep {
    label: string;
    fields: Record<string, UIField>;
}

export interface OptionDetail {
    description: string;
    htmlContent?: string;
}

export interface UIField {
    id: string;
    type: string;
    label?: string;
    description?: string;
    placeholder?: string;
    options?: { value: string | number; label: string; detail?: OptionDetail }[];
    required?: boolean;
    show?: (data: any) => boolean;  // Remove RequestForm dependency
    multiple?: boolean;
    image?: string;
    accept?: string;
    // New additions:
    disabled?: boolean;
    min?: number;
    max?: number;
    step?: number;
    pattern?: string;
    maxLength?: number;
    rows?: number;  // For textarea
    cols?: number;  // For textarea
    autoComplete?: string;
    autoFocus?: boolean;
    readOnly?: boolean;
    className?: string;
    onKeyDown?: (e: React.KeyboardEvent) => void;
    // Keywords field specific properties
    maxKeywords?: number;
    minLength?: number;  // Minimum length for individual keywords
}

export interface User {
    id: number;
    name: string;
    first_name: string;
    last_name: string;
    country: string;
    city: string;
    email: string;
    email_verified_at?: string;
    is_partner: boolean;
    is_admin: boolean;
    is_user: boolean;
}


export interface Role {
    id: number;
    name: string;
}

export interface UserWithRoles extends User {
    roles: Role[];
}


export interface Auth {
    user: User;
}

export interface NavigationItem {
    id: string;
    label: string;
    href?: string;
    route?: string;
    icon?: string;
    badge?: {
        value: number | string;
        variant?: 'primary' | 'danger' | 'warning' | 'info';
    };
    action?: 'sign-out';
    method?: 'GET' | 'POST';
    visible?: boolean;
    divider?: boolean;
}

export interface NavigationConfig {
    items: NavigationItem[];
    user: {
        displayName: string;
        avatar?: string;
    } | null;
}

export interface SharedData {
    name: string;
    auth: Auth;
    ziggy: Config & { location: string };

    unread_notifications?: number;
    navigation?: NavigationConfig;
    devTools?: {
        /** True only for administrators on local/staging, where the sample-data route exists. */
        sampleData: boolean;
    };

    [key: string]: unknown;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
    };
};

export interface PortalGuide {
    url: string;
}

export interface Document {
    id: number;
    name: string;
    path: string;
    file_type?: string;
    document_type?: {
        value: string;
        label: string;
    };
    parent_id?: number;
    parent_type?: string;
    created_at: string;
    updated_at: string;

    // Computed properties
    file_size: number;
    file_size_human: string;
    download_url: string;
    file_extension: string;

    // Relationships
    uploader?: User;

    // Actions from backend Action Provider Pattern
    actions?: EntityAction[];
}

export interface Organization {
    id: number;
    name: string;
    description: string;
    website: string;
    created_at: string;
    updated_at: string;
}

export type OrganizationsList = Organization[];

export interface IOCPlatform {
    id: number;
    name: string;
    description?: string;
    link?: string;
    contact?: string;
    created_at: string;
    updated_at: string;
}

export type IOCPlatformsList = IOCPlatform[];


export interface Notification {
    id: number;
    title: string;
    description: string;
    is_read: boolean;
    created_at: string;
}

export type NotificationList = Notification[];


export interface Settings {
    site_name: string;
    site_description: string;
    site_logo: string;
    site_favicon: string;
    contact_email: string;
    homepage_youtube_video: string;
    show_odc_travel_support_section: string;
    portal_guide: string;
    user_guide: string;
    partner_guide: string;
    organizations_csv: string;
    ioc_platforms_csv: string;
    mandrill_api_key: string;
    linkedin_client_id: string;
    linkedin_client_secret: string;
    google_client_id: string;
    google_client_secret: string;
}

export interface PaginationLinkProps {
    active: boolean;
    label: string;
    url: string;
}

export interface FlashMessages {
    success?: string;
    error?: string;
    warning?: string;
    info?: string;
}

export interface ActionButton {
    label: string;
    href: string;
    icon?: string;  // Heroicon component name
    variant?: 'primary' | 'secondary' | 'danger';
    method?: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE' | 'LINK' | 'DOWNLOAD';
    data?: Record<string, any>;  // Form data for non-GET requests
    confirm?: string;  // Confirmation message for destructive actions
}

export interface PagePropsWithFlash<
    T extends Record<string, unknown> = Record<string, unknown>,
> extends PageProps<T> {
    flash?: FlashMessages;
}

export type Context = 'admin' | 'user_own' | 'public' | 'matched' | 'subscribed';
