import React from 'react';
import { ChevronDownIcon, ChevronUpIcon } from '@heroicons/react/16/solid';
import { RequestSubscription, SubscriptionsList } from '@features/subscriptions/types';
import { PaginationLinkProps, UIField } from '@/types';
import { Table, TableHead, TableBody, TableRow, TableHeader, TableCell } from '@ui/primitives/table';
import { router } from '@inertiajs/react';
import { TablePaginationNav } from '@ui/molecules';
import { TableSearch } from '@ui/organisms/data-table/search';
import { DropdownActions, Action } from '@ui/organisms/data-table/common';

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

type SortField = 'id' | 'user_name' | 'request_title' | 'created_at';

interface TableColumn {
    key: string;
    label: string;
    sortable?: boolean;
    sortField?: SortField;
    render: (subscription: RequestSubscription) => React.ReactNode;
    className?: string;
    headerClassName?: string;
}

interface SubscriptionsDataTableProps {
    subscriptions: SubscriptionsList;
    currentSort: {
        field: string;
        order: string;
    };
    currentSearch?: Record<string, string>;
    pagination?: PaginationData;
    searchFields?: UIField[];
    columns: TableColumn[];
    routeName?: string;
    getActionsForSubscription: (subscription: RequestSubscription) => Action[];
    showSearch?: boolean;
    showActions?: boolean;
}

export function SubscriptionsDataTable({
    subscriptions,
    currentSort,
    currentSearch = {},
    pagination,
    searchFields = [],
    columns,
    routeName = 'admin.subscriptions.index',
    getActionsForSubscription,
    showSearch = true,
    showActions = true,
}: Readonly<SubscriptionsDataTableProps>) {

    const handleSort = (field: SortField) => {
        const newOrder = currentSort.field === field && currentSort.order === 'asc' ? 'desc' : 'asc';
        router.get(route(routeName), {
            sort: field,
            order: newOrder,
            ...currentSearch,
        }, {
            preserveState: false,
            preserveScroll: true,
        });
    };

    const getSortIcon = (field: SortField) => {
        if (currentSort.field !== field) {
            return <ChevronDownIcon className="size-4 opacity-50" />;
        }
        return currentSort.order === 'asc'
            ? <ChevronUpIcon className="size-4" />
            : <ChevronDownIcon className="size-4" />;
    };

    const totalColumns = columns.length + (showActions ? 1 : 0);

    return (
        <>
            {showSearch && searchFields.length > 0 && (
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
                    {subscriptions.length === 0 ? (
                        <TableRow>
                            <TableCell colSpan={totalColumns} className="text-center text-zinc-500 py-8">
                                No subscriptions found.
                            </TableCell>
                        </TableRow>
                    ) : (
                        subscriptions.map((subscription: RequestSubscription) => {
                            const actions = getActionsForSubscription(subscription);
                            return (
                                <TableRow key={subscription.id}>
                                    {columns.map((column) => (
                                        <TableCell key={column.key} className={column.className}>
                                            {column.render(subscription)}
                                        </TableCell>
                                    ))}
                                    {showActions && (
                                        <TableCell className="text-right">
                                            {actions.length > 0 ? (
                                                <DropdownActions actions={actions} />
                                            ) : (
                                                <span className="text-xs text-zinc-400">-</span>
                                            )}
                                        </TableCell>
                                    )}
                                </TableRow>
                            );
                        })
                    )}
                </TableBody>
            </Table>
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
