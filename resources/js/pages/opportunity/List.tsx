import React from 'react';
import {Head} from '@inertiajs/react';
import { FrontendLayout } from '@layouts/index';
import {PageProps, OpportunitiesPagination} from '@/types';
import {OpportunitiesDataTable} from '@ui/organisms/data-table/opportunities';
import {partnerColumns} from '@ui/organisms/data-table/opportunities';
import {useOpportunityActions} from '@features/opportunities/hooks';

interface OpportunitiesListPageProps extends PageProps<{
    opportunities: OpportunitiesPagination;
    currentSort: {
        field: string;
        order: string;
    };
    listRouteName: string;
    showRouteName: string;
    currentSearch?: Record<string, string>;
    context: 'user_own' | 'admin' | 'public';
    searchFields: Array<{
        name: string;
        label: string;
        type: 'text' | 'select';
        options?: Array<{value: string, label: string}>;
    }>;
}> {
}

export default function OpportunitiesListPage({
                                                  opportunities,
                                                  currentSort,
                                                  listRouteName,
                                                  showRouteName,
                                                  currentSearch = {},
                                                  searchFields,
                                                  context,
                                                  auth
                                              }: Readonly<OpportunitiesListPageProps>) {

    const {
        getActionsForOpportunity,
    } = useOpportunityActions(context, showRouteName);
    return (
        <FrontendLayout>
            <Head title="My Opportunities"/>
            <OpportunitiesDataTable
                opportunities={opportunities.data}
                currentSort={currentSort}
                currentSearch={currentSearch}
                columns={partnerColumns}
                routeName={listRouteName}
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
                searchFields={searchFields.map(field => ({
                    id: field.name,
                    type: field.type,
                    label: field.label,
                    placeholder: field.type === 'text'
                        ? `Search by ${field.label.toLowerCase()}...`
                        : `Filter by ${field.label.toLowerCase()}...`,
                    options: field.options || []
                }))}
                showSearch={true}
                showActions={true}
                context={context}
            />

        </FrontendLayout>
    )
}
