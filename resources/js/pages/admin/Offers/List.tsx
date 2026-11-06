import React from 'react';
import {Head} from '@inertiajs/react';
import {SidebarLayout} from '@layouts/index'
import {RequestOfferList, PaginationLinkProps} from '@/types';
import {OffersDataTable} from '@ui/organisms/data-table/offers';
import {PlusIcon} from '@heroicons/react/16/solid';
import {PageHeader} from "@ui/molecules/page-header";

interface OffersPagination {
    current_page: number;
    data: RequestOfferList,
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

interface OffersListPageProps {
    offers: OffersPagination;
    currentSort: {
        field: string;
        order: string;
    };
    routeName: string;
    currentSearch?: Record<string, string>;
    searchFieldsOptions?: {
        requests?: { value: string; label: string }[];
    }
}

export default function OffersList({
                                       offers,
                                       currentSort,
                                       routeName,
                                       currentSearch = {},
                                       searchFieldsOptions = {}
                                   }: Readonly<OffersListPageProps>) {
    return (
        <SidebarLayout>
            <Head title="Manage Offers"/>

            <div className="space-y-6">
                <PageHeader
                    title="Manage Offers"
                    subtitle="View and manage all capacity development offers"
                    actions={{
                        id: 'create',
                        label: 'Create New Offer',
                        icon: PlusIcon,
                        href: route('admin.offer.create')
                    }}
                />

                {/* Data Table */}
                <div className="">
                    <OffersDataTable
                        offers={offers.data}
                        currentSort={currentSort}
                        currentSearch={currentSearch}
                        routeName={routeName}
                        pagination={{
                            current_page: offers.current_page,
                            last_page: offers.last_page,
                            links: offers.links,
                            prev_page_url: offers.prev_page_url,
                            next_page_url: offers.next_page_url,
                            from: offers.from,
                            to: offers.to,
                            total: offers.total
                        }}
                        searchFields={[
                            {
                                id: 'description',
                                type: 'text',
                                label: 'Description',
                                placeholder: 'Search by offer description...'
                            },
                            {
                                id: 'partner',
                                type: 'text',
                                label: 'Partner',
                                placeholder: 'Search by partner name...'
                            },
                            {
                                id: 'request',
                                label: 'Request',
                                placeholder: 'Search by request title...',
                                type: 'select',
                                options: searchFieldsOptions.requests || []
                            }
                        ]}
                    />
                </div>
            </div>
        </SidebarLayout>
    );
}
