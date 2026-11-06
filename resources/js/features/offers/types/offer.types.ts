import {Document, OCDRequest, User} from "@/types/index";
import {EntityAction} from '@/types/actions';

export interface RequestOffer {
    id: number;
    description: string;
    matched_partner_id: number;
    status: { value: string; label: string };
    created_at: string;
    updated_at: string;
    is_accepted: boolean;
    documents?: Document[];
    request: OCDRequest;
    matched_partner?: User;
    actions: EntityAction[];
}

export type RequestOfferList = RequestOffer[];
