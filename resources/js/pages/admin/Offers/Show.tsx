import React from 'react';
import {Head} from '@inertiajs/react';
import {SidebarLayout} from '@layouts/index';
import {RequestOffer} from '@/types';
import {formatDate} from '@shared/utils';
import {
    OfferDetailsCard,
} from '@features/requests/components/show';
import {PageHeader} from "@ui/molecules/page-header";

interface ShowOfferPageProps {
    offer: RequestOffer;
}


export default function ShowOffer({offer}: Readonly<ShowOfferPageProps>) {

    return (
        <SidebarLayout>
            <Head title={`Offer #${offer.id}`}/>
            <PageHeader
                title={`Offer #${offer.id}`}
                subtitle={`Created ${formatDate(offer.created_at)}`}
            />
            <div className="py-8">
                <OfferDetailsCard offer={offer}/>
            </div>
        </SidebarLayout>
    );
}
