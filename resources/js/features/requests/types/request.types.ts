import {User} from '@/types';
import {RequestOffer, RequestOfferList} from '@features/offers/types';
import {EntityAction} from '@/types/actions';

export interface OCDRequestStatus {
    id: string;
    status_label: string;
    status_code: string;
    created_at: string;
    updated_at: string;
}

/**
 * @deprecated Use actions array from EntityAction instead
 * Legacy permission structure - will be removed in future version
 */
export interface RequestPermissions {
    can_view?: boolean;
    can_delete?: boolean;
    can_edit?: boolean;
    can_manage_offers?: boolean;
    can_update_status?: boolean;
    can_accept_offer?: boolean;
    can_request_clarifications?: boolean;
    can_express_interest?: boolean;
}

export interface OCDRequest {
    id: number;
    type: string;
    submission_date: string;
    detail: {
        id: string;
        is_related_decade_action: 'Yes' | 'No';
        unique_related_decade_action_id: string;
        first_name: string;
        last_name: string;
        email: string;
        capacity_development_title: string;
        has_significant_changes: 'Yes' | 'No';
        changes_description: string;
        change_effect: string;
        request_link_type: string;
        project_stage: string;
        project_url: string;
        related_activity: 'Training' | 'Workshop' | 'Both';
        decade_challenges: {
            primary: { value: string; label: string } | null;
            secondary: { value: string; label: string } | null;
            tertiary: { value: string; label: string } | null;
        };
        support_types: Array<{ value: string; label: string }>;
        support_types_other: string;
        gap_description: string;
        has_partner: string;
        partner_name: string;
        partner_confirmed: string;
        needs_financial_support: string;
        budget_breakdown: string;
        support_months: string;
        completion_date: string;
        risks: string;
        personnel_expertise: string;
        direct_beneficiaries: string;
        direct_beneficiaries_number: string;
        expected_outcomes: string;
        success_metrics: string;
        long_term_impact: string;
        delivery_countries: Array<{ value: string; label: string }>;
        target_audience: Array<{ value: string; label: string }>;
        target_audience_other: string;
        target_languages: Array<{ value: string; label: string }>;
        target_languages_other: string;
    }
    created_at: string;
    user_id: string;
    status: OCDRequestStatus,
    user: User;
    offers?: RequestOfferList
    active_offer?: RequestOffer;
    /** @deprecated Use actions array instead */
    permissions?: RequestPermissions;
    matched_partner?: User;
    actions: EntityAction[]; // Actions from backend Action Provider Pattern
}

export type OCDRequestList = OCDRequest[];

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

export interface SubscriptionStats {
    total_subscriptions: number;
    admin_created_subscriptions: number;
    user_created_subscriptions: number;
    unique_subscribers: number;
    unique_subscribed_requests: number;
}

export interface SubscriptionFormOptions {
    users: Array<{ value: number; label: string }>;
    requests: Array<{ value: number; label: string }>;
}
