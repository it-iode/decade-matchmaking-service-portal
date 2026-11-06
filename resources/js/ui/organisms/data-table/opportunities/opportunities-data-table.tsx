import React from 'react';
import {ChevronDownIcon, ChevronUpIcon} from '@heroicons/react/16/solid';
import {Opportunity, OpportunitiesList, PaginationLinkProps, UIField, Context} from "@/types";
import {Table, TableHead, TableBody, TableRow, TableHeader, TableCell} from '@ui/primitives/table';
import {router} from '@inertiajs/react';
import {TablePaginationNav} from '@ui/molecules';
import {TableSearch} from '@ui/organisms/data-table/search';
import {DropdownActions, Action} from '@ui/organisms/data-table/common';

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

type SortField = 'id' | 'title' | 'type' | 'closing_date' | 'status' | 'created_at';

interface TableColumn {
    key: string;
    label: string;
    sortable?: boolean;
    sortField?: SortField;
    render: (opportunity: Opportunity) => React.ReactNode;
    className?: string;
    headerClassName?: string;
}

interface OpportunitiesDataTableProps {
    opportunities: OpportunitiesList;
    currentSort: {
        field: string;
        order: string;
    };
    currentSearch?: Record<string, string>;
    pagination?: PaginationData;
    searchFields?: UIField[];
    columns: TableColumn[];
    routeName?: string;
    getActionsForOpportunity: (context: Context, opportunity: Opportunity) => Action[];
    showSearch?: boolean;
    showActions?: boolean;
    context: Context;
}

export function OpportunitiesDataTable({
                                           opportunities,
                                           currentSort,
                                           currentSearch = {},
                                           pagination,
                                           searchFields = [],
                                           columns,
                                           routeName = 'admin.opportunity.list',
                                           getActionsForOpportunity,
                                           showSearch = true,
                                           showActions = true,
                                           context
                                       }: Readonly<OpportunitiesDataTableProps>) {

    const handleSort = (field: SortField) => {
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
    const getSortIcon = (field: SortField) => {
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
                    {opportunities.length === 0 ? (
                        <TableRow>
                            <TableCell colSpan={totalColumns} className="text-center text-zinc-500 py-8">
                                No opportunities found.
                            </TableCell>
                        </TableRow>
                    ) : (
                        opportunities.map((opportunity: Opportunity) => (
                            <TableRow key={opportunity.id}>
                                {columns.map((column) => (
                                    <TableCell key={column.key} className={column.className}>
                                        {column.render(opportunity)}
                                    </TableCell>
                                ))}
                                {showActions && (
                                    <TableCell className="text-right">
                                        <DropdownActions
                                            actions={getActionsForOpportunity(context, opportunity)}
                                        />
                                    </TableCell>
                                )}
                            </TableRow>
                        ))
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
