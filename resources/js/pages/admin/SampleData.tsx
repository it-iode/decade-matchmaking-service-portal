import React from 'react';
import { Head } from '@inertiajs/react';
import { SidebarLayout } from '@layouts/index';
import { PageHeader } from '@ui/molecules/page-header';
import {
    SampleDataPlanTable,
    SampleDataRunForm,
    SampleDataSummaryPanel,
    type SampleDataPlan,
    type SampleDataSummary,
} from '@features/sample-data';

interface SampleDataPageProps {
    title: string;
    environment: string;
    plan: SampleDataPlan;
    summary: SampleDataSummary | null;
}

/**
 * Admin developer tool (local / staging only): reset the database to a fake
 * dataset and show what was created.
 */
export default function SampleData({ title, environment, plan, summary }: Readonly<SampleDataPageProps>) {
    return (
        <SidebarLayout>
            <Head title={title} />
            <PageHeader
                title={title}
                subtitle="Erase all non-protected data and replace it with a generated dataset of users, opportunities and requests."
            />

            <div className="space-y-10">
                {summary && <SampleDataSummaryPanel summary={summary} />}

                <SampleDataPlanTable plan={plan} />

                <SampleDataRunForm environment={environment} protectedEmails={plan.protected_emails} />
            </div>
        </SidebarLayout>
    );
}
