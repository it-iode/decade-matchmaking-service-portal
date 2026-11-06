import {Head} from '@inertiajs/react';

import {SidebarLayout} from '@layouts/index'

import {NotificationList, PaginationLinkProps} from '@/types';
import {NotificationsDataTable} from '@ui/organisms/data-table/notifications';
import React from "react";
import {PageHeader} from "@ui/molecules/page-header";

interface NotificationPagination {
    current_page: number;
    data: NotificationList,
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

interface NotificationListPageProps {
    notifications: NotificationPagination;
    currentSort: {
        field: string;
        order: string;
    };
}

export default function NotificationListPage({notifications, currentSort}: Readonly<NotificationListPageProps>) {

    return (
        <SidebarLayout>
            <Head title="Notifications"/>
            <PageHeader title="Notifications List" />
            <div className="py-8">
                <NotificationsDataTable
                    notifications={notifications.data}
                    currentSort={currentSort}
                    pagination={{
                        current_page: notifications.current_page,
                        last_page: notifications.last_page,
                        links: notifications.links,
                        prev_page_url: notifications.prev_page_url,
                        next_page_url: notifications.next_page_url,
                        from: notifications.from,
                        to: notifications.to,
                        total: notifications.total
                    }}
                />
            </div>
        </SidebarLayout>
    );
}
