import {router} from '@inertiajs/react';
import {OCDRequest} from '../types/request.types';

/**
 * RequestActionService - Centralized service for all request-related actions
 *
 * This service consolidates all request action handlers to eliminate code duplication
 * between useRequestActions hook and RequestShowActionButtons component.
 * It provides consistent route handling and error management across the application.
 */
export class RequestActionService {
    /**
     * Accept an active offer for a request
     */
    static acceptOffer(request: OCDRequest): void {
        if (!request.active_offer) {
            console.warn('No active offer available to accept');
            return;
        }

        router.post(route('offer.accept', {
            id: request.active_offer.id,
        }));
    }

    /**
     * Request clarifications for an active offer
     */
    static requestClarifications(request: OCDRequest): void {
        if (!request.active_offer) {
            console.warn('No active offer available for clarification request');
            return;
        }

        router.post(route('offer.clarification-request', {
            id: request.active_offer.id
        }));
    }

    /**
     * Navigate to edit request page
     */
    static edit(request: OCDRequest): void {
        router.visit(route('request.edit', {id: request.id}));
    }

    /**
     * Delete a request with confirmation and error handling
     */
    static delete(request: OCDRequest, onError?: (errors: unknown) => void): void {
        const confirmMessage = 'Are you sure you want to delete this request? This action cannot be undone.';

        if (confirm(confirmMessage)) {
            router.delete(route('user.request.destroy', {id: request.id}), {
                onError: (errors) => {
                    console.error('Error deleting request:', errors);
                    if (onError) {
                        onError(errors);
                    }
                }
            });
        }
    }

    /**
     * Navigate to request details page
     */
    static viewDetails(request: OCDRequest, context: 'admin' | 'user' = 'user'): void {
        const routeName = context === 'admin' ? 'admin.request.show' : 'request.show';
        router.visit(route(routeName, {id: request.id}));
    }

    /**
     * Navigate to manage offers page (admin only)
     */
    static manageOffers(request: OCDRequest): void {
        router.visit(route('admin.offer.list', {request: request.id}));
    }

    /**
     * Navigate to view all offers page
     */
    static viewOffers(request: OCDRequest): void {
        // Note: This route might need to be created if it doesn't exist
        // For now, using the same route as manageOffers
        router.visit(route('admin.offer.list', {id: request.id}));
    }

    /**
     * Export request as PDF
     */
    static exportPdf(request: OCDRequest): void {
        window.open(route('request.pdf', {id: request.id}), '_blank');
    }

    /**
     * Navigate to add offer page (admin only)
     */
    static addOffer(request: OCDRequest): void {
        router.visit(route('admin.offer.create', {request_id: request.id}));
    }

    /**
     * Update request status (requires status dialog management)
     * This is a specialized action that requires additional state management
     * and should be handled by the calling component
     */
    static updateStatus(request: OCDRequest, onStatusUpdate: (request: OCDRequest, statuses?: unknown[]) => void, availableStatuses?: unknown[]): void {
        onStatusUpdate(request, availableStatuses);
    }

    static expressInterest(request: OCDRequest): void {
        router.post(route('request.express.interest', {
            id: request.id
        }));
    }
}
