import React from 'react';
import { Badge } from '@ui/primitives/badge';
import { Opportunity, OCDRequest, RequestOffer } from '@/types';
import { OFFER_STATUS_COLORS, DEFAULT_OFFER_STATUS_COLOR } from '@shared/constants';

// Type for Badge colors (matching the Badge component)
type BadgeColor = "teal" | "cyan" | "amber" | "green" | "blue" | "red" | 
                  "orange" | "yellow" | "lime" | "emerald" | "sky" | 
                  "indigo" | "violet" | "purple" | "fuchsia" | "pink" | 
                  "rose" | "zinc";

// Opportunity status color mapping
const OPPORTUNITY_STATUS_COLORS: Record<string, BadgeColor> = {
    "1": 'green',      // ACTIVE
    "2": 'zinc',       // CLOSED
    "3": 'red',        // REJECTED
    "4": 'amber',      // PENDING_REVIEW
};

// Request status color mapping
const REQUEST_STATUS_COLORS: Record<string, BadgeColor> = {
    'draft': 'zinc',
    'under_review': 'amber',
    'validated': 'green',
    'offer_made': 'blue',
    'in_implementation': 'blue',
    'rejected': 'red',
    'unmatched': 'red',
    'closed': 'teal',
};

// Generic status badge renderer factory
export function createStatusBadgeRenderer<T>(
    getValue: (item: T) => string,
    getLabel: (item: T) => string,
    colorMap: Record<string, BadgeColor>,
    defaultColor: BadgeColor = 'zinc'
) {
    return (item: T) => {
        const value = getValue(item);
        const label = getLabel(item);
        const color = colorMap[value] || defaultColor;
        return <Badge color={color}>{label}</Badge>;
    };
}

// Pre-configured renderer for Opportunities
export const opportunityStatusBadgeRenderer = (opportunity: Opportunity) => {
    const color = OPPORTUNITY_STATUS_COLORS[opportunity.status.value] || 'zinc';
    return <Badge color={color}>{opportunity.status.label}</Badge>;
};

// Pre-configured renderer for Requests
export const requestStatusBadgeRenderer = (status: OCDRequest['status']) => {
    const color = REQUEST_STATUS_COLORS[status.status_code] || 'zinc';
    return <Badge color={color}>{status.status_label}</Badge>;
};

// Pre-configured renderer for Offers
export const offerStatusBadgeRenderer = (offer: RequestOffer) => {
    const color = OFFER_STATUS_COLORS[offer.status.value] || DEFAULT_OFFER_STATUS_COLOR;
    return <Badge color={color}>{offer.status.label}</Badge>;
};

// Export color maps for potential reuse
export { OPPORTUNITY_STATUS_COLORS, REQUEST_STATUS_COLORS, OFFER_STATUS_COLORS };