import {PaginationLinkProps, User} from "@/types/index";

export interface OpportunityPermissions {
    can_view?: boolean;
    can_edit?: boolean;
    can_apply?: boolean;
    can_extend?: boolean;
    can_delete?: boolean;
    can_approve?: boolean;
    can_reject?: boolean;
    can_close?: boolean;
}

/** Opportunity status values as the API sends them (see App\Domains\Opportunity\Enums\Status). */
export const OpportunityStatus = {
    ACTIVE: '1',
    CLOSED: '2',
    REJECTED: '3',
    PENDING_REVIEW: '4',
} as const;

export interface Opportunity {
    id: string;
    co_organizers: string[];
    title: string;
    type: { value: string; label: string };
    status: { value: string; label: string };
    closing_date: string;
    /** Null for opportunity types that do not collect it (e.g. ODC travel support). */
    coverage_activity: { value: string; label: string } | null;
    implementation_location: Array<{ value: string; label: string }>;
    thematic_areas: Array<{ value: string; label: string }>;
    thematic_areas_other: string;
    target_audience: Array<{ value: string; label: string }>;
    target_audience_other: string;
    target_languages: Array<{ value: string; label: string }>;
    target_languages_other: string;
    summary: string;
    url: string;
    apply_url: string;
    created_at: string;
    updated_at: string;
    user_id: string;
    can_edit: boolean;
    key_words: string[];
    user?: User;
    permissions?: OpportunityPermissions;
}

export type OpportunitiesList = Opportunity[];

export interface OpportunityFormOptions {
    countries?: Array<{ value: string; label: string }>;
    regions?: Array<{ value: string; label: string }>;
    oceans?: Array<{ value: string; label: string }>;
    target_audience?: Array<{ value: string; label: string }>;
    opportunity_types?: Array<{ value: string; label: string }>;
    coverage_activity?: Array<{ value: string; label: string }>;
    thematic_areas?: Array<{ value: string; label: string }>;
    target_languages?: Array<{ value: string; label: string }>;
    yes_no?: Array<{ value: string; label: string }>;
}

interface OpportunitiesPagination {
    current_page: number;
    data: Opportunity[],
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
