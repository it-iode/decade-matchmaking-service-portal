import React from 'react';
import { Head } from '@inertiajs/react';
import { InvitationsPagination, InvitationStatistics, useInvitationActions } from '@/features/invitations';
import { UIField } from '@/types';
import { SidebarLayout } from '@layouts/index';
import { InvitationsDataTable, invitationColumns } from '@ui/organisms/data-table/invitations';
import { PageHeader } from '@ui/molecules/page-header';

interface InvitationsListPageProps {
    invitations: InvitationsPagination;
    statistics: InvitationStatistics;
    searchFields: UIField[];
    currentSort: {
        field: string;
        order: string;
    };
    currentSearch?: Record<string, string>;
}

export default function InvitationListPage({
    invitations,
    statistics,
    searchFields,
    currentSort,
    currentSearch,
}: Readonly<InvitationsListPageProps>) {
    const { getActionsForInvitation } = useInvitationActions();

    return (
        <SidebarLayout>
            <Head title="User Invitations" />
            <PageHeader title="User Invitations" />

            {/* Statistics Cards */}
            <div className="grid grid-cols-2 gap-4 sm:grid-cols-4 mb-6">
                <div className="bg-white dark:bg-zinc-800 rounded-lg p-4 shadow-sm border border-zinc-200 dark:border-zinc-700">
                    <p className="text-sm font-medium text-zinc-500 dark:text-zinc-400">Total</p>
                    <p className="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">{statistics.total}</p>
                </div>
                <div className="bg-white dark:bg-zinc-800 rounded-lg p-4 shadow-sm border border-zinc-200 dark:border-zinc-700">
                    <p className="text-sm font-medium text-amber-600 dark:text-amber-400">Pending</p>
                    <p className="text-2xl font-semibold text-amber-600 dark:text-amber-400">{statistics.pending}</p>
                </div>
                <div className="bg-white dark:bg-zinc-800 rounded-lg p-4 shadow-sm border border-zinc-200 dark:border-zinc-700">
                    <p className="text-sm font-medium text-green-600 dark:text-green-400">Accepted</p>
                    <p className="text-2xl font-semibold text-green-600 dark:text-green-400">{statistics.accepted}</p>
                </div>
                <div className="bg-white dark:bg-zinc-800 rounded-lg p-4 shadow-sm border border-zinc-200 dark:border-zinc-700">
                    <p className="text-sm font-medium text-zinc-500 dark:text-zinc-400">Expired</p>
                    <p className="text-2xl font-semibold text-zinc-500 dark:text-zinc-400">{statistics.expired}</p>
                </div>
            </div>

            <div className="py-4">
                <InvitationsDataTable
                    invitations={invitations.data}
                    currentSort={currentSort}
                    currentSearch={currentSearch}
                    columns={invitationColumns}
                    routeName="admin.invitations.index"
                    getActionsForInvitation={getActionsForInvitation}
                    pagination={{
                        current_page: invitations.current_page,
                        last_page: invitations.last_page,
                        links: invitations.links,
                        prev_page_url: invitations.prev_page_url,
                        next_page_url: invitations.next_page_url,
                        from: invitations.from,
                        to: invitations.to,
                        total: invitations.total,
                    }}
                    searchFields={searchFields}
                    showSearch={true}
                    showActions={true}
                />
            </div>
        </SidebarLayout>
    );
}
