/**
 * TypeScript definitions for the Unsubscribe feature
 */

import { User, PageProps } from '@/types';

/**
 * Props for the Unsubscribe page component
 */
export interface UnsubscribePageProps extends PageProps {
    /**
     * The authenticated user who is unsubscribing
     */
    user: User;

    /**
     * Optional security token for email-based unsubscribe links
     */
    token?: string;

    /**
     * Optional message to display (e.g., for already unsubscribed users)
     */
    message?: string;
}

/**
 * Form data for unsubscribe confirmation
 */
export interface UnsubscribeFormData {
    /**
     * Security token to validate the unsubscribe request
     */
    token: string;
}

/**
 * Response from the unsubscribe API endpoint
 */
export interface UnsubscribeResponse {
    /**
     * Whether the unsubscribe was successful
     */
    success: boolean;

    /**
     * Response message
     */
    message: string;

    /**
     * Any errors that occurred
     */
    errors?: Record<string, string[]>;
}