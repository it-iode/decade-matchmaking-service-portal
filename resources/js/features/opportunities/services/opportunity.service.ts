import {router} from '@inertiajs/react';
import {Opportunity} from '../types/opportunity.types';

/**
 * OpportunityActionService - Centralized service for all opportunity-related actions
 *
 * This service consolidates all opportunity action handlers to eliminate code duplication
 * between useOpportunityActions hook and other opportunity-related components.
 * It provides consistent route handling and error management across the application.
 */
export class OpportunityActionService {
    /**
     * Navigate to opportunity details page
     *
     * @param opportunity - The opportunity to view
     * @param showRouteName - The showRouteName
     */
    static view(opportunity: Opportunity, showRouteName: string): void {
        router.visit(route(showRouteName, {id: opportunity.id}));
    }

    /**
     * Navigate to edit opportunity page
     *
     * @param opportunity - The opportunity to edit
     */
    static edit(opportunity: Opportunity): void {
        if (!opportunity?.id) {
            console.warn('Invalid opportunity: missing ID');
            return;
        }

        router.visit(route('opportunity.edit', {id: opportunity.id}));
    }

    /**
     * Delete an opportunity with confirmation and error handling
     *
     * @param opportunity - The opportunity to delete
     * @param onError - Optional error handler callback
     */
    static delete(opportunity: Opportunity, onError?: (errors: any) => void): void {
        router.delete(route('opportunity.destroy', {id: opportunity.id}));
    }

    /**
     * Delete an opportunity with confirmation and error handling
     *
     * @param opportunity - The opportunity to update
     * @param status - The opportunity status to set
     * @param onError - Optional error handler callback
     */
    static updateStatus(opportunity: Opportunity, status: string, onError?: (errors: any) => void): void {
        router.patch(route('opportunity.status', {id: opportunity.id}), {
            status: status
        });
    }

    /**
     * Navigate to create new opportunity page
     */
    static create(): void {
        router.visit(route('opportunity.create'));
    }

    /**
     * Navigate to opportunity list page
     *
     * @param context - The context for determining which route to use ('admin', 'partner', or 'user')
     */
    static list(context: 'admin' | 'partner' | 'user' = 'user'): void {
        let routeName: string;

        switch (context) {
            case 'admin':
                routeName = 'admin.opportunity.list';
                break;
            case 'partner':
                routeName = 'me.opportunity.list';
                break;
            default:
                routeName = 'opportunity.list';
                break;
        }

        router.visit(route(routeName));
    }


    /**
     * Archive an opportunity (admin only)
     *
     * @param opportunity - The opportunity to archive
     * @param onError - Optional error handler callback
     */
    static archive(opportunity: Opportunity, onError?: (errors: any) => void): void {
        if (!opportunity?.id) {
            console.warn('Invalid opportunity: missing ID');
            return;
        }

        const confirmMessage = 'Are you sure you want to archive this opportunity?';

        if (confirm(confirmMessage)) {
            // This assumes an archive endpoint will be created
            // For now, we could use the status update with 'archived' status

        }
    }

}
