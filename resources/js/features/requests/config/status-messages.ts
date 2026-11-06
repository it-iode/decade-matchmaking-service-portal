import {
    CheckCircleIcon,
    ClockIcon,
    XCircleIcon,
    ExclamationTriangleIcon,
    ArchiveBoxIcon,
    EnvelopeIcon,
    DocumentMagnifyingGlassIcon,
    CogIcon,
    CheckBadgeIcon,
} from '@heroicons/react/24/outline';

export type StatusCode =
    | 'draft'
    | 'under_review'
    | 'validated'
    | 'offer_made'
    | 'in_implementation'
    | 'rejected'
    | 'unmatched'
    | 'closed';

export type BadgeColor = 'yellow' | 'blue' | 'purple' | 'green' | 'cyan' | 'red' | 'orange' | 'zinc';

export interface StatusConfig {
    color: BadgeColor;
    icon: typeof CheckCircleIcon;
    message: string;
    bgClass: string;
}

export const STATUS_MESSAGES: Record<StatusCode, StatusConfig> = {
    draft: {
        color: 'yellow',
        icon: ClockIcon,
        message: 'Your request is drafted and needs to be submitted for review.',
        bgClass: 'bg-yellow-50 dark:bg-yellow-900/10 border-yellow-200 dark:border-yellow-800',
    },
    under_review: {
        color: 'blue',
        icon: DocumentMagnifyingGlassIcon,
        message: 'Your request is currently being reviewed by the IOC Review Panel.',
        bgClass: 'bg-blue-50 dark:bg-blue-900/10 border-blue-200 dark:border-blue-800',
    },
    offer_made: {
        color: 'purple',
        icon: EnvelopeIcon,
        message: 'You have received an offer from a partner. Please review and take action.',
        bgClass: 'bg-purple-50 dark:bg-purple-900/10 border-purple-200 dark:border-purple-800',
    },
    in_implementation: {
        color: 'cyan',
        icon: CogIcon,
        message: 'Your request is currently being implemented by the matched partner.',
        bgClass: 'bg-cyan-50 dark:bg-cyan-900/10 border-cyan-200 dark:border-cyan-800',
    },
    validated: {
        color: 'green',
        icon: CheckBadgeIcon,
        message: 'Your request has been successfully completed.',
        bgClass: 'bg-green-50 dark:bg-green-900/10 border-green-200 dark:border-green-800',
    },
    rejected: {
        color: 'red',
        icon: XCircleIcon,
        message:
            'Your request was not approved for funding at this time. The IOC Review Panel has carefully reviewed your submission.',
        bgClass: 'bg-red-50 dark:bg-red-900/10 border-red-200 dark:border-red-800',
    },
    unmatched: {
        color: 'orange',
        icon: ExclamationTriangleIcon,
        message:
            'After three months of outreach, no partner was found for your request. Your submission has helped inform the CDF of training gaps.',
        bgClass: 'bg-orange-50 dark:bg-orange-900/10 border-orange-200 dark:border-orange-800',
    },
    closed: {
        color: 'zinc',
        icon: ArchiveBoxIcon,
        message: 'This request has been withdrawn.',
        bgClass: 'bg-gray-50 dark:bg-gray-900/10 border-gray-200 dark:border-gray-800',
    },
};

export function getStatusConfig(statusCode: string): StatusConfig {
    const normalizedCode = statusCode.toLowerCase() as StatusCode;
    return STATUS_MESSAGES[normalizedCode] || STATUS_MESSAGES.draft;
}
