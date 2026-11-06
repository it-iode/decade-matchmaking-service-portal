import React from 'react';
import { Subheading } from '@ui/primitives/heading';
import { Text } from '@ui/primitives/text';
import { EntityCountsTable } from './entity-counts-table';
import type { SampleDataPlan } from '../types';

interface SampleDataPlanProps {
    plan: SampleDataPlan;
}

/**
 * Dry-run view of the purge: what is kept (protected accounts and everything
 * related to them) versus what will be erased.
 */
export function SampleDataPlanTable({ plan }: Readonly<SampleDataPlanProps>) {
    return (
        <section aria-labelledby="sample-data-plan-heading" className="space-y-3">
            <Subheading id="sample-data-plan-heading" level={2}>
                Current database
            </Subheading>
            <Text>
                Rows linked to the protected accounts are kept. Everything else in these tables (and their
                subscriptions, clicks, notifications, settings, invitations, email logs and sessions) is erased.
            </Text>
            <EntityCountsTable kept={plan.keep} erased={plan.delete} />
        </section>
    );
}
