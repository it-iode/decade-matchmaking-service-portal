import React from 'react';
import { RequestOffer } from '@/types';
import { Table, TableBody, TableRow, TableCell } from '@ui/primitives/table';
import { Divider } from '@ui/primitives/divider';

interface OfferInfoSectionProps {
    offer: RequestOffer;
}

export function OfferInfoSection({ offer }: OfferInfoSectionProps) {
    return (
        <>
            {/* Partner Information */}
            <div>
                <h4 className="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3">Partner Information</h4>
                <Table className="[--gutter:theme(spacing.6)] sm:[--gutter:theme(spacing.8)]">
                    <TableBody>
                        <TableRow>
                            <TableCell className="font-medium text-gray-700 dark:text-gray-300 w-1/3">
                                Partner Name
                            </TableCell>
                            <TableCell className="text-gray-900 dark:text-gray-100">
                                {offer.matched_partner?.name || 'Unknown Partner'}
                            </TableCell>
                        </TableRow>
                        {offer.matched_partner?.email && (
                            <TableRow>
                                <TableCell className="font-medium text-gray-700 dark:text-gray-300">Email</TableCell>
                                <TableCell className="text-gray-900 dark:text-gray-100">
                                    {offer.matched_partner.email}
                                </TableCell>
                            </TableRow>
                        )}
                        <TableRow>
                            <TableCell className="font-medium text-gray-700 dark:text-gray-300">Offer Date</TableCell>
                            <TableCell className="text-gray-900 dark:text-gray-100">
                                {new Date(offer.created_at).toLocaleDateString()}
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <Divider />

            {/* Offer Description */}
            <div>
                <h4 className="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-3">Offer Details</h4>
                <p className="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap leading-relaxed">
                    {offer.description}
                </p>
            </div>
        </>
    );
}
