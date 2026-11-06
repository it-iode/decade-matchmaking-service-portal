import {UIField} from '@/types';

export interface Offer {
    description: string;
    partner_id: string;
    document: File | null;

    [key: string]: any;
}

export interface UIStep {
    label: string;
    fields: Record<string, UIField>;
}

export const offerFormFields: UIStep[] = [
    {
        label: 'Offer Details',
        fields: {
            request_id: {
                id: 'request_id',
                type: 'select',
                required: true,
                label: 'Request',
                description: 'Select the request you want to make an offer for',
                placeholder: 'Select a request',
                options: [], // Will be filled dynamically in FieldRenderer
            },
            partner_id: {
                id: 'partner_id',
                type: 'select',
                required: true,
                label: 'Partner',
                description: 'Select a partner from the list',
                placeholder: 'Select a partner',
                options: [], // Will be filled dynamically in FieldRenderer
            },
            description: {
                id: 'description',
                type: 'textarea',
                required: true,
                label: 'Offer Description',
                description: 'Provide a detailed description of your offer including what you can provide, timeline, and any conditions',
                placeholder: 'Describe your capacity development offer in detail...',
            },
            document: {
                id: 'document',
                type: 'file',
                required: false,
                label: 'Supporting Document',
                description: 'Upload a supporting document for your offer (PDF only, max 10MB)',
                accept: 'application/pdf',
            },
        },
    },
];
