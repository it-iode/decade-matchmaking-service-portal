/**
 * OfferDocumentUpload Component
 *
 * Simplified component for uploading a single document to an offer.
 * Uses the FileUploadDialog for consistent upload experience.
 */

import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import { FileUploadDialog } from '@/components/dialogs/FileUploadDialog';
import { Button } from '@ui/primitives/button';
import { DocumentArrowUpIcon } from '@heroicons/react/16/solid';
import type { EntityAction } from '@/types/actions';

export interface OfferDocumentUploadProps {
    offerId: number;
    documentType: string;
    label?: string;
    description?: string;
}

/**
 * Simplified component for uploading documents to offers.
 * Reusable for different document types based on user role.
 */
export function OfferDocumentUpload({
    offerId,
    documentType,
    label = 'Upload Document',
    description,
}: Readonly<OfferDocumentUploadProps>) {
    const [showUploadDialog, setShowUploadDialog] = useState(false);

    // Create a mock action object for FileUploadDialog
    // This follows the same structure as the backend action provider
    const uploadAction: EntityAction = {
        key: `upload_${documentType}`,
        label: label,
        route: route('offer.documents.upload', { id: offerId, type: documentType }),
        method: 'POST',
        enabled: true,
        style: {
            icon: 'document-arrow-up',
            variant: 'outline',
            color: 'blue',
        },
        metadata: {
            handler: 'file_upload',
            file_upload: {
                accept: getAcceptedFileTypes(documentType),
                maxSize: 10, // 10MB
                multiple: false,
                endpoint: route('offer.documents.upload', { id: offerId, type: documentType }),
                documentType: documentType,
                title: label,
                description: description || getDefaultDescription(documentType),
                validationRules: {
                    required: true,
                    mimeTypes: getMimeTypes(documentType),
                    maxSizeBytes: 10485760, // 10MB
                },
            },
        },
    };

    return (
        <>
            <Button
                outline
                onClick={() => setShowUploadDialog(true)}
                className="flex items-center gap-2"
            >
                <DocumentArrowUpIcon data-slot="icon" />
                {label}
            </Button>

            <FileUploadDialog
                isOpen={showUploadDialog}
                onClose={() => setShowUploadDialog(false)}
                action={uploadAction}
                onSuccess={() => {
                    router.reload();
                }}
            />
        </>
    );
}

/**
 * Get accepted file types based on document type
 */
function getAcceptedFileTypes(documentType: string): string {
    switch (documentType) {
        case 'financial_breakdown':
            return '.pdf,.xlsx,.xls,.csv';
        case 'lesson_learned':
            return '.pdf,.docx,.doc';
        case 'offer_document':
            return '.pdf,.docx,.doc,.xlsx,.xls';
        default:
            return '.pdf';
    }
}

/**
 * Get MIME types based on document type
 */
function getMimeTypes(documentType: string): string[] {
    switch (documentType) {
        case 'financial_breakdown':
            return [
                'application/pdf',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'text/csv',
            ];
        case 'lesson_learned':
            return [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ];
        case 'offer_document':
            return [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ];
        default:
            return ['application/pdf'];
    }
}

/**
 * Get default description based on document type
 */
function getDefaultDescription(documentType: string): string {
    switch (documentType) {
        case 'financial_breakdown':
            return 'Upload a detailed financial breakdown document (PDF, Excel, or CSV format)';
        case 'lesson_learned':
            return 'Upload a lessons learned report (PDF or Word format)';
        case 'offer_document':
            return 'Upload an offer document (PDF, Word, or Excel format)';
        default:
            return 'Upload a document';
    }
}
