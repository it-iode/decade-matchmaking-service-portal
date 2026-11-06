import React from 'react';
import { IOCPlatform, IOCPlatformsList } from '@/types';
import { Table, TableHead, TableBody, TableRow, TableHeader, TableCell } from '@ui/primitives/table';

interface IOCPlatformsDataTableProps {
    platforms: IOCPlatformsList;
}

export function IOCPlatformsDataTable({ platforms }: Readonly<IOCPlatformsDataTableProps>) {
    if (platforms.length === 0) {
        return (
            <div className="text-center py-8 text-gray-500">
                <p>No IOC platforms found.</p>
            </div>
        );
    }

    return (
        <div className="[&_td]:whitespace-normal [&_td]:break-words [&_th]:whitespace-nowrap">
            <Table bleed className="[--gutter:--spacing(6)] sm:[--gutter:--spacing(8)]">
                <TableHead>
                    <TableRow>
                        <TableHeader className="w-1/5">Name</TableHeader>
                        <TableHeader className="w-2/5">Description</TableHeader>
                        <TableHeader className="w-1/5">Link</TableHeader>
                        <TableHeader className="w-1/5">Contact</TableHeader>
                    </TableRow>
                </TableHead>
                <TableBody>
                    {platforms.map((platform) => (
                        <TableRow key={platform.id}>
                            <TableCell className="font-medium">
                                {platform.name}
                            </TableCell>
                            <TableCell>
                                {platform.description ? (
                                    <span className="text-sm text-gray-600 leading-relaxed">
                                        {platform.description}
                                    </span>
                                ) : (
                                    <span className="text-sm text-gray-400 italic">No description</span>
                                )}
                            </TableCell>
                            <TableCell>
                                {platform.link ? (
                                    <a
                                        href={platform.link}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="text-blue-600 hover:text-blue-800 underline text-sm whitespace-nowrap"
                                    >
                                        Visit Platform
                                    </a>
                                ) : (
                                    <span className="text-sm text-gray-400 italic">No link</span>
                                )}
                            </TableCell>
                            <TableCell>
                                {platform.contact ? (
                                    <span className="text-sm text-gray-600">
                                        {platform.contact}
                                    </span>
                                ) : (
                                    <span className="text-sm text-gray-400 italic">No contact</span>
                                )}
                            </TableCell>
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
        </div>
    );
}