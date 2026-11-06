import React from 'react';
import { Document } from '@/types';
import { DownloadButton } from '@/components/ui/download-button';
import { Badge } from '@ui/primitives/badge';
import { TrashIcon, ArrowDownTrayIcon } from '@heroicons/react/16/solid';

interface DocumentListProps {
    documents: Document[];
}

export function DocumentList({ documents}: DocumentListProps) {
    if (documents.length === 0) {
        return (
            <div className="text-center py-8 px-4 bg-white dark:bg-gray-800 rounded-lg border border-blue-200 dark:border-blue-700">
                <p className="text-sm text-gray-500 dark:text-gray-400">No documents uploaded yet.</p>
            </div>
        );
    }

    const handleDownloadError = (documentName: string) => (error: Error) => {
        console.error(`Failed to download ${documentName}:`, error);
    };

    return (
        <div className="space-y-3">
            {documents.map((document) => (
                <div
                    key={document.id}
                    className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 p-4 border border-blue-200 dark:border-blue-700 rounded-lg bg-white dark:bg-gray-800"
                >
                    <div className="flex-1 min-w-0">
                        <p className="text-sm font-medium text-gray-900 dark:text-white truncate">{document.name}</p>
                        <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Uploaded {new Date(document.created_at).toLocaleDateString()}
                        </p>
                        {document.document_type && (
                            <Badge color="blue" className="mt-2">
                                {document.document_type.label.toUpperCase()}
                            </Badge>
                        )}
                    </div>

                    <div className="flex items-center gap-2 w-full sm:w-auto">
                        <DownloadButton
                            url={route('user.document.download', document.id)}
                            fileName={document.name}
                            outline
                            className="flex-1 sm:flex-initial"
                            onDownloadError={handleDownloadError(document.name)}
                        >
                            <ArrowDownTrayIcon data-slot="icon" />
                            Download
                        </DownloadButton>

                    </div>
                </div>
            ))}
        </div>
    );
}
