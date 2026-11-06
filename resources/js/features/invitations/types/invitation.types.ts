import { PaginationLinkProps } from '@/types';

export interface InvitationStatus {
    value: 'pending' | 'accepted' | 'expired';
    label: string;
    color: string;
}

export interface InvitationInviter {
    id: number;
    name: string;
    email: string;
}

export interface Invitation {
    id: number;
    name: string;
    email: string;
    status: InvitationStatus;
    inviter: InvitationInviter | null;
    expires_at: string;
    accepted_at: string | null;
    created_at: string;
    is_resendable: boolean;
    is_cancellable: boolean;
}

export interface InvitationsPagination {
    current_page: number;
    data: Invitation[];
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

export interface InvitationStatistics {
    total: number;
    pending: number;
    accepted: number;
    expired: number;
}

export type InvitationsList = Invitation[];
