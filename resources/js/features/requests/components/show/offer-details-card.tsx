import React from 'react';
import {usePage} from '@inertiajs/react';
import {RequestOffer, Auth} from '@/types';
import {Heading} from '@ui/primitives/heading';
import {Divider} from '@ui/primitives/divider';
import {offerStatusBadgeRenderer} from '@shared/utils';
import {DocumentList} from './components/document-list';
import {OfferInfoSection} from './components/offer-info-section';
import {OfferActionButtons} from '@features/offers/components/offer-action-buttons';

interface OfferDetailsCardProps {
    offer: RequestOffer;
}

export function OfferDetailsCard({offer}: OfferDetailsCardProps) {
    const {auth} = usePage<{ auth: Auth }>().props;

    return (
        <div className="bg-blue-50 dark:bg-blue-900/10 rounded-lg border-2 border-blue-200 dark:border-blue-800">
            {/* Header with prominent badge */}
            <div className="px-6 py-4 border-b border-blue-200 dark:border-blue-800 bg-blue-100 dark:bg-blue-900/20">
                <div className="flex items-center justify-between flex-wrap gap-3">
                    <Heading level={3} className="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        Active Offer
                    </Heading>
                    {offerStatusBadgeRenderer(offer)}
                </div>
            </div>

            <div className="p-6 space-y-6">
                {/* Partner Information & Offer Details */}
                <OfferInfoSection offer={offer}/>

                {/* Offer Actions */}
                {offer.actions && offer.actions.length > 0 && (
                    <>
                        <Divider/>
                        <div>
                            <h4 className="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3">Offer
                                Actions</h4>
                            <OfferActionButtons offer={offer} layout="horizontal"/>
                        </div>
                    </>
                )}
                <Divider/>
                {/* Documents Section */}
                <div>
                    <h4 className="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4">Documents</h4>
                    {/* Document List */}
                    <DocumentList documents={offer.documents} />
                </div>
            </div>
        </div>
    );
}
