import React from 'react';
import { Table, TableBody, TableRow, TableCell } from '@ui/primitives/table';
import { Badge } from '@ui/primitives/badge';
import { Heading } from '@ui/primitives/heading';
import { Divider } from '@ui/primitives/divider';
import { OCDRequest } from '@/types';
import { requestFormFields } from '@features/requests/config';

interface RequestDetailsCardProps {
    request: OCDRequest;
}

const RANKED_POS = ['Primary', 'Secondary', 'Tertiary'] as const;

// Render the ranked decade_challenges object ({primary, secondary, tertiary} of {value,label}|null)
const renderRankedChallenges = (value: any) => {
    const ranks = ['primary', 'secondary', 'tertiary'] as const;
    const entries = ranks
        .map((rank, index) => ({rank: RANKED_POS[index], entry: value?.[rank]}))
        .filter((r) => r.entry && (r.entry.label || r.entry.value));

    if (entries.length === 0) {
        return <span className="text-gray-400 dark:text-gray-500">None specified</span>;
    }

    return (
        <div className="flex flex-wrap gap-2">
            {entries.map(({rank, entry}) => (
                <Badge key={rank} color="blue">
                    {rank}: {entry.label || entry.value}
                </Badge>
            ))}
        </div>
    );
};

const isRankedChallenges = (value: any): boolean =>
    !!value && typeof value === 'object' && !Array.isArray(value) &&
    ('primary' in value || 'secondary' in value || 'tertiary' in value);

// Helper function to render field values
const renderFieldValue = (value: any, fieldKey: string) => {
    if (isRankedChallenges(value)) {
        return renderRankedChallenges(value);
    }

    if (Array.isArray(value)) {
        if (value.length === 0) {
            return <span className="text-gray-400 dark:text-gray-500">None specified</span>;
        }

        return (
            <div className="flex flex-wrap gap-2">
                {value.map((item, index) => (
                    <Badge key={`${fieldKey}-${item.label || item}-${index}`} color="blue">
                        {item.label || item}
                    </Badge>
                ))}
            </div>
        );
    }

    if (value === undefined || value === '' || value === null) {
        return <span className="text-gray-400 dark:text-gray-500">N/A</span>;
    }

    // For long text values, preserve formatting
    if (typeof value === 'string' && value.length > 100) {
        return (
            <span className="text-gray-900 dark:text-gray-100 whitespace-pre-wrap break-words">
                {value}
            </span>
        );
    }

    return <span className="text-gray-900 dark:text-gray-100">{value}</span>;
};

export function RequestDetailsCard({ request }: RequestDetailsCardProps) {
    return (
        <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <div className="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <Heading level={3} className="text-lg font-semibold text-gray-900 dark:text-gray-100">
                    Request Details
                </Heading>
            </div>

            <div className="p-6 space-y-8">
                {requestFormFields.map((step, stepIndex) => {
                    // Filter out fields that should not be shown
                    const fields = Object.entries(step.fields).filter(([key, field]) => {
                        if (!field.label || field.type === 'hidden') return false;
                        if (field.show && !field.show(request)) return false;
                        const value = (request.detail as any)[key];
                        return !(value === undefined || value === '');
                    });

                    if (fields.length === 0) return null;

                    return (
                        <div key={step.label}>
                            {stepIndex > 0 && <Divider className="mb-8" />}

                            <div className="mb-4">
                                <h4 className="text-base font-semibold text-gray-900 dark:text-gray-100">
                                    {step.label}
                                </h4>
                            </div>

                            <Table className="[--gutter:theme(spacing.6)] sm:[--gutter:theme(spacing.8)]">
                                <TableBody>
                                    {fields.map(([key, field]) => {
                                        const value = (request.detail as any)[key];
                                        return (
                                            <TableRow key={key}>
                                                <TableCell className="font-medium text-gray-700 dark:text-gray-300 align-top w-1/3">
                                                    <span className="text-wrap"> {field.label}</span>
                                                </TableCell>
                                                <TableCell className="align-top">
                                                    {renderFieldValue(value, key)}
                                                </TableCell>
                                            </TableRow>
                                        );
                                    })}
                                </TableBody>
                            </Table>
                        </div>
                    );
                })}
            </div>
        </div>
    );
}
