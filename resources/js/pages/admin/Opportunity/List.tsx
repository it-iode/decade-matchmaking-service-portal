import React from 'react';
import {Head} from '@inertiajs/react';
import {OpportunitiesPagination , Context} from '@/types';
import {SidebarLayout} from '@layouts/index'
import {OpportunitiesDataTable, adminColumns} from "@ui/organisms/data-table/opportunities";
import {PageHeader} from "@ui/molecules/page-header";
import {useOpportunityActions} from '@features/opportunities/hooks';


interface OpportunitiesListPageProps {
    opportunities: OpportunitiesPagination;
    currentSort: {
        field: string;
        order: string;
    };
    currentSearch?: Record<string, string>;
    context: Context,
    showRouteName: string

}

export default function OpportunityListPage({
                                                opportunities,
                                                currentSort,
                                                currentSearch,
                                                context,
                                                showRouteName
                                            }: Readonly<OpportunitiesListPageProps>) {
    const {
        getActionsForOpportunity,
    } = useOpportunityActions(context, showRouteName);

    return (
        <SidebarLayout>
            <Head title="Opportunities List"/>
            <PageHeader title="Opportunities List" />
            <div className="py-8">
                <OpportunitiesDataTable
                    opportunities={opportunities.data}
                    currentSort={currentSort}
                    currentSearch={currentSearch}
                    columns={adminColumns}
                    routeName="admin.opportunity.list"
                    getActionsForOpportunity={getActionsForOpportunity}
                    pagination={{
                        current_page: opportunities.current_page,
                        last_page: opportunities.last_page,
                        links: opportunities.links,
                        prev_page_url: opportunities.prev_page_url,
                        next_page_url: opportunities.next_page_url,
                        from: opportunities.from,
                        to: opportunities.to,
                        total: opportunities.total
                    }}
                    searchFields={[
                        {
                            id: 'user',
                            type: 'text',
                            label: 'Submitted By',
                            placeholder: 'Search by user name...'
                        },
                        {
                            id: 'title',
                            type: 'text',
                            label: 'Title or keywords',
                            placeholder: 'Search by title or keywords...'
                        }
                    ]}
                    showSearch={true}
                    showActions={true}
                    context={context}
                />
            </div>

        </SidebarLayout>
    );
}
