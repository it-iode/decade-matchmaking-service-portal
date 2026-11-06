import React from 'react';
import { ChevronDownIcon, ChevronUpIcon } from '@heroicons/react/16/solid';
import { UserManagement, UsersPagination, PaginationLinkProps, UIField, UserAction } from '@/types';
import { Table, TableHead, TableBody, TableRow, TableHeader, TableCell } from '@ui/primitives/table';
import { TablePaginationNav } from '@ui/molecules';
import { router } from '@inertiajs/react';
import { TableSearch } from '@ui/organisms/data-table/search';
import { DropdownActions } from '@ui/organisms/data-table/common';

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
    render: (user: UserManagement) => React.ReactNode;
    className?: string;
    headerClassName?: string;
}

interface UsersDataTableProps {
    users: UserManagement[];
    currentSort: {
        field: string;
        order: string;
    };
    currentSearch?: Record<string, string>;
    pagination?: PaginationData;
    searchFields?: UIField[];
    columns: TableColumn[];
    routeName?: string;
    getActionsForUser: (user: UserManagement) => UserAction[];
    showSearch?: boolean;
    showActions?: boolean;
}

export function UsersDataTable({
    users,
    currentSort,
    currentSearch = {},
    pagination,
    searchFields = [],
    columns,
    routeName = 'admin.users.index',
    getActionsForUser,
    showSearch = true,
    showActions = true
}: Readonly<UsersDataTableProps>) {

    const handleSort = (field: string) => {
        const newOrder = currentSort.field === field && currentSort.order === 'asc' ? 'desc' : 'asc';
        router.get(route(routeName), {
            ...currentSearch,
            sort: field,
            direction: newOrder
        }, {
            preserveState: false,
            preserveScroll: true
        });
    };

    const getSortIcon = (field: string) => {
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

            <Table>
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
                                Actions
                            </TableHeader>
                        )}
                    </TableRow>
                </TableHead>
                <TableBody>
                    {users.length === 0 ? (
                        <TableRow>
                            <TableCell colSpan={totalColumns} className="text-center py-8 text-zinc-500">
                                No users found
                            </TableCell>
                        </TableRow>
                    ) : (
                        users.map((user) => (
                            <TableRow key={user.id}>
                                {columns.map((column) => (
                                    <TableCell key={column.key} className={column.className}>
                                        {column.render(user)}
                                    </TableCell>
                                ))}
                                {showActions && (
                                    <TableCell className="text-right">
                                        <DropdownActions actions={getActionsForUser(user)} />
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
