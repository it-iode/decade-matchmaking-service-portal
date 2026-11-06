import React from 'react';
import { Head } from '@inertiajs/react';
import { PlusIcon } from '@heroicons/react/16/solid';
import { SidebarLayout } from '@layouts/index';
import { PageHeader } from '@ui/molecules/page-header';
import { Divider } from '@ui/primitives/divider';
import { SubscriptionsDataTable, subscriptionColumns } from '@ui/organisms/data-table/subscriptions';
import {
    SubscriptionStatsCards,
    CreateSubscriptionDialog,
    useSubscriptionActions,
    SubscriptionsPagination,
    SubscriptionStats,
    SubscriptionFormOptions,
} from '@features/subscriptions';
import { PageProps, UIField } from '@/types';

interface AdminSubscriptionsIndexProps extends PageProps {
    subscriptions: SubscriptionsPagination;
    stats: SubscriptionStats;
    requests: SubscriptionFormOptions['requests'];
    searchFields?: UIField[];
    currentSort?: {
        field: string;
        order: string;
    };
    currentSearch?: Record<string, string>;
}

export default function AdminSubscriptionsIndex({
    subscriptions,
    stats,
    requests,
    searchFields = [],
    currentSort = { field: 'created_at', order: 'desc' },
    currentSearch = {},
}: AdminSubscriptionsIndexProps) {
    const {
        isCreateDialogOpen,
        openCreateDialog,
        closeCreateDialog,
        getActionsForSubscription,
    } = useSubscriptionActions();

    return (
        <SidebarLayout>
            <Head title="Subscription Management" />

            <div className="px-4 py-8 sm:px-6 lg:px-8">
                <PageHeader
                    title="Subscription Management"
                    subtitle="Manage user subscriptions to capacity development requests."
                    actions={{
                        id: 'subscribe',
                        label: 'Subscribe User',
                        icon: PlusIcon,
                        onClick: openCreateDialog
                    }}
                    layout="stacked"
                />

                <SubscriptionStatsCards stats={stats} className="mt-8" />

                <Divider className="my-8" />

                <div className="mt-8">
                    <SubscriptionsDataTable
                        subscriptions={subscriptions.data}
                        currentSort={currentSort}
                        currentSearch={currentSearch}
                        columns={subscriptionColumns}
                        routeName="admin.subscriptions.index"
                        getActionsForSubscription={getActionsForSubscription}
                        pagination={{
                            current_page: subscriptions.current_page,
                            last_page: subscriptions.last_page,
                            links: subscriptions.links,
                            prev_page_url: subscriptions.prev_page_url,
                            next_page_url: subscriptions.next_page_url,
                            from: subscriptions.from,
                            to: subscriptions.to,
                            total: subscriptions.total,
                        }}
                        searchFields={searchFields}
                        showSearch={searchFields.length > 0}
                        showActions={true}
                    />
                </div>

                <CreateSubscriptionDialog
                    isOpen={isCreateDialogOpen}
                    onClose={closeCreateDialog}
                    requests={requests}
                />
            </div>
        </SidebarLayout>
    );
}
