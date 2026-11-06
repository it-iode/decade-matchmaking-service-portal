import React from 'react';
import { Organization, OrganizationsList } from '@/types';
import { Table, TableHead, TableBody, TableRow, TableHeader, TableCell } from '@ui/primitives/table';

interface OrganizationsDataTableProps {
    organizations: OrganizationsList;
}

export function OrganizationsDataTable({ organizations }: Readonly<OrganizationsDataTableProps>) {
    if (organizations.length === 0) {
        return (
            <div className="text-center py-8 text-gray-500">
                <p>No organizations found.</p>
            </div>
        );
    }

    return (
        <div className="[&_td]:whitespace-normal [&_td]:break-words [&_th]:whitespace-nowrap">
            <Table bleed className="[--gutter:--spacing(6)] sm:[--gutter:--spacing(8)]">
                <TableHead>
                    <TableRow>
                        <TableHeader className="w-1/5">Name</TableHeader>
                        <TableHeader className="w-3/5">Description</TableHeader>
                        <TableHeader className="w-1/5">Website</TableHeader>
                    </TableRow>
                </TableHead>
                <TableBody>
                    {organizations.map((organization) => (
                        <TableRow key={organization.id}>
                            <TableCell className="font-medium">
                                {organization.name}
                            </TableCell>
                            <TableCell>
                                {organization.description ? (
                                    <span className="text-sm text-gray-600 leading-relaxed">
                                        {organization.description}
                                    </span>
                                ) : (
                                    <span className="text-sm text-gray-400 italic">No description</span>
                                )}
                            </TableCell>
                            <TableCell>
                                {organization.website ? (
                                    <a
                                        href={organization.website}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="text-blue-600 hover:text-blue-800 underline text-sm whitespace-nowrap"
                                    >
                                        Visit Website
                                    </a>
                                ) : (
                                    <span className="text-sm text-gray-400 italic">No website</span>
                                )}
                            </TableCell>
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
        </div>
    );
}
