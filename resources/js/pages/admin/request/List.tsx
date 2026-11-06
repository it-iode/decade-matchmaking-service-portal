import React from 'react';
import {Head} from '@inertiajs/react';
import {OCDRequestList, PaginationLinkProps, OCDRequestStatus} from '@/types';
import { SidebarLayout } from '@layouts/index'
import { RequestsDataTable, adminColumns } from "@ui/organisms/data-table/requests";
import {PageHeader} from "@ui/molecules/page-header";
import {ArrowDownTrayIcon} from "@heroicons/react/16/solid";


interface RequestsPagination {
    current_page: number;
    data: OCDRequestList,
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

interface RequestsListPageProps {
    requests: RequestsPagination;
    currentSort: {
        field: string;
        order: string;
    };
    currentSearch?: Record<string, string>;
    availableStatuses: OCDRequestStatus[];
}


export default function RequestListPage({requests, currentSort, currentSearch = {}}: Readonly<RequestsListPageProps>) {
    return (
        <SidebarLayout>
            <Head title="Requests List"/>
            <PageHeader
                title="Requests List"
                subtitle="View and manage all requests submitted by users."
                actions={{
                    id: 'export',
                    label: 'Export Requests',
                    icon: ArrowDownTrayIcon,
                    href: route('admin.request.export.csv'),
                    variant: 'outline'
                }}
            />
            <div className="py-8">
                <RequestsDataTable
                    requests={requests.data}
                    currentSort={currentSort}
                    currentSearch={currentSearch}
                    columns={adminColumns}
                    routeName="admin.request.list"
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
                            label: 'Title',
                            placeholder: 'Search by request title...'
                        }
                    ]}
                    showSearch={true}
                    showActions={true}
                />
            </div>
        </SidebarLayout>
    );
}
