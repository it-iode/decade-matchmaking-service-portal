import React from 'react';
import {Head} from '@inertiajs/react';
import { FrontendLayout } from '@layouts/index';
import {OCDRequestList, PaginationLinkProps, PageProps, Context} from '@/types';
import {RequestsDataTable} from '@ui/organisms/data-table/requests';
import {userColumns} from '@ui/organisms/data-table/requests';

interface RequestsPagination {
    current_page: number;
    data: OCDRequestList;
    first_page_url: string;
    from: number;
    last_page: number;
    last_page_url: string;
    links: PaginationLinkProps[];
    next_page_url: string | null;
    path: string;
    per_page: number;
    prev_page_url: string | null;
    to: number;
    total: number;
}

interface RequestsListPageProps extends PageProps<{
    requests: RequestsPagination;
    currentSort: {
        field: string;
        order: string;
    };
    listRouteName: string;
    showRouteName?: string;
    currentSearch?: Record<string, string>;
    context: Context;
    searchFields: Array<{
        name: string;
        label: string;
        type: 'text' | 'select';
        options?: Array<{value: string, label: string}>;
    }>;
    title: string;
    actions?: Array<any>;
    banner?: {
        title: string;
        description: string;
    };
    // Backward compatibility
    routeName?: string;
}> {}

export default function RequestsList({
    requests,
    currentSort,
    listRouteName,
    showRouteName,
    currentSearch = {},
    searchFields,
    context,
    title,
    banner,
    // Backward compatibility: fallback to routeName if listRouteName not provided
    routeName
}: Readonly<RequestsListPageProps>) {

    // Use listRouteName with fallback to routeName for backward compatibility
    const effectiveListRouteName = listRouteName || routeName || 'request.list';

    return (
        <FrontendLayout>
            <Head title={title || "Requests"}/>

            {/* Optional Banner */}
            {banner && (
                <div className="mb-6">
                    <h1 className="text-2xl font-bold">{banner.title}</h1>
                    <p className="text-gray-600 dark:text-gray-400">{banner.description}</p>
                </div>
            )}

            <div>
                <RequestsDataTable
                    requests={requests.data}
                    currentSort={currentSort}
                    currentSearch={currentSearch}
                    columns={userColumns}
                    routeName={effectiveListRouteName}
                    context={context}
                    pagination={{
                        current_page: requests.current_page,
                        last_page: requests.last_page,
                        links: requests.links,
                        prev_page_url: requests.prev_page_url,
                        next_page_url: requests.next_page_url,
                        from: requests.from,
                        to: requests.to,
                        total: requests.total
                    }}
                    // Dynamic search field mapping (like Opportunity List)
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
                />
            </div>
        </FrontendLayout>
    );
}
