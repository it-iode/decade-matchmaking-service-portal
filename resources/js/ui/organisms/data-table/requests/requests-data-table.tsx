import React from 'react';
import {ChevronDownIcon, ChevronUpIcon} from '@heroicons/react/16/solid';
import {OCDRequest, OCDRequestList, PaginationLinkProps, OCDRequestStatus, UIField, Context} from "@/types";
import {Table, TableHead, TableBody, TableRow, TableHeader, TableCell} from '@ui/primitives/table';
import {Badge} from '@ui/primitives/badge'
import {TablePaginationNav} from '@ui/molecules';
import {router} from '@inertiajs/react';
import {TableSearch} from '@ui/organisms/data-table/search';
import { RequestDropdownActions } from '@features/requests/components/request-dropdown-actions';
// Types and Interfaces
interface PaginationData {
    current_page: number;
    last_page: number;
    links: PaginationLinkProps[];
    prev_page_url: string | null;
    next_page_url: string | null;
    from: number;
    to: number;
    total: number;
}

interface TableColumn {
    key: string;
    label: string;
    sortable?: boolean;
    sortField?: string;
    render: (request: OCDRequest) => React.ReactNode;
    className?: string;
    headerClassName?: string;
}

interface RequestsDataTableProps {
    requests: OCDRequestList;
    currentSort: {
        field: string;
        order: string;
    };
    currentSearch?: Record<string, string>;
    pagination?: PaginationData;
    searchFields?: UIField[];
    columns: TableColumn[];
    routeName?: string;
    context?: Context;
    showSearch?: boolean;
    showActions?: boolean;
}

// Utility Functions
const statusBadgeRenderer = (status: OCDRequestStatus) => {
    let color: "teal" | "cyan" | "amber" | "green" | "blue" | "red" | "orange" | "yellow" | "lime" | "emerald" | "sky" | "indigo" | "violet" | "purple" | "fuchsia" | "pink" | "rose" | "zinc" | undefined;
    switch (status.status_code) {
        case 'draft':
            color = 'zinc';
            break;
        case 'under_review':
            color = 'amber';
            break;
        case 'validated':
            color = 'green';
            break;
        case 'offer_made':
            color = 'blue';
            break;
        case 'in_implementation':
            color = 'blue';
            break;
        case 'rejected':
        case 'unmatched':
            color = 'red';
            break;
        case 'closed':
            color = "teal";
            break;
    }

    return (
        <Badge color={color}>{status.status_label}</Badge>
    );
};

export function RequestsDataTable({
                                      requests,
                                      currentSort,
                                      currentSearch = {},
                                      pagination,
                                      searchFields = [],
                                      columns,
                                      routeName = 'admin.request.list',
                                      context = 'public',
                                      showSearch = true,
                                      showActions = true
                                  }: Readonly<RequestsDataTableProps>) {

    const handleSort = (field: string) => {
        const newOrder = currentSort.field === field && currentSort.order === 'asc' ? 'desc' : 'asc';
        router.get(route(routeName), {
            sort: field,
            order: newOrder
        }, {
            preserveState: false,
            preserveScroll: true
        });
    };


    // Helper Functions
    const getSortIcon = (field: string) => {
        if (currentSort.field !== field) {
            return <ChevronDownIcon className="size-4 opacity-50"/>;
        }
        return currentSort.order === 'asc'
            ? <ChevronUpIcon className="size-4"/>
            : <ChevronDownIcon className="size-4"/>;
    };
    const totalColumns = columns.length + (showActions ? 1 : 0);

    return (
        <>
            {showSearch && (
                <TableSearch
                    searchFields={searchFields}
                    routeName={routeName}
                    currentSearch={currentSearch}
                    preserveSort={true}
                />
            )}

            <Table className="[&_td]:whitespace-normal [&_td]:break-words [&_th]:whitespace-nowrap">
                <TableHead className="text-lg">
                    <TableRow>
                        {columns.map((column) => (
                            <TableHeader key={column.key} className={column.headerClassName}>
                                {column.sortable && column.sortField ? (
                                    <button
                                        onClick={() => handleSort(column.sortField!)}
                                        className="flex items-center gap-1 font-semibold hover:text-gray-600 dark:hover:text-gray-300"
                                    >
                                        {column.label}
                                        {getSortIcon(column.sortField)}
                                    </button>
                                ) : (
                                    column.label
                                )}
                            </TableHeader>
                        ))}
                        {showActions && (
                            <TableHeader className="text-right">
                            </TableHeader>
                        )}
                    </TableRow>
                </TableHead>
                <TableBody className="text-lg">
                    {requests.length === 0 ? (
                        <TableRow>
                            <TableCell colSpan={totalColumns} className="text-center text-zinc-500 py-8">
                                No requests found.
                            </TableCell>
                        </TableRow>
                    ) : (
                        requests.map((request: OCDRequest) => (
                            <TableRow key={request.id}>
                                {columns.map((column) => (
                                    <TableCell key={column.key} className={column.className}>
                                        {column.render(request)}
                                    </TableCell>
                                ))}
                                {showActions && (
                                    <TableCell className="text-right">
                                        <RequestDropdownActions request={request} context={context} />
                                    </TableCell>
                                )}
                            </TableRow>
                        ))
                    )}
                </TableBody>
            </Table>

            {/* Pagination */}
            {pagination && (
                <TablePaginationNav
                    links={pagination.links}
                    prevPageUrl={pagination.prev_page_url}
                    nextPageUrl={pagination.next_page_url}
                    from={pagination.from}
                    to={pagination.to}
                    total={pagination.total}
                />
            )}
        </>
    );
}
