import React from 'react';
import {ChevronDownIcon, ChevronUpIcon} from '@heroicons/react/16/solid';
import {RequestOffer, RequestOfferList, PaginationLinkProps, UIField} from "@/types";
import {Table, TableHead, TableBody, TableRow, TableHeader, TableCell} from '@ui/primitives/table';
import {Badge} from '@ui/primitives/badge'
import {TablePaginationNav} from '@ui/molecules';
import {formatDate, offerStatusBadgeRenderer} from '@shared/utils';
import {router} from '@inertiajs/react';
import {TableSearch} from '@ui/organisms/data-table/search';
import {OfferDropdownActions} from '@features/offers/components';

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

type SortField = 'id' | 'created_at' | 'status' | 'matched_partner_id' | 'request_id';

interface DataTableSearchFields {
    key: string;
    label: string;
    placeholder: string;
    type?: 'text' | 'select' | 'date' | 'number';
    options?: { value: string; label: string }[]; // For select fields
}

interface TableColumn {
    key: string;
    label: string;
    sortable?: boolean;
    sortField?: SortField;
    render: (offer: RequestOffer) => React.ReactNode;
    className?: string;
    headerClassName?: string;
}

interface OffersDataTableProps {
    offers: RequestOfferList;
    currentSort: {
        field: string;
        order: string;
    };
    currentSearch?: Record<string, string>;
    pagination?: PaginationData;
    searchFields?: UIField[];
    columns?: TableColumn[];
    routeName?: string;
    showSearch?: boolean;
    showActions?: boolean;
}

// Utility Functions

export function OffersDataTable({
                                    offers,
                                    currentSort,
                                    currentSearch = {},
                                    pagination,
                                    searchFields = [],
                                    columns,
                                    routeName = 'admin.offer.list',
                                    showSearch = true,
                                    showActions = true
                                }: Readonly<OffersDataTableProps>) {

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

    // Default columns for admin interface
    const defaultColumns: TableColumn[] = [
        {
            key: 'id',
            label: 'ID',
            sortable: true,
            sortField: 'id',
            render: (offer) => (
                <span className="font-medium">{offer.id}</span>
            )
        },
        {
            key: 'request',
            label: 'Request',
            sortable: true,
            sortField: 'request_id',
            render: (offer) => (
                <div className="max-w-xs">
                    <span className="text-sm font-medium line-clamp-2">
                        {offer.request?.detail?.capacity_development_title || 'No Title'}
                    </span>
                    <span className="text-xs text-zinc-500 block">
                        ID: {offer.request.id}
                    </span>
                </div>
            )
        },
        {
            key: 'partner',
            label: 'Partner',
            sortable: true,
            sortField: 'matched_partner_id',
            render: (offer) => (
                <div>
                    <span className="font-medium">{offer.matched_partner?.name || 'Unknown'}</span>
                    <span className="text-xs text-zinc-500 block">
                        {offer.matched_partner?.email}
                    </span>
                </div>
            )
        },
        {
            key: 'description',
            label: 'Description',
            render: (offer) => (
                <div className="max-w-sm">
                    <span className="text-sm line-clamp-3">
                        {offer.description}
                    </span>
                </div>
            )
        },
        {
            key: 'is_accepted',
            label: 'Is Accepted',
            sortable: false,
            render: (offer) => (
                <div>
                    {(offer.is_accepted ? <span className="text-green-500 font-semibold">Yes</span> :
                        <span className="text-red-500 font-semibold">No</span>)}
                </div>
            )
        },
        {
            key: 'status',
            label: 'Status',
            sortable: true,
            sortField: 'status',
            render: (offer) => offerStatusBadgeRenderer(offer)
        },
        {
            key: 'created_at',
            label: 'Created At',
            sortable: true,
            sortField: 'created_at',
            render: (offer) => (
                <span className="text-zinc-500">{formatDate(offer.created_at)}</span>
            )
        }
    ];

    const activeColumns = columns || defaultColumns;

    // Helper Functions
    const getSortIcon = (field: SortField) => {
        if (currentSort.field !== field) {
            return <ChevronDownIcon className="size-4 opacity-50"/>;
        }
        return currentSort.order === 'asc'
            ? <ChevronUpIcon className="size-4"/>
            : <ChevronDownIcon className="size-4"/>;
    };
    const totalColumns = activeColumns.length + (showActions ? 1 : 0);

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

            <Table>
                <TableHead className="text-lg">
                    <TableRow>
                        {activeColumns.map((column) => (
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
                <TableBody className="text-lg">
                    {offers.length === 0 ? (
                        <TableRow>
                            <TableCell colSpan={totalColumns} className="text-center text-zinc-500 py-8">
                                No offers found.
                            </TableCell>
                        </TableRow>
                    ) : (
                        offers.map((offer: RequestOffer) => (
                            <TableRow key={offer.id}>
                                {activeColumns.map((column) => (
                                    <TableCell key={column.key} className={column.className}>
                                        {column.render(offer)}
                                    </TableCell>
                                ))}
                                {showActions && (
                                    <TableCell className="text-right">
                                        <OfferDropdownActions offer={offer} />
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
