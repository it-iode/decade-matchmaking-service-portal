import React from 'react';
import { ChevronDownIcon, ChevronUpIcon } from '@heroicons/react/16/solid';
import { Invitation, InvitationsList } from '@/features/invitations';
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

type SortField = 'id' | 'name' | 'email' | 'expires_at' | 'created_at';

interface TableColumn {
    key: string;
    label: string;
    sortable?: boolean;
    sortField?: SortField;
    render: (invitation: Invitation) => React.ReactNode;
    className?: string;
    headerClassName?: string;
}

interface InvitationsDataTableProps {
    invitations: InvitationsList;
    currentSort: {
        field: string;
        order: string;
    };
    currentSearch?: Record<string, string>;
    pagination?: PaginationData;
    searchFields?: UIField[];
    columns: TableColumn[];
    routeName?: string;
    getActionsForInvitation: (invitation: Invitation) => Action[];
    showSearch?: boolean;
    showActions?: boolean;
}

export function InvitationsDataTable({
    invitations,
    currentSort,
    currentSearch = {},
    pagination,
    searchFields = [],
    columns,
    routeName = 'admin.invitations.index',
    getActionsForInvitation,
    showSearch = true,
    showActions = true,
}: Readonly<InvitationsDataTableProps>) {

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
                    {invitations.length === 0 ? (
                        <TableRow>
                            <TableCell colSpan={totalColumns} className="text-center text-zinc-500 py-8">
                                No invitations found.
                            </TableCell>
                        </TableRow>
                    ) : (
                        invitations.map((invitation: Invitation) => {
                            const actions = getActionsForInvitation(invitation);
                            return (
                                <TableRow key={invitation.id}>
                                    {columns.map((column) => (
                                        <TableCell key={column.key} className={column.className}>
                                            {column.render(invitation)}
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
