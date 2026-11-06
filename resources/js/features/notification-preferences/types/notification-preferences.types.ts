import { PageProps } from '@/types/index';

/**
 * Notification entity types supported by the toggle-based preference system.
 */
export type NotificationEntityType = 'opportunity' | 'request';

/**
 * A single togglable notification option (one taxonomy value).
 */
export interface NotificationOption {
    value: string;
    label: string;
    enabled: boolean;
}

/**
 * Opt-out notification settings returned by the backend.
 *
 * - `opportunity` is always present (all users).
 * - `request` is present only for partners; `null` otherwise.
 */
export interface NotificationSettings {
    master_enabled: boolean;
    opportunity: NotificationOption[];
    request: NotificationOption[] | null;
}

/**
 * Page props for the notification preferences List page.
 */
export interface NotificationPreferencesPageProps extends PageProps {
    settings: NotificationSettings;
    title?: string;
    banner?: {
        title: string;
        description: string;
        image?: string;
    };
}

// Entity type constants
export const NOTIFICATION_ENTITY_TYPES = {
    REQUEST: 'request' as const,
    OPPORTUNITY: 'opportunity' as const,
} as const;
