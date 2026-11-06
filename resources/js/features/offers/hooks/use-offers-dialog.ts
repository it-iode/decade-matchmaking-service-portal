import { useState } from 'react';
import { OCDRequest } from '@/types';

interface UseOffersDialogReturn {
    offersDialogVisible: boolean;
    selectedRequest: OCDRequest | null;
    openOffersDialog: (request: OCDRequest) => void;
    closeOffersDialog: () => void;
}

export function useOffersDialog(): UseOffersDialogReturn {
    const [offersDialogVisible, setOffersDialogVisible] = useState(false);
    const [selectedRequest, setSelectedRequest] = useState<OCDRequest | null>(null);

    const openOffersDialog = (request: OCDRequest) => {
        setSelectedRequest(request);
        setOffersDialogVisible(true);
    };

    const closeOffersDialog = () => {
        setOffersDialogVisible(false);
        setSelectedRequest(null);
    };

    return {
        offersDialogVisible,
        selectedRequest,
        openOffersDialog,
        closeOffersDialog,
    };
} 