/**
 * Mirrors the arrays returned by Database\Seeders\SampleDataSeeder::plan() / execute().
 */

export type SampleDataEntityCounts = {
    users: number;
    requests: number;
    offers: number;
    opportunities: number;
    documents: number;
};

export type SampleDataPlan = {
    keep: SampleDataEntityCounts;
    delete: SampleDataEntityCounts;
    protected_emails: string[];
};

export type SampleDataCreatedCounts = {
    admins: number;
    partners: number;
    users: number;
    opportunities: number;
    requests: number;
    offers: number;
    subscriptions: number;
    notification_settings: number;
    notifications: number;
};

export type SampleDataCredentials = {
    domain: string;
    password: string;
    logins: string[];
};

export type SampleDataSummary = {
    environment: string;
    protected_emails: string[];
    protected: SampleDataEntityCounts;
    deleted: SampleDataEntityCounts;
    created: SampleDataCreatedCounts;
    credentials: SampleDataCredentials;
    duration_seconds: number;
};

export const SAMPLE_DATA_CONFIRMATION_PHRASE = 'RESET';

export const ENTITY_LABELS: Record<keyof SampleDataEntityCounts, string> = {
    users: 'Users',
    requests: 'Requests',
    offers: 'Offers',
    opportunities: 'Opportunities',
    documents: 'Documents',
};
