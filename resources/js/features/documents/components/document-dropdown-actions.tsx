import React from 'react';
import { DropdownActions } from '@ui/organisms/data-table/common';
import type { Document } from '@/types';

interface DocumentDropdownActionsProps {
    document: Document;
}

/**
 * DocumentDropdownActions - Entity-specific dropdown for document actions
 *
 * Encapsulates action dropdown for documents (download, delete).
 * Documents don't require dialogs - confirmations are handled via action metadata.
 */
export function DocumentDropdownActions({ document }: Readonly<DocumentDropdownActionsProps>) {
    // Documents use simple actions (download/delete) with browser confirmations
    // No custom dialog state needed

    return (
        <DropdownActions actions={document.actions || []} />
    );
}
