import React from 'react';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@ui/primitives/table';
import { ENTITY_LABELS, type SampleDataEntityCounts } from '../types';

interface EntityCountsTableProps {
    kept: SampleDataEntityCounts;
    erased: SampleDataEntityCounts;
}

/**
 * Kept vs erased counts per entity, shared by the dry-run plan and the
 * post-run summary.
 */
export function EntityCountsTable({ kept, erased }: Readonly<EntityCountsTableProps>) {
    return (
        <Table dense className="[--gutter:--spacing(4)]">
            <TableHead>
                <TableRow>
                    <TableHeader>Entity</TableHeader>
                    <TableHeader className="text-right">Kept</TableHeader>
                    <TableHeader className="text-right">Erased</TableHeader>
                </TableRow>
            </TableHead>
            <TableBody>
                {(Object.keys(ENTITY_LABELS) as Array<keyof SampleDataEntityCounts>).map((key) => (
                    <TableRow key={key}>
                        <TableCell className="font-medium">{ENTITY_LABELS[key]}</TableCell>
                        <TableCell className="text-right tabular-nums text-green-700 dark:text-green-400">
                            {kept[key]}
                        </TableCell>
                        <TableCell className="text-right tabular-nums text-red-700 dark:text-red-400">
                            {erased[key]}
                        </TableCell>
                    </TableRow>
                ))}
            </TableBody>
        </Table>
    );
}
