import React, {useState} from 'react';
import axios from 'axios';
import {CheckBadgeIcon, ChevronDownIcon, ChevronUpIcon} from '@heroicons/react/16/solid';
import {Notification, NotificationList, PaginationLinkProps} from '@/types';
import {NotificationDialogView} from '@ui/organisms/data-table/notifications';
import {TablePaginationNav} from '@ui/molecules';
import {DataTableActionsColumn, DataTableAction} from '@ui/organisms/data-table/common';
import {Table, TableHead, TableBody, TableRow, TableHeader, TableCell} from '@ui/primitives/table';
import {router} from '@inertiajs/react';
import {formatDate} from '@shared/utils';

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

type SortField = 'title' | 'description' | 'created_at' | 'is_read';

interface NotificationsDataTableProps {
    notifications: NotificationList;
    currentSort: {
        field: string;
        order: string;
    };
    pagination?: PaginationData;
}

export function NotificationsDataTable({
                                           notifications,
                                           currentSort,
                                           pagination
                                       }: Readonly<NotificationsDataTableProps>) {

    const [isDialogOpen, setIsDialogOpen] = useState(false);
    const [selectedNotification, setSelectedNotification] = useState<Notification | null>(null);

    const handleSort = (field: SortField) => {
        const newOrder = currentSort.field === field && currentSort.order === 'asc' ? 'desc' : 'asc';
        router.get(route('admin.notifications.index'), {
            sort: field,
            order: newOrder
        }, {
            preserveState: false,
            preserveScroll: true
        });
    };

    const getSortIcon = (field: SortField) => {
        if (currentSort.field !== field) {
            return <ChevronDownIcon className="size-4 opacity-50"/>;
        }
        return currentSort.order === 'asc'
            ? <ChevronUpIcon className="size-4"/>
            : <ChevronDownIcon className="size-4"/>;
    };
    const onViewDetails = (id: number) => {
        const notification = notifications.find(n => n.id === id);
        if (notification) {
            setSelectedNotification(notification);
            setIsDialogOpen(true);
        }
    };

    const closeDialog = () => {
        setIsDialogOpen(false);
        setSelectedNotification(null);
    };

    const markAsRead = (id: number) => {
        axios.get(route('admin.notifications.read', id)).then(() => {
            // Refresh the current page to get updated data from server
            router.reload();
        });
    };

    const getActionsForNotification = (notification: Notification): DataTableAction<Notification>[] => {
        return [
            {
                key: 'mark-as-read',
                label: 'Mark as Read',
                onClick: (row) => markAsRead(row.id)
            },
            {
                key: 'view-details',
                label: 'View more Details',
                onClick: (row) => onViewDetails(row.id)
            }
        ];
    };

    return (
        <div>
            <Table>
                <TableHead>
                    <TableRow>
                        <TableHeader>
                            <button
                                onClick={() => handleSort('is_read')}
                                className="flex items-center gap-1 font-semibold hover:text-gray-600 dark:hover:text-gray-300"
                            >
                                Status
                                {getSortIcon('is_read')}
                            </button>
                        </TableHeader>
                        <TableHeader>
                            Title
                        </TableHeader>
                        <TableHeader className="hidden sm:table-cell">
                            Description
                        </TableHeader>
                        <TableHeader>
                            <button
                                onClick={() => handleSort('created_at')}
                                className="flex items-center gap-1 font-semibold hover:text-gray-600 dark:hover:text-gray-300"
                            >
                                Date
                                {getSortIcon('created_at')}
                            </button>
                        </TableHeader>
                        <TableHeader className="text-right">Actions</TableHeader>
                    </TableRow>
                </TableHead>
                <TableBody>
                    {notifications.length === 0 ? (
                        <TableRow>
                            <TableCell colSpan={5} className="text-center text-zinc-500 py-8">
                                No notifications found.
                            </TableCell>
                        </TableRow>
                    ) : (
                        notifications.map((notification) => (
                            <TableRow key={notification.id}>
                                <TableCell>
                                    <div className="flex items-center">
                                        {notification.is_read ? (
                                            <CheckBadgeIcon
                                                data-slot="icon"
                                                className="size-5 text-green-500"
                                                title="Read"
                                            />
                                        ) : (
                                            <div className="size-2 rounded-full bg-blue-500" title="Unread"/>
                                        )}
                                    </div>
                                </TableCell>
                                <TableCell className="font-medium">
                                    {notification.title}
                                </TableCell>
                                <TableCell className="text-zinc-600 dark:text-zinc-400 hidden sm:table-cell">

                                    <div dangerouslySetInnerHTML={{ __html: notification.description }} />
                                </TableCell>
                                <TableCell className="text-zinc-500">
                                    {formatDate(notification.created_at)}
                                </TableCell>
                                <TableCell className="text-right column-actions">
                                    <DataTableActionsColumn
                                        row={notification}
                                        actions={getActionsForNotification(notification)}
                                    />
                                </TableCell>
                            </TableRow>
                        ))
                    )}
                </TableBody>
            </Table>

            {/* Render pagination if provided */}
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

            <NotificationDialogView
                isOpen={isDialogOpen}
                onClose={closeDialog}
                notification={selectedNotification}
            />
        </div>
    );
}
