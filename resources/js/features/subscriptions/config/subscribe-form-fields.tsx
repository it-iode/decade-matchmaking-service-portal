import { UIStep } from '@/types';

export const subscribeFormFields: UIStep[] = [
    {
        label: 'Subscribe User to Request',
        fields: {
            user_id: {
                id: 'user_id',
                type: 'select',
                required: true,
                label: 'User',
                placeholder: 'Search users by name or email...',
                description: 'Select a user to subscribe to the request',
                // options will be provided by parent component from page props
            },
            request_id: {
                id: 'request_id',
                type: 'select',
                required: true,
                label: 'Request',
                placeholder: 'Search requests by title or requester...',
                description: 'Select a capacity development request',
                // options will be provided by parent component from page props
            },
        },
    },
];
