import React from 'react';
import { Dialog, DialogActions, DialogBody, DialogDescription, DialogTitle } from '@ui/primitives/dialog';
import { Button } from '@ui/primitives/button';
import { Table, TableHead, TableBody, TableRow, TableHeader, TableCell } from '@ui/primitives/table';
import { Badge } from '@ui/primitives/badge';
import { Link } from '@inertiajs/react';
import { Opportunity } from '../types/opportunity.types';

interface OpportunitiesDialogProps {
    open: boolean;
    onClose: () => void;
    opportunities: Opportunity[];
}

interface OpportunityCardProps {
    opportunity: Opportunity;
    formatDate: (dateString: string) => string;
    getDaysRemaining: (closingDate: string) => number;
    isClosingSoon: (closingDate: string) => boolean;
}

function OpportunityCard({ opportunity, formatDate, getDaysRemaining, isClosingSoon }: OpportunityCardProps) {
    return (
        <div className="rounded-lg border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <div className="flex items-start justify-between gap-3">
                <div className="min-w-0 flex-1">
                    <Link
                        href={route('opportunity.show', opportunity.id)}
                        className="font-medium text-blue-600 hover:text-blue-500 hover:underline dark:text-blue-400 dark:hover:text-blue-300"
                    >
                        <span className="line-clamp-2">{opportunity.title}</span>
                    </Link>
                </div>
                <Badge color="blue" className="shrink-0">
                    {opportunity.type.label}
                </Badge>
            </div>

            {opportunity.summary && (
                <p className="mt-2 text-sm text-zinc-500 line-clamp-3 dark:text-zinc-400">
                    {opportunity.summary}
                </p>
            )}

            <div className="mt-3 space-y-2 text-sm">
                <div className="flex items-center justify-between">
                    <span className="text-zinc-500 dark:text-zinc-400">Closing:</span>
                    <div className="text-right">
                        <span className="whitespace-nowrap">{formatDate(opportunity.closing_date)}</span>
                        <span className={`ml-2 ${isClosingSoon(opportunity.closing_date) ? 'text-red-500' : 'text-zinc-500 dark:text-zinc-400'}`}>
                            ({getDaysRemaining(opportunity.closing_date)} days)
                        </span>
                    </div>
                </div>

                {opportunity.implementation_location.length > 0 && (
                    <div>
                        <span className="text-zinc-500 dark:text-zinc-400">Location: </span>
                        <span className="text-zinc-700 dark:text-zinc-300">
                            {opportunity.implementation_location.map(loc => loc.label).join(', ')}
                        </span>
                    </div>
                )}

                {opportunity.target_audience.length > 0 && (
                    <div>
                        <span className="text-zinc-500 dark:text-zinc-400">Audience: </span>
                        <span className="text-zinc-700 dark:text-zinc-300">
                            {opportunity.target_audience.map(aud => aud.label).join(', ')}
                        </span>
                    </div>
                )}
            </div>

            <div className="mt-4">
                <Link
                    href={route('opportunity.show', opportunity.id)}
                    className="inline-flex items-center text-sm font-medium text-blue-600 hover:text-blue-500 hover:underline dark:text-blue-400 dark:hover:text-blue-300"
                >
                    View Details →
                </Link>
            </div>
        </div>
    );
}

export default function OpportunitiesDialog({ open, onClose, opportunities }: OpportunitiesDialogProps) {
    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString();
    };

    const getDaysRemaining = (closingDate: string) => {
        const today = new Date();
        const closing = new Date(closingDate);
        const diffTime = closing.getTime() - today.getTime();
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        return Math.max(0, diffDays);
    };

    const isClosingSoon = (closingDate: string) => {
        return getDaysRemaining(closingDate) <= 7;
    };

    return (
        <Dialog open={open} onClose={onClose} size="4xl">
            <DialogTitle>Recent Partner Opportunities</DialogTitle>
            <DialogDescription>
                Explore the latest capacity development opportunities from our partners.
            </DialogDescription>

            <DialogBody>
                {opportunities.length === 0 ? (
                    <div className="text-center py-12">
                        <div className="text-sm text-gray-600 dark:text-gray-400">No active opportunities available.</div>
                    </div>
                ) : (
                    <>
                        {/* Mobile cards - hidden on sm+ */}
                        <div className="sm:hidden space-y-4">
                            {opportunities.map((opportunity) => (
                                <OpportunityCard
                                    key={opportunity.id}
                                    opportunity={opportunity}
                                    formatDate={formatDate}
                                    getDaysRemaining={getDaysRemaining}
                                    isClosingSoon={isClosingSoon}
                                />
                            ))}
                        </div>

                        {/* Desktop table - hidden on mobile */}
                        <div className="hidden sm:block">
                            <Table className="[--gutter:--spacing(6)] sm:[--gutter:--spacing(8)]">
                                <TableHead>
                                    <TableRow>
                                        <TableHeader>Title</TableHeader>
                                        <TableHeader>Type</TableHeader>
                                        <TableHeader>Closing Date</TableHeader>
                                        <TableHeader className="hidden lg:table-cell">Location</TableHeader>
                                        <TableHeader className="hidden xl:table-cell">Target Audience</TableHeader>
                                        <TableHeader>Action</TableHeader>
                                    </TableRow>
                                </TableHead>
                                <TableBody>
                                    {opportunities.map((opportunity) => (
                                        <TableRow key={opportunity.id}>
                                            <TableCell>
                                                <div className="max-w-xs">
                                                    <Link
                                                        href={route('opportunity.show', opportunity.id)}
                                                        className="font-medium text-blue-600 hover:text-blue-500 hover:underline dark:text-blue-400 dark:hover:text-blue-300"
                                                    >
                                                        <span className="line-clamp-2">{opportunity.title}</span>
                                                    </Link>
                                                    {opportunity.summary && (
                                                        <p className="text-sm text-zinc-500 mt-1 line-clamp-2 dark:text-zinc-400">
                                                            {opportunity.summary}
                                                        </p>
                                                    )}
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <Badge color="blue">
                                                    {opportunity.type.label}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                <div className="whitespace-nowrap">
                                                    <div className="text-sm">
                                                        {formatDate(opportunity.closing_date)}
                                                    </div>
                                                    <div className={`text-xs ${isClosingSoon(opportunity.closing_date) ? 'text-red-500' : 'text-zinc-500 dark:text-zinc-400'}`}>
                                                        {getDaysRemaining(opportunity.closing_date)} days remaining
                                                    </div>
                                                </div>
                                            </TableCell>
                                            <TableCell className="hidden lg:table-cell">
                                                <div className="text-sm max-w-[200px]">
                                                    <div className="line-clamp-2">
                                                        {opportunity.implementation_location.map(loc => loc.label).join(', ')}
                                                    </div>
                                                </div>
                                            </TableCell>
                                            <TableCell className="hidden xl:table-cell">
                                                <div className="text-sm max-w-[200px]">
                                                    <div className="line-clamp-2">
                                                        {opportunity.target_audience.map(aud => aud.label).join(', ')}
                                                    </div>
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <Link
                                                    href={route('opportunity.show', opportunity.id)}
                                                    className="text-blue-600 hover:text-blue-500 text-sm hover:underline whitespace-nowrap dark:text-blue-400 dark:hover:text-blue-300"
                                                >
                                                    View Details
                                                </Link>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                    </>
                )}
            </DialogBody>

            <DialogActions>
                <Button color="firefly" onClick={onClose}>
                    Close
                </Button>
            </DialogActions>
        </Dialog>
    );
}
